<?php
$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();

// User growth (last 30 days)
$userGrowth = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE DATE(created_at) = ? AND role = 'learner'");
    $stmt->execute([$date]);
    $userGrowth[] = ['date' => date('M d', strtotime($date)), 'count' => (int)$stmt->fetch()['cnt']];
}

// Quiz score distribution
$scoreDistribution = [];
$ranges = [
    ['label' => '0-20%', 'min' => 0, 'max' => 20],
    ['label' => '21-40%', 'min' => 21, 'max' => 40],
    ['label' => '41-60%', 'min' => 41, 'max' => 60],
    ['label' => '61-80%', 'min' => 61, 'max' => 80],
    ['label' => '81-100%', 'min' => 81, 'max' => 100],
];
foreach ($ranges as $r) {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM quiz_attempts WHERE percentage >= ? AND percentage <= ?");
    $stmt->execute([$r['min'], $r['max']]);
    $scoreDistribution[] = ['label' => $r['label'], 'count' => (int)$stmt->fetch()['cnt']];
}

// Mock exam pass/fail
$stmt = $db->query("SELECT 
    SUM(CASE WHEN mea.total_score >= me.passing_score THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN mea.total_score < me.passing_score THEN 1 ELSE 0 END) as failed
    FROM mock_exam_attempts mea JOIN mock_exams me ON mea.exam_id = me.id WHERE mea.status = 'completed'");
$examPassFail = $stmt->fetch();

// Activity by module
$stmt = $db->query("SELECT activity_type, COUNT(*) as cnt FROM activity_log GROUP BY activity_type ORDER BY cnt DESC");
$activityByModule = $stmt->fetchAll();

// Top learners by quiz score
$stmt = $db->query("SELECT u.full_name, u.email, COUNT(qa.id) as attempts, AVG(qa.percentage) as avg_score, MAX(qa.percentage) as best_score 
    FROM quiz_attempts qa JOIN users u ON qa.user_id = u.id 
    GROUP BY qa.user_id ORDER BY avg_score DESC LIMIT 10");
$topLearners = $stmt->fetchAll();

// Top learners by streak
$stmt = $db->query("SELECT u.full_name, u.email, us.current_streak, us.longest_streak 
    FROM user_streaks us JOIN users u ON us.user_id = u.id 
    ORDER BY us.current_streak DESC LIMIT 10");
$topStreaks = $stmt->fetchAll();

// Mistake analysis by module
$stmt = $db->query("SELECT module, COUNT(*) as cnt, SUM(is_reviewed) as reviewed FROM mistake_reviews GROUP BY module ORDER BY cnt DESC");
$mistakesByModule = $stmt->fetchAll();

// Daily active users (last 14 days)
$dau = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as cnt FROM activity_log WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $dau[] = ['date' => date('M d', strtotime($date)), 'count' => (int)$stmt->fetch()['cnt']];
}

// Content summary
$contentStats = [
    ['label' => 'Lessons', 'total' => getCount('lessons'), 'published' => getCount('lessons', "status='published'")],
    ['label' => 'Vocabulary', 'total' => getCount('vocabulary'), 'published' => getCount('vocabulary', "status='active'")],
    ['label' => 'Listening Q\'s', 'total' => getCount('listening_questions'), 'published' => getCount('listening_questions', "status='active'")],
    ['label' => 'Reading Passages', 'total' => getCount('reading_passages'), 'published' => getCount('reading_passages', "status='active'")],
    ['label' => 'Quizzes', 'total' => getCount('quizzes'), 'published' => getCount('quizzes', "status='active'")],
    ['label' => 'Mock Exams', 'total' => getCount('mock_exams'), 'published' => getCount('mock_exams', "status='active'")],
];

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Content Overview -->
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Content Overview</h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <?php foreach ($contentStats as $cs): ?>
        <div class="p-4 rounded-xl bg-gray-50 text-center">
            <p class="text-lg font-bold text-gray-900"><?= $cs['total'] ?></p>
            <p class="text-xs text-gray-400"><?= $cs['label'] ?></p>
            <p class="text-[10px] text-green-500 mt-1"><?= $cs['published'] ?> active</p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <!-- User Growth -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">User Growth (30 days)</h3>
        <canvas id="userGrowthChart" height="220"></canvas>
    </div>

    <!-- Daily Active Users -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Daily Active Users (14 days)</h3>
        <canvas id="dauChart" height="220"></canvas>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <!-- Quiz Score Distribution -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Quiz Score Distribution</h3>
        <canvas id="scoreChart" height="220"></canvas>
    </div>

    <!-- Activity by Module -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Activity by Module</h3>
        <canvas id="activityChart" height="220"></canvas>
    </div>
</div>

<!-- Mock Exam Pass/Fail + Mistakes -->
<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Mock Exam Results</h3>
        <canvas id="examChart" height="200"></canvas>
        <div class="flex justify-center gap-6 mt-4 text-sm">
            <span class="text-green-600 font-medium"><?= $examPassFail['passed'] ?? 0 ?> Passed</span>
            <span class="text-red-500 font-medium"><?= $examPassFail['failed'] ?? 0 ?> Failed</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Mistakes by Module</h3>
        <?php if (empty($mistakesByModule)): ?>
            <p class="text-sm text-gray-400 text-center py-8">No data yet.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($mistakesByModule as $m):
                $total = (int)$m['cnt'];
                $reviewed = (int)$m['reviewed'];
                $pct = calcPercent($reviewed, $total);
                $moduleColors = ['listening' => 'bg-blue-500', 'reading' => 'bg-emerald-500', 'quiz' => 'bg-purple-500', 'mock_exam' => 'bg-indigo-500'];
            ?>
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-700 capitalize font-medium"><?= str_replace('_', ' ', $m['activity_type'] ?? $m['module']) ?></span>
                    <span class="text-xs text-gray-400"><?= $reviewed ?>/<?= $total ?> reviewed</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="<?= $moduleColors[$m['module']] ?? 'bg-gray-500' ?> h-2 rounded-full" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Quick Stats</h3>
        <div class="space-y-4">
            <?php
            $totalAttempts = getCount('quiz_attempts');
            $stmt = $db->query("SELECT AVG(percentage) as avg FROM quiz_attempts");
            $avgQuiz = round($stmt->fetch()['avg'] ?? 0, 1);
            $stmt = $db->query("SELECT AVG(percentage) as avg FROM mock_exam_attempts WHERE status='completed'");
            $avgExam = round($stmt->fetch()['avg'] ?? 0, 1);
            $totalActivities = getCount('activity_log');
            $totalMistakes = getCount('mistake_reviews');
            ?>
            <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50">
                <span class="text-xs text-blue-700">Total Quiz Attempts</span>
                <span class="text-sm font-bold text-blue-800"><?= $totalAttempts ?></span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50">
                <span class="text-xs text-emerald-700">Avg Quiz Score</span>
                <span class="text-sm font-bold text-emerald-800"><?= $avgQuiz ?>%</span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50">
                <span class="text-xs text-indigo-700">Avg Exam Score</span>
                <span class="text-sm font-bold text-indigo-800"><?= $avgExam ?>%</span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-xl bg-purple-50">
                <span class="text-xs text-purple-700">Total Activities</span>
                <span class="text-sm font-bold text-purple-800"><?= $totalActivities ?></span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-xl bg-red-50">
                <span class="text-xs text-red-700">Total Mistakes</span>
                <span class="text-sm font-bold text-red-800"><?= $totalMistakes ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Top Learners -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Top Learners (by Avg Score)</h3>
        <?php if (empty($topLearners)): ?>
            <p class="text-sm text-gray-400 text-center py-4">No data yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-2 text-xs font-semibold text-gray-500">#</th>
                        <th class="text-left py-2 text-xs font-semibold text-gray-500">Learner</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500">Avg</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500">Best</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500">Tries</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($topLearners as $idx => $l): ?>
                    <tr>
                        <td class="py-2 text-gray-400"><?= $idx + 1 ?></td>
                        <td class="py-2">
                            <p class="font-medium text-gray-900"><?= sanitize($l['full_name']) ?></p>
                            <p class="text-[10px] text-gray-400"><?= sanitize($l['email']) ?></p>
                        </td>
                        <td class="py-2 text-right font-semibold text-blue-600"><?= round($l['avg_score'], 1) ?>%</td>
                        <td class="py-2 text-right text-green-600"><?= round($l['best_score']) ?>%</td>
                        <td class="py-2 text-right text-gray-500"><?= $l['attempts'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Top Streaks</h3>
        <?php if (empty($topStreaks)): ?>
            <p class="text-sm text-gray-400 text-center py-4">No data yet.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($topStreaks as $idx => $s): ?>
            <div class="flex items-center gap-3 p-3 rounded-xl <?= $idx < 3 ? 'bg-amber-50' : 'bg-gray-50' ?>">
                <span class="w-7 h-7 rounded-full <?= $idx < 3 ? 'bg-amber-200 text-amber-800' : 'bg-gray-200 text-gray-600' ?> flex items-center justify-center text-xs font-bold"><?= $idx + 1 ?></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= sanitize($s['full_name']) ?></p>
                    <p class="text-[10px] text-gray-400"><?= sanitize($s['email']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-orange-600"><?= $s['current_streak'] ?> 🔥</p>
                    <p class="text-[10px] text-gray-400">Best: <?= $s['longest_streak'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// User Growth
new Chart(document.getElementById('userGrowthChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($u) => $u['date'], array_filter($userGrowth, fn($k) => $k % 3 === 0, ARRAY_FILTER_USE_KEY))) ?>,
        datasets: [{
            label: 'New Users',
            data: <?= json_encode(array_column($userGrowth, 'count')) ?>,
            borderColor: '#6366F1', backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 2, fill: true, tension: 0.4, pointRadius: 2
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } }, x: { ticks: { maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } } } }
});

// DAU
new Chart(document.getElementById('dauChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($dau, 'date')) ?>,
        datasets: [{
            label: 'Active Users',
            data: <?= json_encode(array_column($dau, 'count')) ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            borderColor: '#10B981', borderWidth: 2, borderRadius: 6, borderSkipped: false
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } }, x: { ticks: { font: { size: 10 } }, grid: { display: false } } } }
});

// Score Distribution
new Chart(document.getElementById('scoreChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($scoreDistribution, 'label')) ?>,
        datasets: [{
            label: 'Attempts',
            data: <?= json_encode(array_column($scoreDistribution, 'count')) ?>,
            backgroundColor: ['#EF4444', '#F59E0B', '#3B82F6', '#8B5CF6', '#22C55E'],
            borderRadius: 8, borderSkipped: false
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Activity by Module
new Chart(document.getElementById('activityChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($activityByModule, 'activity_type')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($activityByModule, 'cnt')) ?>,
            backgroundColor: ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444', '#6366F1'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 15 } } } }
});

// Exam Pass/Fail
new Chart(document.getElementById('examChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{
            data: [<?= $examPassFail['passed'] ?? 0 ?>, <?= $examPassFail['failed'] ?? 0 ?>],
            backgroundColor: ['#22C55E', '#EF4444'], borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '65%' }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
