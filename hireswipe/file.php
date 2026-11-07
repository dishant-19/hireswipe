<?php
// Shows pictures and CVs that are kept in the database.
require_once __DIR__ . '/includes/bootstrap.php';
$me = current_user();
if (!$me) { http_response_code(403); exit('Please log in.'); }

$id = (int)($_GET['id'] ?? 0);
try {
    $s = db()->prepare('SELECT * FROM stored_files WHERE id = ?');
    $s->execute([$id]);
    $f = $s->fetch();
} catch (Throwable $ex) { $f = null; }
if (!$f) { http_response_code(404); exit('File not found.'); }

// Pictures and logos can be seen by any logged-in person.
// A CV can only be opened by its owner or by someone they matched with.
if ($f['kind'] === 'cv' && (int)$f['owner_id'] !== (int)$me['id']) {
    $m = db()->prepare('SELECT 1 FROM matches WHERE (seeker_id = ? AND employer_id = ?) OR (seeker_id = ? AND employer_id = ?)');
    $m->execute([$f['owner_id'], $me['id'], $me['id'], $f['owner_id']]);
    if (!$m->fetchColumn()) { http_response_code(403); exit('You can only open CVs of people you matched with.'); }
}

header('Content-Type: ' . $f['mime']);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($f['data']));
header('Cache-Control: private, max-age=3600');
$disp = $f['kind'] === 'cv' ? 'attachment' : 'inline';
header('Content-Disposition: ' . $disp . '; filename="' . $f['filename'] . '"');
echo $f['data'];
