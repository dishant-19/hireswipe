<?php
require_once __DIR__ . '/bootstrap.php';
$me = current_user();
$unread = 0;
if ($me && $me['role'] && $me['onboarded']) {
    $q = db()->prepare(
        'SELECT COUNT(*) FROM messages m JOIN matches mt ON mt.id = m.match_id
         WHERE (mt.seeker_id = ? OR mt.employer_id = ?) AND m.sender_id <> ? AND m.is_read = 0'
    );
    $q->execute([$me['id'], $me['id'], $me['id']]);
    $unread = (int)$q->fetchColumn();
}
$pageTitle = $pageTitle ?? APP_NAME;
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="topbar">
  <a class="logo" href="<?= $me ? 'swipe.php' : 'index.php' ?>"><span class="logo-mark">♥</span> <?= APP_NAME ?></a>
  <nav>
  <?php if ($me && $me['role'] && $me['onboarded']): ?>
    <a href="swipe.php" class="<?= $current === 'swipe.php' ? 'active' : '' ?>">Swipe</a>
    <a href="matches.php" class="<?= in_array($current, ['matches.php', 'chat.php']) ? 'active' : '' ?>">Matches<?php if ($unread): ?> <span class="badge"><?= $unread ?></span><?php endif; ?></a>
    <a href="profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>">My profile</a>
  <?php endif; ?>
  <?php if ($me): ?>
    <form method="post" action="logout.php" class="inline"><?= csrf_field() ?><button class="link-btn">Log out</button></form>
  <?php else: ?>
    <a href="login.php">Log in</a>
    <a href="register.php" class="btn btn-small">Sign up</a>
  <?php endif; ?>
  </nav>
</header>
<main class="container">
<?php foreach (flash() as [$type, $msg]): ?>
  <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
<?php endforeach; ?>
