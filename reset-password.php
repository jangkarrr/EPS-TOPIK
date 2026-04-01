<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$token = $_GET['token'] ?? '';
$errors = [];
$valid = false;

if (!empty($token)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $valid = (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        
        if (empty($errors)) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                $stmt->execute([$hashed, $user['id']]);
                setFlash('success', 'Password reset successful! Please sign in.');
                redirect(APP_URL . '/login.php');
            } else {
                $errors[] = 'Invalid or expired reset token.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Reset your password</h2>
            <p class="text-sm text-gray-500 mb-6">Enter your new password below.</p>

            <?php if (!$valid && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200">
                <p class="text-sm text-red-700">Invalid or expired reset link. Please request a new one.</p>
            </div>
            <p class="mt-4 text-center"><a href="<?= APP_URL ?>/forgot-password.php" class="text-blue-600 font-semibold hover:underline text-sm">Request new link</a></p>
            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200">
                <?php foreach ($errors as $e): ?><p class="text-sm text-red-700"><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= sanitize($token) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm" placeholder="At least 6 characters">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm new password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm" placeholder="Re-enter your password">
                </div>
                <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm">Reset Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
