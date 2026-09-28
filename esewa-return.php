<?php
/** eSewa sends the customer here after a payment attempt (success_url). */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payment.php';
requireLogin();

function payFail($message, $reservationId = 0) {
    setFlash('error', $message);
    redirect($reservationId ? 'payment.php?reservation_id=' . (int)$reservationId : 'my-bookings.php');
}

/** Read a value exactly as written in the JSON text (the signature is made from the raw text) */
function rawJsonValue($raw, $name) {
    $pattern = '/"' . preg_quote($name, '/') . '"\s*:\s*(?:"([^"]*)"|([^,}\s]+))/';
    if (!preg_match($pattern, $raw, $m)) return null;
    return ($m[1] !== '') ? $m[1] : ($m[2] ?? '');
}

$raw = base64_decode($_GET['data'] ?? '', true);
$json = $raw ? json_decode($raw, true) : null;
if (!$raw || !is_array($json)) {
    payFail('Invalid response from eSewa.');
}

// 1) Check the signature (proves the answer really came from eSewa and was not changed)
$names = explode(',', (string)($json['signed_field_names'] ?? ''));
$parts = [];
foreach ($names as $n) {
    $v = rawJsonValue($raw, $n);
    if ($v === null) payFail('Invalid response from eSewa.');
    $parts[] = $n . '=' . $v;
}
$expected = esewaSign(implode(',', $parts));
if (!hash_equals($expected, (string)($json['signature'] ?? ''))) {
    payFail('Payment could not be verified (signature mismatch).');
}

// 2) Check it is the payment we started
$ref = (string)($json['transaction_uuid'] ?? '');
$reservationId = reservationIdFromRef($ref);
$reservation = $reservationId ? loadOwnReservation($pdo, $reservationId, $_SESSION['user_id']) : false;
if (!$reservation) payFail('Reservation not found.');

if (!hash_equals((string)($_SESSION['pay_ref_' . $reservationId] ?? ''), $ref)) {
    payFail('This payment session has expired. Please try again.', $reservationId);
}
if (($json['product_code'] ?? '') !== ESEWA_PRODUCT_CODE) {
    payFail('Payment could not be verified (wrong merchant).', $reservationId);
}

// 3) Check status and amount
if (($json['status'] ?? '') !== 'COMPLETE') {
    payFail('eSewa payment was not completed.', $reservationId);
}
if (abs(cleanAmount(rawJsonValue($raw, 'total_amount')) - (float)$reservation['total_amount']) > 0.01) {
    payFail('Payment amount does not match the booking amount.', $reservationId);
}

// 4) Save
$txnCode = (string)($json['transaction_code'] ?? '');
if ($txnCode === '' || !savePaymentSuccess($pdo, $reservation, 'eSewa', $txnCode)) {
    payFail('Could not record the payment. Please contact support with code ' . $txnCode . '.', $reservationId);
}

unset($_SESSION['pay_ref_' . $reservationId]);
setFlash('success', 'Payment successful! Your booking request has been submitted for admin approval.');
redirect('booking-confirmation.php?reservation_id=' . $reservationId);