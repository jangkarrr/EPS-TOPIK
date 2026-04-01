<?php
$pageTitle = 'Lessons';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

// Filters
$categoryFilter = $_GET['category'] ?? '';
$difficultyFilter = $_GET['difficulty'] ?? '';
$search = $_GET['search'] ?? '';

// Fetch categories for lessons
$categories = $db->query("SELECT * FROM categories WHERE module = 'lesson' AND status = 'active' ORDER BY sort_order")->fetchAll();

// Build query
$where = "l.status = 'published'";
$params = [];
if ($categoryFilter) { $where .= " AND l.category_id = ?"; $params[] = $categoryFilter; }
if ($difficultyFilter) { $where .= " AND l.difficulty = ?"; $params[] = $difficultyFilter; }
if ($search) { $where .= " AND (l.title LIKE ? OR l.summary LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT l.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
    (SELECT COUNT(*) FROM lesson_completions lc WHERE lc.lesson_id = l.id AND lc.user_id = ?) as is_completed
    FROM lessons l LEFT JOIN categories c ON l.category_id = c.id WHERE $where ORDER BY l.sort_order, l.id");
$stmt->execute(array_merge([$userId], $params));
$lessons = $stmt->fetchAll();

// Count completed
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM lesson_completions WHERE user_id = ?");
$stmt->execute([$userId]);
$completedCount = (int)$stmt->fetch()['cnt'];
$totalCount = count($lessons);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <p class="text-sm text-gray-500">Complete lessons to build your Korean foundation</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full"><?= $completedCount ?>/<?= $totalCount ?> completed</span>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search lessons..." 
                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
        </div>
        <select name="category" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm bg-white">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="difficulty" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm bg-white">
            <option value="">All Levels</option>
            <option value="beginner" <?= $difficultyFilter === 'beginner' ? 'selected' : '' ?>>Beginner</option>
            <option value="intermediate" <?= $difficultyFilter === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
            <option value="advanced" <?= $difficultyFilter === 'advanced' ? 'selected' : '' ?>>Advanced</option>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition">Filter</button>
        <?php if ($search || $categoryFilter || $difficultyFilter): ?>
            <a href="<?= APP_URL ?>/lessons.php" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition text-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Category Quick Nav -->
<div class="flex gap-2 overflow-x-auto pb-2 mb-6 scrollbar-hide">
    <?php foreach ($categories as $cat): ?>
    <a href="<?= APP_URL ?>/lessons.php?category=<?= $cat['id'] ?>" 
       class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition
       <?= $categoryFilter == $cat['id'] ? 'bg-blue-50 border-blue-200 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
        <span><?= $cat['icon'] ?></span>
        <?= sanitize($cat['name']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Lessons Grid -->
<?php if (empty($lessons)): ?>
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
    </div>
    <p class="text-gray-400 text-sm">No lessons found. Try adjusting your filters.</p>
</div>
<?php else: ?>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($lessons as $lesson): ?>
    <a href="<?= APP_URL ?>/lesson-view.php?id=<?= $lesson['id'] ?>" 
       class="group bg-white rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50 transition-all overflow-hidden">
        <div class="p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-lg" 
                      style="background: <?= $lesson['category_color'] ?>15; color: <?= $lesson['category_color'] ?>">
                    <span><?= $lesson['category_icon'] ?? '📖' ?></span>
                    <?= sanitize($lesson['category_name'] ?? 'General') ?>
                </span>
                <?php if ($lesson['is_completed']): ?>
                    <span class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                <?php endif; ?>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 transition mb-2"><?= sanitize($lesson['title']) ?></h3>
            <?php if ($lesson['summary']): ?>
            <p class="text-xs text-gray-400 line-clamp-2 mb-3"><?= sanitize($lesson['summary']) ?></p>
            <?php endif; ?>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= $lesson['estimated_minutes'] ?>m
                </span>
                <span class="px-2 py-0.5 rounded-md bg-gray-50 capitalize"><?= $lesson['difficulty'] ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
