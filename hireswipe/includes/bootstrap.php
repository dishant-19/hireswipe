<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const EMPLOYMENT_TYPES = [
    'full_time'  => 'Full time',
    'part_time'  => 'Part time',
    'contract'   => 'Contract',
    'internship' => 'Internship',
    'freelance'  => 'Freelance',
];

const INDUSTRIES = [
    'IT & Software', 'Design & Creative', 'Marketing & Sales', 'Education',
    'Healthcare', 'Finance & Banking', 'Food & Beverage', 'Logistics',
    'Manufacturing', 'Retail', 'Hospitality', 'Other',
];

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }

/* ---------- CSRF protection ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

/* ---------- Flash messages ---------- */
function flash(?string $type = null, ?string $msg = null) {
    if ($type !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Current user ---------- */
function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT * FROM users WHERE id = ?');
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function login_user(int $id): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
}
function require_login(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}
/** Logged in AND finished onboarding. */
function require_ready(): array {
    $u = require_login();
    if (!$u['role'] || !$u['onboarded']) redirect('onboarding.php');
    return $u;
}

/* ---------- Validation helpers ---------- */
function normalize_mobile(string $m): string {
    $m = preg_replace('/[\s\-().]/', '', $m);
    return $m;
}
function valid_mobile(string $m): bool { return (bool)preg_match('/^\+?[0-9]{10,15}$/', $m); }
function password_errors(string $pw): array {
    $err = [];
    if (strlen($pw) < 8)               $err[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pw)) $err[] = 'Password needs at least one letter.';
    if (!preg_match('/[0-9]/', $pw))    $err[] = 'Password needs at least one number.';
    return $err;
}

/* ---------- Files ---------- */
function save_upload(string $field, array $exts, int $maxBytes, array &$errors, string $label): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) { $errors[] = "$label could not be uploaded. Please try again."; return null; }
    if ($f['size'] > $maxBytes) { $errors[] = "$label is too big (maximum " . round($maxBytes / 1048576) . " MB)."; return null; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $exts, true)) { $errors[] = "$label must be one of: " . implode(', ', $exts) . '.'; return null; }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $okMime = [
        'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
    if (!in_array($mime, $okMime[$ext] ?? [], true)) { $errors[] = "$label does not look like a valid $ext file."; return null; }

    // 1) Try the uploads folder. 2) If it is locked, keep the file in the database instead.
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    if (is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR)) {
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        if (@move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) return $name;
    }
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS stored_files (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_id INT UNSIGNED NOT NULL,
            kind ENUM("image","cv") NOT NULL,
            mime VARCHAR(100) NOT NULL,
            filename VARCHAR(190) NOT NULL,
            data LONGBLOB NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_owner (owner_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $u = current_user();
        $st = db()->prepare('INSERT INTO stored_files (owner_id, kind, mime, filename, data) VALUES (?,?,?,?,?)');
        $st->bindValue(1, $u['id'] ?? 0, PDO::PARAM_INT);
        $st->bindValue(2, in_array($ext, ['pdf', 'doc', 'docx'], true) ? 'cv' : 'image');
        $st->bindValue(3, $mime);
        $st->bindValue(4, mb_substr(preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($f['name'])), 0, 180));
        $st->bindValue(5, file_get_contents($f['tmp_name']), PDO::PARAM_LOB);
        $st->execute();
        return 'db:' . db()->lastInsertId();
    } catch (Throwable $ex) {
        error_log('HireSwipe: could not save upload: ' . $ex->getMessage());
        $errors[] = "$label could not be saved. Please try a smaller file or try again.";
        return null;
    }
}

/** Web address for a stored picture or CV (folder file or database file). */
function file_url(?string $v): string {
    if (!$v) return '';
    if (strncmp($v, 'db:', 3) === 0) return 'file.php?id=' . (int)substr($v, 3);
    return UPLOAD_URL . $v;
}

function initials(string $name): string {
    $p = preg_split('/\s+/', trim($name));
    $i = mb_strtoupper(mb_substr($p[0] ?? '?', 0, 1));
    if (count($p) > 1) $i .= mb_strtoupper(mb_substr(end($p), 0, 1));
    return $i;
}
function avatar(string $name, ?string $file, string $class = ''): string {
    if ($file) return '<img class="avatar ' . $class . '" src="' . e(file_url($file)) . '" alt="">';
    return '<span class="avatar avatar-text ' . $class . '">' . e(initials($name)) . '</span>';
}
function money($n): string { return CURRENCY . number_format((int)$n); }

/* ---------- Matching score (0-100) ---------- */
function split_list(string $s): array {
    return array_values(array_filter(array_map('trim', explode(',', $s))));
}
function match_score(array $seeker, array $employer): int {
    $score = 0;
    if (in_array($employer['industry'], split_list($seeker['industries']), true)) $score += 40;
    if ($employer['employment_type'] === $seeker['employment_type']) $score += 30;
    $offer = (int)$employer['salary']; $want = (int)$seeker['expected_salary'];
    if ($offer >= $want) $score += 30;
    elseif ($want > 0 && $offer >= 0.8 * $want) $score += 15;
    return $score;
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
