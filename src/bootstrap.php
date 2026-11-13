<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/public/uploads');

$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config.php — copy config.sample.php to config.php and fill in your database details.');
}
$CONFIG = require $configFile;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function config(string $key, $default = null)
{
    global $CONFIG;
    $value = $CONFIG;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = config('db.dsn') ?: sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('db.host', '127.0.0.1'),
            (int) config('db.port', 3306),
            config('db.name', 'engagement')
        );
        $pdo = new PDO($dsn, config('db.user'), config('db.password'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function couple(): string
{
    return config('event.bride') . ' & ' . config('event.groom');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function str_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen(utf8_decode($s));
}

/**
 * Validate an uploaded image, re-encode it as JPEG (strips metadata such as GPS,
 * fixes phone rotation, shrinks huge photos) and return the stored file name.
 */
function store_photo(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That photo is too large. Please choose a smaller one.',
            UPLOAD_ERR_PARTIAL => 'The photo did not finish uploading. Please try again.',
            default => 'We could not receive your photo. Please try again.',
        });
    }
    $maxBytes = (int) config('max_upload_mb', 10) * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('That photo is too large. Please choose one under ' . config('max_upload_mb', 10) . ' MB.');
    }

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
    if (!$info || !in_array($info[2], $allowed, true)) {
        throw new RuntimeException('Please upload a JPG, PNG or WEBP photo.');
    }
    if ($info[0] * $info[1] > 50_000_000) {
        throw new RuntimeException('That photo is too large. Please choose a smaller one.');
    }

    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
        IMAGETYPE_GIF  => @imagecreatefromgif($file['tmp_name']),
    };
    if (!$src) {
        throw new RuntimeException('We could not read that photo. Please try another one.');
    }

    // Respect the camera's rotation flag (phones store portrait shots sideways).
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $rotated = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($src, 180, 0),
            6 => imagerotate($src, -90, 0),
            8 => imagerotate($src, 90, 0),
            default => null,
        };
        if ($rotated) {
            imagedestroy($src);
            $src = $rotated;
        }
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $max = 1600;
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // flatten transparency onto white
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.jpg';
    if (!imagejpeg($dst, UPLOAD_DIR . '/' . $name, 85)) {
        imagedestroy($dst);
        throw new RuntimeException('We could not save your photo. Please try again.');
    }
    imagedestroy($dst);
    return $name;
}
