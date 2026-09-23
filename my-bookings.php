<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $cancelId = (int)$_POST['cancel_reservation_id'];
        $cancel = $pdo->prepare("UPDATE reservations SET status = 'Cancelled' WHERE reservation_id = ? AND user_id = ? AND status IN ('Pending','Approved')");
        $cancel->execute([$cancelId, $userId]);
        setFlash('success', 'Your booking has been cancelled.');
    }
    redirect('my-bookings.php');
}

$stmt = $pdo->prepare("
    SELECT r.*, v.name AS vehicle_name, v.vehicle_type, v.brand
    FROM reservations r
    JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Bookings';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <h1>My Bookings</h1>
    <p class="muted">Track the status of your current and past reservations.</p>

    <?php if (empty($bookings)): ?>
      <div class="info-card text-center mt-2">
        <p>You haven't made any reservations yet.</p>
        <a href="vehicles.php" class="btn btn-primary">Browse Vehicles</a>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="data-table mt-2">
          <thead>
            <tr>
              <th>Ref</th>
              <th>Vehicle</th>
              <th>Pickup</th>
              <th>Return</th>
              <th>Total</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td>#RRN-<?php echo str_pad($b['reservation_id'], 5, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo e($b['brand'] . ' ' . $b['vehicle_name']); ?></td>
                <td><?php echo e($b['pickup_date']); ?></td>
                <td><?php echo e($b['return_date']); ?></td>
                <td><?php echo formatNPR($b['total_amount']); ?></td>
                <td><span class="status-pill status-<?php echo e($b['status']); ?>"><?php echo e($b['status']); ?></span></td>
                <td>
                  <a href="booking-confirmation.php?reservation_id=<?php echo (int)$b['reservation_id']; ?>" class="btn btn-outline btn-sm">View</a>
                  <?php if (in_array($b['status'], ['Pending','Approved'], true)): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking?');">
                      <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                      <input type="hidden" name="cancel_reservation_id" value="<?php echo (int)$b['reservation_id']; ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
