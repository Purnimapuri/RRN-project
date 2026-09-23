<?php
$pageTitle = 'Manage Reservations';
require_once __DIR__ . '/includes/admin-layout-top.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['new_status'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $resId = (int)$_POST['reservation_id'];
        $newStatus = $_POST['new_status'];
        $allowed = ['Pending','Approved','Rejected','Ongoing','Completed','Cancelled'];
        if (in_array($newStatus, $allowed, true)) {
            $upd = $pdo->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
            $upd->execute([$newStatus, $resId]);

            // Keep vehicle availability in sync with active bookings
            $vehStmt = $pdo->prepare("SELECT vehicle_id FROM reservations WHERE reservation_id = ?");
            $vehStmt->execute([$resId]);
            $vehId = $vehStmt->fetchColumn();
            if ($vehId) {
                if (in_array($newStatus, ['Approved','Ongoing'], true)) {
                    $pdo->prepare("UPDATE vehicles SET availability='Booked' WHERE vehicle_id=?")->execute([$vehId]);
                } elseif (in_array($newStatus, ['Completed','Cancelled','Rejected'], true)) {
                    $pdo->prepare("UPDATE vehicles SET availability='Available' WHERE vehicle_id=?")->execute([$vehId]);
                }
            }
            setFlash('success', 'Reservation status updated.');
        }
    }
    redirect('reservations.php');
}

$statusFilter = $_GET['status'] ?? '';
$sql = "
    SELECT r.*, u.full_name, u.email, v.name AS vehicle_name, dl.verification_status
    FROM reservations r
    JOIN users u ON u.user_id = r.user_id
    JOIN vehicles v ON v.vehicle_id = r.vehicle_id
    LEFT JOIN driving_licenses dl ON dl.license_id = r.license_id
";
$params = [];
if ($statusFilter !== '') {
    $sql .= " WHERE r.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
?>

<div style="margin-bottom:16px;">
  <form method="GET">
    <select name="status" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <?php foreach (['Pending','Approved','Rejected','Ongoing','Completed','Cancelled'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div style="overflow-x:auto;">
  <table class="data-table">
    <thead>
      <tr><th>Ref</th><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Amount</th><th>License</th><th>Status</th><th>Update</th></tr>
    </thead>
    <tbody>
      <?php foreach ($reservations as $r): ?>
        <tr>
          <td>#RRN-<?php echo str_pad($r['reservation_id'], 5, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo e($r['full_name']); ?><br><span class="muted" style="font-size:.78rem;"><?php echo e($r['email']); ?></span></td>
          <td><?php echo e($r['vehicle_name']); ?></td>
          <td><?php echo e($r['pickup_date']); ?> &rarr; <?php echo e($r['return_date']); ?></td>
          <td><?php echo formatNPR($r['total_amount']); ?></td>
          <td><span class="status-pill status-<?php echo e($r['verification_status'] ?? 'Pending'); ?>"><?php echo e($r['verification_status'] ?? 'Pending'); ?></span></td>
          <td><span class="status-pill status-<?php echo e($r['status']); ?>"><?php echo e($r['status']); ?></span></td>
          <td>
            <form method="POST" style="display:flex;gap:6px;">
              <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
              <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
              <select name="new_status">
                <?php foreach (['Pending','Approved','Rejected','Ongoing','Completed','Cancelled'] as $s): ?>
                  <option value="<?php echo $s; ?>" <?php echo $r['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-outline btn-sm">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($reservations)): ?>
        <tr><td colspan="8" class="muted">No reservations found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
