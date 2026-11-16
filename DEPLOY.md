# Deploying the Engagement Wishes app

## ⭐ Hostinger hPanel with Git auto-deploy (recommended)

The whole repository is cloned into `public_html`. The root `.htaccess` serves the app from
`public/` and blocks `src/`, `.git`, `schema.sql`, the docs and the config from the web.

### 1. Database (one time)
1. Go to **hPanel → Websites → Manage → Databases → Management**.
2. Create a database and a user. Note the full names, e.g. `u123456789_engagement` and `u123456789_wishes`, and the password.
3. Click **Enter phpMyAdmin** for that database, then **Import** → choose `schema.sql` → **Go**. A `wishes` table appears.

### 2. PHP settings (one time)
Go to **Advanced → PHP Configuration**:
- **PHP version**: 8.2 or 8.3
- **PHP extensions**: make sure `gd`, `exif`, `pdo_mysql` and `mbstring` are ticked
- **PHP options**: `upload_max_filesize` 12M or more and `post_max_size` 14M or more (Hostinger's defaults are usually higher already)

### 3. Empty `public_html`
Hostinger can only deploy into an **empty** folder. Open **Files → File Manager**, go to
`public_html`, and delete the default `default.php` / `index.html` and anything else there.

### 4. Connect GitHub
Go to **Advanced → Git**.

The repository is **private**, so first give Hostinger read access:
1. In hPanel, find the **SSH key** on the Git page and click **Copy**.
2. On GitHub, open the repository, then **Settings → Deploy keys → Add deploy key**.
3. Paste the key, give it a title such as `Hostinger`, leave *Allow write access* **off**, and click **Add key**.

Then fill in **Create a New Repository** in hPanel:
- **Repository**: `git@github.com:mediapointcorp/engagement.git`
- **Branch**: `claude/festive-ptolemy-231cyj` (or `main` once the work is merged there)
- **Directory**: leave **empty** to deploy to `public_html`

Click **Create**, then **Deploy**.

### 5. Auto deployment (deploy on every push)
1. On the same Git page, under **Manage Repositories**, click **Auto Deployment** and **copy the Webhook URL**.
2. On GitHub, go to **Settings → Webhooks → Add webhook**.
3. **Payload URL**: paste the URL. Leave the other fields as they are ("Just the push event"), then click **Add webhook**.

From now on, every push to that branch updates the site automatically.

### 6. Create `config.php`, outside `public_html`
In **File Manager**, go **one level up** from `public_html`, to the folder that contains
`public_html` (e.g. `domains/yourdomain.com/`). Create a new file named **`config.php`** there,
and paste the contents of `config.sample.php` into it. Then fill in:

```php
'db' => [
    'host'     => 'localhost',
    'port'     => 3306,
    'name'     => 'u123456789_engagement',
    'user'     => 'u123456789_wishes',
    'password' => 'your-db-password',
],
'admin_password' => 'a-long-private-password',
```

Because it lives outside `public_html`, deployments never touch it and nobody can reach it
from the web. The app looks for `config.php` there automatically.

### 7. HTTPS
Go to **Security → SSL**, install the free SSL certificate, and switch on **Force HTTPS**.

### 8. Test on your phone
1. Open `https://yourdomain.com`, then tap **Share your Wishes**. Upload and crop a photo, enter a name and wish, and submit. You should reach the Thank You page.
2. Open `https://yourdomain.com/admin.php`, log in, check that the wish and photo appear, and delete the test.
3. Make a QR code for `https://yourdomain.com` for the guests.

**Wishes album:** open `https://yourdomain.com/album.php` and the PDF album downloads automatically.

Guest photos are stored in `public_html/public/uploads/`. Git ignores that folder, so
redeploying never deletes them.

---

## Other hosts

You need **web hosting with PHP 8.1+ and MySQL**, plus a domain or subdomain
(e.g. `wishes.yourdomain.com`). Any ordinary shared host works: Hostinger, BigRock,
GoDaddy, Bluehost, or any cPanel host.

- **Option A**: shared hosting (cPanel / hPanel). Easiest, and enough for an event.
- **Option B**: your own Ubuntu server (VPS).

---

## Option A: Shared hosting (cPanel / Hostinger hPanel)

### 1. Download the code
On GitHub, open the repository, switch to the branch `claude/festive-ptolemy-231cyj`,
then click **Code → Download ZIP**. Unzip it on your computer.

### 2. Check the PHP version
In the control panel, go to **Select PHP Version** (or **MultiPHP Manager**, or Hostinger **PHP Configuration**):
- Choose **PHP 8.1 or newer** (8.2 or 8.3 is ideal).
- Make sure these extensions are ticked: **gd**, **pdo_mysql**, **exif**, **mbstring**.

### 3. Create the database
In **MySQL Databases** (Hostinger: **Databases → Management**):
1. Create a database, e.g. `engagement`. The panel usually adds a prefix, e.g. `u12345_engagement`.
2. Create a user with a strong password, e.g. `u12345_wishes`.
3. Add the user to the database with **ALL PRIVILEGES**.

Write down the **full** database name, user name and password, including the prefix.

### 4. Create the table
Open **phpMyAdmin**, click your database on the left, open the **Import** tab, choose
`schema.sql` from the unzipped folder, and click **Go**. You should now see a `wishes` table.

### 5. Upload the files
Open **File Manager**. The layout must be:

```
/home/youraccount/            ← your hosting home folder (one level ABOVE public_html)
├── config.php                ← you create this in step 6
├── src/                      ← upload the src folder here
└── public_html/              ← the website (for a subdomain, its own folder)
    ├── index.php  wishes.php  thank-you.php  admin.php  robots.txt
    ├── .htaccess  .user.ini
    ├── assets/
    └── uploads/
```

- Upload the **contents** of the `public` folder (not the folder itself) into `public_html`.
- Upload the `src` folder and `config.sample.php` into the home folder, **next to** `public_html`.
- Easiest way: zip everything, upload the zip, then use **Extract** in File Manager and move the files into place.
- Show hidden files (Settings → "Show Hidden Files") and check that `.htaccess` and `.user.ini` were uploaded.

> **Subdomain** (e.g. `wishes.yourdomain.com`): create it first under **Domains / Subdomains**. Its
> folder is often `public_html/wishes`. Then put `src/` and `config.php` **in the folder directly
> above that one**, so the pages can find `../src/bootstrap.php`.

### 6. Create `config.php`
In File Manager, copy `config.sample.php` to `config.php` (same folder), then **Edit** it:

```php
'db' => [
    'host'     => 'localhost',            // keep "localhost" on almost all shared hosts
    'port'     => 3306,
    'name'     => 'u12345_engagement',    // full name from step 3
    'user'     => 'u12345_wishes',
    'password' => 'the-db-password',
],
'admin_password' => 'a-long-private-password',   // used to open /admin.php
```

The names, date, city and families are also in this file, under `event`.

### 7. Make the uploads folder writable
Right-click `public_html/uploads` → **Change Permissions** → **755**.
If photo uploads fail later, try **775**.

### 8. Turn on HTTPS
Go to **SSL/TLS Status** (cPanel AutoSSL) or Hostinger **Security → SSL**, and install the free certificate.
Then open the site with `https://`.

### 9. Test it on your phone
1. Open `https://your-domain/`, then tap **Share your Wishes**.
2. Upload a photo, crop it, enter a name and wish, and submit. You should reach the Thank You page.
3. Open `https://your-domain/admin.php`, log in with your admin password, and check that the wish and photo appear.
4. Delete the test wish.

### 10. Share with guests
Make a QR code for `https://your-domain/` with any free QR generator, then print it for the
venue or send it on WhatsApp.

---

## Option B: Your own Ubuntu server (VPS)

```bash
sudo apt update
sudo apt install -y apache2 mysql-server php php-mysql php-gd php-mbstring libapache2-mod-php unzip
sudo a2enmod headers
```

```bash
sudo git clone -b claude/festive-ptolemy-231cyj https://github.com/mediapointcorp/engagement.git /var/www/engagement
```

```bash
sudo mysql -e "CREATE DATABASE engagement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'engagement_user'@'localhost' IDENTIFIED BY 'CHANGE-ME';
GRANT SELECT, INSERT, DELETE ON engagement.* TO 'engagement_user'@'localhost';"
```

```bash
sudo mysql engagement < /var/www/engagement/schema.sql
```

```bash
cd /var/www/engagement && sudo cp config.sample.php config.php && sudo nano config.php
```

```bash
sudo chown -R www-data:www-data /var/www/engagement/public/uploads
```

Then create `/etc/apache2/sites-available/engagement.conf`:

```apache
<VirtualHost *:80>
    ServerName wishes.yourdomain.com
    DocumentRoot /var/www/engagement/public
    <Directory /var/www/engagement/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite engagement && sudo systemctl reload apache2
```

Point the domain's DNS **A record** to the server's IP address, then add HTTPS:

```bash
sudo apt install -y certbot python3-certbot-apache && sudo certbot --apache -d wishes.yourdomain.com
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| "Missing config.php" | Put `config.php` in the app folder (next to `src/`) or in the folder directly above it. |
| "500 Internal Server Error" | Check the PHP version (8.1+). Then check the error log in the control panel. |
| "Something went wrong while saving" | Wrong database name, user or password in `config.php` (remember the prefix), or `schema.sql` was not imported. |
| Photo upload fails | Set the `uploads` folder permission to 755 or 775. Make sure the **gd** extension is enabled. |
| Admin page says "Incorrect password" | You must change `admin_password` from the sample value. |

## After the event
Log in to `/admin.php` and click **Download CSV**. Then download the uploads folder
(`public_html/public/uploads` with Git deploy, otherwise `public_html/uploads`) from File Manager (right-click → Compress → download the zip) to keep all the photos.
