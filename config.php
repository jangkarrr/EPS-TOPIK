<?php
/**
 * EPS Korean Trainer - Application Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'eps_topik');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'EPS Korean Trainer');
define('APP_URL', 'http://localhost/EPS-TOPIK');
define('APP_VERSION', '1.0.0');

// File Upload Paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('AUDIO_DIR', UPLOAD_DIR . 'audio/');
define('PROFILE_DIR', UPLOAD_DIR . 'profiles/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours
define('REMEMBER_ME_LIFETIME', 30 * 86400); // 30 days

// Pagination
define('ITEMS_PER_PAGE', 15);

// CSRF Token Name
define('CSRF_TOKEN_NAME', 'csrf_token');

// Voice/TTS Configuration
define('TTS_AUDIO_DIR', AUDIO_DIR . 'tts/');
define('TTS_DEFAULT_PROVIDER', 'browser_tts'); // browser_tts | google_cloud | openai
define('TTS_DEFAULT_RATE', 1.0);
define('TTS_DEFAULT_PITCH', 1.0);
define('TTS_CACHE_ENABLED', true);

// Timezone
date_default_timezone_set('Asia/Manila');
