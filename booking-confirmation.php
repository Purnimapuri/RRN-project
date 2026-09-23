<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$reservationId = (int)($_GET['reservation_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.*, v.name AS vehicle_name, v.brand, v.vehicle_type, p.gateway, p.transaction_code, p.paid_at
    FROM reservations r
    JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    LEFT JOIN payments p ON p.reservation_id = r.reservation_id AND p.payment_status = 'Success'
    WHERE r.reservation_id = ? AND r.user_id = ?
");
$stmt->execute([$reservationId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('error', 'Booking not found.');
    redirect('my-bookings.php');
}

$pageTitle = 'Booking Confirmation';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:720px;">
    <div class="steps">
      <div class="step">1. Reservation Details</div>
      <div class="step">2. Payment</div>
      <div class="step active">3. Confirmation</div>
    </div>

    <div class="info-card text-center">
      <h1 style="color:var(--color-success);">Booking Confirmed!</h1>
      <p class="muted">Thank you, your reservation request has been submitted successfully. Our team will verify your driving license and confirm your booking shortly.</p>
    </div>

    <div class="info-card mt-2" id="confirmationPrintable">
      <h3 style="font-size:1.05rem;">Booking Receipt</h3>
      <table class="spec-table">
        <tr><td>Booking Reference</td><td>#RRN-<?php echo str_pad($booking['reservation_id'], 5, '0', STR_PAD_LEFT); ?></td></tr>
        <tr><td>Vehicle</td><td><?php echo e($booking['brand'] . ' ' . $booking['vehicle_name']); ?> (<?php echo e($booking['vehicle_type']); ?>)</td></tr>
        <tr><td>Pickup Date</td><td><?php echo e($booking['pickup_date']); ?></td></tr>
        <tr><td>Return Date</td><td><?php echo e($booking['return_date']); ?></td></tr>
        <tr><td>Pickup Location</td><td><?php echo e($booking['pickup_location']); ?></td></tr>
        <tr><td>Total Amount</td><td><?php echo formatNPR($booking['total_amount']); ?></td></tr>
        <tr><td>Payment Method</td><td><?php echo e($booking['gateway'] ?? 'N/A'); ?></td></tr>
        <tr><td>Transaction Code</td><td><?php echo e($booking['transaction_code'] ?? 'N/A'); ?></td></tr>
        <tr><td>Booking Status</td><td><span class="status-pill status-<?php echo e($booking['status']); ?>"><?php echo e($booking['status']); ?></span></td></tr>
      </table>

      <p class="form-hint">A copy of this confirmation is also available anytime from "My Bookings." This page is email-ready and can be forwarded as your booking confirmation.</p>
    </div>

    <div class="text-center mt-2">
      <a href="my-bookings.php" class="btn btn-primary">View My Bookings</a>
      <a href="vehicles.php" class="btn btn-outline">Browse More Vehicles</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
