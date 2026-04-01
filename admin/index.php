<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';

if (isLoggedIn() && getCurrentUserRole() === 'admin') {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
