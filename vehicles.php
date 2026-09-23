<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// ---- Read filters from query string ----
$type      = $_GET['type'] ?? '';
$location  = $_GET['location'] ?? '';
$brand     = $_GET['brand'] ?? '';
$fuel      = $_GET['fuel'] ?? [];
$transmission = $_GET['transmission'] ?? [];
$minPrice  = $_GET['min_price'] ?? '';
$maxPrice  = $_GET['max_price'] ?? '';
$sort      = $_GET['sort'] ?? 'newest';
$keyword   = trim($_GET['q'] ?? '');

if (!is_array($fuel)) $fuel = [];
if (!is_array($transmission)) $transmission = [];

$where = ["availability = 'Available'"];
$params = [];

if ($type === 'Bike' || $type === 'Car') {
    $where[] = "vehicle_type = ?";
    $params[] = $type;
}
if ($location !== '') {
    $where[] = "pickup_location = ?";
    $params[] = $location;
}
if ($brand !== '') {
    $where[] = "brand = ?";
    $params[] = $brand;
}
if (!empty($fuel)) {
    $placeholders = implode(',', array_fill(0, count($fuel), '?'));
    $where[] = "fuel_type IN ($placeholders)";
    foreach ($fuel as $f) { $params[] = $f; }
}
if (!empty($transmission)) {
    $placeholders = implode(',', array_fill(0, count($transmission), '?'));
    $where[] = "transmission IN ($placeholders)";
    foreach ($transmission as $t) { $params[] = $t; }
}
if ($minPrice !== '' && is_numeric($minPrice)) {
    $where[] = "daily_price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $where[] = "daily_price <= ?";
    $params[] = $maxPrice;
}
if ($keyword !== '') {
    $where[] = "(name LIKE ? OR brand LIKE ? OR description LIKE ?)";
    $like = '%' . $keyword . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$orderBy = "created_at DESC";
switch ($sort) {
    case 'price_low':  $orderBy = "daily_price ASC"; break;
    case 'price_high': $orderBy = "daily_price DESC"; break;
    case 'name_az':    $orderBy = "name ASC"; break;
}

$sql = "SELECT * FROM vehicles WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$brandList = $pdo->query("SELECT DISTINCT brand FROM vehicles ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Browse Vehicles';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-head" style="margin-bottom:30px;">
      <h1>Browse Vehicles</h1>
      <p class="muted">Filter by type, location, price, fuel, and transmission to find your perfect ride.</p>
    </div>

    <div class="vehicles-layout">
      <aside class="filter-panel">
        <form method="GET">
          <div class="form-group">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" placeholder="Search by name or brand" value="<?php echo e($keyword); ?>">
          </div>

          <div class="filter-group">
            <h4>Vehicle Type</h4>
            <label class="checkbox-label"><input type="radio" name="type" value="" <?php echo $type === '' ? 'checked' : ''; ?>> All</label>
            <label class="checkbox-label"><input type="radio" name="type" value="Bike" <?php echo $type === 'Bike' ? 'checked' : ''; ?>> Bike</label>
            <label class="checkbox-label"><input type="radio" name="type" value="Car" <?php echo $type === 'Car' ? 'checked' : ''; ?>> Car</label>
          </div>

          <div class="filter-group">
            <h4>Pickup Location</h4>
            <select name="location">
              <option value="">Any Location</option>
              <?php foreach (['Kathmandu','Pokhara','Lalitpur','Chitwan'] as $loc): ?>
                <option value="<?php echo e($loc); ?>" <?php echo $location === $loc ? 'selected' : ''; ?>><?php echo e($loc); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-group">
            <h4>Brand</h4>
            <select name="brand">
              <option value="">All Brands</option>
              <?php foreach ($brandList as $b): ?>
                <option value="<?php echo e($b); ?>" <?php echo $brand === $b ? 'selected' : ''; ?>><?php echo e($b); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-group">
            <h4>Fuel Type</h4>
            <?php foreach (['Petrol','Diesel','Electric','Hybrid'] as $f): ?>
              <label class="checkbox-label">
                <input type="checkbox" name="fuel[]" value="<?php echo $f; ?>" <?php echo in_array($f, $fuel) ? 'checked' : ''; ?>> <?php echo $f; ?>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="filter-group">
            <h4>Transmission</h4>
            <?php foreach (['Manual','Automatic'] as $t): ?>
              <label class="checkbox-label">
                <input type="checkbox" name="transmission[]" value="<?php echo $t; ?>" <?php echo in_array($t, $transmission) ? 'checked' : ''; ?>> <?php echo $t; ?>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="filter-group">
            <h4>Price per Day (NPR)</h4>
            <div class="form-row">
              <input type="number" name="min_price" placeholder="Min" value="<?php echo e($minPrice); ?>">
              <input type="number" name="max_price" placeholder="Max" value="<?php echo e($maxPrice); ?>">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
          <a href="vehicles.php" class="btn btn-outline btn-block mt-2">Reset</a>
        </form>
      </aside>

      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
          <span class="muted"><?php echo count($vehicles); ?> vehicle(s) found</span>
          <form method="GET" id="sortForm">
            <?php foreach ($_GET as $k => $v) {
                if ($k === 'sort') continue;
                if (is_array($v)) { foreach ($v as $vv) echo '<input type="hidden" name="'.e($k).'[]" value="'.e($vv).'">'; }
                else echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';
            } ?>
            <select name="sort" onchange="document.getElementById('sortForm').submit()">
              <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
              <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
              <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
              <option value="name_az" <?php echo $sort === 'name_az' ? 'selected' : ''; ?>>Name: A to Z</option>
            </select>
          </form>
        </div>

        <div class="vehicle-grid">
          <?php foreach ($vehicles as $v): ?>
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
                <div class="vehicle-brand"><?php echo e($v['brand']); ?> &middot; <?php echo e($v['pickup_location']); ?></div>
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
          <?php if (empty($vehicles)): ?>
            <p class="muted">No vehicles match your filters. Try adjusting your search.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
