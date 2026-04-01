<?php
$pageTitle = 'Lesson Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories WHERE module = 'lesson' AND status = 'active' ORDER BY sort_order")->fetchAll();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';
    
    if ($formAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $estimatedMinutes = max(1, (int)($_POST['estimated_minutes'] ?? 15));
        $content = $_POST['content'] ?? '';
        $summary = trim($_POST['summary'] ?? '');
        $tips = trim($_POST['tips'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'published';

        $audioPath = null;
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $audioPath = 'audio/' . uploadFile($_FILES['audio_file'], AUDIO_DIR, ['mp3', 'wav', 'ogg']);
        }

        if ($editId) {
            $sql = "UPDATE lessons SET title=?, slug=?, category_id=?, difficulty=?, estimated_minutes=?, content=?, summary=?, tips=?, sort_order=?, status=?";
            $params = [$title, $slug, $categoryId, $difficulty, $estimatedMinutes, $content, $summary, $tips, $sortOrder, $status];
            if ($audioPath) { $sql .= ", audio_path=?"; $params[] = $audioPath; }
            $sql .= " WHERE id=?";
            $params[] = $editId;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            setFlash('success', 'Lesson updated.');
        } else {
            $stmt = $db->prepare("INSERT INTO lessons (title, slug, category_id, difficulty, estimated_minutes, content, summary, tips, audio_path, sort_order, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $slug, $categoryId, $difficulty, $estimatedMinutes, $content, $summary, $tips, $audioPath, $sortOrder, $status]);
            setFlash('success', 'Lesson created.');
        }
        redirect(APP_URL . '/admin/lessons.php');
    } elseif ($formAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) {
            $stmt = $db->prepare("DELETE FROM lessons WHERE id = ?");
            $stmt->execute([$deleteId]);
            setFlash('success', 'Lesson deleted.');
        }
        redirect(APP_URL . '/admin/lessons.php');
    }
}

// Load lesson for editing
$lesson = null;
if ($editId && ($action === 'edit' || $action === 'view')) {
    $stmt = $db->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$editId]);
    $lesson = $stmt->fetch();
    if (!$lesson) { redirect(APP_URL . '/admin/lessons.php', 'error', 'Lesson not found.'); }
}

require_once __DIR__ . '/../includes/admin-header.php';

if ($action === 'add' || ($action === 'edit' && $lesson)):
?>

<!-- Add/Edit Form -->
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?= $lesson ? 'Edit Lesson' : 'Add New Lesson' ?></h3>
        <a href="<?= APP_URL ?>/admin/lessons.php" class="text-sm text-gray-500 hover:text-gray-700">← Back to list</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title *</label>
                <input type="text" name="title" value="<?= sanitize($lesson['title'] ?? '') ?>" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Slug</label>
                <input type="text" name="slug" value="<?= sanitize($lesson['slug'] ?? '') ?>" placeholder="Auto-generated if empty"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                <select name="category_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">No Category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($lesson['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $c['icon'] ?> <?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Difficulty</label>
                <select name="difficulty" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="beginner" <?= ($lesson['difficulty'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($lesson['difficulty'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($lesson['difficulty'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Estimated Time (min)</label>
                <input type="number" name="estimated_minutes" value="<?= $lesson['estimated_minutes'] ?? 15 ?>" min="1"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Sort Order</label>
                <input type="number" name="sort_order" value="<?= $lesson['sort_order'] ?? 0 ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="published" <?= ($lesson['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= ($lesson['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Summary</label>
            <textarea name="summary" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none"><?= sanitize($lesson['summary'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lesson Content (HTML supported) *</label>
            <textarea name="content" rows="12" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-mono resize-y"><?= htmlspecialchars($lesson['content'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Tips</label>
            <textarea name="tips" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none"><?= sanitize($lesson['tips'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Audio File (optional)</label>
            <input type="file" name="audio_file" accept="audio/*" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
            <?php if (!empty($lesson['audio_path'])): ?>
                <p class="text-xs text-gray-400 mt-1">Current: <?= sanitize($lesson['audio_path']) ?></p>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                <?= $lesson ? 'Update Lesson' : 'Create Lesson' ?>
            </button>
            <a href="<?= APP_URL ?>/admin/lessons.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<!-- Lesson List -->
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= getCount('lessons') ?> total lessons</p>
    <a href="<?= APP_URL ?>/admin/lessons.php?action=add" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Lesson
    </a>
</div>

<?php
$search = $_GET['search'] ?? '';
$where = "1=1"; $params = [];
if ($search) { $where .= " AND (l.title LIKE ? OR l.summary LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM lessons l WHERE $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT l.*, c.name as category_name, c.icon as category_icon FROM lessons l LEFT JOIN categories c ON l.category_id = c.id WHERE $where ORDER BY l.sort_order, l.id DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset");
$stmt->execute($params);
$lessons = $stmt->fetchAll();
?>

<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search lessons..." class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Search</button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Title</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Category</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Difficulty</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Time</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($lessons as $l): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5">
                        <p class="text-sm font-medium text-gray-900"><?= sanitize($l['title']) ?></p>
                        <?php if ($l['summary']): ?><p class="text-xs text-gray-400 truncate max-w-xs"><?= sanitize($l['summary']) ?></p><?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5"><span class="text-xs"><?= $l['category_icon'] ?? '' ?> <?= sanitize($l['category_name'] ?? 'None') ?></span></td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-gray-100 text-xs text-gray-600 capitalize"><?= $l['difficulty'] ?></span></td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs"><?= $l['estimated_minutes'] ?>m</td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $l['status'] === 'published' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $l['status'] ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= APP_URL ?>/admin/lessons.php?action=edit&id=<?= $l['id'] ?>" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this lesson?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="delete_id" value="<?= $l['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($lessons)): ?>
    <div class="p-8 text-center text-gray-400 text-sm">No lessons found.</div>
    <?php endif; ?>
</div>

<?= paginate($totalItems, ITEMS_PER_PAGE, $page, APP_URL . '/admin/lessons.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))) ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
