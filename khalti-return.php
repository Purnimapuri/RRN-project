<?php
/** Khalti sends the customer here after a payment attempt (return_url). */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payment.php';
requireLogin();

function payFail($message, $reservationId = 0) {
    setFlash('error', $message);
    redirect($reservationId ? 'payment.php?reservation_id=' . (int)$reservationId : 'my-bookings.php');
}

$pidx   = trim($_GET['pidx'] ?? '');
$ref    = trim($_GET['purchase_order_id'] ?? '');
$status = trim($_GET['status'] ?? '');

$reservationId = reservationIdFromRef($ref);
$reservation = $reservationId ? loadOwnReservation($pdo, $reservationId, $_SESSION['user_id']) : false;
if (!$reservation || $pidx === '') payFail('Invalid response from Khalti.');

// Must be the payment we started
if (!hash_equals((string)($_SESSION['khalti_pidx_' . $reservationId] ?? ''), $pidx)
    || !hash_equals((string)($_SESSION['pay_ref_' . $reservationId] ?? ''), $ref)) {
    payFail('This payment session has expired. Please try again.', $reservationId);
}

if ($status === 'User canceled') {
    payFail('You cancelled the Khalti payment. No money was taken.', $reservationId);
}

// Never trust the address bar: ask Khalti directly (lookup)
[$code, $lookup, $curlErr] = khaltiPost('/epayment/lookup/', ['pidx' => $pidx]);
if ($curlErr) {
    error_log('Khalti lookup error: ' . $curlErr);
    payFail('Could not confirm the payment with Khalti. Please try again shortly.', $reservationId);
}

$lookupStatus = $lookup['status'] ?? '';
if ($lookupStatus === 'Pending' || $lookupStatus === 'Initiated') {
    payFail('Khalti says the payment is still pending. Do not pay again; check back in a few minutes.', $reservationId);
}
if ($lookupStatus !== 'Completed') {
    payFail('Khalti payment was not completed (' . $lookupStatus . ').', $reservationId);
}

// Amount is in paisa
$paidPaisa = (int)($lookup['total_amount'] ?? 0);
if ($paidPaisa !== (int)round((float)$reservation['total_amount'] * 100)) {
    payFail('Payment amount does not match the booking amount.', $reservationId);
}

$txnCode = (string)($lookup['transaction_id'] ?? '');
if ($txnCode === '' || !savePaymentSuccess($pdo, $reservation, 'Khalti', $txnCode)) {
    payFail('Could not record the payment. Please contact support with code ' . $txnCode . '.', $reservationId);
}

unset($_SESSION['pay_ref_' . $reservationId], $_SESSION['khalti_pidx_' . $reservationId]);
setFlash('success', 'Payment successful! Your booking request has been submitted for admin approval.');
redirect('booking-confirmation.php?reservation_id=' . $reservationId);