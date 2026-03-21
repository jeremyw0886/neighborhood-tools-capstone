'use strict';

/**
 * Entrance animation — fades up left column and action card with stagger.
 */
(function () {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reducedMotion.matches) return;

  const grid = document.querySelector(
    '.home-page > header > section > div'
  );
  if (!grid) return;

  const left = grid.querySelector(':scope > div:first-child');
  const right = grid.querySelector(':scope > div:last-child');
  if (!left || !right) return;

  requestAnimationFrame(() => {
    left.classList.add('animate-in');
    right.classList.add('animate-in');
  });
})();

/**
 * Trust signal count-up — animates stat numbers from 0 to target.
 */
(function () {
  const list = document.querySelector(
    '.home-page > header > section > div > div:first-child > ul'
  );
  if (!list) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reducedMotion.matches) return;

  const counters = list.querySelectorAll('strong[data-target]');
  if (!counters.length) return;

  const DURATION = 1200;

  function easeOut(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  function animateCounter(el) {
    const target = parseInt(el.dataset.target, 10);
    if (!target || target <= 0) return;

    el.textContent = '0';
    const start = performance.now();

    function tick(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / DURATION, 1);
      const current = Math.round(easeOut(progress) * target);
      el.textContent = current.toLocaleString();
      if (progress < 1) requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
  }

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        observer.disconnect();
        for (const counter of counters) animateCounter(counter);
      }
    },
    { threshold: 0.5 }
  );

  function startObserving() {
    observer.observe(list);
  }

  const leftCol = list.parentElement;
  if (leftCol?.classList.contains('animate-in')) {
    leftCol.addEventListener('animationend', startObserving, { once: true });
  } else {
    startObserving();
  }
})();

/**
 * Rotating subtitle crossfade — cycles hero subtitle every 4 seconds.
 */
(function () {
  const subtitle = document.querySelector(
    '.home-page > header > section > div > div:first-child > p'
  );
  if (!subtitle) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reducedMotion.matches) return;

  const lines = [
    'Borrow tools from your neighbors. Lend yours when you\u2019re not using them.',
    'Save money. Reduce waste. Build trust.',
    'Your neighborhood\u2019s shared toolbox is just a click away.',
  ];

  let current = 0;
  const INTERVAL = 6000;
  const FADE = 300;
  let timer = null;

  function cycle() {
    timer = setTimeout(() => {
      subtitle.classList.add('fading');
      setTimeout(() => {
        current = (current + 1) % lines.length;
        subtitle.textContent = lines[current];
        requestAnimationFrame(() => {
          subtitle.classList.remove('fading');
        });
        cycle();
      }, FADE);
    }, INTERVAL);
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      clearTimeout(timer);
      timer = null;
    } else {
      subtitle.classList.remove('fading');
      if (!timer) cycle();
    }
  });

  cycle();
})();

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
    const centerX = rect.left + rect.width / 2 + window.scrollX;
    const above = rect.top > 50;
    const top = above
      ? rect.top + window.scrollY - 8
      : rect.bottom + window.scrollY + 8;

    tooltip.style.left = `${centerX}px`;
    tooltip.style.top = `${top}px`;
    tooltip.style.transform = above
      ? 'translate(-50%, -100%)'
      : 'translateX(-50%)';
  }

  function cardFrom(el) {
    return el?.closest?.('article');
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
      memberList.setAttribute('aria-live', 'polite');

      try {
        const res = await fetch(`/?location=${encodeURIComponent(city)}`, {
          signal: AbortSignal.any([controller.signal, AbortSignal.timeout(10000)]),
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(res.statusText);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const fresh = doc.getElementById('member-list');

        if (fresh) {
          memberList.replaceChildren(...fresh.childNodes);
          memberList.dispatchEvent(new CustomEvent('member-list:refresh'));
        }
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
        // 3s heuristic: no reliable "announcement complete" event from screen
        // readers — 3 seconds gives JAWS/VoiceOver enough time for longer lists
        setTimeout(() => memberList.setAttribute('aria-live', 'off'), 3000);
      }
    });
  }
})();

/**
 * Member carousel — arrow navigation for desktop.
 *
 * At >700px, unhides prev/next arrow buttons and hides the native
 * scrollbar. Scrolls by 3 card widths per click. At ≤700px, arrows
 * stay hidden and CSS scroll-snap handles single-card swiping.
 *
 * Without JS the native scrollbar remains visible and functional.
 */
(function () {
  const carousel = document.getElementById('member-carousel');
  const memberList = document.getElementById('member-list');
  if (!carousel || !memberList) return;

  const prevBtn = carousel.querySelector('button[data-dir="prev"]');
  const nextBtn = carousel.querySelector('button[data-dir="next"]');
  if (!prevBtn || !nextBtn) return;

  const desktop = window.matchMedia('(min-width: 701px)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function updateArrowState() {
    prevBtn.disabled = memberList.scrollLeft <= 1;
    nextBtn.disabled =
      memberList.scrollLeft + memberList.clientWidth >= memberList.scrollWidth - 1;
  }

  function resetCarousel() {
    memberList.scrollLeft = 0;
    updateArrowState();
  }

  let rafPending = false;
  function onScroll() {
    if (rafPending) return;
    rafPending = true;
    requestAnimationFrame(() => {
      updateArrowState();
      rafPending = false;
    });
  }

  function scrollByCards(direction) {
    const first = memberList.firstElementChild;
    if (!first) return;
    const gap = parseFloat(getComputedStyle(memberList).gap) || 0;
    const cardWidth = first.offsetWidth + gap;
    memberList.scrollBy({
      left: direction * cardWidth * 3,
      behavior: reducedMotion.matches ? 'auto' : 'smooth'
    });
  }

  prevBtn.addEventListener('click', () => scrollByCards(-1));
  nextBtn.addEventListener('click', () => scrollByCards(1));

  function activate() {
    prevBtn.hidden = false;
    nextBtn.hidden = false;
    memberList.dataset.arrows = '';
    memberList.addEventListener('scroll', onScroll, { passive: true });
    resetCarousel();
  }

  function deactivate() {
    prevBtn.hidden = true;
    nextBtn.hidden = true;
    delete memberList.dataset.arrows;
    memberList.removeEventListener('scroll', onScroll);
    resetCarousel();
  }

  memberList.addEventListener('member-list:refresh', () => {
    requestAnimationFrame(resetCarousel);
  });

  function handleViewport(e) {
    if (e.matches) activate();
    else deactivate();
  }

  desktop.addEventListener('change', handleViewport);
  handleViewport(desktop);
})();
