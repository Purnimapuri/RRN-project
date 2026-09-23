<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $city     = trim($_POST['city'] ?? '');

    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) $errors[] = 'A valid phone number is required.';

    if (empty($errors)) {
        $update = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, city = ? WHERE user_id = ?");
        $update->execute([$fullName, $phone, $address, $city, $userId]);
        $_SESSION['user_name'] = $fullName;
        setFlash('success', 'Profile updated successfully.');
        redirect('profile.php');
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:720px;">
    <h1>My Profile</h1>
    <p class="muted">Update your personal information below.</p>

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
        <ul style="margin:0;padding-left:18px;">
          <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="info-card">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

        <div class="form-row">
          <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo e($user['full_name']); ?>" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" value="<?php echo e($user['email']); ?>" disabled>
            <div class="form-hint">Email cannot be changed.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="<?php echo e($user['phone']); ?>" required>
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?php echo e($user['city']); ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="address">Address</label>
          <textarea id="address" name="address" rows="3"><?php echo e($user['address']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>

    <div class="info-card mt-2">
      <h3 style="font-size:1.05rem;">Driving Licenses</h3>
      <p class="muted">Manage your saved driving license details from the reservation flow.</p>
      <a href="my-bookings.php" class="btn btn-outline btn-sm">View My Bookings</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
