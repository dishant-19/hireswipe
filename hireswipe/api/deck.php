<?php
require_once __DIR__ . '/bootstrap_api.php';
$me = api_user();
$pdo = db();
$cards = [];

if ($me['role'] === 'seeker') {
    $mine = $pdo->prepare('SELECT * FROM seeker_profiles WHERE user_id = ?');
    $mine->execute([$me['id']]);
    $mine = $mine->fetch();

    $q = $pdo->prepare(
        "SELECT u.id, e.* FROM users u JOIN employer_profiles e ON e.user_id = u.id
         WHERE u.role = 'employer' AND u.onboarded = 1
           AND u.id NOT IN (SELECT target_id FROM swipes WHERE swiper_id = ?)
         LIMIT 200"
    );
    $q->execute([$me['id']]);
    foreach ($q->fetchAll() as $r) {
        $cards[] = [
            'id'       => (int)$r['id'],
            'title'    => $r['company_name'],
            'subtitle' => $r['position'],
            'location' => $r['location'],
            'image'    => $r['logo'] ? file_url($r['logo']) : null,
            'initials' => initials($r['company_name']),
            'tags'     => [$r['industry'], EMPLOYMENT_TYPES[$r['employment_type']] ?? ''],
            'facts'    => [['Salary offered', money($r['salary']) . ' / month']],
            'text'     => $r['job_detail'],
            'score'    => match_score($mine, $r),
        ];
    }
} else {
    $mine = $pdo->prepare('SELECT * FROM employer_profiles WHERE user_id = ?');
    $mine->execute([$me['id']]);
    $mine = $mine->fetch();

    $q = $pdo->prepare(
        "SELECT u.id, u.name, s.* FROM users u JOIN seeker_profiles s ON s.user_id = u.id
         WHERE u.role = 'seeker' AND u.onboarded = 1
           AND u.id NOT IN (SELECT target_id FROM swipes WHERE swiper_id = ?)
         LIMIT 200"
    );
    $q->execute([$me['id']]);
    foreach ($q->fetchAll() as $r) {
        $cards[] = [
            'id'       => (int)$r['id'],
            'title'    => $r['name'] . ', ' . $r['age'],
            'subtitle' => $r['headline'] ?: $r['qualification'],
            'location' => $r['location'],
            'image'    => $r['photo'] ? file_url($r['photo']) : null,
            'initials' => initials($r['name']),
            'tags'     => array_merge([EMPLOYMENT_TYPES[$r['employment_type']] ?? ''], split_list($r['industries'])),
            'facts'    => [
                ['Qualification', $r['qualification']],
                ['Experience', rtrim(rtrim(number_format((float)$r['experience_years'], 1), '0'), '.') . ' years'],
                ['Expects', money($r['expected_salary']) . ' / month'],
            ],
            'text'     => (string)$r['bio'],
            'score'    => match_score($r, $mine),
        ];
    }
}

// Best-fitting profiles first
usort($cards, fn($a, $b) => $b['score'] <=> $a['score']);
json_out(['cards' => array_slice($cards, 0, 30)]);
