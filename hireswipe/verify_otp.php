<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');

$mobile = $_SESSION['otp_mobile'] ?? '';
if ($mobile === '') redirect('otp_login.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = trim($_POST['code'] ?? '');
    if (!preg_match('/^[0-9]{6}$/', $code)) {
        $errors[] = 'Please enter the 6-digit code.';
    } else {
        $s = db()->prepare('SELECT * FROM otp_codes WHERE mobile = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $s->execute([$mobile]);
        $row = $s->fetch();

        if (!$row || $row['attempts'] >= 5) {
            $errors[] = 'That code has expired or was tried too many times. Please ask for a new one.';
        } elseif (!password_verify($code, $row['code_hash'])) {
            db()->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
            $errors[] = 'That code is not correct.';
        } else {
            db()->prepare('UPDATE otp_codes SET used = 1 WHERE id = ?')->execute([$row['id']]);
            $u = db()->prepare('SELECT * FROM users WHERE mobile = ?');
            $u->execute([$mobile]);
            $user = $u->fetch();
            if ($user) {
                unset($_SESSION['otp_mobile']);
                login_user((int)$user['id']);
                redirect($user['onboarded'] ? 'swipe.php' : 'onboarding.php');
            }
            $errors[] = 'No account found for this number. Please sign up first.';
        }
    }
}

$pageTitle = 'Enter OTP';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card">
  <h2>Enter your code</h2>
  <p class="muted">We sent a 6-digit code to <strong><?= e($mobile) ?></strong>. It works for 5 minutes.</p>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label>6-digit code
      <input type="text" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="otp-input" required autofocus autocomplete="one-time-code">
    </label>
    <button class="btn btn-block">Verify and log in</button>
  </form>
  <p class="muted center"><a href="otp_login.php">Use a different number or resend</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
