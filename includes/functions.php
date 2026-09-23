<?php
/**
 * Shared helper functions used across the site.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escape output to prevent XSS */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect helper */
function redirect($path) {
    header("Location: " . $path);
    exit;
}

/** Is a customer logged in? */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/** Is an admin logged in? */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/** Require customer login or redirect */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

/** Require admin login or redirect */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect('login.php');
    }
}

/** Simple flash message system */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Format a number as Nepali Rupees */
function formatNPR($amount) {
    return 'Rs. ' . number_format((float)$amount, 2);
}

/** Generate a CSRF token and store it in the session */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify a submitted CSRF token */
function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/** Validate an uploaded license/profile image; returns saved filename or false */
function handleImageUpload($fileField, $destinationDir, $prefix = 'img') {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $tmpName = $_FILES[$fileField]['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes, true)) {
        return false;
    }
    if ($_FILES[$fileField]['size'] > $maxSize) {
        return false;
    }

    $ext = pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION);
    $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
    $safeName = $prefix . '_' . uniqid() . '_' . time() . '.' . $ext;
    $destination = rtrim($destinationDir, '/') . '/' . $safeName;

    if (move_uploaded_file($tmpName, $destination)) {
        return $safeName;
    }
    return false;
}

/** Number of days between two Y-m-d dates (minimum 1) */
function calculateDays($pickup, $return) {
    $d1 = new DateTime($pickup);
    $d2 = new DateTime($return);
    $diff = $d1->diff($d2)->days;
    return max(1, $diff);
}
