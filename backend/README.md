# BloodCare

BloodCare is a Laravel and Backpack application for blood donor services and
internal blood-donation management.

The application has two interfaces:

- A public donor website for registration, appointment booking, appointment
  lookup, digital donor cards, eligibility guidance and general information.
- A protected Backpack workspace for staff to manage donors, cards, donation
  records, blood inventory, appointments and system history.

## Current milestone

The first UI milestone is complete.

- Responsive BloodCare public design system
- Public homepage and donor-service pages
- Server-side validation for prototype registration and appointment forms
- Branded Backpack vertical sidebar
- Staff operations dashboard with sample statistics
- Protected preview pages for all six main management modules
- Backpack dashboard and sidebar view overrides under
  `resources/views/vendor/backpack/ui`

The displayed records and statistics are intentionally sample data. The forms
validate input but do not save it yet. Database models, migrations and CRUD
operations are the next milestone.

## Requirements

- PHP 8.3 or newer
- Composer
- Docker Desktop for the project MySQL service
- The existing `.env` and root `.env.docker` files

The current UI uses plain local CSS and JavaScript, so Node is not required just
to run this milestone.

## Start the project

From the project root:

```powershell
docker compose --env-file .env.docker up -d
```

Then from `backend`:

```powershell
composer install
php artisan optimize:clear
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8001
```

If `storage:link` reports that the link already exists, continue normally.

Open:

- Public website: http://127.0.0.1:8001
- Staff login: http://127.0.0.1:8001/admin/login
- Staff dashboard: http://127.0.0.1:8001/admin/dashboard
- phpMyAdmin: http://127.0.0.1:8081

Keep `APP_URL=http://127.0.0.1:8001` in `.env` so Backpack redirects and Basset
assets use the correct port.

## Stop the project

Press `Ctrl+C` in the terminal running Laravel. From the project root:

```powershell
docker compose --env-file .env.docker stop
```

Do not use `docker compose down -v` unless you intentionally want to delete the
database volume.

## Next development milestone

1. Create donor, donation-card, appointment, donation, blood-unit and history
   migrations.
2. Add Eloquent models and relationships.
3. Replace sample dashboard figures with database queries.
4. Generate and customize Backpack CRUD controllers.
5. Make accepted donations update donor eligibility, inventory and history in
   one database transaction.
6. Add feature tests for those workflows.
