# BLUESKILLS.AE — Server deployment guide

Step-by-step set-up of a **test (staging) server** on a fresh Ubuntu VM, and what changes when the
same server set-up is used for the **live (production)** server later.

- Stack on the server: Nginx, PHP 8.5-FPM, PostgreSQL 18, Redis, Node 24 (build only), Supervisor
  (Horizon + Reverb), cron (scheduler), ffmpeg. These match the Sail versions used in development.
- Sail is **not** used on servers. It is a development tool only.
- The launch requirements for production live in `docs/launch-checklist.md`. This file is the
  hands-on "how"; the checklist is the "what must be true before go-live".

Throughout this guide, replace:

| Placeholder | Meaning |
|---|---|
| `test.yourdomain.com` | The domain (or subdomain) that points at the VM |
| `CHANGE_ME_STRONG` | A strong database password you choose |
| `you@example.com` | Your email, for Let's Encrypt certificate notices |

---

## Contents

0. [Before you start](#0-before-you-start)
1. [Base system](#1-base-system)
2. [Install the stack](#2-install-the-stack)
3. [Database](#3-database)
4. [Get the code](#4-get-the-code)
5. [Environment file (`.env`)](#5-environment-file-env)
6. [Install and build](#6-install-and-build)
7. [Nginx and HTTPS](#7-nginx-and-https)
8. [Background processes](#8-background-processes)
9. [Lock the test server down](#9-lock-the-test-server-down)
10. [Smoke test](#10-smoke-test)
11. [Every later update](#11-every-later-update)
12. [Troubleshooting](#12-troubleshooting)
13. [Moving to the live server](#13-moving-to-the-live-server)

---

## 0. Before you start

1. **Domain.** Create a DNS A record `test.yourdomain.com → <VM public IP>`. HTTPS is required:
   the browser only allows the microphone (Voice-to-CV, SkillShow) and secure websockets (Reverb)
   on HTTPS. Without a domain these features will not work.
2. **Code pushed.** The server deploys the `develop` branch from
   `git@github.com:lokman-hosen/blue-skills.git`. Merge and push what you want to test first.
3. **Ubuntu version.** Run `lsb_release -a` on the VM. The commands below are written for
   Ubuntu 24.04 / 26.04 LTS.
4. **VM size.** At least 2 vCPU, 4 GB RAM and 40 GB disk is comfortable for testing.
   Smaller works with the swap file from step 1.

### Why `APP_ENV=staging` matters

The app checks `app()->isProduction()`. With `APP_ENV=staging` (anything except `production`):

- `php artisan db:seed` seeds the **fictional sample world** (Rahim Uddin, ABC Contracting, …)
  as well as the reference data.
- Sign-in codes, WhatsApp and SMS can go to the log (`OTP_CHANNEL=log`, `NOTIFY_*_DRIVER=log`).
- The sample speech-to-text driver works (`SPEECH_TO_TEXT_DRIVER=sample`).

With `APP_ENV=production` none of that happens: sample data is not seeded and the `log` / `sample`
drivers refuse to run.

---

## 1. Base system

Run as root (or prefix with `sudo`).

```bash
apt update && apt upgrade -y
timedatectl set-timezone UTC                     # the app stores all times in UTC

# A deploy user: the app runs as this user, never as root
adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
usermod -aG sudo deploy                          # optional: lets deploy use sudo
passwd deploy                                    # optional: needed if deploy uses sudo

# Firewall: SSH and web only
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Swap, so npm build and Composer don't run out of memory on a small VM
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

---

## 2. Install the stack

```bash
apt install -y software-properties-common curl git unzip ca-certificates gnupg lsb-release

# PHP 8.5 with the extensions the app needs (launch checklist §3)
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.5-fpm php8.5-cli php8.5-pgsql php8.5-redis php8.5-intl php8.5-gd \
  php8.5-bcmath php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip php8.5-opcache

# PostgreSQL 18 from the official PostgreSQL repository
install -d /usr/share/postgresql-common/pgdg
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
  -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc
echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
  > /etc/apt/sources.list.d/pgdg.list
apt update

# Database, cache/queue, web server, process monitor, video tools, TLS certificates
apt install -y postgresql-18 redis-server nginx supervisor ffmpeg certbot python3-certbot-nginx

# Node 24 (only needed to build the frontend)
curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

Check the versions:

```bash
php -v               # 8.5.x  (the app requires ^8.3)
php -m | grep -Ei 'pgsql|redis|intl|gd|bcmath|pcntl|exif|opcache'
node -v              # v24.x
psql --version       # 18.x
redis-cli ping       # PONG
ffmpeg -version | head -1
```

> If `ppa:ondrej/php` does not support your Ubuntu release yet, install the distribution's PHP
> (`apt install php-fpm php-pgsql php-redis …`) as long as it is 8.3 or newer, and replace
> `php8.5` / `php8.5-fpm` in the rest of this guide with that version.

---

## 3. Database

```bash
sudo -u postgres psql <<'SQL'
CREATE USER blueskills WITH PASSWORD 'CHANGE_ME_STRONG';
CREATE DATABASE blueskills OWNER blueskills;
\c blueskills
CREATE EXTENSION IF NOT EXISTS pg_trgm;   -- search indexes need it (D-106)
SQL
```

PostgreSQL only listens on `localhost` by default. Keep it that way; never open port 5432 in the
firewall.

---

## 4. Get the code

The GitHub repository is private, so give the server a read-only **deploy key**.

```bash
sudo -iu deploy
ssh-keygen -t ed25519 -C "blueskills-test" -N "" -f ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub
```

On GitHub: **repository → Settings → Deploy keys → Add deploy key**, paste the key, and leave
"Allow write access" **off**.

```bash
# still as deploy
ssh -T git@github.com            # answer "yes"; it should greet you with the repository name

sudo mkdir -p /var/www && sudo chown deploy:www-data /var/www
git clone -b develop git@github.com:lokman-hosen/blue-skills.git /var/www/blueskills
cd /var/www/blueskills
cp .env.example .env
```

---

## 5. Environment file (`.env`)

Edit `/var/www/blueskills/.env` (`nano .env`) and set these values. Keep the other values from
`.env.example`.

```dotenv
APP_NAME=BLUESKILLS
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://test.yourdomain.com
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blueskills
DB_USERNAME=blueskills
DB_PASSWORD=CHANGE_ME_STRONG

REDIS_HOST=127.0.0.1
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_SECURE_COOKIE=true

# Test-only drivers (all refused when APP_ENV=production)
OTP_CHANNEL=log                 # worker sign-in codes are written to storage/logs/laravel.log
NOTIFY_WHATSAPP_DRIVER=log
NOTIFY_SMS_DRIVER=log
MAIL_MAILER=log
SPEECH_TO_TEXT_DRIVER=sample    # returns the prototype's sample sentence for every recording

# Pilot mode (D-108)
BLUESKILLS_PILOT=true
BLUESKILLS_BILLING_ENFORCE=false
BILLING_PROVIDER=manual

# Content Security Policy: report-only while testing (launch checklist §4)
BLUESKILLS_CSP_ENFORCE=false

# Live updates: Reverb listens on 127.0.0.1:8081 and Nginx serves it on 443
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=blueskills
REVERB_APP_KEY=<output of: openssl rand -hex 16>
REVERB_APP_SECRET=<output of: openssl rand -hex 16>
REVERB_HOST=test.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8081
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=test.yourdomain.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Never set these on a server: `BLUESKILLS_ENFORCE_STAFF_2FA=false`, `INERTIA_DEVTOOLS_ENABLED=true`.

The `VITE_*` values are baked into the JavaScript at build time. If you change them later, run
`npm run build` again.

---

## 6. Install and build

As `deploy`, in `/var/www/blueskills`:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
npm ci
npm run build                    # only after the VITE_REVERB_* values are final
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force      # staging: reference data + the fictional sample world
php artisan optimize             # caches config, routes, views and events

sudo chown -R deploy:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

Create your own staff account (asks for email, name and password):

```bash
php artisan blueskills:create-superuser
```

Sign in with it later and set up two-factor authentication. Every staff screen requires it (D-035).

---

## 7. Nginx and HTTPS

PHP-FPM runs as `www-data` by default. Run it as `deploy` instead so the web requests and the queue
workers write files as the same user:

```bash
sudo sed -i 's/^user = www-data/user = deploy/; s/^group = www-data/group = www-data/' \
  /etc/php/8.5/fpm/pool.d/www.conf

# Upload limits (SkillShow videos, documents)
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 100M/; s/^post_max_size.*/post_max_size = 100M/' \
  /etc/php/8.5/fpm/php.ini

# OPcache (production-style)
echo -e "opcache.enable=1\nopcache.memory_consumption=256\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0" \
  | sudo tee /etc/php/8.5/fpm/conf.d/99-blueskills.ini

sudo systemctl restart php8.5-fpm
```

> With `opcache.validate_timestamps=0`, PHP does not notice changed files by itself. The deploy
> script in §11 reloads PHP-FPM for this reason.

Create `/etc/nginx/sites-available/blueskills`:

```nginx
server {
    listen 80;
    server_name test.yourdomain.com;
    root /var/www/blueskills/public;
    index index.php;

    client_max_body_size 100M;           # SkillShow videos and documents
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }

    # Reverb websockets (live notifications and updates)
    location ~ ^/(app|apps)/ {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Enable it and get a certificate:

```bash
sudo ln -s /etc/nginx/sites-available/blueskills /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

sudo certbot --nginx -d test.yourdomain.com --redirect -m you@example.com --agree-tos -n
sudo certbot renew --dry-run     # checks automatic renewal
```

---

## 8. Background processes

Worker sign-in codes, Voice-to-CV answers, SkillShow checks, notifications and exports run on the
queue, so these processes **must** be running.

Create `/etc/supervisor/conf.d/blueskills.conf`:

```ini
[program:horizon]
command=php /var/www/blueskills/artisan horizon
directory=/var/www/blueskills
user=deploy
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/www/blueskills/storage/logs/horizon.log

[program:reverb]
command=php /var/www/blueskills/artisan reverb:start
directory=/var/www/blueskills
user=deploy
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/blueskills/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status        # horizon and reverb: RUNNING
```

Scheduler: daily new-applicant digest, alerts held for quiet hours, lapsed agency mandates,
evidence expiry and voice-recording clean-up.

```bash
( sudo crontab -u deploy -l 2>/dev/null; \
  echo "* * * * * cd /var/www/blueskills && php artisan schedule:run >> /dev/null 2>&1" ) \
  | sudo crontab -u deploy -

sudo crontab -u deploy -l        # check
```

Horizon dashboard: `https://test.yourdomain.com/horizon`. You need a staff account with the
`horizon.view` permission and confirmed 2FA.

---

## 9. Lock the test server down

On staging the seeder creates the sample logins with the password **`password`** (see the logins
in `CLAUDE.md`). Anyone who finds the URL could sign in with them. Pick at least one option:

**Option A: password-protect the whole site (recommended).**

```bash
sudo apt install -y apache2-utils
sudo htpasswd -c /etc/nginx/.htpasswd tester      # add more people without -c
```

Inside the `server { listen 443 … }` block that Certbot created in
`/etc/nginx/sites-available/blueskills`, add:

```nginx
    auth_basic "BLUESKILLS test";
    auth_basic_user_file /etc/nginx/.htpasswd;
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

**Option B: allow only known IP addresses.**

```bash
sudo ufw delete allow 80/tcp
sudo ufw delete allow 443/tcp
sudo ufw allow from <your office IP> to any port 80,443 proto tcp
```

Also:

- Don't put real workers' data (passports, real phone numbers) on the test server.
- Search engines must not index the test server. Option A already blocks them.

---

## 10. Smoke test

1. Open `https://test.yourdomain.com`. The page loads, and the browser console (F12) shows no
   red errors.
2. **Worker sign-in:** go to `/join`, choose a language and enter `+8801711000001` (Rahim Uddin,
   Bangladesh). Read the code on the server:
   ```bash
   tail -f /var/www/blueskills/storage/logs/laravel.log | grep "sign-in code"
   ```
3. **Voice-to-CV:** Passport → Voice-to-CV, tap the microphone, allow the mic, speak, Stop. The
   sample transcript appears. This shows HTTPS, mic access, the queue and Horizon all work.
4. **Live updates:** sign in as `owner@abc-contracting.test` (password `password`) in another
   browser. Notifications appear without reloading. This shows Reverb works.
5. **Staff:** sign in with the superuser from §6, set up 2FA, open Admin.
6. `sudo supervisorctl status`: `horizon` and `reverb` are RUNNING.

---

## 11. Every later update

Create `/var/www/blueskills/deploy.sh` (as `deploy`):

```bash
#!/usr/bin/env bash
set -euo pipefail
cd /var/www/blueskills

php artisan down --render="errors::503" || true

git pull origin develop
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force        # adds new roles/permissions; never overwrites staff changes (D-108)
php artisan optimize

sudo systemctl reload php8.5-fpm   # OPcache picks up the new code
php artisan horizon:terminate      # Supervisor restarts Horizon with the new code
php artisan reverb:restart

php artisan up
echo "Deployed $(git rev-parse --short HEAD)"
```

```bash
chmod +x deploy.sh
./deploy.sh
```

`deploy.sh` is not in Git, so `git pull` never touches it. If you add it to the repository later,
keep it executable.

Notes:

- When `public/offline.html` changes, bump `VERSION` in `public/sw.js` (D-107).
- To reset the test database with fresh sample data:
  `php artisan migrate:fresh --seed --force`. **This deletes everything.** Never run it on live.
- Role permissions belong to the Superuser (Admin › Roles & permissions, D-112). `db:seed` only
  adds defaults for new roles and new permissions.

---

## 12. Troubleshooting

| Symptom | Check |
|---|---|
| 500 error page | `tail -n 100 storage/logs/laravel.log`; `sudo tail /var/log/nginx/error.log` |
| "Permission denied" on storage or cache | `sudo chown -R deploy:www-data storage bootstrap/cache && sudo chmod -R ug+rwX storage bootstrap/cache` |
| `.env` change has no effect | `php artisan optimize:clear && php artisan optimize`, then `php artisan horizon:terminate` |
| Sign-in code never arrives / voice spinner never ends | The queue isn't running: `sudo supervisorctl status`, `tail storage/logs/horizon.log`, check `/horizon` |
| Jobs failing | `php artisan queue:failed`; retry with `php artisan queue:retry all` |
| No live updates; websocket errors in the console | `sudo supervisorctl status reverb`; `tail storage/logs/reverb.log`; `VITE_REVERB_*` must match the domain and you must have run `npm run build` after setting them |
| Microphone button greyed out / "unsupported" | The site must be on **HTTPS**; check the padlock in the browser |
| SkillShow upload fails | `client_max_body_size` (Nginx) and `upload_max_filesize` / `post_max_size` (PHP) are 100M; `ffmpeg -version` works |
| Styles/JS missing, Vite manifest error | `npm run build` was not run, or failed (memory: check the swap file) |
| Migration fails on `pg_trgm` | Run `CREATE EXTENSION pg_trgm;` as the `postgres` user in the `blueskills` database (§3) |
| Too many sign-in attempts from one office | Per-IP limits; set `BLUESKILLS_OTP_MAX_PER_IP_PER_HOUR` |
| Code changed but the site shows old behaviour | `sudo systemctl reload php8.5-fpm` (OPcache) |

Useful commands:

```bash
php artisan about                          # environment, drivers, cache status
php artisan horizon:status
sudo systemctl status nginx php8.5-fpm postgresql redis-server supervisor
df -h && free -h                           # disk and memory
```

---

## 13. Moving to the live server

Build the live server with the same steps 1–11, then change the following. Work through
`docs/launch-checklist.md` §1–§8 before real users arrive.

**Environment**

```dotenv
APP_ENV=production                 # no sample data; log/sample drivers refuse to run
APP_DEBUG=false
APP_URL=https://blueskills.ae
LOG_LEVEL=warning
BLUESKILLS_CSP_ENFORCE=true        # after staging shows no CSP violations
TRUSTED_PROXIES="*"                # or Cloudflare's IP ranges, when behind Cloudflare
```

**Real providers** (launch checklist §2)

| Area | Test server | Live server |
|---|---|---|
| Worker sign-in codes | `OTP_CHANNEL=log` | `OTP_CHANNEL=whatsapp` + Meta WhatsApp Cloud API settings |
| WhatsApp / SMS alerts | `log` | `NOTIFY_WHATSAPP_DRIVER=cloud`, `NOTIFY_SMS_DRIVER=twilio` |
| Email | `MAIL_MAILER=log` | SMTP / SES / Postmark, with SPF, DKIM and DMARC |
| Voice-to-CV | `SPEECH_TO_TEXT_DRIVER=sample` | A real provider (not built yet), or `none` (typing only) |
| Files | Local private disk | S3-compatible storage in a UAE region; private bucket blocks public access; add the bucket host to `BLUESKILLS_CSP_MEDIA_SOURCES` |
| Access | Basic auth / IP allow-list | Public, behind Cloudflare |

**Also for live**

- Hosting in a UAE region (Laravel Cloud or a UAE VPS), behind Cloudflare. Not shared hosting.
- **Backups** of PostgreSQL and private files, with a tested restore. Not set up yet.
- **Error monitoring** (Sentry). Approved but not installed yet.
- `php artisan db:seed --force` on production adds only reference data (countries, corridor rules,
  languages, taxonomy, roles). Create the first staff account with
  `php artisan blueskills:create-superuser`.
- Never run `migrate:fresh` on the live server.
- Deploy the `main` branch (or a release tag) instead of `develop`: change the branch in
  `git clone` and in `deploy.sh`.
