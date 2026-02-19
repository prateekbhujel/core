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
- Bootstrap + Font Awesome integration (open source)
- Dedicated sidebar docs + settings pages
- UI-based `.env` settings editor for whitelisted keys

## Tech profile

- Laravel 12
- Blade templates
- jQuery (small runtime)
- Bootstrap 5 + Font Awesome 6 (CDN, open source)
- Custom CSS design system (`haarray.css` + `haarray.starter.css` + `haarray.bootstrap-bridge.css`)
- Vite available for future asset pipeline expansion

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Open: `http://127.0.0.1:8000`

## Starter architecture

```txt
haarray-core/
├── app/
├── public/
│   ├── css/
│   │   ├── haarray.css
│   │   ├── haarray.starter.css
│   │   └── haarray.bootstrap-bridge.css
│   ├── js/
│   │   ├── haarray.js
│   │   └── haarray.plugins.js
│   └── icons/icons.svg
├── app/Http/Controllers/SettingsController.php
├── resources/views/
│   ├── layouts/haarray.blade.php
│   ├── components/
│   │   ├── icon.blade.php
│   │   ├── editor.blade.php
│   │   ├── select.blade.php
│   │   └── confirm-modal.blade.php
│   ├── docs/starter-kit.blade.php
│   └── settings/index.blade.php
├── docs/
│   ├── SPA.md
│   └── STARTER_KIT.md
└── routes/web.php
```

## Core frontend modules

`public/js/haarray.js`

- `HTheme`: dark/light mode state
- `HToast`: notifications
- `HModal`: modal controls
- `HApi`: AJAX/form helper
- `HSPA`: partial navigation + lifecycle events

`public/js/haarray.plugins.js`

- `HConfirm`: confirmation flow for links/forms
- `HSelect`: searchable single/multi select
- `HSelectRemote`: Select2 AJAX mode with image support
- `HEditor`: rich text editor with hidden field sync
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
- Add automated tests for your domain modules before deployment

## Documentation

- Starter docs page: `/docs` (inside app)
- Settings page: `/settings` (inside app, authenticated)
- Technical docs: `docs/STARTER_KIT.md`
- SPA details: `docs/SPA.md`

## Philosophy

Haarray Core is not a heavy frontend framework replacement. It keeps Laravel simple, then layers modern UX progressively so every app can scale from MVP to production without rewriting the stack.
