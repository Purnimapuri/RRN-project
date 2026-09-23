<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'FAQ';
require_once __DIR__ . '/includes/header.php';

$faqs = [
    ['q' => 'What documents do I need to rent a vehicle?', 'a' => 'You need a valid driving license (Nepalese or foreign) that has not expired. You will upload a photo of it during the reservation process for verification.'],
    ['q' => 'Can foreign tourists rent a vehicle?', 'a' => 'Yes. We accept valid foreign driving licenses. Select "Foreign Driving License" during reservation and provide your country of issue.'],
    ['q' => 'How does driving license verification work?', 'a' => 'After you submit your license details and image, our admin team reviews it before your booking is confirmed. This usually takes a few hours.'],
    ['q' => 'What payment methods are supported?', 'a' => 'We support eSewa and Khalti, Nepal\'s most widely used digital wallets, for secure online payment.'],
    ['q' => 'Can I cancel a reservation?', 'a' => 'Yes, you can cancel a Pending or Approved reservation from the "My Bookings" page before your pickup date.'],
    ['q' => 'What happens if a vehicle is already booked for my dates?', 'a' => 'Our system automatically prevents double bookings. If a vehicle is unavailable for your chosen dates, you will be asked to pick different dates or another vehicle.'],
    ['q' => 'Is there a security deposit?', 'a' => 'Deposit requirements vary by vehicle and are communicated by our team during booking approval.'],
];
?>

<section class="section">
  <div class="container" style="max-width:760px;">
    <h1>Frequently Asked Questions</h1>
    <p class="muted">Everything you need to know before booking with Rent and Ride Nepal.</p>

    <div class="mt-2">
      <?php foreach ($faqs as $f): ?>
        <div class="faq-item">
          <div class="faq-question"><span><?php echo e($f['q']); ?></span><span>+</span></div>
          <div class="faq-answer"><p><?php echo e($f['a']); ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
