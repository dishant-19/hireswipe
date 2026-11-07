<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw = $_POST['password'] ?? '';

    // Simple protection against guessing: 5 wrong tries = wait 1 minute
    $lockUntil = $_SESSION['login_lock'] ?? 0;
    if ($lockUntil > time()) {
        $errors[] = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $s = db()->prepare('SELECT * FROM users WHERE email = ?');
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && password_verify($pw, $u['password_hash'])) {
            unset($_SESSION['login_fails'], $_SESSION['login_lock']);
            login_user((int)$u['id']);
            redirect($u['onboarded'] ? 'swipe.php' : 'onboarding.php');
        }
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) { $_SESSION['login_lock'] = time() + 60; $_SESSION['login_fails'] = 0; }
        $errors[] = 'Email or password is not correct.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card">
  <h2>Welcome back</h2>
  <div class="tabs">
    <a class="tab active" href="login.php">Email &amp; password</a>
    <a class="tab" href="otp_login.php">Mobile OTP</a>
  </div>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label>Email
      <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
    </label>
    <label>Password
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <p class="right"><a href="forgot_password.php">Forgot password?</a></p>
    <button class="btn btn-block">Log in</button>
  </form>
  <p class="muted center">New here? <a href="register.php">Create an account</a></p>
  <?php if (DEMO_MODE): ?>
    <p class="hint">Demo logins: <code>seeker1@demo.com</code> or <code>employer1@demo.com</code> with password <code>password123</code></p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
