<?php
/**
 * Payment settings + helper functions (eSewa & Khalti, SANDBOX mode)
 * Save as: config/payment.php
 */

// ================== CHANGE THESE ==================
// The address you type in the browser to open your site. No slash at the end.
// It must match exactly (localhost vs 127.0.0.1) or the login session gets lost.
define('BASE_URL', 'http://localhost/project1');

// Paste your Khalti SANDBOX secret key here (from test-admin.khalti.com)
define('KHALTI_SECRET_KEY', 'fadecc1893154e488d36e6eb007d9e57');

// 'sandbox' = test money.  'live' = real money (needs real merchant keys below)
define('PAYMENT_MODE', 'sandbox');
// ==================================================

// eSewa test merchant details (public, from eSewa developer docs).
// For live mode eSewa gives you your own product code and secret key.
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');

if (PAYMENT_MODE === 'sandbox') {
    define('ESEWA_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
    define('KHALTI_API', 'https://dev.khalti.com/api/v2');
} else {
    define('ESEWA_FORM_URL', 'https://epay.esewa.com.np/api/epay/main/v2/form');
    define('KHALTI_API', 'https://khalti.com/api/v2');
}

// Keep true. Only set false briefly if XAMPP shows "SSL certificate problem" (see notes).
define('CURL_VERIFY_SSL', true);

/** eSewa signature: HMAC-SHA256, base64 */
function esewaSign($message) {
    return base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));
}

/** Unique reference for one payment attempt, e.g. RRN-12-1767000000 (letters, digits, hyphen only) */
function makeOrderRef($reservationId) {
    return 'RRN-' . (int)$reservationId . '-' . time();
}

/** Get the reservation id back out of a reference */
function reservationIdFromRef($ref) {
    return preg_match('/^RRN-(\d+)-\d+$/', (string)$ref, $m) ? (int)$m[1] : 0;
}

/** Load a reservation, only if it belongs to this user */
function loadOwnReservation($pdo, $reservationId, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ? AND user_id = ?");
    $stmt->execute([$reservationId, $userId]);
    return $stmt->fetch();
}

/** Turn "1,000.0" or "1000" into a float */
function cleanAmount($value) {
    return (float)str_replace(',', '', (string)$value);
}

/**
 * Save a successful payment. Safe to call twice (no double saving).
 * Returns true if the reservation is now paid.
 */
function savePaymentSuccess($pdo, $reservation, $gateway, $txnCode) {
    $resId = (int)$reservation['reservation_id'];

    $chk = $pdo->prepare("SELECT payment_id FROM payments WHERE reservation_id = ? AND payment_status = 'Success'");
    $chk->execute([$resId]);
    if ($chk->fetch()) return true;                       // already saved

    $dup = $pdo->prepare("SELECT payment_id FROM payments WHERE transaction_code = ?");
    $dup->execute([$txnCode]);
    if ($dup->fetch()) return false;                      // this transaction was used before

    $ins = $pdo->prepare("
        INSERT INTO payments (reservation_id, gateway, transaction_code, amount, payment_status, paid_at)
        VALUES (?, ?, ?, ?, 'Success', NOW())
    ");
    $ins->execute([$resId, $gateway, $txnCode, $reservation['total_amount']]);
    return true;
}

/** Send a JSON POST to Khalti. Returns [httpCode, responseArray, curlError] */
function khaltiPost($path, array $payload) {
    $ch = curl_init(KHALTI_API . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => CURL_VERIFY_SSL,
        CURLOPT_HTTPHEADER     => [
            'Authorization: key ' . KHALTI_SECRET_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $data = json_decode((string)$body, true);
    return [$code, is_array($data) ? $data : [], $err];
}