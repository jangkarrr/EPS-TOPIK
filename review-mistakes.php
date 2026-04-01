<?php
$pageTitle = 'Review Mistakes';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

$moduleFilter = $_GET['module'] ?? '';
$reviewedFilter = $_GET['reviewed'] ?? '';

// Handle mark as reviewed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_reviewed'])) {
    if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $mistakeId = (int)$_POST['mistake_id'];
        $stmt = $db->prepare("UPDATE mistake_reviews SET is_reviewed = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$mistakeId, $userId]);
        setFlash('success', 'Marked as reviewed.');
        redirect(APP_URL . '/review-mistakes.php?' . http_build_query($_GET));
    }
}

// Build query
$where = "mr.user_id = ?";
$params = [$userId];
if ($moduleFilter) { $where .= " AND mr.module = ?"; $params[] = $moduleFilter; }
if ($reviewedFilter === '0') { $where .= " AND mr.is_reviewed = 0"; }
elseif ($reviewedFilter === '1') { $where .= " AND mr.is_reviewed = 1"; }

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM mistake_reviews mr WHERE $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT mr.* FROM mistake_reviews mr WHERE $where ORDER BY mr.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$mistakes = $stmt->fetchAll();

// Stats
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_reviewed = 0 THEN 1 ELSE 0 END) as unreviewed,
    SUM(CASE WHEN module = 'listening' THEN 1 ELSE 0 END) as listening,
    SUM(CASE WHEN module = 'reading' THEN 1 ELSE 0 END) as reading,
    SUM(CASE WHEN module = 'quiz' THEN 1 ELSE 0 END) as quiz,
    SUM(CASE WHEN module = 'mock_exam' THEN 1 ELSE 0 END) as mock_exam
    FROM mistake_reviews WHERE user_id = ?");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-gray-900"><?= $stats['total'] ?></p>
        <p class="text-xs text-gray-400">Total Mistakes</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-red-500"><?= $stats['unreviewed'] ?></p>
        <p class="text-xs text-gray-400">Unreviewed</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-blue-600"><?= $stats['listening'] ?></p>
        <p class="text-xs text-gray-400">Listening</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-emerald-600"><?= $stats['reading'] ?></p>
        <p class="text-xs text-gray-400">Reading</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-purple-600"><?= ($stats['quiz'] ?? 0) + ($stats['mock_exam'] ?? 0) ?></p>
        <p class="text-xs text-gray-400">Quiz/Exam</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <select name="module" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="">All Modules</option>
            <option value="listening" <?= $moduleFilter === 'listening' ? 'selected' : '' ?>>Listening</option>
            <option value="reading" <?= $moduleFilter === 'reading' ? 'selected' : '' ?>>Reading</option>
            <option value="quiz" <?= $moduleFilter === 'quiz' ? 'selected' : '' ?>>Quiz</option>
            <option value="mock_exam" <?= $moduleFilter === 'mock_exam' ? 'selected' : '' ?>>Mock Exam</option>
        </select>
        <select name="reviewed" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="">All Status</option>
            <option value="0" <?= $reviewedFilter === '0' ? 'selected' : '' ?>>Unreviewed</option>
            <option value="1" <?= $reviewedFilter === '1' ? 'selected' : '' ?>>Reviewed</option>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Filter</button>
        <?php if ($moduleFilter || $reviewedFilter): ?>
        <a href="<?= APP_URL ?>/review-mistakes.php" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition text-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Mistakes List -->
<?php if (empty($mistakes)): ?>
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center mx-auto mb-3">
        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h3 class="text-sm font-semibold text-gray-900 mb-1">No mistakes found</h3>
    <p class="text-xs text-gray-400"><?= $totalItems > 0 ? 'Try adjusting your filters.' : 'Great job! Keep practicing to maintain accuracy.' ?></p>
</div>
<?php else: ?>
<div class="space-y-3">
    <?php foreach ($mistakes as $m):
        $moduleColors = ['listening' => 'bg-blue-50 text-blue-700', 'reading' => 'bg-emerald-50 text-emerald-700', 'quiz' => 'bg-purple-50 text-purple-700', 'mock_exam' => 'bg-indigo-50 text-indigo-700'];
        $moduleIcons = ['listening' => '🎧', 'reading' => '📖', 'quiz' => '✏️', 'mock_exam' => '📝'];
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 <?= $m['is_reviewed'] ? 'opacity-60' : '' ?>">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm"><?= $moduleIcons[$m['module']] ?? '📌' ?></span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-lg <?= $moduleColors[$m['module']] ?? 'bg-gray-50 text-gray-600' ?> capitalize"><?= str_replace('_', ' ', $m['module']) ?></span>
                    <?php if ($m['is_reviewed']): ?>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-lg bg-green-50 text-green-600">Reviewed</span>
                    <?php endif; ?>
                    <span class="text-xs text-gray-400 ml-auto"><?= timeAgo($m['created_at']) ?></span>
                </div>
                <p class="text-sm font-medium text-gray-900 mb-2"><?= sanitize($m['question_text']) ?></p>
                <div class="flex items-center gap-4 text-xs">
                    <span class="text-red-500">Your answer: <strong><?= sanitize($m['user_answer']) ?></strong></span>
                    <span class="text-green-600">Correct: <strong><?= sanitize($m['correct_answer']) ?></strong></span>
                </div>
                <?php if ($m['explanation']): ?>
                <div class="mt-2 p-3 rounded-lg bg-blue-50 text-xs text-blue-700">
                    💡 <?= sanitize($m['explanation']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!$m['is_reviewed']): ?>
            <form method="POST" class="flex-shrink-0">
                <?= csrfField() ?>
                <input type="hidden" name="mistake_id" value="<?= $m['id'] ?>">
                <button type="submit" name="mark_reviewed" value="1" class="p-2 rounded-xl border border-gray-200 hover:bg-green-50 hover:border-green-200 text-gray-400 hover:text-green-600 transition" title="Mark as reviewed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?= paginate($totalItems, $perPage, $page, APP_URL . '/review-mistakes.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))) ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
