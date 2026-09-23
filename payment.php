<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
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
        // NOTE: This is a simulated payment flow for the academic project.
        // In production this step would redirect to eSewa / Khalti's hosted
        // checkout and verify the transaction via their signature/callback API.
        $txnCode = strtoupper($gateway) . '-' . strtoupper(bin2hex(random_bytes(5)));

        $insert = $pdo->prepare("
            INSERT INTO payments (reservation_id, gateway, transaction_code, amount, payment_status, paid_at)
            VALUES (?, ?, ?, ?, 'Success', NOW())
        ");
        $insert->execute([$reservationId, $gateway, $txnCode, $reservation['total_amount']]);

        $updateRes = $pdo->prepare("UPDATE reservations SET status = 'Pending' WHERE reservation_id = ?");
        $updateRes->execute([$reservationId]);

        setFlash('success', 'Payment successful! Your booking request has been submitted for admin approval.');
        redirect('booking-confirmation.php?reservation_id=' . $reservationId);
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
        <p class="form-hint text-center mt-2">This is a demo payment flow for academic purposes; no real transaction is processed.</p>
      </form>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
