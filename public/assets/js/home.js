'use strict';

/**
 * Neighbor carousel — progressive enhancement for mobile.
 *
 * At ≤640px the CSS makes the neighbor grid a horizontal scroll-snap
 * container showing one card at a time. This script adds clickable dot
 * indicators and keeps them in sync via IntersectionObserver.
 *
 * The dot nav is only inserted into the DOM at ≤640px and removed
 * above that breakpoint — no CSS hide/show needed.
 *
 * Without JS the cards still scroll and snap — dots simply won't appear.
 */
(function () {
  const MQ = window.matchMedia('(max-width: 640px)');
  const section = document.querySelector('[aria-labelledby="neighbors-heading"]');
  if (!section) return;

  const grid = section.querySelector(':scope > div');
  if (!grid) return;

  const cards = grid.querySelectorAll(':scope > a');
  if (cards.length < 2) return;

  /* ── Build dot nav (not yet in DOM) ── */
  const nav = document.createElement('nav');
  nav.setAttribute('aria-label', 'Neighbor card navigation');
  nav.className = 'carousel-dots';

  const dots = Array.from(cards, (_, i) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('aria-label', `Card ${i + 1} of ${cards.length}`);
    btn.addEventListener('click', () => {
      cards[i].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    });
    nav.appendChild(btn);
    return btn;
  });

  /* ── Track visible card ── */
  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;

        const index = Array.from(cards).indexOf(entry.target);
        if (index === -1) continue;

        for (let i = 0; i < dots.length; i++) {
          const isActive = i === index;
          dots[i].classList.toggle('active', isActive);
          dots[i].setAttribute('aria-current', isActive ? 'true' : 'false');
        }
      }
    },
    { root: grid, threshold: 0.5 }
  );

  /* ── Activate / deactivate on viewport change ── */
  function activate() {
    grid.after(nav);
    for (const card of cards) observer.observe(card);
    dots[0].classList.add('active');
    dots[0].setAttribute('aria-current', 'true');
  }

  function deactivate() {
    nav.remove();
    for (const card of cards) observer.unobserve(card);
    for (const dot of dots) {
      dot.classList.remove('active');
      dot.removeAttribute('aria-current');
    }
  }

  function handleViewport(e) {
    if (e.matches) activate();
    else deactivate();
  }

  MQ.addEventListener('change', handleViewport);
  handleViewport(MQ);
})();

/**
 * Location toggle — progressive enhancement.
 *
 * Unhides the city toggle nav and intercepts clicks to swap
 * the member list via fetch + DOMParser instead of a full
 * page reload.
 *
 * Without JS the toggle stays hidden and the server-rendered
 * default city shows — graceful degradation.
 */
(function () {
  const toggle = document.getElementById('location-toggle');
  if (!toggle) return;

  const memberList = document.getElementById('member-list');
  if (!memberList) return;

  toggle.removeAttribute('hidden');

  const links = toggle.querySelectorAll('a[data-city]');
  let controller = null;

  for (const link of links) {
    link.addEventListener('click', async (e) => {
      e.preventDefault();

      const city = link.dataset.city;

      for (const l of links) l.removeAttribute('aria-current');
      link.setAttribute('aria-current', 'true');

      if (controller) controller.abort();
      controller = new AbortController();

      memberList.setAttribute('aria-busy', 'true');

      try {
        const res = await fetch(`/?location=${encodeURIComponent(city)}`, {
          signal: controller.signal,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(res.statusText);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const fresh = doc.getElementById('member-list');

        if (fresh) memberList.innerHTML = fresh.innerHTML;
      } catch (err) {
        if (err.name !== 'AbortError') window.location.href = link.href;
      } finally {
        memberList.removeAttribute('aria-busy');
        controller = null;
      }
    });
  }
})();

/**
 * Sidebar scroll fade — hides the bottom gradient when
 * the member list is scrolled to the end.
 */
(function () {
  const section = document.getElementById('member-list');
  if (!section) return;

  function checkScroll() {
    const atEnd = section.scrollHeight - section.scrollTop - section.clientHeight < 4;
    section.classList.toggle('scrolled-end', atEnd);
  }

  let rafPending = false;
  function scheduleCheck() {
    if (rafPending) return;
    rafPending = true;
    requestAnimationFrame(() => {
      checkScroll();
      rafPending = false;
    });
  }

  section.addEventListener('scroll', scheduleCheck, { passive: true });

  scheduleCheck();

  const observer = new ResizeObserver(scheduleCheck);
  observer.observe(section);
})();
