<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

page_start('Thank You · ' . couple(), 'page-thanks');
?>
        <section class="frame">
            <svg class="hearts" viewBox="0 0 80 44" aria-hidden="true">
                <path d="M30 40S6 27 6 14C6 8 10.5 4 16 4c6 0 10 4 14 10 4-6 8-10 14-10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                <path d="M50 40S74 27 74 14C74 8 69.5 4 64 4c-6 0-10 4-14 10-4-6-8-10-14-10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <h1 class="title title-thanks">
                <span class="spark spark-l" aria-hidden="true"></span>Your Wishes Made<br>Our Day Special!<span class="spark spark-r" aria-hidden="true"></span>
            </h1>
            <?= divider('lotus') ?>
            <p class="thanks-text">Thank you for showering us with your love, blessings and warm wishes.
                Every message means a lot to us and will be cherished as we begin this beautiful new chapter together.</p>
            <?= divider('lotus') ?>
            <p class="with-love">With love,</p>
            <p class="couple couple-sm">
                <span><?= e(config('event.bride')) ?></span>
                <span class="amp">&amp;</span>
                <span><?= e(config('event.groom')) ?></span>
            </p>
            <?= divider('swirl', 'divider-tight') ?>

            <?php if ($families = config('event.families', [])): ?>
            <div class="families">
                <p class="families-intro">Together with our families,</p>
                <?php foreach ($families as $f): ?>
                <p class="family"><?= e($f) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <p class="event-meta">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 13.5h2m3 0h2m3 0h0M7 17h2m3 0h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span><?= e(config('event.date')) ?></span>
                <span class="sep"></span>
                <span><?= e(config('event.city')) ?></span>
            </p>
            <?= divider('lotus') ?>
        </section>
        <p class="again"><a href="wishes.php">Send another wish</a></p>
<?php
page_end();
