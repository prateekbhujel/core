# Haarray Starter Kit Guide

This document explains how to use the reusable frontend modules bundled with Haarray Core.

## 1. Asset loading order

Load in this order inside your main layout:

1. `bootstrap.css`
2. `font-awesome.css`
3. `public/css/haarray.css`
4. `public/css/haarray.starter.css`
5. `public/css/haarray.bootstrap-bridge.css`
6. jQuery
7. `bootstrap.bundle.js`
8. `public/js/haarray.js`
9. `public/js/haarray.plugins.js`

`#h-spa-content` must wrap your content region for partial page swaps.

## 2. Icon system

### Blade component

```blade
<x-icon name="wallet" class="h-icon h-icon--lg" label="Wallet" />
```

### Runtime icon injection

```html
<span data-icon="chart-line" data-icon-size="20" class="h-icon"></span>
```

All icons are stored in `public/icons/icons.svg`.

## 3. Select component (`HSelect`)

`HSelect` upgrades native `<select>` elements while preserving native form submission.

### Blade usage

```blade
<x-select
  name="currency"
  label="Primary Currency"
  :options="['NPR' => 'Nepalese Rupee', 'USD' => 'US Dollar']"
  placeholder="Choose currency"
/>
```

### Multi-select

```blade
<x-select
  name="watchlist"
  label="Watchlist"
  :options="$currencies"
  :value="['USD', 'EUR']"
  :multiple="true"
/>
```

### Behavior

- Search enabled automatically for larger lists
- Keyboard open (`Enter`, `Space`, `ArrowDown`)
- `Esc` and outside click close dropdown
- Selected values remain on native `<select>` for backend validation/submission

## 3.1 Remote Select2 (`HSelectRemote`)

Use for large datasets with server-side filtering and optional image/avatar rendering.

```blade
<select
  data-select2-remote
  data-endpoint="{{ route('ui.options.leads') }}"
  data-placeholder="Search leads..."
  data-min-input="1">
  <option value="{{ auth()->id() }}" selected data-image="https://...">{{ auth()->user()->name }}</option>
</select>
```

`routes/web.php` already includes a sample endpoint:

- `GET /ui/options/leads` -> `ui.options.leads`

## 4. Rich editor (`HEditor`)

`HEditor` is a lightweight rich text block with formatting toolbar.

### Blade usage

```blade
<x-editor
  name="announcement"
  label="Announcement"
  value="<p>Welcome</p>"
  placeholder="Write update..."
/>
```

### Bare mode

```blade
<x-editor name="body" :bare="true" />
```

### Notes

- Hidden `<textarea>` is injected automatically into the parent form
- Sanitization removes inline script/style/event handlers
- Default paste mode is plain text

## 5. Confirm modal (`HConfirm`)

Attach to links/forms with `data-confirm="true"`.

### Link example

```html
<a href="/posts/1"
   data-confirm="true"
   data-confirm-method="DELETE"
   data-confirm-title="Delete post?"
   data-confirm-text="This action cannot be undone.">
   Delete
</a>
```

### Form example

```html
<form action="/posts/1" method="POST" data-confirm="true">
  <input type="hidden" name="_method" value="DELETE">
  <button type="submit">Delete</button>
</form>
```

Include `<x-confirm-modal />` once in your root layout.

## 6. SPA engine (`HSPA`)

Mark links/forms with `data-spa` for AJAX navigation/submission.

### Example

```blade
<a href="{{ route('docs.index') }}" data-spa>Docs</a>

<form action="{{ route('login.post') }}" method="POST" data-spa>
  @csrf
  ...
</form>
```

### Lifecycle events

```js
document.addEventListener('hspa:beforeLoad', (event) => {
  console.log('before', event.detail.url);
});

document.addEventListener('hspa:afterSwap', (event) => {
  console.log('swapped', event.detail.container);
});


document.addEventListener('hspa:error', (event) => {
  console.error(event.detail);
});
```

## 7. UI utility layer

`haarray.starter.css` provides reusable building blocks:

- Layout: `.h-container`, `.h-grid`, `.h-stack`, `.h-row`
- Surfaces: `.h-card-soft`, `.h-note`, `.h-pill`
- Data display: `.h-table`, `.h-table-wrap`
- Component styling for `HSelect` and `HEditor`

## 8. Production recommendations

- Keep pages functional without JavaScript
- Use server-side validation as source of truth
- Add feature tests for each componentized form flow
- Avoid inline scripts when reusable module hooks are enough
- Define project-specific tokens on top of `haarray.css` variables
- Restrict `.env` UI access to trusted users only (route: `/settings`)
- Keep Telegram and ML threshold keys under source-controlled `.env.example` for consistent environments
