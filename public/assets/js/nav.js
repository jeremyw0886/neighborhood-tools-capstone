'use strict';

/**
 * Navigation JavaScript — hamburger menu, unified mobile menu, hero dropdown,
 * and <dialog> modal system.
 *
 * Progressive enhancement: without this script, nav links navigate normally,
 * dropdown items remain visible, and [data-modal] links go to full pages.
 *
 * Mobile (<=640px):
 *   The hamburger merges both #top-links and #hero-dropdown into a single
 *   slide-down panel. Auth items (greeting, Dashboard, Notifications, Logout
 *   —or— Login, Sign Up) are cloned into #top-links by JS, separated by a
 *   visual divider. The ellipsis toggle and its floating dropdown are hidden
 *   via CSS. The notification bell remains visible in the nav bar for quick
 *   access. Items are added/removed when the viewport crosses the 640px
 *   breakpoint (e.g. device rotation).
 *
 * Desktop (>640px):
 *   Hamburger is hidden. #top-links and #hero-dropdown render side by side.
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

    const icon = toggle.querySelector('i');
    const authSection = document.getElementById('hero-dropdown');
    const authMenu = document.getElementById('hero-dropdown-menu');

    const open = () => {
      menu.classList.add('open');
      toggle.setAttribute('aria-expanded', 'true');
      icon?.classList.replace('fa-bars', 'fa-xmark');

      const firstLink = menu.querySelector('a');
      firstLink?.focus();
    };

    const close = (returnFocus = true) => {
      menu.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      icon?.classList.replace('fa-xmark', 'fa-bars');

      if (returnFocus) toggle.focus();
    };

    const isOpen = () => menu.classList.contains('open');

    // ── Unified mobile menu: merge auth actions into hamburger ──

    const mobile = window.matchMedia('(max-width: 640px)');

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
      addLi().setAttribute('role', 'separator');

      if (!authSection) return;

      // Greeting — logged-in users (extracted from the toggle button)
      const toggleBtn = document.getElementById('hero-dropdown-toggle');
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
      const bellLink = authSection.querySelector(':scope > a[href="/notifications"]');
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

  // ─── Hero Dropdown ──────────────────────────────────────────────────

  const initDropdown = () => {
    const toggle = document.getElementById('hero-dropdown-toggle');
    const menu = document.getElementById('hero-dropdown-menu');
    if (!toggle || !menu) return;

    menu.hidden = true;

    const getItems = () => [
      ...menu.querySelectorAll('[role="menuitem"] a, [role="menuitem"] button')
    ];

    const open = () => {
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

  // ─── Init ───────────────────────────────────────────────────────────

  const init = () => {
    initHamburger();
    initDropdown();
    initModals();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
