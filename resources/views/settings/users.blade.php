@extends('layouts.app')

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
      <div class="doc-sub">Server-side DataTable list with one reusable create/edit modal, module access radios, import/export and confirm delete.</div>
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
        <div class="h-muted" style="font-size:13px;">Yajra server-side table with Edit and Delete actions.</div>
      </div>
      @if($canManageUsers)
        <div class="d-flex gap-2 flex-wrap justify-content-end">
          <button type="button" class="btn btn-primary btn-sm" id="h-user-create-open">
            <i class="fa-solid fa-user-plus me-2"></i>
            Create User
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-modal-open="settings-users-import-modal">
            <i class="fa-solid fa-file-import me-2"></i>
            Import
          </button>
          <a href="{{ route('settings.users.export') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-file-export me-2"></i>
            Export
          </a>
        </div>
      @endif
    </div>

    <div class="body">
      <div class="table-responsive">
        <table
          class="table table-sm table-striped table-hover align-middle"
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
</div>
@endsection

@section('modals')
  @if($canManageUsers)
    <div class="h-modal-overlay" id="settings-users-form-modal">
      <div class="h-modal" style="max-width:900px;">
        <div class="h-modal-head">
          <div class="h-modal-title" id="h-user-form-title">Create User</div>
          <button class="h-modal-close">×</button>
        </div>
        <div class="h-modal-body">
          <form method="POST" action="{{ route('settings.users.store') }}" id="h-user-form" data-spa data-store-action="{{ route('settings.users.store') }}" data-update-template="{{ route('settings.users.update', ['user' => '__ID__']) }}">
            @csrf
            <span id="h-user-method-holder"></span>

            <div class="row g-2">
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Name</label>
                <input type="text" name="name" id="h-user-name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Email</label>
                <input type="email" name="email" id="h-user-email" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Password</label>
                <input type="password" name="password" id="h-user-password" class="form-control" minlength="8" required>
              </div>
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Role</label>
                <select name="role" id="h-user-role" class="form-select" data-h-select required>
                  @foreach($roles as $roleName)
                    <option value="{{ $roleName }}">{{ strtoupper($roleName) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Telegram Chat ID</label>
                <input type="text" name="telegram_chat_id" id="h-user-tg" class="form-control" placeholder="optional">
              </div>
              <div class="col-md-6">
                <label class="h-label" style="display:block;">Direct Permissions</label>
                <select name="permissions[]" id="h-user-permissions" class="form-select" data-h-select multiple>
                  @foreach($permissionOptions as $permissionName)
                    <option value="{{ $permissionName }}">{{ $permissionName }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="h-card-soft mt-3">
              <div class="head">
                <div style="font-family:var(--fd);font-size:15px;font-weight:700;">Notification Channels (On / Off)</div>
                <div class="h-muted" style="font-size:12px;">Toggle default user channels with radios.</div>
              </div>
              <div class="body">
                <div class="h-user-channel-grid">
                  <div>
                    <label class="h-label" style="display:block;margin-bottom:6px;">In-app Notifications</label>
                    <div class="h-radio-inline">
                      <label class="h-radio-pill"><input type="radio" name="receive_in_app_notifications" value="1" checked><span>On</span></label>
                      <label class="h-radio-pill"><input type="radio" name="receive_in_app_notifications" value="0"><span>Off</span></label>
                    </div>
                  </div>
                  <div>
                    <label class="h-label" style="display:block;margin-bottom:6px;">Telegram Notifications</label>
                    <div class="h-radio-inline">
                      <label class="h-radio-pill"><input type="radio" name="receive_telegram_notifications" value="1"><span>On</span></label>
                      <label class="h-radio-pill"><input type="radio" name="receive_telegram_notifications" value="0" checked><span>Off</span></label>
                    </div>
                  </div>
                  <div>
                    <label class="h-label" style="display:block;margin-bottom:6px;">Browser Notifications</label>
                    <div class="h-radio-inline">
                      <label class="h-radio-pill"><input type="radio" name="browser_notifications_enabled" value="1"><span>On</span></label>
                      <label class="h-radio-pill"><input type="radio" name="browser_notifications_enabled" value="0" checked><span>Off</span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="h-card-soft mt-3">
              <div class="head">
                <div style="font-family:var(--fd);font-size:15px;font-weight:700;">User-Level Module Access</div>
                <div class="h-muted" style="font-size:12px;">Override module route access for this specific user.</div>
              </div>
              <div class="body">
                <div class="h-user-access-grid">
                  @foreach($accessModules as $moduleKey => $module)
                    <div class="h-user-access-item" data-user-module-key="{{ $moduleKey }}">
                      <div class="h-user-access-copy">
                        <div style="font-weight:700;">{{ $module['label'] }}</div>
                        <div class="h-muted" style="font-size:11px;"><code>{{ $module['view_permission'] }}</code> / <code>{{ $module['manage_permission'] }}</code></div>
                      </div>
                      <div class="h-radio-inline">
                        <label class="h-radio-pill"><input type="radio" name="module_access[{{ $moduleKey }}]" value="none" checked><span>None</span></label>
                        <label class="h-radio-pill"><input type="radio" name="module_access[{{ $moduleKey }}]" value="view"><span>View</span></label>
                        <label class="h-radio-pill"><input type="radio" name="module_access[{{ $moduleKey }}]" value="manage"><span>Manage</span></label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary" id="h-user-submit-btn" data-busy-text="Saving...">
                <i class="fa-solid fa-floppy-disk me-2"></i>
                Save User
              </button>
            </div>
          </form>

          <form method="POST" action="#" id="h-user-delete-form" data-spa data-confirm="true" data-confirm-title="Delete user?" data-confirm-text="This user will be removed permanently." style="display:none;margin-top:10px;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" id="h-user-delete-btn">
              <i class="fa-solid fa-trash me-2"></i>
              Delete User
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="h-modal-overlay" id="settings-users-import-modal">
      <div class="h-modal" style="max-width:540px;">
        <div class="h-modal-head">
          <div class="h-modal-title">Import Users</div>
          <button class="h-modal-close">×</button>
        </div>
        <div class="h-modal-body">
          <form method="POST" action="{{ route('settings.users.import') }}" enctype="multipart/form-data" data-spa>
            @csrf
            <div class="mb-3">
              <label class="h-label" style="display:block;">Import File (xlsx/xls/csv)</label>
              <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary" data-modal-close="settings-users-import-modal">Cancel</button>
              <button type="submit" class="btn btn-primary" data-busy-text="Importing...">
                <i class="fa-solid fa-file-import me-2"></i>
                Import Users
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif
@endsection

@section('scripts')
<script>
(function () {
  const canManageUsers = @json($canManageUsers);
  if (!canManageUsers) return;

  const users = @json($userPayload);
  const currentUserId = Number(@json((int) auth()->id()));

  const userForm = document.getElementById('h-user-form');
  const userFormTitle = document.getElementById('h-user-form-title');
  const userMethodHolder = document.getElementById('h-user-method-holder');
  const userSubmitBtn = document.getElementById('h-user-submit-btn');
  const userDeleteForm = document.getElementById('h-user-delete-form');

  const userName = document.getElementById('h-user-name');
  const userEmail = document.getElementById('h-user-email');
  const userPassword = document.getElementById('h-user-password');
  const userRole = document.getElementById('h-user-role');
  const userTg = document.getElementById('h-user-tg');
  const userPermissions = document.getElementById('h-user-permissions');

  if (!userForm || !userName || !userEmail || !userPassword || !userRole || !userTg || !userPermissions) {
    return;
  }

  const setRadio = (name, enabled) => {
    document.querySelectorAll('input[type="radio"][name="' + name + '"]').forEach((radio) => {
      radio.checked = String(radio.value) === (enabled ? '1' : '0');
    });
  };

  const setModuleAccess = (payload = {}) => {
    document.querySelectorAll('[data-user-module-key]').forEach((row) => {
      const key = row.getAttribute('data-user-module-key') || '';
      const value = String(payload[key] || 'none');
      row.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.checked = String(radio.value) === value;
      });
    });
  };

  const setPermissions = (selected = []) => {
    const selectedSet = new Set((selected || []).map(String));
    Array.from(userPermissions.options).forEach((option) => {
      option.selected = selectedSet.has(String(option.value));
    });
    userPermissions.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const openModal = () => {
    if (window.HModal) {
      window.HModal.open('settings-users-form-modal');
      return;
    }
    const modal = document.getElementById('settings-users-form-modal');
    if (modal) modal.classList.add('show');
  };

  const openCreate = () => {
    userForm.setAttribute('action', userForm.dataset.storeAction);
    userMethodHolder.innerHTML = '';
    userFormTitle.textContent = 'Create User';
    userSubmitBtn.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Create User';

    userName.value = '';
    userEmail.value = '';
    userPassword.value = '';
    userPassword.required = true;
    userPassword.placeholder = '';
    userRole.selectedIndex = 0;
    userRole.dispatchEvent(new Event('change', { bubbles: true }));
    userTg.value = '';

    setPermissions([]);
    setRadio('receive_in_app_notifications', true);
    setRadio('receive_telegram_notifications', false);
    setRadio('browser_notifications_enabled', false);
    setModuleAccess({});

    if (userDeleteForm) {
      userDeleteForm.style.display = 'none';
      userDeleteForm.setAttribute('action', '#');
    }

    openModal();
  };

  const openEdit = (userId) => {
    const user = users[String(userId)] || users[userId];
    if (!user) return;

    const action = String(userForm.dataset.updateTemplate || '').replace('__ID__', String(user.id));
    userForm.setAttribute('action', action);
    userMethodHolder.innerHTML = '<input type="hidden" name="_method" value="PUT">';
    userFormTitle.textContent = 'Edit User: ' + String(user.name || 'User');
    userSubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Update User';

    userName.value = String(user.name || '');
    userEmail.value = String(user.email || '');
    userPassword.value = '';
    userPassword.required = false;
    userPassword.placeholder = 'Leave blank to keep current password';
    if (user.role) userRole.value = String(user.role);
    userRole.dispatchEvent(new Event('change', { bubbles: true }));
    userTg.value = String(user.telegram_chat_id || '');

    setPermissions(Array.isArray(user.permissions) ? user.permissions : []);
    setRadio('receive_in_app_notifications', Boolean(user.receive_in_app_notifications));
    setRadio('receive_telegram_notifications', Boolean(user.receive_telegram_notifications));
    setRadio('browser_notifications_enabled', Boolean(user.browser_notifications_enabled));
    setModuleAccess(user.module_access || {});

    if (userDeleteForm) {
      const canDelete = Number(user.id) !== currentUserId;
      if (canDelete) {
        const deleteAction = '{{ route('settings.users.delete', ['user' => '__ID__']) }}'.replace('__ID__', String(user.id));
        userDeleteForm.setAttribute('action', deleteAction);
        userDeleteForm.style.display = 'inline-flex';
      } else {
        userDeleteForm.style.display = 'none';
        userDeleteForm.setAttribute('action', '#');
      }
    }

    openModal();
  };

  const createButton = document.getElementById('h-user-create-open');
  if (createButton) createButton.addEventListener('click', openCreate);

  document.addEventListener('click', (event) => {
    const editBtn = event.target.closest('[data-user-edit-id]');
    if (!editBtn) return;
    event.preventDefault();
    const userId = Number(editBtn.getAttribute('data-user-edit-id') || 0);
    if (!userId) return;
    openEdit(userId);
  });

  const query = new URLSearchParams(window.location.search);
  const editUserId = Number(query.get('edit_user') || 0);
  if (editUserId > 0) {
    setTimeout(() => {
      openEdit(editUserId);
      if (window.history && typeof window.history.replaceState === 'function') {
        const url = new URL(window.location.href);
        url.searchParams.delete('edit_user');
        window.history.replaceState({}, '', url.toString());
      }
    }, 80);
  }
})();
</script>
@endsection
