<?php
$pageTitle = 'Import Vehicles (External Source)';
require_once __DIR__ . '/includes/admin-layout-top.php';

/**
 * External Vehicle Source Sync
 * --------------------------------------------------------------
 * This page pulls vehicle listings from an external provider (a
 * JSON API/feed) and lets the admin review and import them into
 * the RRN vehicles table, tagged with source = 'External'.
 *
 * Configure EXTERNAL_API_URL below to point at a real provider
 * feed that returns a JSON array of objects shaped like:
 * [{ "id": "...", "name": "...", "brand": "...", "type": "Bike|Car",
 *    "fuel_type": "...", "transmission": "...", "seats": 4,
 *    "daily_price": 2500, "description": "...", "location": "..." }, ...]
 */
define('EXTERNAL_API_URL', ''); // e.g. 'https://provider.example.com/api/vehicles'

$fetchedVehicles = [];
$fetchError = null;

if (isset($_GET['fetch'])) {
    if (EXTERNAL_API_URL === '') {
        // No real provider configured yet — show sample data so the
        // import workflow below can be demonstrated end-to-end.
        $fetchedVehicles = [
            ['id' => 'EXT-1001', 'name' => 'Splendor Plus', 'brand' => 'Hero', 'type' => 'Bike', 'fuel_type' => 'Petrol', 'transmission' => 'Manual', 'seats' => 2, 'daily_price' => 1000, 'description' => 'Economical commuter bike from an external partner fleet.', 'location' => 'Kathmandu'],
            ['id' => 'EXT-1002', 'name' => 'Creta', 'brand' => 'Hyundai', 'type' => 'Car', 'fuel_type' => 'Diesel', 'transmission' => 'Automatic', 'seats' => 5, 'daily_price' => 8500, 'description' => 'Mid-size SUV sourced from a partner rental agency.', 'location' => 'Pokhara'],
        ];
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $raw = @file_get_contents(EXTERNAL_API_URL, false, $ctx);
        if ($raw === false) {
            $fetchError = 'Could not reach the external vehicle provider. Please check the API URL and try again.';
        } else {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $fetchError = 'The external provider returned an unexpected response format.';
            } else {
                $fetchedVehicles = $decoded;
            }
        }
    }
}

// Handle import of a selected external vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $ext = json_decode($_POST['payload'], true);
        if (is_array($ext)) {
            $check = $pdo->prepare("SELECT vehicle_id FROM vehicles WHERE external_ref_id = ?");
            $check->execute([$ext['id'] ?? '']);
            if ($check->fetch()) {
                setFlash('error', 'This vehicle has already been imported.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicles (vehicle_type, name, brand, fuel_type, transmission, seating_capacity, daily_price, description, pickup_location, availability, source, external_ref_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available', 'External', ?)
                ");
                $stmt->execute([
                    $ext['type'] ?? 'Car', $ext['name'] ?? 'Imported Vehicle', $ext['brand'] ?? 'Unknown',
                    $ext['fuel_type'] ?? 'Petrol', $ext['transmission'] ?? 'Manual', $ext['seats'] ?? 4,
                    $ext['daily_price'] ?? 0, $ext['description'] ?? '', $ext['location'] ?? 'Kathmandu', $ext['id'] ?? null,
                ]);
                setFlash('success', 'Vehicle imported successfully from external source.');
            }
        }
    }
    redirect('external-sync.php');
}

$importedCount = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE source = 'External'")->fetchColumn();
?>

<div class="info-card">
  <p class="muted mb-0">Currently <strong><?php echo (int)$importedCount; ?></strong> vehicle(s) have been imported from external sources. Configure <code>EXTERNAL_API_URL</code> in <code>admin/external-sync.php</code> to connect a real provider feed.</p>
</div>

<div class="mt-2">
  <a href="external-sync.php?fetch=1" class="btn btn-primary">Fetch Vehicles From External Source</a>
</div>

<?php if ($fetchError): ?>
  <div class="flash flash-error mt-2" style="border-radius:8px;"><?php echo e($fetchError); ?></div>
<?php endif; ?>

<?php if (!empty($fetchedVehicles)): ?>
  <div class="mt-2" style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>External ID</th><th>Name</th><th>Brand</th><th>Type</th><th>Daily Price</th><th>Location</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($fetchedVehicles as $ext): ?>
          <tr>
            <td><?php echo e($ext['id'] ?? ''); ?></td>
            <td><?php echo e($ext['name'] ?? ''); ?></td>
            <td><?php echo e($ext['brand'] ?? ''); ?></td>
            <td><?php echo e($ext['type'] ?? ''); ?></td>
            <td><?php echo formatNPR($ext['daily_price'] ?? 0); ?></td>
            <td><?php echo e($ext['location'] ?? ''); ?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                <input type="hidden" name="payload" value='<?php echo htmlspecialchars(json_encode($ext), ENT_QUOTES, "UTF-8"); ?>'>
                <button type="submit" name="import" value="1" class="btn btn-outline btn-sm">Import</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-layout-bottom.php'; ?>
