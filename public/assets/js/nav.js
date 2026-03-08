'use strict';

/**
 * Navigation JavaScript — hamburger menu, unified mobile menu, user-actions dropdown,
 * and <dialog> modal system.
 *
 * Progressive enhancement: without this script, nav links navigate normally,
 * dropdown items remain visible, and [data-modal] links go to full pages.
 *
 * Mobile (<=700px):
 *   The hamburger merges both #top-links and #user-actions into a single
 *   slide-down panel. Auth items (greeting, Dashboard, Notifications, Logout
 *   —or— Login, Sign Up) are cloned into #top-links by JS, separated by a
 *   visual divider. The ellipsis toggle and its floating dropdown are hidden
 *   via CSS. The notification bell remains visible in the nav bar for quick
 *   access. Items are added/removed when the viewport crosses the 700px
 *   breakpoint (e.g. device rotation).
 *
 * Desktop (>700px):
 *   Hamburger is hidden. #top-links and #user-actions render side by side.
 *   The ellipsis toggle opens a floating dropdown menu for account actions.
 *
 * @see src/Views/partials/nav.php   — nav HTML structure
 * @see src/Views/layouts/main.php   — <dialog> modal partials
 * @see public/assets/css/responsive.css — unified mobile menu CSS
 */

(() => {

  // ─── Hamburger Mobile Menu ──────────────────────────────────────────

  const initHamburger = () => {
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('top-links');
    if (!toggle || !menu) return;

    toggle.closest('nav').classList.add('js-nav');

    const authSection = document.getElementById('user-actions');
    const authMenu = document.getElementById('user-actions-menu');
    let releaseTrap = null;

    const open = () => {
      menu.classList.add('open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('menu-open');

      if (window.NT) releaseTrap = NT.focus.trap(menu);

      const firstLink = menu.querySelector('a');
      firstLink?.focus();
    };

    const close = (returnFocus = true) => {
      menu.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('menu-open');

      if (releaseTrap) {
        releaseTrap();
        releaseTrap = null;
      }

      if (returnFocus) toggle.focus();
    };

    const isOpen = () => menu.classList.contains('open');

    // ── Unified mobile menu: merge auth actions into hamburger ──

    const mobile = window.matchMedia('(max-width: 700px)');

    const buildMobileAuthItems = () => {
      if (menu.querySelector('[data-mobile-auth]')) return;

      // Helper — create <li data-mobile-auth>, append to #top-links
      const addLi = () => {
        const li = document.createElement('li');
        li.dataset.mobileAuth = '';
        menu.appendChild(li);
        return li;
      };

      // Visual divider between navigation links and auth section
      const sep = addLi();
      sep.dataset.separator = '';
      sep.setAttribute('aria-hidden', 'true');

      if (!authSection) return;

      // Greeting — logged-in users (extracted from the toggle button)
      const toggleBtn = document.getElementById('user-actions-toggle');
      if (toggleBtn) {
        const span = document.createElement('span');
        for (const node of toggleBtn.childNodes) {
          // Skip the chevron icon — not needed in the mobile menu
          if (node.nodeType === Node.ELEMENT_NODE &&
              node.classList?.contains('fa-chevron-down')) continue;
          span.appendChild(node.cloneNode(true));
        }
        addLi().appendChild(span);
      }

      // Login link — logged-out users
      const loginLink = authSection.querySelector(':scope > a[role="button"]');
      if (loginLink) {
        const a = loginLink.cloneNode(true);
        a.removeAttribute('role');
        addLi().appendChild(a);
      }

      // Sign Up link — logged-out users (direct child, not in a dropdown)
      const signUpLink = authSection.querySelector(':scope > a[href="/register"]');
      if (signUpLink) {
        addLi().appendChild(signUpLink.cloneNode(true));
      }

      // Dropdown menu items (Dashboard / Admin / Logout)
      if (authMenu) {
        for (const item of [...authMenu.children]) {
          const clone = item.cloneNode(true);
          clone.dataset.mobileAuth = '';
          clone.removeAttribute('role');
          menu.appendChild(clone);
        }
      }

      // Notification link — logged-in users (placed before Logout)
      const bellLink = authSection.querySelector('#bell-wrapper > a[href="/notifications"]');
      if (bellLink) {
        const a = document.createElement('a');
        a.href = '/notifications';

        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-bell';
        icon.setAttribute('aria-hidden', 'true');
        a.appendChild(icon);

        const badge = bellLink.querySelector('span');
        const count = badge?.textContent.trim();
        a.append(count ? ` Notifications (${count})` : ' Notifications');

        const li = document.createElement('li');
        li.dataset.mobileAuth = '';
        li.appendChild(a);

        // Insert before Logout (last auth item) so order matches the plan
        const allAuth = menu.querySelectorAll('[data-mobile-auth]');
        const last = allAuth[allAuth.length - 1];
        menu.insertBefore(li, last);
      }
    };

    const removeMobileAuthItems = () => {
      for (const el of menu.querySelectorAll('[data-mobile-auth]')) {
        el.remove();
      }
    };

    // Build on init if already mobile; sync on viewport change
    if (mobile.matches) buildMobileAuthItems();

    mobile.addEventListener('change', (e) => {
      if (e.matches) {
        buildMobileAuthItems();
      } else {
        removeMobileAuthItems();
        if (isOpen()) close(false);
      }
    });

    // ── Event listeners ──

    toggle.addEventListener('click', () => {
      isOpen() ? close() : open();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen()) {
        close();
      }
    });

    document.addEventListener('click', (e) => {
      if (isOpen() && !menu.contains(e.target) && !toggle.contains(e.target)) {
        close(false);
      }
    });
  };

  // ─── User Actions Dropdown ─────────────────────────────────────────

  const initDropdown = () => {
    const toggle = document.getElementById('user-actions-toggle');
    const menu = document.getElementById('user-actions-menu');
    if (!toggle || !menu) return;

    menu.hidden = true;

    const getItems = () => [
      ...menu.querySelectorAll('[role="menuitem"] a, [role="menuitem"] button')
    ];

    const closeBellMenu = () => {
      const bd = document.getElementById('bell-dropdown');
      const bl = document.querySelector('#bell-wrapper > a[href="/notifications"]');
      if (bd && !bd.hidden) {
        bd.hidden = true;
        bl?.setAttribute('aria-expanded', 'false');
      }
    };

    const open = () => {
      closeBellMenu();
      menu.hidden = false;
      toggle.setAttribute('aria-expanded', 'true');

      const items = getItems();
      items[0]?.focus();
    };

    const close = (returnFocus = true) => {
      menu.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');

      if (returnFocus) toggle.focus();
    };

    const isOpen = () => !menu.hidden;

    toggle.addEventListener('click', () => {
      isOpen() ? close() : open();
    });

    // Keyboard navigation within the dropdown menu
    menu.addEventListener('keydown', (e) => {
      const items = getItems();
      const index = items.indexOf(document.activeElement);

      switch (e.key) {
        case 'ArrowDown': {
          e.preventDefault();
          const next = items[(index + 1) % items.length];
          next?.focus();
          break;
        }
        case 'ArrowUp': {
          e.preventDefault();
          const prev = items[(index - 1 + items.length) % items.length];
          prev?.focus();
          break;
        }
        case 'Home': {
          e.preventDefault();
          items[0]?.focus();
          break;
        }
        case 'End': {
          e.preventDefault();
          items[items.length - 1]?.focus();
          break;
        }
        case 'Escape': {
          close();
          break;
        }
        case 'Tab': {
          close(false);
          break;
        }
      }
    });

    // Click outside closes the dropdown
    document.addEventListener('click', (e) => {
      if (isOpen() && !menu.contains(e.target) && !toggle.contains(e.target)) {
        close(false);
      }
    });
  };

  // ─── Dialog Modal System ────────────────────────────────────────────

  const initModals = () => {
    let activeTrigger = null;

    // Open: event delegation on [data-modal] links
    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-modal]');
      if (!trigger) return;

      const name = trigger.dataset.modal;
      const dialog = document.getElementById(`modal-${name}`);
      if (!dialog || !(dialog instanceof HTMLDialogElement)) return;

      e.preventDefault();

      // Only one modal open at a time
      const current = document.querySelector('dialog[open]');
      if (current && current !== dialog) {
        current.close();
      }

      activeTrigger = trigger;
      dialog.showModal();
      document.body.classList.add('modal-active');

      // Focus the close button inside the dialog header
      const closeBtn = dialog.querySelector('header button');
      closeBtn?.focus();

      document.dispatchEvent(
        new CustomEvent('modal:open', { detail: { name } })
      );
    });

    // Close button: event delegation on close buttons inside dialog headers
    document.addEventListener('click', (e) => {
      const closeBtn = e.target.closest('dialog header button');
      if (!closeBtn) return;

      const dialog = closeBtn.closest('dialog');
      dialog?.close();
    });

    // Backdrop click: clicks outside the dialog's visible content area
    document.addEventListener('click', (e) => {
      if (!(e.target instanceof HTMLDialogElement) || !e.target.open) return;

      const rect = e.target.getBoundingClientRect();
      const clickedInside =
        e.clientX >= rect.left &&
        e.clientX <= rect.right &&
        e.clientY >= rect.top &&
        e.clientY <= rect.bottom;

      if (!clickedInside) {
        e.target.close();
      }
    });

    // Cleanup on close — fires for Escape (native), .close(), and form method="dialog".
    document.addEventListener(
      'close',
      (e) => {
        if (!(e.target instanceof HTMLDialogElement)) return;

        document.body.classList.remove('modal-active');

        const name = e.target.id?.replace('modal-', '');
        document.dispatchEvent(
          new CustomEvent('modal:close', { detail: { name } })
        );

        if (activeTrigger) {
          activeTrigger.focus();
          activeTrigger = null;
        }
      },
      true // capture phase — close event does not bubble
    );
  };

  // ─── Notification Badge Polling ───────────────────────────────────

  const initBadgePolling = () => {
    const bellLink = document.querySelector('#bell-wrapper > a[href="/notifications"]');
    if (!bellLink || !window.NT) return;

    const BASE_INTERVAL = 60_000;
    const MAX_INTERVAL = 300_000;
    let interval = BASE_INTERVAL;
    let consecutiveErrors = 0;
    let timerId = null;

    const updateBadge = (count) => {
      let badge = bellLink.querySelector('span');

      if (count > 0) {
        if (!badge) {
          badge = document.createElement('span');
          bellLink.appendChild(badge);
        }
        badge.textContent = count;
        bellLink.setAttribute('aria-label', `Notifications (${count} unread)`);
      } else {
        badge?.remove();
        bellLink.setAttribute('aria-label', 'Notifications');
      }

      // Update mobile menu clone if it exists
      const mobileBell = document.querySelector('[data-mobile-auth] a[href="/notifications"]');
      if (mobileBell) {
        const icon = mobileBell.querySelector('i');
        mobileBell.textContent = '';
        if (icon) mobileBell.appendChild(icon);
        mobileBell.append(count > 0 ? ` Notifications (${count})` : ' Notifications');
      }
    };

    const poll = async () => {
      try {
        const response = await NT.fetch('/notifications/unread-count');
        if (!response.ok) throw new Error(response.status);

        const data = await response.json();

        if (data.success) {
          updateBadge(data.unread);
          consecutiveErrors = 0;
          interval = BASE_INTERVAL;
        }
      } catch {
        consecutiveErrors++;
        interval = Math.min(BASE_INTERVAL * 2 ** consecutiveErrors, MAX_INTERVAL);
      }

      timerId = setTimeout(poll, interval);
    };

    // Pause when tab is hidden, resume when visible
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        clearTimeout(timerId);
        timerId = null;
      } else if (!timerId) {
        poll();
      }
    });

    timerId = setTimeout(poll, BASE_INTERVAL);
  };

  // ─── Bell Dropdown ────────────────────────────────────────────────

  const initBellDropdown = () => {
    const wrapper = document.getElementById('bell-wrapper');
    const bellLink = wrapper?.querySelector('a[href="/notifications"]');
    const dropdown = document.getElementById('bell-dropdown');
    if (!wrapper || !bellLink || !dropdown || !window.NT) return;

    const list = dropdown.querySelector('ul');
    const emptyMsg = dropdown.querySelector('p');
    let loaded = false;
    let loading = false;

    const TYPE_ICONS = {
      request: 'fa-hand',
      approval: 'fa-circle-check',
      denial: 'fa-circle-xmark',
      due: 'fa-clock',
      return: 'fa-rotate-left',
      rating: 'fa-star',
    };

    const relativeTime = (hours) => {
      if (hours < 1) return 'Just now';
      if (hours === 1) return '1 hour ago';
      if (hours < 24) return `${hours} hours ago`;
      const days = Math.floor(hours / 24);
      if (days === 1) return 'Yesterday';
      if (days < 7) return `${days} days ago`;
      return `${Math.floor(days / 7)}w ago`;
    };

    const renderItems = (items) => {
      list.innerHTML = '';

      for (const item of items) {
        const li = document.createElement('li');

        const a = document.createElement('a');
        a.href = item.link;
        a.setAttribute('role', 'menuitem');

        const iconSpan = document.createElement('span');
        const icon = document.createElement('i');
        icon.className = `fa-solid ${TYPE_ICONS[item.type] || 'fa-bell'}`;
        icon.setAttribute('aria-hidden', 'true');
        iconSpan.appendChild(icon);

        const textSpan = document.createElement('span');
        const strong = document.createElement('strong');
        strong.textContent = item.title;
        const em = document.createElement('em');
        em.textContent = relativeTime(item.hoursAgo);
        textSpan.append(strong, em);

        a.append(iconSpan, textSpan);
        li.appendChild(a);
        list.appendChild(li);
      }
    };

    const updateContent = (items) => {
      if (items.length > 0) {
        renderItems(items);
        list.hidden = false;
        emptyMsg.hidden = true;
      } else {
        list.innerHTML = '';
        list.hidden = true;
        emptyMsg.hidden = false;
      }
    };

    const fetchPreview = async () => {
      if (loading) return;
      loading = true;

      try {
        const res = await NT.fetch('/notifications/preview');
        if (!res.ok) throw new Error(res.status);

        const data = await res.json();

        if (data.success) {
          updateContent(data.items);
          loaded = true;
          announceBadge(data.unread);
        }
      } catch {
        loaded = false;
        updateContent([]);
      } finally {
        loading = false;
      }
    };

    const isOpen = () => !dropdown.hidden;

    const closeUserMenu = () => {
      const um = document.getElementById('user-actions-menu');
      const ut = document.getElementById('user-actions-toggle');
      if (um && !um.hidden) {
        um.hidden = true;
        ut?.setAttribute('aria-expanded', 'false');
      }
    };

    const open = () => {
      closeUserMenu();
      dropdown.hidden = false;
      bellLink.setAttribute('aria-expanded', 'true');
      fetchPreview();

      requestAnimationFrame(() => {
        const firstLink = list.querySelector('a')
          || dropdown.querySelector(':scope > a');
        firstLink?.focus();
      });
    };

    const close = (returnFocus = true) => {
      dropdown.hidden = true;
      bellLink.setAttribute('aria-expanded', 'false');
      if (returnFocus) bellLink.focus();
    };

    bellLink.addEventListener('click', (e) => {
      e.preventDefault();
      isOpen() ? close() : open();
    });

    document.addEventListener('click', (e) => {
      if (isOpen() && !wrapper.contains(e.target)) {
        close(false);
      }
    });

    // Keyboard navigation
    dropdown.addEventListener('keydown', (e) => {
      const items = [...dropdown.querySelectorAll('a')];
      const index = items.indexOf(document.activeElement);

      switch (e.key) {
        case 'ArrowDown': {
          e.preventDefault();
          const next = items[(index + 1) % items.length];
          next?.focus();
          break;
        }
        case 'ArrowUp': {
          e.preventDefault();
          const prev = items[(index - 1 + items.length) % items.length];
          prev?.focus();
          break;
        }
        case 'Home': {
          e.preventDefault();
          items[0]?.focus();
          break;
        }
        case 'End': {
          e.preventDefault();
          items[items.length - 1]?.focus();
          break;
        }
        case 'Escape': {
          close();
          break;
        }
        case 'Tab': {
          close(false);
          break;
        }
      }
    });

    let lastAnnouncedCount = -1;

    const announceBadge = (count) => {
      if (count === lastAnnouncedCount || !window.NT) return;
      lastAnnouncedCount = count;

      NT.focus.announce(
        count > 0
          ? `${count} unread notification${count !== 1 ? 's' : ''}`
          : 'No unread notifications'
      );
    };

    // Re-fetch when badge polling updates the count
    const observer = new MutationObserver(() => {
      if (isOpen()) fetchPreview();
    });
    observer.observe(bellLink, { childList: true, subtree: true, characterData: true });
  };

  // ─── Keyboard Shortcut Overlay ──────────────────────────────────

  const initShortcutOverlay = () => {
    const shortcuts = [
      ['?', 'Show this help'],
      ['/', 'Focus search'],
      ['g then h', 'Go to Home'],
      ['g then d', 'Go to Dashboard'],
      ['g then t', 'Go to Tools'],
      ['g then n', 'Go to Notifications'],
    ];

    let dialog = null;
    let pendingG = false;
    let gTimer = null;

    const isTyping = () => {
      const el = document.activeElement;
      if (!el) return false;
      const tag = el.tagName;
      return tag === 'INPUT' || tag === 'TEXTAREA' || el.isContentEditable;
    };

    const buildDialog = () => {
      dialog = document.createElement('dialog');
      dialog.id = 'keyboard-shortcuts';
      dialog.setAttribute('aria-label', 'Keyboard shortcuts');

      const header = document.createElement('header');
      const h2 = document.createElement('h2');
      h2.textContent = 'Keyboard Shortcuts';
      const closeBtn = document.createElement('button');
      closeBtn.type = 'button';
      closeBtn.setAttribute('aria-label', 'Close');
      const closeIcon = document.createElement('i');
      closeIcon.className = 'fa-solid fa-xmark';
      closeIcon.setAttribute('aria-hidden', 'true');
      closeBtn.appendChild(closeIcon);
      closeBtn.addEventListener('click', () => dialog.close());
      header.append(h2, closeBtn);

      const dl = document.createElement('dl');
      dl.className = 'shortcut-list';

      for (const [key, desc] of shortcuts) {
        const dt = document.createElement('dt');
        for (const part of key.split(' then ')) {
          const kbd = document.createElement('kbd');
          kbd.textContent = part;
          dt.appendChild(kbd);
          dt.append(' ');
        }
        const dd = document.createElement('dd');
        dd.textContent = desc;
        dl.append(dt, dd);
      }

      dialog.append(header, dl);
      document.body.appendChild(dialog);
      return dialog;
    };

    document.addEventListener('keydown', (e) => {
      if (isTyping() || e.ctrlKey || e.metaKey || e.altKey) return;

      // Handle "g then X" chord
      if (pendingG) {
        pendingG = false;
        clearTimeout(gTimer);

        const routes = { h: '/', d: '/dashboard', t: '/tools', n: '/notifications' };
        const dest = routes[e.key];
        if (dest && window.location.pathname !== dest) {
          window.location.href = dest;
        }
        return;
      }

      if (e.key === 'g') {
        pendingG = true;
        gTimer = setTimeout(() => { pendingG = false; }, 1_000);
        return;
      }

      if (e.key === '?') {
        e.preventDefault();
        if (!dialog) buildDialog();
        dialog.showModal();
        return;
      }

      if (e.key === '/') {
        const search = document.querySelector('input[name="q"], input[type="search"]');
        if (search) {
          e.preventDefault();
          search.focus();
        }
      }
    });
  };

  // ─── Modal Scroll Lock (iOS hardening) ──────────────────────────

  const initModalScrollLock = () => {
    let scrollY = 0;

    document.addEventListener('modal:open', () => {
      scrollY = window.scrollY;
      document.body.style.position = 'fixed';
      document.body.style.top = `-${scrollY}px`;
      document.body.style.width = '100%';
    });

    document.addEventListener('modal:close', () => {
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.width = '';
      window.scrollTo(0, scrollY);
    });
  };

  // ─── Init ───────────────────────────────────────────────────────────

  const init = () => {
    initHamburger();
    initDropdown();
    initBellDropdown();
    initModals();
    initBadgePolling();
    initShortcutOverlay();
    initModalScrollLock();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
