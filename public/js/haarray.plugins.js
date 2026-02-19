(function (window, document, $) {
  'use strict';

  if (!$) return;

  const HPlugins = {
    init(root) {
      const target = root || document;
      HConfirm.init();
      HTabs.init(target);
      HIcons.init(target);
      HSelect.init(target);
      HSelectRemote.init(target);
      HEditor.init(target);
      HSvgPie.init(target);
    },
  };

  /* --------------------------
     HConfirm: confirm modal
  ---------------------------*/
  const HConfirm = {
    _bound: false,

    init() {
      this.$modal = $('[data-h-confirm]').first();
      if (!this.$modal.length || this._bound) return;

      this.$title = this.$modal.find('#h-confirm-title');
      this.$text = this.$modal.find('#h-confirm-text');
      this.$ok = this.$modal.find('[data-h-confirm-ok]');
      this.$cancel = this.$modal.find('[data-h-confirm-cancel]');
      this.$close = this.$modal.find('[data-h-confirm-close]');
      this.current = null;

      $(document).on('click', 'a[data-confirm="true"]', (event) => {
        const $anchor = $(event.currentTarget);
        if (event.ctrlKey || event.metaKey || event.which === 2) return;
        event.preventDefault();
        this._openFromAnchor($anchor);
      });

      $(document).on('submit', 'form[data-confirm="true"]', (event) => {
        event.preventDefault();
        this._openFromForm($(event.currentTarget));
      });

      this.$cancel.on('click', () => this.close());
      this.$close.on('click', () => this.close());
      this.$modal.on('click', (event) => {
        if (event.target === this.$modal[0]) this.close();
      });
      this.$ok.on('click', () => this._doConfirm());

      $(document).on('keydown', (event) => {
        if (event.key === 'Escape') this.close();
      });

      this._bound = true;
    },

    _openFromAnchor($anchor) {
      this.current = {
        type: 'link',
        href: $anchor.attr('href') || '',
        method: String($anchor.data('confirm-method') || 'GET').toUpperCase(),
      };

      this._show(
        $anchor.data('confirm-title') || 'Confirm action',
        $anchor.data('confirm-text') || 'Are you sure you want to continue?',
        $anchor.data('confirm-ok') || 'Continue',
        $anchor.data('confirm-cancel') || 'Cancel'
      );
    },

    _openFromForm($form) {
      this.current = {
        type: 'form',
        $form,
      };

      this._show(
        $form.data('confirm-title') || 'Confirm action',
        $form.data('confirm-text') || 'Are you sure you want to continue?',
        $form.data('confirm-ok') || 'Continue',
        $form.data('confirm-cancel') || 'Cancel'
      );
    },

    _show(title, text, okText, cancelText) {
      this.$title.text(title);
      this.$text.text(text);
      this.$ok.text(okText);
      this.$cancel.text(cancelText);
      this.$modal.addClass('show').attr('aria-hidden', 'false');
      $('body').css('overflow', 'hidden');
    },

    close() {
      if (!this.$modal || !this.$modal.length) return;
      this.$modal.removeClass('show').attr('aria-hidden', 'true');
      $('body').css('overflow', '');
      this.current = null;
    },

    _doConfirm() {
      if (!this.current) {
        this.close();
        return;
      }

      if (this.current.type === 'link') {
        const method = this.current.method;
        const href = this.current.href;

        if (!href) {
          this.close();
          return;
        }

        if (method === 'GET') {
          window.location.href = href;
          this.close();
          return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = href;
        form.style.display = 'none';

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
          const csrf = document.createElement('input');
          csrf.type = 'hidden';
          csrf.name = '_token';
          csrf.value = tokenMeta.getAttribute('content') || '';
          form.appendChild(csrf);
        }

        if (method !== 'POST') {
          const override = document.createElement('input');
          override.type = 'hidden';
          override.name = '_method';
          override.value = method;
          form.appendChild(override);
        }

        document.body.appendChild(form);
        form.submit();
        this.close();
        return;
      }

      if (this.current.type === 'form' && this.current.$form) {
        const $form = this.current.$form;
        $form.attr('data-confirm-bypass', '1');
        $form.trigger('submit');
        setTimeout(() => $form.removeAttr('data-confirm-bypass'), 0);
        this.close();
      }
    },
  };

  $(document).on('submit', 'form[data-confirm="true"][data-confirm-bypass="1"]', function () {
    this.submit();
    return false;
  });

  /* --------------------------
     HTabs: reusable tab UI
  ---------------------------*/
  const HTabs = {
    selector: '[data-ui-tabs]',

    init(root) {
      const ctx = root && root.querySelectorAll ? root : document;
      ctx.querySelectorAll(this.selector).forEach((container) => this.setup(container));
    },

    setup(container) {
      if (!container || container.dataset.hTabsReady === '1') return;

      const buttons = Array.from(container.querySelectorAll('[data-tab-btn]'));
      const panels = Array.from(container.querySelectorAll('[data-tab-panel]'));
      if (!buttons.length || !panels.length) return;

      const fallback = buttons[0].dataset.tabBtn;
      const initial = container.dataset.defaultTab || fallback;

      const activate = (tabId, shouldFocus = false) => {
        const requested = String(tabId || '').trim();
        const hasMatch = buttons.some((button) => button.dataset.tabBtn === requested);
        const activeId = hasMatch ? requested : fallback;

        buttons.forEach((button) => {
          const isActive = button.dataset.tabBtn === activeId;
          button.classList.toggle('is-active', isActive);
          button.setAttribute('aria-selected', isActive ? 'true' : 'false');
          button.setAttribute('tabindex', isActive ? '0' : '-1');
          if (isActive && shouldFocus) button.focus();
        });

        let activePanel = null;
        panels.forEach((panel) => {
          const isActive = panel.dataset.tabPanel === activeId;
          panel.classList.toggle('is-active', isActive);
          panel.hidden = !isActive;
          if (isActive) activePanel = panel;
        });

        container.dataset.activeTab = activeId;
        document.dispatchEvent(
          new CustomEvent('h:tabs:changed', {
            detail: {
              tabId: activeId,
              container,
              panel: activePanel,
            },
          })
        );
      };

      buttons.forEach((button) => {
        button.addEventListener('click', () => activate(button.dataset.tabBtn));
        button.addEventListener('keydown', (event) => {
          const index = buttons.indexOf(button);
          if (index < 0) return;

          if (event.key === 'ArrowRight') {
            event.preventDefault();
            const next = buttons[(index + 1) % buttons.length];
            activate(next.dataset.tabBtn, true);
            return;
          }

          if (event.key === 'ArrowLeft') {
            event.preventDefault();
            const next = buttons[(index - 1 + buttons.length) % buttons.length];
            activate(next.dataset.tabBtn, true);
          }
        });
      });

      activate(initial);
      container.dataset.hTabsReady = '1';
    },
  };

  /* --------------------------
     HSelect: custom select
  ---------------------------*/
  const HSelect = {
    selector: 'select[data-h-select]:not([data-select2-remote]), select.h-select2:not([data-select2-remote])',
    _bound: false,

    init(root) {
      const ctx = root && root.querySelectorAll ? root : document;
      ctx.querySelectorAll(this.selector).forEach((select) => this.setup(select));
      this._bindGlobalHandlers();
    },

    setup(select) {
      if (select.dataset.hSelectReady === '1') return;

      const options = Array.from(select.options);
      const isMultiple = select.multiple;
      const searchEnabled = select.dataset.hSearch !== 'false' && options.length >= (Number(select.dataset.hSearchMin) || 8);
      const placeholder = select.dataset.placeholder || 'Select an option';

      const wrapper = document.createElement('div');
      wrapper.className = 'h-select2-wrap';
      wrapper.dataset.multiple = isMultiple ? 'true' : 'false';

      wrapper.innerHTML = `
        <button type="button" class="h-select2-control" aria-haspopup="listbox" aria-expanded="false">
          <span class="h-select2-value"></span>
          <span class="h-select2-chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </button>
        <div class="h-select2-panel" hidden>
          <div class="h-select2-search ${searchEnabled ? '' : 'is-hidden'}">
            <input type="text" class="h-select2-search-input" placeholder="Search..." autocomplete="off">
          </div>
          <ul class="h-select2-options" role="listbox"></ul>
        </div>
      `;

      select.classList.add('h-select-native');
      select.dataset.hSelectReady = '1';
      select.after(wrapper);

      const instance = {
        select,
        wrapper,
        control: wrapper.querySelector('.h-select2-control'),
        valueEl: wrapper.querySelector('.h-select2-value'),
        panel: wrapper.querySelector('.h-select2-panel'),
        searchInput: wrapper.querySelector('.h-select2-search-input'),
        list: wrapper.querySelector('.h-select2-options'),
        isMultiple,
        searchEnabled,
        placeholder,
        query: '',
      };

      wrapper.__hSelect = instance;
      this._render(instance);
      this._bindInstance(instance);
    },

    _bindInstance(instance) {
      const { control, panel, searchInput, select } = instance;

      control.addEventListener('click', () => {
        const isOpen = instance.wrapper.classList.contains('is-open');
        isOpen ? this.close(instance) : this.open(instance);
      });

      control.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          this.open(instance);
        }
      });

      panel.addEventListener('click', (event) => {
        const item = event.target.closest('[data-value]');
        if (!item || item.classList.contains('is-disabled')) return;

        const option = Array.from(select.options).find((opt) => opt.value === item.dataset.value);
        if (!option) return;

        if (instance.isMultiple) {
          option.selected = !option.selected;
          this._render(instance);
          this.open(instance);
        } else {
          select.value = option.value;
          this._render(instance);
          this.close(instance);
        }

        select.dispatchEvent(new Event('change', { bubbles: true }));
      });

      if (instance.searchEnabled) {
        searchInput.addEventListener('input', (event) => {
          instance.query = event.target.value.toLowerCase().trim();
          this._renderOptions(instance);
        });
      }

      select.addEventListener('change', () => {
        this._render(instance);
      });
    },

    _bindGlobalHandlers() {
      if (this._bound) return;

      document.addEventListener('click', (event) => {
        document.querySelectorAll('.h-select2-wrap.is-open').forEach((wrap) => {
          if (wrap.contains(event.target)) return;
          this.close(wrap.__hSelect);
        });
      });

      document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.h-select2-wrap.is-open').forEach((wrap) => {
          this.close(wrap.__hSelect);
        });
      });

      this._bound = true;
    },

    open(instance) {
      if (!instance) return;

      document.querySelectorAll('.h-select2-wrap.is-open').forEach((wrap) => {
        if (wrap === instance.wrapper) return;
        this.close(wrap.__hSelect);
      });

      instance.wrapper.classList.add('is-open');
      instance.control.setAttribute('aria-expanded', 'true');
      instance.panel.hidden = false;

      if (instance.searchEnabled && instance.searchInput) {
        instance.searchInput.focus();
      }
    },

    close(instance) {
      if (!instance) return;
      instance.wrapper.classList.remove('is-open');
      instance.control.setAttribute('aria-expanded', 'false');
      instance.panel.hidden = true;
    },

    _render(instance) {
      this._renderValue(instance);
      this._renderOptions(instance);
    },

    _renderValue(instance) {
      const selected = Array.from(instance.select.selectedOptions).filter((opt) => opt.value !== '');

      if (!selected.length) {
        instance.valueEl.innerHTML = `<span class="h-select2-placeholder">${instance.placeholder}</span>`;
        return;
      }

      if (!instance.isMultiple) {
        instance.valueEl.textContent = selected[0].textContent;
        return;
      }

      const tags = selected
        .slice(0, 3)
        .map((opt) => `<span class="h-select2-tag">${opt.textContent}</span>`)
        .join('');

      const extra = selected.length > 3 ? `<span class="h-select2-more">+${selected.length - 3}</span>` : '';
      instance.valueEl.innerHTML = `<span class="h-select2-tags">${tags}${extra}</span>`;
    },

    _renderOptions(instance) {
      const selectedValues = new Set(
        Array.from(instance.select.selectedOptions).map((opt) => String(opt.value))
      );

      const query = instance.query;
      const html = Array.from(instance.select.options)
        .filter((option) => {
          if (!query) return true;
          return option.textContent.toLowerCase().includes(query);
        })
        .map((option) => {
          const value = String(option.value);
          const isSelected = selectedValues.has(value);
          const classes = [
            'h-select2-option',
            isSelected ? 'is-selected' : '',
            option.disabled ? 'is-disabled' : '',
          ]
            .filter(Boolean)
            .join(' ');

          return `<li class="${classes}" data-value="${value}" role="option" aria-selected="${isSelected ? 'true' : 'false'}">${option.textContent}</li>`;
        })
        .join('');

      instance.list.innerHTML = html || '<li class="h-select2-empty">No options found</li>';
    },
  };

  /* --------------------------
     HSelectRemote: select2 ajax
  ---------------------------*/
  const HSelectRemote = {
    selector: 'select[data-select2-remote]',

    init(root) {
      if (typeof $.fn.select2 !== 'function') return;

      const $root = root && root.querySelectorAll ? $(root) : $(document);
      const $targets = $root.is(this.selector) ? $root : $root.find(this.selector);

      $targets.each((_, element) => this.setup($(element)));
    },

    setup($select) {
      if ($select.data('hSelect2Ready')) return;

      const endpoint = $select.data('endpoint');
      if (!endpoint) return;

      const placeholder = $select.data('placeholder') || 'Search options';
      const minInput = Number($select.data('minInput') || 1);
      const valueField = $select.data('valueField') || 'id';
      const textField = $select.data('textField') || 'text';
      const imageField = $select.data('imageField') || 'image';
      const subtitleField = $select.data('subtitleField') || 'subtitle';

      const renderOption = (item) => {
        if (item.loading) return item.text;

        const image = item[imageField] || (item.element ? item.element.dataset.image : '');
        const subtitle = item[subtitleField] || (item.element ? item.element.dataset.subtitle : '');
        const label = item[textField] || item.text || '';
        const imgHtml = image ? `<img src="${image}" alt="" class="h-s2-img">` : '<span class="h-s2-avatar"><i class="fa-solid fa-user"></i></span>';
        const subHtml = subtitle ? `<span class="h-s2-sub">${subtitle}</span>` : '';

        return $(`
          <span class="h-s2-option">
            ${imgHtml}
            <span class="h-s2-copy">
              <span class="h-s2-text">${label}</span>
              ${subHtml}
            </span>
          </span>
        `);
      };

      const renderSelection = (item) => {
        if (!item.id) return item.text || placeholder;
        const image = item[imageField] || (item.element ? item.element.dataset.image : '');
        const label = item[textField] || item.text || '';
        if (!image) return label;
        return $(`
          <span class="h-s2-selected">
            <img src="${image}" alt="" class="h-s2-selected-img">
            <span>${label}</span>
          </span>
        `);
      };

      $select.select2({
        width: '100%',
        placeholder,
        allowClear: !$select.prop('multiple'),
        minimumInputLength: minInput,
        ajax: {
          url: endpoint,
          dataType: 'json',
          delay: 250,
          data(params) {
            return {
              q: params.term || '',
              page: params.page || 1,
            };
          },
          processResults(data) {
            const rows = Array.isArray(data.results) ? data.results : [];
            const mapped = rows.map((row) => ({
              id: row[valueField] ?? row.id,
              text: row[textField] ?? row.text,
              image: row[imageField] ?? row.image,
              subtitle: row[subtitleField] ?? row.subtitle,
            }));

            return {
              results: mapped,
              pagination: {
                more: Boolean(data.pagination && data.pagination.more),
              },
            };
          },
          cache: true,
        },
        templateResult: renderOption,
        templateSelection: renderSelection,
      });

      $select.data('hSelect2Ready', true);
    },
  };

  /* --------------------------
     HEditor: rich text editor
  ---------------------------*/
  const HEditor = {
    selector: '[data-editor], .h-editor',
    toolbar: [
      { cmd: 'bold', label: 'B', title: 'Bold' },
      { cmd: 'italic', label: 'I', title: 'Italic' },
      { cmd: 'underline', label: 'U', title: 'Underline' },
      { cmd: 'insertUnorderedList', label: '• List', title: 'Bulleted list' },
      { cmd: 'insertOrderedList', label: '1. List', title: 'Numbered list' },
      { cmd: 'formatBlock', arg: 'blockquote', label: 'Quote', title: 'Blockquote' },
      { cmd: 'createLink', label: 'Link', title: 'Insert link' },
      { cmd: 'removeFormat', label: 'Clear', title: 'Clear formatting' },
    ],

    init(root) {
      const ctx = root && root.querySelectorAll ? root : document;
      ctx.querySelectorAll(this.selector).forEach((el) => this.setup(el));
    },

    setup(el) {
      if (el.dataset.hEditorReady === '1') return;

      el.dataset.hEditorReady = '1';
      el.classList.add('h-editor');
      el.setAttribute('contenteditable', 'true');
      el.setAttribute('spellcheck', 'true');

      const isBare = el.dataset.editor === 'bare' || el.classList.contains('h-editor--bare');
      const editorName = el.dataset.editorName || el.getAttribute('name') || el.getAttribute('id') || '';
      const placeholder = el.dataset.placeholder || 'Start typing...';

      if (!el.textContent.trim()) {
        el.setAttribute('data-placeholder', placeholder);
      }

      if (!isBare) this._insertToolbar(el);
      this._bindEditorEvents(el, editorName);
      this._syncHiddenField(el, editorName);
    },

    _insertToolbar(editorEl) {
      if (editorEl.previousElementSibling && editorEl.previousElementSibling.classList.contains('h-editor-toolbar')) {
        return;
      }

      const toolbar = document.createElement('div');
      toolbar.className = 'h-editor-toolbar';

      toolbar.innerHTML = this.toolbar
        .map(
          (item) =>
            `<button type="button" class="h-editor-btn" data-cmd="${item.cmd}" data-arg="${item.arg || ''}" title="${item.title}">${item.label}</button>`
        )
        .join('');

      editorEl.parentNode.insertBefore(toolbar, editorEl);

      toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cmd]');
        if (!button) return;

        const cmd = button.dataset.cmd;
        const arg = button.dataset.arg || null;

        editorEl.focus();

        if (cmd === 'createLink') {
          const href = prompt('Enter URL');
          if (!href) return;
          document.execCommand('createLink', false, href);
        } else if (cmd === 'formatBlock') {
          document.execCommand('formatBlock', false, arg || 'p');
        } else {
          document.execCommand(cmd, false, null);
        }

        this._syncHiddenField(editorEl, editorEl.dataset.editorName || editorEl.getAttribute('name') || '');
      });
    },

    _bindEditorEvents(editorEl, editorName) {
      editorEl.addEventListener('input', () => {
        this._syncHiddenField(editorEl, editorName);
      });
      editorEl.addEventListener('blur', () => {
        this._sanitize(editorEl);
        this._syncHiddenField(editorEl, editorName);
      });
      editorEl.addEventListener('focus', () => editorEl.classList.add('is-focused'));
      editorEl.addEventListener('blur', () => editorEl.classList.remove('is-focused'));
      editorEl.addEventListener('paste', (event) => {
        if (editorEl.dataset.paste === 'rich') return;
        event.preventDefault();
        const text = (event.clipboardData || window.clipboardData).getData('text');
        document.execCommand('insertText', false, text);
      });
    },

    _syncHiddenField(editorEl, editorName) {
      const form = editorEl.closest('form');
      if (!form || !editorName) return;

      let hidden = form.querySelector('textarea[data-editor-hidden="' + editorName + '"]');
      if (!hidden) {
        hidden = document.createElement('textarea');
        hidden.name = editorName;
        hidden.setAttribute('data-editor-hidden', editorName);
        hidden.style.display = 'none';
        form.appendChild(hidden);
      }

      hidden.value = editorEl.innerHTML.trim();
    },

    _sanitize(editorEl) {
      const doc = new DOMParser().parseFromString('<div>' + editorEl.innerHTML + '</div>', 'text/html');
      const root = doc.body.firstElementChild;
      if (!root) return;

      root.querySelectorAll('script,style,iframe,object,embed').forEach((node) => node.remove());
      root.querySelectorAll('*').forEach((node) => {
        Array.from(node.attributes).forEach((attr) => {
          const name = attr.name.toLowerCase();
          if (name.startsWith('on') || name === 'style') node.removeAttribute(attr.name);
        });
      });

      editorEl.innerHTML = root.innerHTML;
    },
  };

  /* --------------------------
     HIcons
  ---------------------------*/
  const HIcons = {
    init(root) {
      const ctx = root && root.querySelectorAll ? root : document;

      ctx.querySelectorAll('[data-icon]').forEach((node) => {
        if (node.dataset.iconReady === '1') return;

        const name = node.dataset.icon;
        const size = Number(node.dataset.iconSize || node.dataset.w || 16);
        const classes = node.className || 'h-icon';

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', classes);
        svg.setAttribute('width', String(size));
        svg.setAttribute('height', String(size));
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('aria-hidden', 'true');

        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttribute('href', '/icons/icons.svg#' + name);
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '/icons/icons.svg#' + name);
        svg.appendChild(use);

        node.dataset.iconReady = '1';
        node.replaceWith(svg);
      });
    },

    svg(name, className, size) {
      const cls = className || 'h-icon';
      const sz = size || 16;
      return `<svg class="${cls}" width="${sz}" height="${sz}" viewBox="0 0 24 24" aria-hidden="true"><use href="/icons/icons.svg#${name}" xlink:href="/icons/icons.svg#${name}"></use></svg>`;
    },
  };

  /* --------------------------
     HSvgPie
  ---------------------------*/
  const HSvgPie = {
    init(root) {
      const ctx = root && root.querySelectorAll ? root : document;
      ctx.querySelectorAll('.h-svg-pie[data-pie]').forEach((el) => this.render(el));
    },

    render(container) {
      let data;
      try {
        data = JSON.parse(container.getAttribute('data-pie') || '[]');
      } catch (error) {
        return;
      }

      if (!Array.isArray(data) || !data.length) return;

      const total = data.reduce((sum, row) => sum + (Number(row.value) || 0), 0) || 1;
      const NS = 'http://www.w3.org/2000/svg';
      const svg = document.createElementNS(NS, 'svg');
      svg.setAttribute('viewBox', '0 0 240 120');
      svg.setAttribute('preserveAspectRatio', 'xMinYMid meet');

      const cx = 58;
      const cy = 60;
      const radius = 42;
      let angle = -Math.PI / 2;

      data.forEach((row, index) => {
        const value = Number(row.value) || 0;
        const arc = (value / total) * Math.PI * 2;
        const end = angle + arc;

        const x1 = cx + radius * Math.cos(angle);
        const y1 = cy + radius * Math.sin(angle);
        const x2 = cx + radius * Math.cos(end);
        const y2 = cy + radius * Math.sin(end);

        const largeArc = arc > Math.PI ? 1 : 0;
        const path = document.createElementNS(NS, 'path');
        const color = row.color || this._color(index);

        path.setAttribute('d', `M ${cx} ${cy} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`);
        path.setAttribute('fill', color);

        svg.appendChild(path);

        const y = 22 + index * 18;
        const swatch = document.createElementNS(NS, 'rect');
        swatch.setAttribute('x', '128');
        swatch.setAttribute('y', String(y));
        swatch.setAttribute('width', '10');
        swatch.setAttribute('height', '10');
        swatch.setAttribute('rx', '2');
        swatch.setAttribute('fill', color);
        svg.appendChild(swatch);

        const label = document.createElementNS(NS, 'text');
        label.setAttribute('x', '144');
        label.setAttribute('y', String(y + 9));
        label.setAttribute('font-size', '10');
        label.setAttribute('fill', getComputedStyle(document.documentElement).getPropertyValue('--t1').trim() || '#f0f0f0');
        label.textContent = `${row.label || 'Item'} (${value})`;
        svg.appendChild(label);

        angle = end;
      });

      container.innerHTML = '';
      container.appendChild(svg);
    },

    _color(index) {
      const colors = ['#f5a623', '#2dd4bf', '#60a5fa', '#f87171', '#a78bfa', '#34d399'];
      return colors[index % colors.length];
    },
  };

  $(function () {
    HPlugins.init(document);

    window.HConfirm = HConfirm;
    window.HTabs = HTabs;
    window.HSelect = HSelect;
    window.HSelectRemote = HSelectRemote;
    window.HEditor = HEditor;
    window.HIcons = HIcons;
    window.HSvgPie = HSvgPie;
  });

  document.addEventListener('hspa:afterSwap', function (event) {
    const root = event.detail && event.detail.container ? event.detail.container : document;
    HPlugins.init(root);
  });
})(window, document, window.jQuery);
