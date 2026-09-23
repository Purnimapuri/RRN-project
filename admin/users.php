<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/admin-layout-top.php';

if (isset($_GET['toggle']) && isset($_GET['csrf_token']) && verifyCsrf($_GET['csrf_token'])) {
    $uid = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->execute([$uid]);
    $current = $stmt->fetchColumn();
    if ($current) {
        $new = $current === 'active' ? 'suspended' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$new, $uid]);
        setFlash('success', 'User status updated.');
    }
    redirect('users.php');
}

$users = $pdo->query("
    SELECT u.*, COUNT(r.reservation_id) AS total_bookings
    FROM users u
    LEFT JOIN reservations r ON r.user_id = u.user_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div style="overflow-x:auto;">
  <table class="data-table">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Bookings</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?php echo e($u['full_name']); ?></td>
          <td><?php echo e($u['email']); ?></td>
          <td><?php echo e($u['phone']); ?></td>
          <td><?php echo e($u['city']); ?></td>
          <td><?php echo (int)$u['total_bookings']; ?></td>
          <td><span class="status-pill status-<?php echo $u['status'] === 'active' ? 'Approved' : 'Rejected'; ?>"><?php echo e($u['status']); ?></span></td>
          <td><?php echo e(date('M j, Y', strtotime($u['created_at']))); ?></td>
          <td><a href="users.php?toggle=<?php echo (int)$u['user_id']; ?>&csrf_token=<?php echo e(csrfToken()); ?>" class="btn btn-outline btn-sm"><?php echo $u['status'] === 'active' ? 'Suspend' : 'Reactivate'; ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($users)): ?>
        <tr><td colspan="8" class="muted">No users registered yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
