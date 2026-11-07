<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_ready();
$isSeeker = $me['role'] === 'seeker';

$pageTitle = 'Swipe';
$bodyClass = 'swipe-page';
$pageScripts = ['assets/js/swipe.js'];
require __DIR__ . '/includes/header.php';
?>
<div id="swipe-app" data-csrf="<?= e(csrf_token()) ?>" data-role="<?= e($me['role']) ?>">
  <p class="muted center swipe-intro">
    <?= $isSeeker ? 'Swipe through companies hiring now.' : 'Swipe through job seekers who fit your role.' ?>
    Drag a card, tap the buttons, or use the arrow keys.
  </p>

  <div id="deck" class="deck" aria-live="polite"></div>

  <div id="empty" class="empty card" hidden>
    <div class="empty-emoji">🎉</div>
    <h3>You're all caught up</h3>
    <p class="muted">No more profiles right now. New people join all the time, so check back soon.</p>
    <p>
      <a class="btn" href="matches.php">See my matches</a>
      <button id="reset-btn" class="btn btn-ghost" type="button">Show passed profiles again</button>
    </p>
  </div>

  <div id="actions" class="swipe-actions" hidden>
    <button id="btn-pass" class="round round-pass" type="button" aria-label="Pass">✕</button>
    <button id="btn-like" class="round round-like" type="button" aria-label="Like">♥</button>
  </div>

  <div id="match-modal" class="modal" hidden>
    <div class="modal-box">
      <div class="confetti">🎉</div>
      <h2>It's a match!</h2>
      <p id="match-text" class="muted"></p>
      <p>
        <a id="match-chat" class="btn" href="#">Say hello</a>
        <button id="match-close" class="btn btn-ghost" type="button">Keep swiping</button>
      </p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
