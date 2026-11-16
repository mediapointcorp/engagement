# Engagement Wishes — Prathyusha & Vignesh

A small mobile-first PHP + MySQL web app. Guests open a link (or scan a QR code), and then:

1. **Welcome**: "Warm Welcome to the Engagement Ceremony of Prathyusha & Vignesh" → **Share your Wishes**
2. **Share Your Wishes**: upload a photo from the phone gallery and crop it to a square, enter their name, and write a wish (up to 500 characters).
3. **Thank You**: "Your Wishes Made Our Day Special!", with the families, the date and the city.

The couple reads every wish (with photos) at **`/admin.php`**, behind a password. From there they can download them all as a CSV or delete any wish.

## Wishes Album (PDF)
Open **`/album.php`** (for example `https://yourdomain.com/album.php`). The page builds an A4 PDF
album of every wish and **downloads it automatically**:
- **Front cover**: Ganesha, garlands, the couple's names, the date and city, a fan of guest
  photos, and the number of wishes.
- **Wish pages**: up to 3 wishes per page, each with the guest's photo (or their initial if they
  sent no photo), their wish, name and date, plus page numbers. The last page ends with
  "With Best Wishes".
- **Back cover**: a thank-you note, the families, the date and city, and "Powered by Playtown",
  which is a clickable link in the PDF.

`/album.php?preview=1` shows the pages on screen with a **Download PDF** button.
Any language works (Tamil, Telugu, Hindi, emoji), because the browser draws the pages.
To keep the link private, set `album_key` in `config.php`. The link then becomes
`/album.php?key=YOUR-KEY`.

## Requirements
- PHP 8.1+ with the `pdo_mysql` and `gd` extensions (`exif` is optional; it fixes photo rotation)
- MySQL 5.7+ or MariaDB 10.3+
- Apache (the `.htaccess` files are included) or Nginx (see below)

## Setup
See **DEPLOY.md** for step-by-step instructions (shared hosting such as Hostinger/cPanel, or your own server).

Local test run:
```bash
mysql -u root -p -e "CREATE DATABASE engagement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p engagement < schema.sql
cp config.sample.php config.php    # set DB details + admin password
php -S localhost:8080 -t public
```

## Changing the text
The names, date, city and families all live in `config.php`, under `event`.

## Nginx
```nginx
root /var/www/engagement/public;
index index.php;
client_max_body_size 14m;
location ~ ^/uploads/.*\.php$ { deny all; }
location ~ /\. { deny all; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
```

## How photos are handled
- Guests pick a photo from their gallery (any size), then crop it to a square: drag to move, pinch or use the slider to zoom. The browser compresses the crop to a 1080 × 1080 JPEG, so only a small file (about 100–300 KB) is uploaded.
- The server checks that each file really is an image. It then centre-crops it to a square (as a fallback), re-encodes it as a JPEG with GD, which strips location (GPS) data and fixes rotation, and saves it under a random name in `public/uploads/`.
- Uploads are capped at 10 MB (`max_upload_mb` in the config). `.user.ini` and `.htaccess` raise PHP's default 2 MB limit.

## Security
- CSRF tokens, a hidden "honeypot" field and a 10-second throttle per visitor stop spam.
- All database queries are prepared statements, and all output is escaped.
- Nothing in `uploads/` is ever run as a script.
- The admin page stays locked until you change `admin_password` from its default.
