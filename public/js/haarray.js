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
    init() {
      $(document).on('click', '.h-menu-toggle', () => this.toggle());
      $(document).on('click', '.h-sidebar-overlay', () => this.close());
      $(document).on('click', '.h-nav-item', () => {
        if ($(window).width() <= 768) this.close();
      });
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
  };

  /* ── TOAST ────────────────────────────────────────────── */
  const HToast = {
    init() {
      if (!$('#h-toasts').length) $('body').append('<div id="h-toasts"></div>');
    },

    show(message, type = 'info', duration = 3800) {
      const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
      const colors = {
        success: 'var(--green)',
        error: 'var(--red)',
        warning: 'var(--gold)',
        info: 'var(--teal)',
      };

      const safeMessage = String(message || '').trim() || 'Done';
      const tone = icons[type] ? type : 'info';
      const $toast = $(`
        <div class="h-toast" role="status" aria-live="polite">
          <span style="color:${colors[tone]};font-size:15px">${icons[tone]}</span>
          <span>${safeMessage}</span>
        </div>
      `);

      $('#h-toasts').append($toast);
      requestAnimationFrame(() => $toast.addClass('show'));

      setTimeout(() => {
        $toast.removeClass('show');
        setTimeout(() => $toast.remove(), 300);
      }, duration);
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
      this._pollTimer = window.setInterval(() => this.refresh(true), 45000);
    },

    open() {
      $('#h-notif-tray').addClass('show').attr('aria-hidden', 'false');
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

    _escape(input) {
      return String(input ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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
    if (token) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
    }
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
      return $.ajax(settings);
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
      this.highlightActiveNav(window.location.pathname);

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
        const $submit = $form.find('button[type="submit"]').first();
        const originalText = $submit.text();

        if ($submit.length) {
          $submit.prop('disabled', true).text($submit.data('busy-text') || 'Working...');
        }

        this.showProgress();
        HCore.emit('hspa:beforeLoad', {
          url: $form.attr('action') || window.location.pathname,
          type: 'form',
        });

        HApi.submitForm($form, {
          success: (res, status, xhr) => {
            this._handleResponse(res, xhr, true, $form.attr('action') || window.location.href);
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
          currentContainer.innerHTML = incomingContainer.innerHTML;

          const title = doc.querySelector('title');
          if (title) document.title = title.textContent;

          const resolvedUrl = (xhr && xhr.responseURL) || requestUrl || window.location.href;
          const next = new URL(resolvedUrl, window.location.href);
          const nextPath = next.pathname;

          this._syncTopbar(doc);
          this._emitDocumentToasts(doc);

          if (push !== false) history.pushState({}, '', next.href);
          this.highlightActiveNav(nextPath);
          this._runInlineScripts(currentContainer);
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

    highlightActiveNav(pathname) {
      if (!pathname) return;

      const currentPath = pathname.replace(/\/$/, '') || '/';
      document.querySelectorAll('.h-nav-item').forEach((item) => {
        const href = item.getAttribute('href');
        if (!href || href === '#') return;

        let navPath = '';
        try {
          navPath = new URL(href, window.location.origin).pathname.replace(/\/$/, '') || '/';
        } catch (error) {
          return;
        }

        item.classList.toggle('active', navPath === currentPath);
      });
    },
  };

  /* ── INIT ─────────────────────────────────────────────── */
  $(function () {
    HTheme.init();
    HSidebar.init();
    HToast.init();
    HModal.init();
    HNotify.init();
    initAjax();

    updateClock();
    setInterval(updateClock, 30000);

    window.HCore = HCore;
    window.HTheme = HTheme;
    window.HSidebar = HSidebar;
    window.HToast = HToast;
    window.HModal = HModal;
    window.HNotify = HNotify;
    window.HApi = HApi;
    window.HSPA = HSPA;
    window.HUtils = HUtils;

    HSPA.init();
  });
})(jQuery, window, document);
