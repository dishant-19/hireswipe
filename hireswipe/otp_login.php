<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');

$errors = [];
$mobile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mobile = normalize_mobile($_POST['mobile'] ?? '');
    if (!valid_mobile($mobile)) {
        $errors[] = 'Please enter a valid mobile number (10 to 15 digits).';
    } else {
        // Allow only one new code every 30 seconds per number
        $recent = db()->prepare('SELECT COUNT(*) FROM otp_codes WHERE mobile = ? AND created_at > (NOW() - INTERVAL 30 SECOND)');
        $recent->execute([$mobile]);
        if ($recent->fetchColumn() > 0) {
            $errors[] = 'A code was just sent. Please wait 30 seconds before asking for another.';
        } else {
            $s = db()->prepare('SELECT id FROM users WHERE mobile = ?');
            $s->execute([$mobile]);
            $exists = (bool)$s->fetch();

            $code = (string)random_int(100000, 999999);
            $ins = db()->prepare('INSERT INTO otp_codes (mobile, code_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 5 MINUTE)');
            $ins->execute([$mobile, password_hash($code, PASSWORD_DEFAULT)]);

            // TODO (real launch): send $code by SMS here using your SMS provider.
            if (DEMO_MODE && $exists) flash('info', "Demo mode: your OTP is $code (no SMS service is connected yet).");
            else flash('info', 'If this number is registered, a 6-digit code has been sent.');

            $_SESSION['otp_mobile'] = $mobile;
            redirect('verify_otp.php');
        }
    }
}

$pageTitle = 'Log in with OTP';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card">
  <h2>Welcome back</h2>
  <div class="tabs">
    <a class="tab" href="login.php">Email &amp; password</a>
    <a class="tab active" href="otp_login.php">Mobile OTP</a>
  </div>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label>Mobile number
      <input type="tel" name="mobile" value="<?= e($mobile) ?>" placeholder="9876543210" required autocomplete="tel">
    </label>
    <button class="btn btn-block">Send OTP</button>
  </form>
  <p class="muted center">Prefer a password? <a href="login.php">Log in with email</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
