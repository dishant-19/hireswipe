<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('swipe.php');
$pageTitle = 'Find your match';
$bodyClass = 'landing';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Swipe right on your <span class="grad">next job</span> or your <span class="grad">next hire</span></h1>
  <p class="lead">HireSwipe connects people looking for work with companies looking for talent. Like a profile, and if they like you back, it's a match, and you can start chatting right away.</p>
  <div class="hero-actions">
    <a class="btn" href="register.php">Get started free</a>
    <a class="btn btn-ghost" href="login.php">I already have an account</a>
  </div>
</section>

<section class="steps">
  <div class="step"><div class="step-n">1</div><h3>Create your profile</h3><p>Job seekers share their skills and expected salary. Companies share the role they want to fill.</p></div>
  <div class="step"><div class="step-n">2</div><h3>Swipe</h3><p>Swipe right to like, left to pass. We show the best-fitting profiles first.</p></div>
  <div class="step"><div class="step-n">3</div><h3>Match and chat</h3><p>When both sides like each other, a private chat opens so you can talk about the job.</p></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
