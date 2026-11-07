<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_ready();
$isSeeker = $me['role'] === 'seeker';
$matchId = (int)($_GET['match'] ?? $_POST['match'] ?? 0);

$s = db()->prepare('SELECT * FROM matches WHERE id = ? AND (seeker_id = ? OR employer_id = ?)');
$s->execute([$matchId, $me['id'], $me['id']]);
$match = $s->fetch();
if (!$match) { flash('error', 'That chat was not found.'); redirect('matches.php'); }

// Works even without JavaScript: plain form post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $body = trim($_POST['body'] ?? '');
    if ($body !== '' && mb_strlen($body) <= 2000) {
        db()->prepare('INSERT INTO messages (match_id, sender_id, body) VALUES (?, ?, ?)')->execute([$matchId, $me['id'], $body]);
    }
    redirect('chat.php?match=' . $matchId);
}

// Details of the other person
if ($isSeeker) {
    $o = db()->prepare('SELECT ep.company_name AS title, ep.position AS subtitle, ep.logo AS image, ep.location, ep.salary, ep.employment_type, ep.job_detail
                        FROM employer_profiles ep WHERE ep.user_id = ?');
    $o->execute([$match['employer_id']]);
} else {
    $o = db()->prepare('SELECT u.name AS title, COALESCE(sp.headline, sp.qualification) AS subtitle, sp.photo AS image, sp.location,
                               sp.expected_salary, sp.employment_type, sp.qualification, sp.experience_years, sp.cv
                        FROM seeker_profiles sp JOIN users u ON u.id = sp.user_id WHERE sp.user_id = ?');
    $o->execute([$match['seeker_id']]);
}
$other = $o->fetch();

$msgs = db()->prepare('SELECT * FROM messages WHERE match_id = ? ORDER BY id ASC');
$msgs->execute([$matchId]);
$msgs = $msgs->fetchAll();
db()->prepare('UPDATE messages SET is_read = 1 WHERE match_id = ? AND sender_id <> ? AND is_read = 0')->execute([$matchId, $me['id']]);
$lastId = $msgs ? (int)end($msgs)['id'] : 0;

$pageTitle = 'Chat with ' . $other['title'];
$bodyClass = 'chat-page';
$pageScripts = ['assets/js/chat.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="chat" id="chat" data-match="<?= $matchId ?>" data-last="<?= $lastId ?>" data-csrf="<?= e(csrf_token()) ?>">
  <div class="chat-head card">
    <a href="matches.php" class="back" aria-label="Back to matches">←</a>
    <?= avatar($other['title'], $other['image']) ?>
    <div class="chat-title">
      <strong><?= e($other['title']) ?></strong>
      <span class="muted small"><?= e($other['subtitle']) ?> · <?= e($other['location']) ?></span>
    </div>
    <?php if (!$isSeeker && !empty($other['cv'])): ?>
      <a class="btn btn-small btn-ghost" href="<?= e(file_url($other['cv'])) ?>" target="_blank" rel="noopener">View CV</a>
    <?php endif; ?>
  </div>

  <div class="chat-box" id="chat-box">
    <?php if (!$msgs): ?><p class="muted center" id="chat-empty">You matched! Send the first message 👋</p><?php endif; ?>
    <?php foreach ($msgs as $m): ?>
      <div class="bubble <?= $m['sender_id'] == $me['id'] ? 'mine' : 'theirs' ?>">
        <?= nl2br(e($m['body'])) ?>
        <time><?= e(date('d M, g:i a', strtotime($m['created_at']))) ?></time>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="post" class="chat-form" id="chat-form" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="match" value="<?= $matchId ?>">
    <textarea name="body" id="chat-input" rows="1" maxlength="2000" placeholder="Type a message…" required></textarea>
    <button class="btn">Send</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
