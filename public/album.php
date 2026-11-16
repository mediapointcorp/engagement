<?php
/**
 * Wishes album: builds a print-quality PDF (A4) of every wish, with a
 * front cover, wish pages and a back cover, then downloads it automatically.
 *
 *   album.php            → builds the PDF and downloads it
 *   album.php?preview=1  → shows the pages on screen, with a Download button
 *
 * Open to anyone with the link, unless 'album_key' is set in config.php.
 */
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

// Optional protection: if 'album_key' is set in config.php, the link must end with ?key=THAT-VALUE
$albumKey = (string) config('album_key', '');
if ($albumKey !== '' && !hash_equals($albumKey, (string) ($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('This album link is not valid.');
}
header('Cache-Control: no-store');

$preview = isset($_GET['preview']);
$wishes  = db()->query('SELECT id, name, message, photo, created_at FROM wishes ORDER BY created_at ASC, id ASC')->fetchAll();
$photos  = array_values(array_filter($wishes, fn($w) => $w['photo'] && is_file(UPLOAD_DIR . '/' . $w['photo'])));
$fan     = array_slice($photos, 0, 5); // guest photos shown on the front cover
$bride   = (string) config('event.bride');
$groom   = (string) config('event.groom');
$fileName = preg_replace('/[^A-Za-z0-9]+/', '-', "$bride $groom Engagement Wishes") . '.pdf';

function initials(string $name): string
{
    $name = trim($name);
    $first = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($first, 'UTF-8') : strtoupper($first);
}

function message_size(string $msg): string
{
    $n = str_len($msg);
    return $n <= 170 ? 'msg-l' : ($n <= 300 ? 'msg-m' : ($n <= 420 ? 'msg-s' : 'msg-xs'));
}

$families = config('event.families', []);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Wishes Album · <?= e(couple()) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Marcellus&family=Playfair+Display:ital,wght@1,400;1,600;1,700&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/album.css?v=5">
</head>
<body class="<?= $preview ? 'mode-preview' : 'mode-download' ?>">

<div class="album-bar">
    <span class="bar-title"><?= e(couple()) ?> · <span id="pageCount">…</span> pages</span>
    <button type="button" class="bar-btn" id="downloadBtn" disabled>Download PDF</button>
</div>

<div class="album-status" id="status" role="status" aria-live="polite">
    <img src="assets/ganesha.png" alt="" width="64">
    <p class="status-title">Preparing your album…</p>
    <div class="status-track"><div class="status-fill" id="statusFill"></div></div>
    <p class="status-text" id="statusText">Loading photos and fonts</p>
    <div class="status-done" id="statusDone" hidden>
        <button type="button" class="bar-btn" id="againBtn">Download again</button>
        <a class="bar-link" href="?<?= e(http_build_query(array_filter(['key' => $_GET['key'] ?? null, 'preview' => 1]))) ?>">Preview pages</a>
    </div>
</div>

<main class="album" id="album" data-file="<?= e($fileName) ?>" data-auto="<?= $preview ? '0' : '1' ?>">

    <!-- ================= FRONT COVER ================= -->
    <section class="sheet cover">
        <div class="mandala"></div>
        <img class="garland garland-l" src="assets/top-left.png" alt="">
        <img class="garland garland-r" src="assets/top-right.png" alt="">
        <img class="lotus lotus-l" src="assets/bottom-left.png" alt="">
        <img class="lotus lotus-r" src="assets/bottom-right.png" alt="">

        <img class="cover-ganesha" src="assets/ganesha.png" alt="">
        <p class="cover-kicker">The Book of Blessings</p>
        <?= divider('swirl', 'cover-divider') ?>
        <p class="cover-for">Engagement Wishes for</p>
        <h1 class="cover-names">
            <span><?= e($bride) ?></span>
            <span class="cover-amp">&amp;</span>
            <span><?= e($groom) ?></span>
        </h1>
        <div class="rings-row">
            <span class="rule"></span><img src="assets/rings.png" alt=""><span class="rule rule-r"></span>
        </div>
        <p class="cover-date"><?= e(config('event.date')) ?> <span class="dot">✦</span> <?= e(config('event.city')) ?></p>

        <?php if ($fan): ?>
        <div class="fan fan-<?= count($fan) ?>">
            <?php foreach ($fan as $i => $w): ?>
            <figure class="polaroid p<?= $i + 1 ?>">
                <img src="uploads/<?= e($w['photo']) ?>" alt="">
                <figcaption><?= e(str_len($w['name']) > 16 ? rtrim(mb_substr($w['name'], 0, 15, 'UTF-8')) . '…' : $w['name']) ?></figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="fan fan-empty"><img src="assets/rings.png" alt=""></div>
        <?php endif; ?>

        <div class="ribbon cover-ribbon">
            <?= count($wishes) ?> Heartfelt <?= count($wishes) === 1 ? 'Wish' : 'Wishes' ?> from Family &amp; Friends
        </div>
    </section>

    <!-- ================= WISH PAGES (built by album.js) ================= -->
    <template id="pageTpl">
        <section class="sheet inner">
            <div class="mandala"></div>
            <img class="garland garland-l small" src="assets/top-left.png" alt="">
            <img class="garland garland-r small" src="assets/top-right.png" alt="">
            <img class="lotus lotus-l small" src="assets/bottom-left.png" alt="">
            <img class="lotus lotus-r small" src="assets/bottom-right.png" alt="">
            <header class="inner-head">
                <img class="inner-ganesha" src="assets/ganesha.png" alt="">
                <?= divider('swirl', 'inner-divider') ?>
                <p class="inner-for">Engagement Wishes for</p>
                <p class="inner-names"><?= e($bride) ?> <span>&amp;</span> <?= e($groom) ?></p>
                <div class="rings-row small">
                    <span class="rule"></span><img src="assets/rings.png" alt=""><span class="rule rule-r"></span>
                </div>
            </header>
            <div class="cards"></div>
            <footer class="inner-foot">
                <div class="ribbon best" hidden>With Best Wishes</div>
                <div class="page-no"><span class="rule"></span><span class="num"></span><span class="rule rule-r"></span></div>
            </footer>
        </section>
    </template>

    <div class="card-pool" id="cardPool">
        <?php foreach ($wishes as $w): ?>
        <article class="wish-card">
            <div class="wish-photo<?= $w['photo'] ? '' : ' no-photo' ?>">
                <?php if ($w['photo'] && is_file(UPLOAD_DIR . '/' . $w['photo'])): ?>
                    <img src="uploads/<?= e($w['photo']) ?>" alt="">
                <?php else: ?>
                    <span class="initial"><?= e(initials($w['name'])) ?></span>
                    <img class="mini-rings" src="assets/rings.png" alt="">
                <?php endif; ?>
            </div>
            <div class="wish-text">
                <div class="quote">
                    <span class="q q-open">&ldquo;</span>
                    <p class="msg <?= message_size($w['message']) ?>"><?= nl2br(e($w['message'])) ?></p>
                    <span class="q q-close">&rdquo;</span>
                </div>
                <div class="wish-meta">
                    <span class="who">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7.5" r="4.5" fill="currentColor"/><path d="M3 21.5c0-5 4-8 9-8s9 3 9 8Z" fill="currentColor"/></svg>
                        <?= e($w['name']) ?>
                    </span>
                    <span class="sep"></span>
                    <span class="when">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M7 13.5h2m3 0h2m3 0h1M7 17h2m3 0h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <?= e(date('j M Y', strtotime($w['created_at']))) ?>
                    </span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- ================= BACK COVER ================= -->
    <section class="sheet back">
        <div class="mandala"></div>
        <img class="garland garland-l" src="assets/top-left.png" alt="">
        <img class="garland garland-r" src="assets/top-right.png" alt="">
        <img class="lotus lotus-l" src="assets/bottom-left.png" alt="">
        <img class="lotus lotus-r" src="assets/bottom-right.png" alt="">
        <img class="back-ganesha" src="assets/ganesha.png" alt="">

        <div class="back-panel">
            <div class="back-inner">
                <svg class="back-hearts" viewBox="0 0 80 44" aria-hidden="true">
                    <path d="M30 40S6 27 6 14C6 8 10.5 4 16 4c6 0 10 4 14 10 4-6 8-10 14-10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    <path d="M50 40S74 27 74 14C74 8 69.5 4 64 4c-6 0-10 4-14 10-4-6-8-10-14-10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                <h2 class="back-title">Thank You</h2>
                <?= divider('lotus', 'back-divider') ?>
                <p class="back-text">Thank you for showering us with your love, blessings and warm wishes.
                    Every message means a lot to us and will be cherished as we begin this beautiful new chapter together.</p>
                <p class="back-love">With love,</p>
                <p class="back-names"><?= e($bride) ?> <span>&amp;</span> <?= e($groom) ?></p>
                <?php if ($families): ?>
                <p class="back-fam-intro">Together with our families,</p>
                <div class="back-families">
                    <?php foreach ($families as $f): ?><p><?= e($f) ?></p><?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?= divider('swirl', 'back-divider') ?>
                <p class="back-date"><?= e(config('event.date')) ?> <span>|</span> <?= e(config('event.city')) ?></p>
            </div>
        </div>

        <p class="back-credit">Powered by <a href="https://playtown.co.in/" data-pdf-link>Playtown</a></p>
    </section>
</main>

<script src="assets/vendor/html2canvas-1.4.1.min.js"></script>
<script src="assets/vendor/jspdf-2.5.1.umd.min.js"></script>
<script src="assets/js/album.js?v=3"></script>
</body>
</html>
