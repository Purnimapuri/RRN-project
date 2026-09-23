<?php
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
require_once __DIR__ . '/functions.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' | Rent and Ride Nepal' : 'Rent and Ride Nepal (RRN)'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Quicksand:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php" class="logo">Rent<span>&amp;</span>Ride <em>Nepal</em></a>

    <nav class="main-nav" id="mainNav">
      <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">Home</a>
      <a href="<?php echo isset($basePath) ? $basePath : ''; ?>vehicles.php">Vehicles</a>
      <a href="<?php echo isset($basePath) ? $basePath : ''; ?>about.php">About</a>
      <a href="<?php echo isset($basePath) ? $basePath : ''; ?>faq.php">FAQ</a>
      <a href="<?php echo isset($basePath) ? $basePath : ''; ?>contact.php">Contact</a>
      <?php if (isLoggedIn()): ?>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>my-bookings.php">My Bookings</a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>profile.php">Profile</a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>logout.php" class="btn-nav-outline">Logout</a>
      <?php else: ?>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>login.php">Login</a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>register.php" class="btn-nav">Sign Up</a>
      <?php endif; ?>
    </nav>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<?php if ($flash): ?>
  <div class="flash flash-<?php echo e($flash['type']); ?>">
    <div class="container"><?php echo e($flash['message']); ?></div>
  </div>
<?php endif; ?>
