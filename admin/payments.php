<?php
$pageTitle = 'Payment Records';
require_once __DIR__ . '/includes/admin-layout-top.php';

$payments = $pdo->query("
    SELECT p.*, r.reservation_id, u.full_name, v.name AS vehicle_name
    FROM payments p
    JOIN reservations r ON r.reservation_id = p.reservation_id
    JOIN users u ON u.user_id = r.user_id
    JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    ORDER BY p.paid_at DESC
")->fetchAll();

$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Success'")->fetchColumn();
?>

<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card"><div class="num"><?php echo formatNPR($totalRevenue); ?></div><div class="label">Total Revenue</div></div>
  <div class="stat-card"><div class="num"><?php echo count($payments); ?></div><div class="label">Total Transactions</div></div>
  <div class="stat-card"><div class="num"><?php echo count(array_filter($payments, fn($p) => $p['gateway']==='eSewa')); ?> / <?php echo count(array_filter($payments, fn($p) => $p['gateway']==='Khalti')); ?></div><div class="label">eSewa / Khalti Split</div></div>
</div>

<div style="overflow-x:auto;">
  <table class="data-table">
    <thead><tr><th>Txn Code</th><th>Customer</th><th>Vehicle</th><th>Gateway</th><th>Amount</th><th>Status</th><th>Paid At</th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><?php echo e($p['transaction_code']); ?></td>
          <td><?php echo e($p['full_name']); ?></td>
          <td><?php echo e($p['vehicle_name']); ?></td>
          <td><?php echo e($p['gateway']); ?></td>
          <td><?php echo formatNPR($p['amount']); ?></td>
          <td><span class="status-pill status-<?php echo e($p['payment_status']); ?>"><?php echo e($p['payment_status']); ?></span></td>
          <td><?php echo e($p['paid_at']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($payments)): ?>
        <tr><td colspan="7" class="muted">No payments recorded yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
