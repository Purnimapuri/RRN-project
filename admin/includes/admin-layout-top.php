<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' | RRN Admin' : 'RRN Admin'; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="index.php" class="logo-admin">RRN <span>Admin</span></a>
    <nav>
      <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
      <a href="vehicles.php" class="<?php echo in_array($currentPage, ['vehicles.php','vehicle-add.php','vehicle-edit.php']) ? 'active' : ''; ?>">Vehicles</a>
      <a href="reservations.php" class="<?php echo $currentPage === 'reservations.php' ? 'active' : ''; ?>">Reservations</a>
      <a href="licenses.php" class="<?php echo $currentPage === 'licenses.php' ? 'active' : ''; ?>">License Verification</a>
      <a href="payments.php" class="<?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">Payments</a>
      <a href="users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">Users</a>
      <a href="reports.php" class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">Reports</a>
      <a href="external-sync.php" class="<?php echo $currentPage === 'external-sync.php' ? 'active' : ''; ?>">Import Vehicles</a>
      <a href="logout.php">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <h2 style="margin:0;font-size:1.3rem;"><?php echo isset($pageTitle) ? e($pageTitle) : 'Dashboard'; ?></h2>
      <span class="muted">Welcome, <?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?></span>
    </div>

    <?php if ($flash): ?>
      <div class="flash flash-<?php echo e($flash['type']); ?>" style="border-radius:8px;margin-bottom:20px;">
        <?php echo e($flash['message']); ?>
      </div>
    <?php endif; ?>
