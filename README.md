# Alaise Leads Portal

Laravel 13 rebuild of the PHP 7.4 leads admin (`admin.alaise.co.uk`). Blade + Livewire 4, Tailwind 4, MySQL.

Requires **PHP 8.3+** (the server runs 8.5) and MySQL 5.7+ / MariaDB 10.3+. No Node is needed on the server: the compiled CSS is committed in `public/build`.

## What it covers

| Area | Pages |
|---|---|
| Bookings (all four businesses) | Add booking, customer lists with search and CSV export, booking page with notes, documents and history |
| Studios (Flesh, Salford, MTS) | Daily tracker, marketing spend, marketing performance |
| TRUK | New bookings from Timely, customers, appointments, technician performance, calls to make, unassigned appointments, month on month |
| Admin | Users, per-business settings, system settings |
| Automation | Timely webhook, auto-booking of returning customers, nightly "not dispositioned" job |

The four copies of each page in the old system are now one page per feature, scoped by business in the URL (`/flesh/tracker`, `/mts/tracker`, ...).

## How releases work

Code is pushed to `main` on GitHub. A GitHub Action (`.github/workflows/build.yml`) installs the Composer packages on PHP 8.5, builds the CSS, runs the tests, and commits `composer.lock` and `public/build` back to `main`. The server then pulls and deploys through cPanel's Git Version Control, which runs `.cpanel.yml`.

To release: wait for the green tick on the Actions tab, then cPanel > Git Version Control > Manage > Pull or Deploy > **Update from Remote** > **Deploy HEAD Commit**.

## First-time server set-up (cPanel account, not WHM root)

1. **PHP.** MultiPHP Manager: `crm.alaise.co.uk` on ea-php85. On the command line always use `/opt/cpanel/ea-php85/root/usr/bin/php`; plain `php` is the server default (8.2), which is too old.
2. **Deploy key.** cPanel > SSH Access > Manage SSH Keys > Generate a New Key with **no passphrase**, then Manage > Authorize. Add its public key to the GitHub repo under Settings > Deploy keys.
3. **Clone.** cPanel > Git Version Control > Create > Clone a Repository. URL `git@github.com:bigjonpenn75/alaise-crm.git`, path `repositories/alaise-crm`. The folder must not exist beforehand.
4. **Document root.** cPanel > Domains > `crm.alaise.co.uk` > Manage: set the document root to `repositories/alaise-crm/public`.
5. **Databases.** cPanel > MySQL Databases: create a new database and user (all privileges). Add the same user to the old `alaiseuk_leadsadmin` database so the import can read it.
6. **Environment.** Copy `.env.example` to `.env` in File Manager and fill in `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (host `localhost`).
7. **First deploy.** Git Version Control > Manage > Pull or Deploy > Deploy HEAD Commit. Then in cPanel Terminal:
   ```bash
   cd ~/repositories/alaise-crm
   /opt/cpanel/ea-php85/root/usr/bin/php artisan key:generate --force
   /opt/cpanel/ea-php85/root/usr/bin/php artisan alaise:import-legacy
   /opt/cpanel/ea-php85/root/usr/bin/php artisan optimize
   ```
   The import prints rows read and written per table. Re-run it with `--fresh` at cutover.
8. **SSL.** cPanel > SSL/TLS Status > run AutoSSL for the subdomain.

## At cutover

- **Final import:** `php artisan alaise:import-legacy --fresh` (replaces everything in the new database).
- **Uploaded documents:** copy the old `uploaded_docs/` files into `storage/app/private/documents/legacy/`. They are served through the app to signed-in managers only.
- **Cron**, every minute, and remove the old entries for `update_existing.php` and `update-dispositions.php`:
  ```
  * * * * * /opt/cpanel/ea-php85/root/usr/bin/php /home/<user>/repositories/alaise-crm/artisan schedule:run >/dev/null 2>&1
  ```
- **Timely:** change the webhook URL to `https://crm.alaise.co.uk/api/timely/leads`.

## Users and sign-in

Usernames and passwords carry over. The old system already stored bcrypt hashes, which Laravel verifies as they are.

| Level | Can do |
|---|---|
| Superuser | Everything in every business, including month on month |
| Management, Marketing | Reports, customers, users and settings for the businesses ticked on their account |
| Reception | Dashboard, add booking |
| Standard | Add booking; My appointments if linked to a technician |

The old system only hid menu items. Every page now checks the level and the business on the server.

## Timely webhook

`POST /api/timely/leads` takes the same form fields as the old `api/timely_leads.php` (`firstName`, `lastName`, `tel`, `emailAddress`, `location`, `nextAppointment`) and returns the same JSON shape. The old path `/api/timely_leads.php` also works on the new hostname.

Set `TIMELY_WEBHOOK_TOKEN` in `.env` to require a shared secret (`?token=...` or an `X-Webhook-Token` header). Left empty, anyone can post, which is how the old endpoint behaved.

## Rules that used to be hard-coded

These were ID lists inside the old PHP files. They are now data, seeded with the old values by the import (see `app/Support/LegacyMap.php`), and editable under Settings.

- **Reception staff.** The old trackers listed "reception" `lead_users` IDs in each file, and the lists had drifted apart between pages. There is now one Reception tick per staff member, seeded from the longest (latest) list.
- **Walk-ins count as reception.** A tick on the lead source (System settings).
- **Channel sources.** Which lead sources count as Google / Facebook / Instagram / TikTok, per business (bottom of each business's settings page).
- **Disposition buckets.** Which dispositions count as Converts / Lost / To be contacted in technician performance (TRUK settings).

## Differences from the old system

- **TRUK marketing performance** queried `truk_customer.sourceID` and `fullCost`, columns that do not exist, so those cells were always blank. Revenue is now appointment fees for the day (no-shows and cancellations excluded, as month on month does), and channel leads come from bookings logged against TRUK.
- **Deleting** a booking, customer or appointment is a soft delete. Rows the old system had flagged `active = 0` are imported as soft-deleted.
- **Auto-booking from Timely** skips bookings whose date has already passed, so the backlog of old unprocessed bookings is not booked in bulk at cutover.
- **User types** are fixed (they map to permissions), so "add user type" is gone.
- **Not carried over:** `export-all-leads.php`, `get-jb-data.php` and the wills/LPA email templates belonged to a different system and queried tables this database does not have. Customer lists have their own CSV export.

## What the import leaves behind

`leads_050623`, `leads_150623`, `truk_appointments_temp`, `truk_customer_temp`, `truk_all_appointments`, `truk_cron_test`, `admin_options`, `user_levels`: backups, scratch tables and settings that are now config. Also skipped: lead source 0 "No Source Added" (stored as blank), bookings and spend rows with no business or a `0000-00-00` date, and notes/history whose customer no longer exists. The old database is only read, never changed.

## Development

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate   # set APP_ENV=local, DB_CONNECTION=sqlite for a quick start
php artisan migrate --seed                          # reference data + an "admin" superuser (password printed)
npm run build                                       # after changing views or CSS; commit public/build
php artisan serve
vendor/bin/phpunit
```
