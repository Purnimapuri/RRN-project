<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Featured vehicles: 6 available, most recently added
$stmt = $pdo->query("SELECT * FROM vehicles WHERE availability = 'Available' ORDER BY created_at DESC LIMIT 6");
$featured = $stmt->fetchAll();

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <div class="hero-eyebrow">Bikes &amp; Cars, Anywhere in Nepal</div>
    <h1>Rent the ride that fits your journey.</h1>
    <p class="lead">From scooters for the Kathmandu valley to SUVs for the hills of Mustang &mdash; book a verified vehicle in minutes, with driving-license verification and secure payment built in.</p>
    <div class="hero-cta">
      <a href="vehicles.php" class="btn btn-primary">Browse Vehicles</a>
      <a href="how-it-works.php" class="btn btn-outline" style="border-color:#fff;color:#fff;">How It Works</a>
    </div>

    <form action="vehicles.php" method="GET" class="search-widget">
      <div>
        <label for="type">Vehicle Type</label>
        <select name="type" id="type">
          <option value="">All Types</option>
          <option value="Bike">Bike</option>
          <option value="Car">Car</option>
        </select>
      </div>
      <div>
        <label for="location">Pickup Location</label>
        <select name="location" id="location">
          <option value="">Any Location</option>
          <option value="Kathmandu">Kathmandu</option>
          <option value="Pokhara">Pokhara</option>
          <option value="Lalitpur">Lalitpur</option>
          <option value="Chitwan">Chitwan</option>
        </select>
      </div>
      <div>
        <label for="pickup_date">Pickup Date</label>
        <input type="date" name="pickup_date" id="pickup_date">
      </div>
      <div>
        <label for="return_date">Return Date</label>
        <input type="date" name="return_date" id="return_date">
      </div>
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div class="hero-eyebrow">Featured Vehicles</div>
      <h2>Popular picks this week</h2>
      <p class="muted">Hand-picked bikes and cars currently available for booking.</p>
    </div>

    <div class="vehicle-grid">
      <?php foreach ($featured as $v): ?>
        <div class="vehicle-card">
          <div class="thumb">
            <?php if ($v['image']): ?>
              <img src="assets/images/vehicles/<?php echo e($v['image']); ?>" alt="<?php echo e($v['name']); ?>" onerror="this.parentElement.textContent='<?php echo e($v['name']); ?>';">
            <?php else: ?>
              <?php echo e($v['name']); ?>
            <?php endif; ?>
          </div>
          <div class="vehicle-body">
            <span class="vehicle-type-tag"><?php echo e($v['vehicle_type']); ?></span>
            <h3><?php echo e($v['name']); ?></h3>
            <div class="vehicle-brand"><?php echo e($v['brand']); ?> &middot; <?php echo e($v['model_year']); ?></div>
            <div class="vehicle-specs">
              <span class="spec-chip"><?php echo e($v['fuel_type']); ?></span>
              <span class="spec-chip"><?php echo e($v['transmission']); ?></span>
              <span class="spec-chip"><?php echo e($v['seating_capacity']); ?> Seats</span>
            </div>
            <div class="vehicle-price-row">
              <div class="vehicle-price"><?php echo formatNPR($v['daily_price']); ?> <small>/ day</small></div>
              <a href="vehicle-detail.php?id=<?php echo (int)$v['vehicle_id']; ?>" class="btn btn-dark btn-sm">View</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($featured)): ?>
        <p class="muted">No vehicles available right now. Please check back soon.</p>
      <?php endif; ?>
    </div>

    <div class="text-center mt-2">
      <a href="vehicles.php" class="btn btn-outline">View All Vehicles</a>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <div class="hero-eyebrow">Why RRN</div>
      <h2>Built for travelers across Nepal</h2>
    </div>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="num">100%</div>
        <div class="label">Licensed &amp; verified vehicles</div>
      </div>
      <div class="stat-card">
        <div class="num">24/7</div>
        <div class="label">Customer support</div>
      </div>
      <div class="stat-card">
        <div class="num">eSewa</div>
        <div class="label">&amp; Khalti payment support</div>
      </div>
      <div class="stat-card">
        <div class="num">5+</div>
        <div class="label">Cities covered nationwide</div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
