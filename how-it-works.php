<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'How It Works';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:820px;">
    <h1>How It Works</h1>
    <p class="muted">Booking a bike or car with RRN takes just a few steps.</p>

    <div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-top:30px;">
      <div class="stat-card">
        <div class="num">1</div>
        <div class="label">Search &amp; choose a vehicle that fits your trip</div>
      </div>
      <div class="stat-card">
        <div class="num">2</div>
        <div class="label">Submit your driving license for verification</div>
      </div>
      <div class="stat-card">
        <div class="num">3</div>
        <div class="label">Pay securely via eSewa or Khalti</div>
      </div>
      <div class="stat-card">
        <div class="num">4</div>
        <div class="label">Get confirmation and pick up your vehicle</div>
      </div>
    </div>

    <div class="text-center mt-2">
      <a href="vehicles.php" class="btn btn-primary">Start Browsing</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
