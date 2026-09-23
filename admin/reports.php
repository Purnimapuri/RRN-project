<?php
$pageTitle = 'Reports & Statistics';
require_once __DIR__ . '/includes/admin-layout-top.php';

$byType = $pdo->query("
    SELECT v.vehicle_type, COUNT(r.reservation_id) AS bookings, COALESCE(SUM(r.total_amount),0) AS revenue
    FROM reservations r JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    GROUP BY v.vehicle_type
")->fetchAll();

$topVehicles = $pdo->query("
    SELECT v.name, v.brand, COUNT(r.reservation_id) AS bookings
    FROM reservations r JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    GROUP BY r.vehicle_id ORDER BY bookings DESC LIMIT 5
")->fetchAll();

$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month, SUM(amount) AS revenue
    FROM payments WHERE payment_status = 'Success'
    GROUP BY month ORDER BY month DESC LIMIT 6
")->fetchAll();

$statusBreakdown = $pdo->query("
    SELECT status, COUNT(*) AS total FROM reservations GROUP BY status
")->fetchAll();
?>

<div class="stat-grid" style="grid-template-columns:repeat(2,1fr);">
  <div class="info-card">
    <h3 style="font-size:1rem;">Bookings &amp; Revenue by Vehicle Type</h3>
    <table class="spec-table">
      <?php foreach ($byType as $t): ?>
        <tr><td><?php echo e($t['vehicle_type']); ?></td><td><?php echo (int)$t['bookings']; ?> bookings &mdash; <?php echo formatNPR($t['revenue']); ?></td></tr>
      <?php endforeach; ?>
      <?php if (empty($byType)): ?><tr><td colspan="2" class="muted">No data yet.</td></tr><?php endif; ?>
    </table>
  </div>

  <div class="info-card">
    <h3 style="font-size:1rem;">Reservation Status Breakdown</h3>
    <table class="spec-table">
      <?php foreach ($statusBreakdown as $s): ?>
        <tr><td><span class="status-pill status-<?php echo e($s['status']); ?>"><?php echo e($s['status']); ?></span></td><td><?php echo (int)$s['total']; ?></td></tr>
      <?php endforeach; ?>
      <?php if (empty($statusBreakdown)): ?><tr><td colspan="2" class="muted">No data yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(2,1fr);">
  <div class="info-card">
    <h3 style="font-size:1rem;">Top 5 Most Booked Vehicles</h3>
    <table class="spec-table">
      <?php foreach ($topVehicles as $v): ?>
        <tr><td><?php echo e($v['brand'] . ' ' . $v['name']); ?></td><td><?php echo (int)$v['bookings']; ?> bookings</td></tr>
      <?php endforeach; ?>
      <?php if (empty($topVehicles)): ?><tr><td colspan="2" class="muted">No data yet.</td></tr><?php endif; ?>
    </table>
  </div>

  <div class="info-card">
    <h3 style="font-size:1rem;">Monthly Revenue (Last 6 Months)</h3>
    <table class="spec-table">
      <?php foreach ($monthlyRevenue as $m): ?>
        <tr><td><?php echo e($m['month']); ?></td><td><?php echo formatNPR($m['revenue']); ?></td></tr>
      <?php endforeach; ?>
      <?php if (empty($monthlyRevenue)): ?><tr><td colspan="2" class="muted">No data yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
