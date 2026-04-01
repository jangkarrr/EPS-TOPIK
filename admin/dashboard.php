<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();

// Stats
$totalUsers = getCount('users', "role = 'learner'");
$activeUsers = getCount('users', "role = 'learner' AND last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$totalLessons = getCount('lessons');
$totalVocab = getCount('vocabulary');
$totalListening = getCount('listening_questions');
$totalReading = getCount('reading_passages');
$totalQuizAttempts = getCount('quiz_attempts');
$totalExamAttempts = getCount('mock_exam_attempts', "status = 'completed'");

// Average learner score
$stmt = $db->query("SELECT AVG(percentage) as avg_score FROM quiz_attempts");
$avgScore = round($stmt->fetch()['avg_score'] ?? 0, 1);

// Recent users
$recentUsers = $db->query("SELECT id, full_name, email, created_at, last_login FROM users WHERE role = 'learner' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent activity
$recentActivity = $db->query("SELECT al.*, u.full_name FROM activity_log al JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

// Weekly signups for chart
$weekSignups = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE DATE(created_at) = ? AND role = 'learner'");
    $stmt->execute([$date]);
    $weekSignups[] = ['day' => date('D', strtotime($date)), 'count' => (int)$stmt->fetch()['cnt']];
}

// Weekly quiz attempts
$weekQuizzes = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM quiz_attempts WHERE DATE(completed_at) = ?");
    $stmt->execute([$date]);
    $weekQuizzes[] = ['day' => date('D', strtotime($date)), 'count' => (int)$stmt->fetch()['cnt']];
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <span class="text-xs font-medium text-green-500 bg-green-50 px-2 py-0.5 rounded-full"><?= $activeUsers ?> active</span>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $totalUsers ?></p>
        <p class="text-xs text-gray-400">Total Learners</p>
    </div>
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $totalLessons ?></p>
        <p class="text-xs text-gray-400">Total Lessons</p>
    </div>
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $totalVocab ?></p>
        <p class="text-xs text-gray-400">Vocabulary Items</p>
    </div>
    <div class="stat-card bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $avgScore ?>%</p>
        <p class="text-xs text-gray-400">Avg Learner Score</p>
    </div>
</div>

<!-- Secondary Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-sm">🎧</div>
        <div><p class="text-lg font-bold text-gray-900"><?= $totalListening ?></p><p class="text-[10px] text-gray-400">Listening Q's</p></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-sm">📄</div>
        <div><p class="text-lg font-bold text-gray-900"><?= $totalReading ?></p><p class="text-[10px] text-gray-400">Reading Passages</p></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-sm">✏️</div>
        <div><p class="text-lg font-bold text-gray-900"><?= $totalQuizAttempts ?></p><p class="text-[10px] text-gray-400">Quiz Attempts</p></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-sm">📝</div>
        <div><p class="text-lg font-bold text-gray-900"><?= $totalExamAttempts ?></p><p class="text-[10px] text-gray-400">Exam Attempts</p></div>
    </div>
</div>

<!-- Charts -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">New Signups (7 days)</h3>
        <canvas id="signupsChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Quiz Activity (7 days)</h3>
        <canvas id="quizChart" height="200"></canvas>
    </div>
</div>

<!-- Recent Users & Activity -->
<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Recent Learners</h3>
            <a href="<?= APP_URL ?>/admin/users.php" class="text-xs text-indigo-600 font-medium hover:underline">View All</a>
        </div>
        <div class="space-y-3">
            <?php foreach ($recentUsers as $u): ?>
            <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-xs"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= sanitize($u['full_name']) ?></p>
                    <p class="text-xs text-gray-400"><?= sanitize($u['email']) ?></p>
                </div>
                <span class="text-xs text-gray-400"><?= timeAgo($u['created_at']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentUsers)): ?>
            <p class="text-sm text-gray-400 text-center py-4">No learners yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <div class="space-y-3">
            <?php foreach ($recentActivity as $a): ?>
            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                <div class="w-7 h-7 rounded-lg bg-gray-50 flex items-center justify-center text-xs">
                    <?php
                    $icons = ['lesson' => '📖', 'vocabulary' => '📝', 'listening' => '🎧', 'reading' => '📄', 'quiz' => '✏️', 'exam' => '📝'];
                    echo $icons[$a['activity_type']] ?? '📌';
                    ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-700 truncate"><strong><?= sanitize($a['full_name']) ?>:</strong> <?= sanitize($a['description']) ?></p>
                    <p class="text-[10px] text-gray-400"><?= timeAgo($a['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentActivity)): ?>
            <p class="text-sm text-gray-400 text-center py-4">No activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('signupsChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($weekSignups, 'day')) ?>,
        datasets: [{
            label: 'Signups',
            data: <?= json_encode(array_column($weekSignups, 'count')) ?>,
            backgroundColor: 'rgba(99, 102, 241, 0.15)',
            borderColor: 'rgba(99, 102, 241, 0.8)',
            borderWidth: 2, borderRadius: 8, borderSkipped: false,
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('quizChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($weekQuizzes, 'day')) ?>,
        datasets: [{
            label: 'Attempts',
            data: <?= json_encode(array_column($weekQuizzes, 'count')) ?>,
            borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2, fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#10B981'
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
