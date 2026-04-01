<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-check.php';

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'toggle_status' && $targetId) {
        $stmt = $db->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ? AND id != ?");
        $stmt->execute([$targetId, getCurrentUserId()]);
        setFlash('success', 'User status updated.');
    } elseif ($action === 'delete' && $targetId) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND id != ? AND role != 'admin'");
        $stmt->execute([$targetId, getCurrentUserId()]);
        setFlash('success', 'User deleted.');
    }
    redirect(APP_URL . '/admin/users.php?' . http_build_query($_GET));
}

// Filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = "1=1";
$params = [];
if ($search) { $where .= " AND (full_name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleFilter) { $where .= " AND role = ?"; $params[] = $roleFilter; }
if ($statusFilter) { $where .= " AND status = ?"; $params[] = $statusFilter; }

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT u.*, (SELECT us.current_streak FROM user_streaks us WHERE us.user_id = u.id) as streak FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search by name or email..."
                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition text-sm">
        </div>
        <select name="role" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="">All Roles</option>
            <option value="learner" <?= $roleFilter === 'learner' ? 'selected' : '' ?>>Learner</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <select name="status" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
            <option value="">All Status</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Search</button>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">User</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Role</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Streak</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Joined</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500">Last Login</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-xs flex-shrink-0"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= sanitize($u['full_name']) ?></p>
                                <p class="text-xs text-gray-400"><?= sanitize($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-md text-xs font-medium capitalize <?= $u['role'] === 'admin' ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-600' ?>"><?= $u['role'] ?></span>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $u['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?>"><?= $u['status'] ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500"><?= $u['streak'] ?? 0 ?> 🔥</td>
                    <td class="px-5 py-3.5 text-gray-400 text-xs"><?= formatDate($u['created_at'], 'M d, Y') ?></td>
                    <td class="px-5 py-3.5 text-gray-400 text-xs"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
                    <td class="px-5 py-3.5 text-center">
                        <?php if ($u['id'] !== getCurrentUserId()): ?>
                        <div class="flex items-center justify-center gap-1">
                            <form method="POST" class="inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition" title="Toggle status">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </button>
                            </form>
                            <?php if ($u['role'] !== 'admin'): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span class="text-xs text-gray-300">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($users)): ?>
    <div class="p-8 text-center text-gray-400 text-sm">No users found.</div>
    <?php endif; ?>
</div>

<?= paginate($totalItems, $perPage, $page, APP_URL . '/admin/users.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))) ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
