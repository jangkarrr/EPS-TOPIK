/**
 * Korean TTS Module
 * 2-layer voice system: Browser SpeechSynthesis (free) + Premium API (server-side)
 * 
 * Usage:
 *   KoreanTTS.speak('안녕하세요');
 *   KoreanTTS.speak('한국어', { rate: 0.75 });
 *   KoreanTTS.stop();
 */
const KoreanTTS = (() => {
    'use strict';

    // State
    let config = {
        provider: 'browser_tts',
        fallbackEnabled: true,
        defaultRate: 1.0,
        defaultPitch: 1.0,
        preference: 'uploaded_first',
        cacheEnabled: true,
        ttsEndpoint: '',
        lang: 'ko-KR'
    };
    let koreanVoice = null;
    let voicesLoaded = false;
    let currentAudio = null;       // HTMLAudioElement for file-based playback
    let isSpeaking = false;
    let activeButton = null;       // Currently active speaker button element
    const audioCache = new Map();  // Client-side URL cache

    // ─── Initialization ───────────────────────────────────────

    function init(userConfig = {}) {
        Object.assign(config, userConfig);
        loadKoreanVoice();
        // Voices may load async in some browsers
        if (window.speechSynthesis) {
            window.speechSynthesis.onvoiceschanged = loadKoreanVoice;
        }
    }

    function loadKoreanVoice() {
        if (!window.speechSynthesis) return;
        const voices = window.speechSynthesis.getVoices();
        // Prefer ko-KR voices, then any ko voice
        koreanVoice = voices.find(v => v.lang === 'ko-KR' && v.localService) ||
                      voices.find(v => v.lang === 'ko-KR') ||
                      voices.find(v => v.lang.startsWith('ko')) ||
                      null;
        voicesLoaded = voices.length > 0;
    }

    function isSupported() {
        return !!(window.speechSynthesis) || config.provider !== 'browser_tts';
    }

    function hasKoreanVoice() {
        loadKoreanVoice();
        return koreanVoice !== null;
    }

    // ─── Core Speak Method ────────────────────────────────────

    /**
     * Speak Korean text using the best available method
     * @param {string} text - Korean text to speak
     * @param {Object} opts - Options
     * @param {string} opts.audioUrl - Pre-resolved audio file URL (uploaded/generated)
     * @param {number} opts.rate - Speech rate (0.5-2.0)
     * @param {number} opts.pitch - Speech pitch (0.5-2.0)
     * @param {string} opts.type - Audio source type: 'uploaded'|'generated'|'browser_tts'
     * @param {HTMLElement} opts.button - The speaker button element (for state management)
     * @param {Function} opts.onStart - Callback when playback starts
     * @param {Function} opts.onEnd - Callback when playback ends
     * @param {Function} opts.onError - Callback on error
     */
    function speak(text, opts = {}) {
        if (!text || !text.trim()) return;

        // Stop any current playback first
        stop();

        const rate = opts.rate || config.defaultRate;
        const pitch = opts.pitch || config.defaultPitch;
        const audioUrl = opts.audioUrl || null;
        const type = opts.type || 'browser_tts';

        // Track active button
        if (opts.button) {
            activeButton = opts.button;
            setButtonState(activeButton, 'playing');
        }

        isSpeaking = true;

        const onEnd = () => {
            isSpeaking = false;
            if (activeButton) setButtonState(activeButton, 'idle');
            activeButton = null;
            if (opts.onEnd) opts.onEnd();
        };

        const onError = (err) => {
            isSpeaking = false;
            if (activeButton) setButtonState(activeButton, 'error');
            activeButton = null;
            if (opts.onError) opts.onError(err);
        };

        if (opts.onStart) opts.onStart();

        // Route to correct playback method
        if ((type === 'uploaded' || type === 'generated') && audioUrl) {
            playAudioFile(audioUrl, onEnd, onError);
        } else if (type === 'browser_tts' || config.fallbackEnabled) {
            speakBrowserTTS(text, rate, pitch, onEnd, onError);
        } else {
            onError('No audio source available');
        }
    }

    // ─── Browser TTS ──────────────────────────────────────────

    function speakBrowserTTS(text, rate, pitch, onEnd, onError) {
        if (!window.speechSynthesis) {
            onError('Speech synthesis not supported in this browser');
            return;
        }

        // Ensure voices are loaded
        if (!voicesLoaded) loadKoreanVoice();

        if (!koreanVoice) {
            // Try one more time
            const voices = window.speechSynthesis.getVoices();
            koreanVoice = voices.find(v => v.lang === 'ko-KR') ||
                          voices.find(v => v.lang.startsWith('ko'));
        }

        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'ko-KR';
        utterance.rate = Math.max(0.1, Math.min(2.0, rate));
        utterance.pitch = Math.max(0.1, Math.min(2.0, pitch));
        utterance.volume = 1.0;

        if (koreanVoice) {
            utterance.voice = koreanVoice;
        }

        utterance.onend = onEnd;
        utterance.onerror = (e) => {
            if (e.error === 'canceled') { onEnd(); return; }
            onError(e.error || 'Speech synthesis error');
        };

        window.speechSynthesis.speak(utterance);
    }

    // ─── Audio File Playback ──────────────────────────────────

    function playAudioFile(url, onEnd, onError) {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
            currentAudio = null;
        }

        const audio = new Audio(url);
        currentAudio = audio;

        audio.onended = () => { currentAudio = null; onEnd(); };
        audio.onerror = () => { currentAudio = null; onError('Failed to load audio file'); };

        audio.play().catch((err) => {
            currentAudio = null;
            onError(err.message || 'Playback failed');
        });
    }

    // ─── Premium TTS (Server-side) ────────────────────────────

    /**
     * Request audio from the server API (for premium TTS)
     * Returns audio URL on success
     */
    async function fetchPremiumAudio(text, moduleType = 'phrase', moduleItemId = null) {
        if (!config.ttsEndpoint) return null;

        // Check client cache first
        const cacheKey = text + '_' + moduleType;
        if (audioCache.has(cacheKey)) return audioCache.get(cacheKey);

        try {
            const body = new FormData();
            body.append('text', text);
            body.append('module_type', moduleType);
            if (moduleItemId) body.append('module_item_id', moduleItemId);

            const resp = await fetch(config.ttsEndpoint, { method: 'POST', body });
            const data = await resp.json();

            if (data.success && data.audio_url) {
                if (config.cacheEnabled) audioCache.set(cacheKey, data.audio_url);
                return data.audio_url;
            }
            return null;
        } catch (e) {
            console.warn('Premium TTS fetch failed:', e);
            return null;
        }
    }

    /**
     * High-level: speak with auto-resolution
     * Tries premium first (if configured), falls back to browser TTS
     */
    async function speakSmart(text, opts = {}) {
        if (!text || !text.trim()) return;

        // If an uploaded/generated URL is already provided, use it directly
        if (opts.audioUrl) {
            speak(text, opts);
            return;
        }

        // If premium provider is configured, try fetching from server
        if (config.provider !== 'browser_tts') {
            if (opts.button) setButtonState(opts.button, 'loading');

            const audioUrl = await fetchPremiumAudio(text, opts.moduleType, opts.moduleItemId);
            if (audioUrl) {
                speak(text, { ...opts, audioUrl, type: 'generated' });
                return;
            }
        }

        // Fallback to browser TTS
        speak(text, { ...opts, type: 'browser_tts' });
    }

    // ─── Stop ─────────────────────────────────────────────────

    function stop() {
        // Stop browser TTS
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        // Stop file audio
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
            currentAudio = null;
        }
        // Reset state
        isSpeaking = false;
        if (activeButton) {
            setButtonState(activeButton, 'idle');
            activeButton = null;
        }
    }

    // ─── Button State Management ──────────────────────────────

    function setButtonState(btn, state) {
        if (!btn) return;
        btn.classList.remove('tts-idle', 'tts-playing', 'tts-loading', 'tts-error');
        btn.classList.add('tts-' + state);

        const icon = btn.querySelector('.tts-icon');
        const label = btn.querySelector('.tts-label');

        switch (state) {
            case 'playing':
                btn.disabled = false;
                if (icon) icon.innerHTML = ICONS.stop;
                if (label) label.textContent = 'Stop';
                break;
            case 'loading':
                btn.disabled = true;
                if (icon) icon.innerHTML = ICONS.loading;
                if (label) label.textContent = 'Loading...';
                break;
            case 'error':
                btn.disabled = false;
                if (icon) icon.innerHTML = ICONS.error;
                if (label) label.textContent = 'Retry';
                setTimeout(() => { if (btn.classList.contains('tts-error')) setButtonState(btn, 'idle'); }, 3000);
                break;
            default: // idle
                btn.disabled = false;
                if (icon) icon.innerHTML = ICONS.speaker;
                if (label) label.textContent = label.dataset.defaultLabel || 'Hear';
                break;
        }
    }

    // ─── SVG Icons ────────────────────────────────────────────

    const ICONS = {
        speaker: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>',
        stop: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/></svg>',
        loading: '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"/></svg>',
        error: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        slow: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    };

    // ─── Auto-bind Speaker Buttons ────────────────────────────

    /**
     * Auto-bind all [data-tts-text] buttons on the page
     * <button data-tts-text="안녕하세요" data-tts-rate="1" data-tts-audio-url="" class="tts-btn">
     */
    function bindAll() {
        document.querySelectorAll('[data-tts-text]').forEach(btn => {
            if (btn.dataset.ttsBound) return;
            btn.dataset.ttsBound = '1';

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const text = btn.dataset.ttsText;
                const rate = parseFloat(btn.dataset.ttsRate) || config.defaultRate;
                const audioUrl = btn.dataset.ttsAudioUrl || null;
                const type = btn.dataset.ttsType || (audioUrl ? 'uploaded' : 'browser_tts');
                const moduleType = btn.dataset.ttsModule || 'phrase';
                const moduleItemId = btn.dataset.ttsItemId || null;

                // If currently playing from this button, stop
                if (isSpeaking && activeButton === btn) {
                    stop();
                    return;
                }

                if (audioUrl) {
                    speak(text, { audioUrl, type, rate, button: btn });
                } else if (config.provider !== 'browser_tts') {
                    speakSmart(text, { rate, button: btn, moduleType, moduleItemId });
                } else {
                    speak(text, { rate, type: 'browser_tts', button: btn });
                }
            });
        });
    }

    // ─── Speed Control ────────────────────────────────────────

    /**
     * Create a speed selector dropdown that updates the default rate
     * Returns an HTMLElement
     */
    function createSpeedSelector(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const speeds = [
            { label: '0.5x', value: 0.5 },
            { label: '0.75x', value: 0.75 },
            { label: '1x', value: 1.0 },
            { label: '1.25x', value: 1.25 },
            { label: '1.5x', value: 1.5 }
        ];

        container.innerHTML = speeds.map(s =>
            `<button type="button" class="tts-speed-btn px-2.5 py-1 rounded-lg text-xs font-medium transition
                ${s.value === config.defaultRate ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}"
                data-speed="${s.value}">${s.label}</button>`
        ).join('');

        container.querySelectorAll('.tts-speed-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                config.defaultRate = parseFloat(btn.dataset.speed);
                container.querySelectorAll('.tts-speed-btn').forEach(b => {
                    b.className = b.className.replace(/bg-blue-600 text-white|bg-gray-100 text-gray-600 hover:bg-gray-200/g, '');
                    b.classList.add(b === btn ? 'bg-blue-600' : 'bg-gray-100', b === btn ? 'text-white' : 'text-gray-600');
                    if (b !== btn) b.classList.add('hover:bg-gray-200');
                });
            });
        });
    }

    // ─── Status Info ──────────────────────────────────────────

    function getStatus() {
        return {
            supported: isSupported(),
            hasKoreanVoice: hasKoreanVoice(),
            voiceName: koreanVoice ? koreanVoice.name : null,
            isSpeaking,
            provider: config.provider,
            fallbackEnabled: config.fallbackEnabled
        };
    }

    // ─── Public API ───────────────────────────────────────────

    return {
        init,
        speak,
        speakSmart,
        stop,
        bindAll,
        isSupported,
        hasKoreanVoice,
        getStatus,
        createSpeedSelector,
        fetchPremiumAudio,
        ICONS,
        get isSpeaking() { return isSpeaking; },
        get config() { return { ...config }; }
    };
})();

// Auto-init on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    // Read config from page if embedded
    const configEl = document.getElementById('tts-config');
    if (configEl) {
        try { KoreanTTS.init(JSON.parse(configEl.textContent)); } catch (e) {}
    } else {
        KoreanTTS.init();
    }
    // Auto-bind all speaker buttons
    KoreanTTS.bindAll();
});
