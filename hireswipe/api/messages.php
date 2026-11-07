<?php
require_once __DIR__ . '/bootstrap_api.php';
$me = api_user();
$pdo = db();

function fmt_msg(array $m, int $uid): array {
    return [
        'id'   => (int)$m['id'],
        'mine' => (int)$m['sender_id'] === $uid,
        'body' => $m['body'],
        'time' => date('d M, g:i a', strtotime($m['created_at'])),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = api_body();
    $matchId = (int)($d['match_id'] ?? 0);
    $body = trim((string)($d['body'] ?? ''));
    if (!my_match($matchId, $me['id'])) json_out(['error' => 'Chat not found.'], 404);
    if ($body === '' || mb_strlen($body) > 2000) json_out(['error' => 'Message must be 1 to 2000 characters.'], 400);

    $pdo->prepare('INSERT INTO messages (match_id, sender_id, body) VALUES (?, ?, ?)')->execute([$matchId, $me['id'], $body]);
    $id = (int)$pdo->lastInsertId();
    $row = $pdo->prepare('SELECT * FROM messages WHERE id = ?');
    $row->execute([$id]);
    json_out(['ok' => true, 'message' => fmt_msg($row->fetch(), $me['id'])]);
}

// GET: new messages after a given id
$matchId = (int)($_GET['match'] ?? 0);
$after = (int)($_GET['after'] ?? 0);
if (!my_match($matchId, $me['id'])) json_out(['error' => 'Chat not found.'], 404);

$q = $pdo->prepare('SELECT * FROM messages WHERE match_id = ? AND id > ? ORDER BY id ASC LIMIT 100');
$q->execute([$matchId, $after]);
$rows = $q->fetchAll();

$pdo->prepare('UPDATE messages SET is_read = 1 WHERE match_id = ? AND sender_id <> ? AND is_read = 0')->execute([$matchId, $me['id']]);
json_out(['messages' => array_map(fn($m) => fmt_msg($m, $me['id']), $rows)]);
