# HAARRAY CORE

Laravel starter kit for Haarray products with progressive SPA behavior, reusable UI primitives, and custom frontend components.

## Why this starter

- Server-first Laravel architecture (controllers return normal Blade/redirect responses)
- Progressive enhancement via lightweight SPA engine (`HSPA`)
- Custom icon library (SVG sprite + Blade component)
- Font Awesome class-based icon support (`<i class="fa-...">`)
- Custom Select2-style component (`HSelect`)
- Remote Select2 (server-side AJAX + image/avatar results)
- Custom CKEditor-style component (`HEditor`)
- Yajra DataTables + server-side table endpoints
- Bootstrap + Font Awesome integration (open source)
- Dedicated sidebar docs + Settings control center
- Collapsible/expandable desktop sidebar + mobile hamburger sidebar
- Settings tabs for app-level controls: App & Branding, Activity, Security, Notifications, System
- Dedicated full pages for `Users` and `Access & RBAC` from sidebar
- Dedicated `Media Library` page (`/settings/media`) with folders, CSV export, and local image resize
- Built-in media/file manager endpoints for modal picker, editor picker, and settings asset injection
- Storage fallback: uses S3 when configured, otherwise local `public/uploads`
- Global search modal (`⌘K` / `Ctrl+K`) with config-driven + DB-backed results
- Editor tools now use modal UI (link/image/table) instead of browser prompts
- Built-in debug console tray for client errors + SPA failures
- Sidebar settings entries are configurable in `config/menu.php`
- UI-based `.env` settings editor for whitelisted keys
- Optional PWA install flow (manifest + service worker + install prompt)
- Settings app-branding panel with DB-backed app name/logo/favicon/app icon/theme color
- Profile modal from sidebar user menu (name/email/password/telegram/browser notify)
- Activity tab with Yajra DataTable + export
- Collapsible sidebar settings group with query-aware tab links + dedicated Users/RBAC links
- Notification tray actions: per-row mark-read + mark-all-read + optional custom audio
- Global `App\\Support\\Notifier` helper for controller/job notification dispatch
- Advanced health checker (DB/cache/storage/queue/RBAC/notifications)
- Optional local hot reload via SSE stream (no browser polling timer)
- Shared-hosting inline automation fallback (suggestions + market refresh) without requiring queue workers
- Built-in EN/NE language toggle in topbar with locale-aware UI clock
- Nepali BS + English AD dual-date support (`AD | BS`) across live clock and DataTables date columns
- Auth screens (login/register/2FA) now support EN/NE switch before login

## Tech profile

- Laravel 12
- Blade templates
- jQuery (small runtime)
- Bootstrap 5 + Font Awesome 6 (CDN, open source)
- DataTables (CDN) + `yajra/laravel-datatables-oracle`
- Custom CSS design system merged to `public/css/haarray.app.css`
- Vite available for future asset pipeline expansion

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Open: `http://127.0.0.1:8000`

Default admin seeding (full access) creates:

- `prateekbhujelpb@gmail.com`
- `admin@admin.com`

Password is from `HAARRAY_ADMIN_PASSWORD` (fallback: `Admin@12345`).

## Starter architecture

```txt
haarray-core/
├── app/
├── public/
│   ├── css/
│   │   └── haarray.app.css
│   ├── js/
│   │   └── haarray.app.js
│   └── icons/icons.svg
├── app/Http/Controllers/SettingsController.php
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── layouts/haarray.blade.php (compat alias)
│   ├── components/
│   │   ├── icon.blade.php
│   │   ├── editor.blade.php
│   │   ├── select.blade.php
│   │   └── confirm-modal.blade.php
│   ├── docs/starter-kit.blade.php
│   └── settings/
│       ├── index.blade.php
│       ├── users.blade.php
│       ├── media.blade.php
│       ├── rbac.blade.php
│       ├── rbac-create.blade.php
│       └── rbac-edit.blade.php
├── resources/views/errors/
│   ├── layout.blade.php
│   ├── 403.blade.php
│   ├── 404.blade.php
│   ├── 419.blade.php
│   ├── 429.blade.php
│   ├── 500.blade.php
│   └── 503.blade.php
├── docs/
│   ├── SPA.md
│   └── STARTER_KIT.md
└── routes/web.php
```

## Core frontend modules

Runtime source files:

- `public/js/haarray.js`
- `public/js/haarray.plugins.js`

Served bundle:

- `public/js/haarray.app.js`

- `HTheme`: dark/light mode state
- `HSidebar`: mobile toggle + desktop compact mode persistence
- `HToast`: notifications
- `HModal`: modal controls
- `HApi`: AJAX/form helper
- `HSPA`: partial navigation + lifecycle events
- `HDebug`: debug console tray + client error capture
- `HSearch`: global search overlay
- `HMediaManager`: global media browser/upload/picker
- `HNepaliDate`: Bikram Sambat conversion + dual date formatter

- `HConfirm`: confirmation flow for links/forms
- `HSelect`: searchable single/multi select
- `HSelectRemote`: Select2 AJAX mode with image support
- `HDataTable`: DataTables initializer compatible with SPA swaps
- `HEditor`: advanced rich text editor (headings/lists/alignment/link/image/table/code + hidden field sync)
- `HIcons`: runtime icon helper
- `HSvgPie`: lightweight pie charts

## SPA lifecycle hooks

Use these events for page-level re-initialization:

```js
document.addEventListener('hspa:beforeLoad', (event) => {
  console.log(event.detail.url);
});

document.addEventListener('hspa:afterSwap', (event) => {
  console.log(event.detail.container);
});
```

## Reusable Blade components

```blade
<x-icon name="dashboard" class="h-icon h-icon--lg" label="Dashboard" />
<i class="fa-brands fa-facebook-f"></i>

<x-select
  name="currency"
  label="Currency"
  :options="['NPR' => 'Nepalese Rupee', 'USD' => 'US Dollar']"
  placeholder="Choose currency"
/>

<x-editor name="notes" label="Notes" placeholder="Write notes..." />
```

## Production readiness checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Run `php artisan config:cache`, `route:cache`, `view:cache`
- Build and serve versioned assets (`npm run build` when bundling custom assets)
- Ensure DB credentials, queue, mail, and cache drivers are configured
- Verify role/permission assignments and route visibility before deployment

## Starter commands

```bash
# Sync permissions/roles (and optionally ensure admin users)
php artisan haarray:permissions:sync --seed-admins

# Full starter bootstrap helper
php artisan haarray:starter:setup --seed-admins

# Health diagnostics from CLI
php artisan haarray:health:check
```

### Shared hosting (no queue worker required)

Inline automation is enabled by default, so key updates can run naturally during normal authenticated page requests:

- `HAARRAY_INLINE_AUTOMATION=true`
- `HAARRAY_INLINE_SUGGESTIONS_EVERY_SECONDS=900`
- `HAARRAY_INLINE_MARKET_REFRESH_EVERY_SECONDS=3600`

This reduces hard dependency on VPS-managed queue workers for core user flows.

## Running modes

### Artisan serve

```bash
php artisan serve
```

### PHP built-in server via root `server.php`

```bash
php -S 127.0.0.1:8000 server.php
```

### XAMPP/shared hosting (project root web path)

- Root `.htaccess` rewrites traffic into `/public` automatically.
- Root `server.php` supports base-path style routing for:
  - `/harray-core`
  - `/haaray-core`
  - `/haaray`
- Both project URLs are supported:
  - `http://localhost/harray-core`
  - `http://localhost/haaray-core` (compatibility alias)
- Keep writable folders ready:

```bash
chmod -R 0777 storage bootstrap/cache public/uploads
chmod 0666 .env
```

If Blade cache writes fail, run the chmod commands above again.

## Documentation

- Starter docs page: `/docs` (inside app)
- Settings page: `/settings` (inside app, authenticated)
- Technical docs: `docs/STARTER_KIT.md`
- SPA details: `docs/SPA.md`
- Tutorials:
  - `docs/tutorials/CRUD_WORKFLOW.md`
  - `docs/tutorials/NOTIFIER_HELPER.md`
  - `docs/tutorials/MEDIA_MANAGER.md`
  - `docs/tutorials/DEPLOYMENT_MODES.md`
  - `docs/tutorials/INTEGRATIONS_FACEBOOK_TELEGRAM.md`

## Localization and dual dates

- Toggle language from topbar (`EN/ने`) or auth screens before login.
- Set default language globally from `Settings -> Branding` via `Default Language`.
- UI dates can render as dual calendar text (`AD | BS`) for easier EN/NE usage.

## Philosophy

Haarray Core is not a heavy frontend framework replacement. It keeps Laravel simple, then layers modern UX progressively so every app can scale from MVP to production without rewriting the stack.

## GitHub Actions auto-deploy to cPanel

Workflow: `.github/workflows/deploy-cpanel-core.yml`

Every push to `main` deploys this repository to your shared-hosting path through SSH + `rsync`, then runs composer/artisan optimization on the server.

Required repository secrets:

- `CPANEL_HOST`
- `CPANEL_SSH_PORT` (optional, defaults to `22`)
- `CPANEL_USER`
- `CPANEL_SSH_PRIVATE_KEY`
- `CORE_DEPLOY_PATH` (example: `/home8/pratikb1/core`)
- `CORE_RUN_MIGRATIONS` (`1` or `0`)
- `CORE_DEPLOY_DELETE` (`1` or `0`)

Deploy behavior:

- Preserves runtime data by excluding `.env`, `storage/`, and `public/uploads/`.
- Keeps `vendor/` out of upload and runs `composer install` server-side when composer exists.
