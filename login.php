<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    header('Location: ' . APP_URL . ($role === 'admin' ? '/admin/dashboard.php' : '/dashboard.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email)) $errors[] = 'Email is required.';
        if (empty($password)) $errors[] = 'Password is required.';

        if (empty($errors)) {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                setUserSession($user);
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                    setcookie('remember_token', $token, time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
                }

                $redirect = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/dashboard.php';
                redirect(APP_URL . $redirect);
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+KR:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', 'Noto Sans KR', sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="min-h-full flex">
        <!-- Left: Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-700 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-20 left-20 text-[200px] font-bold text-white/20 select-none" style="font-family:'Noto Sans KR'">한</div>
                <div class="absolute bottom-20 right-20 text-[150px] font-bold text-white/20 select-none" style="font-family:'Noto Sans KR'">국</div>
            </div>
            <div class="relative z-10 flex flex-col justify-center px-16">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-white text-3xl font-bold mb-8" style="font-family:'Noto Sans KR'">한</div>
                <h1 class="text-4xl font-bold text-white mb-4">EPS Korean Trainer</h1>
                <p class="text-lg text-blue-100 mb-8 leading-relaxed">Your smart companion for EPS-TOPIK exam preparation. Study vocabulary, practice listening, master reading, and track your progress.</p>
                <div class="flex gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">500+</div>
                        <div class="text-xs text-blue-200">Vocabulary</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">200+</div>
                        <div class="text-xs text-blue-200">Questions</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">50+</div>
                        <div class="text-xs text-blue-200">Lessons</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">
                <!-- Mobile logo -->
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg" style="font-family:'Noto Sans KR'">한</div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">EPS Korean Trainer</h1>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h2>
                <p class="text-sm text-gray-500 mb-8">Sign in to continue your Korean learning journey</p>

                <?php if ($flash): ?>
                <div class="mb-4 px-4 py-3 rounded-xl border <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                    <p class="text-sm"><?= sanitize($flash['message']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm text-red-700"><?= sanitize($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <?= csrfField() ?>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                        <input type="email" name="email" value="<?= sanitize($_POST['email'] ?? '') ?>" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                            placeholder="you@example.com">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <a href="<?= APP_URL ?>/forgot-password.php" class="text-xs text-blue-600 hover:underline">Forgot password?</a>
                        </div>
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                            placeholder="Enter your password">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember me for 30 days</label>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-lg shadow-blue-600/20 text-sm">
                        Sign In
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-500">
                    Don't have an account? 
                    <a href="<?= APP_URL ?>/register.php" class="text-blue-600 font-semibold hover:underline">Sign up free</a>
                </p>

                <div class="mt-8 p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 mb-2">Demo Accounts:</p>
                    <div class="space-y-1 text-xs text-gray-500">
                        <p><span class="font-medium">Admin:</span> admin@epstrainer.com / password</p>
                        <p><span class="font-medium">Learner:</span> juan@example.com / password</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
