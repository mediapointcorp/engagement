<?php
declare(strict_types=1);

function page_start(string $title, string $pageClass = ''): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#f8f1ea">
<title><?= e($title) ?></title>
<meta name="description" content="Engagement ceremony of <?= e(couple()) ?> — share your wishes with the couple.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Marcellus&family=Playfair+Display:ital,wght@1,600;1,700&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=3">
</head>
<body class="<?= e($pageClass) ?>">
<div class="page">
    <div class="mandala" aria-hidden="true"></div>
    <img class="decor decor-tl" src="assets/top-left.png" alt="" aria-hidden="true">
    <img class="decor decor-tr" src="assets/top-right.png" alt="" aria-hidden="true">
    <header class="crest">
        <img src="assets/ganesha.png" alt="Lord Ganesha" width="142" height="169">
    </header>
    <main class="content">
<?php
}

function page_end(bool $withScript = false): void
{
    ?>
    </main>
    <footer class="ground" aria-hidden="true">
        <img class="decor decor-bl" src="assets/bottom-left.png" alt="">
        <img class="decor decor-br" src="assets/bottom-right.png" alt="">
        <img class="ground-rings" src="assets/rings.png" alt="">
    </footer>
</div>
<?php if ($withScript): ?>
<script src="assets/js/app.js?v=2" defer></script>
<?php endif; ?>
</body>
</html>
<?php
}

/** Gold ornamental divider. $variant: 'swirl' | 'lotus' | 'plain' */
function divider(string $variant = 'swirl', string $class = ''): string
{
    $center = match ($variant) {
        'lotus' => '<path d="M100 3c-3 4-4 7-4 10 0 3 2 5 4 6 2-1 4-3 4-6 0-3-1-6-4-10Zm-12 9c2 4 5 6 9 7m15-7c-2 4-5 6-9 7M84 18h32" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
        'swirl' => '<path d="M100 4l5 7-5 7-5-7Z" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M95 11c-6 0-8-5-13-5s-6 6-2 7m25-2c6 0 8-5 13-5s6 6 2 7" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
        default => '<path d="M100 6l4 5-4 5-4-5Z" fill="currentColor"/>',
    };
    return '<svg class="divider ' . e($class) . '" viewBox="0 0 200 22" preserveAspectRatio="xMidYMid meet" aria-hidden="true">'
        . '<path d="M8 11H76M124 11H192" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>'
        . $center . '</svg>';
}
