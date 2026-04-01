<?php
$pageTitle = 'Quiz Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories WHERE module IN ('quiz','lesson') AND status = 'active' ORDER BY sort_order")->fetchAll();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';
    
    if ($formAction === 'save_quiz') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $quizType = $_POST['quiz_type'] ?? 'mixed';
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $timeLimit = (int)($_POST['time_limit_minutes'] ?? 0) ?: null;
        $questionCount = (int)($_POST['question_count'] ?? 10);
        $status = $_POST['status'] ?? 'active';

        if ($editId) {
            $db->prepare("UPDATE quizzes SET title=?, description=?, quiz_type=?, category_id=?, difficulty=?, time_limit_minutes=?, question_count=?, status=? WHERE id=?")
                ->execute([$title, $description, $quizType, $categoryId, $difficulty, $timeLimit, $questionCount, $status, $editId]);
            setFlash('success', 'Quiz updated.');
        } else {
            $db->prepare("INSERT INTO quizzes (title, description, quiz_type, category_id, difficulty, time_limit_minutes, question_count, status) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$title, $description, $quizType, $categoryId, $difficulty, $timeLimit, $questionCount, $status]);
            $editId = $db->lastInsertId();
            setFlash('success', 'Quiz created. Add questions below.');
        }
        redirect(APP_URL . '/admin/quizzes.php?action=edit&id=' . $editId);
    } elseif ($formAction === 'save_question') {
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $questionText = trim($_POST['question_text'] ?? '');
        $questionType = $_POST['question_type'] ?? 'multiple_choice';
        $choiceA = trim($_POST['choice_a'] ?? '');
        $choiceB = trim($_POST['choice_b'] ?? '');
        $choiceC = trim($_POST['choice_c'] ?? '');
        $choiceD = trim($_POST['choice_d'] ?? '');
        $correctAnswer = $_POST['correct_answer'] ?? 'A';
        $explanation = trim($_POST['explanation'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($questionId) {
            $db->prepare("UPDATE quiz_questions SET question_text=?, question_type=?, choice_a=?, choice_b=?, choice_c=?, choice_d=?, correct_answer=?, explanation=?, sort_order=? WHERE id=?")
                ->execute([$questionText, $questionType, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $sortOrder, $questionId]);
            setFlash('success', 'Question updated.');
        } else {
            $db->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$quizId, $questionText, $questionType, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $sortOrder]);
            setFlash('success', 'Question added.');
        }
        redirect(APP_URL . '/admin/quizzes.php?action=edit&id=' . $quizId);
    } elseif ($formAction === 'delete_quiz') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) {
            $db->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?")->execute([$deleteId]);
            $db->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$deleteId]);
            setFlash('success', 'Quiz deleted.');
        }
        redirect(APP_URL . '/admin/quizzes.php');
    } elseif ($formAction === 'delete_question') {
        $qId = (int)($_POST['question_id'] ?? 0);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        if ($qId) { $db->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([$qId]); setFlash('success', 'Question deleted.'); }
        redirect(APP_URL . '/admin/quizzes.php?action=edit&id=' . $quizId);
    }
}

$quiz = null; $questions = [];
if ($editId && $action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$editId]);
    $quiz = $stmt->fetch();
    if ($quiz) {
        $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order, id");
        $stmt->execute([$editId]);
        $questions = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

if ($action === 'add' || ($action === 'edit' && $quiz)):
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?= $quiz ? 'Edit Quiz' : 'Create Quiz' ?></h3>
        <a href="<?= APP_URL ?>/admin/quizzes.php" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>

    <!-- Quiz Form -->
    <form method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5 mb-6">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save_quiz">

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title *</label>
                <input type="text" name="title" value="<?= sanitize($quiz['title'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea name="description" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none"><?= sanitize($quiz['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Quiz Type</label>
                <select name="quiz_type" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <?php foreach (['mixed','listening','reading','vocabulary'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($quiz['quiz_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                <select name="category_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">None</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($quiz['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Difficulty</label>
                <select name="difficulty" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="beginner" <?= ($quiz['difficulty'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($quiz['difficulty'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($quiz['difficulty'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Time Limit (min, 0=none)</label>
                <input type="number" name="time_limit_minutes" value="<?= $quiz['time_limit_minutes'] ?? 0 ?>" min="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Question Count</label>
                <input type="number" name="question_count" value="<?= $quiz['question_count'] ?? 10 ?>" min="1" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="active" <?= ($quiz['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($quiz['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
            <?= $quiz ? 'Update Quiz' : 'Create Quiz' ?>
        </button>
    </form>

    <?php if ($quiz): ?>
    <!-- Questions -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Questions (<?= count($questions) ?>)</h3>

        <?php foreach ($questions as $idx => $q): ?>
        <div class="p-4 rounded-xl border border-gray-100 mb-3">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 mb-1"><span class="text-indigo-600"><?= $idx + 1 ?>.</span> <?= sanitize($q['question_text']) ?></p>
                    <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                        <?php foreach (['A','B','C','D'] as $opt):
                            $f = 'choice_' . strtolower($opt);
                            if (!$q[$f]) continue;
                        ?>
                        <span class="<?= $q['correct_answer'] === $opt ? 'text-green-600 font-bold' : '' ?>"><?= $opt ?>: <?= sanitize($q[$f]) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="delete_question">
                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                    <button type="submit" class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <details class="mt-4">
            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-700">+ Add New Question</summary>
            <form method="POST" class="mt-3 p-4 rounded-xl bg-gray-50 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="save_question">
                <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Question *</label>
                    <textarea name="question_text" rows="2" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                    <select name="question_type" class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True/False</option>
                        <option value="fill_blank">Fill in Blank</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="choice_a" placeholder="Choice A *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_b" placeholder="Choice B *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_c" placeholder="Choice C" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_d" placeholder="Choice D" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <select name="correct_answer" class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                        <option value="A">Correct: A</option><option value="B">Correct: B</option><option value="C">Correct: C</option><option value="D">Correct: D</option>
                    </select>
                    <input type="number" name="sort_order" value="0" placeholder="Sort" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Add</button>
                </div>
                <textarea name="explanation" placeholder="Explanation (optional)" rows="1" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"></textarea>
            </form>
        </details>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= getCount('quizzes') ?> total quizzes</p>
    <a href="<?= APP_URL ?>/admin/quizzes.php?action=add" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Quiz
    </a>
</div>

<?php
$stmt = $db->query("SELECT q.*, c.name as category_name, (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as q_count, (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) as attempts FROM quizzes q LEFT JOIN categories c ON q.category_id = c.id ORDER BY q.id DESC");
$quizzes = $stmt->fetchAll();
?>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Title</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Type</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Level</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Questions</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Attempts</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($quizzes as $q): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5">
                        <p class="text-sm font-medium text-gray-900"><?= sanitize($q['title']) ?></p>
                        <p class="text-xs text-gray-400"><?= sanitize($q['category_name'] ?? '') ?></p>
                    </td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 text-xs capitalize"><?= $q['quiz_type'] ?></span></td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-gray-100 text-xs capitalize"><?= $q['difficulty'] ?></span></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= $q['q_count'] ?></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= $q['attempts'] ?></td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $q['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $q['status'] ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= APP_URL ?>/admin/quizzes.php?action=edit&id=<?= $q['id'] ?>" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete quiz and all questions?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete_quiz">
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
    <?php if (empty($quizzes)): ?><div class="p-8 text-center text-gray-400 text-sm">No quizzes found.</div><?php endif; ?>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
