<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();
if ($me['role'] && $me['onboarded']) redirect('swipe.php');
if ($me['role']) redirect('profile.php');   // role chosen, profile not finished

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $role = $_POST['role'] ?? '';
    if (in_array($role, ['seeker', 'employer'], true)) {
        db()->prepare('UPDATE users SET role = ? WHERE id = ? AND role IS NULL')->execute([$role, $me['id']]);
        redirect('profile.php');
    }
    flash('error', 'Please choose one option to continue.');
}

$pageTitle = 'Choose your path';
require __DIR__ . '/includes/header.php';
?>
<div class="center-block">
  <h2 class="center">How would you like to use <?= APP_NAME ?>?</h2>
  <p class="muted center">You can't change this later, so pick the one that fits you.</p>
  <form method="post" class="role-grid">
    <?= csrf_field() ?>
    <button name="role" value="seeker" class="role-card">
      <span class="role-emoji">🙋</span>
      <strong>I'm looking for a job</strong>
      <span>Show your profile to companies and swipe through open roles.</span>
    </button>
    <button name="role" value="employer" class="role-card">
      <span class="role-emoji">🏢</span>
      <strong>I'm hiring</strong>
      <span>Post your role and swipe through job seekers who fit.</span>
    </button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
