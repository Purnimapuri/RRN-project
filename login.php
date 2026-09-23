<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { redirect('index.php'); }

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id, full_name, password_hash, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account has been suspended. Please contact support.';
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect($redirect);
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <h1>Log in</h1>
      <p class="muted">Access your bookings and manage your reservations.</p>

      <?php if (!empty($errors)): ?>
        <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
          <ul style="margin:0;padding-left:18px;">
            <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Log In</button>
      </form>

      <p class="auth-footer-link">Don't have an account? <a href="register.php">Sign up here</a></p>
      <p class="auth-footer-link"><a href="admin/login.php">Admin login &rarr;</a></p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
