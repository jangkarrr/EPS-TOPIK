<?php
$pageTitle = 'Reading Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories WHERE module = 'reading' AND status = 'active' ORDER BY sort_order")->fetchAll();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';
    
    if ($formAction === 'save_passage') {
        $title = trim($_POST['title'] ?? '');
        $passageText = $_POST['passage_text'] ?? '';
        $contentType = $_POST['content_type'] ?? 'article';
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $status = $_POST['status'] ?? 'active';

        if ($editId) {
            $db->prepare("UPDATE reading_passages SET title=?, passage_text=?, content_type=?, category_id=?, difficulty=?, status=? WHERE id=?")
                ->execute([$title, $passageText, $contentType, $categoryId, $difficulty, $status, $editId]);
            setFlash('success', 'Passage updated.');
        } else {
            $db->prepare("INSERT INTO reading_passages (title, passage_text, content_type, category_id, difficulty, status) VALUES (?,?,?,?,?,?)")
                ->execute([$title, $passageText, $contentType, $categoryId, $difficulty, $status]);
            $editId = $db->lastInsertId();
            setFlash('success', 'Passage created. Now add questions below.');
        }
        redirect(APP_URL . '/admin/reading.php?action=edit&id=' . $editId);
    } elseif ($formAction === 'save_question') {
        $passageId = (int)($_POST['passage_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $questionText = trim($_POST['question_text'] ?? '');
        $choiceA = trim($_POST['choice_a'] ?? '');
        $choiceB = trim($_POST['choice_b'] ?? '');
        $choiceC = trim($_POST['choice_c'] ?? '');
        $choiceD = trim($_POST['choice_d'] ?? '');
        $correctAnswer = $_POST['correct_answer'] ?? 'A';
        $explanation = trim($_POST['explanation'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($questionId) {
            $db->prepare("UPDATE reading_questions SET question_text=?, choice_a=?, choice_b=?, choice_c=?, choice_d=?, correct_answer=?, explanation=?, sort_order=? WHERE id=?")
                ->execute([$questionText, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $sortOrder, $questionId]);
            setFlash('success', 'Question updated.');
        } else {
            $db->prepare("INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$passageId, $questionText, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $sortOrder]);
            setFlash('success', 'Question added.');
        }
        redirect(APP_URL . '/admin/reading.php?action=edit&id=' . $passageId);
    } elseif ($formAction === 'delete_passage') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) {
            $db->prepare("DELETE FROM reading_questions WHERE passage_id = ?")->execute([$deleteId]);
            $db->prepare("DELETE FROM reading_passages WHERE id = ?")->execute([$deleteId]);
            setFlash('success', 'Passage and questions deleted.');
        }
        redirect(APP_URL . '/admin/reading.php');
    } elseif ($formAction === 'delete_question') {
        $qId = (int)($_POST['question_id'] ?? 0);
        $pId = (int)($_POST['passage_id'] ?? 0);
        if ($qId) { $db->prepare("DELETE FROM reading_questions WHERE id = ?")->execute([$qId]); setFlash('success', 'Question deleted.'); }
        redirect(APP_URL . '/admin/reading.php?action=edit&id=' . $pId);
    }
}

$passage = null; $questions = [];
if ($editId && ($action === 'edit' || $action === 'add_question')) {
    $stmt = $db->prepare("SELECT * FROM reading_passages WHERE id = ?");
    $stmt->execute([$editId]);
    $passage = $stmt->fetch();
    if ($passage) {
        $stmt = $db->prepare("SELECT * FROM reading_questions WHERE passage_id = ? ORDER BY sort_order, id");
        $stmt->execute([$editId]);
        $questions = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

if ($action === 'add' || ($action === 'edit' && $passage)):
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?= $passage ? 'Edit Passage' : 'Add Reading Passage' ?></h3>
        <a href="<?= APP_URL ?>/admin/reading.php" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>

    <!-- Passage Form -->
    <form method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5 mb-6">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save_passage">

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title *</label>
                <input type="text" name="title" value="<?= sanitize($passage['title'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Content Type</label>
                <select name="content_type" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <?php foreach (['notice','sign','instruction','dialogue','passage','schedule','workplace'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($passage['content_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                <select name="category_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">None</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($passage['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Difficulty</label>
                <select name="difficulty" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="beginner" <?= ($passage['difficulty'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($passage['difficulty'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($passage['difficulty'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="active" <?= ($passage['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($passage['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Passage Text (HTML supported) *</label>
            <textarea name="passage_text" rows="8" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-mono resize-y"><?= htmlspecialchars($passage['passage_text'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
            <?= $passage ? 'Update Passage' : 'Create Passage' ?>
        </button>
    </form>

    <?php if ($passage): ?>
    <!-- Questions Section -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Questions (<?= count($questions) ?>)</h3>
        
        <?php foreach ($questions as $idx => $q): ?>
        <div class="p-4 rounded-xl border border-gray-100 mb-3">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 mb-1"><?= ($idx + 1) ?>. <?= sanitize($q['question_text']) ?></p>
                    <div class="text-xs text-gray-500 space-x-3">
                        <span class="<?= $q['correct_answer'] === 'A' ? 'text-green-600 font-bold' : '' ?>">A: <?= sanitize($q['choice_a']) ?></span>
                        <span class="<?= $q['correct_answer'] === 'B' ? 'text-green-600 font-bold' : '' ?>">B: <?= sanitize($q['choice_b']) ?></span>
                        <span class="<?= $q['correct_answer'] === 'C' ? 'text-green-600 font-bold' : '' ?>">C: <?= sanitize($q['choice_c']) ?></span>
                        <span class="<?= $q['correct_answer'] === 'D' ? 'text-green-600 font-bold' : '' ?>">D: <?= sanitize($q['choice_d']) ?></span>
                    </div>
                </div>
                <form method="POST" class="inline" onsubmit="return confirm('Delete question?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="delete_question">
                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                    <input type="hidden" name="passage_id" value="<?= $passage['id'] ?>">
                    <button type="submit" class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add Question Form -->
        <details class="mt-4">
            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-700">+ Add New Question</summary>
            <form method="POST" class="mt-3 p-4 rounded-xl bg-gray-50 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="save_question">
                <input type="hidden" name="passage_id" value="<?= $passage['id'] ?>">

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Question Text *</label>
                    <textarea name="question_text" rows="2" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="choice_a" placeholder="Choice A *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_b" placeholder="Choice B *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_c" placeholder="Choice C *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_d" placeholder="Choice D *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <select name="correct_answer" class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                        <option value="A">Correct: A</option><option value="B">Correct: B</option><option value="C">Correct: C</option><option value="D">Correct: D</option>
                    </select>
                    <input type="number" name="sort_order" value="0" placeholder="Sort" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Add Question</button>
                </div>
                <textarea name="explanation" placeholder="Explanation (optional)" rows="1" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"></textarea>
            </form>
        </details>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= getCount('reading_passages') ?> total passages</p>
    <a href="<?= APP_URL ?>/admin/reading.php?action=add" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Passage
    </a>
</div>

<?php
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;
$totalItems = getCount('reading_passages');

$stmt = $db->prepare("SELECT rp.*, c.name as category_name, (SELECT COUNT(*) FROM reading_questions rq WHERE rq.passage_id = rp.id) as q_count FROM reading_passages rp LEFT JOIN categories c ON rp.category_id = c.id ORDER BY rp.id DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET ?");
$stmt->execute([$offset]);
$passages = $stmt->fetchAll();
?>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Title</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Type</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Category</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Level</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Questions</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($passages as $p): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5 font-medium text-gray-900"><?= sanitize($p['title']) ?></td>
                    <td class="px-5 py-3.5 text-xs capitalize text-gray-500"><?= $p['content_type'] ?></td>
                    <td class="px-5 py-3.5 text-xs text-gray-500"><?= sanitize($p['category_name'] ?? '-') ?></td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-gray-100 text-xs capitalize"><?= $p['difficulty'] ?></span></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= $p['q_count'] ?></td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $p['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $p['status'] ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= APP_URL ?>/admin/reading.php?action=edit&id=<?= $p['id'] ?>" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete passage and all questions?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete_passage">
                                <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
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
    <?php if (empty($passages)): ?><div class="p-8 text-center text-gray-400 text-sm">No passages found.</div><?php endif; ?>
</div>

<?= paginate($totalItems, ITEMS_PER_PAGE, $page, APP_URL . '/admin/reading.php?x=1') ?>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
