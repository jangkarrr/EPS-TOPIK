<?php
$pageTitle = 'Reading Practice';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

$categoryFilter = $_GET['category'] ?? '';
$difficultyFilter = $_GET['difficulty'] ?? '';
$passageId = (int)($_GET['passage'] ?? 0);

$categories = $db->query("SELECT * FROM categories WHERE module = 'reading' AND status = 'active' ORDER BY sort_order")->fetchAll();

if ($passageId) {
    // Single passage view with questions
    $stmt = $db->prepare("SELECT rp.*, c.name as category_name, c.icon as category_icon FROM reading_passages rp LEFT JOIN categories c ON rp.category_id = c.id WHERE rp.id = ? AND rp.status = 'active'");
    $stmt->execute([$passageId]);
    $passage = $stmt->fetch();

    if (!$passage) { redirect(APP_URL . '/reading.php', 'error', 'Passage not found.'); }
    $pageTitle = $passage['title'];

    $stmt = $db->prepare("SELECT * FROM reading_questions WHERE passage_id = ? ORDER BY sort_order");
    $stmt->execute([$passageId]);
    $questions = $stmt->fetchAll();

    $sessionKey = 'reading_' . $passageId;
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['answers' => []];
    }
    $session = &$_SESSION[$sessionKey];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answers'])) {
        if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            foreach ($questions as $idx => $q) {
                $answer = strtoupper($_POST['answer_' . $q['id']] ?? '');
                if ($answer && !isset($session['answers'][$q['id']])) {
                    $isCorrect = $answer === $q['correct_answer'];
                    $session['answers'][$q['id']] = [
                        'answer' => $answer,
                        'correct' => $q['correct_answer'],
                        'is_correct' => $isCorrect
                    ];
                    if (!$isCorrect) {
                        logMistake($userId, 'reading', $q['id'], $q['question_text'], $answer, $q['correct_answer'], $q['explanation']);
                    }
                }
            }
            $today = date('Y-m-d');
            $stmt2 = $db->prepare("INSERT INTO daily_goals (user_id, goal_date, completed_reading) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE completed_reading = completed_reading + 1");
            $stmt2->execute([$userId, $today]);
            logActivity($userId, 'reading', 'Completed reading: ' . $passage['title'], $passageId);
        }
        redirect(APP_URL . '/reading.php?passage=' . $passageId . '&submitted=1');
    }

    if (isset($_GET['reset'])) {
        unset($_SESSION[$sessionKey]);
        redirect(APP_URL . '/reading.php?passage=' . $passageId);
    }

    $submitted = !empty($session['answers']);

    require_once __DIR__ . '/includes/header.php';
    ?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="<?= APP_URL ?>/reading.php" class="hover:text-blue-600 transition">Reading</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 truncate"><?= sanitize($passage['title']) ?></span>
    </nav>

    <div class="max-w-4xl mx-auto">
        <!-- Passage -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700"><?= sanitize($passage['category_name'] ?? 'General') ?></span>
                <span class="px-2.5 py-1 text-xs rounded-lg bg-gray-50 text-gray-500 capitalize"><?= $passage['difficulty'] ?></span>
                <span class="px-2.5 py-1 text-xs rounded-lg bg-gray-50 text-gray-500 capitalize"><?= $passage['content_type'] ?></span>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-4"><?= sanitize($passage['title']) ?></h2>
            <div class="prose prose-sm max-w-none korean-text leading-relaxed text-gray-700 bg-gray-50 rounded-xl p-6 border border-gray-100">
                <?= $passage['passage_text'] ?>
            </div>
        </div>

        <!-- Questions -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-6">Questions (<?= count($questions) ?>)</h3>

            <?php if ($submitted): ?>
                <?php
                $correctCount = count(array_filter($session['answers'], fn($a) => $a['is_correct']));
                $totalQ = count($questions);
                ?>
                <div class="mb-6 p-4 rounded-xl <?= $correctCount >= $totalQ * 0.7 ? 'bg-green-50 border border-green-100' : 'bg-amber-50 border border-amber-100' ?> text-center">
                    <p class="text-lg font-bold <?= $correctCount >= $totalQ * 0.7 ? 'text-green-700' : 'text-amber-700' ?>"><?= $correctCount ?>/<?= $totalQ ?> Correct (<?= calcPercent($correctCount, $totalQ) ?>%)</p>
                </div>

                <?php foreach ($questions as $idx => $q):
                    $ans = $session['answers'][$q['id']] ?? null;
                ?>
                <div class="mb-6 p-5 rounded-xl border <?= $ans && $ans['is_correct'] ? 'border-green-200 bg-green-50/30' : 'border-red-200 bg-red-50/30' ?>">
                    <p class="text-sm font-semibold text-gray-900 mb-3"><?= ($idx + 1) ?>. <?= sanitize($q['question_text']) ?></p>
                    <div class="space-y-2">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                            $field = 'choice_' . strtolower($opt);
                            $isCorrectChoice = $q['correct_answer'] === $opt;
                            $isUserChoice = $ans && $ans['answer'] === $opt;
                        ?>
                        <div class="flex items-center gap-2 p-2.5 rounded-lg text-sm <?= $isCorrectChoice ? 'bg-green-100 text-green-800 font-medium' : ($isUserChoice && !$isCorrectChoice ? 'bg-red-100 text-red-800' : 'text-gray-600') ?>">
                            <span class="w-6 h-6 rounded flex items-center justify-center text-xs font-bold <?= $isCorrectChoice ? 'bg-green-500 text-white' : ($isUserChoice ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-500') ?>"><?= $opt ?></span>
                            <?= sanitize($q[$field]) ?>
                            <?php if ($isCorrectChoice): ?> ✓<?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($q['explanation']): ?>
                    <div class="mt-3 p-3 rounded-lg bg-blue-50 text-sm text-blue-700">
                        <strong>💡</strong> <?= sanitize($q['explanation']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="flex justify-center gap-3 mt-6">
                    <a href="<?= APP_URL ?>/reading.php?passage=<?= $passageId ?>&reset=1" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Try Again</a>
                    <a href="<?= APP_URL ?>/reading.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">All Passages</a>
                </div>

            <?php else: ?>
                <form method="POST">
                    <?= csrfField() ?>
                    <?php foreach ($questions as $idx => $q): ?>
                    <div class="mb-6 pb-6 <?= $idx < count($questions) - 1 ? 'border-b border-gray-100' : '' ?>">
                        <p class="text-sm font-semibold text-gray-900 mb-3"><?= ($idx + 1) ?>. <?= sanitize($q['question_text']) ?></p>
                        <div class="space-y-2">
                            <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                                $field = 'choice_' . strtolower($opt);
                            ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 cursor-pointer transition group">
                                <input type="radio" name="answer_<?= $q['id'] ?>" value="<?= $opt ?>" required class="hidden peer">
                                <span class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-blue-100 peer-checked:bg-blue-600 peer-checked:text-white flex items-center justify-center text-xs font-bold text-gray-500 transition"><?= $opt ?></span>
                                <span class="text-sm text-gray-700"><?= sanitize($q[$field]) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" name="submit_answers" value="1" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm">Submit All Answers</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

<?php
} else {
    // Passage list
    $where = "rp.status = 'active'";
    $params = [];
    if ($categoryFilter) { $where .= " AND rp.category_id = ?"; $params[] = $categoryFilter; }
    if ($difficultyFilter) { $where .= " AND rp.difficulty = ?"; $params[] = $difficultyFilter; }

    $stmt = $db->prepare("SELECT rp.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
        (SELECT COUNT(*) FROM reading_questions rq WHERE rq.passage_id = rp.id) as question_count
        FROM reading_passages rp LEFT JOIN categories c ON rp.category_id = c.id WHERE $where ORDER BY rp.id");
    $stmt->execute($params);
    $passages = $stmt->fetchAll();

    require_once __DIR__ . '/includes/header.php';
?>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <select name="category" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= sanitize($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="difficulty" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                <option value="">All Levels</option>
                <option value="beginner" <?= $difficultyFilter === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                <option value="intermediate" <?= $difficultyFilter === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                <option value="advanced" <?= $difficultyFilter === 'advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Filter</button>
        </form>
    </div>

    <?php if (empty($passages)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <p class="text-gray-400 text-sm">No reading passages found.</p>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($passages as $p): ?>
        <a href="<?= APP_URL ?>/reading.php?passage=<?= $p['id'] ?>" class="group bg-white rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50 transition-all p-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg" style="background: <?= $p['category_color'] ?? '#3B82F6' ?>15; color: <?= $p['category_color'] ?? '#3B82F6' ?>"><?= sanitize($p['category_name'] ?? 'General') ?></span>
                <span class="px-2 py-0.5 text-xs rounded-md bg-gray-50 text-gray-500 capitalize"><?= $p['content_type'] ?></span>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 transition mb-3"><?= sanitize($p['title']) ?></h3>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="capitalize"><?= $p['difficulty'] ?></span>
                <span><?= $p['question_count'] ?> question<?= $p['question_count'] != 1 ? 's' : '' ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php } ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
