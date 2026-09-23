<?php
/**
 * EPS Korean Trainer - Application Configuration
 */

// Load .env file if present
(function() {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
})();

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        if ($value === 'true' || $value === '(true)') return true;
        if ($value === 'false' || $value === '(false)') return false;
        if ($value === 'empty' || $value === '(empty)') return '';
        if ($value === 'null' || $value === '(null)') return null;
        return $value;
    }
}

// Environment & Error Handling
define('APP_ENV', env('APP_ENV', 'development'));
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'eps_topik'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Application Configuration
define('APP_NAME', env('APP_NAME', 'EPS Korean Trainer'));
define('APP_VERSION', '1.0.0');

// Dynamic APP_URL Detection
$configuredUrl = env('APP_URL', 'auto');
if (empty($configuredUrl) || strtolower($configuredUrl) === 'auto') {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dirName = rtrim(dirname($scriptName), '/\\');
    $dirName = preg_replace('#/(admin|api)$#', '', $dirName);
    
    $appUrl = $scheme . '://' . $host . ($dirName === '/' || $dirName === '.' || $dirName === '' ? '' : $dirName);
} else {
    $appUrl = rtrim($configuredUrl, '/');
}
define('APP_URL', $appUrl);

// File Upload Paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('AUDIO_DIR', UPLOAD_DIR . 'audio/');
define('PROFILE_DIR', UPLOAD_DIR . 'profiles/');
define('FLASHCARD_DIR', UPLOAD_DIR . 'flashcards/');
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
define('TTS_DEFAULT_PROVIDER', env('TTS_DEFAULT_PROVIDER', 'browser_tts')); // browser_tts | google_cloud | openai
define('TTS_DEFAULT_RATE', 1.0);
define('TTS_DEFAULT_PITCH', 1.0);
define('TTS_CACHE_ENABLED', true);

// Timezone
date_default_timezone_set(env('TIMEZONE', 'Asia/Manila'));

