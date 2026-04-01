<?php
$pageTitle = 'Vocabulary Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories WHERE module = 'vocabulary' AND status = 'active' ORDER BY sort_order")->fetchAll();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $formAction = $_POST['form_action'] ?? '';
    
    if ($formAction === 'save') {
        $koreanWord = trim($_POST['korean_word'] ?? '');
        $transliteration = trim($_POST['transliteration'] ?? '');
        $englishMeaning = trim($_POST['english_meaning'] ?? '');
        $partOfSpeech = $_POST['part_of_speech'] ?? 'noun';
        $exampleKr = trim($_POST['example_sentence_kr'] ?? '');
        $exampleEn = trim($_POST['example_sentence_en'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $status = $_POST['status'] ?? 'active';

        $audioPath = null;
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $audioPath = 'audio/' . uploadFile($_FILES['audio_file'], AUDIO_DIR, ['mp3', 'wav', 'ogg']);
        }

        if ($editId) {
            $sql = "UPDATE vocabulary SET korean_word=?, transliteration=?, english_meaning=?, part_of_speech=?, example_sentence_kr=?, example_sentence_en=?, category_id=?, difficulty=?, status=?";
            $params = [$koreanWord, $transliteration, $englishMeaning, $partOfSpeech, $exampleKr, $exampleEn, $categoryId, $difficulty, $status];
            if ($audioPath) { $sql .= ", audio_path=?"; $params[] = $audioPath; }
            $sql .= " WHERE id=?";
            $params[] = $editId;
            $db->prepare($sql)->execute($params);
            setFlash('success', 'Vocabulary updated.');
        } else {
            $db->prepare("INSERT INTO vocabulary (korean_word, transliteration, english_meaning, part_of_speech, example_sentence_kr, example_sentence_en, audio_path, category_id, difficulty, status) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$koreanWord, $transliteration, $englishMeaning, $partOfSpeech, $exampleKr, $exampleEn, $audioPath, $categoryId, $difficulty, $status]);
            setFlash('success', 'Vocabulary added.');
        }
        redirect(APP_URL . '/admin/vocabulary.php');
    } elseif ($formAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) { $db->prepare("DELETE FROM vocabulary WHERE id = ?")->execute([$deleteId]); setFlash('success', 'Vocabulary deleted.'); }
        redirect(APP_URL . '/admin/vocabulary.php');
    }
}

$vocab = null;
if ($editId && $action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM vocabulary WHERE id = ?");
    $stmt->execute([$editId]);
    $vocab = $stmt->fetch();
    if (!$vocab) { redirect(APP_URL . '/admin/vocabulary.php', 'error', 'Not found.'); }
}

require_once __DIR__ . '/../includes/admin-header.php';

if ($action === 'add' || ($action === 'edit' && $vocab)):
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?= $vocab ? 'Edit Vocabulary' : 'Add Vocabulary' ?></h3>
        <a href="<?= APP_URL ?>/admin/vocabulary.php" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Korean Word *</label>
                <input type="text" name="korean_word" value="<?= sanitize($vocab['korean_word'] ?? '') ?>" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-lg korean-text font-bold focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Transliteration</label>
                <input type="text" name="transliteration" value="<?= sanitize($vocab['transliteration'] ?? '') ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">English Meaning *</label>
                <input type="text" name="english_meaning" value="<?= sanitize($vocab['english_meaning'] ?? '') ?>" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Part of Speech</label>
                <select name="part_of_speech" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <?php foreach (['noun','verb','adjective','adverb','phrase','expression','other'] as $pos): ?>
                        <option value="<?= $pos ?>" <?= ($vocab['part_of_speech'] ?? '') === $pos ? 'selected' : '' ?>><?= ucfirst($pos) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                <select name="category_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">No Category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($vocab['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $c['icon'] ?> <?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Difficulty</label>
                <select name="difficulty" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="beginner" <?= ($vocab['difficulty'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($vocab['difficulty'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($vocab['difficulty'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Example Sentence (Korean)</label>
            <input type="text" name="example_sentence_kr" value="<?= sanitize($vocab['example_sentence_kr'] ?? '') ?>"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm korean-text focus:border-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Example Sentence (English)</label>
            <input type="text" name="example_sentence_en" value="<?= sanitize($vocab['example_sentence_en'] ?? '') ?>"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-indigo-500 outline-none">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Audio File</label>
                <input type="file" name="audio_file" accept="audio/*" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                <?php if (!empty($vocab['audio_path'])): ?><p class="text-xs text-gray-400 mt-1">Current: <?= sanitize($vocab['audio_path']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="active" <?= ($vocab['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($vocab['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                <?= $vocab ? 'Update' : 'Create' ?>
            </button>
            <a href="<?= APP_URL ?>/admin/vocabulary.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= getCount('vocabulary') ?> total items</p>
    <a href="<?= APP_URL ?>/admin/vocabulary.php?action=add" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Vocabulary
    </a>
</div>

<?php
$search = $_GET['search'] ?? '';
$catFilter = $_GET['category'] ?? '';
$where = "1=1"; $params = [];
if ($search) { $where .= " AND (v.korean_word LIKE ? OR v.english_meaning LIKE ? OR v.transliteration LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= " AND v.category_id = ?"; $params[] = $catFilter; }

$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;
$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM vocabulary v WHERE $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT v.*, c.name as category_name, c.icon as category_icon FROM vocabulary v LEFT JOIN categories c ON v.category_id = c.id WHERE $where ORDER BY v.id DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset");
$stmt->execute($params);
$vocabItems = $stmt->fetchAll();
?>

<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search vocabulary..." class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
        <select name="category" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Search</button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Korean</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Romanization</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">English</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Category</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Level</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($vocabItems as $v): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5"><span class="korean-text text-base font-bold text-gray-900"><?= sanitize($v['korean_word']) ?></span></td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs"><?= sanitize($v['transliteration'] ?? '') ?></td>
                    <td class="px-5 py-3.5 font-medium text-gray-700"><?= sanitize($v['english_meaning']) ?></td>
                    <td class="px-5 py-3.5 text-xs"><?= $v['category_icon'] ?? '' ?> <?= sanitize($v['category_name'] ?? '-') ?></td>
                    <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md bg-gray-100 text-xs capitalize"><?= $v['difficulty'] ?></span></td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $v['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $v['status'] ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= APP_URL ?>/admin/vocabulary.php?action=edit&id=<?= $v['id'] ?>" class="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
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
    <?php if (empty($vocabItems)): ?><div class="p-8 text-center text-gray-400 text-sm">No vocabulary found.</div><?php endif; ?>
</div>

<?= paginate($totalItems, ITEMS_PER_PAGE, $page, APP_URL . '/admin/vocabulary.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))) ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
