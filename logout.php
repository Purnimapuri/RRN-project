<?php
require_once __DIR__ . '/includes/functions.php';

// Clear only customer-session keys (keep admin session separate/unaffected)
unset($_SESSION['user_id'], $_SESSION['user_name']);
session_regenerate_id(true);

redirect('login.php');
