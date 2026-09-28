<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payment.php';
requireLogin();

$userId = $_SESSION['user_id'];
$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.*, v.name AS vehicle_name, v.image AS vehicle_image
    FROM reservations r
    JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    WHERE r.reservation_id = ? AND r.user_id = ?
");
$stmt->execute([$reservationId, $userId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('error', 'Reservation not found.');
    redirect('my-bookings.php');
}

// If already paid, go straight to confirmation
$payCheck = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? AND payment_status = 'Success'");
$payCheck->execute([$reservationId]);
if ($payCheck->fetch()) {
    redirect('booking-confirmation.php?reservation_id=' . $reservationId);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }
    $gateway = $_POST['gateway'] ?? '';
    if (!in_array($gateway, ['eSewa', 'Khalti'], true)) {
        $errors[] = 'Please select a payment method.';
    }

    if (empty($errors)) {
        $total = (float)$reservation['total_amount'];
        $ref   = makeOrderRef($reservationId);
        $_SESSION['pay_ref_' . $reservationId] = $ref;        // remembered to check on return
        $_SESSION['last_pay_reservation']      = $reservationId;

        // ---------------- eSewa: send the customer to eSewa with a signed form ----------------
        if ($gateway === 'eSewa') {
            $amount = (string)$total;                          // e.g. "2500"
            $fields = [
                'amount'                  => $amount,
                'tax_amount'              => '0',
                'total_amount'            => $amount,
                'transaction_uuid'        => $ref,
                'product_code'            => ESEWA_PRODUCT_CODE,
                'product_service_charge'  => '0',
                'product_delivery_charge' => '0',
                'success_url'             => BASE_URL . '/esewa-return.php',
                'failure_url'             => BASE_URL . '/esewa-failed.php',
                'signed_field_names'      => 'total_amount,transaction_uuid,product_code',
            ];
            $fields['signature'] = esewaSign(
                'total_amount=' . $amount . ',transaction_uuid=' . $ref . ',product_code=' . ESEWA_PRODUCT_CODE
            );
            ?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Redirecting to eSewa...</title></head>
<body>
  <p>Redirecting to eSewa, please wait...</p>
  <form id="esewaForm" method="POST" action="<?php echo e(ESEWA_FORM_URL); ?>">
    <?php foreach ($fields as $k => $v): ?>
      <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>">
    <?php endforeach; ?>
    <noscript><button type="submit">Continue to eSewa</button></noscript>
  </form>
  <script>document.getElementById('esewaForm').submit();</script>
</body></html>
            <?php
            exit;
        }

        // ---------------- Khalti: ask Khalti for a payment link, then redirect ----------------
        if ($gateway === 'Khalti') {
            if (strpos(KHALTI_SECRET_KEY, 'PASTE_') === 0) {
                $errors[] = 'Khalti key is not set yet. Add your sandbox secret key in config/payment.php.';
            } elseif ($total < 10) {
                $errors[] = 'Khalti needs a payment of at least Rs. 10.';
            } else {
                [$code, $resp, $curlErr] = khaltiPost('/epayment/initiate/', [
                    'return_url'          => BASE_URL . '/khalti-return.php',
                    'website_url'         => BASE_URL . '/',
                    'amount'              => (int)round($total * 100),     // Khalti wants paisa
                    'purchase_order_id'   => $ref,
                    'purchase_order_name' => 'Rental: ' . $reservation['vehicle_name'],
                ]);
                if ($code === 200 && !empty($resp['payment_url']) && !empty($resp['pidx'])) {
                    $_SESSION['khalti_pidx_' . $reservationId] = $resp['pidx'];
                    redirect($resp['payment_url']);
                }
                error_log('Khalti initiate failed: HTTP ' . $code . ' ' . json_encode($resp) . ' ' . $curlErr);
                $errors[] = 'Could not start the Khalti payment. ' . ($curlErr ?: json_encode($resp));
            }
        }
    }
}

$pageTitle = 'Payment';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:720px;">
    <div class="steps">
      <div class="step">1. Reservation Details</div>
      <div class="step active">2. Payment</div>
      <div class="step">3. Confirmation</div>
    </div>

    <h1>Payment Summary</h1>

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
        <ul style="margin:0;padding-left:18px;">
          <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="info-card">
      <table class="spec-table">
        <tr><td>Vehicle</td><td><?php echo e($reservation['vehicle_name']); ?></td></tr>
        <tr><td>Pickup Date</td><td><?php echo e($reservation['pickup_date']); ?></td></tr>
        <tr><td>Return Date</td><td><?php echo e($reservation['return_date']); ?></td></tr>
        <tr><td>Total Days</td><td><?php echo (int)$reservation['total_days']; ?></td></tr>
        <tr><td>Pickup Location</td><td><?php echo e($reservation['pickup_location']); ?></td></tr>
        <tr><td><strong>Total Amount</strong></td><td><strong><?php echo formatNPR($reservation['total_amount']); ?></strong></td></tr>
      </table>

      <form method="POST" class="mt-2">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <input type="hidden" name="reservation_id" value="<?php echo (int)$reservationId; ?>">
        <input type="hidden" name="gateway" id="selectedGateway" value="">

        <h3 style="font-size:1rem;">Choose a Payment Method</h3>
        <div class="gateway-options">
          <div class="gateway-card" data-gateway="eSewa">
            <div style="font-family:var(--font-display);font-weight:700;color:#5C2D91;font-size:1.1rem;">eSewa</div>
            <p class="form-hint mb-0">Pay using your eSewa wallet</p>
          </div>
          <div class="gateway-card" data-gateway="Khalti">
            <div style="font-family:var(--font-display);font-weight:700;color:#5C2D91;font-size:1.1rem;">Khalti</div>
            <p class="form-hint mb-0">Pay using your Khalti wallet</p>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Pay <?php echo formatNPR($reservation['total_amount']); ?></button>
        <?php if (PAYMENT_MODE === 'sandbox'): ?>
          <p class="form-hint text-center mt-2">Test mode: you will be taken to the gateway's test page and no real money is charged.</p>
        <?php endif; ?>
      </form>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>