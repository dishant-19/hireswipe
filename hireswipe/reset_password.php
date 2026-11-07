<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$row = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $s = db()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()');
    $s->execute([hash('sha256', $token)]);
    $row = $s->fetch();
}

$errors = [];
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw  = $_POST['password'] ?? '';
    $cpw = $_POST['confirm_password'] ?? '';
    $errors = password_errors($pw);
    if ($pw !== $cpw) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($pw, PASSWORD_DEFAULT), $row['user_id']]);
        db()->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$row['user_id']]);
        flash('success', 'Your password has been changed. Please log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Reset password';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card">
  <h2>Choose a new password</h2>
  <?php if (!$row): ?>
    <div class="alert alert-error">This reset link is not valid any more.</div>
    <p class="center"><a class="btn" href="forgot_password.php">Get a new link</a></p>
  <?php else: ?>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>New password
        <input type="password" name="password" minlength="8" required autocomplete="new-password">
        <small>At least 8 characters with a letter and a number.</small>
      </label>
      <label>Confirm new password
        <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
      </label>
      <button class="btn btn-block">Save password</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
