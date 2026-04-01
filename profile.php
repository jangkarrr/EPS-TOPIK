<?php
$pageTitle = 'Profile & Settings';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$db = getDB();

$stmt = $db->prepare("SELECT u.*, up.daily_target, up.learning_level, up.preferred_study_mode, up.sound_enabled, up.dark_mode, up.notification_enabled FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $dailyTarget = max(1, (int)($_POST['daily_target'] ?? 20));
            $level = $_POST['learning_level'] ?? 'beginner';
            $studyMode = $_POST['preferred_study_mode'] ?? 'flashcard';
            $sound = isset($_POST['sound_enabled']) ? 1 : 0;
            $notifications = isset($_POST['notification_enabled']) ? 1 : 0;

            if (empty($fullName)) $errors[] = 'Name is required.';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

            // Check email uniqueness
            if (empty($errors) && $email !== $user['email']) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) $errors[] = 'Email already in use.';
            }

            if (empty($errors)) {
                // Handle profile image upload
                $profileImage = $user['profile_image'];
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $uploaded = uploadFile($_FILES['profile_image'], PROFILE_DIR, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    if ($uploaded) $profileImage = $uploaded;
                }

                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $profileImage, $userId]);

                $stmt = $db->prepare("UPDATE user_profiles SET daily_target = ?, learning_level = ?, preferred_study_mode = ?, sound_enabled = ?, notification_enabled = ? WHERE user_id = ?");
                $stmt->execute([$dailyTarget, $level, $studyMode, $sound, $notifications, $userId]);

                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_avatar'] = $profileImage;

                setFlash('success', 'Profile updated successfully!');
                redirect(APP_URL . '/profile.php');
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPass, $user['password'])) $errors[] = 'Current password is incorrect.';
            if (strlen($newPass) < 6) $errors[] = 'New password must be at least 6 characters.';
            if ($newPass !== $confirmPass) $errors[] = 'Passwords do not match.';

            if (empty($errors)) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $userId]);
                setFlash('success', 'Password changed successfully!');
                redirect(APP_URL . '/profile.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($errors)): ?>
<div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200">
    <?php foreach ($errors as $e): ?><p class="text-sm text-red-700"><?= sanitize($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="max-w-3xl mx-auto">
    <!-- Profile Card -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
            <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xl flex-shrink-0">
                <?php if ($user['profile_image']): ?>
                    <img src="<?= APP_URL ?>/uploads/profiles/<?= sanitize($user['profile_image']) ?>" class="w-16 h-16 rounded-2xl object-cover" alt="Profile">
                <?php else: ?>
                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900"><?= sanitize($user['full_name']) ?></h2>
                <p class="text-sm text-gray-500"><?= sanitize($user['email']) ?></p>
                <p class="text-xs text-gray-400">Member since <?= formatDate($user['created_at']) ?></p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" name="full_name" value="<?= sanitize($user['full_name']) ?>" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= sanitize($user['email']) ?>" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Profile Image</label>
                <input type="file" name="profile_image" accept="image/*"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Daily Target (words)</label>
                    <input type="number" name="daily_target" value="<?= $user['daily_target'] ?? 20 ?>" min="1" max="100"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 outline-none transition text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Learning Level</label>
                    <select name="learning_level" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm bg-white">
                        <option value="beginner" <?= ($user['learning_level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                        <option value="intermediate" <?= ($user['learning_level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                        <option value="advanced" <?= ($user['learning_level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Preferred Study Mode</label>
                    <select name="preferred_study_mode" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm bg-white">
                        <option value="flashcard" <?= ($user['preferred_study_mode'] ?? '') === 'flashcard' ? 'selected' : '' ?>>Flashcards</option>
                        <option value="list" <?= ($user['preferred_study_mode'] ?? '') === 'list' ? 'selected' : '' ?>>List View</option>
                        <option value="quiz" <?= ($user['preferred_study_mode'] ?? '') === 'quiz' ? 'selected' : '' ?>>Quiz Mode</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="sound_enabled" <?= ($user['sound_enabled'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Sound effects</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="notification_enabled" <?= ($user['notification_enabled'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Notifications</span>
                </label>
            </div>

            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Change Password</h3>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 outline-none transition text-sm">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 outline-none transition text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 outline-none transition text-sm">
                </div>
            </div>
            <button type="submit" class="px-6 py-3 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-xl transition text-sm">
                Change Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
