<?php
$pageTitle = 'Voice / TTS Settings';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tts-helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save_settings') {
        $settingsToUpdate = [
            'tts_provider' => $_POST['tts_provider'] ?? 'browser_tts',
            'tts_fallback_enabled' => isset($_POST['tts_fallback_enabled']) ? '1' : '0',
            'tts_default_rate' => max(0.5, min(2.0, (float)($_POST['tts_default_rate'] ?? 1))),
            'tts_default_pitch' => max(0.5, min(2.0, (float)($_POST['tts_default_pitch'] ?? 1))),
            'tts_audio_preference' => $_POST['tts_audio_preference'] ?? 'uploaded_first',
            'tts_google_api_key' => trim($_POST['tts_google_api_key'] ?? ''),
            'tts_openai_api_key' => trim($_POST['tts_openai_api_key'] ?? ''),
            'tts_cache_enabled' => isset($_POST['tts_cache_enabled']) ? '1' : '0'
        ];

        foreach ($settingsToUpdate as $key => $value) {
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'voice') ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        setFlash('success', 'Voice settings saved successfully.');
        redirect(APP_URL . '/admin/voice-settings.php');
    }

    if ($formAction === 'clear_cache') {
        $db->exec("DELETE FROM generated_audio WHERE status != 'ready'");
        $cleared = $db->exec("UPDATE generated_audio SET is_cached = 0");
        setFlash('success', 'Audio cache cleared.');
        redirect(APP_URL . '/admin/voice-settings.php');
    }

    if ($formAction === 'test_tts') {
        $testText = trim($_POST['test_text'] ?? '안녕하세요');
        $provider = $_POST['test_provider'] ?? 'browser_tts';

        if ($provider !== 'browser_tts') {
            $audioId = requestAudioGeneration($testText, 'phrase', null, $provider);
            $result = null;
            if ($provider === 'google_cloud') {
                $result = generateGoogleTTS($testText, $audioId);
            } elseif ($provider === 'openai') {
                $result = generateOpenAITTS($testText, $audioId);
            }
            if ($result) {
                setFlash('success', 'Audio generated successfully: ' . $result);
            } else {
                $err = $db->prepare("SELECT error_message FROM generated_audio WHERE id = ?");
                $err->execute([$audioId]);
                $errRow = $err->fetch();
                setFlash('error', 'Generation failed: ' . ($errRow['error_message'] ?? 'Unknown error'));
            }
        } else {
            setFlash('info', 'Browser TTS does not require server-side generation. Test it on a learner page.');
        }
        redirect(APP_URL . '/admin/voice-settings.php');
    }
}

// Load current settings
$settings = getSettingsByGroup('voice');

// Audio cache stats
$cacheStats = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(play_count) as total_plays
    FROM generated_audio")->fetch();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Voice / TTS Settings</h2>
            <p class="text-sm text-gray-400 mt-1">Configure AI-powered Korean voice playback</p>
        </div>
    </div>

    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save_settings">

        <!-- Provider Selection -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
                TTS Provider
            </h3>

            <div class="grid sm:grid-cols-3 gap-3 mb-5">
                <?php
                $providers = [
                    'browser_tts' => ['label' => 'Browser TTS', 'desc' => 'Free, built-in Web Speech API', 'badge' => 'Free', 'badgeColor' => 'bg-green-100 text-green-700'],
                    'google_cloud' => ['label' => 'Google Cloud TTS', 'desc' => 'High-quality Neural2 Korean voices', 'badge' => 'Premium', 'badgeColor' => 'bg-blue-100 text-blue-700'],
                    'openai' => ['label' => 'OpenAI TTS', 'desc' => 'Natural multilingual voices', 'badge' => 'Premium', 'badgeColor' => 'bg-purple-100 text-purple-700']
                ];
                $current = $settings['tts_provider'] ?? 'browser_tts';
                foreach ($providers as $key => $p):
                ?>
                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition hover:shadow-md
                    <?= $current === $key ? 'border-blue-500 bg-blue-50/50' : 'border-gray-100 hover:border-gray-200' ?>">
                    <input type="radio" name="tts_provider" value="<?= $key ?>" <?= $current === $key ? 'checked' : '' ?> class="hidden peer">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-900"><?= $p['label'] ?></span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $p['badgeColor'] ?>"><?= $p['badge'] ?></span>
                    </div>
                    <p class="text-xs text-gray-500"><?= $p['desc'] ?></p>
                    <div class="absolute top-3 right-3 w-4 h-4 rounded-full border-2 <?= $current === $key ? 'border-blue-500 bg-blue-500' : 'border-gray-300' ?> flex items-center justify-center">
                        <?php if ($current === $key): ?><div class="w-1.5 h-1.5 bg-white rounded-full"></div><?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="tts_fallback_enabled" value="1" <?= ($settings['tts_fallback_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Enable browser TTS as fallback when premium is unavailable</span>
                </label>
            </div>
        </div>

        <!-- Audio Preference -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Audio Source Priority</h3>
            <p class="text-xs text-gray-400 mb-3">When multiple audio sources are available for a Korean phrase, which should play first?</p>
            <div class="space-y-2">
                <?php
                $prefs = [
                    'uploaded_first' => ['label' => 'Uploaded audio first', 'desc' => 'Use admin-uploaded audio, then generated TTS, then browser'],
                    'generated_first' => ['label' => 'Generated TTS first', 'desc' => 'Use premium-generated audio, then uploaded, then browser'],
                    'browser_only' => ['label' => 'Browser TTS only', 'desc' => 'Always use browser speech synthesis (free, no server calls)']
                ];
                $currentPref = $settings['tts_audio_preference'] ?? 'uploaded_first';
                foreach ($prefs as $key => $p):
                ?>
                <label class="flex items-center gap-3 p-3 rounded-xl border <?= $currentPref === $key ? 'border-blue-200 bg-blue-50/30' : 'border-gray-100' ?> cursor-pointer hover:bg-gray-50 transition">
                    <input type="radio" name="tts_audio_preference" value="<?= $key ?>" <?= $currentPref === $key ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                    <div>
                        <span class="text-sm font-medium text-gray-900"><?= $p['label'] ?></span>
                        <p class="text-xs text-gray-400"><?= $p['desc'] ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Voice Settings -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Voice Defaults</h3>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Speech Rate</label>
                    <input type="range" name="tts_default_rate" min="0.5" max="2.0" step="0.1"
                        value="<?= $settings['tts_default_rate'] ?? '1' ?>"
                        oninput="document.getElementById('rate-val').textContent = this.value + 'x'"
                        class="w-full h-2 rounded-lg appearance-none bg-gray-200 cursor-pointer accent-blue-600">
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>0.5x (Slow)</span>
                        <span id="rate-val" class="font-semibold text-blue-600"><?= $settings['tts_default_rate'] ?? '1' ?>x</span>
                        <span>2.0x (Fast)</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Speech Pitch</label>
                    <input type="range" name="tts_default_pitch" min="0.5" max="2.0" step="0.1"
                        value="<?= $settings['tts_default_pitch'] ?? '1' ?>"
                        oninput="document.getElementById('pitch-val').textContent = this.value"
                        class="w-full h-2 rounded-lg appearance-none bg-gray-200 cursor-pointer accent-blue-600">
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>0.5 (Low)</span>
                        <span id="pitch-val" class="font-semibold text-blue-600"><?= $settings['tts_default_pitch'] ?? '1' ?></span>
                        <span>2.0 (High)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Keys -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Premium API Keys</h3>
            <p class="text-xs text-gray-400 mb-4">Required only if using a premium TTS provider</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Google Cloud TTS API Key</label>
                    <input type="password" name="tts_google_api_key" value="<?= sanitize($settings['tts_google_api_key'] ?? '') ?>"
                        placeholder="AIzaSy..." class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-mono">
                    <p class="text-xs text-gray-400 mt-1">Get a key from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-500 hover:underline">Google Cloud Console</a></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">OpenAI TTS API Key</label>
                    <input type="password" name="tts_openai_api_key" value="<?= sanitize($settings['tts_openai_api_key'] ?? '') ?>"
                        placeholder="sk-..." class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-mono">
                    <p class="text-xs text-gray-400 mt-1">Get a key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-500 hover:underline">OpenAI Dashboard</a></p>
                </div>
            </div>
        </div>

        <!-- Cache Settings -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Audio Cache</h3>
            <div class="flex items-center gap-3 mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="tts_cache_enabled" value="1" <?= ($settings['tts_cache_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Cache generated audio files</span>
                </label>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="p-3 rounded-xl bg-gray-50 text-center">
                    <p class="text-lg font-bold text-gray-900"><?= (int)($cacheStats['total'] ?? 0) ?></p>
                    <p class="text-xs text-gray-400">Total Files</p>
                </div>
                <div class="p-3 rounded-xl bg-green-50 text-center">
                    <p class="text-lg font-bold text-green-600"><?= (int)($cacheStats['ready'] ?? 0) ?></p>
                    <p class="text-xs text-gray-400">Ready</p>
                </div>
                <div class="p-3 rounded-xl bg-red-50 text-center">
                    <p class="text-lg font-bold text-red-600"><?= (int)($cacheStats['failed'] ?? 0) ?></p>
                    <p class="text-xs text-gray-400">Failed</p>
                </div>
                <div class="p-3 rounded-xl bg-blue-50 text-center">
                    <p class="text-lg font-bold text-blue-600"><?= number_format((int)($cacheStats['total_plays'] ?? 0)) ?></p>
                    <p class="text-xs text-gray-400">Total Plays</p>
                </div>
            </div>
        </div>

        <!-- Save -->
        <div class="flex items-center justify-between">
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm shadow-lg shadow-blue-600/20">
                Save Settings
            </button>
        </div>
    </form>

    <!-- Actions -->
    <div class="mt-6 bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Actions</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <!-- Test TTS -->
            <form method="POST" class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="test_tts">
                <input type="hidden" name="test_provider" value="<?= sanitize($settings['tts_provider'] ?? 'browser_tts') ?>">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">Test Premium TTS</h4>
                <input type="text" name="test_text" value="안녕하세요. 한국어를 공부합니다." placeholder="Korean text..." class="w-full px-3 py-2 rounded-lg border border-blue-200 text-sm mb-2">
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    Generate Test Audio
                </button>
            </form>

            <!-- Clear Cache -->
            <form method="POST" class="p-4 rounded-xl bg-amber-50 border border-amber-100" onsubmit="return confirm('Clear all cached audio? This cannot be undone.')">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="clear_cache">
                <h4 class="text-sm font-semibold text-amber-800 mb-2">Clear Audio Cache</h4>
                <p class="text-xs text-amber-600 mb-3">Remove all generated audio files and reset cache records.</p>
                <button type="submit" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition">
                    Clear Cache
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
