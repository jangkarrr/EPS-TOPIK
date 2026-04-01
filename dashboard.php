<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$user = getCurrentUser();
$db = getDB();

// Fetch dashboard stats
$streak = getUserStreak($userId);

// Lessons completed
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM lesson_completions WHERE user_id = ?");
$stmt->execute([$userId]);
$lessonsCompleted = (int)$stmt->fetch()['cnt'];
$totalLessons = getCount('lessons', "status = 'published'");

// Vocabulary mastered
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM user_vocabulary_status WHERE user_id = ? AND status = 'mastered'");
$stmt->execute([$userId]);
$vocabMastered = (int)$stmt->fetch()['cnt'];
$totalVocab = getCount('vocabulary', "status = 'active'");

// Latest quiz score
$stmt = $db->prepare("SELECT q.title, qa.score, qa.total_questions, qa.percentage FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.user_id = ? ORDER BY qa.completed_at DESC LIMIT 1");
$stmt->execute([$userId]);
$latestQuiz = $stmt->fetch();

// Latest mock exam
$stmt = $db->prepare("SELECT me.title, mea.total_score, mea.percentage, mea.listening_score, mea.reading_score FROM mock_exam_attempts mea JOIN mock_exams me ON mea.exam_id = me.id WHERE mea.user_id = ? AND mea.status = 'completed' ORDER BY mea.completed_at DESC LIMIT 1");
$stmt->execute([$userId]);
$latestExam = $stmt->fetch();

// Daily goal progress
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM daily_goals WHERE user_id = ? AND goal_date = ?");
$stmt->execute([$userId, $today]);
$dailyGoal = $stmt->fetch();

// Weekly activity for chart
$weekData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM activity_log WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$userId, $date]);
    $weekData[] = ['day' => date('D', strtotime($date)), 'count' => (int)$stmt->fetch()['cnt']];
}

// Recent activity
$stmt = $db->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentActivity = $stmt->fetchAll();

// Mistake count
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM mistake_reviews WHERE user_id = ? AND is_reviewed = 0");
$stmt->execute([$userId]);
$unreviewedMistakes = (int)$stmt->fetch()['cnt'];

// Listening accuracy
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(is_correct) as correct FROM quiz_attempt_answers qaa JOIN quiz_attempts qa ON qaa.attempt_id = qa.id JOIN quiz_questions qq ON qaa.question_id = qq.id JOIN quizzes q ON qq.quiz_id = q.id WHERE qa.user_id = ? AND q.quiz_type = 'listening'");
$stmt->execute([$userId]);
$listeningStats = $stmt->fetch();
$listeningAccuracy = calcPercent((int)($listeningStats['correct'] ?? 0), (int)($listeningStats['total'] ?? 0));

// Reading accuracy  
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(is_correct) as correct FROM quiz_attempt_answers qaa JOIN quiz_attempts qa ON qaa.attempt_id = qa.id JOIN quiz_questions qq ON qaa.question_id = qq.id JOIN quizzes q ON qq.quiz_id = q.id WHERE qa.user_id = ? AND q.quiz_type = 'reading'");
$stmt->execute([$userId]);
$readingStats = $stmt->fetch();
$readingAccuracy = calcPercent((int)($readingStats['correct'] ?? 0), (int)($readingStats['total'] ?? 0));

require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Card -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-6 sm:p-8 text-white mb-6 relative overflow-hidden">
    <div class="absolute right-0 top-0 opacity-10 text-[120px] font-bold leading-none select-none" style="font-family:'Noto Sans KR'">한국어</div>
    <div class="relative z-10">
        <p class="text-blue-100 text-sm mb-1">Welcome back,</p>
        <h1 class="text-2xl sm:text-3xl font-bold mb-3"><?= sanitize($user['name']) ?> 👋</h1>
        <p class="text-blue-100 text-sm mb-4">Keep up the great work! You're on a <span class="text-white font-semibold"><?= $streak ?>-day streak</span>.</p>
        <div class="flex flex-wrap gap-3">
            <a href="<?= APP_URL ?>/lessons.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur rounded-xl text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Continue Learning
            </a>
            <a href="<?= APP_URL ?>/mock-exam.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur rounded-xl text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Take Mock Exam
            </a>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Streak -->
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                <span class="text-lg">🔥</span>
            </div>
            <span class="text-xs font-medium text-orange-500 bg-orange-50 px-2 py-1 rounded-full">Daily</span>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $streak ?></p>
        <p class="text-xs text-gray-400 mt-0.5">Day Streak</p>
    </div>

    <!-- Lessons -->
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $lessonsCompleted ?><span class="text-sm font-normal text-gray-400">/<?= $totalLessons ?></span></p>
        <p class="text-xs text-gray-400 mt-0.5">Lessons Done</p>
    </div>

    <!-- Vocab -->
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $vocabMastered ?><span class="text-sm font-normal text-gray-400">/<?= $totalVocab ?></span></p>
        <p class="text-xs text-gray-400 mt-0.5">Words Mastered</p>
    </div>

    <!-- Quiz Score -->
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $latestQuiz ? round($latestQuiz['percentage']) . '%' : '--' ?></p>
        <p class="text-xs text-gray-400 mt-0.5">Latest Quiz</p>
    </div>
</div>

<!-- Quick Actions & Daily Progress -->
<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <!-- Quick Actions -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="<?= APP_URL ?>/vocabulary.php" class="group flex flex-col items-center gap-2 p-4 rounded-xl bg-blue-50 hover:bg-blue-100 transition text-center">
                <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-700">Study Vocab</span>
            </a>
            <a href="<?= APP_URL ?>/listening.php" class="group flex flex-col items-center gap-2 p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition text-center">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-700">Listening</span>
            </a>
            <a href="<?= APP_URL ?>/reading.php" class="group flex flex-col items-center gap-2 p-4 rounded-xl bg-amber-50 hover:bg-amber-100 transition text-center">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-700">Reading</span>
            </a>
            <a href="<?= APP_URL ?>/quizzes.php" class="group flex flex-col items-center gap-2 p-4 rounded-xl bg-purple-50 hover:bg-purple-100 transition text-center">
                <div class="w-10 h-10 rounded-xl bg-purple-500 text-white flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-700">Take Quiz</span>
            </a>
        </div>

        <?php if ($unreviewedMistakes > 0): ?>
        <div class="mt-4 p-4 rounded-xl bg-red-50 border border-red-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-red-700">You have <?= $unreviewedMistakes ?> unreviewed mistake<?= $unreviewedMistakes > 1 ? 's' : '' ?></p>
                    <p class="text-xs text-red-500">Review them to improve your score</p>
                </div>
            </div>
            <a href="<?= APP_URL ?>/review-mistakes.php" class="text-xs font-semibold text-red-600 hover:underline">Review Now</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Daily Progress -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Today's Progress</h3>
        <?php
        $goalWords = $dailyGoal ? $dailyGoal['target_words'] : 20;
        $doneWords = $dailyGoal ? $dailyGoal['completed_words'] : 0;
        $goalPercent = calcPercent($doneWords, $goalWords);
        ?>
        <div class="flex justify-center mb-4">
            <div class="relative w-28 h-28">
                <svg class="w-28 h-28 progress-ring" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#F1F5F9" stroke-width="10"/>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#3B82F6" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="<?= 2 * 3.14159 * 52 ?>" 
                        stroke-dashoffset="<?= 2 * 3.14159 * 52 * (1 - $goalPercent / 100) ?>"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-xl font-bold text-gray-900"><?= round($goalPercent) ?>%</span>
                    <span class="text-[10px] text-gray-400">completed</span>
                </div>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Words studied</span>
                <span class="font-medium text-gray-900"><?= $doneWords ?>/<?= $goalWords ?></span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Lessons</span>
                <span class="font-medium text-gray-900"><?= $dailyGoal ? $dailyGoal['completed_lessons'] : 0 ?>/<?= $dailyGoal ? $dailyGoal['target_lessons'] : 1 ?></span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Listening</span>
                <span class="font-medium text-gray-900"><?= $dailyGoal ? $dailyGoal['completed_listening'] : 0 ?>/<?= $dailyGoal ? $dailyGoal['target_listening'] : 10 ?></span>
            </div>
        </div>
        <a href="<?= APP_URL ?>/daily-goals.php" class="mt-4 block text-center text-xs font-semibold text-blue-600 hover:underline">View Full Plan</a>
    </div>
</div>

<!-- Weekly Chart & Performance -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <!-- Weekly Activity Chart -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Weekly Activity</h3>
        <canvas id="weeklyChart" height="200"></canvas>
    </div>

    <!-- Performance Cards -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Performance</h3>
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="text-gray-600">Listening Accuracy</span>
                    <span class="font-semibold text-gray-900"><?= $listeningAccuracy ?>%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all" style="width: <?= $listeningAccuracy ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="text-gray-600">Reading Accuracy</span>
                    <span class="font-semibold text-gray-900"><?= $readingAccuracy ?>%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-blue-500 h-2.5 rounded-full transition-all" style="width: <?= $readingAccuracy ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="text-gray-600">Vocabulary Progress</span>
                    <span class="font-semibold text-gray-900"><?= calcPercent($vocabMastered, $totalVocab) ?>%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-purple-500 h-2.5 rounded-full transition-all" style="width: <?= calcPercent($vocabMastered, $totalVocab) ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="text-gray-600">Lessons Progress</span>
                    <span class="font-semibold text-gray-900"><?= calcPercent($lessonsCompleted, $totalLessons) ?>%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-amber-500 h-2.5 rounded-full transition-all" style="width: <?= calcPercent($lessonsCompleted, $totalLessons) ?>%"></div>
                </div>
            </div>

            <?php if ($latestExam): ?>
            <div class="mt-2 p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                <p class="text-xs font-medium text-indigo-700">Latest Mock Exam</p>
                <p class="text-lg font-bold text-indigo-900"><?= round($latestExam['percentage']) ?>%</p>
                <p class="text-xs text-indigo-500">L: <?= $latestExam['listening_score'] ?> | R: <?= $latestExam['reading_score'] ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Recent Activity</h3>
    <?php if (empty($recentActivity)): ?>
    <div class="text-center py-8">
        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-sm text-gray-400">No activity yet. Start learning!</p>
        <a href="<?= APP_URL ?>/lessons.php" class="inline-block mt-3 text-sm font-semibold text-blue-600 hover:underline">Start your first lesson</a>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($recentActivity as $activity): ?>
        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                <?php
                $icons = [
                    'lesson' => '📖', 'vocabulary' => '📝', 'listening' => '🎧',
                    'reading' => '📄', 'quiz' => '✅', 'exam' => '📋'
                ];
                echo $icons[$activity['activity_type']] ?? '📌';
                ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 truncate"><?= sanitize($activity['description']) ?></p>
                <p class="text-xs text-gray-400"><?= timeAgo($activity['created_at']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Weekly Activity Chart
const ctx = document.getElementById('weeklyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($weekData, 'day')) ?>,
        datasets: [{
            label: 'Activities',
            data: <?= json_encode(array_column($weekData, 'count')) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.15)',
            borderColor: 'rgba(59, 130, 246, 0.8)',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#F1F5F9' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
