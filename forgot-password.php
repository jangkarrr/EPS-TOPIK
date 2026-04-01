<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
                $stmt->execute([$token, $expires, $email]);
            }
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="flex items-center gap-3 mb-8 justify-center">
            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg" style="font-family:'Noto Sans KR'">한</div>
            <h1 class="text-lg font-bold text-gray-900"><?= APP_NAME ?></h1>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Forgot your password?</h2>
            <p class="text-sm text-gray-500 mb-6">Enter your email and we'll send you a reset link.</p>

            <?php if ($success): ?>
            <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-200 mb-4">
                <p class="text-sm text-green-700">If an account with that email exists, a reset link has been generated. Check the URL: <code class="text-xs bg-green-100 px-1 py-0.5 rounded">reset-password.php?token=TOKEN</code></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200">
                <?php foreach ($errors as $e): ?><p class="text-sm text-red-700"><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm" placeholder="you@example.com">
                </div>
                <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm">Send Reset Link</button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                <a href="<?= APP_URL ?>/login.php" class="text-blue-600 font-semibold hover:underline">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>
