<?php
$pageTitle = 'Manage Vehicles';
require_once __DIR__ . '/includes/admin-layout-top.php';

// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && verifyCsrf($_GET['csrf_token'])) {
    $delId = (int)$_GET['delete'];
    $del = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
    $del->execute([$delId]);
    setFlash('success', 'Vehicle deleted successfully.');
    redirect('vehicles.php');
}

// Handle availability quick-toggle
if (isset($_GET['toggle']) && isset($_GET['csrf_token']) && verifyCsrf($_GET['csrf_token'])) {
    $toggleId = (int)$_GET['toggle'];
    $vStmt = $pdo->prepare("SELECT availability FROM vehicles WHERE vehicle_id = ?");
    $vStmt->execute([$toggleId]);
    $current = $vStmt->fetchColumn();
    if ($current) {
        $new = $current === 'Available' ? 'Maintenance' : 'Available';
        $upd = $pdo->prepare("UPDATE vehicles SET availability = ? WHERE vehicle_id = ?");
        $upd->execute([$new, $toggleId]);
        setFlash('success', 'Vehicle status updated.');
    }
    redirect('vehicles.php');
}

$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY created_at DESC")->fetchAll();
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
  <a href="vehicle-add.php" class="btn btn-primary">+ Add Vehicle</a>
</div>

<div style="overflow-x:auto;">
  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Name</th><th>Type</th><th>Brand</th><th>Price/Day</th><th>Availability</th><th>Source</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($vehicles as $v): ?>
        <tr>
          <td>#<?php echo (int)$v['vehicle_id']; ?></td>
          <td><?php echo e($v['name']); ?></td>
          <td><?php echo e($v['vehicle_type']); ?></td>
          <td><?php echo e($v['brand']); ?></td>
          <td><?php echo formatNPR($v['daily_price']); ?></td>
          <td><span class="status-pill availability-<?php echo e($v['availability']); ?>"><?php echo e($v['availability']); ?></span></td>
          <td><?php echo e($v['source']); ?></td>
          <td>
            <a href="vehicle-edit.php?id=<?php echo (int)$v['vehicle_id']; ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="vehicles.php?toggle=<?php echo (int)$v['vehicle_id']; ?>&csrf_token=<?php echo e(csrfToken()); ?>" class="btn btn-outline btn-sm">Toggle Status</a>
            <a href="vehicles.php?delete=<?php echo (int)$v['vehicle_id']; ?>&csrf_token=<?php echo e(csrfToken()); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this vehicle permanently?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($vehicles)): ?>
        <tr><td colspan="8" class="muted">No vehicles listed yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
