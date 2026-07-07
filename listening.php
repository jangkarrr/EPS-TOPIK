<?php
$pageTitle = 'Listening Practice';
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
$mode = $_GET['mode'] ?? 'practice';

// Categories
$categories = $db->query("SELECT * FROM categories WHERE module = 'listening' AND status = 'active' ORDER BY sort_order")->fetchAll();

// Build query for questions
$where = "lq.status = 'active'";
$params = [];
if ($categoryFilter) { $where .= " AND lq.category_id = ?"; $params[] = $categoryFilter; }
if ($difficultyFilter) { $where .= " AND lq.difficulty = ?"; $params[] = $difficultyFilter; }

$stmt = $db->prepare("SELECT lq.*, c.name as category_name, c.icon as category_icon 
    FROM listening_questions lq LEFT JOIN categories c ON lq.category_id = c.id WHERE $where ORDER BY " . ($mode === 'practice' ? 'lq.id' : 'RAND()'));
$stmt->execute($params);
$questions = $stmt->fetchAll();

// Session tracking
$sessionKey = 'listening_session_' . md5(json_encode($_GET));
if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = ['answers' => [], 'current' => 0, 'start_time' => time()];
}
$session = &$_SESSION[$sessionKey];

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $qIndex = (int)$_POST['question_index'];
        $answer = strtoupper($_POST['answer']);
        
        if (isset($questions[$qIndex]) && !isset($session['answers'][$qIndex])) {
            $q = $questions[$qIndex];
            $isCorrect = $answer === $q['correct_answer'];
            $session['answers'][$qIndex] = [
                'answer' => $answer,
                'correct' => $q['correct_answer'],
                'is_correct' => $isCorrect
            ];
            
            if (!$isCorrect) {
                logMistake($userId, 'listening', $q['id'], $q['question_text'], $answer, $q['correct_answer'], $q['explanation']);
            }
            
            // Update daily goal
            $today = date('Y-m-d');
            $stmt2 = $db->prepare("INSERT INTO daily_goals (user_id, goal_date, completed_listening) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE completed_listening = completed_listening + 1");
            $stmt2->execute([$userId, $today]);
            
            logActivity($userId, 'listening', 'Answered listening question: ' . ($isCorrect ? 'Correct' : 'Incorrect'), $q['id']);
        }
    }
}

// Handle reset
if (isset($_GET['reset'])) {
    unset($_SESSION[$sessionKey]);
    redirect(APP_URL . '/listening.php?' . http_build_query(array_diff_key($_GET, ['reset' => ''])));
}

$currentQ = (int)($_GET['q'] ?? $session['current'] ?? 0);
$totalQ = count($questions);

require_once __DIR__ . '/includes/header.php';
?>

<?php if (empty($questions)): ?>
<!-- No questions -->
<div class="max-w-lg mx-auto text-center py-12">
    <div class="w-20 h-20 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Listening Questions Available</h3>
    <p class="text-sm text-gray-400 mb-4">Try selecting a different category or difficulty level.</p>
    <a href="<?= APP_URL ?>/listening.php" class="text-sm font-semibold text-blue-600 hover:underline">Reset Filters</a>
</div>

<?php else: ?>
<!-- Filters Bar -->
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <select name="category" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white flex-1">
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
        <select name="mode" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="practice" <?= $mode === 'practice' ? 'selected' : '' ?>>Practice Mode</option>
            <option value="random" <?= $mode === 'random' ? 'selected' : '' ?>>Random Mode</option>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Apply</button>
        <a href="<?= APP_URL ?>/listening.php?<?= http_build_query(array_merge($_GET, ['reset' => 1])) ?>" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition text-center">Reset</a>
    </form>
</div>

<!-- Progress Bar -->
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Progress</span>
        <span class="text-sm text-gray-500"><?= count($session['answers']) ?> / <?= $totalQ ?> answered</span>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-2.5">
        <div class="bg-blue-500 h-2.5 rounded-full transition-all" style="width: <?= calcPercent(count($session['answers']), $totalQ) ?>%"></div>
    </div>
    <?php
    $correctCount = count(array_filter($session['answers'], fn($a) => $a['is_correct']));
    $incorrectCount = count($session['answers']) - $correctCount;
    ?>
    <div class="flex items-center gap-4 mt-2 text-xs">
        <span class="text-green-600 font-medium"><?= $correctCount ?> correct</span>
        <span class="text-red-500 font-medium"><?= $incorrectCount ?> incorrect</span>
    </div>
</div>

<?php if ($currentQ < $totalQ): ?>
<?php $q = $questions[$currentQ]; $answered = $session['answers'][$currentQ] ?? null; ?>

<!-- Question Card -->
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
        <!-- Question Header -->
        <div class="flex items-center justify-between mb-6">
            <span class="text-xs font-medium text-gray-400">Question <?= $currentQ + 1 ?> of <?= $totalQ ?></span>
            <span class="px-2.5 py-1 text-xs rounded-lg bg-gray-50 text-gray-500 capitalize"><?= $q['difficulty'] ?></span>
        </div>

        <!-- Audio Player -->
        <?php
        $hasAudioFile = !empty($q['audio_path']) && $q['audio_path'] !== 'audio/listening/placeholder.mp3';
        $audioSrc = $hasAudioFile ? APP_URL . '/uploads/' . sanitize($q['audio_path']) : '';
        // Use dialogue_text for TTS if available, fall back to question_text
        $ttsText = !empty($q['dialogue_text']) ? $q['dialogue_text'] : $q['question_text'];
        ?>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-6 text-center">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
            </div>
            <?php if ($hasAudioFile): ?>
            <audio id="audioPlayer" class="hidden" preload="auto">
                <source src="<?= $audioSrc ?>" type="audio/mpeg">
            </audio>
            <?php endif; ?>
            <div class="flex items-center justify-center gap-3">
                <button onclick="playListeningAudio()" id="playBtn"
                    data-audio-url="<?= $audioSrc ?>"
                    data-tts-text="<?= htmlspecialchars($ttsText, ENT_QUOTES, 'UTF-8') ?>"
                    data-has-file="<?= $hasAudioFile ? '1' : '0' ?>"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium text-sm transition shadow-lg shadow-blue-600/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                    <span id="playText">Play Audio</span>
                </button>
                <button onclick="playListeningAudioSlow()" id="slowBtn"
                    class="inline-flex items-center gap-2 px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium text-sm transition shadow-lg shadow-amber-500/20" title="Play at 0.6x speed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Slow</span>
                </button>
                <button onclick="replayListeningAudio()" class="p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition" title="Replay">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
            <?php if (!$hasAudioFile): ?>
            <p class="text-xs text-amber-500 mt-2 flex items-center justify-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Using AI voice (no uploaded audio)
            </p>
            <?php else: ?>
            <p class="text-xs text-blue-400 mt-2">Listen carefully, then answer below</p>
            <?php endif; ?>
        </div>

        <!-- Dialogue Script (Korean conversation) -->
        <?php if (!empty($q['dialogue_text'])): ?>
        <div class="mb-6">
            <button type="button" onclick="toggleScript(this)" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 mb-3 transition">
                <svg class="w-4 h-4 script-icon transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                <span class="script-toggle-text">대화 보기 (Show Script)</span>
            </button>
            <div class="dialogue-script hidden bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-5 border border-gray-100">
                <div class="korean-text text-sm leading-relaxed text-gray-700 whitespace-pre-line"><?= nl2br(sanitize($q['dialogue_text'])) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Question Text -->
        <h3 class="text-lg font-semibold text-gray-900 mb-6"><?= sanitize($q['question_text']) ?></h3>

        <!-- Answer Choices -->
        <?php if ($answered): ?>
            <!-- Show results -->
            <div class="space-y-3">
                <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                    $field = 'choice_' . strtolower($opt);
                    $isCorrectChoice = $q['correct_answer'] === $opt;
                    $isUserChoice = $answered['answer'] === $opt;
                    $borderClass = $isCorrectChoice ? 'border-green-300 bg-green-50' : ($isUserChoice && !$isCorrectChoice ? 'border-red-300 bg-red-50' : 'border-gray-100');
                ?>
                <div class="flex items-center gap-3 p-4 rounded-xl border-2 <?= $borderClass ?>">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold
                        <?= $isCorrectChoice ? 'bg-green-500 text-white' : ($isUserChoice ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-500') ?>">
                        <?= $opt ?>
                    </span>
                    <span class="text-sm <?= $isCorrectChoice ? 'text-green-800 font-medium' : ($isUserChoice ? 'text-red-800' : 'text-gray-600') ?>"><?= sanitize($q[$field]) ?></span>
                    <?php if ($isCorrectChoice): ?>
                        <svg class="w-5 h-5 text-green-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <?php elseif ($isUserChoice && !$isCorrectChoice): ?>
                        <svg class="w-5 h-5 text-red-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Explanation -->
            <?php if ($q['explanation']): ?>
            <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                <div class="flex items-start gap-2">
                    <span class="text-lg">💡</span>
                    <div>
                        <p class="text-xs font-semibold text-blue-700 mb-1">Explanation</p>
                        <p class="text-sm text-blue-800"><?= sanitize($q['explanation']) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Result badge -->
            <div class="mt-4 text-center">
                <?php if ($answered['is_correct']): ?>
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-xl font-medium text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Correct!
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-xl font-medium text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Incorrect
                    </span>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Answer form -->
            <form method="POST" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="question_index" value="<?= $currentQ ?>">
                <?php foreach (['A', 'B', 'C', 'D'] as $opt):
                    $field = 'choice_' . strtolower($opt);
                ?>
                <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 cursor-pointer transition group">
                    <input type="radio" name="answer" value="<?= $opt ?>" required class="hidden peer">
                    <span class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-blue-100 peer-checked:bg-blue-600 peer-checked:text-white flex items-center justify-center text-sm font-bold text-gray-500 transition"><?= $opt ?></span>
                    <span class="text-sm text-gray-700 peer-checked:text-blue-700 peer-checked:font-medium"><?= sanitize($q[$field]) ?></span>
                </label>
                <?php endforeach; ?>
                <div class="pt-3">
                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm">Submit Answer</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Navigation -->
    <div class="flex items-center justify-between">
        <?php if ($currentQ > 0): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['q' => $currentQ - 1])) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Previous
        </a>
        <?php else: ?><div></div><?php endif; ?>

        <?php if ($currentQ < $totalQ - 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['q' => $currentQ + 1])) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition">
            Next
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <?php else: ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['q' => $totalQ])) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium transition">
            View Results
        </a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Results Page -->
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center mb-6">
        <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
            <?php if ($correctCount >= $totalQ * 0.7): ?>
                <span class="text-4xl">🎉</span>
            <?php elseif ($correctCount >= $totalQ * 0.5): ?>
                <span class="text-4xl">👍</span>
            <?php else: ?>
                <span class="text-4xl">💪</span>
            <?php endif; ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Session Complete!</h2>
        <p class="text-gray-500 mb-6">Here's how you did</p>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="p-4 rounded-xl bg-blue-50">
                <p class="text-2xl font-bold text-blue-600"><?= $totalQ ?></p>
                <p class="text-xs text-blue-400">Total</p>
            </div>
            <div class="p-4 rounded-xl bg-green-50">
                <p class="text-2xl font-bold text-green-600"><?= $correctCount ?></p>
                <p class="text-xs text-green-400">Correct</p>
            </div>
            <div class="p-4 rounded-xl bg-red-50">
                <p class="text-2xl font-bold text-red-500"><?= $incorrectCount ?></p>
                <p class="text-xs text-red-400">Incorrect</p>
            </div>
        </div>

        <div class="w-full bg-gray-100 rounded-full h-4 mb-2">
            <div class="bg-green-500 h-4 rounded-full" style="width: <?= calcPercent($correctCount, $totalQ) ?>%"></div>
        </div>
        <p class="text-lg font-bold text-gray-900"><?= calcPercent($correctCount, $totalQ) ?>% Accuracy</p>
    </div>

    <div class="flex justify-center gap-3">
        <a href="<?= APP_URL ?>/listening.php?<?= http_build_query(array_merge(array_diff_key($_GET, ['q' => '', 'reset' => '']), ['reset' => 1])) ?>" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Try Again</a>
        <a href="<?= APP_URL ?>/review-mistakes.php?module=listening" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Review Mistakes</a>
        <a href="<?= APP_URL ?>/dashboard.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Dashboard</a>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
const audioEl = document.getElementById('audioPlayer');
const playBtn = document.getElementById('playBtn');
let listeningPlaying = false;

function playListeningAudio() {
    if (listeningPlaying) { stopListeningAudio(); return; }

    const hasFile = playBtn?.dataset.hasFile === '1';
    const ttsText = playBtn?.dataset.ttsText || '';

    listeningPlaying = true;
    document.getElementById('playText').textContent = 'Playing...';

    if (hasFile && audioEl) {
        audioEl.play();
        audioEl.onended = () => { resetPlayState(); };
        audioEl.onerror = () => {
            // Fallback to TTS if audio file fails
            if (ttsText) {
                KoreanTTS.speak(ttsText, {
                    type: 'browser_tts',
                    button: playBtn,
                    onEnd: resetPlayState,
                    onError: resetPlayState
                });
            } else { resetPlayState(); }
        };
    } else if (ttsText) {
        KoreanTTS.speak(ttsText, {
            type: 'browser_tts',
            onEnd: resetPlayState,
            onError: resetPlayState
        });
    } else {
        resetPlayState();
    }
}

function playListeningAudioSlow() {
    if (listeningPlaying) { stopListeningAudio(); }

    const hasFile = playBtn?.dataset.hasFile === '1';
    const ttsText = playBtn?.dataset.ttsText || '';

    listeningPlaying = true;
    document.getElementById('playText').textContent = 'Playing slow...';

    if (hasFile && audioEl) {
        audioEl.playbackRate = 0.6;
        audioEl.currentTime = 0;
        audioEl.play();
        audioEl.onended = () => { audioEl.playbackRate = 1.0; resetPlayState(); };
        audioEl.onerror = () => {
            audioEl.playbackRate = 1.0;
            if (ttsText) {
                KoreanTTS.speak(ttsText, { type: 'browser_tts', rate: 0.6, onEnd: resetPlayState, onError: resetPlayState });
            } else { resetPlayState(); }
        };
    } else if (ttsText) {
        KoreanTTS.speak(ttsText, { type: 'browser_tts', rate: 0.6, onEnd: resetPlayState, onError: resetPlayState });
    } else {
        resetPlayState();
    }
}

function replayListeningAudio() {
    stopListeningAudio();
    setTimeout(playListeningAudio, 100);
}

function stopListeningAudio() {
    if (audioEl) { audioEl.pause(); audioEl.currentTime = 0; }
    KoreanTTS.stop();
    resetPlayState();
}

function resetPlayState() {
    listeningPlaying = false;
    const txt = document.getElementById('playText');
    if (txt) txt.textContent = 'Play Again';
}

function toggleScript(btn) {
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
