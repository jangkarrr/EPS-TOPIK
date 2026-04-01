<?php
/**
 * TTS / Voice Helper Functions
 * Handles audio source selection, caching, and premium TTS integration
 */

require_once __DIR__ . '/db.php';

/**
 * Get a system setting value
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['setting_value'] : $default;
    } catch (\PDOException $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

/**
 * Get all settings for a group
 */
function getSettingsByGroup(string $group): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_key, setting_value, description FROM system_settings WHERE setting_group = ?");
        $stmt->execute([$group]);
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    } catch (\PDOException $e) {
        return [];
    }
}

/**
 * Update a system setting
 */
function updateSetting(string $key, string $value): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
    return $stmt->execute([$value, $key]);
}

/**
 * Generate a hash for Korean text (for cache lookup)
 */
function textHash(string $koreanText): string {
    return hash('sha256', mb_strtolower(trim($koreanText), 'UTF-8'));
}

/**
 * Resolve the best audio source for a given Korean text
 * Priority: uploaded_audio > cached_generated > browser_tts
 * Returns: ['type' => 'uploaded'|'generated'|'browser_tts'|'none', 'url' => '...', 'text' => '...']
 */
function resolveAudioSource(string $koreanText, string $moduleType = 'phrase', ?int $moduleItemId = null, ?string $uploadedAudioPath = null): array {
    $preference = getSetting('tts_audio_preference', 'uploaded_first');
    $provider = getSetting('tts_provider', TTS_DEFAULT_PROVIDER);
    $fallbackEnabled = (bool)getSetting('tts_fallback_enabled', '1');

    // 1. Check uploaded audio
    if ($preference === 'uploaded_first' && $uploadedAudioPath && !empty(trim($uploadedAudioPath))) {
        return [
            'type' => 'uploaded',
            'url' => APP_URL . '/uploads/' . $uploadedAudioPath,
            'text' => $koreanText,
            'provider' => 'uploaded'
        ];
    }

    // 2. Check for cached generated audio (premium providers)
    if ($provider !== 'browser_tts') {
        $cached = getCachedAudio($koreanText, $provider);
        if ($cached && $cached['status'] === 'ready' && $cached['audio_path']) {
            incrementPlayCount($cached['id']);
            return [
                'type' => 'generated',
                'url' => APP_URL . '/uploads/' . $cached['audio_path'],
                'text' => $koreanText,
                'provider' => $cached['provider'],
                'audio_id' => $cached['id']
            ];
        }
    }

    // 3. Fallback to browser TTS
    if ($fallbackEnabled || $provider === 'browser_tts') {
        return [
            'type' => 'browser_tts',
            'url' => null,
            'text' => $koreanText,
            'provider' => 'browser_tts',
            'rate' => (float)getSetting('tts_default_rate', '1'),
            'pitch' => (float)getSetting('tts_default_pitch', '1')
        ];
    }

    return ['type' => 'none', 'url' => null, 'text' => $koreanText, 'provider' => 'none'];
}

/**
 * Look up cached generated audio by text hash + provider
 */
function getCachedAudio(string $koreanText, string $provider): ?array {
    $db = getDB();
    $hash = textHash($koreanText);
    $stmt = $db->prepare("SELECT * FROM generated_audio WHERE text_hash = ? AND provider = ? AND status = 'ready' LIMIT 1");
    $stmt->execute([$hash, $provider]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a pending audio generation record
 */
function requestAudioGeneration(string $koreanText, string $moduleType, ?int $moduleItemId = null, string $provider = 'google_cloud'): int {
    $db = getDB();
    $hash = textHash($koreanText);

    // Check if already exists
    $existing = getCachedAudio($koreanText, $provider);
    if ($existing) return $existing['id'];

    $stmt = $db->prepare("INSERT INTO generated_audio (module_type, module_item_id, korean_text, text_hash, provider, status) 
        VALUES (?, ?, ?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
    $stmt->execute([$moduleType, $moduleItemId, $koreanText, $hash, $provider]);
    return (int)$db->lastInsertId();
}

/**
 * Mark audio as generated and store path
 */
function markAudioReady(int $audioId, string $audioPath, ?float $duration = null): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE generated_audio SET audio_path = ?, duration_seconds = ?, is_cached = 1, status = 'ready' WHERE id = ?");
    return $stmt->execute([$audioPath, $duration, $audioId]);
}

/**
 * Mark audio generation as failed
 */
function markAudioFailed(int $audioId, string $error): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE generated_audio SET status = 'failed', error_message = ? WHERE id = ?");
    return $stmt->execute([$error, $audioId]);
}

/**
 * Increment play count for cached audio
 */
function incrementPlayCount(int $audioId): void {
    $db = getDB();
    $db->prepare("UPDATE generated_audio SET play_count = play_count + 1 WHERE id = ?")->execute([$audioId]);
}

/**
 * Get the current TTS configuration for frontend JS
 * Returns JSON-safe array to embed in page
 */
function getTTSConfig(): array {
    return [
        'provider' => getSetting('tts_provider', TTS_DEFAULT_PROVIDER),
        'fallbackEnabled' => (bool)getSetting('tts_fallback_enabled', '1'),
        'defaultRate' => (float)getSetting('tts_default_rate', '1'),
        'defaultPitch' => (float)getSetting('tts_default_pitch', '1'),
        'preference' => getSetting('tts_audio_preference', 'uploaded_first'),
        'cacheEnabled' => (bool)getSetting('tts_cache_enabled', '1'),
        'ttsEndpoint' => APP_URL . '/api/tts.php',
        'lang' => 'ko-KR'
    ];
}

/**
 * Generate audio using Google Cloud TTS (premium)
 * Requires tts_google_api_key to be set
 */
function generateGoogleTTS(string $koreanText, int $audioId): ?string {
    $apiKey = getSetting('tts_google_api_key', '');
    if (empty($apiKey)) {
        markAudioFailed($audioId, 'Google Cloud API key not configured');
        return null;
    }

    $payload = json_encode([
        'input' => ['text' => $koreanText],
        'voice' => ['languageCode' => 'ko-KR', 'name' => 'ko-KR-Neural2-A', 'ssmlGender' => 'FEMALE'],
        'audioConfig' => ['audioEncoding' => 'MP3', 'speakingRate' => 1.0, 'pitch' => 0]
    ]);

    $ch = curl_init('https://texttospeech.googleapis.com/v1/text:synthesize?key=' . $apiKey);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        markAudioFailed($audioId, 'Google TTS HTTP ' . $httpCode);
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['audioContent'])) {
        markAudioFailed($audioId, 'Empty audio content from Google');
        return null;
    }

    $audioData = base64_decode($data['audioContent']);
    $dir = TTS_AUDIO_DIR;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'tts_' . substr(textHash($koreanText), 0, 16) . '_' . time() . '.mp3';
    $filepath = $dir . $filename;
    file_put_contents($filepath, $audioData);

    $relativePath = 'audio/tts/' . $filename;
    markAudioReady($audioId, $relativePath);
    return $relativePath;
}

/**
 * Generate audio using OpenAI TTS (premium)
 * Requires tts_openai_api_key to be set
 */
function generateOpenAITTS(string $koreanText, int $audioId): ?string {
    $apiKey = getSetting('tts_openai_api_key', '');
    if (empty($apiKey)) {
        markAudioFailed($audioId, 'OpenAI API key not configured');
        return null;
    }

    $payload = json_encode([
        'model' => 'tts-1',
        'input' => $koreanText,
        'voice' => 'nova',
        'response_format' => 'mp3'
    ]);

    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20
    ]);
    $audioData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($audioData)) {
        markAudioFailed($audioId, 'OpenAI TTS HTTP ' . $httpCode);
        return null;
    }

    $dir = TTS_AUDIO_DIR;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'tts_' . substr(textHash($koreanText), 0, 16) . '_' . time() . '.mp3';
    $filepath = $dir . $filename;
    file_put_contents($filepath, $audioData);

    $relativePath = 'audio/tts/' . $filename;
    markAudioReady($audioId, $relativePath);
    return $relativePath;
}

/**
 * Generate TTS audio via the configured premium provider
 */
function generatePremiumTTS(string $koreanText, string $moduleType = 'phrase', ?int $moduleItemId = null): ?string {
    $provider = getSetting('tts_provider', TTS_DEFAULT_PROVIDER);
    if ($provider === 'browser_tts') return null;

    $audioId = requestAudioGeneration($koreanText, $moduleType, $moduleItemId, $provider);

    switch ($provider) {
        case 'google_cloud':
            return generateGoogleTTS($koreanText, $audioId);
        case 'openai':
            return generateOpenAITTS($koreanText, $audioId);
        default:
            return null;
    }
}
