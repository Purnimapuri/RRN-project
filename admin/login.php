<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) { redirect('index.php'); }

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
        $stmt = $pdo->prepare("SELECT admin_id, full_name, password_hash, role FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } else {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            redirect('index.php');
        }
    }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | RRN</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:var(--color-navy);min-height:100vh;">

<div class="auth-wrap" style="margin-top:100px;">
  <h1>RRN Admin Login</h1>
  <p class="muted">Restricted access for site administrators only.</p>

  <?php if (!empty($errors)): ?>
    <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
      <ul style="margin:0;padding-left:18px;">
        <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
    <div class="form-group">
      <label for="email">Admin Email</label>
      <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Log In</button>
  </form>

  <p class="auth-footer-link"><a href="../index.php">&larr; Back to main site</a></p>
</div>

</body>
</html>
