<?php
/**
 * EPS Korean Trainer - Server Health & Environment Check
 * Usage: Access via browser (http://domain.com/health.php) or CLI (php health.php)
 */

require_once __DIR__ . '/config.php';

$isCli = (php_sapi_name() === 'cli');

$checks = [
    'php_version' => [
        'name' => 'PHP Version (>= 7.4)',
        'pass' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'detail' => PHP_VERSION
    ],
    'ext_pdo' => [
        'name' => 'PDO Extension',
        'pass' => extension_loaded('pdo'),
        'detail' => extension_loaded('pdo') ? 'Installed' : 'Missing'
    ],
    'ext_pdo_mysql' => [
        'name' => 'PDO MySQL Extension',
        'pass' => extension_loaded('pdo_mysql'),
        'detail' => extension_loaded('pdo_mysql') ? 'Installed' : 'Missing'
    ],
    'ext_mbstring' => [
        'name' => 'Mbstring Extension (Korean text)',
        'pass' => extension_loaded('mbstring'),
        'detail' => extension_loaded('mbstring') ? 'Installed' : 'Missing'
    ],
    'ext_curl' => [
        'name' => 'cURL Extension (TTS APIs)',
        'pass' => extension_loaded('curl'),
        'detail' => extension_loaded('curl') ? 'Installed' : 'Missing'
    ],
    'ext_json' => [
        'name' => 'JSON Extension',
        'pass' => extension_loaded('json'),
        'detail' => extension_loaded('json') ? 'Installed' : 'Missing'
    ]
];

// Check Database Connection
$dbStatus = false;
$dbDetail = '';
$tableCount = 0;
try {
    require_once __DIR__ . '/includes/db.php';
    $db = getDB();
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    $dbStatus = true;
    $dbDetail = "Connected successfully ($tableCount tables found)";
} catch (Exception $e) {
    $dbStatus = false;
    $dbDetail = "Connection failed: " . $e->getMessage();
}

$checks['database'] = [
    'name' => 'Database Connection (' . DB_NAME . '@' . DB_HOST . ')',
    'pass' => $dbStatus,
    'detail' => $dbDetail
];

// Check Upload Directory Permissions
$uploadDirs = [
    'uploads' => UPLOAD_DIR,
    'uploads/audio' => AUDIO_DIR,
    'uploads/profiles' => PROFILE_DIR,
    'uploads/flashcards' => FLASHCARD_DIR,
    'uploads/audio/tts' => TTS_AUDIO_DIR,
];

foreach ($uploadDirs as $key => $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $isWritable = is_writable($dir);
    $checks['dir_' . $key] = [
        'name' => "Directory Writable ($key)",
        'pass' => $isWritable,
        'detail' => $isWritable ? 'Writable (0755/0775)' : 'Not Writable (Check chmod permissions)'
    ];
}

// System Summary
$allPassed = true;
foreach ($checks as $chk) {
    if (!$chk['pass']) {
        $allPassed = false;
        break;
    }
}

if ($isCli) {
    echo "========================================================\n";
    echo " EPS Korean Trainer - Environment Diagnostics\n";
    echo "========================================================\n";
    foreach ($checks as $chk) {
        $status = $chk['pass'] ? '[OK]' : '[FAIL]';
        echo sprintf("%-6s %-40s : %s\n", $status, $chk['name'], $chk['detail']);
    }
    echo "========================================================\n";
    echo $allPassed ? "RESULT: System is READY for webhosting deployment!\n" : "RESULT: Please fix failed items above before deployment.\n";
    exit($allPassed ? 0 : 1);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Health Check - <?= htmlspecialchars(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-6 font-sans">
    <div class="max-w-3xl mx-auto bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
        <div class="p-6 bg-slate-800/80 border-b border-slate-700 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    🇰🇷 <?= htmlspecialchars(APP_NAME) ?> Diagnostic
                </h1>
                <p class="text-sm text-slate-400 mt-1">Webhosting Deployment & Server Compatibility Check</p>
            </div>
            <div>
                <?php if ($allPassed): ?>
                    <span class="px-4 py-2 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full font-semibold text-sm">✓ Ready for Production</span>
                <?php else: ?>
                    <span class="px-4 py-2 bg-rose-500/20 text-rose-400 border border-rose-500/30 rounded-full font-semibold text-sm">⚠️ Configuration Required</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm bg-slate-900/50 p-4 rounded-lg border border-slate-700/50">
                <div><span class="text-slate-400">Environment Mode:</span> <strong class="text-amber-400"><?= htmlspecialchars(APP_ENV) ?></strong></div>
                <div><span class="text-slate-400">Application Base URL:</span> <strong class="text-blue-400"><?= htmlspecialchars(APP_URL) ?></strong></div>
                <div><span class="text-slate-400">PHP Version:</span> <strong class="text-slate-200"><?= PHP_VERSION ?></strong></div>
                <div><span class="text-slate-400">Timezone:</span> <strong class="text-slate-200"><?= date_default_timezone_get() ?></strong></div>
            </div>

            <div class="divide-y divide-slate-700/60 border border-slate-700 rounded-lg overflow-hidden">
                <?php foreach ($checks as $chk): ?>
                    <div class="p-4 bg-slate-800/40 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <?php if ($chk['pass']): ?>
                                <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs border border-emerald-500/30">✓</span>
                            <?php else: ?>
                                <span class="w-6 h-6 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-xs border border-rose-500/30">✕</span>
                            <?php endif; ?>
                            <span class="font-medium text-slate-200"><?= htmlspecialchars($chk['name']) ?></span>
                        </div>
                        <span class="text-sm font-mono <?= $chk['pass'] ? 'text-slate-400' : 'text-rose-400 font-semibold' ?>">
                            <?= htmlspecialchars($chk['detail']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pt-4 flex justify-between items-center text-xs text-slate-400">
                <span>For security, delete or restrict <code>health.php</code> after completing setup.</span>
                <a href="<?= htmlspecialchars(APP_URL) ?>/login.php" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition font-medium text-sm">Go to Application →</a>
            </div>
        </div>
    </div>
</body>
</html>
