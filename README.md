# Clinic Management System

A production-ready clinic platform built with **Laravel 13, PHP 8.3 and MySQL 8**: patient registration and
medical history, appointments and visits, doctor consultations, digital prescriptions with versioning,
medication continuation and authorised repeat prescriptions, laboratory requests and private report storage,
reports with PDF/Excel export, and a full audit trail.

## Features

| Module | Highlights |
|---|---|
| **Patients** | Configurable permanent IDs (`PAT-2026-00001`), duplicate detection (name + phone/DOB), search by name/ID/phone, filters, archive & restore, print profile, full history PDF |
| **Medical history** | Structured entries: current & historical conditions, previous diseases, surgeries, family history, allergies (type/severity), clinical notes; every change kept in a per-entry change log |
| **Appointments & visits** | Slot-based booking with double-booking protection, status workflow, check-in creates a visit; walk-ins; every encounter is a separate, never-overwritten visit |
| **Consultations** | Doctor workspace with the patient's allergies, history, medications, previous consultations, prescriptions and lab reports alongside; vitals with BMI; drafts; completion locks the record; corrections are tracked amendments (old/new values + reason) |
| **Prescriptions** | Multiple medicines (catalogue search or custom), templates, copy from previous Rx, allergy warning, branded PDF; finalised prescriptions are immutable — amendments create a new version and supersede the old one |
| **Medication** | Per-medicine courses (start, days, expected end, status, reason); repeat prescriptions only by an authorising doctor, with reason and links to the source; other / intercurrent medication kept separate from clinic prescriptions |
| **Laboratory** | Test catalogue, requests with priority, technician status workflow, PDF/JPG/PNG uploads (extension *and* content checked) on the private disk, authorised download/preview, doctor review |
| **Dashboard** | Role-aware cards and lists: patients, appointments, waiting room, visits, pending labs, follow-ups, with date filters |
| **Reports** | 10 reports (registrations, daily/monthly visits, doctor-wise, appointments, prescriptions, medication continuation, repeats, lab summary, follow-ups) with filters, Excel/CSV/PDF export and print views |
| **Admin** | Users & roles, clinic branding and document settings, patient ID format, appointment hours, time zone, date formats, audit log |

### Roles

| | Admin | Doctor | Receptionist | Lab technician |
|---|:-:|:-:|:-:|:-:|
| Register / edit patients | ✓ | | ✓ | |
| View clinical records | read-only | ✓ | | lab only |
| Medical history, consultations, prescriptions, repeats | | ✓ | | |
| Appointments & visits | ✓ | visits | ✓ | |
| Request tests / review reports | | ✓ | | |
| Update lab status / upload reports | | | | ✓ |
| Reports, users, settings, audit log | ✓ | | | |

Permissions are enforced server-side with Policies and `spatie/laravel-permission`; menus only reflect them.
One user can hold several roles (e.g. a clinic owner who is also a doctor).

## Stack

Laravel 13 · PHP 8.3 · MySQL 8 · Blade + Tailwind CSS 4 + Alpine.js · Vite.
Packages: `spatie/laravel-permission` (roles), `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (exports),
`spatie/laravel-backup` (backups). Activity logging is a small in-house service (`App\Services\AuditLogger`)
so clinical diffs, redaction and patient linkage are under our control.

## Local installation

Requirements: PHP 8.3 (pdo_mysql, gd, intl, bcmath, zip), Composer 2, Node 22, MySQL 8.

```bash
git clone https://github.com/emperorsEditx/clinic.git && cd clinic
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env, then:
php artisan migrate --seed                       # roles, permissions, lab test catalogue
php artisan db:seed --class=DemoSeeder           # optional demo data (password: "password")
npm ci && npm run build
php artisan storage:link
php artisan serve
```

Demo accounts (DemoSeeder only): `admin@clinic.test`, `doctor@clinic.test`, `reception@clinic.test`,
`lab@clinic.test` — password `password`. To create a real first admin, set `ADMIN_EMAIL` and `ADMIN_PASSWORD`
in `.env` and run `php artisan db:seed`.

Run a queue worker and the scheduler for notifications, nightly medication-course completion and backups:

```bash
php artisan queue:work
php artisan schedule:work
```

## Tests

```bash
php artisan test                  # SQLite in memory (default)
DB_CONNECTION=mysql DB_DATABASE=clinic_test php artisan test   # against MySQL
vendor/bin/pint --test            # code style
```

58 feature tests cover authentication, role restrictions, patient IDs and duplicates, search, visits and
consultations, amendment history, prescriptions and versioning, duration calculations, repeat authorisation,
lab uploads and file validation, private file access, and a smoke test that renders every screen for every role.

## Deployment

Pushes to `main` are tested on MySQL 8, built into a Docker image on GHCR and deployed to the VPS over SSH with
Docker Compose (app + queue + scheduler). See **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** for server setup,
GitHub secrets, reverse proxy options, rollback, and backup & recovery.

## Documentation

- [docs/ERD.md](docs/ERD.md) — database relationship diagram
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — production deployment, backups, recovery
- [DEVELOPMENT.md](DEVELOPMENT.md) — module status, architecture decisions, known issues

> Medicine names, doses and templates in the demo data and tests are illustrative fixtures, not clinical
> recommendations. The system records the prescriber's decisions; it does not suggest treatment.
