<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'mobile' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name   = trim($_POST['name'] ?? '');
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $mobile = normalize_mobile($_POST['mobile'] ?? '');
    $pw     = $_POST['password'] ?? '';
    $cpw    = $_POST['confirm_password'] ?? '';
    $old    = ['name' => $name, 'email' => $email, 'mobile' => $mobile];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Please enter your name (2 to 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $errors[] = 'Please enter a valid email address.';
    if (!valid_mobile($mobile)) $errors[] = 'Please enter a valid mobile number (10 to 15 digits).';
    $errors = array_merge($errors, password_errors($pw));
    if ($pw !== $cpw) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $s = db()->prepare('SELECT email, mobile FROM users WHERE email = ? OR mobile = ?');
        $s->execute([$email, $mobile]);
        foreach ($s->fetchAll() as $row) {
            if ($row['email'] === $email)   $errors[] = 'That email is already registered. Try logging in.';
            if ($row['mobile'] === $mobile) $errors[] = 'That mobile number is already registered. Try logging in.';
        }
    }
    if (!$errors) {
        $ins = db()->prepare('INSERT INTO users (name, email, mobile, password_hash) VALUES (?, ?, ?, ?)');
        $ins->execute([$name, $email, $mobile, password_hash($pw, PASSWORD_DEFAULT)]);
        login_user((int)db()->lastInsertId());
        flash('success', 'Welcome to ' . APP_NAME . '! Let\'s set up your profile.');
        redirect('onboarding.php');
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card">
  <h2>Create your account</h2>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label>Full name
      <input type="text" name="name" value="<?= e($old['name']) ?>" maxlength="100" required autocomplete="name">
    </label>
    <label>Email
      <input type="email" name="email" value="<?= e($old['email']) ?>" maxlength="190" required autocomplete="email">
    </label>
    <label>Mobile number
      <input type="tel" name="mobile" value="<?= e($old['mobile']) ?>" placeholder="9876543210" required autocomplete="tel">
    </label>
    <label>New password
      <input type="password" name="password" minlength="8" required autocomplete="new-password">
      <small>At least 8 characters with a letter and a number.</small>
    </label>
    <label>Confirm password
      <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
    </label>
    <button class="btn btn-block">Create account</button>
  </form>
  <p class="muted center">Already have an account? <a href="login.php">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
