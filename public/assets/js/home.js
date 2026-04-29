'use strict';

const MOBILE_MQ = '(max-width: 700px)';
const DESKTOP_MQ = '(min-width: 701px)';

// ─── Counter Animation ───────────────────────────────────────────────

class CounterAnimation {
  static #instance = null;
  static #DURATION = 1200;

  /** @type {IntersectionObserver} */
  #observer;
  /** @type {NodeList} */
  #counters;
  #abortController = new AbortController();

  /**
   * Observe the stats list and trigger one-shot counter animation on first intersection.
   *
   * @param {HTMLElement} list - Container element observed for visibility
   * @param {NodeList} counters - The numeric `<strong data-target>` nodes to animate
   */
  constructor(list, counters) {
    this.#counters = counters;

    this.#observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          this.#observer.disconnect();
          for (const counter of this.#counters) CounterAnimation.#animateCounter(counter);
        }
      },
      { threshold: 0.5 }
    );

    this.#observer.observe(list);
  }

  /**
   * Initialize the singleton CounterAnimation when the platform-highlights list and counters are present.
   *
   * @returns {CounterAnimation|null}
   */
  static init() {
    if (CounterAnimation.#instance) return CounterAnimation.#instance;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

    const list = document.querySelector(
      '.home-page > header > section > div > ul[aria-label="Platform highlights"]'
    );
    if (!list) return null;

    const counters = list.querySelectorAll('strong[data-target]');
    if (!counters.length) return null;

    return (CounterAnimation.#instance = new CounterAnimation(list, counters));
  }

  /**
   * Disconnect the IntersectionObserver, abort listeners, and reset the singleton.
   */
  destroy() {
    this.#observer.disconnect();
    this.#abortController.abort();
    CounterAnimation.#instance = null;
  }

  static #easeOut(t) {
    return 1 - (1 - t) ** 3;
  }

  /** @param {HTMLElement} el */
  static #animateCounter(el) {
    const target = parseInt(el.dataset.target, 10);
    if (!target || target <= 0) return;

    if (!el.id) el.id = `pf-counter-${crypto.randomUUID().slice(0, 8)}`;
    const lockedWidth = el.getBoundingClientRect().width;
    NT.style.setRule(
      `counter-${el.id}`,
      `#${CSS.escape(el.id)}`,
      `min-width:${lockedWidth}px`
    );

    el.textContent = '0';
    const start = performance.now();

    const tick = (now) => {
      const elapsed = now - start;
      const progress = Math.min(elapsed / CounterAnimation.#DURATION, 1);
      const current = Math.round(CounterAnimation.#easeOut(progress) * target);
      el.textContent = current.toLocaleString();
      if (progress < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  }
}

// ─── Neighbor Carousel ───────────────────────────────────────────────

class NeighborCarousel {
  static #instance = null;

  /** @type {HTMLElement} */
  #grid;
  /** @type {NodeList} */
  #cards;
  /** @type {HTMLAnchorElement[]} */
  #cardsArray;
  /** @type {HTMLElement} */
  #nav;
  /** @type {HTMLButtonElement[]} */
  #dots;
  /** @type {IntersectionObserver} */
  #observer;
  /** @type {MediaQueryList} */
  #mq;
  #abortController = new AbortController();

  /**
   * Build the dot navigation, observe the cards, and bind viewport changes.
   *
   * @param {HTMLElement} section - Section containing the neighbor cards grid
   */
  constructor(section) {
    this.#grid = section.querySelector(':scope > div');
    this.#cards = this.#grid.querySelectorAll(':scope > a');
    this.#cardsArray = Array.from(this.#cards);
    this.#mq = window.matchMedia(MOBILE_MQ);

    this.#nav = document.createElement('nav');
    this.#nav.setAttribute('aria-label', 'Neighbor card navigation');
    this.#nav.className = 'carousel-dots';

    const { signal } = this.#abortController;

    this.#dots = Array.from(this.#cards, (_, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('aria-label', `Card ${i + 1} of ${this.#cards.length}`);
      btn.addEventListener('click', () => {
        this.#cards[i].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }, { signal });
      this.#nav.appendChild(btn);
      return btn;
    });

    this.#observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          const index = this.#cardsArray.indexOf(entry.target);
          if (index === -1) continue;
          for (let i = 0; i < this.#dots.length; i++) {
            const isActive = i === index;
            this.#dots[i].classList.toggle('active', isActive);
            this.#dots[i].setAttribute('aria-current', isActive ? 'true' : 'false');
          }
        }
      },
      { root: this.#grid, threshold: 0.5 }
    );

    this.#mq.addEventListener('change', this.#handleViewport, { signal });
    this.#handleViewport(this.#mq);
  }

  /**
   * Initialize the singleton NeighborCarousel when the section has at least two cards.
   *
   * @returns {NeighborCarousel|null}
   */
  static init() {
    if (NeighborCarousel.#instance) return NeighborCarousel.#instance;
    const section = document.querySelector('[aria-labelledby="neighbors-heading"]');
    if (!section) return null;
    const grid = section.querySelector(':scope > div');
    if (!grid) return null;
    if (grid.querySelectorAll(':scope > a').length < 2) return null;
    return (NeighborCarousel.#instance = new NeighborCarousel(section));
  }

  /**
   * Disconnect the observer, abort listeners, remove the dot nav, and reset the singleton.
   */
  destroy() {
    this.#observer.disconnect();
    this.#abortController.abort();
    this.#nav.remove();
    NeighborCarousel.#instance = null;
  }

  #activate() {
    this.#grid.after(this.#nav);
    for (const card of this.#cards) this.#observer.observe(card);
    this.#dots[0].classList.add('active');
    this.#dots[0].setAttribute('aria-current', 'true');
  }

  #deactivate() {
    this.#nav.remove();
    for (const card of this.#cards) this.#observer.unobserve(card);
    for (const dot of this.#dots) {
      dot.classList.remove('active');
      dot.removeAttribute('aria-current');
    }
  }

  #handleViewport = (e) => {
    if (e.matches) this.#activate();
    else this.#deactivate();
  };
}

// ─── Location Toggle ─────────────────────────────────────────────────

class LocationToggle {
  static #instance = null;
  static #ARIA_LIVE_RESET_MS = 3_000;

  /** @type {HTMLElement} */
  #toggle;
  /** @type {HTMLElement} */
  #memberList;
  /** @type {NodeList} */
  #links;
  /** @type {HTMLAnchorElement} */
  #lastLink;
  #abortController = new AbortController();
  /** @type {AbortController|null} */
  #fetchController = null;
  #ariaLiveTimer = null;

  /**
   * Wire up location-toggle links to swap the member list via fetch.
   *
   * @param {HTMLElement} toggle - Location-toggle nav element
   * @param {HTMLElement} memberList - Member list element to refresh
   */
  constructor(toggle, memberList) {
    this.#toggle = toggle;
    this.#memberList = memberList;
    this.#links = toggle.querySelectorAll('a[data-city]');
    this.#lastLink = this.#links[this.#links.length - 1];

    toggle.removeAttribute('hidden');
    toggle.dataset.active = toggle.querySelector('a:last-child[aria-current="true"]') ? 'end' : 'start';

    const { signal } = this.#abortController;
    for (const link of this.#links) {
      link.addEventListener('click', this.#handleClick, { signal });
    }
  }

  /**
   * Initialize the singleton LocationToggle when the toggle nav and member list are both present.
   *
   * @returns {LocationToggle|null}
   */
  static init() {
    if (LocationToggle.#instance) return LocationToggle.#instance;
    const toggle = document.getElementById('location-toggle');
    if (!toggle) return null;
    const memberList = document.getElementById('member-list');
    if (!memberList) return null;
    return (LocationToggle.#instance = new LocationToggle(toggle, memberList));
  }

  /**
   * Clear the aria-live reset timer, abort any in-flight fetch, detach listeners, and reset the singleton.
   */
  destroy() {
    clearTimeout(this.#ariaLiveTimer);
    this.#fetchController?.abort();
    this.#abortController.abort();
    LocationToggle.#instance = null;
  }

  #handleClick = async (e) => {
    e.preventDefault();

    const link = e.currentTarget;
    const city = link.dataset.city;

    for (const l of this.#links) l.removeAttribute('aria-current');
    link.setAttribute('aria-current', 'true');
    this.#toggle.dataset.active = link === this.#lastLink ? 'end' : 'start';

    this.#fetchController?.abort();
    this.#fetchController = new AbortController();

    this.#memberList.setAttribute('aria-busy', 'true');
    this.#memberList.setAttribute('aria-live', 'polite');

    try {
      const res = await NT.fetch(`/?location=${encodeURIComponent(city)}`, {
        signal: AbortSignal.any([this.#fetchController.signal, AbortSignal.timeout(10_000)]),
        headers: { Accept: 'text/html' },
      });

      if (!res.ok) throw new Error(res.statusText);

      const doc = NT.parseHtmlDocument(await res.text());
      for (const styleNode of doc.querySelectorAll('style')) styleNode.remove();
      const fresh = doc.getElementById('member-list');

      if (fresh) {
        this.#memberList.replaceChildren(...fresh.childNodes);
        this.#memberList.dispatchEvent(new CustomEvent('member-list:refresh'));
      }
    } catch (err) {
      if (err.name !== 'AbortError') {
        window.location.href = link.href;
      }
    } finally {
      this.#memberList.removeAttribute('aria-busy');
      this.#fetchController = null;
      this.#ariaLiveTimer = setTimeout(
        () => this.#memberList.setAttribute('aria-live', 'off'),
        LocationToggle.#ARIA_LIVE_RESET_MS
      );
    }
  };
}

// ─── Member Carousel ─────────────────────────────────────────────────

class MemberCarousel {
  static #instance = null;
  static #CARDS_PER_PAGE = 3;

  /** @type {HTMLElement} */
  #memberList;
  /** @type {HTMLButtonElement} */
  #prevBtn;
  /** @type {HTMLButtonElement} */
  #nextBtn;
  /** @type {MediaQueryList} */
  #desktop;
  /** @type {MediaQueryList} */
  #reducedMotion;
  #rafPending = false;
  #abortController = new AbortController();

  /**
   * Wire up prev/next buttons and viewport-driven activation.
   *
   * @param {HTMLElement} carousel - Carousel wrapper holding the prev/next buttons
   * @param {HTMLElement} memberList - The scrollable member list element
   */
  constructor(carousel, memberList) {
    this.#memberList = memberList;
    this.#prevBtn = carousel.querySelector('button[data-dir="prev"]');
    this.#nextBtn = carousel.querySelector('button[data-dir="next"]');
    this.#desktop = window.matchMedia(DESKTOP_MQ);
    this.#reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    this.#bind();
    this.#handleViewport(this.#desktop);
  }

  /**
   * Initialize the singleton MemberCarousel when the member-carousel wrapper and its prev/next buttons exist.
   *
   * @returns {MemberCarousel|null}
   */
  static init() {
    if (MemberCarousel.#instance) return MemberCarousel.#instance;
    const carousel = document.getElementById('member-carousel');
    const memberList = document.getElementById('member-list');
    if (!carousel || !memberList) return null;
    const prevBtn = carousel.querySelector('button[data-dir="prev"]');
    const nextBtn = carousel.querySelector('button[data-dir="next"]');
    if (!prevBtn || !nextBtn) return null;
    return (MemberCarousel.#instance = new MemberCarousel(carousel, memberList));
  }

  /**
   * Detach listeners and reset the singleton.
   */
  destroy() {
    this.#abortController.abort();
    MemberCarousel.#instance = null;
  }

  #bind() {
    const { signal } = this.#abortController;
    this.#prevBtn.addEventListener('click', this.#handlePrev, { signal });
    this.#nextBtn.addEventListener('click', this.#handleNext, { signal });
    this.#memberList.addEventListener('member-list:refresh', this.#handleRefresh, { signal });
    this.#memberList.addEventListener('scroll', this.#handleScroll, { signal, passive: true });
    this.#desktop.addEventListener('change', this.#handleViewport, { signal });
  }

  #updateArrowState() {
    this.#prevBtn.disabled = this.#memberList.scrollLeft <= 1;
    this.#nextBtn.disabled =
      this.#memberList.scrollLeft + this.#memberList.clientWidth >= this.#memberList.scrollWidth - 1;
  }

  #resetCarousel() {
    requestAnimationFrame(() => {
      this.#memberList.scrollLeft = 0;
      this.#updateArrowState();
    });
  }

  #scrollByCards(direction) {
    const first = this.#memberList.firstElementChild;
    if (!first) return;
    const gap = parseFloat(getComputedStyle(this.#memberList).gap) || 0;
    const cardWidth = first.offsetWidth + gap;
    this.#memberList.scrollBy({
      left: direction * cardWidth * MemberCarousel.#CARDS_PER_PAGE,
      behavior: this.#reducedMotion.matches ? 'auto' : 'smooth',
    });
  }

  #activate() {
    this.#memberList.dataset.arrows = '';
    this.#resetCarousel();
  }

  #deactivate() {
    delete this.#memberList.dataset.arrows;
    this.#resetCarousel();
  }

  #handlePrev = () => this.#scrollByCards(-1);

  #handleNext = () => this.#scrollByCards(1);

  #handleRefresh = () => {
    requestAnimationFrame(() => this.#resetCarousel());
  };

  #handleScroll = () => {
    if (this.#rafPending) return;
    this.#rafPending = true;
    requestAnimationFrame(() => {
      this.#updateArrowState();
      this.#rafPending = false;
    });
  };

  #handleViewport = (e) => {
    if (e.matches) this.#activate();
    else this.#deactivate();
  };
}

// ─── Popular Carousel ─────────────────────────────────────────────────

class PopularCarousel {
  static #instance = null;
  static #CARDS_PER_PAGE = 3;

  /** @type {HTMLElement} */
  #list;
  /** @type {HTMLButtonElement} */
  #prevBtn;
  /** @type {HTMLButtonElement} */
  #nextBtn;
  /** @type {MediaQueryList} */
  #desktop;
  /** @type {MediaQueryList} */
  #reducedMotion;
  #rafPending = false;
  #abortController = new AbortController();

  /**
   * Wire up prev/next buttons and viewport-driven activation.
   *
   * @param {HTMLElement} carousel - Carousel wrapper holding the prev/next buttons
   * @param {HTMLElement} list - The scrollable popular-tools list element
   */
  constructor(carousel, list) {
    this.#list = list;
    this.#prevBtn = carousel.querySelector('button[data-dir="prev"]');
    this.#nextBtn = carousel.querySelector('button[data-dir="next"]');
    this.#desktop = window.matchMedia(DESKTOP_MQ);
    this.#reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    this.#bind();
    this.#handleViewport(this.#desktop);
  }

  /**
   * Initialize the singleton PopularCarousel when the popular-carousel wrapper and its prev/next buttons exist.
   *
   * @returns {PopularCarousel|null}
   */
  static init() {
    if (PopularCarousel.#instance) return PopularCarousel.#instance;
    const carousel = document.getElementById('popular-carousel');
    const list = document.getElementById('popular-list');
    if (!carousel || !list) return null;
    const prevBtn = carousel.querySelector('button[data-dir="prev"]');
    const nextBtn = carousel.querySelector('button[data-dir="next"]');
    if (!prevBtn || !nextBtn) return null;
    return (PopularCarousel.#instance = new PopularCarousel(carousel, list));
  }

  /**
   * Detach listeners and reset the singleton.
   */
  destroy() {
    this.#abortController.abort();
    PopularCarousel.#instance = null;
  }

  #bind() {
    const { signal } = this.#abortController;
    this.#prevBtn.addEventListener('click', this.#handlePrev, { signal });
    this.#nextBtn.addEventListener('click', this.#handleNext, { signal });
    this.#list.addEventListener('scroll', this.#handleScroll, { signal, passive: true });
    this.#desktop.addEventListener('change', this.#handleViewport, { signal });
  }

  #updateArrowState() {
    this.#prevBtn.disabled = this.#list.scrollLeft <= 1;
    this.#nextBtn.disabled =
      this.#list.scrollLeft + this.#list.clientWidth >= this.#list.scrollWidth - 1;
  }

  #scrollByCards(direction) {
    const card = this.#list.querySelector('article');
    if (!card) return;
    const gap = parseFloat(getComputedStyle(this.#list).gap) || 0;
    const cardWidth = card.offsetWidth + gap;
    this.#list.scrollBy({
      left: direction * cardWidth * PopularCarousel.#CARDS_PER_PAGE,
      behavior: this.#reducedMotion.matches ? 'auto' : 'smooth',
    });
  }

  #activate() {
    this.#list.dataset.arrows = '';
    requestAnimationFrame(() => {
      this.#list.scrollLeft = 0;
      this.#updateArrowState();
    });
  }

  #deactivate() {
    delete this.#list.dataset.arrows;
    requestAnimationFrame(() => {
      this.#list.scrollLeft = 0;
      this.#updateArrowState();
    });
  }

  #handlePrev = () => this.#scrollByCards(-1);

  #handleNext = () => this.#scrollByCards(1);

  #handleScroll = () => {
    if (this.#rafPending) return;
    this.#rafPending = true;
    requestAnimationFrame(() => {
      this.#updateArrowState();
      this.#rafPending = false;
    });
  };

  #handleViewport = (e) => {
    if (e.matches) this.#activate();
    else this.#deactivate();
  };
}

// ─── Init ────────────────────────────────────────────────────────────

CounterAnimation.init();

requestAnimationFrame(() => {
  LocationToggle.init();

  requestAnimationFrame(() => {
    PopularCarousel.init();
    MemberCarousel.init();
    NeighborCarousel.init();
  });
});
