<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$vehicleId = (int)($_GET['vehicle_id'] ?? $_POST['vehicle_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ? AND availability = 'Available'");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlash('error', 'This vehicle is not available for reservation.');
    redirect('vehicles.php');
}

$errors = [];
$old = [
    'pickup_date' => $_GET['pickup_date'] ?? '',
    'return_date' => $_GET['return_date'] ?? '',
    'pickup_location' => $vehicle['pickup_location'],
    'holder_name' => $_SESSION['user_name'] ?? '',
    'license_number' => '',
    'license_type' => 'Nepalese',
    'country_of_issue' => 'Nepal',
    'expiry_date' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $old['pickup_date'] = $_POST['pickup_date'] ?? '';
    $old['return_date'] = $_POST['return_date'] ?? '';
    $old['pickup_location'] = trim($_POST['pickup_location'] ?? '');
    $old['holder_name'] = trim($_POST['holder_name'] ?? '');
    $old['license_number'] = trim($_POST['license_number'] ?? '');
    $old['license_type'] = $_POST['license_type'] ?? 'Nepalese';
    $old['country_of_issue'] = $old['license_type'] === 'Foreign' ? trim($_POST['country_of_issue'] ?? '') : 'Nepal';
    $old['expiry_date'] = $_POST['expiry_date'] ?? '';

    // ---- Validate dates ----
    $today = date('Y-m-d');
    if (empty($old['pickup_date']) || empty($old['return_date'])) {
        $errors[] = 'Please provide both pickup and return dates.';
    } elseif ($old['pickup_date'] < $today) {
        $errors[] = 'Pickup date cannot be in the past.';
    } elseif ($old['return_date'] <= $old['pickup_date']) {
        $errors[] = 'Return date must be after the pickup date.';
    }

    if ($old['pickup_location'] === '') $errors[] = 'Pickup location is required.';
    if ($old['holder_name'] === '') $errors[] = "License holder's name is required.";
    if ($old['license_number'] === '') $errors[] = 'License number is required.';
    if (empty($old['expiry_date']) || $old['expiry_date'] < $today) $errors[] = 'A valid, non-expired license expiry date is required.';
    if ($old['license_type'] === 'Foreign' && $old['country_of_issue'] === '') $errors[] = 'Country of issue is required for foreign licenses.';

    $licenseImage = false;
    if (empty($errors)) {
        $licenseImage = handleImageUpload('license_image', __DIR__ . '/assets/uploads/licenses', 'license');
        if (!$licenseImage) {
            $errors[] = 'Please upload a valid license image (JPG, PNG, or WEBP, max 2MB).';
        }
    }

    // ---- Prevent double booking: check overlapping reservations for this vehicle ----
    if (empty($errors)) {
        $overlap = $pdo->prepare("
            SELECT reservation_id FROM reservations
            WHERE vehicle_id = ?
              AND status IN ('Pending','Approved','Ongoing')
              AND NOT (return_date <= ? OR pickup_date >= ?)
        ");
        $overlap->execute([$vehicleId, $old['pickup_date'], $old['return_date']]);
        if ($overlap->fetch()) {
            $errors[] = 'This vehicle is already reserved for the selected dates. Please choose different dates.';
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Save driving license
            $licStmt = $pdo->prepare("
                INSERT INTO driving_licenses (user_id, holder_name, license_number, license_type, country_of_issue, expiry_date, license_image)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $licStmt->execute([
                $userId, $old['holder_name'], $old['license_number'], $old['license_type'],
                $old['country_of_issue'], $old['expiry_date'], $licenseImage
            ]);
            $licenseId = $pdo->lastInsertId();

            $days = calculateDays($old['pickup_date'], $old['return_date']);
            $total = $days * (float)$vehicle['daily_price'];

            $resStmt = $pdo->prepare("
                INSERT INTO reservations (user_id, vehicle_id, license_id, pickup_date, return_date, pickup_location, total_days, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $resStmt->execute([
                $userId, $vehicleId, $licenseId, $old['pickup_date'], $old['return_date'],
                $old['pickup_location'], $days, $total
            ]);
            $reservationId = $pdo->lastInsertId();

            $pdo->commit();
            redirect('payment.php?reservation_id=' . $reservationId);
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while creating your reservation. Please try again.';
        }
    }
}

$pageTitle = 'Reserve ' . $vehicle['name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:820px;">
    <div class="steps">
      <div class="step active">1. Reservation Details</div>
      <div class="step">2. Payment</div>
      <div class="step">3. Confirmation</div>
    </div>

    <h1>Reserve: <?php echo e($vehicle['name']); ?></h1>
    <p class="muted"><?php echo formatNPR($vehicle['daily_price']); ?> / day &middot; <?php echo e($vehicle['pickup_location']); ?></p>

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
        <ul style="margin:0;padding-left:18px;">
          <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="info-card">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicleId; ?>">

        <h3 style="font-size:1.05rem;">Rental Period</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="pickup_date">Pickup Date</label>
            <input type="date" id="pickup_date" name="pickup_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($old['pickup_date']); ?>" required>
          </div>
          <div class="form-group">
            <label for="return_date">Return Date</label>
            <input type="date" id="return_date" name="return_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($old['return_date']); ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label for="pickup_location">Pickup Location</label>
          <input type="text" id="pickup_location" name="pickup_location" value="<?php echo e($old['pickup_location']); ?>" required>
        </div>
        <p class="form-hint" id="costEstimate" style="margin-bottom:20px;">Estimated cost will appear here once dates are chosen.</p>
        <div id="dailyPrice" data-price="<?php echo e($vehicle['daily_price']); ?>" style="display:none;"></div>

        <h3 style="font-size:1.05rem;margin-top:30px;">Driving License Verification</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="holder_name">License Holder's Name</label>
            <input type="text" id="holder_name" name="holder_name" value="<?php echo e($old['holder_name']); ?>" required>
          </div>
          <div class="form-group">
            <label for="license_number">License Number</label>
            <input type="text" id="license_number" name="license_number" value="<?php echo e($old['license_number']); ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="license_type">License Type</label>
            <select id="license_type" name="license_type">
              <option value="Nepalese" <?php echo $old['license_type'] === 'Nepalese' ? 'selected' : ''; ?>>Nepalese Driving License</option>
              <option value="Foreign" <?php echo $old['license_type'] === 'Foreign' ? 'selected' : ''; ?>>Foreign Driving License</option>
            </select>
          </div>
          <div class="form-group">
            <label for="expiry_date">License Expiry Date</label>
            <input type="date" id="expiry_date" name="expiry_date" value="<?php echo e($old['expiry_date']); ?>" required>
          </div>
        </div>

        <div class="form-group" id="countryOfIssueGroup">
          <label for="country_of_issue">Country of Issue</label>
          <input type="text" id="country_of_issue" name="country_of_issue" value="<?php echo e($old['country_of_issue']); ?>">
        </div>

        <div class="form-group">
          <label for="license_image">Upload License Image</label>
          <input type="file" id="license_image" name="license_image" accept=".jpg,.jpeg,.png,.webp" required>
          <div class="form-hint">JPG, PNG, or WEBP. Max size 2MB. Stored securely for admin verification before your booking is confirmed.</div>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-2">Continue to Payment</button>
      </form>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
