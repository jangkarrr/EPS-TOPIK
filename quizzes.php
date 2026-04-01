<?php
$pageTitle = 'Quizzes';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

// Check if taking a quiz
$quizId = (int)($_GET['take'] ?? 0);

if ($quizId) {
    // Taking a quiz
    $stmt = $db->prepare("SELECT q.*, c.name as category_name FROM quizzes q LEFT JOIN categories c ON q.category_id = c.id WHERE q.id = ? AND q.status = 'active'");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch();
    if (!$quiz) { redirect(APP_URL . '/quizzes.php', 'error', 'Quiz not found.'); }

    $pageTitle = $quiz['title'];

    $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order, id");
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll();

    $sessionKey = 'quiz_' . $quizId;
    if (!isset($_SESSION[$sessionKey]) || isset($_GET['restart'])) {
        $_SESSION[$sessionKey] = ['answers' => [], 'start_time' => time()];
    }
    $session = &$_SESSION[$sessionKey];

    // Handle submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
        if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $score = 0;
            $answers = [];
            foreach ($questions as $q) {
                $answer = strtoupper($_POST['answer_' . $q['id']] ?? '');
                $isCorrect = $answer === $q['correct_answer'];
                if ($isCorrect) $score++;
                $answers[$q['id']] = ['answer' => $answer, 'correct' => $q['correct_answer'], 'is_correct' => $isCorrect];
                if (!$isCorrect && $answer) {
                    logMistake($userId, 'quiz', $q['id'], $q['question_text'], $answer, $q['correct_answer'], $q['explanation']);
                }
            }
            $session['answers'] = $answers;
            $totalQ = count($questions);
            $percentage = calcPercent($score, $totalQ);
            $timeSpent = time() - $session['start_time'];

            // Save attempt
            $stmt = $db->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions, percentage, time_spent_seconds) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $quizId, $score, $totalQ, $percentage, $timeSpent]);
            $attemptId = $db->lastInsertId();

            foreach ($answers as $qId => $ans) {
                $stmt = $db->prepare("INSERT INTO quiz_attempt_answers (attempt_id, question_id, user_answer, is_correct) VALUES (?, ?, ?, ?)");
                $stmt->execute([$attemptId, $qId, $ans['answer'], $ans['is_correct'] ? 1 : 0]);
            }

            logActivity($userId, 'quiz', "Completed quiz: {$quiz['title']} ({$percentage}%)", $quizId);
            redirect(APP_URL . '/quizzes.php?take=' . $quizId . '&results=1');
        }
    }

    $showResults = isset($_GET['results']) && !empty($session['answers']);

    require_once __DIR__ . '/includes/header.php';
?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="<?= APP_URL ?>/quizzes.php" class="hover:text-blue-600 transition">Quizzes</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600"><?= sanitize($quiz['title']) ?></span>
    </nav>

    <div class="max-w-3xl mx-auto">
        <?php if ($showResults): ?>
        <!-- Results -->
        <?php
        $correctCount = count(array_filter($session['answers'], fn($a) => $a['is_correct']));
        $totalQ = count($questions);
        $pct = calcPercent($correctCount, $totalQ);
        $timeSpent = time() - $session['start_time'];
        ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center mb-6">
            <div class="w-20 h-20 rounded-full <?= $pct >= 70 ? 'bg-green-50' : ($pct >= 50 ? 'bg-amber-50' : 'bg-red-50') ?> flex items-center justify-center mx-auto mb-4">
                <?php if ($pct >= 70): ?><span class="text-4xl">🎉</span>
                <?php elseif ($pct >= 50): ?><span class="text-4xl">👍</span>
                <?php else: ?><span class="text-4xl">💪</span><?php endif; ?>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Quiz Complete!</h2>
            <p class="text-gray-500 mb-6"><?= sanitize($quiz['title']) ?></p>

            <div class="inline-block mb-6">
                <div class="relative w-32 h-32">
                    <svg class="w-32 h-32" style="transform:rotate(-90deg)" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#F1F5F9" stroke-width="10"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="<?= $pct >= 70 ? '#22C55E' : ($pct >= 50 ? '#F59E0B' : '#EF4444') ?>" stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="<?= 2 * 3.14159 * 52 ?>" stroke-dashoffset="<?= 2 * 3.14159 * 52 * (1 - $pct / 100) ?>"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-gray-900"><?= round($pct) ?>%</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="p-3 rounded-xl bg-green-50"><p class="text-xl font-bold text-green-600"><?= $correctCount ?></p><p class="text-xs text-green-500">Correct</p></div>
                <div class="p-3 rounded-xl bg-red-50"><p class="text-xl font-bold text-red-500"><?= $totalQ - $correctCount ?></p><p class="text-xs text-red-400">Wrong</p></div>
                <div class="p-3 rounded-xl bg-blue-50"><p class="text-xl font-bold text-blue-600"><?= gmdate("i:s", $timeSpent) ?></p><p class="text-xs text-blue-400">Time</p></div>
            </div>
        </div>

        <!-- Review Questions -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Review Answers</h3>
            <?php foreach ($questions as $idx => $q):
                $ans = $session['answers'][$q['id']] ?? null;
            ?>
            <div class="mb-5 pb-5 <?= $idx < count($questions) - 1 ? 'border-b border-gray-100' : '' ?>">
                <div class="flex items-start gap-2 mb-3">
                    <span class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold <?= $ans && $ans['is_correct'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= $idx + 1 ?>
                    </span>
                    <p class="text-sm font-medium text-gray-900"><?= sanitize($q['question_text']) ?></p>
                </div>
                <div class="ml-8 space-y-1.5">
                    <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                        $field = 'choice_' . strtolower($opt);
                        if (!$q[$field]) continue;
                        $isCorrectChoice = $q['correct_answer'] === $opt;
                        $isUserChoice = $ans && $ans['answer'] === $opt;
                    ?>
                    <div class="flex items-center gap-2 text-sm py-1 <?= $isCorrectChoice ? 'text-green-700 font-medium' : ($isUserChoice && !$isCorrectChoice ? 'text-red-600 line-through' : 'text-gray-500') ?>">
                        <span class="w-5 h-5 rounded text-[10px] font-bold flex items-center justify-center <?= $isCorrectChoice ? 'bg-green-500 text-white' : ($isUserChoice ? 'bg-red-500 text-white' : 'bg-gray-100') ?>"><?= $opt ?></span>
                        <?= sanitize($q[$field]) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($q['explanation'] && $ans && !$ans['is_correct']): ?>
                <div class="ml-8 mt-2 p-2.5 rounded-lg bg-blue-50 text-xs text-blue-700">💡 <?= sanitize($q['explanation']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-center gap-3">
            <a href="<?= APP_URL ?>/quizzes.php?take=<?= $quizId ?>&restart=1" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Retry Quiz</a>
            <a href="<?= APP_URL ?>/quizzes.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">All Quizzes</a>
        </div>

        <?php else: ?>
        <!-- Quiz Form -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900"><?= sanitize($quiz['title']) ?></h2>
                    <p class="text-sm text-gray-500"><?= count($questions) ?> questions<?= $quiz['time_limit_minutes'] ? ' · ' . $quiz['time_limit_minutes'] . ' min' : '' ?></p>
                </div>
                <?php if ($quiz['time_limit_minutes']): ?>
                <div id="timer" class="text-lg font-mono font-bold text-blue-600 bg-blue-50 px-4 py-2 rounded-xl"></div>
                <?php endif; ?>
            </div>

            <form method="POST" id="quizForm">
                <?= csrfField() ?>
                <?php foreach ($questions as $idx => $q): ?>
                <div class="mb-6 pb-6 <?= $idx < count($questions) - 1 ? 'border-b border-gray-100' : '' ?>">
                    <p class="text-sm font-semibold text-gray-900 mb-3">
                        <span class="text-blue-600"><?= $idx + 1 ?>.</span> <?= sanitize($q['question_text']) ?>
                    </p>
                    <div class="space-y-2">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                            $field = 'choice_' . strtolower($opt);
                            if (!$q[$field]) continue;
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

                <button type="submit" name="submit_quiz" value="1" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm">
                    Submit Quiz
                </button>
            </form>
        </div>

        <?php if ($quiz['time_limit_minutes']): ?>
        <script>
        let timeLeft = <?= $quiz['time_limit_minutes'] * 60 ?>;
        const timerEl = document.getElementById('timer');
        const interval = setInterval(() => {
            timeLeft--;
            const m = Math.floor(timeLeft / 60);
            const s = timeLeft % 60;
            timerEl.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            if (timeLeft <= 60) timerEl.classList.add('text-red-600');
            if (timeLeft <= 0) {
                clearInterval(interval);
                document.getElementById('quizForm').submit();
            }
        }, 1000);
        timerEl.textContent = `${String(Math.floor(timeLeft / 60)).padStart(2, '0')}:${String(timeLeft % 60).padStart(2, '0')}`;
        </script>
        <?php endif; ?>
        <?php endif; ?>
    </div>

<?php
} else {
    // Quiz List
    $stmt = $db->prepare("SELECT q.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
        (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as actual_questions,
        (SELECT qa.percentage FROM quiz_attempts qa WHERE qa.user_id = ? AND qa.quiz_id = q.id ORDER BY qa.completed_at DESC LIMIT 1) as latest_score,
        (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.user_id = ? AND qa.quiz_id = q.id) as attempt_count
        FROM quizzes q LEFT JOIN categories c ON q.category_id = c.id WHERE q.status = 'active' ORDER BY q.id");
    $stmt->execute([$userId, $userId]);
    $quizzes = $stmt->fetchAll();

    require_once __DIR__ . '/includes/header.php';
?>
    <p class="text-sm text-gray-500 mb-6">Test your knowledge with quizzes on various topics</p>

    <?php if (empty($quizzes)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <p class="text-gray-400 text-sm">No quizzes available yet.</p>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($quizzes as $q): ?>
        <div class="bg-white rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50 transition-all p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg" style="background: <?= $q['category_color'] ?? '#3B82F6' ?>15; color: <?= $q['category_color'] ?? '#3B82F6' ?>">
                    <?= sanitize($q['category_name'] ?? $q['quiz_type']) ?>
                </span>
                <span class="px-2 py-0.5 text-xs rounded-md bg-gray-50 text-gray-500 capitalize"><?= $q['difficulty'] ?></span>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 mb-1"><?= sanitize($q['title']) ?></h3>
            <?php if ($q['description']): ?><p class="text-xs text-gray-400 mb-3"><?= sanitize($q['description']) ?></p><?php endif; ?>
            
            <div class="flex items-center gap-3 text-xs text-gray-400 mb-4">
                <span><?= $q['actual_questions'] ?> questions</span>
                <?php if ($q['time_limit_minutes']): ?><span><?= $q['time_limit_minutes'] ?> min</span><?php endif; ?>
                <span class="capitalize"><?= $q['quiz_type'] ?></span>
            </div>

            <?php if ($q['latest_score'] !== null): ?>
            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 mb-4">
                <span class="text-xs text-gray-500">Best: <span class="font-semibold text-gray-700"><?= round($q['latest_score']) ?>%</span></span>
                <span class="text-xs text-gray-400"><?= $q['attempt_count'] ?> attempt<?= $q['attempt_count'] != 1 ? 's' : '' ?></span>
            </div>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/quizzes.php?take=<?= $q['id'] ?>&restart=1" class="block text-center py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition">
                <?= $q['attempt_count'] > 0 ? 'Retake Quiz' : 'Start Quiz' ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php } ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
