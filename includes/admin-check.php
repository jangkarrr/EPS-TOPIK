<?php
/**
 * Admin Guard - Require authenticated admin
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/session.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login to continue.');
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

if (getCurrentUserRole() !== 'admin') {
    setFlash('error', 'Access denied. Admin privileges required.');
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}
