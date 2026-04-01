<?php
/**
 * Admin Layout Header
 */
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - Admin - ' : 'Admin - ' ?><?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans KR', sans-serif; }
        .sidebar-link.active { background: rgba(99, 102, 241, 0.1); color: #6366F1; border-right: 3px solid #6366F1; }
        .sidebar-link:hover { background: rgba(99, 102, 241, 0.05); }
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .toast { animation: slideIn 0.3s ease, fadeOut 0.3s ease 4.7s; }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes fadeOut { to { opacity: 0; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body class="h-full bg-gray-50">

<div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- Admin Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-50 h-full w-64 bg-slate-900 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">
    <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700/50">
        <div class="w-9 h-9 rounded-xl bg-indigo-500 flex items-center justify-center text-white font-bold text-sm">한</div>
        <div>
            <h1 class="text-sm font-bold text-white leading-tight">EPS Korean</h1>
            <p class="text-[10px] text-slate-400 font-medium">Admin Panel</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            Dashboard
        </a>

        <p class="px-3 pt-4 pb-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Manage</p>

        <a href="<?= APP_URL ?>/admin/users.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'users' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Users
        </a>
        <a href="<?= APP_URL ?>/admin/lessons.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'lessons' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Lessons
        </a>
        <a href="<?= APP_URL ?>/admin/vocabulary.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'vocabulary' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
            Vocabulary
        </a>
        <a href="<?= APP_URL ?>/admin/listening.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'listening' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
            Listening
        </a>
        <a href="<?= APP_URL ?>/admin/reading.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'reading' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reading
        </a>
        <a href="<?= APP_URL ?>/admin/quizzes.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'quizzes' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Quizzes
        </a>
        <a href="<?= APP_URL ?>/admin/mock-exams.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'mock-exams' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            Mock Exams
        </a>
        <a href="<?= APP_URL ?>/admin/categories.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'categories' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Categories
        </a>

        <p class="px-3 pt-4 pb-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Insights</p>

        <a href="<?= APP_URL ?>/admin/reports.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'reports' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Reports
        </a>

        <p class="px-3 pt-4 pb-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Settings</p>

        <a href="<?= APP_URL ?>/admin/voice-settings.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 <?= $currentPage === 'voice-settings' ? 'active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
            Voice / TTS
        </a>
    </nav>

    <div class="border-t border-slate-700/50 p-3">
        <div class="flex items-center gap-3 px-3 py-2">
            <div class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 font-semibold text-xs">
                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?= sanitize($currentUser['name']) ?></p>
                <p class="text-[11px] text-slate-400">Admin</p>
            </div>
            <a href="<?= APP_URL ?>/logout.php" class="p-1.5 rounded-lg hover:bg-slate-700 transition text-slate-400 hover:text-red-400" title="Logout">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</aside>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div class="flex items-center justify-between px-4 sm:px-6 py-3">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-800"><?= $pageTitle ?? 'Admin Dashboard' ?></h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= APP_URL ?>/dashboard.php" class="text-xs text-indigo-600 hover:underline font-medium">View Learner Site</a>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
    <div id="flash-toast" class="toast fixed top-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg border <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : ($flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-blue-50 border-blue-200 text-blue-800') ?>">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium"><?= sanitize($flash['message']) ?></span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    <script>setTimeout(() => { const t = document.getElementById('flash-toast'); if(t) t.remove(); }, 5000);</script>
    <?php endif; ?>

    <main class="flex-1 p-4 sm:p-6 fade-in">
