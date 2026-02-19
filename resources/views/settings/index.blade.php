@extends('layouts.app')

@section('title', 'Settings')
@section('page_title', 'Settings')

@section('topbar_extra')
  <span class="h-live-badge">
    <i class="fa-solid fa-sliders"></i>
    Settings Center
  </span>
@endsection

@section('content')
@php
  $defaultSettingsTab = (string) request()->query('tab', 'settings-app');
  $themeColor = (string) ($uiBranding['theme_color'] ?? '#2f7df6');
  $logoUrl = (string) ($uiBranding['logo_url'] ?? '');
  $faviconUrl = (string) ($uiBranding['favicon_url'] ?? '');
  $appIconUrl = (string) ($uiBranding['app_icon_url'] ?? '');
  $dbLabel = ($dbConnectionInfo['database'] ?? '') !== '' ? (string) $dbConnectionInfo['database'] : 'n/a';
  $mlDefaults = (array) ($mlDiagnostics['probe_defaults'] ?? []);
@endphp

<div class="hl-docs hl-settings">
  <div class="doc-head">
    <div>
      <div class="doc-title">Settings Control Panel</div>
      <div class="doc-sub">
        One page for app branding, activity, security, notifications, system config and diagnostics.
      </div>
    </div>
    <span class="h-pill teal">DB: {{ $dbLabel }}</span>
  </div>

  <div class="h-tab-shell h-settings-shell h-settings-shell--sidebar-nav" id="settings-main-tabs" data-ui-tabs data-default-tab="{{ $defaultSettingsTab }}">
    <div class="h-tab-nav" role="tablist" aria-label="Settings sections">
      <button type="button" class="h-tab-btn" data-tab-btn="settings-app"><i class="fa-solid fa-palette"></i> App & Branding</button>
      <button type="button" class="h-tab-btn" data-tab-btn="settings-activity"><i class="fa-solid fa-chart-line"></i> Activity</button>
      <button type="button" class="h-tab-btn" data-tab-btn="settings-security"><i class="fa-solid fa-user-shield"></i> Security</button>
      <button type="button" class="h-tab-btn" data-tab-btn="settings-notifications"><i class="fa-solid fa-bell"></i> Notifications</button>
      @if($canManageSettings)
        <button type="button" class="h-tab-btn" data-tab-btn="settings-system"><i class="fa-solid fa-gear"></i> System</button>
        <button type="button" class="h-tab-btn" data-tab-btn="settings-diagnostics"><i class="fa-solid fa-stethoscope"></i> Diagnostics</button>
      @endif
    </div>

    <div class="h-tab-panel" data-tab-panel="settings-app">
      @if($canManageSettings)
        <div class="h-card-soft mb-3">
          <div class="head">
            <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Branding, Icons, Theme</div>
            <div class="h-muted" style="font-size:13px;">Set app title, theme color, logo/favicon/app icon and manage assets from one place.</div>
          </div>
          <div class="body">
            <form method="POST" action="{{ route('settings.branding') }}" enctype="multipart/form-data" data-spa>
              @csrf
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">App Name</label>
                  <input type="text" name="ui_display_name" class="form-control" value="{{ old('ui_display_name', $uiBranding['display_name'] ?? config('app.name')) }}" required>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Brand Subtitle</label>
                  <input type="text" name="ui_brand_subtitle" class="form-control" value="{{ old('ui_brand_subtitle', $uiBranding['brand_subtitle'] ?? ('by ' . config('haarray.brand_name'))) }}">
                </div>
                <div class="col-md-2">
                  <label class="h-label" style="display:block;">Mark</label>
                  <input type="text" name="ui_brand_mark" class="form-control" maxlength="8" value="{{ old('ui_brand_mark', $uiBranding['brand_mark'] ?? 'H') }}" required>
                </div>
                <div class="col-md-2">
                  <label class="h-label" style="display:block;">Theme Color</label>
                  <input type="color" name="ui_theme_color" class="form-control form-control-color" value="{{ old('ui_theme_color', $themeColor) }}">
                </div>

                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Logo URL</label>
                  <input type="text" name="ui_logo_url" id="ui-logo-url" class="form-control" value="{{ old('ui_logo_url', $logoUrl) }}" placeholder="https://.../logo.png">
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Favicon URL</label>
                  <input type="text" name="ui_favicon_url" id="ui-favicon-url" class="form-control" value="{{ old('ui_favicon_url', $faviconUrl) }}" placeholder="https://.../favicon.ico">
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">App Icon URL</label>
                  <input type="text" name="ui_app_icon_url" id="ui-app-icon-url" class="form-control" value="{{ old('ui_app_icon_url', $appIconUrl) }}" placeholder="https://.../app-icon.png">
                </div>

                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Upload Logo</label>
                  <label class="h-file-dropzone">
                    <input type="file" name="ui_logo_file" data-file-preview accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                    <img src="" alt="Logo preview" class="h-file-preview">
                    <span class="h-file-copy">Drop logo here or click to browse</span>
                  </label>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Upload Favicon</label>
                  <label class="h-file-dropzone">
                    <input type="file" name="ui_favicon_file" data-file-preview accept=".ico,.png,.webp,.svg,image/*">
                    <img src="" alt="Favicon preview" class="h-file-preview">
                    <span class="h-file-copy">Drop favicon here or click to browse</span>
                  </label>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Upload App Icon</label>
                  <label class="h-file-dropzone">
                    <input type="file" name="ui_app_icon_file" data-file-preview accept=".ico,.jpg,.jpeg,.png,.webp,.svg,image/*">
                    <img src="" alt="App icon preview" class="h-file-preview">
                    <span class="h-file-copy">Drop app icon here or click to browse</span>
                  </label>
                </div>
              </div>

              <div class="h-note mt-3">
                Active DB from `.env`: <code>{{ $dbConnectionInfo['connection'] }}</code> / <code>{{ $dbConnectionInfo['database'] ?: 'n/a' }}</code>
              </div>

              <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" data-busy-text="Saving...">
                  <i class="fa-solid fa-floppy-disk me-2"></i>
                  Save App Settings
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="h-card-soft mb-3">
          <div class="head">
            <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Media Library</div>
            <div class="h-muted" style="font-size:13px;">Click any asset below to apply as logo, favicon, or app icon.</div>
          </div>
          <div class="body">
            @if(empty($mediaLibrary))
              <div class="h-note">No branding assets uploaded yet.</div>
            @else
              <div class="h-media-grid">
                @foreach($mediaLibrary as $asset)
                  <div class="h-media-card">
                    <img src="{{ $asset['url'] }}" alt="{{ $asset['name'] }}">
                    <div class="h-media-meta">
                      <div class="h-media-name" title="{{ $asset['name'] }}">{{ $asset['name'] }}</div>
                      <div class="h-muted" style="font-size:11px;">{{ $asset['size_kb'] }} KB • {{ $asset['modified_at'] }}</div>
                    </div>
                    <div class="h-media-actions">
                      <button type="button" class="btn btn-outline-secondary btn-sm" data-media-pick data-media-target="ui-logo-url" data-media-url="{{ $asset['url'] }}">Logo</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" data-media-pick data-media-target="ui-favicon-url" data-media-url="{{ $asset['url'] }}">Favicon</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" data-media-pick data-media-target="ui-app-icon-url" data-media-url="{{ $asset['url'] }}">App Icon</button>
                      <form method="POST" action="{{ route('settings.branding.media.delete') }}" data-spa data-confirm="true" data-confirm-title="Delete media?" data-confirm-text="This media file will be removed permanently.">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="asset_path" value="{{ $asset['path'] ?? '' }}">
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete media">
                          <i class="fa-solid fa-xmark"></i>
                        </button>
                      </form>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      @else
        <div class="h-note">Only users with <code>manage settings</code> can update branding.</div>
      @endif
    </div>

    <div class="h-tab-panel" data-tab-panel="settings-activity">
      <div class="h-card-soft mb-3">
        <div class="head h-split">
          <div>
            <div style="font-family:var(--fd);font-size:16px;font-weight:700;">User Activity Log</div>
            <div class="h-muted" style="font-size:13px;">Tracks routes, method, status and user identity by default.</div>
          </div>
          <a href="{{ route('settings.activity.export') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-file-export me-2"></i>
            Export Activity
          </a>
        </div>

        <div class="body">
          <div class="table-responsive">
            <table
              class="table table-sm align-middle"
              data-h-datatable
              data-endpoint="{{ route('ui.datatables.activities') }}"
              data-page-length="20"
              data-length-menu="10,20,50,100"
              data-order-col="0"
              data-order-dir="desc"
            >
              <thead>
                <tr>
                  <th data-col="id">ID</th>
                  <th data-col="created_at">Datetime</th>
                  <th data-col="user">User</th>
                  <th data-col="method">Method</th>
                  <th data-col="path">Path</th>
                  <th data-col="route_name">Route</th>
                  <th data-col="status">Status</th>
                  <th data-col="ip_address">IP</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="h-tab-panel" data-tab-panel="settings-security">
      <div class="h-card-soft mb-3">
        <div class="head">
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Personal Security</div>
          <div class="h-muted" style="font-size:13px;">2FA and your notification channel preferences.</div>
        </div>
        <div class="body">
          <form method="POST" action="{{ route('settings.security') }}" data-spa>
            @csrf
            <div class="row g-3">
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Telegram Chat ID</label>
                <input type="text" name="telegram_chat_id" class="form-control" value="{{ old('telegram_chat_id', auth()->user()->telegram_chat_id) }}" placeholder="e.g. 123456789">
              </div>
              <div class="col-md-6" style="padding-top:24px;">
                <div class="h-radio-stack">
                  <label class="form-check"><input class="form-check-input" type="checkbox" name="two_factor_enabled" value="1" @checked(auth()->user()->two_factor_enabled)><span class="form-check-label">Enable 2FA login</span></label>
                  <label class="form-check"><input class="form-check-input" type="checkbox" name="receive_in_app_notifications" value="1" @checked(auth()->user()->receive_in_app_notifications)><span class="form-check-label">In-app notifications</span></label>
                  <label class="form-check"><input class="form-check-input" type="checkbox" name="receive_telegram_notifications" value="1" @checked(auth()->user()->receive_telegram_notifications)><span class="form-check-label">Telegram notifications</span></label>
                  <label class="form-check"><input class="form-check-input" type="checkbox" name="browser_notifications_enabled" value="1" @checked(auth()->user()->browser_notifications_enabled)><span class="form-check-label">Browser notifications</span></label>
                </div>
              </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary" data-busy-text="Saving...">
                <i class="fa-solid fa-shield-halved me-2"></i>
                Save Security Preferences
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="h-tab-panel" data-tab-panel="settings-notifications">
      <div class="h-card-soft mb-3">
        <div class="head">
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Broadcast Notifications</div>
          <div class="h-muted" style="font-size:13px;">Send in-app + Telegram alerts and keep browser alerts noticeable with sound + vibration.</div>
        </div>
        <div class="body">
          @can('manage notifications')
            <form method="POST" action="{{ route('notifications.broadcast') }}" data-spa>
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Title</label>
                  <input type="text" name="title" class="form-control" placeholder="System update" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Level</label>
                  <select name="level" class="form-select" data-h-select>
                    <option value="info">Info</option>
                    <option value="success">Success</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="h-label" style="display:block;">Message</label>
                  <textarea name="message" class="form-control" rows="3" placeholder="Write notification message..." required></textarea>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Audience</label>
                  <select name="audience" class="form-select" data-h-select>
                    <option value="all">All users</option>
                    <option value="role">By role</option>
                    <option value="users">Specific users</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Channels</label>
                  <div class="h-radio-stack" style="grid-template-columns:1fr;">
                    <label class="form-check">
                      <input class="form-check-input" type="checkbox" name="channels[]" value="in_app" checked>
                      <span class="form-check-label">In-app</span>
                    </label>
                    <label class="form-check">
                      <input class="form-check-input" type="checkbox" name="channels[]" value="telegram">
                      <span class="form-check-label">Telegram</span>
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Role (if audience=role)</label>
                  <select name="role" class="form-select" data-h-select>
                    <option value="">Choose role</option>
                    @foreach(['super-admin','admin','manager','user','test-role'] as $roleName)
                      <option value="{{ $roleName }}">{{ strtoupper($roleName) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">Users (if audience=users)</label>
                  <select name="user_ids[]" class="form-select" multiple data-select2-remote data-endpoint="{{ route('ui.options.leads') }}" data-placeholder="Search users..." data-min-input="1" data-dropdown-parent="#settings-main-tabs"></select>
                </div>
              </div>

              <div class="h-note mt-3">Browser alerts trigger sound + vibration where supported when new unread notifications arrive.</div>

              <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" data-busy-text="Sending...">
                  <i class="fa-solid fa-paper-plane me-2"></i>
                  Send Broadcast
                </button>
              </div>
            </form>
          @else
            <div class="h-note">You need <code>manage notifications</code> permission to send broadcast messages.</div>
          @endcan
        </div>
      </div>
    </div>

    @if($canManageSettings)
      <div class="h-tab-panel" data-tab-panel="settings-system">
        @if(!$envWritable)
          <div class="alert alert-danger mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <strong>.env is not writable.</strong> Update file permissions first.
          </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" data-spa>
          @csrf
          @foreach($sections as $sectionKey => $section)
            <div class="h-card-soft mb-3">
              <div class="head">
                <div style="font-family:var(--fd);font-size:16px;font-weight:700;">{{ $section['title'] }}</div>
                <div class="h-muted" style="font-size:13px;">{{ $section['description'] }}</div>
              </div>
              <div class="body">
                <div class="row g-3">
                  @foreach($fields as $key => $field)
                    @continue($field['section'] !== $sectionKey)
                    @php
                      $current = old($key, $values[$key] ?? ($field['default'] ?? ''));
                      $type = $field['type'] ?? 'text';
                    @endphp
                    <div class="col-md-6">
                      <label for="{{ $key }}" class="form-label h-label" style="display:block;">{{ $field['label'] }}</label>
                      @if($type === 'select')
                        <select id="{{ $key }}" name="{{ $key }}" class="form-select" data-h-select {{ ($field['required'] ?? false) ? 'required' : '' }}>
                          @foreach($field['options'] ?? [] as $option)
                            <option value="{{ $option }}" @selected((string) $current === (string) $option)>{{ strtoupper($option) }}</option>
                          @endforeach
                        </select>
                      @elseif($type === 'bool')
                        <select id="{{ $key }}" name="{{ $key }}" class="form-select" data-h-select {{ ($field['required'] ?? false) ? 'required' : '' }}>
                          <option value="true" @selected(in_array(strtolower((string) $current), ['1','true','on','yes'], true))>Enabled</option>
                          <option value="false" @selected(in_array(strtolower((string) $current), ['0','false','off','no'], true))>Disabled</option>
                        </select>
                      @else
                        <input id="{{ $key }}" name="{{ $key }}" type="{{ in_array($type, ['email','password','url','number','decimal'], true) ? ($type === 'decimal' ? 'number' : $type) : 'text' }}" value="{{ $current }}" class="form-control" {{ ($field['required'] ?? false) ? 'required' : '' }} autocomplete="off" @if($type === 'decimal') step="0.01" min="0" max="100" @endif>
                      @endif
                      @error($key)
                        <div class="h-error-msg mt-1">{{ $message }}</div>
                      @enderror
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endforeach

          <div class="d-flex justify-content-end gap-2 mt-3 mb-4">
            <button type="submit" class="btn btn-primary" data-busy-text="Saving..." @disabled(!$envWritable)>
              <i class="fa-solid fa-floppy-disk me-2"></i>
              Save Environment Config
            </button>
          </div>
        </form>
      </div>

      <div class="h-tab-panel" data-tab-panel="settings-diagnostics">
        @if(!$opsUiEnabled)
          <div class="h-note mb-3">Diagnostics shell controls are disabled. Enable <code>HAARRAY_ALLOW_SHELL_UI=true</code> if you need maintenance actions.</div>
        @endif

        <div class="h-card-soft mb-3">
          <div class="head"><div style="font-family:var(--fd);font-size:16px;font-weight:700;">Overview</div><div class="h-muted" style="font-size:13px;">Environment health and runtime details.</div></div>
          <div class="body">
            <div class="row g-3">
              <div class="col-md-3"><div class="h-note"><div class="h-ops-metric-label">App Env</div><div class="h-ops-metric-value">{{ $opsSnapshot['app_env'] ?? app()->environment() }}</div></div></div>
              <div class="col-md-3"><div class="h-note"><div class="h-ops-metric-label">Debug</div><div class="h-ops-metric-value">{{ strtoupper((string) ($opsSnapshot['app_debug'] ?? (config('app.debug') ? 'true' : 'false'))) }}</div></div></div>
              <div class="col-md-3"><div class="h-note"><div class="h-ops-metric-label">PHP</div><div class="h-ops-metric-value">{{ $opsSnapshot['php_version'] ?? PHP_VERSION }}</div></div></div>
              <div class="col-md-3"><div class="h-note"><div class="h-ops-metric-label">DB</div><div class="h-ops-metric-value">{{ $dbConnectionInfo['database'] ?: 'N/A' }}</div></div></div>
            </div>
          </div>
        </div>

        <div class="h-card-soft mb-3">
          <div class="head"><div style="font-family:var(--fd);font-size:16px;font-weight:700;">Database Browser</div><div class="h-muted" style="font-size:13px;">Read-only table preview from active connection.</div></div>
          <div class="body">
            @if(!empty($dbBrowser['error']))
              <div class="alert alert-danger">{{ $dbBrowser['error'] }}</div>
            @endif

            <form method="GET" action="{{ route('settings.index') }}" data-spa class="mb-3">
              <input type="hidden" name="tab" value="settings-diagnostics">
              <div class="row g-2 align-items-end">
                <div class="col-md-8">
                  <label class="h-label" style="display:block;">Table</label>
                  <select name="db_table" class="form-select" data-h-select>
                    @foreach($dbBrowser['tables'] ?? [] as $table)
                      <option value="{{ $table }}" @selected(($dbBrowser['selected'] ?? '') === $table)>{{ $table }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4"><button type="submit" class="btn btn-outline-secondary w-100">Load Table</button></div>
              </div>
            </form>

            @if(!empty($dbBrowser['rows']))
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead><tr>@foreach($dbBrowser['columns'] ?? [] as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                  <tbody>
                    @foreach($dbBrowser['rows'] as $row)
                      <tr>
                        @foreach($dbBrowser['columns'] ?? [] as $column)
                          @php $cell = is_scalar($row[$column] ?? null) ? (string) $row[$column] : json_encode($row[$column] ?? null); @endphp
                          <td class="h-muted" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $cell }}">{{ $cell }}</td>
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="h-note">No preview rows available for selected table.</div>
            @endif
          </div>
        </div>

        <div class="h-card-soft mb-3">
          <div class="head"><div style="font-family:var(--fd);font-size:16px;font-weight:700;">ML Diagnostic Lab</div><div class="h-muted" style="font-size:13px;">Run wrapper probe to see current classification output.</div></div>
          <div class="body">
            <div class="row g-3 mb-3">
              @foreach(($mlDiagnostics['checks'] ?? []) as $check)
                <div class="col-md-4">
                  <div class="h-note h-100">
                    <div class="h-ops-metric-label">{{ $check['title'] }}</div>
                    <div class="h-ops-metric-value {{ !empty($check['status']) ? 'text-success' : 'text-danger' }}">{{ !empty($check['status']) ? 'OK' : 'Issue' }}</div>
                    <div class="h-muted" style="font-size:12px;">{{ $check['note'] }}</div>
                  </div>
                </div>
              @endforeach
            </div>

            <form method="POST" action="{{ route('settings.ml.probe') }}" data-spa>
              @csrf
              <div class="row g-3">
                <div class="col-md-4"><label class="h-label" style="display:block;">Food Ratio</label><input type="number" step="0.01" min="0" max="1" name="food_ratio" class="form-control" value="{{ old('food_ratio', $mlDefaults['food_ratio'] ?? 0.35) }}" required></div>
                <div class="col-md-4"><label class="h-label" style="display:block;">Entertainment Ratio</label><input type="number" step="0.01" min="0" max="1" name="entertainment_ratio" class="form-control" value="{{ old('entertainment_ratio', $mlDefaults['entertainment_ratio'] ?? 0.20) }}" required></div>
                <div class="col-md-4"><label class="h-label" style="display:block;">Savings Rate</label><input type="number" step="0.01" min="0" max="1" name="savings_rate" class="form-control" value="{{ old('savings_rate', $mlDefaults['savings_rate'] ?? 0.30) }}" required></div>
              </div>
              <div class="d-flex justify-content-end mt-3"><button type="submit" class="btn btn-outline-secondary" data-busy-text="Running...">Run ML Probe</button></div>
            </form>

            @if(!empty($mlProbeResult))
              <div class="h-note mt-3"><div class="h-ops-metric-label">Probe Output</div><pre style="margin:8px 0 0;white-space:pre-wrap;color:var(--t1);font-family:var(--fm);font-size:11px;">{{ json_encode($mlProbeResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            @endif
          </div>
        </div>

        <div class="h-card-soft mb-3">
          <div class="head"><div style="font-family:var(--fd);font-size:16px;font-weight:700;">Recent Logs</div></div>
          <div class="body"><pre style="margin:0;max-height:300px;overflow:auto;white-space:pre-wrap;color:var(--t1);font-family:var(--fm);font-size:11px;">{{ $opsSnapshot['log_tail'] ?? 'No log data available.' }}</pre></div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  const tabs = document.getElementById('settings-main-tabs');
  if (!tabs) return;

  const updateQuery = (tabId) => {
    const url = new URL(window.location.href);
    if (tabId) url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url.toString());
  };

  document.addEventListener('h:tabs:changed', function (event) {
    if (!event.detail || event.detail.container !== tabs) return;
    updateQuery(event.detail.tabId);
  });

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-media-pick]');
    if (!trigger) return;

    const targetId = trigger.getAttribute('data-media-target');
    const mediaUrl = trigger.getAttribute('data-media-url') || '';
    const target = document.getElementById(targetId);
    if (!target) return;

    target.value = mediaUrl;
    target.dispatchEvent(new Event('input', { bubbles: true }));
    if (window.HToast) HToast.success('Selected asset applied. Save settings to persist.');
  });

  const bindFilePreviews = () => {
    document.querySelectorAll('input[type="file"][data-file-preview]').forEach((input) => {
      if (input.dataset.previewReady === '1') return;
      input.dataset.previewReady = '1';

      input.addEventListener('change', () => {
        const wrap = input.closest('.h-file-dropzone');
        if (!wrap) return;
        const preview = wrap.querySelector('.h-file-preview');
        if (!preview) return;

        const file = input.files && input.files[0] ? input.files[0] : null;
        if (!file || !file.type.startsWith('image/')) {
          preview.setAttribute('src', '');
          wrap.classList.remove('has-preview');
          return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
          preview.setAttribute('src', String((event.target && event.target.result) || ''));
          wrap.classList.add('has-preview');
        };
        reader.readAsDataURL(file);
      });
    });
  };

  bindFilePreviews();
})();
</script>
@endsection
