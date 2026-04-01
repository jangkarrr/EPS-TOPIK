<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
destroySession();
header('Location: ' . APP_URL . '/login.php');
exit;
