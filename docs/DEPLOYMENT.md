# Production deployment

Every push to `main` runs the pipeline in `.github/workflows/ci-cd.yml`:

1. **Tests** — PHP 8.3 + MySQL 8 service, Pint style check, full PHPUnit suite, migrate/rollback check.
2. **Image** — builds the Docker image (`Dockerfile`) and pushes `ghcr.io/<owner>/clinic:sha-<commit>` and `:latest`.
3. **Deploy** — SSHes into the VPS, uploads `docker-compose.prod.yml`, pulls the image, takes a database
   backup, runs migrations, restarts `app`, `queue` and `scheduler`, and waits for `/up` to pass.

Pull requests run step 1 only. If the deploy secrets are not configured yet, step 3 is skipped with a warning
instead of failing.

## 1. One-time VPS setup

The stack expects the shared services you already run in Docker (one MySQL, one SSL reverse proxy) to be on a
common Docker network.

```bash
# Shared network (skip if it exists — use its name for DOCKER_NETWORK)
docker network create web
docker network connect web <your-mysql-container>
docker network connect web <your-proxy-container>

# Database and user in the shared MySQL
docker exec -it <your-mysql-container> mysql -uroot -p -e "
  CREATE DATABASE clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'clinic'@'%' IDENTIFIED BY '<strong-password>';
  GRANT ALL PRIVILEGES ON clinic.* TO 'clinic'@'%'; FLUSH PRIVILEGES;"

# App folder and environment
sudo mkdir -p /opt/apps/clinic && sudo chown $USER /opt/apps/clinic
cd /opt/apps/clinic
# paste .env.production.example from the repo into .env, then fill it in:
nano .env && chmod 600 .env
```

Generate `APP_KEY` with `php artisan key:generate --show` on any machine with the project, or
`docker run --rm ghcr.io/<owner>/clinic:latest php artisan key:generate --show` after the first image build.
Set `DB_HOST` to the MySQL container name on the shared network.

Create a deploy key for GitHub Actions:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/clinic_deploy -N "" -C "github-actions-clinic"
cat ~/.ssh/clinic_deploy.pub >> ~/.ssh/authorized_keys
cat ~/.ssh/clinic_deploy        # → VPS_SSH_KEY secret
```

The deploy user must be able to run `docker` (member of the `docker` group).

## 2. GitHub configuration

Repository → Settings → Environments → **production**:

| Kind | Name | Value |
|---|---|---|
| Secret | `VPS_HOST` | VPS IP or hostname |
| Secret | `VPS_USER` | SSH user |
| Secret | `VPS_SSH_KEY` | private key from above |
| Secret | `VPS_PORT` | optional, default `22` |
| Variable | `DEPLOY_PATH` | optional, default `/opt/apps/clinic` |
| Variable | `APP_URL` | e.g. `https://clinic.tubernexus.cloud` (shown on the deployment) |

You can add required reviewers to the environment if you want a manual approval before each deploy.
The image is pulled with the workflow's short-lived `GITHUB_TOKEN`, so the GHCR package can stay private.

## 3. Reverse proxy / SSL

The app container listens on **8080** and is reachable as `clinic-app:8080` on the shared network, and on
`127.0.0.1:${APP_PORT}` on the host. Point your proxy at either:

- **nginx-proxy + acme-companion**: nothing to do — set `CLINIC_DOMAIN` in `.env`; the container already
  publishes `VIRTUAL_HOST`, `VIRTUAL_PORT=8080` and `LETSENCRYPT_HOST`.
- **Nginx Proxy Manager / Traefik / Caddy in Docker**: proxy the domain to `http://clinic-app:8080`.
- **Host Nginx**: `proxy_pass http://127.0.0.1:8090;` with `proxy_set_header Host $host; X-Forwarded-For; X-Forwarded-Proto $scheme;`
  and `client_max_body_size 12m;` for lab report uploads.

Laravel trusts proxy headers and forces HTTPS URLs in production.

## 4. First deploy

Push to `main` (or run the workflow manually). Then create the first administrator — either set
`ADMIN_EMAIL`/`ADMIN_PASSWORD` in `.env` and run:

```bash
cd /opt/apps/clinic
docker compose exec app php artisan db:seed --force     # roles, permissions, lab test catalogue, admin
```

Remove `ADMIN_PASSWORD` from `.env` afterwards. Sign in, open **Settings** to set clinic branding, patient ID
format and time zone, then add staff under **Users**. Do **not** run `DemoSeeder` in production.

## 5. Operations

```bash
cd /opt/apps/clinic
docker compose ps                         # app, queue, scheduler should be healthy
docker compose logs -f app                # web + PHP logs
docker compose exec app php artisan about
docker compose exec app php artisan backup:list
```

**Rollback**: every build is tagged by commit. Re-run an earlier successful workflow, or on the VPS:

```bash
IMAGE_TAG=sha-<previous-commit> docker compose up -d
```

Migrations are additive; rolling the image back does not roll back the schema.

## 6. Backup and recovery

When `BACKUP_ENABLED=true` the scheduler container runs nightly:

- `01:30` `backup:run` — mysqldump of the database **plus** `storage/app` (lab report files, logo) into a zip on the
  `clinic-backups` volume (`storage/backups`). Set `BACKUP_ARCHIVE_PASSWORD` to encrypt archives.
- `01:00` `backup:clean` — retention: all backups for 7 days, dailies for 16 days, then weeklies/monthlies
  (see `config/backup.php`).
- Every deploy also takes a database-only backup before running migrations.

Recommendations:

1. **Keep a copy off the server.** Sync the backup volume nightly, e.g.
   `rsync -a /var/lib/docker/volumes/clinic_clinic-backups/_data/ backup-host:/clinic/` or add an S3/Backblaze disk
   to `BACKUP_DISKS` (comma-separated) after configuring it in `config/filesystems.php`.
2. **Test a restore monthly** on a scratch database.
3. Medical records may have legal retention periods in your jurisdiction — size retention accordingly.

Restore:

```bash
cd /opt/apps/clinic
docker compose exec app php artisan down
unzip -P "$BACKUP_ARCHIVE_PASSWORD" <backup>.zip -d /tmp/restore
docker exec -i <mysql-container> mysql -uclinic -p clinic < /tmp/restore/db-dumps/mysql-clinic.sql
docker compose cp /tmp/restore/var/www/html/storage/app/. app:/var/www/html/storage/app/
docker compose exec app php artisan up
```

(Archive paths mirror the container path; check with `unzip -l` first.)

## 7. Security checklist

- `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, strong `DB_PASSWORD`.
- Keep `.env` at `chmod 600`; never commit it.
- Lab reports live on the private disk; they are streamed only through authorised routes, never from `/storage`.
- Only ports 80/443 should be public; the app binds to `127.0.0.1`.
- Review the **Audit Log** periodically (sign-ins, failed logins, exports, record changes).
