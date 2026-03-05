'use strict';

/**
 * Neighbor carousel — progressive enhancement for mobile.
 *
 * At ≤700px the CSS makes the neighbor grid a horizontal scroll-snap
 * container showing one card at a time. This script adds clickable dot
 * indicators and keeps them in sync via IntersectionObserver.
 *
 * The dot nav is only inserted into the DOM at ≤700px and removed
 * above that breakpoint — no CSS hide/show needed.
 *
 * Without JS the cards still scroll and snap — dots simply won't appear.
 */
(function () {
  const MQ = window.matchMedia('(max-width: 700px)');
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
 * Featured tool card hover previews — shows condition, fee, and owner
 * in a tooltip on pointer hover or keyboard focus.
 *
 * Touch devices are excluded — tap navigates to the tool page as normal.
 * Without JS the cards work identically; this is purely additive.
 */
(function () {
  const section = document.querySelector('[aria-labelledby="popular-heading"]');
  if (!section) return;

  const container = section.querySelector(':scope > div[role="list"]');
  if (!container) return;

  const tooltip = document.createElement('div');
  tooltip.className = 'tool-preview';
  tooltip.setAttribute('role', 'tooltip');
  tooltip.hidden = true;
  document.body.appendChild(tooltip);

  let activeCard = null;
  let hideTimeout = null;

  function conditionLabel(raw) {
    const map = { 'new': 'New', 'good': 'Good', 'fair': 'Fair', 'poor': 'Poor' };
    return map[raw?.toLowerCase()] ?? raw ?? '';
  }

  function show(card) {
    const condition = card.dataset.condition;
    const owner     = card.dataset.owner;
    const deposit   = card.dataset.deposit;

    if (!condition && !owner) return;

    clearTimeout(hideTimeout);

    tooltip.innerHTML =
      (condition
        ? `<span data-condition="${condition.toLowerCase()}">${conditionLabel(condition)}</span>`
        : '') +
      (deposit
        ? `<span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> $${deposit} deposit</span>`
        : '') +
      (owner ? `<span>${owner}</span>` : '');

    tooltip.hidden = false;
    activeCard = card;
    position(card);
  }

  function hide() {
    hideTimeout = setTimeout(() => {
      tooltip.hidden = true;
      activeCard = null;
    }, 80);
  }

  function position(card) {
    const rect = card.getBoundingClientRect();
    const tw = tooltip.offsetWidth;
    const th = tooltip.offsetHeight;

    let left = rect.left + rect.width / 2 - tw / 2;
    let top = rect.top - th - 8;

    left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));

    if (top < 8) {
      top = rect.bottom + 8;
    }

    tooltip.style.left = `${left + window.scrollX}px`;
    tooltip.style.top  = `${top + window.scrollY}px`;
  }

  function cardFrom(el) {
    return el?.closest?.('[role="listitem"]');
  }

  container.addEventListener('pointerover', (e) => {
    if (e.pointerType === 'touch') return;
    const card = cardFrom(e.target);
    if (card && card !== activeCard) show(card);
  });

  container.addEventListener('pointerout', (e) => {
    if (e.pointerType === 'touch') return;
    const card = cardFrom(e.target);
    if (!card || card !== activeCard) return;
    const related = cardFrom(e.relatedTarget);
    if (related !== card) hide();
  });

  container.addEventListener('focusin', (e) => {
    const card = cardFrom(e.target);
    if (card) show(card);
  });

  container.addEventListener('focusout', (e) => {
    const card = cardFrom(e.target);
    if (card && card === activeCard) hide();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && activeCard) {
      tooltip.hidden = true;
      activeCard = null;
    }
  });

  window.addEventListener('scroll', () => {
    if (activeCard) position(activeCard);
  }, { passive: true });
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
  const lastLink = links[links.length - 1];
  let controller = null;

  toggle.dataset.active = toggle.querySelector('a:last-child[aria-current="true"]') ? 'end' : 'start';

  for (const link of links) {
    link.addEventListener('click', async (e) => {
      e.preventDefault();

      const city = link.dataset.city;

      for (const l of links) l.removeAttribute('aria-current');
      link.setAttribute('aria-current', 'true');
      toggle.dataset.active = link === lastLink ? 'end' : 'start';

      if (controller) controller.abort();
      controller = new AbortController();

      memberList.setAttribute('aria-busy', 'true');

      try {
        const res = await fetch(`/?location=${encodeURIComponent(city)}`, {
          signal: AbortSignal.any([controller.signal, AbortSignal.timeout(10000)]),
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(res.statusText);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const fresh = doc.getElementById('member-list');

        if (fresh) memberList.replaceChildren(...fresh.childNodes);
      } catch (err) {
        if (err.name !== 'AbortError') {
          const status = document.createElement('p');
          status.setAttribute('role', 'status');
          status.textContent = 'Refreshing\u2026';
          memberList.replaceChildren(status);
          window.location.href = link.href;
        }
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
