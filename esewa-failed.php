<?php
/** eSewa sends the customer here if the payment failed or was cancelled (failure_url). */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = (int)($_SESSION['last_pay_reservation'] ?? 0);
setFlash('error', 'eSewa payment was cancelled or failed. No money was taken. You can try again.');
redirect($id ? 'payment.php?reservation_id=' . $id : 'my-bookings.php');