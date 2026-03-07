'use strict';

/**
 * Notifications page — progressive JS enhancements.
 *
 * Intercepts per-item mark-as-read form submissions via fetch,
 * updates the UI without a full page reload, and keeps the nav
 * badge count in sync with optimistic updates and rollback on failure.
 */
(() => {
  if (!window.NT) return;

  const section = document.querySelector(
    'section[aria-labelledby="notifications-heading"]'
  );
  if (!section) return;

  // ─── Badge Helpers ──────────────────────────────────────────────────

  const getBellLink = () =>
    document.querySelector('#user-actions > a[href="/notifications"]');

  const readBadgeCount = () => {
    const badge = getBellLink()?.querySelector('span');
    return badge ? parseInt(badge.textContent, 10) || 0 : 0;
  };

  const setBadgeCount = (count) => {
    const bellLink = getBellLink();
    if (!bellLink) return;

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

    const mobileBell = document.querySelector(
      '[data-mobile-auth] a[href="/notifications"]'
    );
    if (mobileBell) {
      const icon = mobileBell.querySelector('i');
      mobileBell.textContent = '';
      if (icon) mobileBell.appendChild(icon);
      mobileBell.append(count > 0 ? ` Notifications (${count})` : ' Notifications');
    }
  };

  // ─── Mark-as-Read Interception ──────────────────────────────────────

  const handleMarkRead = async (form) => {
    const li = form.closest('li[data-unread]');
    if (!li) return;

    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;

    const previousCount = readBadgeCount();
    setBadgeCount(Math.max(0, previousCount - 1));
    li.removeAttribute('data-unread');

    const heading = li.querySelector('article > div > a > h2');
    if (heading) heading.style.fontWeight = '';

    const srLabel = li.querySelector('.visually-hidden');

    try {
      const body = new FormData(form);
      const res = await NT.fetch(form.action, { method: 'POST', body });
      const data = await res.json();

      if (!data.success) throw new Error('Server returned failure');

      setBadgeCount(data.unread);
      form.remove();
      srLabel?.remove();
    } catch {
      li.setAttribute('data-unread', '');
      if (heading) heading.style.fontWeight = '700';
      setBadgeCount(previousCount);
      button.disabled = false;
      NT.toast('Could not mark notification as read. Please try again.', 'error');
    }
  };

  section.addEventListener('submit', (e) => {
    const form = e.target.closest(
      'li[data-unread] > article > div > footer > form'
    );
    if (!form) return;

    e.preventDefault();
    handleMarkRead(form);
  });
})();
