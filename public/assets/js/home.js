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

  const cards = grid.querySelectorAll('.neighbor-card');
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
