<?php
require __DIR__ . '/../src/bootstrap.php';

$authed = !empty($_SESSION['admin']);
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Session expired, please try again.';
    } elseif ($action === 'login') {
        $expected = (string) config('admin_password', '');
        if ($expected !== '' && $expected !== 'change-this-admin-password'
            && hash_equals($expected, (string) ($_POST['password'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            redirect('admin.php');
        }
        sleep(1);
        $error = 'Incorrect password.';
    } elseif ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect('admin.php');
    } elseif ($action === 'delete' && $authed) {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('SELECT photo FROM wishes WHERE id = ?');
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            db()->prepare('DELETE FROM wishes WHERE id = ?')->execute([$id]);
            if ($row['photo'] && preg_match('/^[\w.-]+\.jpg$/', $row['photo'])) {
                @unlink(UPLOAD_DIR . '/' . $row['photo']);
            }
        }
        redirect('admin.php');
    }
}

if ($authed && ($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="wishes-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // so Excel opens UTF-8 correctly
    fputcsv($out, ['ID', 'Name', 'Wishes', 'Photo', 'Received']);
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
          . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/uploads/';
    foreach (db()->query('SELECT id, name, message, photo, created_at FROM wishes ORDER BY created_at DESC') as $r) {
        // Guard against spreadsheet formula injection
        $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v;
        fputcsv($out, [$r['id'], $safe($r['name']), $safe($r['message']), $r['photo'] ? $base . $r['photo'] : '', $r['created_at']]);
    }
    exit;
}

$wishes = [];
if ($authed) {
    $wishes = db()->query('SELECT id, name, message, photo, created_at FROM wishes ORDER BY created_at DESC')->fetchAll();
}
$withPhotos = count(array_filter($wishes, fn($w) => $w['photo']));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Wishes · <?= e(couple()) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=1">
</head>
<body class="admin">
<div class="admin-wrap">
    <header class="admin-head">
        <img src="assets/ganesha.png" alt="" width="56">
        <div>
            <h1 class="couple couple-xs"><?= e(couple()) ?></h1>
            <p>Wishes from your guests</p>
        </div>
        <?php if ($authed): ?>
        <form method="post" class="admin-logout">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button name="action" value="logout" class="btn-link">Log out</button>
        </form>
        <?php endif; ?>
    </header>

<?php if (!$authed): ?>
    <form method="post" class="admin-login">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="login">
        <label for="pw">Password</label>
        <input id="pw" type="password" name="password" autocomplete="current-password" required autofocus>
        <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
        <button class="btn btn-primary">View wishes</button>
    </form>
<?php else: ?>
    <div class="admin-bar">
        <p><strong><?= count($wishes) ?></strong> wishes · <strong><?= $withPhotos ?></strong> with photos</p>
        <a class="btn btn-outline btn-sm" href="admin.php?export=csv">Download CSV</a>
    </div>

    <?php if (!$wishes): ?>
        <p class="admin-empty">No wishes yet. Share the link with your guests!</p>
    <?php endif; ?>

    <div class="wish-grid">
    <?php foreach ($wishes as $w): ?>
        <article class="wish-card">
            <?php if ($w['photo']): ?>
                <a href="uploads/<?= e($w['photo']) ?>" target="_blank" rel="noopener">
                    <img src="uploads/<?= e($w['photo']) ?>" alt="Photo from <?= e($w['name']) ?>" loading="lazy">
                </a>
            <?php endif; ?>
            <div class="wish-body">
                <p class="wish-msg"><?= nl2br(e($w['message'])) ?></p>
                <p class="wish-from">— <?= e($w['name']) ?></p>
                <div class="wish-meta">
                    <time datetime="<?= e($w['created_at']) ?>"><?= e(date('j M Y, g:i a', strtotime($w['created_at']))) ?></time>
                    <form method="post" onsubmit="return confirm('Delete this wish permanently?');">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                        <button name="action" value="delete" class="btn-link">Delete</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
</body>
</html>
