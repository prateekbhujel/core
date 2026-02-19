(function ($, window, document) {
  'use strict';

  const HCore = {
    emit(name, detail = {}) {
      document.dispatchEvent(new CustomEvent(name, { detail }));
    },
  };

  /* ── THEME ────────────────────────────────────────────── */
  const HTheme = {
    init() {
      const saved = localStorage.getItem('h_theme') || 'dark';
      this.apply(saved, false);

      $(document).on('click', '.h-theme-toggle', () => {
        this.apply(this.current() === 'dark' ? 'light' : 'dark');
      });
    },

    apply(theme, toast = true) {
      const finalTheme = theme === 'light' ? 'light' : 'dark';
      $('html').attr('data-theme', finalTheme);
      localStorage.setItem('h_theme', finalTheme);
      $('.h-theme-toggle .moon').toggle(finalTheme === 'dark');
      $('.h-theme-toggle .sun').toggle(finalTheme === 'light');
      if (toast) HToast.info('Switched to ' + finalTheme + ' mode');
    },

    current() {
      return $('html').attr('data-theme') === 'light' ? 'light' : 'dark';
    },
  };

  /* ── SIDEBAR ──────────────────────────────────────────── */
  const HSidebar = {
    storageKey: 'h_nav_group_state',

    init() {
      $(document).on('click', '.h-menu-toggle', () => this.toggle());
      $(document).on('click', '.h-sidebar-overlay', () => this.close());
      $(document).on('click', '.h-nav-item, .h-nav-sub-item', (event) => {
        if ($(window).width() > 768) return;
        if ($(event.currentTarget).is('[data-nav-toggle]')) return;
        this.close();
      });

      $(document).on('click', '[data-nav-toggle]', (event) => {
        event.preventDefault();
        const key = String($(event.currentTarget).data('nav-toggle') || '').trim();
        if (!key) return;
        this.toggleGroup(key);
      });

      this.restoreGroupState();
      this.syncGroups();
    },

    toggle() {
      $('#h-sidebar').hasClass('open') ? this.close() : this.open();
    },

    open() {
      $('#h-sidebar').addClass('open');
      $('.h-sidebar-overlay').addClass('show');
      $('body').css('overflow', 'hidden');
    },

    close() {
      $('#h-sidebar').removeClass('open');
      $('.h-sidebar-overlay').removeClass('show');
      $('body').css('overflow', '');
    },

    toggleGroup(key) {
      const group = document.querySelector('[data-nav-group="' + key + '"]');
      if (!group) return;
      const expanded = group.dataset.expanded === '1';
      group.dataset.manual = '1';
      group.dataset.expanded = expanded ? '0' : '1';
      this.persistGroupState();
      this.syncGroups();
    },

    syncGroups() {
      document.querySelectorAll('[data-nav-group]').forEach((group) => {
        const hasActiveChild = Boolean(group.querySelector('.h-nav-sub-item.active'));
        const expandedByUser = group.dataset.expanded === '1';
        const manualOverride = group.dataset.manual === '1';
        const shouldOpen = manualOverride ? expandedByUser : (hasActiveChild || expandedByUser);

        group.classList.toggle('open', shouldOpen);

        const toggle = group.querySelector('[data-nav-toggle]');
        if (toggle) {
          toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        }
      });
    },

    restoreGroupState() {
      let payload = {};
      try {
        payload = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
      } catch (error) {
        payload = {};
      }

      if (!payload || typeof payload !== 'object') return;

      document.querySelectorAll('[data-nav-group]').forEach((group) => {
        const key = String(group.dataset.navGroup || '').trim();
        if (!key || !payload[key]) return;

        const state = payload[key];
        if (state && typeof state === 'object') {
          if (state.expanded === '1' || state.expanded === '0') {
            group.dataset.expanded = state.expanded;
          }
          if (state.manual === '1' || state.manual === '0') {
            group.dataset.manual = state.manual;
          }
        }
      });
    },

    persistGroupState() {
      const state = {};
      document.querySelectorAll('[data-nav-group]').forEach((group) => {
        const key = String(group.dataset.navGroup || '').trim();
        if (!key) return;
        state[key] = {
          expanded: group.dataset.expanded === '1' ? '1' : '0',
          manual: group.dataset.manual === '1' ? '1' : '0',
        };
      });

      try {
        localStorage.setItem(this.storageKey, JSON.stringify(state));
      } catch (error) {
        // Ignore storage errors.
      }
    },
  };

  /* ── TOAST ────────────────────────────────────────────── */
  const HToast = {
    init() {
      if (!$('#h-toasts').length) $('body').append('<div id="h-toasts"></div>');
    },

    show(message, type = 'info', duration = 3800) {
      const safeMessage = String(message || '').trim() || 'Done';
      const tone = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
      const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
      };
      const titles = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Info',
      };

      const $toast = $(`
        <div class="h-toast h-toast-${tone}" role="status" aria-live="polite" style="--h-toast-duration:${Math.max(1200, duration)}ms;">
          <div class="h-toast-icon"><i class="fa-solid ${icons[tone]}"></i></div>
          <div class="h-toast-body">
            <div class="h-toast-title">${titles[tone]}</div>
            <div class="h-toast-msg">${this._escape(safeMessage)}</div>
          </div>
          <button type="button" class="h-toast-close" aria-label="Dismiss">
            <i class="fa-solid fa-xmark"></i>
          </button>
          <span class="h-toast-progress"></span>
        </div>
      `);

      $('#h-toasts').append($toast);
      requestAnimationFrame(() => $toast.addClass('show'));

      const removeToast = () => {
        $toast.removeClass('show');
        setTimeout(() => $toast.remove(), 300);
      };

      $toast.find('.h-toast-close').on('click', removeToast);
      setTimeout(removeToast, duration);
    },

    success(message, duration) {
      this.show(message, 'success', duration);
    },
    error(message, duration) {
      this.show(message, 'error', duration);
    },
    warning(message, duration) {
      this.show(message, 'warning', duration);
    },
    info(message, duration) {
      this.show(message, 'info', duration);
    },

    _escape(input) {
      return String(input ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },
  };

  /* ── MODAL ────────────────────────────────────────────── */
  const HModal = {
    init() {
      $(document).on('click', '[data-modal-open]', function () {
        HModal.open($(this).data('modal-open'));
      });

      $(document).on('click', '.h-modal-close, [data-modal-close]', function () {
        HModal.close($(this).closest('.h-modal-overlay').attr('id'));
      });

      $(document).on('click', '.h-modal-overlay', function (event) {
        if ($(event.target).hasClass('h-modal-overlay')) {
          HModal.close($(this).attr('id'));
        }
      });

      $(document).on('keydown', (event) => {
        if (event.key === 'Escape') HModal.closeAll();
      });
    },

    open(id) {
      if (!id) return;
      $('#' + id).addClass('show');
      $('body').css('overflow', 'hidden');
    },

    close(id) {
      if (!id) return;
      $('#' + id).removeClass('show');
      if (!$('.h-modal-overlay.show').length) $('body').css('overflow', '');
    },

    closeAll() {
      $('.h-modal-overlay').removeClass('show');
      $('body').css('overflow', '');
    },
  };

  /* ── NOTIFICATIONS ───────────────────────────────────── */
  const HNotify = {
    _pollTimer: null,
    _isLoading: false,
    _bootstrapped: false,
    _knownUnreadIds: new Set(),
    _lastAttentionAt: 0,

    init() {
      if (!$('#h-notif-tray').length) return;

      $(document).on('click', '[data-notif-toggle]', (event) => {
        event.preventDefault();
        event.stopPropagation();
        this.toggle();
      });

      $(document).on('click', '[data-notif-close]', (event) => {
        event.preventDefault();
        this.close();
      });

      $(document).on('click', (event) => {
        const $tray = $('#h-notif-tray');
        if (!$tray.length || !$tray.hasClass('show')) return;
        if ($(event.target).closest('#h-notif-tray, [data-notif-toggle]').length) return;
        this.close();
      });

      $(document).on('keydown', (event) => {
        if (event.key === 'Escape') this.close();
      });

      $(document).on('click', '[data-notif-refresh]', (event) => {
        event.preventDefault();
        this.refresh(false);
      });

      $(document).on('click', '.h-notif-item[data-notif-id]', (event) => {
        const itemEl = event.currentTarget;
        const id = itemEl.getAttribute('data-notif-id');
        if (!id) return;

        this.markRead(id);

        const targetUrl = itemEl.getAttribute('data-notif-url');
        if (!targetUrl) return;

        if (window.HUtils && HUtils.isSameOrigin(targetUrl) && window.HSPA) {
          HSPA.navigate(targetUrl, true);
        } else {
          window.location.href = targetUrl;
        }
      });

      document.addEventListener('hspa:afterLoad', () => this.refresh(true));

      this.refresh(true);
      const pollSeconds = Number($('body').data('notificationsPollSeconds') || 20);
      const safePollMs = Math.max(10, Math.min(pollSeconds, 300)) * 1000;
      this._pollTimer = window.setInterval(() => this.refresh(true), safePollMs);
    },

    open() {
      $('#h-notif-tray').addClass('show').attr('aria-hidden', 'false');
      this._requestBrowserPermission();
    },

    close() {
      $('#h-notif-tray').removeClass('show').attr('aria-hidden', 'true');
    },

    toggle() {
      const $tray = $('#h-notif-tray');
      if (!$tray.length) return;
      if ($tray.hasClass('show')) {
        this.close();
        return;
      }

      this.open();
      this.refresh(true);
    },

    refresh(silent = true) {
      const endpoint = $('body').data('notificationsFeedUrl');
      if (!endpoint || this._isLoading || !window.HApi) return;

      this._isLoading = true;
      HApi.get(endpoint)
        .done((payload) => {
          this.render(payload || {});
        })
        .fail(() => {
          if (!silent) HToast.error('Unable to load notifications.');
        })
        .always(() => {
          this._isLoading = false;
        });
    },

    render(payload) {
      const unreadCount = Number(payload.unread_count || 0);
      const items = Array.isArray(payload.items) ? payload.items : [];

      this._updateBell(unreadCount);

      const $list = $('#h-notif-list');
      if (!$list.length) return;

      if (!items.length) {
        this._knownUnreadIds = new Set();
        this._bootstrapped = true;
        $list.html(`
          <div class="h-notif-empty">
            <i class="fa-regular fa-bell-slash"></i>
            <span>No notifications yet.</span>
          </div>
        `);
        return;
      }

      const levelIcons = {
        info: 'fa-circle-info',
        success: 'fa-circle-check',
        warning: 'fa-triangle-exclamation',
        error: 'fa-circle-exclamation',
      };

      const rows = items
        .map((item) => {
          const level = String(item.level || 'info');
          const icon = levelIcons[level] || levelIcons.info;
          const isUnread = !item.read;
          const title = this._escape(item.title || 'Notification');
          const message = this._escape(item.message || '');
          const time = this._escape(item.time || '');
          const url = item.url ? this._escape(item.url) : '';

          return `
            <div class="h-notif-item ${isUnread ? 'is-unread' : ''}" data-notif-id="${this._escape(item.id || '')}" ${url ? `data-notif-url="${url}"` : ''}>
              <span class="h-notif-icon"><i class="fa-solid ${icon}"></i></span>
              <div style="min-width:0;">
                <div class="h-notif-item-title">${title}</div>
                ${message ? `<div class="h-notif-item-meta">${message}</div>` : ''}
                <div class="h-notif-item-time">${time}</div>
              </div>
            </div>
          `;
        })
        .join('');

      $list.html(rows);
      this._emitNewAlerts(items);
    },

    markRead(id) {
      const template = $('body').data('notificationReadUrlTemplate');
      if (!template || !id || !window.HApi) return;

      const readUrl = String(template).replace('__ID__', encodeURIComponent(String(id)));

      HApi.post(readUrl).done(() => {
        const $item = $('.h-notif-item[data-notif-id="' + id + '"]');
        $item.removeClass('is-unread');
        this.refresh(true);
      });
    },

    _updateBell(unreadCount) {
      const $dot = $('.h-notif-dot').first();
      if (!$dot.length) return;

      if (unreadCount > 0) {
        $dot.removeClass('is-hidden');
        $dot.attr('title', unreadCount + ' unread notifications');
      } else {
        $dot.addClass('is-hidden');
        $dot.removeAttr('title');
      }
    },

    _emitNewAlerts(items) {
      const unread = items.filter((item) => !item.read && item.id);
      const nextUnreadIds = new Set(unread.map((item) => String(item.id)));

      if (!this._bootstrapped) {
        this._knownUnreadIds = nextUnreadIds;
        this._bootstrapped = true;
        return;
      }

      const newItems = unread.filter((item) => !this._knownUnreadIds.has(String(item.id)));
      this._knownUnreadIds = nextUnreadIds;

      if (!newItems.length) return;

      const first = newItems[0];
      HToast.info('New notification: ' + String(first.title || 'Update'));
      this._triggerAttention();
      this._notifyBrowser(first);
    },

    _notifyBrowser(item) {
      if (!this._browserNotifyEnabled()) return;
      if (!('Notification' in window)) return;
      if (Notification.permission !== 'granted') return;

      const iconUrl = String($('body').data('faviconUrl') || '/favicon.ico');

      const notification = new Notification(String(item.title || 'HariLog Notification'), {
        body: String(item.message || ''),
        icon: iconUrl,
      });

      notification.onclick = () => {
        window.focus();
        if (item.url && window.HSPA && HUtils.isSameOrigin(item.url)) {
          HSPA.navigate(item.url, true);
        } else if (item.url) {
          window.location.href = item.url;
        }
        notification.close();
      };
    },

    _requestBrowserPermission() {
      if (!this._browserNotifyEnabled()) return;
      if (!('Notification' in window)) return;
      if (Notification.permission !== 'default') return;

      Notification.requestPermission().catch(() => {
        // Ignore permission prompt failures.
      });
    },

    _browserNotifyEnabled() {
      return Number($('body').data('browserNotifyEnabled') || 0) === 1;
    },

    _triggerAttention() {
      const now = Date.now();
      if (now - this._lastAttentionAt < 3000) return;
      this._lastAttentionAt = now;

      try {
        if (navigator && typeof navigator.vibrate === 'function') {
          navigator.vibrate([130, 45, 130]);
        }
      } catch (error) {
        // Ignore unsupported vibration API.
      }

      this._playBeep();
    },

    _playBeep() {
      try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (typeof AudioContextClass !== 'function') return;

        const context = new AudioContextClass();
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = 1120;
        gain.gain.value = 0.03;

        oscillator.connect(gain);
        gain.connect(context.destination);

        const now = context.currentTime;
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.03, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.25);

        oscillator.start(now);
        oscillator.stop(now + 0.26);

        oscillator.onended = () => {
          try {
            context.close();
          } catch (closeError) {
            // Ignore close errors.
          }
        };
      } catch (error) {
        // Ignore autoplay or audio context restrictions.
      }
    },

    _escape(input) {
      return String(input ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },
  };

  /* ── PWA ─────────────────────────────────────────────── */
  const HPWA = {
    deferredPrompt: null,

    init() {
      if (Number($('body').data('pwaEnabled') || 0) !== 1) return;

      const installButton = document.getElementById('h-pwa-install');
      if (installButton) {
        installButton.addEventListener('click', () => this.install());
      }

      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        this.deferredPrompt = event;
        this.toggleInstallButton(true);
      });

      window.addEventListener('appinstalled', () => {
        this.deferredPrompt = null;
        this.toggleInstallButton(false);
        HToast.success('HariLog installed successfully.');
      });

      if ('serviceWorker' in navigator) {
        const swUrl = String($('body').data('swUrl') || '/sw.js');
        window.addEventListener('load', () => {
          navigator.serviceWorker.register(swUrl).catch(() => {
            // Keep app functional even if SW registration fails.
          });
        });
      }
    },

    async install() {
      if (!this.deferredPrompt) {
        HToast.info('Install prompt is not available on this browser/session yet.');
        return;
      }

      this.deferredPrompt.prompt();

      try {
        await this.deferredPrompt.userChoice;
      } catch (error) {
        // Ignore prompt cancel/failure noise.
      }

      this.deferredPrompt = null;
      this.toggleInstallButton(false);
    },

    toggleInstallButton(show) {
      const installButton = document.getElementById('h-pwa-install');
      if (!installButton) return;
      installButton.style.display = show ? 'inline-flex' : 'none';
    },
  };

  /* ── DEBUG STORE ─────────────────────────────────────── */
  const HDebug = {
    key: 'h_client_errors',
    _bound: false,

    init() {
      if (this._bound) return;

      window.addEventListener('error', (event) => {
        this.push({
          type: 'error',
          message: event.message || 'Unhandled error',
          source: event.filename || '',
          line: event.lineno || 0,
          time: new Date().toISOString(),
        });
      });

      window.addEventListener('unhandledrejection', (event) => {
        const reason = event.reason && event.reason.message ? event.reason.message : String(event.reason || 'Promise rejection');
        this.push({
          type: 'promise',
          message: reason,
          source: '',
          line: 0,
          time: new Date().toISOString(),
        });
      });

      this._bound = true;
    },

    push(entry) {
      try {
        const items = this.read();
        items.unshift(entry);
        localStorage.setItem(this.key, JSON.stringify(items.slice(0, 30)));
      } catch (error) {
        // Ignore localStorage failures.
      }
    },

    read() {
      try {
        const raw = localStorage.getItem(this.key);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
      } catch (error) {
        return [];
      }
    },
  };

  /* ── PASSWORD TOGGLE ──────────────────────────────────── */
  $(document).on('click', '.h-pw-toggle', function () {
    const $input = $(this).closest('.h-input-wrap').find('.h-input');
    if (!$input.length) return;

    const nextType = $input.attr('type') === 'password' ? 'text' : 'password';
    $input.attr('type', nextType);
    $(this).find('.eye-on').toggle(nextType === 'text');
    $(this).find('.eye-off').toggle(nextType === 'password');
  });

  /* ── CLOCK ────────────────────────────────────────────── */
  function updateClock() {
    const $clock = $('#h-live-clock,#h-clock');
    if (!$clock.length) return;

    $clock.text(
      new Date().toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    );
  }

  /* ── AJAX BASE CONFIG ─────────────────────────────────── */
  function initAjax() {
    const token = $('meta[name="csrf-token"]').attr('content');
    const headers = {
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (token) {
      headers['X-CSRF-TOKEN'] = token;
    }

    $.ajaxSetup({ headers });
  }

  /* ── UTILITIES ────────────────────────────────────────── */
  const HUtils = {
    formatNPR(numberValue) {
      const parsed = typeof numberValue === 'number' ? numberValue : Number(numberValue) || 0;
      return 'रू ' + parsed.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    },

    htmlToDoc(html) {
      return new DOMParser().parseFromString(html, 'text/html');
    },

    extractContainer(html, selector) {
      const doc = this.htmlToDoc(html);
      return doc.querySelector(selector);
    },

    isSameOrigin(url) {
      try {
        const parsed = new URL(url, window.location.href);
        return parsed.origin === window.location.origin;
      } catch (error) {
        return false;
      }
    },
  };

  /* ── API WRAPPER ──────────────────────────────────────── */
  const HApi = {
    request(settings = {}) {
      const options = $.extend(true, {}, settings);
      const method = String(options.method || options.type || 'GET').toUpperCase();
      const token = $('meta[name="csrf-token"]').attr('content');

      options.headers = $.extend(
        {
          'X-Requested-With': 'XMLHttpRequest',
        },
        options.headers || {}
      );

      if (token) {
        options.headers['X-CSRF-TOKEN'] = token;
      }

      if (
        token &&
        ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) &&
        options.data &&
        !(options.data instanceof window.FormData)
      ) {
        if (typeof options.data === 'string') {
          if (!/(^|&)_token=/.test(options.data)) {
            options.data += (options.data ? '&' : '') + '_token=' + encodeURIComponent(token);
          }
        } else if (typeof options.data === 'object') {
          if (!Object.prototype.hasOwnProperty.call(options.data, '_token')) {
            options.data._token = token;
          }
        }
      }

      return $.ajax(options);
    },

    get(url, data = {}, opts = {}) {
      return this.request($.extend({ url, method: 'GET', data }, opts));
    },

    post(url, data = {}, opts = {}) {
      return this.request($.extend({ url, method: 'POST', data }, opts));
    },

    submitForm($form, opts = {}) {
      const url = $form.attr('action') || window.location.href;
      const method = ($form.attr('method') || 'POST').toUpperCase();
      const hasFile = $form.find('input[type="file"]').length > 0;
      const token = $('meta[name="csrf-token"]').attr('content');

      if (token && method !== 'GET' && !$form.find('input[name="_token"]').length) {
        $form.append(`<input type="hidden" name="_token" value="${token}">`);
      }

      const settings = {
        url,
        method,
        headers: {
          'X-HSPA': '1',
        },
      };

      if (hasFile) {
        settings.data = new FormData($form[0]);
        settings.processData = false;
        settings.contentType = false;
      } else {
        settings.data = $form.serialize();
      }

      return this.request(settings)
        .done((res, status, xhr) => {
          if (typeof opts.success === 'function') opts.success(res, status, xhr);
        })
        .fail((xhr) => {
          if (typeof opts.error === 'function') opts.error(xhr);
        });
    },
  };

  /* ── SPA LAYER ────────────────────────────────────────── */
  const HSPA = {
    container: '#h-spa-content',
    progress: null,

    init() {
      this.createProgress();
      this.bindLinks();
      this.bindForms();
      this.highlightActiveNav(window.location.pathname + window.location.search);

      window.addEventListener('popstate', () => {
        this.load(window.location.pathname + window.location.search);
      });
    },

    createProgress() {
      if (!document.querySelector('.h-spa-progress')) {
        const progress = document.createElement('div');
        progress.className = 'h-spa-progress hide';
        document.body.appendChild(progress);
        this.progress = progress;
      } else {
        this.progress = document.querySelector('.h-spa-progress');
      }
    },

    showProgress() {
      if (!this.progress) this.createProgress();
      this.progress.classList.remove('hide');
      this.progress.style.width = '8%';
      this.progress.offsetWidth;

      setTimeout(() => {
        if (this.progress) this.progress.style.width = '45%';
      }, 100);

      document.documentElement.classList.add('h-spa-loading');
    },

    finishProgress() {
      if (!this.progress) return;

      this.progress.style.width = '100%';
      setTimeout(() => {
        if (!this.progress) return;
        this.progress.classList.add('hide');
        this.progress.style.width = '0%';
      }, 180);

      document.documentElement.classList.remove('h-spa-loading');
    },

    bindLinks() {
      $(document).on('click', 'a[data-spa]', (event) => {
        if (!this.shouldHandleLink(event)) return;

        event.preventDefault();
        const href = $(event.currentTarget).attr('href');
        this.navigate(href, true);
      });
    },

    shouldHandleLink(event) {
      const anchor = event.currentTarget;
      const href = anchor.getAttribute('href');

      if (!href || href.startsWith('#')) return false;
      if (href.startsWith('mailto:') || href.startsWith('tel:')) return false;
      if (anchor.hasAttribute('download')) return false;
      if (anchor.getAttribute('target') === '_blank') return false;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return false;
      if (!HUtils.isSameOrigin(anchor.href)) return false;

      return true;
    },

    bindForms() {
      $(document).on('submit', 'form[data-spa]', (event) => {
        event.preventDefault();

        const $form = $(event.currentTarget);
        const fallbackUrl = window.location.href;
        const $submit = $form.find('button[type="submit"]').first();
        const originalText = $submit.text();

        if ($submit.length) {
          $submit.prop('disabled', true).text($submit.data('busy-text') || 'Working...');
        }

        this.showProgress();
        HCore.emit('hspa:beforeLoad', {
          url: $form.attr('action') || fallbackUrl,
          type: 'form',
        });

        HApi.submitForm($form, {
          success: (res, status, xhr) => {
            this._handleResponse(res, xhr, true, fallbackUrl);
            if ($submit.length) $submit.prop('disabled', false).text(originalText);
          },
          error: (xhr) => {
            this._handleError(xhr);
            if ($submit.length) $submit.prop('disabled', false).text(originalText);
          },
        });
      });
    },

    navigate(url, push = true) {
      if (!url) return;

      this.showProgress();
      HCore.emit('hspa:beforeLoad', { url, type: 'link' });

      return $.ajax({
        url,
        method: 'GET',
        dataType: 'html',
        headers: {
          'X-HSPA': '1',
        },
      })
        .done((html, status, xhr) => {
          this._handleResponse(html, xhr, push, url);
        })
        .fail((xhr) => {
          this.finishProgress();
          HToast.error('Failed to load page. Falling back to full navigation.');
          HCore.emit('hspa:error', { url, status: xhr.status });
          window.location.href = url;
        });
    },

    load(url) {
      return this.navigate(url, false);
    },

    _handleResponse(response, xhr, push, requestUrl = '') {
      const contentType = xhr && xhr.getResponseHeader ? xhr.getResponseHeader('Content-Type') || '' : '';

      if (contentType.includes('application/json') || (typeof response !== 'string' && typeof response === 'object')) {
        try {
          const json = typeof response === 'string' ? JSON.parse(response) : response;

          if (json.redirect) {
            this.navigate(json.redirect);
            return;
          }

          if (json.message) HToast.success(json.message);
          this.finishProgress();
          HCore.emit('hspa:afterLoad', { url: requestUrl || window.location.href, mode: 'json' });
          return;
        } catch (error) {
          HToast.warning('Response parsed with fallback mode.');
          this.finishProgress();
          return;
        }
      }

      if (typeof response === 'string') {
        const doc = HUtils.htmlToDoc(response);
        const currentContainer = document.querySelector(this.container);
        const incomingContainer = doc.querySelector(this.container);

        if (currentContainer && incomingContainer) {
          this._syncCsrfToken(doc);
          this._syncBodyDataset(doc);
          currentContainer.innerHTML = incomingContainer.innerHTML;

          const title = doc.querySelector('title');
          if (title) document.title = title.textContent;

          const resolvedUrl = (xhr && xhr.responseURL) || requestUrl || window.location.href;
          const next = new URL(resolvedUrl, window.location.href);
          const nextPath = next.pathname + next.search;

          this._syncTopbar(doc);
          this._syncDynamicRegions(doc);
          this._emitDocumentToasts(doc);

          if (push !== false) history.pushState({}, '', next.href);
          this.highlightActiveNav(nextPath);
          this._runInlineScripts(currentContainer);
          this._runInlineScripts(document.querySelector('#h-page-scripts'));
          this._rehydrate({
            container: currentContainer,
            url: next.href,
          });

          window.scrollTo({ top: 0, behavior: 'auto' });
          this.finishProgress();
          return;
        }

        this._replaceDocument(response);
        return;
      }

      this.finishProgress();
    },

    _replaceDocument(html) {
      this.finishProgress();
      document.open();
      document.write(html);
      document.close();
    },

    _handleError(xhr) {
      let message = 'Something went wrong.';
      const contentType = xhr.getResponseHeader ? xhr.getResponseHeader('Content-Type') || '' : '';

      if (xhr.status === 419) {
        HToast.warning('Session token expired. Reloading page...');
        setTimeout(() => window.location.reload(), 800);
        this.finishProgress();
        HCore.emit('hspa:error', { status: xhr.status, message: 'CSRF token expired.' });
        return;
      }

      if (contentType.includes('application/json')) {
        try {
          const json = JSON.parse(xhr.responseText || '{}');
          message =
            json.message ||
            (json.errors && Object.values(json.errors)[0] && Object.values(json.errors)[0][0]) ||
            message;
        } catch (error) {
          // keep fallback message
        }
      } else if (typeof xhr.responseText === 'string') {
        const doc = HUtils.htmlToDoc(xhr.responseText);
        const errorEl = doc.querySelector('.h-alert.error, .alert-danger');
        if (errorEl) message = errorEl.textContent.trim().slice(0, 220);
      }

      HToast.error(message);
      this.finishProgress();
      HCore.emit('hspa:error', { status: xhr.status, message });
    },

    _runInlineScripts(container) {
      const scripts = container.querySelectorAll('script');

      scripts.forEach((script) => {
        if (script.src) {
          if (document.querySelector('script[src="' + script.src + '"]')) return;
          const next = document.createElement('script');
          next.src = script.src;
          next.async = false;
          document.body.appendChild(next);
          return;
        }

        if (script.textContent && script.textContent.trim()) {
          try {
            window.eval(script.textContent);
          } catch (error) {
            console.error('Inline script error:', error);
          }
        }
      });
    },

    _syncTopbar(doc) {
      if (!doc) return;

      const nextPageTitle = doc.querySelector('#h-page-title');
      const currentPageTitle = document.querySelector('#h-page-title');
      if (nextPageTitle && currentPageTitle) {
        currentPageTitle.textContent = nextPageTitle.textContent || '';
      }

      const nextExtra = doc.querySelector('#h-topbar-extra');
      const currentExtra = document.querySelector('#h-topbar-extra');
      if (nextExtra && currentExtra) {
        currentExtra.innerHTML = nextExtra.innerHTML;
      }
    },

    _syncDynamicRegions(doc) {
      if (!doc) return;
      this._syncRegion(doc, '#h-page-flash');
      this._syncRegion(doc, '#h-page-modals');
      this._syncRegion(doc, '#h-page-fab');
      this._syncRegion(doc, '#h-page-scripts');
      this._syncRegion(doc, '#h-sidebar-brand');
      this._syncFavicon(doc);
      this._syncThemeColor(doc);
    },

    _syncCsrfToken(doc) {
      if (!doc) return;

      const incomingMeta = doc.querySelector('meta[name="csrf-token"]');
      if (!incomingMeta) return;

      const nextToken = (incomingMeta.getAttribute('content') || '').trim();
      if (!nextToken) return;

      const currentMeta = document.querySelector('meta[name="csrf-token"]');
      if (currentMeta) {
        currentMeta.setAttribute('content', nextToken);
      }

      $.ajaxSetup({
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': nextToken,
        },
      });
    },

    _syncBodyDataset(doc) {
      if (!doc || !doc.body || !document.body) return;

      const keys = [
        'notificationsFeedUrl',
        'notificationReadUrlTemplate',
        'notificationsPollSeconds',
        'browserNotifyEnabled',
        'pwaEnabled',
        'swUrl',
        'iconSpriteUrl',
        'faviconUrl',
        'themeColor',
      ];

      keys.forEach((key) => {
        const nextValue = doc.body.dataset ? doc.body.dataset[key] : null;
        if (typeof nextValue === 'undefined' || nextValue === null) return;
        document.body.dataset[key] = nextValue;
      });
    },

    _syncRegion(doc, selector) {
      const incoming = doc.querySelector(selector);
      const current = document.querySelector(selector);
      if (!incoming || !current) return;
      current.innerHTML = incoming.innerHTML;
    },

    _syncFavicon(doc) {
      if (!doc) return;

      const incoming = doc.querySelector('link[rel="icon"]');
      if (!incoming) return;

      const href = String(incoming.getAttribute('href') || '').trim();
      if (!href) return;

      let current = document.querySelector('link[rel="icon"]');
      if (!current) {
        current = document.createElement('link');
        current.setAttribute('rel', 'icon');
        document.head.appendChild(current);
      }

      current.setAttribute('href', href);
    },

    _syncThemeColor(doc) {
      if (!doc) return;

      const incomingMeta = doc.querySelector('meta[name="theme-color"]');
      if (!incomingMeta) return;

      const color = String(incomingMeta.getAttribute('content') || '').trim();
      if (!/^#[0-9a-fA-F]{6}$/.test(color)) return;

      let currentMeta = document.querySelector('meta[name="theme-color"]');
      if (!currentMeta) {
        currentMeta = document.createElement('meta');
        currentMeta.setAttribute('name', 'theme-color');
        document.head.appendChild(currentMeta);
      }

      currentMeta.setAttribute('content', color);
      document.documentElement.style.setProperty('--gold', color);
      document.documentElement.style.setProperty('--gold-dk', color);
      document.body.dataset.themeColor = color;
    },

    _emitDocumentToasts(doc) {
      if (!doc) return;

      const success = doc.querySelector('.h-alert.success, .alert-success');
      if (success && success.textContent) {
        HToast.success(success.textContent.trim().slice(0, 220));
      }

      const error = doc.querySelector('.h-alert.error, .alert-danger');
      if (error && error.textContent) {
        HToast.error(error.textContent.trim().slice(0, 220));
      }
    },

    _rehydrate(payload = {}) {
      updateClock();
      HSidebar.close();
      HNotify.close();
      HCore.emit('hspa:afterSwap', payload);
      HCore.emit('hspa:afterLoad', payload);
    },

    highlightActiveNav(pathnameWithQuery) {
      if (!pathnameWithQuery) return;

      let currentUrl;
      try {
        currentUrl = new URL(pathnameWithQuery, window.location.origin);
      } catch (error) {
        currentUrl = new URL(window.location.href);
      }

      const currentPath = currentUrl.pathname.replace(/\/$/, '') || '/';
      const currentQuery = currentUrl.searchParams;

      document.querySelectorAll('.h-nav-item, .h-nav-sub-item').forEach((item) => {
        const href = item.getAttribute('href');
        if (!href || href === '#') return;

        let navPath = '';
        try {
          navPath = new URL(href, window.location.origin).pathname.replace(/\/$/, '') || '/';
        } catch (error) {
          return;
        }

        let isActive = navPath === currentPath;
        const matchQueryRaw = String(item.getAttribute('data-match-query') || '').trim();

        if (isActive && matchQueryRaw !== '') {
          const queryPairs = matchQueryRaw.split('&').map((pair) => pair.trim()).filter(Boolean);
          isActive = queryPairs.every((pair) => {
            const [key, value = ''] = pair.split('=');
            if (!key) return true;
            return currentQuery.get(key) === value;
          });
        }

        item.classList.toggle('active', isActive);
      });

      if (window.HSidebar && typeof window.HSidebar.syncGroups === 'function') {
        window.HSidebar.syncGroups();
      }
    },
  };

  /* ── INIT ─────────────────────────────────────────────── */
  $(function () {
    HTheme.init();
    HSidebar.init();
    HToast.init();
    HModal.init();
    HNotify.init();
    HPWA.init();
    HDebug.init();
    initAjax();

    updateClock();
    setInterval(updateClock, 30000);

    window.HCore = HCore;
    window.HTheme = HTheme;
    window.HSidebar = HSidebar;
    window.HToast = HToast;
    window.HModal = HModal;
    window.HNotify = HNotify;
    window.HPWA = HPWA;
    window.HDebug = HDebug;
    window.HApi = HApi;
    window.HSPA = HSPA;
    window.HUtils = HUtils;

    HSPA.init();
  });
})(jQuery, window, document);
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
      HDataTable.init(target);
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

      const buttons = Array.from(container.querySelectorAll('[data-tab-btn]')).filter((button) => {
        const owner = button.closest('[data-ui-tabs]');
        return owner === container;
      });
      const panels = Array.from(container.querySelectorAll('[data-tab-panel]')).filter((panel) => {
        const owner = panel.closest('[data-ui-tabs]');
        return owner === container;
      });
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
      const dropdownParent = $($select.data('dropdownParent') || document.body);

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
        dropdownParent,
        dropdownCssClass: 'h-s2-dropdown',
        minimumInputLength: minInput,
        escapeMarkup(markup) {
          return markup;
        },
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
     HDataTable: DataTables bridge
  ---------------------------*/
  const HDataTable = {
    selector: 'table[data-h-datatable]',

    init(root) {
      if (typeof $.fn.DataTable !== 'function') return;

      const $root = root && root.querySelectorAll ? $(root) : $(document);
      const $targets = $root.is(this.selector) ? $root : $root.find(this.selector);
      $targets.each((_, table) => this.setup($(table)));
    },

    setup($table) {
      if (!$table || !$table.length) return;

      const tableEl = $table[0];
      const endpoint = String($table.data('endpoint') || '').trim();
      const columns = this._columns($table);
      const pageLength = Number($table.data('pageLength') || 10);
      const orderCol = Number($table.data('orderCol') || 0);
      const orderDir = String($table.data('orderDir') || 'desc').toLowerCase() === 'asc' ? 'asc' : 'desc';
      const lengthMenu = this._parseLengthMenu(String($table.data('lengthMenu') || '').trim());

      if ($.fn.DataTable.isDataTable(tableEl)) {
        const api = $table.DataTable();
        if (endpoint && api.ajax) {
          api.ajax.url(endpoint).load(null, false);
        }
        if (api.columns && typeof api.columns.adjust === 'function') {
          api.columns.adjust();
        }
        return;
      }

      const options = {
        processing: Boolean(endpoint),
        serverSide: Boolean(endpoint),
        searching: true,
        ordering: true,
        lengthChange: true,
        autoWidth: false,
        responsive: typeof $.fn.dataTable.Responsive === 'function',
        stateSave: true,
        deferRender: true,
        searchDelay: 280,
        pageLength: Number.isFinite(pageLength) ? Math.max(10, Math.min(pageLength, 100)) : 10,
        lengthMenu,
        dom: "<'row align-items-center mb-2'<'col-md-6'l><'col-md-6'f>>" +
          "<'row'<'col-12'tr>>" +
          "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        order: [[Number.isFinite(orderCol) ? Math.max(0, orderCol) : 0, orderDir]],
        language: {
          search: '',
          searchPlaceholder: 'Search...',
          lengthMenu: 'Show _MENU_ rows',
          emptyTable: 'No records found.',
        },
      };

      if (endpoint) {
        options.ajax = {
          url: endpoint,
          type: 'GET',
        };

        if (columns.length) {
          options.columns = columns;
        }
      }

      $table.DataTable(options);
      $table.data('hDatatableReady', true);
    },

    _columns($table) {
      const columns = [];

      $table.find('thead th[data-col]').each((_, th) => {
        const key = String($(th).data('col') || '').trim();
        if (!key) return;
        columns.push({ data: key, name: key });
      });

      return columns;
    },

    _parseLengthMenu(raw) {
      if (!raw) {
        return [
          [10, 20, 50, 100],
          [10, 20, 50, 100],
        ];
      }

      const items = raw
        .split(',')
        .map((item) => Number(item.trim()))
        .filter((item) => Number.isFinite(item) && item > 0);

      if (!items.length) {
        return [
          [10, 20, 50, 100],
          [10, 20, 50, 100],
        ];
      }

      const unique = Array.from(new Set(items));
      return [unique, unique];
    },
  };

  /* --------------------------
     HEditor: rich text editor
  ---------------------------*/
  const HEditor = {
    selector: '[data-editor], .h-editor',
    toolbar: [
      { cmd: 'formatBlock', arg: 'p', label: 'P', title: 'Paragraph' },
      { cmd: 'formatBlock', arg: 'h2', label: 'H2', title: 'Heading 2' },
      { cmd: 'formatBlock', arg: 'h3', label: 'H3', title: 'Heading 3' },
      { cmd: 'bold', label: 'B', title: 'Bold' },
      { cmd: 'italic', label: 'I', title: 'Italic' },
      { cmd: 'underline', label: 'U', title: 'Underline' },
      { cmd: 'strikeThrough', label: 'S', title: 'Strikethrough' },
      { cmd: 'insertUnorderedList', label: '• List', title: 'Bulleted list' },
      { cmd: 'insertOrderedList', label: '1. List', title: 'Numbered list' },
      { cmd: 'formatBlock', arg: 'blockquote', label: 'Quote', title: 'Blockquote' },
      { cmd: 'formatBlock', arg: 'pre', label: '</>', title: 'Code block' },
      { cmd: 'createLink', label: 'Link', title: 'Insert link' },
      { cmd: 'unlink', label: 'Unlink', title: 'Remove link' },
      { cmd: 'insertImage', label: 'Image', title: 'Insert image by URL' },
      { cmd: 'undo', label: 'Undo', title: 'Undo' },
      { cmd: 'redo', label: 'Redo', title: 'Redo' },
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
        this._execCommand(editorEl, cmd, arg);
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

      editorEl.addEventListener('keydown', (event) => {
        if (!(event.metaKey || event.ctrlKey)) return;

        const key = String(event.key || '').toLowerCase();
        if (!['b', 'i', 'u', 'k', 'z', 'y'].includes(key)) return;
        event.preventDefault();

        if (key === 'b') this._execCommand(editorEl, 'bold');
        if (key === 'i') this._execCommand(editorEl, 'italic');
        if (key === 'u') this._execCommand(editorEl, 'underline');
        if (key === 'k') this._execCommand(editorEl, 'createLink');
        if (key === 'z') this._execCommand(editorEl, 'undo');
        if (key === 'y') this._execCommand(editorEl, 'redo');
      });
    },

    _execCommand(editorEl, cmd, arg = null) {
      if (!editorEl || !cmd) return;

      if (cmd === 'createLink') {
        const href = prompt('Enter URL');
        if (!href) return;
        document.execCommand('createLink', false, href);
      } else if (cmd === 'insertImage') {
        const src = prompt('Enter image URL');
        if (!src) return;
        document.execCommand('insertImage', false, src);
      } else if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, arg || 'p');
      } else {
        document.execCommand(cmd, false, arg || null);
      }

      editorEl.dispatchEvent(new Event('input', { bubbles: true }));
      this._syncHiddenField(editorEl, editorEl.dataset.editorName || editorEl.getAttribute('name') || '');
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
      const spriteUrl = this._spriteUrl();

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
        use.setAttribute('href', spriteUrl + '#' + name);
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', spriteUrl + '#' + name);
        svg.appendChild(use);

        node.dataset.iconReady = '1';
        node.replaceWith(svg);
      });
    },

    svg(name, className, size) {
      const cls = className || 'h-icon';
      const sz = size || 16;
      const spriteUrl = this._spriteUrl();
      return `<svg class="${cls}" width="${sz}" height="${sz}" viewBox="0 0 24 24" aria-hidden="true"><use href="${spriteUrl}#${name}" xlink:href="${spriteUrl}#${name}"></use></svg>`;
    },

    _spriteUrl() {
      const body = document.body;
      const fromData = body && body.dataset ? body.dataset.iconSpriteUrl : '';
      return String(fromData || '/icons/icons.svg');
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
    window.HDataTable = HDataTable;
    window.HEditor = HEditor;
    window.HIcons = HIcons;
    window.HSvgPie = HSvgPie;
  });

  document.addEventListener('hspa:afterSwap', function (event) {
    const root = event.detail && event.detail.container ? event.detail.container : document;
    HPlugins.init(root);
  });
})(window, document, window.jQuery);
