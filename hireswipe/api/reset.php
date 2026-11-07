<?php
require_once __DIR__ . '/bootstrap_api.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Not allowed.'], 405);

$me = api_user();
api_body();   // checks the security token

// Bring back only the profiles this person passed on (likes are kept)
db()->prepare("DELETE FROM swipes WHERE swiper_id = ? AND direction = 'pass'")->execute([$me['id']]);
json_out(['ok' => true]);
