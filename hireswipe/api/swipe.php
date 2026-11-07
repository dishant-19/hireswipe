<?php
require_once __DIR__ . '/bootstrap_api.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Not allowed.'], 405);

$me = api_user();
$d = api_body();
$target = (int)($d['target_id'] ?? 0);
$dir = $d['direction'] ?? '';
if (!$target || !in_array($dir, ['like', 'pass'], true)) json_out(['error' => 'Invalid request.'], 400);

$pdo = db();

// The other person must exist, be finished with setup, and be on the opposite side
$t = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ? AND onboarded = 1');
$t->execute([$target]);
$other = $t->fetch();
$wanted = $me['role'] === 'seeker' ? 'employer' : 'seeker';
if (!$other || $other['role'] !== $wanted) json_out(['error' => 'That profile is not available.'], 404);

$pdo->prepare(
    'INSERT INTO swipes (swiper_id, target_id, direction) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE direction = VALUES(direction), created_at = CURRENT_TIMESTAMP'
)->execute([$me['id'], $target, $dir]);

$matched = false; $matchId = null; $otherName = $other['name'];

if ($dir === 'like') {
    $back = $pdo->prepare("SELECT 1 FROM swipes WHERE swiper_id = ? AND target_id = ? AND direction = 'like'");
    $back->execute([$target, $me['id']]);
    if ($back->fetchColumn()) {
        $seekerId   = $me['role'] === 'seeker' ? $me['id'] : $target;
        $employerId = $me['role'] === 'seeker' ? $target : $me['id'];
        $pdo->prepare('INSERT IGNORE INTO matches (seeker_id, employer_id) VALUES (?, ?)')->execute([$seekerId, $employerId]);
        $m = $pdo->prepare('SELECT id FROM matches WHERE seeker_id = ? AND employer_id = ?');
        $m->execute([$seekerId, $employerId]);
        $matchId = (int)$m->fetchColumn();
        $matched = true;

        // Show the company name (not the HR contact name) when the other side is an employer
        if ($other['role'] === 'employer') {
            $c = $pdo->prepare('SELECT company_name FROM employer_profiles WHERE user_id = ?');
            $c->execute([$target]);
            $otherName = $c->fetchColumn() ?: $otherName;
        }
    }
}

json_out(['ok' => true, 'matched' => $matched, 'match_id' => $matchId, 'name' => $otherName]);
