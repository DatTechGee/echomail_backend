# echomail_backend

Laravel 12 REST API backend for the EchoMail email marketing platform.

## Requirements

- PHP ^8.2
- Composer
- MySQL or SQLite

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Scheduled tasks

Add cron entries for `php artisan schedule:run` (every minute) and `php artisan queue:work --stop-when-empty` (every minute) when `QUEUE_CONNECTION=database`.

## Hostinger note

Deploy the whole project under `public_html/echomail/` (the included root `.htaccess` rewrites into `public/`). See `.env.hostinger` for a production env template.
