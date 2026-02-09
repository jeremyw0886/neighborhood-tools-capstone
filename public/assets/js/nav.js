'use strict';

/**
 * Navigation JavaScript — hamburger menu, hero dropdown, and <dialog> modal system.
 *
 * Progressive enhancement: without this script, nav links navigate normally,
 * dropdown items remain visible, and [data-modal] links go to full pages.
 *
 * @see src/Views/partials/nav.php  — nav HTML structure
 * @see src/Views/layouts/main.php  — <dialog> modal partials
 */

(() => {

  // ─── Hamburger Mobile Menu ──────────────────────────────────────────

  const initHamburger = () => {
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('top-links');
    if (!toggle || !menu) return;

    const icon = toggle.querySelector('i');

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

    toggle.addEventListener('click', () => {
      isOpen() ? close() : open();
    });

    // Escape closes the mobile menu
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen()) {
        close();
      }
    });

    // Click outside closes the mobile menu
    document.addEventListener('click', (e) => {
      if (isOpen() && !menu.contains(e.target) && !toggle.contains(e.target)) {
        close(false);
      }
    });

    // Close mobile menu when viewport crosses the breakpoint (e.g. device rotation)
    const desktop = window.matchMedia('(min-width: 641px)');
    desktop.addEventListener('change', (e) => {
      if (e.matches && isOpen()) {
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
