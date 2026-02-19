@extends('layouts.haarray')

@section('title', 'Users')
@section('page_title', 'Users')

@section('topbar_extra')
  <span class="h-live-badge">
    <i class="fa-solid fa-users"></i>
    User Management
  </span>
@endsection

@section('content')
<div class="hl-docs hl-settings">
  <div class="doc-head">
    <div>
      <div class="doc-title">Users</div>
      <div class="doc-sub">Dedicated user CRUD, role assignment, direct permission mapping, and Excel import/export.</div>
    </div>
    @if($canManageUsers)
      <span class="h-pill gold">Manage Users</span>
    @else
      <span class="h-pill teal">Read Only</span>
    @endif
  </div>

  <div class="h-card-soft mb-3">
    <div class="head h-split">
      <div>
        <div style="font-family:var(--fd);font-size:16px;font-weight:700;">User Directory DataTable</div>
        <div class="h-muted" style="font-size:13px;">Server-side Yajra DataTable for fast filtering in SPA mode.</div>
      </div>
    </div>

    <div class="body">
      <div class="table-responsive">
        <table
          class="table table-sm align-middle"
          data-h-datatable
          data-endpoint="{{ route('ui.datatables.users') }}"
          data-page-length="10"
          data-length-menu="10,20,50,100"
          data-order-col="0"
          data-order-dir="desc"
        >
          <thead>
            <tr>
              <th data-col="id">ID</th>
              <th data-col="name">Name</th>
              <th data-col="email">Email</th>
              <th data-col="role">Role</th>
              <th data-col="channels">Channels</th>
              <th data-col="created_at">Joined</th>
              <th data-col="actions">Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  @if($canManageUsers)
    <div class="h-card-soft mb-3">
      <div class="head h-split">
        <div>
          <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Import / Export</div>
          <div class="h-muted" style="font-size:13px;">Use template export, edit rows, and import back to create/update users.</div>
        </div>
        <a href="{{ route('settings.users.export') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa-solid fa-file-export me-2"></i>
          Export Users
        </a>
      </div>

      <div class="body">
        <form method="POST" action="{{ route('settings.users.import') }}" enctype="multipart/form-data" data-spa>
          @csrf
          <div class="row g-2 align-items-end">
            <div class="col-md-9">
              <label class="h-label" style="display:block;">Import File (xlsx/xls/csv)</label>
              <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary w-100" data-busy-text="Importing...">
                <i class="fa-solid fa-file-import me-2"></i>
                Import Users
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  @endif

  <div class="h-grid-main h-users-grid mb-3">
    <div>
      @if($canManageUsers)
        <div class="h-card-soft mb-3" id="user-editor">
          <div class="head">
            <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Create User</div>
          </div>
          <div class="body">
            <form method="POST" action="{{ route('settings.users.store') }}" data-spa>
              @csrf
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Name</label>
                  <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Email</label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Password</label>
                  <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Role</label>
                  <select name="role" class="form-select" data-h-select required>
                    @foreach($roles as $roleName)
                      <option value="{{ $roleName }}">{{ strtoupper($roleName) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Telegram Chat ID</label>
                  <input type="text" name="telegram_chat_id" class="form-control" placeholder="optional">
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Direct Permissions</label>
                  <select name="permissions[]" class="form-select" data-h-select multiple>
                    @foreach($permissionOptions as $permissionName)
                      <option value="{{ $permissionName }}">{{ $permissionName }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="h-radio-stack mt-2">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="receive_in_app_notifications" value="1" checked>
                  <span class="form-check-label">In-app notifications</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="receive_telegram_notifications" value="1">
                  <span class="form-check-label">Telegram notifications</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="browser_notifications_enabled" value="1">
                  <span class="form-check-label">Browser notifications</span>
                </label>
              </div>

              <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" data-busy-text="Creating...">
                  <i class="fa-solid fa-user-plus me-2"></i>
                  Create User
                </button>
              </div>
            </form>
          </div>
        </div>
      @else
        <div class="h-note">Your account can view user directory but cannot manage users.</div>
      @endif
    </div>

    <div>
      @if($selectedUser && $canManageUsers)
        @php
          $selectedRole = $hasSpatiePermissions
            ? ($selectedUser->roles->first()->name ?? $selectedUser->role ?? 'user')
            : ($selectedUser->role ?? 'user');
          $selectedPermissions = $hasSpatiePermissions
            ? $selectedUser->permissions->pluck('name')->values()->all()
            : [];
        @endphp
        <div class="h-card-soft" id="user-edit-card">
          <div class="head h-split">
            <div>
              <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Edit User</div>
              <div class="h-muted" style="font-size:13px;">{{ $selectedUser->name }} • {{ $selectedUser->email }}</div>
            </div>
            @if(auth()->id() !== $selectedUser->id)
              <form method="POST" action="{{ route('settings.users.delete', $selectedUser) }}" data-spa data-confirm="true" data-confirm-title="Delete {{ $selectedUser->name }}?" data-confirm-text="This account will be removed permanently." data-confirm-ok="Delete" data-confirm-cancel="Cancel">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                  <i class="fa-solid fa-trash me-1"></i>
                  Delete
                </button>
              </form>
            @endif
          </div>

          <div class="body">
            <form method="POST" action="{{ route('settings.users.update', $selectedUser) }}" data-spa>
              @csrf
              @method('PUT')
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Name</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name', $selectedUser->name) }}" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Email</label>
                  <input type="email" name="email" class="form-control" value="{{ old('email', $selectedUser->email) }}" required>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">New Password (optional)</label>
                  <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Role</label>
                  <select name="role" class="form-select" data-h-select required>
                    @foreach($roles as $roleName)
                      <option value="{{ $roleName }}" @selected($selectedRole === $roleName)>{{ strtoupper($roleName) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Telegram Chat ID</label>
                  <input type="text" name="telegram_chat_id" class="form-control" value="{{ old('telegram_chat_id', $selectedUser->telegram_chat_id) }}" placeholder="optional">
                </div>
                <div class="col-md-6">
                  <label class="h-label" style="display:block;">Direct Permissions</label>
                  <select name="permissions[]" class="form-select" data-h-select multiple>
                    @foreach($permissionOptions as $permissionName)
                      <option value="{{ $permissionName }}" @selected(in_array($permissionName, $selectedPermissions, true))>{{ $permissionName }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="h-radio-stack mt-2">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="receive_in_app_notifications" value="1" @checked($selectedUser->receive_in_app_notifications)>
                  <span class="form-check-label">In-app notifications</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="receive_telegram_notifications" value="1" @checked($selectedUser->receive_telegram_notifications)>
                  <span class="form-check-label">Telegram notifications</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="browser_notifications_enabled" value="1" @checked($selectedUser->browser_notifications_enabled)>
                  <span class="form-check-label">Browser notifications</span>
                </label>
              </div>

              <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" data-busy-text="Updating...">
                  <i class="fa-solid fa-floppy-disk me-2"></i>
                  Update User
                </button>
              </div>
            </form>
          </div>
        </div>
      @else
        <div class="h-note">Choose a user from the table above and click <strong>Edit</strong> to open edit form here.</div>
      @endif
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  const params = new URLSearchParams(window.location.search);
  if (!params.get('user')) return;

  const target = document.getElementById('user-edit-card') || document.getElementById('user-editor');
  if (!target) return;

  setTimeout(() => {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 120);
})();
</script>
@endsection
