<?php
/**
 * Flashcard API Endpoint
 * Handles all flashcard CRUD operations via AJAX
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Auth check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = getCurrentUserId();
$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        // ─── LIST FLASHCARDS ──────────────────────────────
        case 'list':
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'newest';
            $status = $_GET['status'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 24)));
            $offset = ($page - 1) * $perPage;

            $where = "f.user_id = ?";
            $params = [$userId];

            $deckId = $_GET['deck_id'] ?? '';
            if ($deckId !== '') {
                if ($deckId === 'null') {
                    $where .= " AND f.deck_id IS NULL";
                } else {
                    $where .= " AND f.deck_id = ?";
                    $params[] = (int)$deckId;
                }
            }

            if ($search) {
                $where .= " AND (f.term LIKE ? OR f.definition LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status && in_array($status, ['new', 'known', 'review'])) {
                $where .= " AND f.status = ?";
                $params[] = $status;
            }

            // Count
            $countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM flashcards f WHERE $where");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetch()['cnt'];

            // Sort
            $orderBy = match($sort) {
                'oldest' => 'f.created_at ASC',
                'alpha_asc' => 'f.term ASC',
                'alpha_desc' => 'f.term DESC',
                'status' => 'f.status ASC, f.term ASC',
                default => 'f.created_at DESC',
            };

            $stmt = $db->prepare("SELECT f.*, fd.name as deck_name, fd.color as deck_color 
                FROM flashcards f 
                LEFT JOIN flashcard_decks fd ON fd.id = f.deck_id 
                WHERE $where 
                ORDER BY $orderBy 
                LIMIT $perPage OFFSET $offset");
            $stmt->execute($params);
            $cards = $stmt->fetchAll();

            // Add image URLs
            foreach ($cards as &$card) {
                if ($card['image_path']) {
                    $card['image_url'] = APP_URL . '/uploads/flashcards/' . $card['image_path'];
                }
            }

            echo json_encode([
                'success' => true,
                'cards' => $cards,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
            ]);
            break;

        // ─── GET SINGLE FLASHCARD ─────────────────────────
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM flashcards WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $card = $stmt->fetch();

            if (!$card) {
                http_response_code(404);
                echo json_encode(['error' => 'Flashcard not found']);
                break;
            }

            if ($card['image_path']) {
                $card['image_url'] = APP_URL . '/uploads/flashcards/' . $card['image_path'];
            }

            echo json_encode(['success' => true, 'card' => $card]);
            break;

        // ─── CREATE FLASHCARD ─────────────────────────────
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $term = trim($_POST['term'] ?? '');
            $definition = trim($_POST['definition'] ?? '');

            if (!$term || !$definition) {
                http_response_code(422);
                echo json_encode(['error' => 'Term and Definition are required']);
                break;
            }

            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = uploadFile(
                    $_FILES['image'],
                    FLASHCARD_DIR,
                    ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
                );
            }

            $deckId = isset($_POST['deck_id']) && $_POST['deck_id'] !== '' ? (int)$_POST['deck_id'] : null;
            if ($deckId) {
                $check = $db->prepare("SELECT id FROM flashcard_decks WHERE id = ? AND user_id = ?");
                $check->execute([$deckId, $userId]);
                if (!$check->fetch()) {
                    $deckId = null;
                }
            }

            $stmt = $db->prepare("INSERT INTO flashcards (user_id, deck_id, term, definition, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $deckId, $term, $definition, $imagePath]);
            $newId = $db->lastInsertId();

            // Auto-create default deck if none exists
            $deckCheck = $db->prepare("SELECT id FROM flashcard_decks WHERE user_id = ?");
            $deckCheck->execute([$userId]);
            if (!$deckCheck->fetch()) {
                $db->prepare("INSERT INTO flashcard_decks (user_id, name) VALUES (?, 'My Flashcards')")->execute([$userId]);
            }

            logActivity($userId, 'flashcard_create', "Created flashcard: $term", $newId);

            $stmt = $db->prepare("SELECT * FROM flashcards WHERE id = ?");
            $stmt->execute([$newId]);
            $card = $stmt->fetch();
            if ($card['image_path']) {
                $card['image_url'] = APP_URL . '/uploads/flashcards/' . $card['image_path'];
            }

            echo json_encode(['success' => true, 'card' => $card, 'message' => 'Flashcard created successfully']);
            break;

        // ─── UPDATE FLASHCARD ─────────────────────────────
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);
            $term = trim($_POST['term'] ?? '');
            $definition = trim($_POST['definition'] ?? '');

            if (!$id || !$term || !$definition) {
                http_response_code(422);
                echo json_encode(['error' => 'ID, Term and Definition are required']);
                break;
            }

            // Verify ownership
            $stmt = $db->prepare("SELECT * FROM flashcards WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => 'Flashcard not found']);
                break;
            }

            $imagePath = $existing['image_path'];

            // Handle image removal
            if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                if ($imagePath && file_exists(FLASHCARD_DIR . $imagePath)) {
                    unlink(FLASHCARD_DIR . $imagePath);
                }
                $imagePath = null;
            }

            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Remove old image
                if ($existing['image_path'] && file_exists(FLASHCARD_DIR . $existing['image_path'])) {
                    unlink(FLASHCARD_DIR . $existing['image_path']);
                }
                $imagePath = uploadFile(
                    $_FILES['image'],
                    FLASHCARD_DIR,
                    ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
                );
            }

            $deckId = isset($_POST['deck_id']) && $_POST['deck_id'] !== '' ? (int)$_POST['deck_id'] : null;
            if ($deckId) {
                $check = $db->prepare("SELECT id FROM flashcard_decks WHERE id = ? AND user_id = ?");
                $check->execute([$deckId, $userId]);
                if (!$check->fetch()) {
                    $deckId = null;
                }
            }

            $stmt = $db->prepare("UPDATE flashcards SET deck_id = ?, term = ?, definition = ?, image_path = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$deckId, $term, $definition, $imagePath, $id, $userId]);

            $stmt = $db->prepare("SELECT * FROM flashcards WHERE id = ?");
            $stmt->execute([$id]);
            $card = $stmt->fetch();
            if ($card['image_path']) {
                $card['image_url'] = APP_URL . '/uploads/flashcards/' . $card['image_path'];
            }

            echo json_encode(['success' => true, 'card' => $card, 'message' => 'Flashcard updated successfully']);
            break;

        // ─── DELETE FLASHCARD ─────────────────────────────
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            // Get card for image cleanup
            $stmt = $db->prepare("SELECT image_path FROM flashcards WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $card = $stmt->fetch();

            if (!$card) {
                http_response_code(404);
                echo json_encode(['error' => 'Flashcard not found']);
                break;
            }

            // Delete image file
            if ($card['image_path'] && file_exists(FLASHCARD_DIR . $card['image_path'])) {
                unlink(FLASHCARD_DIR . $card['image_path']);
            }

            $stmt = $db->prepare("DELETE FROM flashcards WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            echo json_encode(['success' => true, 'message' => 'Flashcard deleted successfully']);
            break;

        // ─── BULK DELETE ──────────────────────────────────
        case 'bulk_delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (empty($ids) || !is_array($ids)) {
                http_response_code(422);
                echo json_encode(['error' => 'No cards selected']);
                break;
            }

            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Get images for cleanup
            $stmt = $db->prepare("SELECT image_path FROM flashcards WHERE id IN ($placeholders) AND user_id = ?");
            $stmt->execute([...$ids, $userId]);
            $images = $stmt->fetchAll();

            foreach ($images as $img) {
                if ($img['image_path'] && file_exists(FLASHCARD_DIR . $img['image_path'])) {
                    unlink(FLASHCARD_DIR . $img['image_path']);
                }
            }

            $stmt = $db->prepare("DELETE FROM flashcards WHERE id IN ($placeholders) AND user_id = ?");
            $stmt->execute([...$ids, $userId]);
            $deleted = $stmt->rowCount();

            echo json_encode(['success' => true, 'deleted' => $deleted, 'message' => "$deleted flashcard(s) deleted"]);
            break;

        // ─── IMPORT FLASHCARDS ────────────────────────────
        case 'import':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $cardsData = json_decode($_POST['cards'] ?? '[]', true);
            if (empty($cardsData) || !is_array($cardsData)) {
                http_response_code(422);
                echo json_encode(['error' => 'No valid cards to import']);
                break;
            }

            $imported = 0;
            $skipped = 0;
            $stmt = $db->prepare("INSERT INTO flashcards (user_id, term, definition, image_path) VALUES (?, ?, ?, ?)");

            foreach ($cardsData as $card) {
                $term = trim($card['term'] ?? '');
                $definition = trim($card['definition'] ?? '');
                $image = trim($card['image'] ?? '') ?: null;

                if (!$term || !$definition) {
                    $skipped++;
                    continue;
                }

                $stmt->execute([$userId, $term, $definition, $image]);
                $imported++;
            }

            if ($imported > 0) {
                // Auto-create default deck if none exists
                $deckCheck = $db->prepare("SELECT id FROM flashcard_decks WHERE user_id = ?");
                $deckCheck->execute([$userId]);
                if (!$deckCheck->fetch()) {
                    $db->prepare("INSERT INTO flashcard_decks (user_id, name) VALUES (?, 'My Flashcards')")->execute([$userId]);
                }
                logActivity($userId, 'flashcard_import', "Imported $imported flashcards");
            }

            echo json_encode([
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => "$imported flashcard(s) imported successfully" . ($skipped > 0 ? " ($skipped skipped)" : ""),
            ]);
            break;

        // ─── UPDATE STATUS ────────────────────────────────
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';

            if (!$id || !in_array($newStatus, ['new', 'known', 'review'])) {
                http_response_code(422);
                echo json_encode(['error' => 'Invalid card or status']);
                break;
            }

            $stmt = $db->prepare("UPDATE flashcards SET status = ?, review_count = review_count + 1, last_reviewed = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$newStatus, $id, $userId]);

            echo json_encode(['success' => true, 'message' => 'Status updated']);
            break;

        // ─── EXPORT FLASHCARDS ────────────────────────────
        case 'export':
            $ids = $_GET['ids'] ?? '';
            $where = "user_id = ?";
            $params = [$userId];

            if ($ids) {
                $idArr = array_map('intval', explode(',', $ids));
                $placeholders = implode(',', array_fill(0, count($idArr), '?'));
                $where .= " AND id IN ($placeholders)";
                $params = array_merge($params, $idArr);
            }

            $stmt = $db->prepare("SELECT term, definition, image_path FROM flashcards WHERE $where ORDER BY created_at DESC");
            $stmt->execute($params);
            $cards = $stmt->fetchAll();

            echo json_encode(['success' => true, 'cards' => $cards]);
            break;

        // ─── STATS ────────────────────────────────────────
        case 'stats':
            $deckId = $_GET['deck_id'] ?? '';
            $where = "user_id = ?";
            $params = [$userId];
            if ($deckId !== '') {
                if ($deckId === 'null') {
                    $where .= " AND deck_id IS NULL";
                } else {
                    $where .= " AND deck_id = ?";
                    $params[] = (int)$deckId;
                }
            }

            $stmt = $db->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status = 'known' THEN 1 ELSE 0 END) as known_count,
                SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as review_count
                FROM flashcards WHERE $where");
            $stmt->execute($params);
            $stats = $stmt->fetch();

            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        // ─── GET ALL FOR STUDY ────────────────────────────
        case 'study_cards':
            $status = $_GET['filter'] ?? '';
            $where = "user_id = ?";
            $params = [$userId];

            $deckId = $_GET['deck_id'] ?? '';
            if ($deckId !== '') {
                if ($deckId === 'null') {
                    $where .= " AND deck_id IS NULL";
                } else {
                    $where .= " AND deck_id = ?";
                    $params[] = (int)$deckId;
                }
            }

            if ($status && in_array($status, ['new', 'known', 'review'])) {
                $where .= " AND status = ?";
                $params[] = $status;
            }

            $stmt = $db->prepare("SELECT * FROM flashcards WHERE $where ORDER BY created_at ASC");
            $stmt->execute($params);
            $cards = $stmt->fetchAll();

            foreach ($cards as &$card) {
                if ($card['image_path']) {
                    $card['image_url'] = APP_URL . '/uploads/flashcards/' . $card['image_path'];
                }
            }

            echo json_encode(['success' => true, 'cards' => $cards]);
            break;

        // ─── LIST FOLDERS (DECKS) ─────────────────────────
        case 'list_folders':
            $stmt = $db->prepare("SELECT fd.*, COUNT(f.id) as card_count 
                FROM flashcard_decks fd 
                LEFT JOIN flashcards f ON f.deck_id = fd.id 
                WHERE fd.user_id = ? 
                GROUP BY fd.id 
                ORDER BY fd.name ASC");
            $stmt->execute([$userId]);
            $folders = $stmt->fetchAll();

            echo json_encode(['success' => true, 'folders' => $folders]);
            break;

        // ─── CREATE FOLDER (DECK) ─────────────────────────
        case 'create_folder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $color = trim($_POST['color'] ?? '#3B82F6');

            if (!$name) {
                http_response_code(422);
                echo json_encode(['error' => 'Folder name is required']);
                break;
            }

            $stmt = $db->prepare("INSERT INTO flashcard_decks (user_id, name, description, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $description, $color]);
            $newId = $db->lastInsertId();

            $stmt = $db->prepare("SELECT * FROM flashcard_decks WHERE id = ?");
            $stmt->execute([$newId]);
            $folder = $stmt->fetch();

            echo json_encode(['success' => true, 'folder' => $folder, 'message' => 'Folder created successfully']);
            break;

        // ─── UPDATE FOLDER (DECK) ─────────────────────────
        case 'update_folder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $color = trim($_POST['color'] ?? '#3B82F6');

            if (!$id || !$name) {
                http_response_code(422);
                echo json_encode(['error' => 'ID and Folder name are required']);
                break;
            }

            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM flashcard_decks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Folder not found']);
                break;
            }

            $stmt = $db->prepare("UPDATE flashcard_decks SET name = ?, description = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $description, $color, $id, $userId]);

            $stmt = $db->prepare("SELECT * FROM flashcard_decks WHERE id = ?");
            $stmt->execute([$id]);
            $folder = $stmt->fetch();

            echo json_encode(['success' => true, 'folder' => $folder, 'message' => 'Folder updated successfully']);
            break;

        // ─── DELETE FOLDER (DECK) ─────────────────────────
        case 'delete_folder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM flashcard_decks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Folder not found']);
                break;
            }

            // Delete deck (cards will have deck_id set to NULL due to ON DELETE SET NULL constraint)
            $stmt = $db->prepare("DELETE FROM flashcard_decks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            echo json_encode(['success' => true, 'message' => 'Folder deleted successfully']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Flashcard API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
}
