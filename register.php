<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { redirect('index.php'); }

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'city' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $old['city']      = trim($_POST['city'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirmPassword  = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!preg_match('/^[0-9+\-\s]{7,15}$/', $old['phone'])) $errors[] = 'A valid phone number is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, city) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$old['full_name'], $old['email'], $old['phone'], $hash, $old['city']]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_name'] = $old['full_name'];
        setFlash('success', 'Welcome to Rent and Ride Nepal! Your account has been created.');
        redirect('index.php');
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <h1>Create your account</h1>
      <p class="muted">Sign up to reserve bikes and cars anywhere in Nepal.</p>

      <?php if (!empty($errors)): ?>
        <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
          <ul style="margin:0;padding-left:18px;">
            <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" id="registerForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo e($old['full_name']); ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo e($old['email']); ?>" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" placeholder="98XXXXXXXX" value="<?php echo e($old['phone']); ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="city">City</label>
          <input type="text" id="city" name="city" placeholder="e.g. Kathmandu" value="<?php echo e($old['city']); ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="8" required>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
      </form>

      <p class="auth-footer-link">Already have an account? <a href="login.php">Log in here</a></p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
