# DigitalOcean Deployment

This app deploys to a plain DigitalOcean Droplet with Nginx, PHP-FPM, MySQL, Supervisor, and GitHub Actions over SSH. There is no Docker, no GHCR, and no container registry.

GitHub Actions builds Composer dependencies and Vite assets, syncs the prepared release to the server with `rsync`, then runs Laravel deployment commands over SSH.

## Architecture

- DigitalOcean Basic Droplet: 2 GB RAM, 1 vCPU, 50 GB SSD
- Ubuntu LTS
- Nginx serving Laravel's `public` directory
- PHP 8.3 FPM
- MySQL on the Droplet
- Supervisor for `php artisan queue:work`
- Cron for Laravel scheduler
- GitHub Actions for automated deploys
- Certbot for HTTPS

## 1. Create The Droplet

Create a DigitalOcean Droplet:

- Image: Ubuntu LTS
- Size: Basic 2 GB RAM / 1 vCPU / 50 GB SSD
- Authentication: SSH key
- Backups: optional, but recommended

SSH into the Droplet as root:

```bash
ssh root@YOUR_DROPLET_IP
```

Create a deploy user:

```bash
adduser deploy
usermod -aG sudo,www-data deploy
```

Copy root's SSH access to the deploy user:

```bash
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

## 2. Buy The Domain And Configure DNS

Use:

```text
Registrar: Namecheap
DNS provider: DigitalOcean DNS
```

Buy the domain in Namecheap. Then, in Namecheap, change the domain's nameservers to DigitalOcean:

```text
ns1.digitalocean.com
ns2.digitalocean.com
ns3.digitalocean.com
```

In DigitalOcean, open:

```text
Networking > Domains > Add Domain
```

Add the root domain, for example:

```text
yourdomain.com
```

Then create these DNS records in DigitalOcean:

```text
A    @    YOUR_DROPLET_IP
A    *    YOUR_DROPLET_IP
```

The wildcard record is required because tenant routing depends on subdomains:

```text
yourdomain.com
tenant-a.yourdomain.com
tenant-b.yourdomain.com
```

Set these production values:

```dotenv
APP_URL=https://yourdomain.com
TENANTED_DOMAIN=yourdomain.com
```

DNS changes can take a few minutes to several hours to propagate.

## 3. Install Server Packages

SSH as the deploy user:

```bash
ssh deploy@YOUR_DROPLET_IP
```

Install Nginx, MySQL, PHP-FPM, and required PHP extensions:

```bash
sudo apt update
sudo apt install -y \
  nginx \
  mysql-server \
  mysql-client \
  supervisor \
  cron \
  rsync \
  unzip \
  php8.3-fpm \
  php8.3-cli \
  php8.3-bcmath \
  php8.3-curl \
  php8.3-intl \
  php8.3-mbstring \
  php8.3-mysql \
  php8.3-xml \
  php8.3-zip
```

Start services:

```bash
sudo systemctl enable --now nginx mysql supervisor cron php8.3-fpm
```

## 4. Create The MySQL Database

Open MySQL:

```bash
sudo mysql
```

Create the database and user:

```sql
CREATE DATABASE onlyvo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'onlyvo'@'localhost' IDENTIFIED BY 'replace-with-a-strong-password';
GRANT ALL PRIVILEGES ON onlyvo.* TO 'onlyvo'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Use the same password in the production `.env`.

## 5. Create The App Directory

```bash
sudo mkdir -p /var/www/onlyvo
sudo chown -R deploy:www-data /var/www/onlyvo
sudo chmod -R ug+rwX /var/www/onlyvo
sudo find /var/www/onlyvo -type d -exec chmod g+s {} \;
```

## 6. Create The Production Environment

Create the production `.env` on the Droplet:

```bash
nano /var/www/onlyvo/.env
```

Use:

```dotenv
APP_NAME=Onlyvo
APP_ENV=production
APP_KEY=base64:replace-with-a-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com
TENANTED_DOMAIN=yourdomain.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=onlyvo
DB_USERNAME=onlyvo
DB_PASSWORD=replace-with-a-strong-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
VITE_API_URL=
```

Generate `APP_KEY`:

```bash
printf 'base64:%s\n' "$(openssl rand -base64 32)"
```

Replace `APP_KEY`, database password, and domain values.

## 7. Configure GitHub Secrets

In GitHub, open:

```text
Repository > Settings > Secrets and variables > Actions
```

Create these repository secrets:

```text
DO_HOST=YOUR_DROPLET_IP
DO_USER=deploy
DO_SSH_PORT=22
DEPLOY_PATH=/var/www/onlyvo
DO_SSH_PRIVATE_KEY=your-private-ssh-key-for-the-deploy-user
```

The workflow does not need GHCR or Docker secrets.

## 8. Deploy From GitHub Actions

Push to `main`, or manually run the `Deploy` workflow from GitHub Actions.

The workflow will:

1. Install Composer dependencies in GitHub Actions.
2. Install frontend dependencies in GitHub Actions.
3. Build Vite assets in GitHub Actions.
4. Put the current app into maintenance mode if it exists.
5. Sync the prepared release to `/var/www/onlyvo`.
6. Preserve `.env`, storage, logs, uploads, and `public/storage`.
7. Run `php artisan migrate --force`.
8. Run `php artisan storage:link`.
9. Cache config and views.
10. Restart queues.
11. Bring the app back up.

Check the deployed files:

```bash
ls -la /var/www/onlyvo
```

Check Laravel:

```bash
cd /var/www/onlyvo
php artisan about
```

## 9. Configure Nginx

Create the Nginx site config:

```bash
sudo nano /etc/nginx/sites-available/onlyvo
```

Use:

```nginx
server {
    listen 80;
    server_name yourdomain.com *.yourdomain.com;
    root /var/www/onlyvo/public;

    index index.php index.html;
    client_max_body_size 25m;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param HTTPS $https if_not_empty;
        fastcgi_param HTTP_PROXY "";
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/onlyvo /etc/nginx/sites-enabled/onlyvo
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 10. Configure Queue Worker

Create a Supervisor config:

```bash
sudo nano /etc/supervisor/conf.d/onlyvo-worker.conf
```

Use:

```ini
[program:onlyvo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/onlyvo/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/onlyvo/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor after the first successful deploy:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## 11. Configure Laravel Scheduler

Open the deploy user's crontab:

```bash
crontab -e
```

Add:

```cron
* * * * * cd /var/www/onlyvo && php artisan schedule:run >> /dev/null 2>&1
```

This runs the scheduled weekly tenant analytics summary command registered in `routes/console.php`.

## 12. Configure HTTPS

Install Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

This app uses tenant subdomains, so use a wildcard certificate. The certificate must cover both:

```text
yourdomain.com
*.yourdomain.com
```

Do not use the root-domain-only Certbot command for this app, because it would cover `yourdomain.com` but not tenant domains like `tenant-a.yourdomain.com`.

Wildcard certificates require DNS validation. Since this guide uses DigitalOcean DNS, install the DigitalOcean DNS plugin:

```bash
sudo apt install -y python3-certbot-dns-digitalocean
```

Create a DigitalOcean API token with DNS write access, then store it on the Droplet:

```bash
sudo mkdir -p /etc/letsencrypt/secrets
sudo nano /etc/letsencrypt/secrets/digitalocean.ini
```

Add:

```ini
dns_digitalocean_token = YOUR_DIGITALOCEAN_DNS_TOKEN
```

Secure it:

```bash
sudo chmod 600 /etc/letsencrypt/secrets/digitalocean.ini
```

Request the wildcard certificate:

```bash
sudo certbot --nginx \
  --dns-digitalocean \
  --dns-digitalocean-credentials /etc/letsencrypt/secrets/digitalocean.ini \
  -d yourdomain.com \
  -d '*.yourdomain.com'
```

Check renewal:

```bash
sudo certbot renew --dry-run
```

## 13. Final Checks

Open:

```text
https://yourdomain.com
https://yourdomain.com/admin/login
https://some-tenant.yourdomain.com
```

Check Laravel health:

```text
https://yourdomain.com/up
```

Check logs:

```bash
tail -f /var/www/onlyvo/storage/logs/laravel.log
tail -f /var/www/onlyvo/storage/logs/worker.log
```

Check services:

```bash
sudo systemctl status nginx php8.3-fpm mysql supervisor cron
sudo supervisorctl status
```

## 14. Backups

DigitalOcean Droplet backups are a simple first layer, but database dumps are still important.

Create a manual MySQL dump:

```bash
mysqldump -u onlyvo -p onlyvo > onlyvo-$(date +%F).sql
```

Before real users, automate daily dumps and copy them off the Droplet.

## Notes

- Keep `VITE_API_URL` empty for same-origin API calls through `/api`.
- Do not run `php artisan route:cache` until closure routes are removed from the app.
- The workflow preserves `.env`, `storage`, logs, uploads, and `public/storage`.
- The server does not need Node.js, Composer, Docker, or GitHub repo access for normal deploys.
