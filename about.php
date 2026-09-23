<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" style="padding:60px 0 70px;">
  <div class="container">
    <div class="hero-eyebrow">About RRN</div>
    <h1>Making vehicle rental simple across Nepal.</h1>
    <p class="lead">Rent and Ride Nepal connects travelers, commuters, and adventurers with verified bikes and cars in cities across the country.</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:820px;">
    <h2>Our Story</h2>
    <p>Rent and Ride Nepal (RRN) was built to solve a simple problem: renting a reliable bike or car in Nepal often means navigating scattered listings, unclear pricing, and paperwork done entirely on paper. We built a single platform where every vehicle is verified, every price is transparent, and every booking is backed by a proper driving-license check.</p>

    <h2>What We Offer</h2>
    <p>From scooters ideal for weaving through Kathmandu's streets to SUVs built for the hills, our fleet spans the vehicles Nepal actually needs. Customers can search, compare, and reserve online, upload their driving license (Nepalese or foreign) for verification, and pay securely through eSewa or Khalti &mdash; all without leaving the browser.</p>

    <h2>Our Commitment</h2>
    <p>Every vehicle listed on RRN is checked for condition and documentation before it goes live. Every reservation is reviewed by our team, and every payment is recorded transparently. We're building the rental experience we'd want to use ourselves.</p>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
