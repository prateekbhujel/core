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
      <div class="doc-sub">Professional route/link/action access matrix with role CRUD and delegation.</div>
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
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Role Directory DataTable</div>
          <div class="h-muted" style="font-size:13px;">Server-side styled table for roles and quick edit navigation.</div>
        </div>
      </div>
      <div class="body">
        <div class="table-responsive">
          <table
            class="table table-sm align-middle"
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
                <th data-col="actions">Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="h-grid-main h-rbac-grid mb-3" id="role-editor">
      <div class="h-card-soft mb-3">
        <div class="head">
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Create Role</div>
        </div>
        <div class="body">
          <form method="POST" action="{{ route('settings.roles.store') }}" data-spa>
            @csrf
            <div class="mb-2">
              <label class="h-label" style="display:block;">Role Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. auditor" required>
            </div>

            <div class="mb-2">
              <label class="h-label" style="display:block;">Permissions</label>
              <select name="permissions[]" class="form-select" data-h-select multiple>
                @foreach($permissionOptions as $permissionName)
                  <option value="{{ $permissionName }}">{{ $permissionName }}</option>
                @endforeach
              </select>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary" data-busy-text="Creating...">
                <i class="fa-solid fa-plus me-2"></i>
                Create Role
              </button>
            </div>
          </form>
        </div>
      </div>

      <div>
        @if($editRole)
          <div class="h-card-soft mb-3" id="role-editor-card">
            <div class="head">
              <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Edit Role: {{ strtoupper($editRole->name) }}</div>
            </div>
            <div class="body">
              <form method="POST" action="{{ route('settings.roles.update', $editRole) }}" data-spa>
                @csrf
                @method('PUT')

                <div class="mb-2">
                  <label class="h-label" style="display:block;">Role Name</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name', $editRole->name) }}" required>
                </div>

                @php $selectedPermissions = $editRole->permissions->pluck('name')->values()->all(); @endphp
                <div class="mb-2">
                  <label class="h-label" style="display:block;">Permissions</label>
                  <select name="permissions[]" class="form-select" data-h-select multiple>
                    @foreach($permissionOptions as $permissionName)
                      <option value="{{ $permissionName }}" @selected(in_array($permissionName, $selectedPermissions, true))>{{ $permissionName }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="d-flex justify-content-end mt-3">
                  <button type="submit" class="btn btn-primary" data-busy-text="Updating...">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    Update Role
                  </button>
                </div>
              </form>
            </div>
          </div>
        @else
          <div class="h-note mb-3">Select a role from table and click <strong>Edit</strong> to load editor here.</div>
        @endif

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
                          <button type="submit" class="btn btn-outline-danger btn-sm" @disabled($isProtected || ((int) $roleRow['users_count']) > 0)>
                            Delete
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
      </div>
    </div>

    <div class="h-card-soft mb-3">
      <div class="head">
        <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Access Matrix</div>
        <div class="h-muted" style="font-size:13px;">Each role can be set to <strong>None / View / Manage</strong> per module using radio controls.</div>
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
                          <label class="h-radio-pill">
                            <input type="radio" name="{{ $groupName }}" value="none" @checked($currentLevel === 'none')>
                            <span>Off</span>
                          </label>
                          <label class="h-radio-pill">
                            <input type="radio" name="{{ $groupName }}" value="view" @checked($currentLevel === 'view')>
                            <span>View</span>
                          </label>
                          <label class="h-radio-pill">
                            <input type="radio" name="{{ $groupName }}" value="manage" @checked($currentLevel === 'manage')>
                            <span>Manage</span>
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
            $extraPermissionOptions = collect($permissionOptions)
              ->reject(fn ($permission) => in_array($permission, $modulePermissionNames, true))
              ->values();
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
  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-role-edit-id]');
    if (!trigger) return;

    event.preventDefault();
    const roleId = Number(trigger.getAttribute('data-role-edit-id') || 0);
    if (!roleId) return;

    const url = new URL('{{ route('settings.rbac') }}', window.location.origin);
    url.searchParams.set('role', String(roleId));
    url.hash = 'role-editor-card';

    if (window.HSPA) {
      window.HSPA.navigate(url.pathname + url.search + url.hash, true);
      return;
    }

    window.location.href = url.toString();
  });

  const params = new URLSearchParams(window.location.search);
  if (!params.get('role')) return;

  const target = document.getElementById('role-editor-card') || document.getElementById('role-editor');
  if (!target) return;

  setTimeout(() => {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 120);
})();
</script>
@endsection
