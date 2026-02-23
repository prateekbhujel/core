@extends('layouts.app')

@section('title', 'Media Library')
@section('page_title', 'Media Library')

@section('topbar_extra')
  <span class="h-live-badge">
    <i class="fa-solid fa-photo-film"></i>
    Media Manager
  </span>
@endsection

@section('content')
<div class="hl-docs hl-settings">
  <div class="doc-head">
    <div>
      <div class="doc-title">Media Library</div>
      <div class="doc-sub">Folder-based media management with CSV export, image resize, and one-click URL injection workflow.</div>
    </div>
    <span class="h-pill teal">{{ $storageLabel }}</span>
  </div>

  <div class="h-card-soft mb-3">
    <div class="head">
      <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Browser Controls</div>
      <div class="h-muted" style="font-size:13px;">Use folder navigation, search, and dedicated actions from one place.</div>
    </div>
    <div class="body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="h-label" style="display:block;">Current Folder</label>
          <input type="text" id="settings-media-folder" class="form-control" value="{{ $initialFolder }}" placeholder="e.g. branding/icons">
        </div>
        <div class="col-md-3">
          <label class="h-label" style="display:block;">Search</label>
          <input type="text" id="settings-media-search" class="form-control" placeholder="Find files...">
        </div>
        <div class="col-md-3">
          <label class="h-label" style="display:block;">Create Folder</label>
          <input type="text" id="settings-media-create-name" class="form-control" placeholder="new-folder">
        </div>
        <div class="col-md-2 d-grid">
          <button type="button" class="btn btn-primary" id="settings-media-create-folder">
            <i class="fa-solid fa-folder-plus me-2"></i>
            Create
          </button>
        </div>
      </div>

      <div class="h-row mt-3" style="gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="settings-media-root">
          <i class="fa-solid fa-house me-2"></i>
          Root
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="settings-media-load">
          <i class="fa-solid fa-rotate me-2"></i>
          Refresh
        </button>
        <a href="{{ route('ui.filemanager.export') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm" id="settings-media-export">
          <i class="fa-solid fa-file-csv me-2"></i>
          Export CSV
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-media-manager-open>
          <i class="fa-solid fa-folder-open me-2"></i>
          Open Global Modal
        </button>
      </div>
    </div>
  </div>

  <div class="h-card-soft">
    <div class="head h-split">
      <div>
        <div style="font-family:var(--fd);font-size:16px;font-weight:700;">Files</div>
        <div class="h-muted" style="font-size:13px;" id="settings-media-current-path">/uploads</div>
      </div>
      <span class="h-muted" id="settings-media-count">0 files</span>
    </div>
    <div class="body">
      <div id="settings-media-folders" class="h-row mb-3" style="gap:8px;flex-wrap:wrap;"></div>

      <div class="table-responsive">
        <table class="table table-sm align-middle h-table-sticky-actions">
          <thead>
            <tr>
              <th style="min-width:78px;">Preview</th>
              <th>Name</th>
              <th>Path</th>
              <th>Type</th>
              <th>Size</th>
              <th>Modified</th>
              <th class="h-col-actions">Actions</th>
            </tr>
          </thead>
          <tbody id="settings-media-table-body">
            <tr>
              <td colspan="7" class="text-center h-muted py-4">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('modals')
<div class="h-modal-overlay" id="settings-media-delete-modal">
  <div class="h-modal" style="max-width:520px;">
    <div class="h-modal-head">
      <div class="h-modal-title">Delete Media</div>
      <button class="h-modal-close">×</button>
    </div>
    <div class="h-modal-body">
      <p id="settings-media-delete-text" class="mb-3">Delete this file permanently?</p>
      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
        <button type="button" class="btn btn-danger" id="settings-media-delete-confirm">
          <i class="fa-solid fa-trash me-2"></i>
          Delete
        </button>
      </div>
    </div>
  </div>
</div>

<div class="h-modal-overlay" id="settings-media-resize-modal">
  <div class="h-modal" style="max-width:520px;">
    <div class="h-modal-head">
      <div class="h-modal-title">Resize Image</div>
      <button class="h-modal-close">×</button>
    </div>
    <div class="h-modal-body">
      <form id="settings-media-resize-form">
        <input type="hidden" name="path" id="settings-media-resize-path" value="">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="h-label" style="display:block;">Max Width</label>
            <input type="number" class="form-control" name="width" min="32" max="4096" value="1280" required>
          </div>
          <div class="col-md-6">
            <label class="h-label" style="display:block;">Max Height</label>
            <input type="number" class="form-control" name="height" min="32" max="4096" value="1280" required>
          </div>
          <div class="col-12">
            <label class="h-switch">
              <input type="hidden" name="replace" value="0">
              <input type="checkbox" name="replace" value="1">
              <span class="track"><span class="thumb"></span></span>
              <span class="h-switch-text">Replace original file</span>
            </label>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary" data-busy-text="Resizing...">
            <i class="fa-solid fa-expand me-2"></i>
            Resize
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  const endpointList = String(document.body.dataset.fileManagerListUrl || '').trim();
  const endpointDelete = String(document.body.dataset.fileManagerDeleteUrl || '').trim();
  const endpointFolder = String(document.body.dataset.fileManagerFolderUrl || '').trim();
  const endpointExport = String(document.body.dataset.fileManagerExportUrl || '').trim();
  const endpointResize = String(document.body.dataset.fileManagerResizeUrl || '').trim();

  if (!endpointList || !window.HApi) return;

  const folderInput = document.getElementById('settings-media-folder');
  const searchInput = document.getElementById('settings-media-search');
  const createNameInput = document.getElementById('settings-media-create-name');
  const folderWrap = document.getElementById('settings-media-folders');
  const tableBody = document.getElementById('settings-media-table-body');
  const currentPath = document.getElementById('settings-media-current-path');
  const countText = document.getElementById('settings-media-count');
  const exportLink = document.getElementById('settings-media-export');

  const deleteText = document.getElementById('settings-media-delete-text');
  const deleteConfirmBtn = document.getElementById('settings-media-delete-confirm');
  const resizeForm = document.getElementById('settings-media-resize-form');
  const resizePathInput = document.getElementById('settings-media-resize-path');

  let deletePath = '';
  let state = {
    folder: sanitizeFolder((folderInput && folderInput.value) ? folderInput.value : ''),
    search: '',
  };

  function openModal(id) {
    if (window.HModal) {
      window.HModal.open(id);
      return;
    }
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('show');
  }

  function closeModal(id) {
    if (window.HModal) {
      window.HModal.close(id);
      return;
    }
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('show');
  }

  function sanitizeFolder(value) {
    const cleaned = String(value || '').replace(/\\/g, '/').trim().replace(/^\/+|\/+$/g, '');
    if (!cleaned) return '';

    return cleaned
      .split('/')
      .map((part) => part.trim())
      .filter((part) => part && part !== '.' && part !== '..')
      .map((part) => part.replace(/[^a-zA-Z0-9._-]+/g, '-').replace(/^-+|-+$/g, ''))
      .filter(Boolean)
      .join('/');
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function updateFolderState(nextFolder, syncInput = true) {
    state.folder = sanitizeFolder(nextFolder);
    if (syncInput && folderInput) folderInput.value = state.folder;

    if (currentPath) {
      currentPath.textContent = '/uploads' + (state.folder ? ('/' + state.folder) : '');
    }

    if (exportLink && endpointExport) {
      const url = new URL(endpointExport, window.location.origin);
      if (state.folder) {
        url.searchParams.set('folder', state.folder);
      }
      exportLink.href = url.toString();
    }

    if (window.history && typeof window.history.replaceState === 'function') {
      const url = new URL(window.location.href);
      if (state.folder) {
        url.searchParams.set('folder', state.folder);
      } else {
        url.searchParams.delete('folder');
      }
      window.history.replaceState({}, '', url.toString());
    }
  }

  function loadMedia() {
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="7" class="text-center h-muted py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading...</td></tr>';

    window.HApi.get(endpointList, {
      folder: state.folder,
      q: state.search,
      limit: 240,
    }).done((payload) => {
      const items = Array.isArray(payload.items) ? payload.items : [];
      const folders = Array.isArray(payload.folders) ? payload.folders : [];
      updateFolderState(String(payload.current_folder || state.folder || ''), true);
      renderFolders(folders);
      renderRows(items);
    }).fail(() => {
      tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Unable to load media files.</td></tr>';
    });
  }

  function renderFolders(folders) {
    if (!folderWrap) return;

    const actions = [];
    if (state.folder) {
      actions.push('<button type="button" class="btn btn-outline-secondary btn-sm" data-open-folder=""><i class="fa-solid fa-house me-1"></i>Root</button>');
      const parent = state.folder.includes('/') ? state.folder.slice(0, state.folder.lastIndexOf('/')) : '';
      actions.push('<button type="button" class="btn btn-outline-secondary btn-sm" data-open-folder="' + escapeHtml(parent) + '"><i class="fa-solid fa-arrow-left me-1"></i>Up</button>');
    }

    folders.forEach((folder) => {
      const path = escapeHtml(folder.path || '');
      const name = escapeHtml(folder.name || path || 'Folder');
      actions.push('<button type="button" class="btn btn-outline-secondary btn-sm" data-open-folder="' + path + '"><i class="fa-regular fa-folder me-1"></i>' + name + '</button>');
    });

    folderWrap.innerHTML = actions.length
      ? actions.join('')
      : '<span class="h-muted">No subfolders</span>';
  }

  function renderRows(items) {
    if (!tableBody) return;

    if (!Array.isArray(items) || items.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="7" class="text-center h-muted py-4">No files found.</td></tr>';
      if (countText) countText.textContent = '0 files';
      return;
    }

    if (countText) {
      countText.textContent = items.length + ' file' + (items.length === 1 ? '' : 's');
    }

    tableBody.innerHTML = items.map((item) => {
      const name = escapeHtml(item.name || 'file');
      const path = escapeHtml(item.path || '');
      const url = escapeHtml(item.url || '');
      const type = String(item.type || 'file');
      const ext = escapeHtml(item.extension || '');
      const size = escapeHtml(item.size_kb || '0');
      const modified = escapeHtml(item.modified_at || '-');

      let preview = '<span class="h-muted"><i class="fa-regular fa-file"></i></span>';
      if (type === 'image') {
        preview = '<img src="' + url + '" alt="' + name + '" style="width:52px;height:40px;object-fit:cover;border-radius:8px;border:1px solid var(--bd2);">';
      } else if (type === 'audio') {
        preview = '<span class="h-muted"><i class="fa-solid fa-wave-square"></i></span>';
      }

      return [
        '<tr>',
        '<td>' + preview + '</td>',
        '<td>' + name + '</td>',
        '<td><code>' + path + '</code></td>',
        '<td>' + escapeHtml(type.toUpperCase()) + ' <span class="h-muted">(' + ext.toUpperCase() + ')</span></td>',
        '<td>' + size + ' KB</td>',
        '<td>' + modified + '</td>',
        '<td class="h-col-actions">',
        '  <span class="h-action-group">',
        '    <button type="button" class="btn btn-outline-secondary btn-sm h-action-icon" data-copy-url="' + url + '" title="Copy URL"><i class="fa-solid fa-link"></i></button>',
        (type === 'image' ? '    <button type="button" class="btn btn-outline-secondary btn-sm h-action-icon" data-resize-path="' + path + '" title="Resize image"><i class="fa-solid fa-expand"></i></button>' : ''),
        '    <button type="button" class="btn btn-outline-danger btn-sm h-action-icon" data-delete-path="' + path + '" data-delete-name="' + name + '" title="Delete"><i class="fa-solid fa-trash"></i></button>',
        '  </span>',
        '</td>',
        '</tr>'
      ].join('');
    }).join('');
  }

  function createFolder() {
    if (!endpointFolder) {
      if (window.HToast) HToast.error('Folder endpoint is not configured.');
      return;
    }

    const rawName = String((createNameInput && createNameInput.value) ? createNameInput.value : '').trim();
    const name = rawName
      .replace(/[\\\\/]+/g, '-')
      .replace(/[^a-zA-Z0-9._-]+/g, '-')
      .replace(/^-+|-+$/g, '');

    if (!name) {
      if (window.HToast) HToast.warning('Enter a valid folder name.');
      return;
    }

    window.HApi.post(endpointFolder, {
      parent: state.folder,
      name,
    }, {
      dataType: 'json',
      headers: { Accept: 'application/json' },
    }).done((payload) => {
      if (createNameInput) createNameInput.value = '';
      const nextFolder = [state.folder, name].filter(Boolean).join('/');
      updateFolderState(nextFolder, true);
      loadMedia();
      if (window.HToast) HToast.success(payload && payload.message ? payload.message : 'Folder created.');
    }).fail((xhr) => {
      const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to create folder.';
      if (window.HToast) HToast.error(message);
    });
  }

  function deleteFile(path) {
    if (!endpointDelete) {
      if (window.HToast) HToast.error('Delete endpoint is not configured.');
      return;
    }

    window.HApi.post(endpointDelete, { path }, {
      dataType: 'json',
      headers: { Accept: 'application/json' },
    }).done((payload) => {
      closeModal('settings-media-delete-modal');
      loadMedia();
      if (window.HToast) HToast.success(payload && payload.message ? payload.message : 'Media deleted.');
    }).fail((xhr) => {
      const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to delete media.';
      if (window.HToast) HToast.error(message);
    });
  }

  function resizeImage(form) {
    if (!endpointResize) {
      if (window.HToast) HToast.error('Resize endpoint is not configured.');
      return;
    }

    const formData = new FormData(form);
    window.HApi.post(endpointResize, formData, {
      dataType: 'json',
      processData: false,
      contentType: false,
      headers: { Accept: 'application/json' },
    }).done((payload) => {
      closeModal('settings-media-resize-modal');
      loadMedia();
      if (window.HToast) HToast.success(payload && payload.message ? payload.message : 'Image resized.');
    }).fail((xhr) => {
      const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to resize image.';
      if (window.HToast) HToast.error(message);
    });
  }

  if (folderInput) {
    folderInput.addEventListener('change', () => {
      updateFolderState(folderInput.value, true);
      loadMedia();
    });
  }

  if (searchInput) {
    let timer = null;
    searchInput.addEventListener('input', () => {
      state.search = String(searchInput.value || '').trim();
      window.clearTimeout(timer);
      timer = window.setTimeout(() => loadMedia(), 180);
    });
  }

  const loadButton = document.getElementById('settings-media-load');
  if (loadButton) {
    loadButton.addEventListener('click', () => loadMedia());
  }

  const rootButton = document.getElementById('settings-media-root');
  if (rootButton) {
    rootButton.addEventListener('click', () => {
      updateFolderState('', true);
      loadMedia();
    });
  }

  const createButton = document.getElementById('settings-media-create-folder');
  if (createButton) {
    createButton.addEventListener('click', createFolder);
  }

  if (folderWrap) {
    folderWrap.addEventListener('click', (event) => {
      const button = event.target.closest('[data-open-folder]');
      if (!button) return;
      const folder = String(button.getAttribute('data-open-folder') || '');
      updateFolderState(folder, true);
      loadMedia();
    });
  }

  if (tableBody) {
    tableBody.addEventListener('click', (event) => {
      const copyBtn = event.target.closest('[data-copy-url]');
      if (copyBtn) {
        const url = String(copyBtn.getAttribute('data-copy-url') || '').trim();
        if (!url) return;
        if (navigator && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
          navigator.clipboard.writeText(url)
            .then(() => window.HToast && HToast.success('URL copied.'))
            .catch(() => window.HToast && HToast.info('URL: ' + url));
        } else if (window.HToast) {
          HToast.info('URL: ' + url);
        }
        return;
      }

      const deleteBtn = event.target.closest('[data-delete-path]');
      if (deleteBtn) {
        deletePath = String(deleteBtn.getAttribute('data-delete-path') || '').trim();
        const label = String(deleteBtn.getAttribute('data-delete-name') || deletePath || 'file');
        if (deleteText) {
          deleteText.textContent = 'Delete "' + label + '" permanently?';
        }
        openModal('settings-media-delete-modal');
        return;
      }

      const resizeBtn = event.target.closest('[data-resize-path]');
      if (resizeBtn) {
        const path = String(resizeBtn.getAttribute('data-resize-path') || '').trim();
        if (!path || !resizePathInput) return;
        resizePathInput.value = path;
        openModal('settings-media-resize-modal');
      }
    });
  }

  if (deleteConfirmBtn) {
    deleteConfirmBtn.addEventListener('click', () => {
      if (!deletePath) return;
      deleteFile(deletePath);
    });
  }

  if (resizeForm) {
    resizeForm.addEventListener('submit', (event) => {
      event.preventDefault();
      resizeImage(resizeForm);
    });
  }

  updateFolderState(state.folder, true);
  loadMedia();
})();
</script>
@endsection
