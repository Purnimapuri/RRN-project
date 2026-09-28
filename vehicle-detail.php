<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$vehicleId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlash('error', 'That vehicle could not be found.');
    redirect('vehicles.php');
}

$pageTitle = $vehicle['name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="detail-grid">
      <div>
        <div class="detail-image">
          <?php if ($vehicle['image']): ?>
            <img src="assets/images/vehicles/<?php echo e($vehicle['image']); ?>" alt="<?php echo e($vehicle['name']); ?>" onerror="this.parentElement.textContent='<?php echo e($vehicle['name']); ?>';">
          <?php else: ?>
            <?php echo e($vehicle['name']); ?> photo
          <?php endif; ?>
        </div>

        <h1 class="mt-2"><?php echo e($vehicle['name']); ?></h1>
        <span class="availability-badge availability-<?php echo e($vehicle['availability']); ?>"><?php echo e($vehicle['availability']); ?></span>
        <p class="mt-2"><?php echo nl2br(e($vehicle['description'])); ?></p>

        <table class="spec-table">
          <tr><td>Vehicle Type</td><td><?php echo e($vehicle['vehicle_type']); ?></td></tr>
          <tr><td>Brand</td><td><?php echo e($vehicle['brand']); ?></td></tr>
          <tr><td>Model Year</td><td><?php echo e($vehicle['model_year']); ?></td></tr>
          <tr><td>Fuel Type</td><td><?php echo e($vehicle['fuel_type']); ?></td></tr>
          <tr><td>Transmission</td><td><?php echo e($vehicle['transmission']); ?></td></tr>
          <tr><td>Seating Capacity</td><td><?php echo e($vehicle['seating_capacity']); ?> people</td></tr>
          <tr><td>Pickup Location</td><td><?php echo e($vehicle['pickup_location']); ?></td></tr>
        </table>
      </div>

      <div class="booking-box">
        <h3 id="dailyPrice" data-price="<?php echo e($vehicle['daily_price']); ?>">
          <?php echo formatNPR($vehicle['daily_price']); ?> <small style="font-weight:400;font-size:.85rem;color:var(--color-muted);">/ day</small>
        </h3>

        <?php if ($vehicle['availability'] === 'Available'): ?>
          <!-- CHANGED: a real form, so the chosen dates are sent to reserve.php -->
          <form method="GET" action="reserve.php">
            <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicle['vehicle_id']; ?>">

            <div class="form-group mt-2">
              <label for="pickup_date">Pickup Date</label>
              <input type="date" id="pickup_date" name="pickup_date" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
              <label for="return_date">Return Date</label>
              <input type="date" id="return_date" name="return_date" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <p class="form-hint" id="costEstimate">Select pickup and return dates to see the estimated cost.</p>

            <button type="submit" class="btn btn-primary btn-block mt-2">Reserve This Vehicle</button>
            <p class="form-hint text-center">You'll add your license details on the next step.</p>
          </form>

          <script>
            // Return date can never be on or before the pickup date
            (function () {
              var p = document.getElementById('pickup_date');
              var r = document.getElementById('return_date');
              p.addEventListener('change', function () {
                if (!p.value) return;
                var d = new Date(p.value);
                d.setDate(d.getDate() + 1);
                r.min = d.toISOString().slice(0, 10);
                if (r.value && r.value < r.min) r.value = '';
              });
            })();
          </script>
        <?php else: ?>
          <p class="muted mt-2">This vehicle is currently unavailable for booking.</p>
          <a href="vehicles.php" class="btn btn-outline btn-block">Browse Other Vehicles</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>