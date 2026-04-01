<?php
$pageTitle = 'Listening Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories WHERE module = 'listening' AND status = 'active' ORDER BY sort_order")->fetchAll();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';
    
    if ($formAction === 'save') {
        $questionText = trim($_POST['question_text'] ?? '');
        $choiceA = trim($_POST['choice_a'] ?? '');
        $choiceB = trim($_POST['choice_b'] ?? '');
        $choiceC = trim($_POST['choice_c'] ?? '');
        $choiceD = trim($_POST['choice_d'] ?? '');
        $correctAnswer = $_POST['correct_answer'] ?? 'A';
        $explanation = trim($_POST['explanation'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $status = $_POST['status'] ?? 'active';

        $audioPath = '';
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $audioPath = 'audio/listening/' . uploadFile($_FILES['audio_file'], AUDIO_DIR . 'listening/', ['mp3', 'wav', 'ogg']);
        }

        if ($editId) {
            $sql = "UPDATE listening_questions SET question_text=?, choice_a=?, choice_b=?, choice_c=?, choice_d=?, correct_answer=?, explanation=?, category_id=?, difficulty=?, status=?";
            $params = [$questionText, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $categoryId, $difficulty, $status];
            if ($audioPath) { $sql .= ", audio_path=?"; $params[] = $audioPath; }
            $sql .= " WHERE id=?"; $params[] = $editId;
            $db->prepare($sql)->execute($params);
            setFlash('success', 'Listening question updated.');
        } else {
            if (!$audioPath) $audioPath = 'audio/listening/placeholder.mp3';
            $db->prepare("INSERT INTO listening_questions (audio_path, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, category_id, difficulty, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$audioPath, $questionText, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $categoryId, $difficulty, $status]);
            setFlash('success', 'Listening question added.');
        }
        redirect(APP_URL . '/admin/listening.php');
    } elseif ($formAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) { $db->prepare("DELETE FROM listening_questions WHERE id = ?")->execute([$deleteId]); setFlash('success', 'Deleted.'); }
        redirect(APP_URL . '/admin/listening.php');
    }
}

$item = null;
if ($editId && $action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM listening_questions WHERE id = ?");
    $stmt->execute([$editId]);
    $item = $stmt->fetch();
    if (!$item) redirect(APP_URL . '/admin/listening.php', 'error', 'Not found.');
}

require_once __DIR__ . '/../includes/admin-header.php';

if ($action === 'add' || ($action === 'edit' && $item)):
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?= $item ? 'Edit' : 'Add' ?> Listening Question</h3>
        <a href="<?= APP_URL ?>/admin/listening.php" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Audio File *</label>
            <input type="file" name="audio_file" accept="audio/*" <?= $item ? '' : '' ?> class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
            <?php if (!empty($item['audio_path'])): ?><p class="text-xs text-gray-400 mt-1">Current: <?= sanitize($item['audio_path']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Question Text *</label>
            <textarea name="question_text" rows="2" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none"><?= sanitize($item['question_text'] ?? '') ?></textarea>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Choice A *</label>
                <input type="text" name="choice_a" value="<?= sanitize($item['choice_a'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Choice B *</label>
                <input type="text" name="choice_b" value="<?= sanitize($item['choice_b'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Choice C *</label>
                <input type="text" name="choice_c" value="<?= sanitize($item['choice_c'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Choice D *</label>
                <input type="text" name="choice_d" value="<?= sanitize($item['choice_d'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Correct Answer *</label>
                <select name="correct_answer" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <?php foreach (['A','B','C','D'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($item['correct_answer'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                <select name="category_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">None</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($item['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Difficulty</label>
                <select name="difficulty" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="beginner" <?= ($item['difficulty'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($item['difficulty'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($item['difficulty'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Explanation</label>
            <textarea name="explanation" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none"><?= sanitize($item['explanation'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
            <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white max-w-xs">
                <option value="active" <?= ($item['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($item['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm"><?= $item ? 'Update' : 'Create' ?></button>
            <a href="<?= APP_URL ?>/admin/listening.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= getCount('listening_questions') ?> total questions</p>
    <a href="<?= APP_URL ?>/admin/listening.php?action=add" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Question
    </a>
</div>

<?php
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;
$totalItems = getCount('listening_questions');

$stmt = $db->prepare("SELECT lq.*, c.name as category_name FROM listening_questions lq LEFT JOIN categories c ON lq.category_id = c.id ORDER BY lq.id DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET ?");
$stmt->execute([$offset]);
$items = $stmt->fetchAll();
?>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">#</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Question</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Answer</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Category</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Level</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($items as $q): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5 text-gray-400"><?= $q['id'] ?></td>
                    <td class="px-5 py-3.5"><p class="text-sm text-gray-900 truncate max-w-xs"><?= sanitize($q['question_text']) ?></p></td>
                    <td class="px-5 py-3.5"><span class="w-6 h-6 rounded bg-green-100 text-green-700 inline-flex items-center justify-center text-xs font-bold"><?= $q['correct_answer'] ?></span></td>
                    <td class="px-5 py-3.5 text-xs text-gray-500"><?= sanitize($q['category_name'] ?? '-') ?></td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-gray-100 text-xs capitalize"><?= $q['difficulty'] ?></span></td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= APP_URL ?>/admin/listening.php?action=edit&id=<?= $q['id'] ?>" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="delete_id" value="<?= $q['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition">
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
    <?php if (empty($items)): ?><div class="p-8 text-center text-gray-400 text-sm">No questions found.</div><?php endif; ?>
</div>

<?= paginate($totalItems, ITEMS_PER_PAGE, $page, APP_URL . '/admin/listening.php?x=1') ?>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
