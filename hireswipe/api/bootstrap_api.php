<?php
require_once __DIR__ . '/../includes/bootstrap.php';

/** Returns the logged-in, onboarded user or stops with a JSON error. */
function api_user(): array {
    $u = current_user();
    if (!$u || !$u['role'] || !$u['onboarded']) json_out(['error' => 'Please log in again.'], 401);
    return $u;
}

/** Reads a JSON request body and checks the CSRF header. */
function api_body(): array {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
        json_out(['error' => 'Session expired. Please refresh the page.'], 419);
    }
    $d = json_decode(file_get_contents('php://input'), true);
    return is_array($d) ? $d : [];
}

/** Returns the match row if $uid belongs to it, otherwise null. */
function my_match(int $matchId, int $uid): ?array {
    $s = db()->prepare('SELECT * FROM matches WHERE id = ? AND (seeker_id = ? OR employer_id = ?)');
    $s->execute([$matchId, $uid, $uid]);
    return $s->fetch() ?: null;
}
