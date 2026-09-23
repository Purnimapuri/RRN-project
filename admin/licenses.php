<?php
$pageTitle = 'Driving License Verification';
require_once __DIR__ . '/includes/admin-layout-top.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_id'], $_POST['decision'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $licId = (int)$_POST['license_id'];
        $decision = $_POST['decision'] === 'Verified' ? 'Verified' : 'Rejected';
        $remarks = trim($_POST['remarks'] ?? '');
        $upd = $pdo->prepare("UPDATE driving_licenses SET verification_status = ?, admin_remarks = ? WHERE license_id = ?");
        $upd->execute([$decision, $remarks, $licId]);
        setFlash('success', 'License marked as ' . $decision . '.');
    }
    redirect('licenses.php');
}

$statusFilter = $_GET['status'] ?? 'Pending';
$sql = "SELECT dl.*, u.full_name AS account_name, u.email FROM driving_licenses dl JOIN users u ON u.user_id = dl.user_id";
$params = [];
if ($statusFilter !== '') {
    $sql .= " WHERE dl.verification_status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY dl.submitted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$licenses = $stmt->fetchAll();
?>

<div style="margin-bottom:16px;">
  <form method="GET">
    <select name="status" onchange="this.form.submit()">
      <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>All Statuses</option>
      <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
      <option value="Verified" <?php echo $statusFilter === 'Verified' ? 'selected' : ''; ?>>Verified</option>
      <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
    </select>
  </form>
</div>

<?php foreach ($licenses as $l): ?>
  <div class="info-card mt-2">
    <div style="display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;">
      <div style="flex:1;min-width:260px;">
        <h3 style="font-size:1rem;"><?php echo e($l['holder_name']); ?> <span class="status-pill status-<?php echo e($l['verification_status']); ?>"><?php echo e($l['verification_status']); ?></span></h3>
        <table class="spec-table">
          <tr><td>Account</td><td><?php echo e($l['account_name']); ?> (<?php echo e($l['email']); ?>)</td></tr>
          <tr><td>License Number</td><td><?php echo e($l['license_number']); ?></td></tr>
          <tr><td>Type</td><td><?php echo e($l['license_type']); ?></td></tr>
          <tr><td>Country of Issue</td><td><?php echo e($l['country_of_issue']); ?></td></tr>
          <tr><td>Expiry Date</td><td><?php echo e($l['expiry_date']); ?></td></tr>
          <tr><td>Submitted</td><td><?php echo e($l['submitted_at']); ?></td></tr>
        </table>
      </div>
      <div style="width:220px;">
        <div class="detail-image" style="height:150px;">
          <img src="../assets/uploads/licenses/<?php echo e($l['license_image']); ?>" alt="License image" onerror="this.parentElement.textContent='Image unavailable';">
        </div>
      </div>
    </div>

    <?php if ($l['verification_status'] === 'Pending'): ?>
      <form method="POST" class="mt-2" style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <input type="hidden" name="license_id" value="<?php echo (int)$l['license_id']; ?>">
        <input type="text" name="remarks" placeholder="Optional remarks" style="flex:1;min-width:200px;">
        <button type="submit" name="decision" value="Verified" class="btn btn-primary btn-sm">Verify</button>
        <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-sm">Reject</button>
      </form>
    <?php elseif ($l['admin_remarks']): ?>
      <p class="form-hint mt-2">Remarks: <?php echo e($l['admin_remarks']); ?></p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if (empty($licenses)): ?>
  <p class="muted mt-2">No license submissions found for this filter.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
