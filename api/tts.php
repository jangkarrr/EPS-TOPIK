<?php
/**
 * TTS API Endpoint
 * Handles premium TTS generation requests from the frontend
 * 
 * POST /api/tts.php
 * Params: text, module_type, module_item_id
 * Returns: JSON { success, audio_url, provider, cached }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tts-helpers.php';

// Must be logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$text = trim($_POST['text'] ?? '');
$moduleType = $_POST['module_type'] ?? 'phrase';
$moduleItemId = !empty($_POST['module_item_id']) ? (int)$_POST['module_item_id'] : null;

if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'No text provided']);
    exit;
}

// Limit text length to prevent abuse
if (mb_strlen($text, 'UTF-8') > 2000) {
    echo json_encode(['success' => false, 'error' => 'Text too long (max 2000 characters)']);
    exit;
}

$provider = getSetting('tts_provider', TTS_DEFAULT_PROVIDER);

// If browser_tts, no server-side generation needed
if ($provider === 'browser_tts') {
    echo json_encode([
        'success' => true,
        'type' => 'browser_tts',
        'audio_url' => null,
        'provider' => 'browser_tts',
        'cached' => false,
        'text' => $text
    ]);
    exit;
}

// Check cache first
$cached = getCachedAudio($text, $provider);
if ($cached && $cached['audio_path']) {
    incrementPlayCount($cached['id']);
    echo json_encode([
        'success' => true,
        'type' => 'generated',
        'audio_url' => APP_URL . '/uploads/' . $cached['audio_path'],
        'provider' => $cached['provider'],
        'cached' => true,
        'audio_id' => $cached['id']
    ]);
    exit;
}

// Generate new audio
$validModules = ['vocabulary', 'lesson', 'reading', 'listening', 'mock_exam', 'phrase'];
if (!in_array($moduleType, $validModules)) {
    $moduleType = 'phrase';
}

$audioPath = generatePremiumTTS($text, $moduleType, $moduleItemId);

if ($audioPath) {
    echo json_encode([
        'success' => true,
        'type' => 'generated',
        'audio_url' => APP_URL . '/uploads/' . $audioPath,
        'provider' => $provider,
        'cached' => false
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Audio generation failed',
        'fallback' => 'browser_tts'
    ]);
}
