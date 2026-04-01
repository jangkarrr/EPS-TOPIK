<?php
$pageTitle = 'Lesson';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();
$lessonId = (int)($_GET['id'] ?? 0);

if (!$lessonId) { redirect(APP_URL . '/lessons.php'); }

// Fetch lesson
$stmt = $db->prepare("SELECT l.*, c.name as category_name, c.icon as category_icon, c.color as category_color FROM lessons l LEFT JOIN categories c ON l.category_id = c.id WHERE l.id = ? AND l.status = 'published'");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();
if (!$lesson) { redirect(APP_URL . '/lessons.php', 'error', 'Lesson not found.'); }

$pageTitle = $lesson['title'];

// Check completion
$stmt = $db->prepare("SELECT id FROM lesson_completions WHERE user_id = ? AND lesson_id = ?");
$stmt->execute([$userId, $lessonId]);
$isCompleted = (bool)$stmt->fetch();

// Check bookmark
$stmt = $db->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND bookmarkable_type = 'lesson' AND bookmarkable_id = ?");
$stmt->execute([$userId, $lessonId]);
$isBookmarked = (bool)$stmt->fetch();

// Previous/Next
$stmt = $db->prepare("SELECT id, title FROM lessons WHERE status = 'published' AND (category_id = ? OR ? IS NULL) AND sort_order < ? ORDER BY sort_order DESC, id DESC LIMIT 1");
$stmt->execute([$lesson['category_id'], $lesson['category_id'], $lesson['sort_order'] ?: $lesson['id']]);
$prevLesson = $stmt->fetch();

$stmt = $db->prepare("SELECT id, title FROM lessons WHERE status = 'published' AND (category_id = ? OR ? IS NULL) AND sort_order > ? ORDER BY sort_order ASC, id ASC LIMIT 1");
$stmt->execute([$lesson['category_id'], $lesson['category_id'], $lesson['sort_order'] ?: $lesson['id']]);
$nextLesson = $stmt->fetch();

// Handle mark complete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        if ($action === 'complete' && !$isCompleted) {
            $stmt = $db->prepare("INSERT IGNORE INTO lesson_completions (user_id, lesson_id) VALUES (?, ?)");
            $stmt->execute([$userId, $lessonId]);
            logActivity($userId, 'lesson', 'Completed lesson: ' . $lesson['title'], $lessonId);
            
            // Update daily goal
            $today = date('Y-m-d');
            $stmt = $db->prepare("INSERT INTO daily_goals (user_id, goal_date, completed_lessons) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE completed_lessons = completed_lessons + 1");
            $stmt->execute([$userId, $today]);
            
            $isCompleted = true;
            setFlash('success', 'Lesson completed! Great job!');
        } elseif ($action === 'bookmark') {
            if ($isBookmarked) {
                $stmt = $db->prepare("DELETE FROM bookmarks WHERE user_id = ? AND bookmarkable_type = 'lesson' AND bookmarkable_id = ?");
                $stmt->execute([$userId, $lessonId]);
                $isBookmarked = false;
                setFlash('success', 'Bookmark removed.');
            } else {
                $stmt = $db->prepare("INSERT INTO bookmarks (user_id, bookmarkable_type, bookmarkable_id) VALUES (?, 'lesson', ?)");
                $stmt->execute([$userId, $lessonId]);
                $isBookmarked = true;
                setFlash('success', 'Lesson bookmarked!');
            }
        }
    }
    redirect(APP_URL . '/lesson-view.php?id=' . $lessonId);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
    <a href="<?= APP_URL ?>/lessons.php" class="hover:text-blue-600 transition">Lessons</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-gray-600 truncate"><?= sanitize($lesson['title']) ?></span>
</nav>

<div class="max-w-4xl mx-auto">
    <!-- Lesson Header -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-lg"
                          style="background: <?= $lesson['category_color'] ?>15; color: <?= $lesson['category_color'] ?>">
                        <span><?= $lesson['category_icon'] ?? '📖' ?></span>
                        <?= sanitize($lesson['category_name'] ?? 'General') ?>
                    </span>
                    <span class="px-2.5 py-1 text-xs rounded-lg bg-gray-50 text-gray-500 capitalize"><?= $lesson['difficulty'] ?></span>
                    <?php if ($isCompleted): ?>
                        <span class="px-2.5 py-1 text-xs rounded-lg bg-green-50 text-green-700 font-medium">Completed ✓</span>
                    <?php endif; ?>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?= sanitize($lesson['title']) ?></h1>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <form method="POST" class="inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="bookmark">
                    <button type="submit" class="p-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition" title="<?= $isBookmarked ? 'Remove bookmark' : 'Bookmark' ?>">
                        <svg class="w-5 h-5 <?= $isBookmarked ? 'text-amber-500 fill-amber-500' : 'text-gray-400' ?>" fill="<?= $isBookmarked ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                    </button>
                </form>
            </div>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-400">
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ~<?= $lesson['estimated_minutes'] ?> minutes
            </span>
        </div>
    </div>

    <!-- Voice Controls -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
                <span class="text-sm font-medium text-gray-700">AI Voice</span>
                <span class="text-xs text-gray-400">Click speaker icons to hear pronunciation</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">Speed:</span>
                <div id="lesson-speed-selector" class="flex items-center gap-1"></div>
            </div>
        </div>
    </div>

    <!-- Lesson Content -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
        <?php if ($lesson['summary']): ?>
        <div class="mb-5 p-4 rounded-xl bg-gray-50 border border-gray-100">
            <p class="text-sm text-gray-600"><?= sanitize($lesson['summary']) ?></p>
        </div>
        <?php endif; ?>

        <div id="lesson-content" class="prose prose-sm max-w-none prose-headings:text-gray-900 prose-p:text-gray-600 prose-li:text-gray-600">
            <?= $lesson['content'] ?>
        </div>

        <?php if ($lesson['tips']): ?>
        <div class="mt-6 p-5 rounded-xl bg-blue-50 border border-blue-100">
            <div class="flex items-start gap-3">
                <span class="text-lg">💡</span>
                <div>
                    <h4 class="text-sm font-semibold text-blue-800 mb-1">Study Tips</h4>
                    <p class="text-sm text-blue-700"><?= sanitize($lesson['tips']) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        KoreanTTS.createSpeedSelector('lesson-speed-selector');

        // Auto-attach speaker buttons to .vocab-item elements in lesson content
        document.querySelectorAll('#lesson-content .vocab-item').forEach(item => {
            const koreanEl = item.querySelector('.korean-large, .korean-xlarge, .korean-text');
            if (!koreanEl) return;
            const text = koreanEl.textContent.trim();
            if (!text) return;

            // Check if it contains Korean characters
            if (!/[\uAC00-\uD7AF\u1100-\u11FF\u3130-\u318F]/.test(text)) return;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tts-btn tts-idle inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 transition mt-1.5';
            btn.title = 'Listen';
            btn.dataset.ttsText = text;
            btn.dataset.ttsModule = 'lesson';
            btn.innerHTML = '<span class="tts-icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg></span>';
            item.appendChild(btn);
        });

        // Re-bind after dynamic buttons are added
        KoreanTTS.bindAll();
    });
    </script>

    <!-- Complete Button -->
    <?php if (!$isCompleted): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6 text-center">
        <p class="text-sm text-gray-500 mb-3">Finished reading this lesson?</p>
        <form method="POST" class="inline">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="complete">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition shadow-lg shadow-green-600/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Mark as Completed
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="flex items-center justify-between gap-4">
        <?php if ($prevLesson): ?>
        <a href="<?= APP_URL ?>/lesson-view.php?id=<?= $prevLesson['id'] ?>" class="flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition text-sm text-gray-600 max-w-[45%]">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span class="truncate"><?= sanitize($prevLesson['title']) ?></span>
        </a>
        <?php else: ?><div></div><?php endif; ?>

        <?php if ($nextLesson): ?>
        <a href="<?= APP_URL ?>/lesson-view.php?id=<?= $nextLesson['id'] ?>" class="flex items-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 rounded-xl transition text-sm text-white font-medium max-w-[45%]">
            <span class="truncate"><?= sanitize($nextLesson['title']) ?></span>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <?php else: ?><div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
