<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_ready();
$isSeeker = $me['role'] === 'seeker';

// Each match with the other person's details, last message and unread count
if ($isSeeker) {
    $sql = "SELECT mt.id AS match_id, mt.created_at AS matched_at, u.id AS other_id,
                   ep.company_name AS title, ep.position AS subtitle, ep.logo AS image
            FROM matches mt
            JOIN users u ON u.id = mt.employer_id
            JOIN employer_profiles ep ON ep.user_id = u.id
            WHERE mt.seeker_id = ?";
} else {
    $sql = "SELECT mt.id AS match_id, mt.created_at AS matched_at, u.id AS other_id,
                   u.name AS title, COALESCE(sp.headline, sp.qualification) AS subtitle, sp.photo AS image
            FROM matches mt
            JOIN users u ON u.id = mt.seeker_id
            JOIN seeker_profiles sp ON sp.user_id = u.id
            WHERE mt.employer_id = ?";
}
$q = db()->prepare($sql);
$q->execute([$me['id']]);
$list = $q->fetchAll();

$last = db()->prepare('SELECT body, sender_id, created_at FROM messages WHERE match_id = ? ORDER BY id DESC LIMIT 1');
$unreadQ = db()->prepare('SELECT COUNT(*) FROM messages WHERE match_id = ? AND sender_id <> ? AND is_read = 0');
foreach ($list as &$m) {
    $last->execute([$m['match_id']]);
    $m['last'] = $last->fetch() ?: null;
    $unreadQ->execute([$m['match_id'], $me['id']]);
    $m['unread'] = (int)$unreadQ->fetchColumn();
    $m['sort'] = $m['last']['created_at'] ?? $m['matched_at'];
}
unset($m);
usort($list, fn($a, $b) => strcmp($b['sort'], $a['sort']));

$pageTitle = 'Matches';
require __DIR__ . '/includes/header.php';
?>
<h2>Your matches</h2>
<?php if (!$list): ?>
  <div class="card empty">
    <div class="empty-emoji">💌</div>
    <h3>No matches yet</h3>
    <p class="muted">When you and someone like each other, they'll show up here so you can chat.</p>
    <p><a class="btn" href="swipe.php">Start swiping</a></p>
  </div>
<?php else: ?>
  <div class="match-list">
  <?php foreach ($list as $m): ?>
    <a class="match-item card" href="chat.php?match=<?= (int)$m['match_id'] ?>">
      <?= avatar($m['title'], $m['image']) ?>
      <div class="match-info">
        <strong><?= e($m['title']) ?></strong>
        <span class="muted small"><?= e($m['subtitle']) ?></span>
        <span class="preview">
          <?php if ($m['last']): ?>
            <?= $m['last']['sender_id'] == $me['id'] ? 'You: ' : '' ?><?= e(mb_strimwidth($m['last']['body'], 0, 70, '…')) ?>
          <?php else: ?>
            <em>You matched! Say hello 👋</em>
          <?php endif; ?>
        </span>
      </div>
      <?php if ($m['unread']): ?><span class="badge"><?= $m['unread'] ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
