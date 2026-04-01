<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || strlen($fullName) < 2) $errors[] = 'Full name must be at least 2 characters.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, 'learner', 'active')");
                $stmt->execute([$fullName, $email, $hashedPassword]);
                $userId = $db->lastInsertId();

                // Create profile
                $stmt = $db->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $stmt->execute([$userId]);

                // Create streak record
                $stmt = $db->prepare("INSERT INTO user_streaks (user_id, current_streak, longest_streak) VALUES (?, 0, 0)");
                $stmt->execute([$userId]);

                setFlash('success', 'Account created successfully! Please sign in.');
                redirect(APP_URL . '/login.php');
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
    <title>Register - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+KR:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', 'Noto Sans KR', sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="min-h-full flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="flex items-center gap-3 mb-8 justify-center">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg" style="font-family:'Noto Sans KR'">한</div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900"><?= APP_NAME ?></h1>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Create your account</h2>
                <p class="text-sm text-gray-500 mb-6">Start your EPS-TOPIK preparation journey</p>

                <?php if (!empty($errors)): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm text-red-700"><?= sanitize($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <?= csrfField() ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
                        <input type="text" name="full_name" value="<?= sanitize($_POST['full_name'] ?? '') ?>" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                            placeholder="Juan Dela Cruz">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                        <input type="email" name="email" value="<?= sanitize($_POST['email'] ?? '') ?>" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                            placeholder="you@example.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <input type="password" name="password" required minlength="6"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                            placeholder="At least 6 characters">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                            placeholder="Re-enter your password">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-lg shadow-blue-600/20 text-sm">
                        Create Account
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-500">
                    Already have an account? 
                    <a href="<?= APP_URL ?>/login.php" class="text-blue-600 font-semibold hover:underline">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
