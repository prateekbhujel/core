@extends('layouts.app')

@section('title', 'Access & RBAC')
@section('page_title', 'Access & RBAC')

@section('topbar_extra')
  <span class="h-live-badge">
    <i class="fa-solid fa-user-lock"></i>
    RBAC Console
  </span>
@endsection

@section('content')
<div class="hl-docs hl-settings">
  <div class="doc-head">
    <div>
      <div class="doc-title">Roles & Permissions</div>
      <div class="doc-sub">Full-page role CRUD + matrix access management for modules and actions.</div>
    </div>
    <span class="h-pill gold">RBAC</span>
  </div>

  @if(!$hasSpatiePermissions)
    <div class="alert alert-warning" role="alert">
      <i class="fa-solid fa-triangle-exclamation me-2"></i>
      Spatie permission tables are not ready. Run migrations first.
    </div>
  @else
    <div class="h-card-soft mb-3" id="role-editor">
      <div class="head h-split">
        <div>
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">
            @if($editRole)
              Edit Role: {{ strtoupper((string) $editRole->name) }}
            @else
              Create Role
            @endif
          </div>
          <div class="h-muted" style="font-size:13px;">
            @if($editRole)
              Update role name and permission grants here. For very large role sets, this full-page editor avoids modal overflow issues.
            @else
              Create a new role and assign permissions from the full list.
            @endif
          </div>
        </div>
        @if($editRole)
          <a href="{{ route('settings.rbac') }}" data-spa class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-xmark me-1"></i>
            Cancel Edit
          </a>
        @endif
      </div>
      <div class="body">
        <form method="POST" action="{{ $editRole ? route('settings.roles.update', $editRole) : route('settings.roles.store') }}" data-spa>
          @csrf
          @if($editRole)
            @method('PUT')
          @endif

          @php
            $selectedPermissions = $editRole ? $editRole->permissions->pluck('name')->values()->all() : [];
          @endphp

          <div class="row g-3">
            <div class="col-md-4">
              <label class="h-label" style="display:block;">Role Name</label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $editRole ? $editRole->name : '') }}" placeholder="e.g. auditor" required>
            </div>
            <div class="col-md-8">
              <label class="h-label" style="display:block;">Permissions</label>
              <select name="permissions[]" class="form-select" data-h-select multiple>
                @foreach($permissionOptions as $permissionName)
                  <option value="{{ $permissionName }}" @selected(in_array($permissionName, old('permissions', $selectedPermissions), true))>{{ $permissionName }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="h-note mt-3">
            <div class="h-muted" style="font-size:12px;margin-bottom:8px;">Route permission map for module-level roles</div>
            <div class="h-route-permission-grid">
              @foreach($accessModules as $module)
                <div class="h-route-permission-item">
                  <div class="h-route-permission-title">{{ $module['label'] }}</div>
                  <div class="h-muted" style="font-size:11px;">
                    <code>{{ $module['view_permission'] }}</code><br>
                    <code>{{ $module['manage_permission'] }}</code>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary" data-busy-text="Saving...">
              <i class="fa-solid fa-floppy-disk me-2"></i>
              {{ $editRole ? 'Update Role' : 'Create Role' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="h-card-soft mb-3">
      <div class="head h-split">
        <div>
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Role Directory</div>
          <div class="h-muted" style="font-size:13px;">Full-page role list with edit/delete actions.</div>
        </div>
        <a href="#role-editor" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-plus me-2"></i>
          Create Role
        </a>
      </div>
      <div class="body">
        <div class="table-responsive">
          <table
            class="table table-sm align-middle h-table-sticky-actions"
            data-h-datatable
            data-endpoint="{{ route('ui.datatables.roles') }}"
            data-page-length="10"
            data-length-menu="10,20,50,100"
            data-order-col="0"
            data-order-dir="desc"
            data-empty-text="Empty"
          >
            <thead>
              <tr>
                <th data-col="id">ID</th>
                <th data-col="name">Role</th>
                <th data-col="permissions_count">Permissions</th>
                <th data-col="users_count">Users</th>
                <th data-col="is_protected">Protected</th>
                <th data-col="actions" class="h-col-actions" data-orderable="false" data-searchable="false">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="h-card-soft mb-3">
      <div class="head h-split">
        <div>
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Access Matrix</div>
          <div class="h-muted" style="font-size:13px;">Toggle <strong>View</strong> and <strong>Manage</strong> for each role/module. Changes auto-save in real time.</div>
        </div>
        <span class="h-muted" id="h-rbac-live-status" style="font-size:12px;">Live sync ready</span>
      </div>

      <div class="body">
        <form method="POST" action="{{ route('settings.roles.matrix') }}" data-spa id="h-rbac-matrix-form">
          @csrf
          <div class="table-responsive h-access-matrix-wrap">
            <table class="table table-sm align-middle h-access-matrix">
              <thead>
                <tr>
                  <th style="min-width:200px;">Module</th>
                  <th style="min-width:260px;">Route / Link / Action Scope</th>
                  @foreach($roleNames as $roleName)
                    <th style="min-width:190px;">{{ strtoupper($roleName) }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($accessModules as $moduleKey => $module)
                  <tr>
                    <td>
                      <div style="font-weight:700;">{{ $module['label'] }}</div>
                      <div class="h-muted" style="font-size:11px;">
                        <code>{{ $module['view_permission'] }}</code><br>
                        <code>{{ $module['manage_permission'] }}</code>
                      </div>
                    </td>
                    <td class="h-muted" style="font-size:12px;">{{ $module['description'] }}</td>
                    @foreach($roleNames as $roleName)
                      @php
                        $currentLevel = $roleAccessMap[$roleName][$moduleKey] ?? ($roleName === 'admin' ? 'manage' : 'none');
                        $groupName = "role_modules[{$roleName}][{$moduleKey}]";
                      @endphp
                      <td data-role-module-cell>
                        <input type="hidden" name="{{ $groupName }}" value="{{ $currentLevel }}" data-role-module-value>
                        <div class="h-access-toggle-stack">
                          <label class="h-switch h-access-switch">
                            <input type="checkbox" data-role-module-view @checked($currentLevel !== 'none')>
                            <span class="track"><span class="thumb"></span></span>
                            <span class="h-switch-text">View</span>
                          </label>
                          <label class="h-switch h-access-switch">
                            <input type="checkbox" data-role-module-manage @checked($currentLevel === 'manage')>
                            <span class="track"><span class="thumb"></span></span>
                            <span class="h-switch-text">Manage</span>
                          </label>
                        </div>
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          @php
            $extraPermissionOptions = collect($permissionOptions)->reject(fn ($permission) => in_array($permission, $modulePermissionNames, true))->values();
          @endphp

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="h-label" style="display:block;">Extra Action Permissions (Optional)</label>
              @if($extraPermissionOptions->isEmpty())
                <div class="h-note" style="margin-top:6px;">No extra permissions available.</div>
              @else
                <div class="h-access-extra-grid">
                  @foreach($roleNames as $roleName)
                    @php $selectedExtra = $roleExtraPermissionMap[$roleName] ?? []; @endphp
                    <div>
                      <label class="h-label" style="display:block;margin-bottom:6px;">{{ strtoupper($roleName) }}</label>
                      <select name="extra_permissions[{{ $roleName }}][]" class="form-select form-select-sm" data-h-select multiple>
                        @foreach($extraPermissionOptions as $permissionName)
                          <option value="{{ $permissionName }}" @selected(in_array($permissionName, $selectedExtra, true))>{{ $permissionName }}</option>
                        @endforeach
                      </select>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

          <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary" data-busy-text="Updating...">
              <i class="fa-solid fa-user-lock me-2"></i>
              Save Access Matrix
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
@endsection

@section('scripts')
<script>
(function () {
  const params = new URLSearchParams(window.location.search);
  const target = document.getElementById('role-editor');
  if (params.get('role') && target) {
    setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
  }

  const form = document.getElementById('h-rbac-matrix-form');
  if (!form) return;

  const status = document.getElementById('h-rbac-live-status');
  let saveTimer = null;
  let isSaving = false;
  let hasPendingSave = false;

  const nowStamp = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

  const setStatus = (message, tone) => {
    if (!status) return;
    status.textContent = message;
    status.classList.remove('text-success', 'text-danger');
    if (tone === 'success') status.classList.add('text-success');
    if (tone === 'error') status.classList.add('text-danger');
  };

  const syncCellState = (cell, source) => {
    const hidden = cell.querySelector('[data-role-module-value]');
    const viewToggle = cell.querySelector('[data-role-module-view]');
    const manageToggle = cell.querySelector('[data-role-module-manage]');
    if (!hidden || !viewToggle || !manageToggle) return;

    if (source === 'manage' && manageToggle.checked) {
      viewToggle.checked = true;
    }
    if (source === 'view' && !viewToggle.checked) {
      manageToggle.checked = false;
    }

    hidden.value = manageToggle.checked ? 'manage' : (viewToggle.checked ? 'view' : 'none');
  };

  const pushLiveSave = () => {
    if (isSaving) {
      hasPendingSave = true;
      return;
    }

    if (!window.HApi || typeof window.HApi.post !== 'function') return;

    isSaving = true;
    setStatus('Saving access changes...', 'pending');

    window.HApi.post(form.action, $(form).serialize(), {
      dataType: 'json',
      headers: {
        Accept: 'application/json',
        'X-HSPA': '1',
      },
    }).done((response) => {
      const message = response && response.message ? response.message : 'Role access matrix updated successfully.';
      setStatus(message + ' • ' + nowStamp(), 'success');
    }).fail((xhr) => {
      const message = xhr && xhr.responseJSON && xhr.responseJSON.message
        ? xhr.responseJSON.message
        : 'Live sync failed. Use Save Access Matrix to retry.';
      setStatus(message, 'error');
      if (window.HToast && typeof window.HToast.error === 'function') {
        window.HToast.error(message);
      }
    }).always(() => {
      isSaving = false;
      if (hasPendingSave) {
        hasPendingSave = false;
        pushLiveSave();
      }
    });
  };

  const queueLiveSave = () => {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(pushLiveSave, 260);
  };

  form.querySelectorAll('[data-role-module-cell]').forEach((cell) => {
    const viewToggle = cell.querySelector('[data-role-module-view]');
    const manageToggle = cell.querySelector('[data-role-module-manage]');
    if (!viewToggle || !manageToggle) return;

    syncCellState(cell);

    viewToggle.addEventListener('change', () => {
      syncCellState(cell, 'view');
      queueLiveSave();
    });
    manageToggle.addEventListener('change', () => {
      syncCellState(cell, 'manage');
      queueLiveSave();
    });
  });

  $(form).on('change', 'select[name^="extra_permissions["]', () => {
    queueLiveSave();
  });
})();
</script>
@endsection
