<?php
$pageTitle = 'Mock Exam';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

$examId = (int)($_GET['take'] ?? 0);

if ($examId) {
    $stmt = $db->prepare("SELECT * FROM mock_exams WHERE id = ? AND status = 'active'");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();
    if (!$exam) { redirect(APP_URL . '/mock-exam.php', 'error', 'Exam not found.'); }

    $pageTitle = $exam['title'];

    $stmt = $db->prepare("SELECT * FROM mock_exam_questions WHERE exam_id = ? ORDER BY section, question_number");
    $stmt->execute([$examId]);
    $questions = $stmt->fetchAll();

    $listeningQs = array_filter($questions, fn($q) => $q['section'] === 'listening');
    $readingQs = array_filter($questions, fn($q) => $q['section'] === 'reading');

    $sessionKey = 'mock_exam_' . $examId;
    if (!isset($_SESSION[$sessionKey]) || isset($_GET['restart'])) {
        $_SESSION[$sessionKey] = ['answers' => [], 'start_time' => time()];
    }
    $session = &$_SESSION[$sessionKey];

    // Handle submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
        if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $listeningScore = 0; $readingScore = 0; $totalCorrect = 0;
            $answers = [];

            foreach ($questions as $q) {
                $answer = strtoupper($_POST['answer_' . $q['id']] ?? '');
                $isCorrect = $answer === $q['correct_answer'];
                if ($isCorrect) {
                    $totalCorrect++;
                    if ($q['section'] === 'listening') $listeningScore += $q['points'];
                    else $readingScore += $q['points'];
                }
                $answers[$q['id']] = ['answer' => $answer, 'correct' => $q['correct_answer'], 'is_correct' => $isCorrect, 'section' => $q['section']];
                if (!$isCorrect && $answer) {
                    logMistake($userId, 'mock_exam', $q['id'], $q['question_text'], $answer, $q['correct_answer'], $q['explanation']);
                }
            }

            $session['answers'] = $answers;
            $totalScore = $listeningScore + $readingScore;
            $percentage = calcPercent($totalScore, $exam['total_score']);
            $timeSpent = time() - $session['start_time'];
            $totalIncorrect = count($questions) - $totalCorrect;

            $stmt = $db->prepare("INSERT INTO mock_exam_attempts (user_id, exam_id, listening_score, reading_score, total_score, total_correct, total_incorrect, percentage, time_spent_seconds, status, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())");
            $stmt->execute([$userId, $examId, $listeningScore, $readingScore, $totalScore, $totalCorrect, $totalIncorrect, $percentage, $timeSpent]);
            $attemptId = $db->lastInsertId();

            foreach ($answers as $qId => $ans) {
                $stmt = $db->prepare("INSERT INTO mock_exam_attempt_answers (attempt_id, question_id, user_answer, is_correct) VALUES (?, ?, ?, ?)");
                $stmt->execute([$attemptId, $qId, $ans['answer'], $ans['is_correct'] ? 1 : 0]);
            }

            logActivity($userId, 'exam', "Completed mock exam: {$exam['title']} ({$percentage}%)", $examId);
            redirect(APP_URL . '/mock-exam.php?take=' . $examId . '&results=1');
        }
    }

    $showResults = isset($_GET['results']) && !empty($session['answers']);
    $currentSection = $_GET['section'] ?? 'listening';
    $currentQ = (int)($_GET['q'] ?? 0);

    require_once __DIR__ . '/includes/header.php';
?>

    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="<?= APP_URL ?>/mock-exam.php" class="hover:text-blue-600 transition">Mock Exams</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600"><?= sanitize($exam['title']) ?></span>
    </nav>

    <?php if ($showResults): ?>
    <?php
    $answers = $session['answers'];
    $lCorrect = count(array_filter($answers, fn($a) => $a['is_correct'] && $a['section'] === 'listening'));
    $rCorrect = count(array_filter($answers, fn($a) => $a['is_correct'] && $a['section'] === 'reading'));
    $totalCorrect = $lCorrect + $rCorrect;
    $totalQ = count($questions);
    $lTotal = count($listeningQs); $rTotal = count($readingQs);
    $lScore = $lCorrect * 4; $rScore = $rCorrect * 4;
    $totalScore = $lScore + $rScore;
    $pct = calcPercent($totalScore, $exam['total_score']);
    $passed = $totalScore >= $exam['passing_score'];
    $timeSpent = time() - $session['start_time'];
    ?>

    <!-- Score Summary -->
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center mb-6">
            <div class="w-24 h-24 rounded-full <?= $passed ? 'bg-green-50' : 'bg-red-50' ?> flex items-center justify-center mx-auto mb-4">
                <span class="text-5xl"><?= $passed ? '🎉' : '💪' ?></span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1"><?= $passed ? 'Congratulations! You Passed!' : 'Keep Practicing!' ?></h2>
            <p class="text-gray-500 mb-6"><?= sanitize($exam['title']) ?></p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="p-4 rounded-xl bg-blue-50">
                    <p class="text-3xl font-bold text-blue-600"><?= $totalScore ?></p>
                    <p class="text-xs text-blue-400">Total Score / <?= $exam['total_score'] ?></p>
                </div>
                <div class="p-4 rounded-xl bg-purple-50">
                    <p class="text-3xl font-bold text-purple-600"><?= round($pct) ?>%</p>
                    <p class="text-xs text-purple-400">Percentage</p>
                </div>
                <div class="p-4 rounded-xl bg-green-50">
                    <p class="text-3xl font-bold text-green-600"><?= $totalCorrect ?></p>
                    <p class="text-xs text-green-400">Correct / <?= $totalQ ?></p>
                </div>
                <div class="p-4 rounded-xl bg-amber-50">
                    <p class="text-3xl font-bold text-amber-600"><?= gmdate("H:i:s", $timeSpent) ?></p>
                    <p class="text-xs text-amber-400">Time Spent</p>
                </div>
            </div>

            <!-- Section Breakdown -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-5 rounded-xl border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 mb-2">🎧 Listening</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $lScore ?> pts</p>
                    <p class="text-xs text-gray-400"><?= $lCorrect ?>/<?= $lTotal ?> correct</p>
                    <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width:<?= calcPercent($lCorrect, $lTotal) ?>%"></div>
                    </div>
                </div>
                <div class="p-5 rounded-xl border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 mb-2">📖 Reading</p>
                    <p class="text-2xl font-bold text-emerald-600"><?= $rScore ?> pts</p>
                    <p class="text-xs text-gray-400"><?= $rCorrect ?>/<?= $rTotal ?> correct</p>
                    <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width:<?= calcPercent($rCorrect, $rTotal) ?>%"></div>
                    </div>
                </div>
            </div>

            <?php
            $weakArea = $lCorrect / max($lTotal, 1) < $rCorrect / max($rTotal, 1) ? 'Listening' : 'Reading';
            $strongArea = $weakArea === 'Listening' ? 'Reading' : 'Listening';
            ?>
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-100 text-sm text-amber-800">
                <strong>Recommendation:</strong> Focus more on <strong><?= $weakArea ?></strong> practice. Your <strong><?= $strongArea ?></strong> is stronger.
            </div>
        </div>

        <!-- Detailed Review -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Question Review</h3>
            <?php foreach ($questions as $idx => $q):
                $ans = $answers[$q['id']] ?? null;
            ?>
            <div class="mb-4 pb-4 <?= $idx < count($questions) - 1 ? 'border-b border-gray-50' : '' ?>">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold <?= $ans && $ans['is_correct'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $q['question_number'] ?></span>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-gray-100 text-gray-500 uppercase"><?= $q['section'] ?></span>
                    <span class="text-xs text-gray-400 ml-auto">
                        Your: <strong class="<?= $ans && $ans['is_correct'] ? 'text-green-600' : 'text-red-600' ?>"><?= $ans['answer'] ?: '-' ?></strong>
                        | Correct: <strong class="text-green-600"><?= $q['correct_answer'] ?></strong>
                    </span>
                </div>
                <?php if (!empty($q['passage_text'])): ?>
                <div class="bg-gray-50 rounded-lg p-3 mb-2 korean-text text-xs leading-relaxed text-gray-600 whitespace-pre-line border border-gray-100">
                    <?= nl2br(sanitize($q['passage_text'])) ?>
                </div>
                <?php endif; ?>
                <p class="text-sm text-gray-700"><?= sanitize($q['question_text']) ?></p>
                <?php if (!($ans && $ans['is_correct']) && $q['explanation']): ?>
                <p class="text-xs text-blue-600 mt-1">💡 <?= sanitize($q['explanation']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-center gap-3">
            <a href="<?= APP_URL ?>/mock-exam.php?take=<?= $examId ?>&restart=1" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Retake Exam</a>
            <a href="<?= APP_URL ?>/mock-exam.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">All Exams</a>
        </div>
    </div>

    <?php else: ?>
    <!-- Exam Taking UI -->
    <div class="max-w-4xl mx-auto">
        <!-- Timer & Info Bar -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6 sticky top-16 z-20">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <h3 class="text-sm font-semibold text-gray-900"><?= sanitize($exam['title']) ?></h3>
                    <span class="text-xs text-gray-400"><?= count($questions) ?> questions</span>
                </div>
                <div class="flex items-center gap-3">
                    <div id="examTimer" class="text-lg font-mono font-bold text-blue-600 bg-blue-50 px-4 py-2 rounded-xl"></div>
                    <button onclick="confirmSubmit()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-medium transition">Submit Exam</button>
                </div>
            </div>

            <!-- Question Navigator -->
            <div class="mt-3 flex flex-wrap gap-1.5">
                <?php foreach ($questions as $idx => $q): ?>
                <a href="#q<?= $q['id'] ?>" class="question-nav w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-xs font-medium text-gray-500 hover:bg-blue-50 hover:border-blue-200 transition" data-qid="<?= $q['id'] ?>">
                    <?= $q['question_number'] ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" id="examForm" onsubmit="return confirmBeforeSubmit()">
            <?= csrfField() ?>

            <!-- Listening Section -->
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-sm">🎧</span>
                    Listening Section
                </h3>
                <?php foreach ($listeningQs as $q): ?>
                <div id="q<?= $q['id'] ?>" class="bg-white rounded-2xl border border-gray-100 p-6 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-blue-600">Question <?= $q['question_number'] ?></span>
                        <span class="text-xs text-gray-400"><?= $q['points'] ?> pts</span>
                    </div>

                    <?php
                    $examHasAudio = !empty($q['audio_path']) && $q['audio_path'] !== 'audio/exam/placeholder.mp3';
                    // Use passage_text (dialogue script) for TTS, fall back to question_text
                    $examTtsText = !empty($q['passage_text']) ? $q['passage_text'] : $q['question_text'];
                    ?>
                    <div class="bg-blue-50 rounded-xl p-4 mb-4 text-center">
                        <?php if ($examHasAudio): ?>
                        <audio id="audio_<?= $q['id'] ?>" preload="auto"><source src="<?= APP_URL ?>/uploads/<?= sanitize($q['audio_path']) ?>" type="audio/mpeg"></audio>
                        <?php endif; ?>
                        <button type="button"
                            onclick="playExamAudio('<?= $q['id'] ?>')"
                            id="examBtn_<?= $q['id'] ?>"
                            data-has-file="<?= $examHasAudio ? '1' : '0' ?>"
                            data-tts-text="<?= htmlspecialchars($examTtsText, ENT_QUOTES, 'UTF-8') ?>"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                            <span id="examBtnText_<?= $q['id'] ?>">Play Audio</span>
                        </button>
                        <button type="button"
                            onclick="playExamAudioSlow('<?= $q['id'] ?>')"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition" title="Slow playback">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Slow
                        </button>
                        <?php if (!$examHasAudio): ?>
                        <p class="text-xs text-amber-500 mt-1.5">AI voice</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($q['passage_text'])): ?>
                    <div class="mb-4">
                        <button type="button" onclick="toggleExamScript(this)" class="inline-flex items-center gap-2 text-xs font-medium text-blue-600 hover:text-blue-700 mb-2 transition">
                            <svg class="w-3.5 h-3.5 script-icon transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <span class="script-toggle-text">대화 보기 (Show Script)</span>
                        </button>
                        <div class="dialogue-script hidden bg-gray-50 rounded-xl p-4 korean-text text-sm leading-relaxed text-gray-700 whitespace-pre-line border border-gray-100">
                            <?= nl2br(sanitize($q['passage_text'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <p class="text-sm font-semibold text-gray-900 mb-4"><?= sanitize($q['question_text']) ?></p>

                    <div class="space-y-2">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                            $field = 'choice_' . strtolower($opt);
                        ?>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-blue-200 cursor-pointer transition group">
                            <input type="radio" name="answer_<?= $q['id'] ?>" value="<?= $opt ?>" class="hidden peer answer-input" data-qid="<?= $q['id'] ?>" onchange="markAnswered(<?= $q['id'] ?>)">
                            <span class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-blue-100 peer-checked:bg-blue-600 peer-checked:text-white flex items-center justify-center text-xs font-bold text-gray-500 transition"><?= $opt ?></span>
                            <span class="text-sm text-gray-700"><?= sanitize($q[$field]) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Reading Section -->
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-sm">📖</span>
                    Reading Section
                </h3>
                <?php foreach ($readingQs as $q): ?>
                <div id="q<?= $q['id'] ?>" class="bg-white rounded-2xl border border-gray-100 p-6 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-emerald-600">Question <?= $q['question_number'] ?></span>
                        <span class="text-xs text-gray-400"><?= $q['points'] ?> pts</span>
                    </div>

                    <?php if ($q['passage_text']): ?>
                    <div class="bg-gray-50 rounded-xl p-5 mb-4 korean-text text-sm leading-relaxed text-gray-700 whitespace-pre-line border border-gray-100">
                        <?= nl2br(sanitize($q['passage_text'])) ?>
                    </div>
                    <?php endif; ?>

                    <p class="text-sm font-semibold text-gray-900 mb-4"><?= sanitize($q['question_text']) ?></p>

                    <div class="space-y-2">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                            $field = 'choice_' . strtolower($opt);
                        ?>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-blue-200 cursor-pointer transition group">
                            <input type="radio" name="answer_<?= $q['id'] ?>" value="<?= $opt ?>" class="hidden peer answer-input" data-qid="<?= $q['id'] ?>" onchange="markAnswered(<?= $q['id'] ?>)">
                            <span class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-emerald-100 peer-checked:bg-emerald-600 peer-checked:text-white flex items-center justify-center text-xs font-bold text-gray-500 transition"><?= $opt ?></span>
                            <span class="text-sm text-gray-700"><?= sanitize($q[$field]) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center">
                <button type="submit" name="submit_exam" value="1" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm shadow-lg shadow-blue-600/20">
                    Submit Exam
                </button>
            </div>
        </form>
    </div>

    <script>
    // Timer
    let timeLeft = <?= $exam['time_limit_minutes'] * 60 ?>;
    const timerEl = document.getElementById('examTimer');
    const timerInterval = setInterval(() => {
        timeLeft--;
        const h = Math.floor(timeLeft / 3600);
        const m = Math.floor((timeLeft % 3600) / 60);
        const s = timeLeft % 60;
        timerEl.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (timeLeft <= 300) timerEl.classList.replace('text-blue-600', 'text-red-600');
        if (timeLeft <= 0) { clearInterval(timerInterval); document.getElementById('examForm').submit(); }
    }, 1000);
    timerEl.textContent = `${String(Math.floor(timeLeft / 3600)).padStart(2,'0')}:${String(Math.floor((timeLeft % 3600) / 60)).padStart(2,'0')}:${String(timeLeft % 60).padStart(2,'0')}`;

    function markAnswered(qid) {
        const nav = document.querySelector(`.question-nav[data-qid="${qid}"]`);
        if (nav) { nav.classList.add('bg-blue-500', 'text-white', 'border-blue-500'); nav.classList.remove('text-gray-500'); }
    }

    let currentExamAudio = null;
    function playExamAudio(qId) {
        // Stop any previous playback
        if (currentExamAudio) { currentExamAudio.pause(); currentExamAudio.currentTime = 0; }
        KoreanTTS.stop();

        const btn = document.getElementById('examBtn_' + qId);
        const hasFile = btn?.dataset.hasFile === '1';
        const ttsText = btn?.dataset.ttsText || '';
        const textEl = document.getElementById('examBtnText_' + qId);

        if (hasFile) {
            const a = document.getElementById('audio_' + qId);
            if (a) {
                currentExamAudio = a;
                if (textEl) textEl.textContent = 'Playing...';
                a.currentTime = 0;
                a.play();
                a.onended = () => { if (textEl) textEl.textContent = 'Play Again'; currentExamAudio = null; };
                a.onerror = () => {
                    // Fallback to TTS on file error
                    if (ttsText) KoreanTTS.speak(ttsText, { type: 'browser_tts', onEnd: () => { if (textEl) textEl.textContent = 'Play Again'; } });
                    else if (textEl) textEl.textContent = 'Play Again';
                };
            }
        } else if (ttsText) {
            if (textEl) textEl.textContent = 'Playing...';
            KoreanTTS.speak(ttsText, {
                type: 'browser_tts',
                onEnd: () => { if (textEl) textEl.textContent = 'Play Again'; },
                onError: () => { if (textEl) textEl.textContent = 'Play Again'; }
            });
        }
    }

    function playExamAudioSlow(qId) {
        // Stop any previous playback
        if (currentExamAudio) { currentExamAudio.pause(); currentExamAudio.currentTime = 0; currentExamAudio.playbackRate = 1.0; }
        KoreanTTS.stop();

        const btn = document.getElementById('examBtn_' + qId);
        const hasFile = btn?.dataset.hasFile === '1';
        const ttsText = btn?.dataset.ttsText || '';
        const textEl = document.getElementById('examBtnText_' + qId);

        if (hasFile) {
            const a = document.getElementById('audio_' + qId);
            if (a) {
                currentExamAudio = a;
                if (textEl) textEl.textContent = 'Playing slow...';
                a.playbackRate = 0.6;
                a.currentTime = 0;
                a.play();
                a.onended = () => { a.playbackRate = 1.0; if (textEl) textEl.textContent = 'Play Again'; currentExamAudio = null; };
                a.onerror = () => {
                    a.playbackRate = 1.0;
                    if (ttsText) KoreanTTS.speak(ttsText, { type: 'browser_tts', rate: 0.6, onEnd: () => { if (textEl) textEl.textContent = 'Play Again'; } });
                    else if (textEl) textEl.textContent = 'Play Again';
                };
            }
        } else if (ttsText) {
            if (textEl) textEl.textContent = 'Playing slow...';
            KoreanTTS.speak(ttsText, {
                type: 'browser_tts',
                rate: 0.6,
                onEnd: () => { if (textEl) textEl.textContent = 'Play Again'; },
                onError: () => { if (textEl) textEl.textContent = 'Play Again'; }
            });
        }
    }

    function confirmSubmit() {
        const unanswered = document.querySelectorAll('.question-nav:not(.bg-blue-500)').length;
        if (unanswered > 0) {
            if (confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`)) {
                document.getElementById('examForm').submit();
            }
        } else {
            document.getElementById('examForm').submit();
        }
    }

    function confirmBeforeSubmit() {
        return confirm('Are you sure you want to submit this exam?');
    }

    // Warn before leaving
    window.addEventListener('beforeunload', (e) => { e.preventDefault(); e.returnValue = ''; });

    function toggleExamScript(btn) {
        const container = btn.parentElement;
        const script = container.querySelector('.dialogue-script');
        const icon = btn.querySelector('.script-icon');
        const text = btn.querySelector('.script-toggle-text');
        if (script.classList.contains('hidden')) {
            script.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
            text.textContent = '대화 숨기기 (Hide Script)';
        } else {
            script.classList.add('hidden');
            icon.style.transform = '';
            text.textContent = '대화 보기 (Show Script)';
        }
    }
    </script>
    <?php endif; ?>

<?php
} else {
    // Exam list
    $stmt = $db->prepare("SELECT me.*, 
        (SELECT mea.percentage FROM mock_exam_attempts mea WHERE mea.user_id = ? AND mea.exam_id = me.id AND mea.status = 'completed' ORDER BY mea.completed_at DESC LIMIT 1) as latest_score,
        (SELECT COUNT(*) FROM mock_exam_attempts mea WHERE mea.user_id = ? AND mea.exam_id = me.id AND mea.status = 'completed') as attempt_count
        FROM mock_exams me WHERE me.status = 'active' ORDER BY me.id");
    $stmt->execute([$userId, $userId]);
    $exams = $stmt->fetchAll();

    require_once __DIR__ . '/includes/header.php';
?>
    <p class="text-sm text-gray-500 mb-6">Simulate the real EPS-TOPIK exam experience</p>

    <!-- Exam Info -->
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-6 mb-6 border border-indigo-100">
        <h3 class="text-sm font-semibold text-indigo-900 mb-2">About the EPS-TOPIK Mock Exam</h3>
        <div class="grid sm:grid-cols-3 gap-4 text-sm text-indigo-700">
            <div><strong>Format:</strong> Listening + Reading</div>
            <div><strong>Duration:</strong> 70 minutes</div>
            <div><strong>Passing:</strong> 80/200 points</div>
        </div>
    </div>

    <?php if (empty($exams)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <p class="text-gray-400 text-sm">No mock exams available yet.</p>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <?php foreach ($exams as $e): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-blue-50 transition-all">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-lg">📝</span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900"><?= sanitize($e['title']) ?></h3>
                    <p class="text-xs text-gray-400"><?= $e['listening_count'] + $e['reading_count'] ?> questions · <?= $e['time_limit_minutes'] ?> min</p>
                </div>
            </div>
            <?php if ($e['description']): ?><p class="text-xs text-gray-500 mb-3"><?= sanitize($e['description']) ?></p><?php endif; ?>

            <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                <div class="p-2 rounded-lg bg-gray-50"><p class="text-xs text-gray-400">Listening</p><p class="text-sm font-bold"><?= $e['listening_count'] ?></p></div>
                <div class="p-2 rounded-lg bg-gray-50"><p class="text-xs text-gray-400">Reading</p><p class="text-sm font-bold"><?= $e['reading_count'] ?></p></div>
                <div class="p-2 rounded-lg bg-gray-50"><p class="text-xs text-gray-400">Pass</p><p class="text-sm font-bold"><?= $e['passing_score'] ?></p></div>
            </div>

            <?php if ($e['latest_score'] !== null): ?>
            <div class="p-3 rounded-xl bg-blue-50 mb-4 flex items-center justify-between">
                <span class="text-xs text-blue-600">Latest: <strong><?= round($e['latest_score']) ?>%</strong></span>
                <span class="text-xs text-blue-400"><?= $e['attempt_count'] ?> attempt<?= $e['attempt_count'] != 1 ? 's' : '' ?></span>
            </div>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/mock-exam.php?take=<?= $e['id'] ?>&restart=1" class="block text-center py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition shadow-lg shadow-indigo-600/20">
                <?= $e['attempt_count'] > 0 ? 'Retake Exam' : 'Start Exam' ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php } ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
