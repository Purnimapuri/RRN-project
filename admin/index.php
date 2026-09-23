<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin-layout-top.php';

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$pendingReservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Pending'")->fetchColumn();
$pendingLicenses = $pdo->query("SELECT COUNT(*) FROM driving_licenses WHERE verification_status = 'Pending'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status = 'Success'")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

$recentReservations = $pdo->query("
    SELECT r.*, u.full_name, v.name AS vehicle_name
    FROM reservations r
    JOIN users u ON u.user_id = r.user_id
    JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    ORDER BY r.created_at DESC LIMIT 8
")->fetchAll();
?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?php echo (int)$totalUsers; ?></div><div class="label">Registered Users</div></div>
  <div class="stat-card"><div class="num"><?php echo (int)$totalVehicles; ?></div><div class="label">Vehicles Listed</div></div>
  <div class="stat-card"><div class="num"><?php echo (int)$pendingReservations; ?></div><div class="label">Pending Reservations</div></div>
  <div class="stat-card"><div class="num"><?php echo (int)$pendingLicenses; ?></div><div class="label">Licenses to Verify</div></div>
</div>
<div class="stat-grid">
  <div class="stat-card"><div class="num"><?php echo formatNPR($totalRevenue); ?></div><div class="label">Total Revenue Collected</div></div>
  <div class="stat-card"><div class="num"><?php echo (int)$totalBookings; ?></div><div class="label">Total Bookings</div></div>
</div>

<h3 style="margin-top:10px;">Recent Reservations</h3>
<div style="overflow-x:auto;">
  <table class="data-table">
    <thead>
      <tr><th>Ref</th><th>Customer</th><th>Vehicle</th><th>Pickup</th><th>Return</th><th>Amount</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php foreach ($recentReservations as $r): ?>
        <tr>
          <td>#RRN-<?php echo str_pad($r['reservation_id'], 5, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo e($r['full_name']); ?></td>
          <td><?php echo e($r['vehicle_name']); ?></td>
          <td><?php echo e($r['pickup_date']); ?></td>
          <td><?php echo e($r['return_date']); ?></td>
          <td><?php echo formatNPR($r['total_amount']); ?></td>
          <td><span class="status-pill status-<?php echo e($r['status']); ?>"><?php echo e($r['status']); ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recentReservations)): ?>
        <tr><td colspan="7" class="muted">No reservations yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
