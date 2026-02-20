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
    <div class="h-card-soft mb-3">
      <div class="head h-split">
        <div>
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Role Directory</div>
          <div class="h-muted" style="font-size:13px;">Server-side table for role lookup and edit actions.</div>
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
      <div class="head">
        <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Delete Roles</div>
        <div class="h-muted" style="font-size:13px;">Protected roles and roles with assigned users cannot be deleted.</div>
      </div>
      <div class="body">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Role</th>
                <th>Users</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roleCatalog as $roleRow)
                @php
                  $roleName = (string) $roleRow['name'];
                  $isProtected = in_array($roleName, $protectedRoleNames, true);
                @endphp
                <tr>
                  <td>{{ strtoupper($roleName) }}</td>
                  <td>{{ (int) $roleRow['users_count'] }}</td>
                  <td>
                    <form method="POST" action="{{ route('settings.roles.delete', $roleRow['id']) }}" data-spa data-confirm="true" data-confirm-title="Delete role?" data-confirm-text="Role will be removed permanently if no user is assigned.">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm h-action-icon" title="Delete role" @disabled($isProtected || ((int) $roleRow['users_count']) > 0)>
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="h-muted">No roles available.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="h-card-soft mb-3">
      <div class="head">
        <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Access Matrix</div>
        <div class="h-muted" style="font-size:13px;">Each role can be set to <strong>None / View / Manage</strong> per module.</div>
      </div>

      <div class="body">
        <form method="POST" action="{{ route('settings.roles.matrix') }}" data-spa>
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
                      <td>
                        <div class="h-radio-inline">
                          <label class="h-radio-pill"><input type="radio" name="{{ $groupName }}" value="none" @checked($currentLevel === 'none')><span>Off</span></label>
                          <label class="h-radio-pill"><input type="radio" name="{{ $groupName }}" value="view" @checked($currentLevel === 'view')><span>View</span></label>
                          <label class="h-radio-pill"><input type="radio" name="{{ $groupName }}" value="manage" @checked($currentLevel === 'manage')><span>Manage</span></label>
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
  if (!params.get('role')) return;
  const target = document.getElementById('role-editor');
  if (!target) return;
  setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
})();
</script>
@endsection
