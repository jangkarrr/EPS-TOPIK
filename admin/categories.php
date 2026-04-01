<?php
$pageTitle = 'Category Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $module = $_POST['module'] ?? 'lesson';
        $icon = trim($_POST['icon'] ?? '📁');
        $color = trim($_POST['color'] ?? '#3B82F6');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($catId) {
            $db->prepare("UPDATE categories SET name=?, slug=?, module=?, icon=?, color=?, description=?, sort_order=?, status=? WHERE id=?")
                ->execute([$name, $slug, $module, $icon, $color, $description, $sortOrder, $status, $catId]);
            setFlash('success', 'Category updated.');
        } else {
            $db->prepare("INSERT INTO categories (name, slug, module, icon, color, description, sort_order, status) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$name, $slug, $module, $icon, $color, $description, $sortOrder, $status]);
            setFlash('success', 'Category created.');
        }
        redirect(APP_URL . '/admin/categories.php');
    } elseif ($formAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$deleteId]);
            setFlash('success', 'Category deleted.');
        }
        redirect(APP_URL . '/admin/categories.php');
    }
}

$moduleFilter = $_GET['module'] ?? '';
$where = "1=1"; $params = [];
if ($moduleFilter) { $where .= " AND module = ?"; $params[] = $moduleFilter; }

$stmt = $db->prepare("SELECT * FROM categories WHERE $where ORDER BY module, sort_order, name");
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Count items per category
$catCounts = [];
foreach ($categories as $c) {
    $table = match($c['module']) {
        'lesson' => 'lessons',
        'vocabulary' => 'vocabulary',
        'listening' => 'listening_questions',
        'reading' => 'reading_passages',
        'quiz' => 'quizzes',
        default => null
    };
    if ($table) {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM $table WHERE category_id = ?");
        $stmt->execute([$c['id']]);
        $catCounts[$c['id']] = (int)$stmt->fetch()['cnt'];
    } else {
        $catCounts[$c['id']] = 0;
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Filter -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-2">
        <a href="<?= APP_URL ?>/admin/categories.php" class="px-3 py-1.5 rounded-lg text-xs font-medium <?= !$moduleFilter ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:bg-gray-100' ?> transition">All</a>
        <?php foreach (['lesson','vocabulary','listening','reading','quiz'] as $mod): ?>
        <a href="<?= APP_URL ?>/admin/categories.php?module=<?= $mod ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium capitalize <?= $moduleFilter === $mod ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:bg-gray-100' ?> transition"><?= $mod ?></a>
        <?php endforeach; ?>
    </div>
    <button onclick="openModal('addCategoryModal')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Category
    </button>
</div>

<!-- Categories Grid -->
<?php if (empty($categories)): ?>
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
    <p class="text-gray-400 text-sm">No categories found.</p>
</div>
<?php else: ?>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($categories as $c): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-all">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background: <?= $c['color'] ?>15">
                    <?= $c['icon'] ?>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900"><?= sanitize($c['name']) ?></h3>
                    <p class="text-xs text-gray-400 capitalize"><?= $c['module'] ?></p>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $c['status'] === 'active' ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' ?>"><?= $c['status'] ?></span>
        </div>
        <?php if ($c['description']): ?><p class="text-xs text-gray-400 mb-3"><?= sanitize($c['description']) ?></p><?php endif; ?>
        <div class="flex items-center justify-between pt-3 border-t border-gray-50">
            <span class="text-xs text-gray-400"><?= $catCounts[$c['id']] ?? 0 ?> items · Sort: <?= $c['sort_order'] ?></span>
            <div class="flex items-center gap-1">
                <button onclick="editCategory(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                    <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add/Edit Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add Category</h3>
            <button onclick="closeModal('addCategoryModal')" class="p-1 rounded-lg hover:bg-gray-100 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="category_id" id="catId" value="0">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" id="catName" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" id="catSlug" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Module *</label>
                    <select name="module" id="catModule" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                        <option value="lesson">Lesson</option>
                        <option value="vocabulary">Vocabulary</option>
                        <option value="listening">Listening</option>
                        <option value="reading">Reading</option>
                        <option value="quiz">Quiz</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (emoji)</label>
                    <input type="text" name="icon" id="catIcon" value="📁" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="color" name="color" id="catColor" value="#3B82F6" class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="catSort" value="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="catStatus" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="catDesc" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm resize-none"></textarea>
                </div>
            </div>
            <div class="flex items-center gap-3 pt-3">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">Save</button>
                <button type="button" onclick="closeModal('addCategoryModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catSlug').value = cat.slug;
    document.getElementById('catModule').value = cat.module;
    document.getElementById('catIcon').value = cat.icon;
    document.getElementById('catColor').value = cat.color;
    document.getElementById('catSort').value = cat.sort_order;
    document.getElementById('catStatus').value = cat.status;
    document.getElementById('catDesc').value = cat.description || '';
    openModal('addCategoryModal');
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
