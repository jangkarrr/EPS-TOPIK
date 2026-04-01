<?php
/**
 * Auth Guard - Require authenticated learner
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/session.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login to continue.');
    header('Location: ' . APP_URL . '/login.php');
    exit;
}
