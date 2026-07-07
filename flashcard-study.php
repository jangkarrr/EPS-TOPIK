<?php
$pageTitle = 'Study Flashcards';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$csrfToken = generateCSRFToken();
$csrfName = CSRF_TOKEN_NAME;

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   FLASHCARD STUDY STYLES
   ═══════════════════════════════════════════════════════════ */
.study-card-wrapper {
    perspective: 1200px;
    width: 100%;
    max-width: 580px;
    height: 380px;
    margin: 0 auto;
    cursor: pointer;
}

@media (max-width: 640px) {
    .study-card-wrapper {
        height: 320px;
    }
}

.study-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d;
}

.study-card-inner.flipped {
    transform: rotateY(180deg);
}

.study-card-front,
.study-card-back {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    overflow: hidden;
}

.study-card-front {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
}

.study-card-back {
    transform: rotateY(180deg);
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 50%, #f8fafc 100%);
    border: 1px solid #bfdbfe;
    box-shadow: 0 4px 24px rgba(59, 130, 246, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04);
}

.study-card-text {
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    line-height: 1.3;
    word-break: break-word;
}

@media (max-width: 640px) {
    .study-card-text {
        font-size: 1.5rem;
    }
}

.study-card-image {
    max-width: 100%;
    max-height: 160px;
    object-fit: contain;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
}

.study-card-label {
    position: absolute;
    top: 1rem;
    left: 1.5rem;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
}

.study-card-front .study-card-label {
    background: #f1f5f9;
    color: #64748b;
}

.study-card-back .study-card-label {
    background: #dbeafe;
    color: #3b82f6;
}

.study-card-tts {
    position: absolute;
    top: 0.85rem;
    right: 1.25rem;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    z-index: 5;
}

.study-card-front .study-card-tts {
    background: #eff6ff;
    color: #3b82f6;
}

.study-card-front .study-card-tts:hover {
    background: #dbeafe;
}

.study-card-back .study-card-tts {
    background: #dbeafe;
    color: #2563eb;
}

.study-card-back .study-card-tts:hover {
    background: #bfdbfe;
}

.study-card-tts.tts-active {
    background: #3b82f6 !important;
    color: white !important;
}

.study-card-tts.tts-active svg {
    animation: tts-pulse 1s ease-in-out infinite;
}

@keyframes tts-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.text-size-control {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
}

.text-size-control input[type=range] {
    width: 70px;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: #e2e8f0;
    border-radius: 9999px;
    outline: none;
    cursor: pointer;
}

.text-size-control input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
}

.text-size-control input[type=range]::-moz-range-thumb {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #3b82f6;
    border: none;
    cursor: pointer;
}

.study-progress-bar {
    height: 4px;
    border-radius: 9999px;
    background: #e2e8f0;
    overflow: hidden;
}

.study-progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #3b82f6, #6366f1);
    transition: width 0.4s ease;
}

.shortcut-key {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 6px;
    border-radius: 6px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    font-family: 'Inter', monospace;
}

.study-nav-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.study-nav-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.study-nav-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.study-action-btn {
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}
</style>

<input type="hidden" id="fc-csrf-name" value="<?= $csrfName ?>">

<!-- ═══════════════════════════════════════════════════════════
     LOADING / EMPTY STATE
     ═══════════════════════════════════════════════════════════ -->
<div id="study-loading" class="text-center py-20">
    <div class="w-12 h-12 mx-auto mb-4 rounded-full border-4 border-blue-200 border-t-blue-600 animate-spin"></div>
    <p class="text-sm text-gray-400">Loading flashcards...</p>
</div>

<div id="study-empty" class="hidden text-center py-20">
    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
        <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    </div>
    <h3 class="text-lg font-semibold text-gray-700 mb-1">No flashcards to study</h3>
    <p class="text-sm text-gray-400 mb-4">Create some flashcards first, then come back to study!</p>
    <a href="<?= APP_URL ?>/flashcards.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Go to Flashcards
    </a>
</div>

<!-- ═══════════════════════════════════════════════════════════
     STUDY INTERFACE
     ═══════════════════════════════════════════════════════════ -->
<div id="study-container" class="hidden">

    <!-- Top Controls -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= APP_URL ?>/flashcards.php" class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Back to Flashcards">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Study Mode</h2>
                <p class="text-xs text-gray-400" id="study-counter">0 / 0</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Filter -->
            <select id="study-filter" class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">All Cards</option>
                <option value="new">New Only</option>
                <option value="review">Needs Review</option>
                <option value="known">Known Only</option>
            </select>

            <!-- Front side toggle -->
            <select id="study-front-side" class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="term">Show Term First</option>
                <option value="definition">Show Definition First</option>
            </select>

            <!-- Shuffle -->
            <button id="study-shuffle-btn" onclick="StudyMode.toggleShuffle()" class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition flex items-center gap-1.5" title="Shuffle">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Shuffle
            </button>

            <!-- Text Size -->
            <div class="text-size-control" title="Adjust text size">
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><text x="4" y="18" font-size="14" font-weight="bold">A</text></svg>
                <input type="range" id="study-text-size" min="1" max="4" step="0.5" value="2" oninput="StudyMode.setTextSize(this.value)">
                <svg class="w-5 h-5 text-gray-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><text x="2" y="20" font-size="20" font-weight="bold">A</text></svg>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="study-progress-bar mb-6">
        <div class="study-progress-fill" id="study-progress" style="width: 0%"></div>
    </div>

    <!-- Card Area -->
    <div class="flex items-center justify-center gap-4 sm:gap-8 mb-8">
        <!-- Prev Button -->
        <button id="study-prev-btn" onclick="StudyMode.prev()" class="study-nav-btn flex-shrink-0" title="Previous (←)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <!-- Flashcard -->
        <div class="study-card-wrapper" onclick="StudyMode.flip()">
            <div class="study-card-inner" id="study-card">
                <div class="study-card-front">
                    <span class="study-card-label" id="study-front-label">TERM</span>
                    <button type="button" class="study-card-tts" id="study-front-tts" onclick="event.stopPropagation(); StudyMode.playTermAudio('front')" title="Listen (P)">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
                    </button>
                    <div id="study-front-image"></div>
                    <div class="study-card-text korean-text" id="study-front-text"></div>
                    <p class="absolute bottom-4 text-xs text-gray-300">Click or press Space to flip</p>
                </div>
                <div class="study-card-back">
                    <span class="study-card-label" id="study-back-label">DEFINITION</span>
                    <button type="button" class="study-card-tts" id="study-back-tts" onclick="event.stopPropagation(); StudyMode.playTermAudio('back')" title="Listen (P)">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
                    </button>
                    <div id="study-back-image"></div>
                    <div class="study-card-text" id="study-back-text"></div>
                </div>
            </div>
        </div>

        <!-- Next Button -->
        <button id="study-next-btn" onclick="StudyMode.next()" class="study-nav-btn flex-shrink-0" title="Next (→)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <!-- Status Buttons -->
    <div class="flex items-center justify-center gap-3 mb-8">
        <button onclick="StudyMode.markStatus('known')" class="study-action-btn bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Known
            <span class="shortcut-key">1</span>
        </button>
        <button onclick="StudyMode.markStatus('review')" class="study-action-btn bg-amber-50 text-amber-700 hover:bg-amber-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Review
            <span class="shortcut-key">2</span>
        </button>
    </div>

    <!-- Keyboard Shortcuts Help -->
    <div class="max-w-md mx-auto">
        <button onclick="document.getElementById('shortcuts-panel').classList.toggle('hidden')" class="w-full text-center text-xs text-gray-400 hover:text-gray-500 transition flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Keyboard Shortcuts
        </button>
        <div id="shortcuts-panel" class="hidden mt-3 bg-gray-50 rounded-xl p-4">
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="flex items-center gap-2"><span class="shortcut-key">←</span><span class="text-gray-500">Previous card</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">→</span><span class="text-gray-500">Next card</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">Space</span><span class="text-gray-500">Flip card</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">S</span><span class="text-gray-500">Toggle shuffle</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">P</span><span class="text-gray-500">Play audio</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">1</span><span class="text-gray-500">Mark as Known</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">2</span><span class="text-gray-500">Mark as Review</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">+ / −</span><span class="text-gray-500">Text size</span></div>
                <div class="flex items-center gap-2"><span class="shortcut-key">Esc</span><span class="text-gray-500">Back to list</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Sound FX Helper (Procedural Web Audio API) -->
<script>
const SoundFX = {
    ctx: null,
    initCtx() {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
    },
    playKnown() {
        this.initCtx();
        const now = this.ctx.currentTime;
        
        // High chime sequence (C6 -> E6 -> G6)
        this.playTone(880, 0.08, now);
        this.playTone(1046.5, 0.08, now + 0.06);
        this.playTone(1318.5, 0.25, now + 0.12);
    },
    playReview() {
        this.initCtx();
        const now = this.ctx.currentTime;
        
        // Descending warning tone (G4 -> Eb4)
        this.playTone(392, 0.12, now, 'triangle');
        this.playTone(311.1, 0.22, now + 0.1, 'triangle');
    },
    playFlip() {
        this.initCtx();
        const now = this.ctx.currentTime;
        // Subtle soft click/swoosh
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.frequency.setValueAtTime(400, now);
        osc.frequency.exponentialRampToValueAtTime(120, now + 0.08);
        gain.gain.setValueAtTime(0.05, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        osc.start(now);
        osc.stop(now + 0.08);
    },
    playWhoosh() {
        this.initCtx();
        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(180, now);
        osc.frequency.exponentialRampToValueAtTime(320, now + 0.15);
        gain.gain.setValueAtTime(0.06, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc.start(now);
        osc.stop(now + 0.15);
    },
    playShuffle() {
        this.initCtx();
        const now = this.ctx.currentTime;
        // Cascade of quick high-speed notes
        const notes = [440, 554, 659, 880];
        notes.forEach((freq, idx) => {
            this.playTone(freq, 0.04, now + (idx * 0.05), 'sine', 0.04);
        });
    },
    playTone(freq, duration, time, type = 'sine', volume = 0.08) {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.type = type;
        osc.frequency.setValueAtTime(freq, time);
        gain.gain.setValueAtTime(volume, time);
        gain.gain.exponentialRampToValueAtTime(0.001, time + duration);
        osc.start(time);
        osc.stop(time + duration);
    }
};
</script>

<!-- ═══════════════════════════════════════════════════════════
     STUDY MODE SCRIPT
     ═══════════════════════════════════════════════════════════ -->
<script>
const StudyMode = {
    apiUrl: '<?= APP_URL ?>/api/flashcards.php',
    csrfToken: '<?= $csrfToken ?>',
    csrfName: '<?= $csrfName ?>',
    cards: [],
    originalOrder: [],
    currentIndex: 0,
    isFlipped: false,
    isShuffled: false,
    frontSide: 'term', // 'term' or 'definition'
    textSizeLevel: parseFloat(localStorage.getItem('fc_text_size') || '2'),
    ttsPlaying: false,

    TEXT_SIZES: {
        1:   { card: '1rem',   mobile: '0.875rem' },
        1.5: { card: '1.5rem', mobile: '1.125rem' },
        2:   { card: '2rem',   mobile: '1.5rem' },
        2.5: { card: '2.5rem', mobile: '1.875rem' },
        3:   { card: '3rem',   mobile: '2.25rem' },
        3.5: { card: '3.5rem', mobile: '2.625rem' },
        4:   { card: '4rem',   mobile: '3rem' },
    },

    async init() {
        // Restore saved text size
        const slider = document.getElementById('study-text-size');
        if (slider) slider.value = this.textSizeLevel;
        this.applyTextSize();

        await this.loadCards();
        this.bindEvents();
    },

    async loadCards(filter = '') {
        try {
            const params = new URLSearchParams({ action: 'study_cards', filter });
            const res = await fetch(`${this.apiUrl}?${params}`);
            const data = await res.json();

            document.getElementById('study-loading').classList.add('hidden');

            if (data.success && data.cards.length > 0) {
                this.cards = data.cards;
                this.originalOrder = [...data.cards];
                this.currentIndex = 0;
                this.isFlipped = false;

                document.getElementById('study-container').classList.remove('hidden');
                document.getElementById('study-empty').classList.add('hidden');
                this.renderCard();
                this.updateProgress();
            } else {
                document.getElementById('study-container').classList.add('hidden');
                document.getElementById('study-empty').classList.remove('hidden');
            }
        } catch {
            document.getElementById('study-loading').classList.add('hidden');
            document.getElementById('study-empty').classList.remove('hidden');
        }
    },

    bindEvents() {
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Don't trigger if user is typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;

            switch (e.key) {
                case 'ArrowLeft':
                case 'a':
                case 'A':
                    e.preventDefault();
                    this.prev();
                    break;
                case 'ArrowRight':
                case 'd':
                case 'D':
                    e.preventDefault();
                    this.next();
                    break;
                case ' ':
                case 'Enter':
                    e.preventDefault();
                    this.flip();
                    break;
                case 's':
                case 'S':
                    e.preventDefault();
                    this.toggleShuffle();
                    break;
                case 'p':
                case 'P':
                    e.preventDefault();
                    this.playTermAudio(this.isFlipped ? 'back' : 'front');
                    break;
                case '=':
                case '+':
                    e.preventDefault();
                    this.adjustTextSize(0.5);
                    break;
                case '-':
                case '_':
                    e.preventDefault();
                    this.adjustTextSize(-0.5);
                    break;
                case '1':
                    e.preventDefault();
                    this.markStatus('known');
                    break;
                case '2':
                    e.preventDefault();
                    this.markStatus('review');
                    break;
                case 'Escape':
                    window.location.href = '<?= APP_URL ?>/flashcards.php';
                    break;
            }
        });

        // Front side toggle
        document.getElementById('study-front-side').addEventListener('change', (e) => {
            this.frontSide = e.target.value;
            this.isFlipped = false;
            this.renderCard();
        });

        // Filter change
        document.getElementById('study-filter').addEventListener('change', (e) => {
            this.loadCards(e.target.value);
        });
    },

    renderCard() {
        if (this.cards.length === 0) return;

        const card = this.cards[this.currentIndex];
        const cardEl = document.getElementById('study-card');

        // Reset flip
        if (this.isFlipped) {
            cardEl.classList.remove('flipped');
            this.isFlipped = false;
        }

        // Determine front/back content based on setting
        const frontText = this.frontSide === 'term' ? card.term : card.definition;
        const backText = this.frontSide === 'term' ? card.definition : card.term;
        const frontLabel = this.frontSide === 'term' ? 'TERM' : 'DEFINITION';
        const backLabel = this.frontSide === 'term' ? 'DEFINITION' : 'TERM';

        document.getElementById('study-front-text').textContent = frontText;
        document.getElementById('study-back-text').textContent = backText;
        document.getElementById('study-front-label').textContent = frontLabel;
        document.getElementById('study-back-label').textContent = backLabel;

        // Handle image — show on the back
        const backImageContainer = document.getElementById('study-back-image');
        const frontImageContainer = document.getElementById('study-front-image');
        frontImageContainer.innerHTML = '';
        backImageContainer.innerHTML = '';

        if (card.image_url) {
            backImageContainer.innerHTML = `<img src="${card.image_url}" class="study-card-image" alt="">`;
        }

        // Front text style: use korean-text class for korean text
        const frontTextEl = document.getElementById('study-front-text');
        if (this.frontSide === 'term') {
            frontTextEl.classList.add('korean-text');
        } else {
            frontTextEl.classList.remove('korean-text');
        }

        const backTextEl = document.getElementById('study-back-text');
        if (this.frontSide === 'definition') {
            backTextEl.classList.add('korean-text');
        } else {
            backTextEl.classList.remove('korean-text');
        }

        // Update nav button states
        document.getElementById('study-prev-btn').disabled = this.currentIndex === 0;
        document.getElementById('study-next-btn').disabled = this.currentIndex >= this.cards.length - 1;

        // Show/hide TTS buttons (only show if the text side has Korean-like content)
        const frontTtsBtn = document.getElementById('study-front-tts');
        const backTtsBtn = document.getElementById('study-back-tts');
        if (frontTtsBtn) frontTtsBtn.style.display = frontText ? '' : 'none';
        if (backTtsBtn) backTtsBtn.style.display = backText ? '' : 'none';

        // Apply current text size
        this.applyTextSize();
    },

    flip() {
        const cardEl = document.getElementById('study-card');
        cardEl.classList.toggle('flipped');
        this.isFlipped = !this.isFlipped;
        try { SoundFX.playFlip(); } catch(e){}
    },

    next() {
        if (this.currentIndex < this.cards.length - 1) {
            this.currentIndex++;
            this.isFlipped = false;
            this.renderCard();
            this.updateProgress();
            try { SoundFX.playWhoosh(); } catch(e){}
        }
    },

    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.isFlipped = false;
            this.renderCard();
            this.updateProgress();
            try { SoundFX.playWhoosh(); } catch(e){}
        }
    },

    toggleShuffle() {
        this.isShuffled = !this.isShuffled;
        const btn = document.getElementById('study-shuffle-btn');

        if (this.isShuffled) {
            // Fisher-Yates shuffle
            for (let i = this.cards.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [this.cards[i], this.cards[j]] = [this.cards[j], this.cards[i]];
            }
            btn.classList.add('bg-blue-100', 'text-blue-600', 'border-blue-200');
            btn.classList.remove('text-gray-600');
            showToast('Cards shuffled', 'info');
            try { SoundFX.playShuffle(); } catch(e){}
        } else {
            this.cards = [...this.originalOrder];
            btn.classList.remove('bg-blue-100', 'text-blue-600', 'border-blue-200');
            btn.classList.add('text-gray-600');
            showToast('Original order restored', 'info');
            try { SoundFX.playShuffle(); } catch(e){}
        }

        this.currentIndex = 0;
        this.isFlipped = false;
        this.renderCard();
        this.updateProgress();
    },

    updateProgress() {
        const total = this.cards.length;
        const current = this.currentIndex + 1;
        const pct = total > 0 ? (current / total) * 100 : 0;

        document.getElementById('study-counter').textContent = `${current} / ${total}`;
        document.getElementById('study-progress').style.width = `${pct}%`;
    },

    async markStatus(status) {
        if (this.cards.length === 0) return;

        const card = this.cards[this.currentIndex];
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('id', card.id);
        formData.append('status', status);
        formData.append(this.csrfName, this.csrfToken);

        // Sound trigger
        if (status === 'known') {
            try { SoundFX.playKnown(); } catch(e){}
        } else if (status === 'review') {
            try { SoundFX.playReview(); } catch(e){}
        }

        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                card.status = status;
                const label = status === 'known' ? '✅ Marked as Known' : '🔄 Marked for Review';
                showToast(label, status === 'known' ? 'success' : 'warning');

                // Auto-advance to next card
                if (this.currentIndex < this.cards.length - 1) {
                    setTimeout(() => this.next(), 400);
                }
            }
        } catch {
            showToast('Failed to update status', 'error');
        }
    },

    // ── TTS Audio Playback ──────────────────────────────────
    playTermAudio(side) {
        // Stop any existing playback
        if (this.ttsPlaying) {
            if (typeof KoreanTTS !== 'undefined') KoreanTTS.stop();
            window.speechSynthesis?.cancel();
            this.resetTtsButtons();
            this.ttsPlaying = false;
            return;
        }

        if (this.cards.length === 0) return;

        const card = this.cards[this.currentIndex];
        const textToSpeak = side === 'front'
            ? (this.frontSide === 'term' ? card.term : card.definition)
            : (this.frontSide === 'term' ? card.definition : card.term);

        if (!textToSpeak) return;

        const btn = document.getElementById(side === 'front' ? 'study-front-tts' : 'study-back-tts');
        if (btn) btn.classList.add('tts-active');
        this.ttsPlaying = true;

        const onDone = () => {
            this.ttsPlaying = false;
            this.resetTtsButtons();
        };

        // Try KoreanTTS first (project's TTS system), fallback to browser
        if (typeof KoreanTTS !== 'undefined') {
            KoreanTTS.speak(textToSpeak, {
                type: 'browser_tts',
                onEnd: onDone,
                onError: onDone
            });
        } else if ('speechSynthesis' in window) {
            const utter = new SpeechSynthesisUtterance(textToSpeak);
            utter.lang = 'ko-KR';
            utter.rate = 0.9;
            utter.onend = onDone;
            utter.onerror = onDone;
            window.speechSynthesis.speak(utter);
        }
    },

    resetTtsButtons() {
        document.querySelectorAll('.study-card-tts').forEach(b => b.classList.remove('tts-active'));
    },

    // ── Text Size Control ──────────────────────────────────
    setTextSize(val) {
        this.textSizeLevel = parseFloat(val);
        localStorage.setItem('fc_text_size', this.textSizeLevel);
        this.applyTextSize();
    },

    adjustTextSize(delta) {
        let newVal = Math.min(4, Math.max(1, this.textSizeLevel + delta));
        this.textSizeLevel = newVal;
        localStorage.setItem('fc_text_size', newVal);
        const slider = document.getElementById('study-text-size');
        if (slider) slider.value = newVal;
        this.applyTextSize();
    },

    applyTextSize() {
        const size = this.TEXT_SIZES[this.textSizeLevel] || this.TEXT_SIZES[2];
        const isMobile = window.innerWidth < 640;
        const fontSize = isMobile ? size.mobile : size.card;
        document.querySelectorAll('.study-card-text').forEach(el => {
            el.style.fontSize = fontSize;
        });
    },
};

// Init study mode
StudyMode.init();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
