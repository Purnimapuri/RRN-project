<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($message === '') $errors[] = 'Message cannot be empty.';

    // In a production system this would be inserted into a `contact_messages`
    // table or sent via a mail library (e.g. PHPMailer). Kept simple here.
    if (empty($errors)) {
        $sent = true;
    }
}

$pageTitle = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:640px;">
    <h1>Contact Us</h1>
    <p class="muted">Have a question about a booking, a vehicle, or partnering with us? Send us a message.</p>

    <?php if ($sent): ?>
      <div class="flash flash-success" style="border-radius:6px;margin-bottom:16px;">Thanks for reaching out! Our team will get back to you within 24 hours.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error" style="border-radius:6px;margin-bottom:16px;">
        <ul style="margin:0;padding-left:18px;">
          <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="info-card">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <div class="form-group">
          <label for="name">Your Name</label>
          <input type="text" id="name" name="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="5" required><?php echo e($_POST['message'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Message</button>
      </form>
    </div>

    <div class="info-card mt-2">
      <h3 style="font-size:1rem;">Other Ways to Reach Us</h3>
      <p class="muted mb-0">Thamel, Kathmandu, Nepal<br>+977-1-4123456<br>support@rentandridenepal.com</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
