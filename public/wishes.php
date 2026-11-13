<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

$maxName = (int) config('max_name_len', 80);
$maxMsg  = (int) config('max_message_len', 500);
$errors  = [];
$old     = ['name' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = trim((string) ($_POST['name'] ?? ''));
    $old['message'] = trim(str_replace("\r\n", "\n", (string) ($_POST['message'] ?? '')));

    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = 'That photo is too large. Please choose a smaller one.';
    } elseif (!csrf_check($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please submit again.';
    } elseif (!empty($_POST['website'])) {
        redirect('thank-you.php'); // honeypot filled → bot; pretend success
    } else {
        if ($old['name'] === '') {
            $errors[] = 'Please enter your name.';
        } elseif (str_len($old['name']) > $maxName) {
            $errors[] = "Your name can be at most {$maxName} characters.";
        }
        if ($old['message'] === '') {
            $errors[] = 'Please write your wishes.';
        } elseif (str_len($old['message']) > $maxMsg) {
            $errors[] = "Your wishes can be at most {$maxMsg} characters.";
        }
        $last = $_SESSION['last_submit'] ?? 0;
        if (!$errors && time() - $last < 10) {
            $errors[] = 'Please wait a few seconds before sending again.';
        }

        $photo = null;
        // The photo can arrive from either the "Take Photo" or the "Upload Photo" input.
        $upload = null;
        foreach (['photo', 'photo_camera'] as $field) {
            if (isset($_FILES[$field]) && is_int($_FILES[$field]['error']) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = $_FILES[$field];
                break;
            }
        }
        if (!$errors && $upload) {
            try {
                $photo = store_photo($upload);
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            }
        }

        if (!$errors) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO wishes (name, message, photo, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['name'],
                    $old['message'],
                    $photo,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]);
                $_SESSION['last_submit'] = time();
                redirect('thank-you.php');
            } catch (PDOException $ex) {
                error_log('Wish insert failed: ' . $ex->getMessage());
                if ($photo) {
                    @unlink(UPLOAD_DIR . '/' . $photo);
                }
                $errors[] = 'Something went wrong while saving your wishes. Please try again.';
            }
        }
    }
}

page_start('Share Your Wishes · ' . couple(), 'page-form');
?>
        <?= divider('swirl', 'divider-tight') ?>
        <h1 class="title">Share Your Wishes</h1>
        <?= divider('swirl', 'divider-tight') ?>
        <p class="lead">Capture a photo or upload one, add your name, and share your heartfelt wishes with the couple.</p>

        <?php if ($errors): ?>
        <div class="alert" role="alert">
            <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form class="wish-form" method="post" action="wishes.php" enctype="multipart/form-data" novalidate
              data-max-mb="<?= (int) config('max_upload_mb', 10) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="hp" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="photo-box" id="photoBox">
                <div class="photo-empty">
                    <svg class="photo-icon" viewBox="0 0 64 56" aria-hidden="true">
                        <path d="M6 18a6 6 0 0 1 6-6h8l4-6h16l4 6h6a6 6 0 0 1 6 6v8" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/>
                        <path d="M38 48H12a6 6 0 0 1-6-6V18" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="30" cy="29" r="9" fill="none" stroke="currentColor" stroke-width="4"/>
                        <circle cx="52" cy="44" r="10" fill="#c81d63"/>
                        <path d="M52 39v10M47 44h10" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <p class="photo-label">Take a Photo or Upload a Photo</p>
                </div>
                <div class="photo-preview" hidden>
                    <img id="photoPreview" alt="Your selected photo">
                    <button type="button" class="photo-remove" id="photoRemove" aria-label="Remove photo">&times;</button>
                </div>
                <div class="photo-actions">
                    <label class="btn btn-outline">
                        <input type="file" name="photo_camera" accept="image/*" capture="user" class="visually-hidden" data-photo-input>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 8.5A2.5 2.5 0 0 1 5.5 6H8l1.5-2h5L16 6h2.5A2.5 2.5 0 0 1 21 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.6" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>
                        <span>Take Photo</span>
                    </label>
                    <label class="btn btn-outline">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" class="visually-hidden" data-photo-input>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M3 17l5-5 4 4 3-3 6 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="16" cy="9" r="1.7" fill="currentColor"/></svg>
                        <span>Upload Photo</span>
                    </label>
                </div>
            </div>

            <div class="field field-inline">
                <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4.2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M4 20.5c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                <label class="visually-hidden" for="name">Your Name</label>
                <input id="name" name="name" type="text" placeholder="Your Name *" required maxlength="<?= $maxName ?>"
                       autocomplete="name" value="<?= e($old['name']) ?>">
            </div>

            <div class="field field-stacked">
                <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5c4.9 0 8.5 3.4 8.5 7.6s-3.6 7.6-8.5 7.6c-1 0-2-.1-2.9-.4L4.5 20l1.2-3.7a7.3 7.3 0 0 1-2.2-5.2c0-4.2 3.6-7.6 8.5-7.6Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="8.3" cy="11.2" r="1.1" fill="currentColor"/><circle cx="12" cy="11.2" r="1.1" fill="currentColor"/><circle cx="15.7" cy="11.2" r="1.1" fill="currentColor"/></svg>
                <div class="field-body">
                    <label for="message" class="field-label">Your Wishes <span>*</span></label>
                    <div class="textarea-wrap">
                        <textarea id="message" name="message" rows="5" required maxlength="<?= $maxMsg ?>"
                                  placeholder="Send your wishes to <?= e(couple()) ?>..."><?= e($old['message']) ?></textarea>
                        <span class="counter" id="counter"><span>0</span> / <?= $maxMsg ?></span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">
                <svg class="btn-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 2.5 2.5 10l7.2 3.3m11.8-10.8L14 21.5l-4.3-8.2m11.8-10.8L9.7 13.3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                <span class="btn-text">Submit Your Wishes</span>
            </button>
        </form>
<?php
page_end(true);
