<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');

$errors = [];
$email = '';
$demoLink = null;
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $s = db()->prepare('SELECT id FROM users WHERE email = ?');
        $s->execute([$email]);
        if ($u = $s->fetch()) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 30 MINUTE)')
                ->execute([$u['id'], hash('sha256', $token)]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/reset_password.php?token=' . $token;
            // TODO (real launch): email $link to the user instead of showing it.
            if (DEMO_MODE) $demoLink = $link;
        }
        $sent = true;
    }
}

$pageTitle = 'Forgot password';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card">
  <h2>Forgot your password?</h2>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <?php if ($sent): ?>
    <div class="alert alert-success">If that email is registered, a reset link has been sent. It works for 30 minutes.</div>
    <?php if ($demoLink): ?>
      <div class="alert alert-info">Demo mode (no email service connected): <a href="<?= e($demoLink) ?>">open your reset link</a></div>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted">Enter your email and we'll help you choose a new password.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label>Email
        <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
      </label>
      <button class="btn btn-block">Send reset link</button>
    </form>
  <?php endif; ?>
  <p class="muted center"><a href="login.php">Back to log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
