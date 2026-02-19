@extends('layouts.haarray')

@section('title', 'Settings')
@section('page_title', 'Settings')

@section('topbar_extra')
  <span class="h-live-badge">
    <i class="fa-solid fa-gear"></i>
    Control Center
  </span>
@endsection

@section('content')
<div class="hl-docs hl-settings">
  <div class="doc-head">
    <div>
      <div class="doc-title">Settings Control Center</div>
      <div class="doc-sub">
        Manage security, notification channels, Telegram setup, ML thresholds, environment configuration,
        and role-based access from one place.
      </div>
    </div>
    @if($isAdmin)
      <span class="h-pill gold">Admin Mode</span>
    @else
      <span class="h-pill teal">User Mode</span>
    @endif
  </div>

  <div class="h-tab-shell" data-ui-tabs data-default-tab="settings-profile">
    <div class="h-tab-nav" role="tablist" aria-label="Settings sections">
      <button type="button" class="h-tab-btn" data-tab-btn="settings-profile">
        <i class="fa-solid fa-user-shield"></i>
        Profile & Security
      </button>

      @if($isAdmin)
        <button type="button" class="h-tab-btn" data-tab-btn="settings-system">
          <i class="fa-solid fa-sliders"></i>
          System Config
        </button>
        <button type="button" class="h-tab-btn" data-tab-btn="settings-access">
          <i class="fa-solid fa-user-lock"></i>
          Roles & Access
        </button>
        <button type="button" class="h-tab-btn" data-tab-btn="settings-broadcast">
          <i class="fa-solid fa-bullhorn"></i>
          Broadcasts
        </button>
      @endif
    </div>

    <div class="h-tab-panel" data-tab-panel="settings-profile">
      <div class="h-card-soft mb-3">
        <div class="head">
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Personal Security</div>
          <div class="h-muted" style="font-size:13px;">Your own 2FA and delivery preferences.</div>
        </div>

        <div class="body">
          <form method="POST" action="{{ route('settings.security') }}" data-spa>
            @csrf

            <div class="row g-3">
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Telegram Chat ID</label>
                <input
                  type="text"
                  name="telegram_chat_id"
                  class="form-control"
                  value="{{ old('telegram_chat_id', auth()->user()->telegram_chat_id) }}"
                  placeholder="e.g. 123456789"
                >
              </div>

              <div class="col-md-6 d-flex align-items-center gap-3" style="padding-top:24px;flex-wrap:wrap;">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="two_factor_enabled" value="1" id="two_factor_enabled" @checked(auth()->user()->two_factor_enabled)>
                  <label class="form-check-label" for="two_factor_enabled">Enable 2FA login</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="receive_in_app_notifications" value="1" id="receive_in_app_notifications" @checked(auth()->user()->receive_in_app_notifications)>
                  <label class="form-check-label" for="receive_in_app_notifications">In-app notifications</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="receive_telegram_notifications" value="1" id="receive_telegram_notifications" @checked(auth()->user()->receive_telegram_notifications)>
                  <label class="form-check-label" for="receive_telegram_notifications">Telegram notifications</label>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary" data-busy-text="Saving...">
                <i class="fa-solid fa-shield-halved me-2"></i>
                Save Personal Preferences
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    @if($isAdmin)
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
                <div class="h-split">
                  <div>
                    <div style="font-family:var(--fd);font-size:16px;font-weight:700;">{{ $section['title'] }}</div>
                    <div class="h-muted" style="font-size:13px;">{{ $section['description'] }}</div>
                  </div>
                </div>
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
                        <select id="{{ $key }}" name="{{ $key }}" class="form-select" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                          @foreach($field['options'] ?? [] as $option)
                            <option value="{{ $option }}" @selected((string) $current === (string) $option)>{{ strtoupper($option) }}</option>
                          @endforeach
                        </select>
                      @elseif($type === 'bool')
                        <select id="{{ $key }}" name="{{ $key }}" class="form-select" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                          <option value="true" @selected(in_array(strtolower((string) $current), ['1','true','on','yes'], true))>Enabled</option>
                          <option value="false" @selected(in_array(strtolower((string) $current), ['0','false','off','no'], true))>Disabled</option>
                        </select>
                      @else
                        <input
                          id="{{ $key }}"
                          name="{{ $key }}"
                          type="{{ in_array($type, ['email','password','url','number','decimal'], true) ? ($type === 'decimal' ? 'number' : $type) : 'text' }}"
                          value="{{ $current }}"
                          class="form-control"
                          {{ ($field['required'] ?? false) ? 'required' : '' }}
                          autocomplete="off"
                          @if($type === 'decimal') step="0.01" min="0" max="100" @endif
                        >
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

      <div class="h-tab-panel" data-tab-panel="settings-access">
        <div class="h-card-soft mb-3">
          <div class="head">
            <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Roles, Permissions & Delivery Rules</div>
            <div class="h-muted" style="font-size:13px;">Define who can do what and who receives notifications.</div>
          </div>

          <div class="body">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Permissions CSV</th>
                    <th>Channels</th>
                    <th>Telegram Chat ID</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($users as $managedUser)
                    <tr>
                      <td>
                        <form id="access-form-{{ $managedUser->id }}" method="POST" action="{{ route('settings.users.access', $managedUser) }}" data-spa>
                          @csrf
                        </form>
                        <div style="font-weight:600;">{{ $managedUser->name }}</div>
                        <div class="h-muted" style="font-size:11px;">{{ $managedUser->email }}</div>
                      </td>
                      <td>
                        <select name="role" class="form-select form-select-sm" form="access-form-{{ $managedUser->id }}">
                          @foreach(['admin','manager','user'] as $role)
                            <option value="{{ $role }}" @selected($managedUser->role === $role)>{{ strtoupper($role) }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <input
                          type="text"
                          name="permissions"
                          class="form-control form-control-sm"
                          value="{{ implode(',', $managedUser->permissions ?? []) }}"
                          placeholder="manage_leads,approve_expense"
                          form="access-form-{{ $managedUser->id }}"
                        >
                      </td>
                      <td>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="receive_in_app_notifications" value="1" id="in_app_{{ $managedUser->id }}" @checked($managedUser->receive_in_app_notifications) form="access-form-{{ $managedUser->id }}">
                          <label class="form-check-label" for="in_app_{{ $managedUser->id }}">In-app</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="receive_telegram_notifications" value="1" id="tg_{{ $managedUser->id }}" @checked($managedUser->receive_telegram_notifications) form="access-form-{{ $managedUser->id }}">
                          <label class="form-check-label" for="tg_{{ $managedUser->id }}">Telegram</label>
                        </div>
                      </td>
                      <td>
                        <input type="text" name="telegram_chat_id" class="form-control form-control-sm" value="{{ $managedUser->telegram_chat_id }}" placeholder="chat_id" form="access-form-{{ $managedUser->id }}">
                      </td>
                      <td>
                        <button type="submit" class="btn btn-sm btn-outline-secondary" form="access-form-{{ $managedUser->id }}">Update</button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="h-tab-panel" data-tab-panel="settings-broadcast">
        <div class="h-card-soft mb-3">
          <div class="head">
            <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Broadcast Notifications</div>
            <div class="h-muted" style="font-size:13px;">Send in-app + Telegram notifications to selected audience.</div>
          </div>

          <div class="body">
            <form method="POST" action="{{ route('notifications.broadcast') }}" data-spa>
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Title</label>
                  <input type="text" name="title" class="form-control" placeholder="System maintenance" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Level</label>
                  <select name="level" class="form-select" required>
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
                  <select name="audience" class="form-select" id="audience-select" required>
                    <option value="all">All users</option>
                    <option value="admins">Admins only</option>
                    <option value="role">By role</option>
                    <option value="users">Specific users</option>
                  </select>
                </div>
                <div class="col-md-4" id="role-filter-wrap">
                  <label class="h-label" style="display:block;">Role Filter (if audience=role)</label>
                  <select name="role" class="form-select">
                    <option value="user">User</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="h-label" style="display:block;">URL (optional)</label>
                  <input type="url" name="url" class="form-control" placeholder="https://...">
                </div>
                <div class="col-md-12" id="user-filter-wrap">
                  <label class="h-label" style="display:block;">Specific Users (if audience=users)</label>
                  <select
                    name="user_ids[]"
                    class="form-select"
                    multiple
                    data-select2-remote
                    data-endpoint="{{ route('ui.options.leads') }}"
                    data-placeholder="Search users..."
                    data-min-input="1"
                  ></select>
                </div>
                <div class="col-md-12 d-flex gap-3 flex-wrap">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="channels[]" value="in_app" id="ch_in_app" checked>
                    <label class="form-check-label" for="ch_in_app">In-app</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="channels[]" value="telegram" id="ch_telegram">
                    <label class="form-check-label" for="ch_telegram">Telegram</label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" data-busy-text="Broadcasting...">
                  <i class="fa-solid fa-paper-plane me-2"></i>
                  Send Broadcast
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    const audience = document.getElementById('audience-select');
    const roleWrap = document.getElementById('role-filter-wrap');
    const userWrap = document.getElementById('user-filter-wrap');

    function syncAudienceFields() {
      if (!audience || !roleWrap || !userWrap) return;

      const roleSelect = roleWrap.querySelector('select[name="role"]');
      const userSelect = userWrap.querySelector('select[name="user_ids[]"]');
      const mode = audience.value;
      const isRole = mode === 'role';
      const isUsers = mode === 'users';

      roleWrap.style.display = isRole ? '' : 'none';
      userWrap.style.display = isUsers ? '' : 'none';

      if (roleSelect) roleSelect.required = isRole;
      if (userSelect) userSelect.required = isUsers;
    }

    function ensureRemoteSelect(panel) {
      if (!panel || !window.HSelectRemote || !window.jQuery) return;

      panel.querySelectorAll('select[data-select2-remote]').forEach((el) => {
        window.HSelectRemote.setup(window.jQuery(el));
      });
    }

    if (audience) {
      audience.addEventListener('change', syncAudienceFields);
      syncAudienceFields();
    }

    document.addEventListener('h:tabs:changed', function (event) {
      const detail = event.detail || {};
      if (detail.tabId === 'settings-broadcast') {
        syncAudienceFields();
        ensureRemoteSelect(detail.panel || null);
      }
    });
  })();
</script>
@endsection
