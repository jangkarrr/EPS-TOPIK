<?php
$pageTitle = 'Mock Exam Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save_exam') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $timeLimitMinutes = max(1, (int)($_POST['time_limit_minutes'] ?? 70));
        $totalScore = max(1, (int)($_POST['total_score'] ?? 200));
        $passingScore = max(1, (int)($_POST['passing_score'] ?? 80));
        $listeningCount = max(0, (int)($_POST['listening_count'] ?? 25));
        $readingCount = max(0, (int)($_POST['reading_count'] ?? 25));
        $status = $_POST['status'] ?? 'active';

        if ($editId) {
            $db->prepare("UPDATE mock_exams SET title=?, description=?, time_limit_minutes=?, total_score=?, passing_score=?, listening_count=?, reading_count=?, status=? WHERE id=?")
                ->execute([$title, $description, $timeLimitMinutes, $totalScore, $passingScore, $listeningCount, $readingCount, $status, $editId]);
            setFlash('success', 'Exam updated.');
        } else {
            $db->prepare("INSERT INTO mock_exams (title, description, time_limit_minutes, total_score, passing_score, listening_count, reading_count, status) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$title, $description, $timeLimitMinutes, $totalScore, $passingScore, $listeningCount, $readingCount, $status]);
            $editId = $db->lastInsertId();
            setFlash('success', 'Exam created. Add questions below.');
        }
        redirect(APP_URL . '/admin/mock-exams.php?action=edit&id=' . $editId);
    } elseif ($formAction === 'save_question') {
        $examId = (int)($_POST['exam_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $section = $_POST['section'] ?? 'listening';
        $questionNumber = (int)($_POST['question_number'] ?? 1);
        $questionText = trim($_POST['question_text'] ?? '');
        $passageText = trim($_POST['passage_text'] ?? '');
        $choiceA = trim($_POST['choice_a'] ?? '');
        $choiceB = trim($_POST['choice_b'] ?? '');
        $choiceC = trim($_POST['choice_c'] ?? '');
        $choiceD = trim($_POST['choice_d'] ?? '');
        $correctAnswer = $_POST['correct_answer'] ?? 'A';
        $explanation = trim($_POST['explanation'] ?? '');
        $points = max(1, (int)($_POST['points'] ?? 4));

        $audioPath = null;
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $audioPath = 'audio/exam/' . uploadFile($_FILES['audio_file'], AUDIO_DIR . 'exam/', ['mp3', 'wav', 'ogg']);
        }

        if ($questionId) {
            $sql = "UPDATE mock_exam_questions SET section=?, question_number=?, question_text=?, passage_text=?, choice_a=?, choice_b=?, choice_c=?, choice_d=?, correct_answer=?, explanation=?, points=?";
            $params = [$section, $questionNumber, $questionText, $passageText, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $points];
            if ($audioPath) { $sql .= ", audio_path=?"; $params[] = $audioPath; }
            $sql .= " WHERE id=?"; $params[] = $questionId;
            $db->prepare($sql)->execute($params);
            setFlash('success', 'Question updated.');
        } else {
            $db->prepare("INSERT INTO mock_exam_questions (exam_id, section, question_number, question_text, passage_text, audio_path, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, points) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$examId, $section, $questionNumber, $questionText, $passageText, $audioPath, $choiceA, $choiceB, $choiceC, $choiceD, $correctAnswer, $explanation, $points]);
            setFlash('success', 'Question added.');
        }
        redirect(APP_URL . '/admin/mock-exams.php?action=edit&id=' . $examId);
    } elseif ($formAction === 'delete_exam') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) {
            $db->prepare("DELETE FROM mock_exam_questions WHERE exam_id = ?")->execute([$deleteId]);
            $db->prepare("DELETE FROM mock_exams WHERE id = ?")->execute([$deleteId]);
            setFlash('success', 'Exam deleted.');
        }
        redirect(APP_URL . '/admin/mock-exams.php');
    } elseif ($formAction === 'delete_question') {
        $qId = (int)($_POST['question_id'] ?? 0);
        $examId = (int)($_POST['exam_id'] ?? 0);
        if ($qId) { $db->prepare("DELETE FROM mock_exam_questions WHERE id = ?")->execute([$qId]); setFlash('success', 'Deleted.'); }
        redirect(APP_URL . '/admin/mock-exams.php?action=edit&id=' . $examId);
    }
}

$exam = null; $examQuestions = [];
if ($editId && $action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM mock_exams WHERE id = ?");
    $stmt->execute([$editId]);
    $exam = $stmt->fetch();
    if ($exam) {
        $stmt = $db->prepare("SELECT * FROM mock_exam_questions WHERE exam_id = ? ORDER BY section, question_number");
        $stmt->execute([$editId]);
        $examQuestions = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

if ($action === 'add' || ($action === 'edit' && $exam)):
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?= $exam ? 'Edit Mock Exam' : 'Create Mock Exam' ?></h3>
        <a href="<?= APP_URL ?>/admin/mock-exams.php" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>

    <!-- Exam Form -->
    <form method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5 mb-6">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save_exam">

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title *</label>
                <input type="text" name="title" value="<?= sanitize($exam['title'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea name="description" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none"><?= sanitize($exam['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Time Limit (min)</label>
                <input type="number" name="time_limit_minutes" value="<?= $exam['time_limit_minutes'] ?? 70 ?>" min="1" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Total Score</label>
                <input type="number" name="total_score" value="<?= $exam['total_score'] ?? 200 ?>" min="1" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Passing Score</label>
                <input type="number" name="passing_score" value="<?= $exam['passing_score'] ?? 80 ?>" min="1" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Listening Questions</label>
                <input type="number" name="listening_count" value="<?= $exam['listening_count'] ?? 25 ?>" min="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Reading Questions</label>
                <input type="number" name="reading_count" value="<?= $exam['reading_count'] ?? 25 ?>" min="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="active" <?= ($exam['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($exam['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
            <?= $exam ? 'Update Exam' : 'Create Exam' ?>
        </button>
    </form>

    <?php if ($exam): ?>
    <!-- Questions -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <?php
        $listeningQs = array_filter($examQuestions, fn($q) => $q['section'] === 'listening');
        $readingQs = array_filter($examQuestions, fn($q) => $q['section'] === 'reading');
        ?>
        <h3 class="text-base font-semibold text-gray-900 mb-1">Questions (<?= count($examQuestions) ?>)</h3>
        <p class="text-xs text-gray-400 mb-4">Listening: <?= count($listeningQs) ?> | Reading: <?= count($readingQs) ?></p>

        <?php foreach ($examQuestions as $q): ?>
        <div class="p-3 rounded-xl border border-gray-100 mb-2 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="w-6 h-6 rounded flex-shrink-0 flex items-center justify-center text-[10px] font-bold <?= $q['section'] === 'listening' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' ?>"><?= $q['question_number'] ?></span>
                <span class="text-[10px] uppercase font-medium <?= $q['section'] === 'listening' ? 'text-blue-500' : 'text-emerald-500' ?>"><?= $q['section'] ?></span>
                <p class="text-sm text-gray-700 truncate"><?= sanitize($q['question_text']) ?></p>
                <span class="text-xs text-green-600 font-bold flex-shrink-0"><?= $q['correct_answer'] ?></span>
            </div>
            <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="delete_question">
                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                <button type="submit" class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-500 transition">✕</button>
            </form>
        </div>
        <?php endforeach; ?>

        <details class="mt-4">
            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-700">+ Add Question</summary>
            <form method="POST" enctype="multipart/form-data" class="mt-3 p-4 rounded-xl bg-gray-50 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="save_question">
                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">

                <div class="grid grid-cols-3 gap-3">
                    <select name="section" class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                        <option value="listening">Listening</option>
                        <option value="reading">Reading</option>
                    </select>
                    <input type="number" name="question_number" placeholder="Question #" min="1" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="number" name="points" value="4" min="1" placeholder="Points" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                </div>
                <textarea name="question_text" rows="2" required placeholder="Question text *" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"></textarea>
                <textarea name="passage_text" rows="2" placeholder="Passage text (for reading questions)" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"></textarea>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Audio (for listening)</label>
                    <input type="file" name="audio_file" accept="audio/*" class="w-full text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="choice_a" placeholder="Choice A *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_b" placeholder="Choice B *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_c" placeholder="Choice C *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <input type="text" name="choice_d" placeholder="Choice D *" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <select name="correct_answer" class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                        <option value="A">Correct: A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                    </select>
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
    <p class="text-sm text-gray-500"><?= getCount('mock_exams') ?> total exams</p>
    <a href="<?= APP_URL ?>/admin/mock-exams.php?action=add" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Exam
    </a>
</div>

<?php
$exams = $db->query("SELECT me.*, (SELECT COUNT(*) FROM mock_exam_questions meq WHERE meq.exam_id = me.id) as q_count, (SELECT COUNT(*) FROM mock_exam_attempts mea WHERE mea.exam_id = me.id AND mea.status='completed') as attempts FROM mock_exams me ORDER BY me.id DESC")->fetchAll();
?>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Title</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Time</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Score</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Questions</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Attempts</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($exams as $e): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5">
                        <p class="text-sm font-medium text-gray-900"><?= sanitize($e['title']) ?></p>
                        <p class="text-xs text-gray-400">L:<?= $e['listening_count'] ?> R:<?= $e['reading_count'] ?></p>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs"><?= $e['time_limit_minutes'] ?>m</td>
                    <td class="px-5 py-3.5 text-xs text-gray-500">Pass: <?= $e['passing_score'] ?>/<?= $e['total_score'] ?></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= $e['q_count'] ?></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= $e['attempts'] ?></td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $e['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $e['status'] ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= APP_URL ?>/admin/mock-exams.php?action=edit&id=<?= $e['id'] ?>" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete exam and all questions?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete_exam">
                                <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
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
    <?php if (empty($exams)): ?><div class="p-8 text-center text-gray-400 text-sm">No exams found.</div><?php endif; ?>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
