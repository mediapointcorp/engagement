<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

page_start('Engagement of ' . couple(), 'page-welcome');
?>
        <?= divider('swirl', 'divider-top') ?>
        <p class="welcome-line">Warm Welcome to the<br>Engagement Ceremony of</p>
        <h1 class="couple">
            <span><?= e(config('event.bride')) ?></span>
            <span class="amp">&amp;</span>
            <span><?= e(config('event.groom')) ?></span>
        </h1>
        <div class="rings-divider" aria-hidden="true">
            <span class="line"></span>
            <span class="rings-wrap"><span class="sparkle"></span><img src="assets/rings.png" alt=""></span>
            <span class="line"></span>
        </div>

        <a class="btn btn-primary btn-wishes" href="wishes.php">
            <svg class="btn-icon" viewBox="0 0 40 40" aria-hidden="true">
                <path d="M20 33S5 24.5 5 14.5C5 9.8 8.6 6.5 12.8 6.5c3 0 5.6 1.7 7.2 4.3 1.6-2.6 4.2-4.3 7.2-4.3C31.4 6.5 35 9.8 35 14.5c0 2.2-.7 4.3-1.9 6.3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M33.5 19.5l3 3-11 11-4.3 1.3 1.3-4.3Z M30.6 22.4l3 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            <span>Share your Wishes</span>
            <svg class="btn-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
<?php
page_end();
