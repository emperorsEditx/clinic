# Development log

## Status (Nov 2026)

| Phase | Module | Status |
|---|---|---|
| 1 | Setup, auth, users & roles, layout, clinic settings | ✅ Complete |
| 2 | Patients, profiles, medical history, search & filters | ✅ Complete |
| 3 | Appointments, visits, consultations, vitals, timeline | ✅ Complete |
| 4 | Medicine catalogue, prescriptions, durations, continuation, repeats, other medication | ✅ Complete |
| 5 | Lab requests, private uploads, review | ✅ Complete |
| 6 | Dashboard, 10 reports, PDF/Excel/CSV, print views | ✅ Complete |
| 7 | Authorisation review, audit log, tests, CI/CD, Docker deploy, backups | ✅ Complete |

**Pending / possible next steps:** SMS/email appointment reminders, patient-facing portal, multi-branch support,
two-factor authentication for staff, S3-compatible off-site backup disk.

## Architecture decisions

- **Thin controllers, service layer.** Business rules live in `app/Services` (Patient, MedicalHistory, Appointment,
  Visit, Consultation, Prescription, Medication, Laboratory, Dashboard, Timeline, Pdf, Sequence, AuditLogger).
  Multi-record operations run in DB transactions. Rule violations throw `BusinessRuleException`, rendered as a
  flash error (or 422 JSON).
- **Visit vs consultation.** A visit is the physical encounter (arrival/check-in, front-desk data); the clinical
  content (symptoms, vitals, diagnosis, plan) lives in the consultation. One visit can hold more than one
  consultation (e.g. two doctors). The spec's visit-level clinical fields are therefore captured on the consultation.
- **Immutability.** Completed consultations are edited only through `consultation_amendments` (field-level
  old/new + reason). Finalised prescriptions are never updated: an amendment clones a draft `version + 1` linked by
  `previous_version_id`; finalising it marks the old version `superseded` and discontinues its active courses.
  No delete route exists for finalised records; all clinical FKs are `RESTRICT`.
- **Medication continuation** is one `medication_courses` row per finalised item. Expected end date is inclusive:
  `start + duration − 1` (a 3-day course starting 10 Mar ends 12 Mar). A nightly job completes elapsed courses.
- **Repeats** require `prescriptions.repeat` *and* the doctor role. Each repeated item is recorded in
  `medication_repetitions`; if no draft consultation is chosen, a "medication review" visit + consultation is
  created so every prescription still belongs to a patient and consultation.
- **Numbering.** `document_sequences` with row locks generates collision-free numbers; patient IDs follow the
  configurable `{PREFIX}-{YEAR}-{SEQ}` format and reset yearly only if `{YEAR}` is used.
- **Audit.** `AuditLogger` writes `activity_logs` with user, action, morph alias, entity id, patient id, IP and
  sanitised diffs (passwords/tokens redacted). A morph map keeps stored type names stable.
- **Private files.** Lab reports are stored on the `local` disk (`storage/app/private`) with random names and
  streamed only through policy-checked controller routes with `no-store` and `nosniff` headers. The local disk's
  built-in `/storage/*` serving route is disabled.
- **Frontend.** Server-rendered Blade with small Alpine components (patient search, prescription builder,
  confirm dialogs, toasts). `Model::preventLazyLoading()` is on outside production to catch N+1 queries.

## Assumptions (configurable defaults)

- Single clinic; branding, patient ID format, hours, slot length, appointment types, time zone and date formats are
  admin settings. **Set the time zone before go-live** — timestamps are stored in the application time zone.
- Administrators have read-only clinical access; only doctors write clinical records.
- Lab technicians can look up patients (name/ID only) to attach external reports, but cannot open patient profiles.
- Notifications use the database channel (in-app bell). Mail can be added per notification once SMTP is set.

## Database changes

All schema lives in `database/migrations/2026_01_01_*`. Changes this session: none to the schema; added a
`backups` filesystem disk and published `config/backup.php` (database + `storage/app` only).

## Bugs fixed during verification

- Editing any medical-history entry crashed: the audit diff cast enum values to string.
- Saving vital signs crashed: `recorded_by` was not mass-assignable (hidden in the demo seeder, which runs unguarded).
- Prescription rows could be reordered on save: `validated()` rebuilds wildcard arrays in rule order.
- Dashboard "today" counts were wrong on SQLite: date-only `whereBetween` compared against datetime strings.
- Medicine autocomplete never opened: the JS `debounce` helper dropped `this`.
- Change log hid the removal reason when a snapshot was present.

## Testing status

- 58 feature tests, passing on SQLite and MariaDB 10.11 locally; CI runs them on MySQL 8.0.
- `PageSmokeTest` renders every page for all four roles against demo data and asserts no 5xx, plus a role/URL matrix.
- Browser check (headless Chrome): doctor flow from waiting room → consultation → catalogue medicine search →
  lab request → completion, desktop and mobile, no console errors.

## Known issues

- The Docker image could not be built inside the development sandbox (Docker Hub blocked); each build stage was
  simulated locally and passed. The first GitHub Actions run is the first real image build.
