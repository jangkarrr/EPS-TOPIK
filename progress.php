<?php
$pageTitle = 'Progress';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

// Overall stats
$streak = getUserStreak($userId);
$lessonsCompleted = getCount('lesson_completions', 'user_id = ?', [$userId]);
$totalLessons = getCount('lessons', "status = 'published'");

$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='mastered' THEN 1 ELSE 0 END) as mastered FROM user_vocabulary_status WHERE user_id = ?");
$stmt->execute([$userId]);
$vocabStats = $stmt->fetch();

$totalVocab = getCount('vocabulary', "status = 'active'");
$vocabMastered = (int)($vocabStats['mastered'] ?? 0);

// Quiz stats
$stmt = $db->prepare("SELECT COUNT(*) as attempts, AVG(percentage) as avg_score, MAX(percentage) as best_score FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$userId]);
$quizStats = $stmt->fetch();

// Mock exam stats
$stmt = $db->prepare("SELECT COUNT(*) as attempts, AVG(percentage) as avg_score, MAX(percentage) as best_score, AVG(listening_score) as avg_listening, AVG(reading_score) as avg_reading FROM mock_exam_attempts WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$examStats = $stmt->fetch();

// Listening accuracy
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(is_correct) as correct FROM quiz_attempt_answers qaa JOIN quiz_attempts qa ON qaa.attempt_id = qa.id JOIN quiz_questions qq ON qaa.question_id = qq.id JOIN quizzes q ON qq.quiz_id = q.id WHERE qa.user_id = ? AND q.quiz_type = 'listening'");
$stmt->execute([$userId]);
$lStats = $stmt->fetch();
$listeningAcc = calcPercent((int)($lStats['correct'] ?? 0), (int)($lStats['total'] ?? 0));

// Reading accuracy
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(is_correct) as correct FROM quiz_attempt_answers qaa JOIN quiz_attempts qa ON qaa.attempt_id = qa.id JOIN quiz_questions qq ON qaa.question_id = qq.id JOIN quizzes q ON qq.quiz_id = q.id WHERE qa.user_id = ? AND q.quiz_type = 'reading'");
$stmt->execute([$userId]);
$rStats = $stmt->fetch();
$readingAcc = calcPercent((int)($rStats['correct'] ?? 0), (int)($rStats['total'] ?? 0));

// Mistakes stats
$totalMistakes = getCount('mistake_reviews', 'user_id = ?', [$userId]);
$unreviewedMistakes = getCount('mistake_reviews', 'user_id = ? AND is_reviewed = 0', [$userId]);

// Weekly quiz scores for chart
$weekScores = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT AVG(percentage) as avg_score FROM quiz_attempts WHERE user_id = ? AND DATE(completed_at) = ?");
    $stmt->execute([$userId, $date]);
    $r = $stmt->fetch();
    $weekScores[] = ['day' => date('D', strtotime($date)), 'score' => round($r['avg_score'] ?? 0, 1)];
}

// Activity over last 30 days for chart
$monthActivity = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM activity_log WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$userId, $date]);
    $monthActivity[] = ['date' => date('M d', strtotime($date)), 'count' => (int)$stmt->fetch()['cnt']];
}

// Longest streak
$stmt = $db->prepare("SELECT longest_streak FROM user_streaks WHERE user_id = ?");
$stmt->execute([$userId]);
$longestStreak = (int)($stmt->fetch()['longest_streak'] ?? 0);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Overview Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5 text-center">
        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center mx-auto mb-2"><span class="text-lg">🔥</span></div>
        <p class="text-2xl font-bold text-gray-900"><?= $streak ?></p>
        <p class="text-xs text-gray-400">Current Streak</p>
        <p class="text-[10px] text-gray-300 mt-1">Best: <?= $longestStreak ?></p>
    </div>
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5 text-center">
        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mx-auto mb-2">📖</div>
        <p class="text-2xl font-bold text-gray-900"><?= $lessonsCompleted ?><span class="text-sm font-normal text-gray-400">/<?= $totalLessons ?></span></p>
        <p class="text-xs text-gray-400">Lessons</p>
    </div>
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5 text-center">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mx-auto mb-2">📝</div>
        <p class="text-2xl font-bold text-gray-900"><?= $vocabMastered ?><span class="text-sm font-normal text-gray-400">/<?= $totalVocab ?></span></p>
        <p class="text-xs text-gray-400">Vocab Mastered</p>
    </div>
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5 text-center">
        <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mx-auto mb-2">✏️</div>
        <p class="text-2xl font-bold text-gray-900"><?= $quizStats['attempts'] ?? 0 ?></p>
        <p class="text-xs text-gray-400">Quiz Attempts</p>
    </div>
</div>

<!-- Accuracy Cards -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 mb-2">Listening Accuracy</p>
        <div class="flex items-end gap-2">
            <span class="text-2xl font-bold text-blue-600"><?= $listeningAcc ?>%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
            <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $listeningAcc ?>%"></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 mb-2">Reading Accuracy</p>
        <div class="flex items-end gap-2">
            <span class="text-2xl font-bold text-emerald-600"><?= $readingAcc ?>%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
            <div class="bg-emerald-500 h-2 rounded-full" style="width:<?= $readingAcc ?>%"></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 mb-2">Avg Quiz Score</p>
        <div class="flex items-end gap-2">
            <span class="text-2xl font-bold text-purple-600"><?= round($quizStats['avg_score'] ?? 0) ?>%</span>
        </div>
        <p class="text-xs text-gray-400 mt-1">Best: <?= round($quizStats['best_score'] ?? 0) ?>%</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 mb-2">Avg Mock Exam</p>
        <div class="flex items-end gap-2">
            <span class="text-2xl font-bold text-indigo-600"><?= round($examStats['avg_score'] ?? 0) ?>%</span>
        </div>
        <p class="text-xs text-gray-400 mt-1"><?= $examStats['attempts'] ?? 0 ?> attempt<?= ($examStats['attempts'] ?? 0) != 1 ? 's' : '' ?></p>
    </div>
</div>

<!-- Charts -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Weekly Quiz Performance</h3>
        <canvas id="quizChart" height="220"></canvas>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Monthly Activity</h3>
        <canvas id="activityChart" height="220"></canvas>
    </div>
</div>

<!-- Mastery Breakdown -->
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Mastery Breakdown</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Lessons</span>
                <span class="text-sm font-bold text-blue-600"><?= calcPercent($lessonsCompleted, $totalLessons) ?>%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3">
                <div class="bg-blue-500 h-3 rounded-full transition-all" style="width:<?= calcPercent($lessonsCompleted, $totalLessons) ?>%"></div>
            </div>
        </div>
        <div class="p-4 rounded-xl border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Vocabulary</span>
                <span class="text-sm font-bold text-emerald-600"><?= calcPercent($vocabMastered, $totalVocab) ?>%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3">
                <div class="bg-emerald-500 h-3 rounded-full transition-all" style="width:<?= calcPercent($vocabMastered, $totalVocab) ?>%"></div>
            </div>
        </div>
        <div class="p-4 rounded-xl border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Mistakes Reviewed</span>
                <span class="text-sm font-bold text-amber-600"><?= $totalMistakes > 0 ? calcPercent($totalMistakes - $unreviewedMistakes, $totalMistakes) : 100 ?>%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3">
                <div class="bg-amber-500 h-3 rounded-full transition-all" style="width:<?= $totalMistakes > 0 ? calcPercent($totalMistakes - $unreviewedMistakes, $totalMistakes) : 100 ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Achievements -->
<div class="bg-white rounded-2xl border border-gray-100 p-6">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Achievements</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $achievements = [
            ['icon' => '🔥', 'title' => 'First Steps', 'desc' => 'Complete first lesson', 'earned' => $lessonsCompleted >= 1],
            ['icon' => '📚', 'title' => 'Bookworm', 'desc' => 'Complete 5 lessons', 'earned' => $lessonsCompleted >= 5],
            ['icon' => '📝', 'title' => 'Word Collector', 'desc' => 'Master 10 words', 'earned' => $vocabMastered >= 10],
            ['icon' => '🎯', 'title' => 'Sharpshooter', 'desc' => '80%+ on a quiz', 'earned' => ($quizStats['best_score'] ?? 0) >= 80],
            ['icon' => '🏆', 'title' => 'Exam Ready', 'desc' => 'Pass a mock exam', 'earned' => ($examStats['best_score'] ?? 0) >= 40],
            ['icon' => '⚡', 'title' => 'Streaker', 'desc' => '7-day streak', 'earned' => $longestStreak >= 7],
            ['icon' => '💎', 'title' => 'Dedicated', 'desc' => '30-day streak', 'earned' => $longestStreak >= 30],
            ['icon' => '🌟', 'title' => 'Perfectionist', 'desc' => '100% on a quiz', 'earned' => ($quizStats['best_score'] ?? 0) >= 100],
        ];
        foreach ($achievements as $a):
        ?>
        <div class="p-4 rounded-xl border <?= $a['earned'] ? 'border-amber-200 bg-amber-50' : 'border-gray-100 bg-gray-50 opacity-50' ?> text-center">
            <span class="text-2xl"><?= $a['icon'] ?></span>
            <p class="text-xs font-semibold text-gray-900 mt-1"><?= $a['title'] ?></p>
            <p class="text-[10px] text-gray-400"><?= $a['desc'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Quiz Performance Chart
new Chart(document.getElementById('quizChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($weekScores, 'day')) ?>,
        datasets: [{
            label: 'Avg Score %',
            data: <?= json_encode(array_column($weekScores, 'score')) ?>,
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#8B5CF6'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { font: { size: 11 } }, grid: { color: '#F1F5F9' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});

// Monthly Activity Chart
new Chart(document.getElementById('activityChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($a) => $a['date'], array_filter($monthActivity, fn($k) => $k % 3 === 0, ARRAY_FILTER_USE_KEY))) ?>,
        datasets: [{
            label: 'Activities',
            data: <?= json_encode(array_column($monthActivity, 'count')) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.15)',
            borderColor: 'rgba(59, 130, 246, 0.6)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#F1F5F9' } },
            x: { ticks: { font: { size: 10 }, maxTicksLimit: 10 }, grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
