<?php
$pageTitle = 'Add Vehicle';
require_once __DIR__ . '/includes/admin-layout-top.php';

$errors = [];
$old = [
    'vehicle_type' => 'Bike', 'category_id' => '', 'name' => '', 'brand' => '', 'model_year' => date('Y'),
    'fuel_type' => 'Petrol', 'transmission' => 'Manual', 'seating_capacity' => 2, 'daily_price' => '',
    'description' => '', 'pickup_location' => 'Kathmandu',
];

$categories = $pdo->query("SELECT * FROM vehicle_categories ORDER BY vehicle_type, category_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) $errors[] = 'Invalid form submission.';

    foreach ($old as $k => $v) { $old[$k] = trim($_POST[$k] ?? $v); }

    if ($old['name'] === '') $errors[] = 'Vehicle name is required.';
    if ($old['brand'] === '') $errors[] = 'Brand is required.';
    if (!is_numeric($old['daily_price']) || $old['daily_price'] <= 0) $errors[] = 'A valid daily price is required.';

    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $imageName = handleImageUpload('image', __DIR__ . '/../assets/uploads/vehicles', 'vehicle');
        if ($imageName) {
            // Also copy to the public-facing vehicles image directory used by e() lookups
            @copy(__DIR__ . '/../assets/uploads/vehicles/' . $imageName, __DIR__ . '/../assets/images/vehicles/' . $imageName);
        } else {
            $errors[] = 'Vehicle image must be JPG, PNG, or WEBP (max 2MB).';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (vehicle_type, category_id, name, brand, model_year, fuel_type, transmission, seating_capacity, daily_price, image, description, pickup_location, availability, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available', 'Internal')
        ");
        $stmt->execute([
            $old['vehicle_type'], $old['category_id'] ?: null, $old['name'], $old['brand'], $old['model_year'],
            $old['fuel_type'], $old['transmission'], $old['seating_capacity'], $old['daily_price'],
            $imageName, $old['description'], $old['pickup_location']
        ]);
        setFlash('success', 'Vehicle added successfully.');
        redirect('vehicles.php');
    }
}
?>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error" style="border-radius:8px;margin-bottom:16px;">
    <ul style="margin:0;padding-left:18px;"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="info-card" style="max-width:760px;">
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

    <div class="form-row">
      <div class="form-group">
        <label for="vehicle_type">Vehicle Type</label>
        <select id="vehicle_type" name="vehicle_type">
          <option value="Bike" <?php echo $old['vehicle_type'] === 'Bike' ? 'selected' : ''; ?>>Bike</option>
          <option value="Car" <?php echo $old['vehicle_type'] === 'Car' ? 'selected' : ''; ?>>Car</option>
        </select>
      </div>
      <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">-- Select --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo (int)$c['category_id']; ?>" <?php echo (string)$old['category_id'] === (string)$c['category_id'] ? 'selected' : ''; ?>><?php echo e($c['category_name']); ?> (<?php echo e($c['vehicle_type']); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group"><label for="name">Vehicle Name</label><input type="text" id="name" name="name" value="<?php echo e($old['name']); ?>" required></div>
      <div class="form-group"><label for="brand">Brand</label><input type="text" id="brand" name="brand" value="<?php echo e($old['brand']); ?>" required></div>
    </div>

    <div class="form-row">
      <div class="form-group"><label for="model_year">Model Year</label><input type="number" id="model_year" name="model_year" value="<?php echo e($old['model_year']); ?>" min="1990" max="2100"></div>
      <div class="form-group"><label for="daily_price">Daily Price (NPR)</label><input type="number" step="0.01" id="daily_price" name="daily_price" value="<?php echo e($old['daily_price']); ?>" required></div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="fuel_type">Fuel Type</label>
        <select id="fuel_type" name="fuel_type">
          <?php foreach (['Petrol','Diesel','Electric','Hybrid'] as $f): ?>
            <option value="<?php echo $f; ?>" <?php echo $old['fuel_type'] === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="transmission">Transmission</label>
        <select id="transmission" name="transmission">
          <?php foreach (['Manual','Automatic'] as $t): ?>
            <option value="<?php echo $t; ?>" <?php echo $old['transmission'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group"><label for="seating_capacity">Seating Capacity</label><input type="number" id="seating_capacity" name="seating_capacity" value="<?php echo e($old['seating_capacity']); ?>" min="1" max="20"></div>
      <div class="form-group"><label for="pickup_location">Pickup Location</label><input type="text" id="pickup_location" name="pickup_location" value="<?php echo e($old['pickup_location']); ?>"></div>
    </div>

    <div class="form-group"><label for="description">Description</label><textarea id="description" name="description" rows="4"><?php echo e($old['description']); ?></textarea></div>

    <div class="form-group">
      <label for="image">Vehicle Image</label>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <button type="submit" class="btn btn-primary">Add Vehicle</button>
    <a href="vehicles.php" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
