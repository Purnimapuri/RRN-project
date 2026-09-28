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

// ---- NEW: does this user already have a usable license saved? ----
$today = date('Y-m-d');
$licLookup = $pdo->prepare("SELECT * FROM driving_licenses WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
$licLookup->execute([$userId]);
$existingLicense = $licLookup->fetch();

// Reuse the saved license if it is Verified or still Pending, and not expired.
// Rejected, expired, or missing license => the upload form is shown again.
$canReuse = $existingLicense
    && in_array($existingLicense['verification_status'], ['Verified', 'Pending'], true)
    && $existingLicense['expiry_date'] >= $today;
$req = $canReuse ? '' : 'required';   // hidden fields must not be "required"
// ------------------------------------------------------------------

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
    if (empty($old['pickup_date']) || empty($old['return_date'])) {
        $errors[] = 'Please provide both pickup and return dates.';
    } elseif ($old['pickup_date'] < $today) {
        $errors[] = 'Pickup date cannot be in the past.';
    } elseif ($old['return_date'] <= $old['pickup_date']) {
        $errors[] = 'Return date must be after the pickup date.';
    }

    if ($old['pickup_location'] === '') $errors[] = 'Pickup location is required.';

    // ---- License checks: only when a NEW license is being submitted ----
    if (!$canReuse) {
        if ($old['holder_name'] === '') $errors[] = "License holder's name is required.";
        if ($old['license_number'] === '') $errors[] = 'License number is required.';
        if (empty($old['expiry_date']) || $old['expiry_date'] < $today) $errors[] = 'A valid, non-expired license expiry date is required.';
        if ($old['license_type'] === 'Foreign' && $old['country_of_issue'] === '') $errors[] = 'Country of issue is required for foreign licenses.';
    }

    $licenseImage = false;
    if (empty($errors) && !$canReuse) {
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
            if ($canReuse) {
                // Use the license already saved in the database
                $licenseId = $existingLicense['license_id'];
            } else {
                // Save the new driving license
                $licStmt = $pdo->prepare("
                    INSERT INTO driving_licenses (user_id, holder_name, license_number, license_type, country_of_issue, expiry_date, license_image)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $licStmt->execute([
                    $userId, $old['holder_name'], $old['license_number'], $old['license_type'],
                    $old['country_of_issue'], $old['expiry_date'], $licenseImage
                ]);
                $licenseId = $pdo->lastInsertId();
            }

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

// ---- NEW: dates were already chosen on the vehicle page, so just show them ----
$lockDates = empty($errors)
    && $old['pickup_date'] !== '' && $old['return_date'] !== ''
    && $old['pickup_date'] >= $today && $old['return_date'] > $old['pickup_date'];
if ($lockDates) {
    $lockDays  = calculateDays($old['pickup_date'], $old['return_date']);
    $lockTotal = $lockDays * (float)$vehicle['daily_price'];
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
        <?php if ($lockDates): ?>
          <!-- Dates come from the vehicle page: shown as text, sent as hidden fields -->
          <input type="hidden" id="pickup_date" name="pickup_date" value="<?php echo e($old['pickup_date']); ?>">
          <input type="hidden" id="return_date" name="return_date" value="<?php echo e($old['return_date']); ?>">
          <div class="form-row" style="margin-bottom:12px;">
            <div class="form-group">
              <label>Pickup Date</label>
              <div><strong><?php echo e(date('d M Y', strtotime($old['pickup_date']))); ?></strong></div>
            </div>
            <div class="form-group">
              <label>Return Date</label>
              <div><strong><?php echo e(date('d M Y', strtotime($old['return_date']))); ?></strong></div>
            </div>
          </div>
        <?php else: ?>
          <!-- Fallback: no valid dates were passed, so let the user choose here -->
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
        <?php endif; ?>
        <div class="form-group">
          <label for="pickup_location">Pickup Location</label>
          <input type="text" id="pickup_location" name="pickup_location" value="<?php echo e($old['pickup_location']); ?>" required>
        </div>
        <p class="form-hint" id="costEstimate" style="margin-bottom:20px;"><?php echo $lockDates
          ? $lockDays . ' day(s) &times; ' . formatNPR($vehicle['daily_price']) . ' = <strong>' . formatNPR($lockTotal) . '</strong>'
          : 'Estimated cost will appear here once dates are chosen.'; ?></p>
        <div id="dailyPrice" data-price="<?php echo e($vehicle['daily_price']); ?>" style="display:none;"></div>

        <h3 style="font-size:1.05rem;margin-top:30px;">Driving License Verification</h3>

        <?php if ($canReuse): ?>
          <div class="flash flash-success" style="border-radius:6px;margin-bottom:16px;">
            <?php if ($existingLicense['verification_status'] === 'Verified'): ?>
              Your license (No. <?php echo e($existingLicense['license_number']); ?>) is already verified. You do not need to upload it again.
            <?php else: ?>
              Your license (No. <?php echo e($existingLicense['license_number']); ?>) has been submitted and is waiting for admin verification. You do not need to upload it again.
            <?php endif; ?>
          </div>
        <?php elseif ($existingLicense && $existingLicense['verification_status'] === 'Rejected'): ?>
          <p class="form-hint">Your previous license was rejected<?php echo $existingLicense['admin_remarks'] ? ': ' . e($existingLicense['admin_remarks']) : ''; ?>. Please upload it again.</p>
        <?php elseif ($existingLicense): ?>
          <p class="form-hint">Your saved license has expired. Please upload a valid one.</p>
        <?php endif; ?>

        <!-- Kept in the page (hidden) so the footer JavaScript keeps working -->
        <div id="licenseFields" style="<?php echo $canReuse ? 'display:none;' : ''; ?>">
          <div class="form-row">
            <div class="form-group">
              <label for="holder_name">License Holder's Name</label>
              <input type="text" id="holder_name" name="holder_name" value="<?php echo e($old['holder_name']); ?>" <?php echo $req; ?>>
            </div>
            <div class="form-group">
              <label for="license_number">License Number</label>
              <input type="text" id="license_number" name="license_number" value="<?php echo e($old['license_number']); ?>" <?php echo $req; ?>>
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
              <input type="date" id="expiry_date" name="expiry_date" value="<?php echo e($old['expiry_date']); ?>" <?php echo $req; ?>>
            </div>
          </div>

          <div class="form-group" id="countryOfIssueGroup">
            <label for="country_of_issue">Country of Issue</label>
            <input type="text" id="country_of_issue" name="country_of_issue" value="<?php echo e($old['country_of_issue']); ?>">
          </div>

          <div class="form-group">
            <label for="license_image">Upload License Image</label>
            <input type="file" id="license_image" name="license_image" accept=".jpg,.jpeg,.png,.webp" <?php echo $req; ?>>
            <div class="form-hint">JPG, PNG, or WEBP. Max size 2MB. Stored securely for admin verification before your booking is confirmed.</div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-2">Continue to Payment</button>
      </form>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>