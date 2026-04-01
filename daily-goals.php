<?php
$pageTitle = 'Daily Goals';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

// Get user profile for targets
$stmt = $db->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
$dailyTarget = $profile ? $profile['daily_target'] : 20;

$today = date('Y-m-d');

// Ensure today's goal exists
$stmt = $db->prepare("SELECT * FROM daily_goals WHERE user_id = ? AND goal_date = ?");
$stmt->execute([$userId, $today]);
$todayGoal = $stmt->fetch();

if (!$todayGoal) {
    $stmt = $db->prepare("INSERT INTO daily_goals (user_id, goal_date, target_words, target_lessons, target_listening, target_reading) VALUES (?, ?, ?, 1, 10, 5)");
    $stmt->execute([$userId, $today, $dailyTarget]);
    $stmt = $db->prepare("SELECT * FROM daily_goals WHERE user_id = ? AND goal_date = ?");
    $stmt->execute([$userId, $today]);
    $todayGoal = $stmt->fetch();
}

// Handle goal update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goals'])) {
    if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $tw = max(1, (int)$_POST['target_words']);
        $tl = max(1, (int)$_POST['target_lessons']);
        $tli = max(1, (int)$_POST['target_listening']);
        $tr = max(1, (int)$_POST['target_reading']);
        $stmt = $db->prepare("UPDATE daily_goals SET target_words = ?, target_lessons = ?, target_listening = ?, target_reading = ? WHERE user_id = ? AND goal_date = ?");
        $stmt->execute([$tw, $tl, $tli, $tr, $userId, $today]);
        setFlash('success', 'Goals updated!');
        redirect(APP_URL . '/daily-goals.php');
    }
}

// Streak
$streak = getUserStreak($userId);

// Get last 7 days
$weekGoals = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT * FROM daily_goals WHERE user_id = ? AND goal_date = ?");
    $stmt->execute([$userId, $date]);
    $g = $stmt->fetch();
    $weekGoals[] = [
        'date' => $date,
        'day' => date('D', strtotime($date)),
        'goal' => $g,
        'completed' => $g && $g['is_completed']
    ];
}

// Calculate today's completion percentages
$wordsPct = calcPercent($todayGoal['completed_words'], $todayGoal['target_words']);
$lessonsPct = calcPercent($todayGoal['completed_lessons'], $todayGoal['target_lessons']);
$listeningPct = calcPercent($todayGoal['completed_listening'], $todayGoal['target_listening']);
$readingPct = calcPercent($todayGoal['completed_reading'], $todayGoal['target_reading']);
$overallPct = ($wordsPct + $lessonsPct + $listeningPct + $readingPct) / 4;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Streak & Overview -->
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl border border-orange-100 p-6 text-center">
        <span class="text-4xl">🔥</span>
        <p class="text-3xl font-bold text-orange-600 mt-2"><?= $streak ?></p>
        <p class="text-sm text-orange-500">Day Streak</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-6 text-center">
        <div class="relative w-24 h-24 mx-auto">
            <svg class="w-24 h-24" style="transform:rotate(-90deg)" viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="52" fill="none" stroke="#F1F5F9" stroke-width="10"/>
                <circle cx="60" cy="60" r="52" fill="none" stroke="<?= $overallPct >= 100 ? '#22C55E' : '#3B82F6' ?>" stroke-width="10" stroke-linecap="round"
                    stroke-dasharray="<?= 2 * 3.14159 * 52 ?>" stroke-dashoffset="<?= 2 * 3.14159 * 52 * (1 - min($overallPct, 100) / 100) ?>"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-xl font-bold text-gray-900"><?= round($overallPct) ?>%</span>
            </div>
        </div>
        <p class="text-sm text-gray-500 mt-2">Today's Progress</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-6 text-center">
        <p class="text-sm text-gray-500 mb-3"><?= date('l, M d', strtotime($today)) ?></p>
        <?php if ($overallPct >= 100): ?>
            <span class="text-4xl">🎉</span>
            <p class="text-sm font-semibold text-green-600 mt-2">All goals completed!</p>
        <?php else: ?>
            <span class="text-4xl">💪</span>
            <p class="text-sm font-medium text-gray-600 mt-2">Keep going!</p>
        <?php endif; ?>
    </div>
</div>

<!-- Weekly Calendar -->
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-base font-semibold text-gray-900 mb-4">This Week</h3>
    <div class="grid grid-cols-7 gap-2">
        <?php foreach ($weekGoals as $wg): ?>
        <div class="text-center p-3 rounded-xl <?= $wg['date'] === $today ? 'bg-blue-50 border-2 border-blue-200' : 'bg-gray-50' ?>">
            <p class="text-xs font-medium text-gray-500 mb-1"><?= $wg['day'] ?></p>
            <p class="text-xs text-gray-400"><?= date('d', strtotime($wg['date'])) ?></p>
            <div class="mt-2">
                <?php if ($wg['completed']): ?>
                    <span class="text-green-500 text-lg">✓</span>
                <?php elseif ($wg['goal']): ?>
                    <span class="text-amber-500 text-lg">◐</span>
                <?php else: ?>
                    <span class="text-gray-300 text-lg">○</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Today's Tasks -->
<div class="grid sm:grid-cols-2 gap-4 mb-6">
    <!-- Words -->
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-sm">📝</div>
                <span class="text-sm font-semibold text-gray-900">Study Words</span>
            </div>
            <span class="text-xs font-medium <?= $wordsPct >= 100 ? 'text-green-600' : 'text-gray-400' ?>"><?= $todayGoal['completed_words'] ?>/<?= $todayGoal['target_words'] ?></span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5">
            <div class="bg-blue-500 h-2.5 rounded-full transition-all" style="width:<?= min($wordsPct, 100) ?>%"></div>
        </div>
        <?php if ($wordsPct < 100): ?>
        <a href="<?= APP_URL ?>/vocabulary.php" class="block mt-3 text-xs text-blue-600 font-medium hover:underline">Study vocabulary →</a>
        <?php else: ?>
        <p class="mt-3 text-xs text-green-600 font-medium">✓ Completed!</p>
        <?php endif; ?>
    </div>

    <!-- Lessons -->
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-sm">📖</div>
                <span class="text-sm font-semibold text-gray-900">Complete Lessons</span>
            </div>
            <span class="text-xs font-medium <?= $lessonsPct >= 100 ? 'text-green-600' : 'text-gray-400' ?>"><?= $todayGoal['completed_lessons'] ?>/<?= $todayGoal['target_lessons'] ?></span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5">
            <div class="bg-emerald-500 h-2.5 rounded-full transition-all" style="width:<?= min($lessonsPct, 100) ?>%"></div>
        </div>
        <?php if ($lessonsPct < 100): ?>
        <a href="<?= APP_URL ?>/lessons.php" class="block mt-3 text-xs text-emerald-600 font-medium hover:underline">Go to lessons →</a>
        <?php else: ?>
        <p class="mt-3 text-xs text-green-600 font-medium">✓ Completed!</p>
        <?php endif; ?>
    </div>

    <!-- Listening -->
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-sm">🎧</div>
                <span class="text-sm font-semibold text-gray-900">Listening Practice</span>
            </div>
            <span class="text-xs font-medium <?= $listeningPct >= 100 ? 'text-green-600' : 'text-gray-400' ?>"><?= $todayGoal['completed_listening'] ?>/<?= $todayGoal['target_listening'] ?></span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5">
            <div class="bg-purple-500 h-2.5 rounded-full transition-all" style="width:<?= min($listeningPct, 100) ?>%"></div>
        </div>
        <?php if ($listeningPct < 100): ?>
        <a href="<?= APP_URL ?>/listening.php" class="block mt-3 text-xs text-purple-600 font-medium hover:underline">Practice listening →</a>
        <?php else: ?>
        <p class="mt-3 text-xs text-green-600 font-medium">✓ Completed!</p>
        <?php endif; ?>
    </div>

    <!-- Reading -->
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-sm">📄</div>
                <span class="text-sm font-semibold text-gray-900">Reading Practice</span>
            </div>
            <span class="text-xs font-medium <?= $readingPct >= 100 ? 'text-green-600' : 'text-gray-400' ?>"><?= $todayGoal['completed_reading'] ?>/<?= $todayGoal['target_reading'] ?></span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5">
            <div class="bg-amber-500 h-2.5 rounded-full transition-all" style="width:<?= min($readingPct, 100) ?>%"></div>
        </div>
        <?php if ($readingPct < 100): ?>
        <a href="<?= APP_URL ?>/reading.php" class="block mt-3 text-xs text-amber-600 font-medium hover:underline">Practice reading →</a>
        <?php else: ?>
        <p class="mt-3 text-xs text-green-600 font-medium">✓ Completed!</p>
        <?php endif; ?>
    </div>
</div>

<!-- Adjust Goals -->
<div class="bg-white rounded-2xl border border-gray-100 p-6">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Adjust Today's Goals</h3>
    <form method="POST" class="grid sm:grid-cols-4 gap-4">
        <?= csrfField() ?>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Words to study</label>
            <input type="number" name="target_words" value="<?= $todayGoal['target_words'] ?>" min="1" max="100" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Lessons</label>
            <input type="number" name="target_lessons" value="<?= $todayGoal['target_lessons'] ?>" min="1" max="20" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Listening items</label>
            <input type="number" name="target_listening" value="<?= $todayGoal['target_listening'] ?>" min="1" max="50" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Reading items</label>
            <input type="number" name="target_reading" value="<?= $todayGoal['target_reading'] ?>" min="1" max="50" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-blue-500 outline-none">
        </div>
        <div class="sm:col-span-4">
            <button type="submit" name="update_goals" value="1" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition">Update Goals</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
