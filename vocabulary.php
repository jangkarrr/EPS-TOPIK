<?php
$pageTitle = 'Vocabulary';
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
$search = $_GET['search'] ?? '';
$view = $_GET['view'] ?? 'cards';
$statusFilter = $_GET['status'] ?? '';

// Categories
$categories = $db->query("SELECT * FROM categories WHERE module = 'vocabulary' AND status = 'active' ORDER BY sort_order")->fetchAll();

// Build query
$where = "v.status = 'active'";
$params = [$userId];
if ($categoryFilter) { $where .= " AND v.category_id = ?"; $params[] = $categoryFilter; }
if ($difficultyFilter) { $where .= " AND v.difficulty = ?"; $params[] = $difficultyFilter; }
if ($search) { $where .= " AND (v.korean_word LIKE ? OR v.english_meaning LIKE ? OR v.transliteration LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($statusFilter === 'mastered') { $where .= " AND uvs.status = 'mastered'"; }
elseif ($statusFilter === 'hard') { $where .= " AND uvs.status = 'hard'"; }
elseif ($statusFilter === 'favorite') { $where .= " AND uvs.status = 'favorite'"; }
elseif ($statusFilter === 'learning') { $where .= " AND (uvs.status = 'learning' OR uvs.status IS NULL)"; }

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM vocabulary v LEFT JOIN user_vocabulary_status uvs ON v.id = uvs.vocabulary_id AND uvs.user_id = ? WHERE $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT v.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
    uvs.status as user_status, uvs.review_count
    FROM vocabulary v 
    LEFT JOIN categories c ON v.category_id = c.id 
    LEFT JOIN user_vocabulary_status uvs ON v.id = uvs.vocabulary_id AND uvs.user_id = ?
    WHERE $where ORDER BY v.id LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$vocabItems = $stmt->fetchAll();

// Stats
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN uvs.status = 'mastered' THEN 1 ELSE 0 END) as mastered,
    SUM(CASE WHEN uvs.status = 'hard' THEN 1 ELSE 0 END) as hard,
    SUM(CASE WHEN uvs.status = 'favorite' THEN 1 ELSE 0 END) as favorites
    FROM vocabulary v LEFT JOIN user_vocabulary_status uvs ON v.id = uvs.vocabulary_id AND uvs.user_id = ?
    WHERE v.status = 'active'");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        echo json_encode(['error' => 'Invalid token']); exit;
    }
    $vocabId = (int)($_POST['vocab_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $validStatuses = ['learning', 'mastered', 'hard', 'favorite'];
    
    if ($vocabId && in_array($newStatus, $validStatuses)) {
        $stmt = $db->prepare("INSERT INTO user_vocabulary_status (user_id, vocabulary_id, status, review_count, last_reviewed) 
            VALUES (?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE status = ?, review_count = review_count + 1, last_reviewed = NOW()");
        $stmt->execute([$userId, $vocabId, $newStatus, $newStatus]);
        
        if ($newStatus === 'mastered') {
            $today = date('Y-m-d');
            $stmt = $db->prepare("INSERT INTO daily_goals (user_id, goal_date, completed_words) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE completed_words = completed_words + 1");
            $stmt->execute([$userId, $today]);
        }
        
        logActivity($userId, 'vocabulary', "Marked word as $newStatus", $vocabId);
        setFlash('success', 'Status updated!');
    }
    redirect(APP_URL . '/vocabulary.php?' . http_build_query($_GET));
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stats Bar -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-gray-900"><?= $stats['total'] ?></p>
        <p class="text-xs text-gray-400">Total Words</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-green-600"><?= $stats['mastered'] ?? 0 ?></p>
        <p class="text-xs text-gray-400">Mastered</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-red-500"><?= $stats['hard'] ?? 0 ?></p>
        <p class="text-xs text-gray-400">Hard Words</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <p class="text-xl font-bold text-amber-500"><?= $stats['favorites'] ?? 0 ?></p>
        <p class="text-xs text-gray-400">Favorites</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="view" value="<?= sanitize($view) ?>">
        <div class="flex-1">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search Korean, English, or romanization..." 
                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
        </div>
        <select name="category" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
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
        <select name="status" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="">All Status</option>
            <option value="learning" <?= $statusFilter === 'learning' ? 'selected' : '' ?>>Learning</option>
            <option value="mastered" <?= $statusFilter === 'mastered' ? 'selected' : '' ?>>Mastered</option>
            <option value="hard" <?= $statusFilter === 'hard' ? 'selected' : '' ?>>Hard</option>
            <option value="favorite" <?= $statusFilter === 'favorite' ? 'selected' : '' ?>>Favorites</option>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium transition hover:bg-blue-700">Filter</button>
    </form>
</div>

<!-- View Toggle & Actions -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'cards'])) ?>" class="p-2 rounded-lg <?= $view === 'cards' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:bg-gray-100' ?> transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'list'])) ?>" class="p-2 rounded-lg <?= $view === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:bg-gray-100' ?> transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'flashcard'])) ?>" class="p-2 rounded-lg <?= $view === 'flashcard' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:bg-gray-100' ?> transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </a>
    </div>
    <p class="text-xs text-gray-400"><?= $totalItems ?> words found</p>
</div>

<?php if (empty($vocabItems)): ?>
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10"/></svg>
    </div>
    <p class="text-gray-400 text-sm">No vocabulary found. Try adjusting your filters.</p>
</div>

<?php elseif ($view === 'flashcard'): ?>
<!-- Flashcard View -->
<div class="max-w-lg mx-auto">
    <div id="flashcard-container">
        <div id="flashcard" onclick="flipCard()" class="cursor-pointer bg-white rounded-2xl border-2 border-gray-100 hover:border-blue-200 p-10 text-center min-h-[300px] flex flex-col items-center justify-center transition-all shadow-lg">
            <div id="card-front">
                <p class="korean-text text-4xl font-bold text-gray-900 mb-3" id="fc-korean"></p>
                <p class="text-sm text-gray-400" id="fc-romanize"></p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <button type="button" id="fc-hear-btn" onclick="event.stopPropagation(); KoreanTTS.speak(vocabData[currentIndex]?.korean_word || '', {button: this, rate: KoreanTTS.config.defaultRate})" class="tts-btn tts-idle inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium bg-blue-50 hover:bg-blue-100 text-blue-600 transition">
                        <span class="tts-icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg></span>
                        <span class="tts-label" data-default-label="Hear">Hear</span>
                    </button>
                    <button type="button" onclick="event.stopPropagation(); KoreanTTS.speak(vocabData[currentIndex]?.korean_word || '', {rate: 0.6, button: this})" class="tts-btn tts-idle inline-flex items-center gap-1 px-2.5 py-2 rounded-xl text-xs font-medium bg-amber-50 hover:bg-amber-100 text-amber-600 transition">
                        <span class="tts-icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                        <span class="tts-label" data-default-label="Slow">Slow</span>
                    </button>
                </div>
                <p class="text-xs text-gray-300 mt-3">Click card to reveal answer</p>
            </div>
            <div id="card-back" class="hidden">
                <p class="korean-text text-3xl font-bold text-gray-900 mb-2" id="fc-korean-back"></p>
                <p class="text-lg font-semibold text-blue-600 mb-1" id="fc-english"></p>
                <p class="text-sm text-gray-500 mb-1" id="fc-pos"></p>
                <p class="text-sm text-gray-400 italic mt-3" id="fc-example-kr"></p>
                <p class="text-xs text-gray-400" id="fc-example-en"></p>
                <button type="button" id="fc-hear-sentence" onclick="event.stopPropagation(); const s = vocabData[currentIndex]?.example_sentence_kr; if(s) KoreanTTS.speak(s, {button: this});" class="tts-btn tts-idle inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium bg-blue-50 hover:bg-blue-100 text-blue-600 transition mt-3">
                    <span class="tts-icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg></span>
                    <span class="tts-label" data-default-label="Hear sentence">Hear sentence</span>
                </button>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3 mt-6">
            <form method="POST" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="vocab_id" id="fc-vocab-id" value="">
                <button type="submit" name="new_status" value="hard" class="px-4 py-2.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium transition">Hard</button>
            </form>
            <button onclick="nextCard()" class="px-6 py-2.5 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 text-sm font-medium transition">Skip</button>
            <form method="POST" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="vocab_id" id="fc-vocab-id2" value="">
                <button type="submit" name="new_status" value="mastered" class="px-4 py-2.5 rounded-xl bg-green-50 text-green-600 hover:bg-green-100 text-sm font-medium transition">Mastered</button>
            </form>
        </div>
        <p class="text-center text-xs text-gray-400 mt-3"><span id="fc-counter">1</span> / <?= count($vocabItems) ?></p>
    </div>
</div>

<script>
const vocabData = <?= json_encode(array_values($vocabItems)) ?>;
let currentIndex = 0;
let flipped = false;

function showCard(index) {
    if (index >= vocabData.length) { currentIndex = 0; index = 0; }
    const item = vocabData[index];
    document.getElementById('fc-korean').textContent = item.korean_word;
    document.getElementById('fc-romanize').textContent = item.transliteration || '';
    document.getElementById('fc-korean-back').textContent = item.korean_word;
    document.getElementById('fc-english').textContent = item.english_meaning;
    document.getElementById('fc-pos').textContent = item.part_of_speech;
    document.getElementById('fc-example-kr').textContent = item.example_sentence_kr || '';
    document.getElementById('fc-example-en').textContent = item.example_sentence_en || '';
    document.getElementById('fc-vocab-id').value = item.id;
    document.getElementById('fc-vocab-id2').value = item.id;
    document.getElementById('fc-counter').textContent = index + 1;
    document.getElementById('card-front').classList.remove('hidden');
    document.getElementById('card-back').classList.add('hidden');
    flipped = false;
}

function flipCard() {
    flipped = !flipped;
    document.getElementById('card-front').classList.toggle('hidden');
    document.getElementById('card-back').classList.toggle('hidden');
}

function nextCard() { currentIndex++; showCard(currentIndex); }

showCard(0);

document.addEventListener('keydown', (e) => {
    if (e.code === 'Space') { e.preventDefault(); flipCard(); }
    if (e.code === 'ArrowRight') { nextCard(); }
});
</script>

<?php elseif ($view === 'list'): ?>
<!-- List View -->
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Korean</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Romanization</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">English</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Type</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($vocabItems as $v): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5">
                        <span class="korean-text text-base font-bold text-gray-900"><?= sanitize($v['korean_word']) ?></span>
                        <?= speakerBtnInline($v['korean_word'], ['module' => 'vocabulary', 'itemId' => $v['id'], 'audioUrl' => $v['audio_path'] ? APP_URL . '/uploads/' . $v['audio_path'] : '']) ?>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500"><?= sanitize($v['transliteration'] ?? '') ?></td>
                    <td class="px-5 py-3.5 font-medium text-gray-700"><?= sanitize($v['english_meaning']) ?></td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-500 text-xs capitalize"><?= $v['part_of_speech'] ?></span></td>
                    <td class="px-5 py-3.5">
                        <?php
                        $us = $v['user_status'] ?? 'new';
                        $statusColors = ['mastered' => 'bg-green-50 text-green-700', 'hard' => 'bg-red-50 text-red-700', 'favorite' => 'bg-amber-50 text-amber-700', 'learning' => 'bg-blue-50 text-blue-700'];
                        ?>
                        <span class="px-2 py-0.5 rounded-md text-xs font-medium capitalize <?= $statusColors[$us] ?? 'bg-gray-50 text-gray-500' ?>"><?= $us ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <form method="POST" class="inline-flex gap-1">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="vocab_id" value="<?= $v['id'] ?>">
                            <button type="submit" name="new_status" value="mastered" class="p-1 rounded hover:bg-green-50 text-gray-400 hover:text-green-600 transition" title="Mark mastered">✓</button>
                            <button type="submit" name="new_status" value="hard" class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Mark hard">✗</button>
                            <button type="submit" name="new_status" value="favorite" class="p-1 rounded hover:bg-amber-50 text-gray-400 hover:text-amber-600 transition" title="Favorite">★</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Card View (default) -->
<div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($vocabItems as $v): ?>
    <div class="bg-white rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50 transition-all p-5 group">
        <div class="flex items-start justify-between mb-3">
            <span class="text-xs font-medium px-2 py-0.5 rounded-lg" style="background: <?= $v['category_color'] ?? '#3B82F6' ?>15; color: <?= $v['category_color'] ?? '#3B82F6' ?>"><?= sanitize($v['category_name'] ?? 'General') ?></span>
            <?php
            $us = $v['user_status'] ?? '';
            if ($us === 'mastered') echo '<span class="text-green-500 text-xs">✓ Mastered</span>';
            elseif ($us === 'hard') echo '<span class="text-red-500 text-xs">Hard</span>';
            elseif ($us === 'favorite') echo '<span class="text-amber-500 text-xs">★</span>';
            ?>
        </div>
        <div class="flex items-center gap-2 mb-1">
            <p class="korean-text text-2xl font-bold text-gray-900"><?= sanitize($v['korean_word']) ?></p>
            <?= speakerBtnInline($v['korean_word'], ['module' => 'vocabulary', 'itemId' => $v['id'], 'audioUrl' => $v['audio_path'] ? APP_URL . '/uploads/' . $v['audio_path'] : '']) ?>
        </div>
        <p class="text-xs text-gray-400 mb-2"><?= sanitize($v['transliteration'] ?? '') ?></p>
        <p class="text-sm font-medium text-blue-600 mb-1"><?= sanitize($v['english_meaning']) ?></p>
        <p class="text-xs text-gray-400 capitalize mb-3"><?= $v['part_of_speech'] ?></p>
        <?php if ($v['example_sentence_kr']): ?>
        <div class="pt-3 border-t border-gray-50">
            <div class="flex items-center gap-1">
                <p class="text-xs text-gray-500 korean-text flex-1"><?= sanitize($v['example_sentence_kr']) ?></p>
                <?= speakerBtnInline($v['example_sentence_kr'], ['module' => 'vocabulary', 'itemId' => $v['id']]) ?>
            </div>
            <p class="text-xs text-gray-400"><?= sanitize($v['example_sentence_en'] ?? '') ?></p>
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-1 mt-3 pt-3 border-t border-gray-50">
            <form method="POST" class="flex gap-1 w-full">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="vocab_id" value="<?= $v['id'] ?>">
                <button type="submit" name="new_status" value="mastered" class="flex-1 py-1.5 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 text-xs font-medium transition">Mastered</button>
                <button type="submit" name="new_status" value="hard" class="flex-1 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium transition">Hard</button>
                <button type="submit" name="new_status" value="favorite" class="py-1.5 px-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 text-xs transition">★</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Pagination -->
<?= paginate($totalItems, $perPage, $page, APP_URL . '/vocabulary.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))) ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
