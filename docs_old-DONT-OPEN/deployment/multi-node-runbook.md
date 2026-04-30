# Multi-Node Deployment Runbook

Nexstage horizontal scaling checklist. Apply top-to-bottom before going live
with more than one web node.

---

## Pre-flight: shared state (REQUIRED on every node)

### APP_KEY
All nodes must use the **identical** `APP_KEY`. Generate once:

```bash
php artisan key:generate --show
```

Set it in every node's `.env`. Mismatched keys corrupt encrypted sessions and
signed URLs.

### Redis (session, cache, queue, Horizon)
Point all nodes at the same Redis instance (or Sentinel/Cluster):

```
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=<shared-redis-host>
REDIS_PASSWORD=<password>
```

The codebase already sets `SESSION_DRIVER=database` and `CACHE_STORE=database`
as safe single-node defaults. Switch both to `redis` for multi-node. The Redis
databases are pre-partitioned: DB 0 (default), DB 1 (cache), DB 2 (Horizon).

### File storage (PDF reports + future uploads)
`GenerateMonthlyReportJob` and any future upload actions use
`config('filesystems.default')`. On multi-node this **must** be a shared
backend:

```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=nexstage-prod
AWS_DEFAULT_REGION=...
# For DigitalOcean Spaces or other S3-compatible endpoints:
# AWS_ENDPOINT=https://<region>.digitaloceanspaces.com
```

PDF reports land at `reports/monthly/{workspace_id}/{YYYY-MM}.pdf` on the
configured disk. With `local`, each node writes to its own filesystem and the
file is lost on node replacement.

---

## Per-node post-deploy steps

Run on **every** web node after deploying a new release:

```bash
# 1. Create the public/storage symlink
php artisan storage:link

# 2. Clear route + config caches (or re-cache for production speed)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Run migrations (safe to run on one node; idempotent)
php artisan migrate --force
```

---

## Scheduler: one cron node only

`routes/console.php` uses `->onOneServer()->withoutOverlapping()` on every
scheduled task, so duplicate fires from multiple nodes acquire a Redis mutex
and the second instance exits immediately. Even so, the simplest setup is a
**single dedicated cron node** that runs:

```
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

Do **not** add the cron to web nodes unless you explicitly intend the
`onOneServer()` mutex to resolve races.

---

## Horizon: worker node(s) only

Run `php artisan horizon` on **dedicated worker node(s)**, not on web nodes.
Each worker node registers under a unique `HORIZON_NAME` so the dashboard
shows them separately:

```
HORIZON_NAME=worker-1   # unique per node
```

Web nodes should not run `horizon`. If a worker node dies, Horizon restarts
automatically via Supervisor:

```ini
[program:horizon]
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
```

---

## Load balancer / Cloudflare trusted proxies

`bootstrap/app.php` calls `$middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'))`.
The `.env.example` default is `*`, which covers Cloudflare and most VPS load
balancers. If you know your LB's CIDR, narrow it:

```
TRUSTED_PROXIES=10.0.0.0/8
```

Without this, `request()->ip()` returns the LB's internal IP, rate limiting
and audit logs are wrong, and `isSecure()` returns false behind SSL termination.

---

## Logging: centralize log streams

Default `LOG_STACK=single` writes to `storage/logs/laravel.log` on each node
separately. For multi-node, switch to a centralized channel:

**Option A — stderr (Docker/systemd captures it):**
```
LOG_STACK=stderr
```

**Option B — Papertrail:**
```
LOG_STACK=papertrail
PAPERTRAIL_URL=logs.papertrailapp.com
PAPERTRAIL_PORT=<your-port>
```

---

## Database: Postgres connection

`config/database.php` already sets `sslmode=prefer` via `DB_SSLMODE`. For a
managed DBaaS (RDS, Supabase, Neon, DO Managed Postgres) use `require`:

```
DB_CONNECTION=pgsql
DB_SSLMODE=require
```

For high-concurrency workloads (>5 web nodes), add PgBouncer in
transaction-pooling mode in front of Postgres. Laravel is compatible with
transaction-mode pooling as long as `persistent` connections are disabled
(default in this codebase).

---

## Maintenance mode

`APP_MAINTENANCE_DRIVER=file` writes `.maintenance` to local disk only — other
nodes stay up. Switch to `database` so a single artisan call affects all nodes:

```
APP_MAINTENANCE_DRIVER=database
```

Then `php artisan down` / `up` propagates instantly to all nodes.

---

## Checklist summary

- [ ] `APP_KEY` identical on all nodes
- [ ] `SESSION_DRIVER=redis`
- [ ] `CACHE_STORE=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] `FILESYSTEM_DISK=s3` (or equivalent shared backend)
- [ ] `TRUSTED_PROXIES=*` (or LB CIDR)
- [ ] `APP_MAINTENANCE_DRIVER=database`
- [ ] `LOG_STACK=stderr` or `papertrail`
- [ ] `php artisan storage:link` run on every node post-deploy
- [ ] `php artisan migrate --force` run once per deploy (one node)
- [ ] Cron configured on exactly ONE node (`schedule:run`)
- [ ] Horizon running on worker node(s), not web nodes, with unique `HORIZON_NAME`
- [ ] PgBouncer configured if deploying >5 web nodes
