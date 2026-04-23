'use strict';

// ─── Entrance Animation ──────────────────────────────────────────────

class EntranceAnimation {
  static #instance = null;

  /**
   * @param {Element[]} elements
   */
  constructor(elements) {
    requestAnimationFrame(() => {
      for (const el of elements) el.classList.add('animate-in');
    });
  }

  /** @returns {EntranceAnimation|null} */
  static init() {
    if (EntranceAnimation.#instance) return EntranceAnimation.#instance;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

    const grid = document.querySelector('.home-page > header > section > div');
    if (!grid) return null;

    const headline = grid.querySelector(':scope > div:first-child');
    const stats = grid.querySelector(':scope > ul[aria-label="Platform highlights"]');
    const right = grid.querySelector(':scope > div:last-child');
    if (!right) return null;

    const elements = [
      ...(headline ? [...headline.children].filter((el) => el.tagName !== 'H1') : []),
      stats,
      right,
    ].filter(Boolean);

    return (EntranceAnimation.#instance = new EntranceAnimation(elements));
  }

  destroy() {
    EntranceAnimation.#instance = null;
  }
}

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
   * @param {HTMLElement} list
   * @param {NodeList} counters
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

  /** @returns {CounterAnimation|null} */
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

  /** @param {HTMLElement} section */
  constructor(section) {
    this.#grid = section.querySelector(':scope > div');
    this.#cards = this.#grid.querySelectorAll(':scope > a');
    this.#cardsArray = Array.from(this.#cards);
    this.#mq = window.matchMedia('(max-width: 700px)');

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

  /** @returns {NeighborCarousel|null} */
  static init() {
    if (NeighborCarousel.#instance) return NeighborCarousel.#instance;
    const section = document.querySelector('[aria-labelledby="neighbors-heading"]');
    if (!section) return null;
    const grid = section.querySelector(':scope > div');
    if (!grid) return null;
    if (grid.querySelectorAll(':scope > a').length < 2) return null;
    return (NeighborCarousel.#instance = new NeighborCarousel(section));
  }

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
   * @param {HTMLElement} toggle
   * @param {HTMLElement} memberList
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

  /** @returns {LocationToggle|null} */
  static init() {
    if (LocationToggle.#instance) return LocationToggle.#instance;
    const toggle = document.getElementById('location-toggle');
    if (!toggle) return null;
    const memberList = document.getElementById('member-list');
    if (!memberList) return null;
    return (LocationToggle.#instance = new LocationToggle(toggle, memberList));
  }

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

      const text = (await res.text()).replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '');
      const doc = NT.parseHtmlDocument(text);
      const fresh = doc.getElementById('member-list');

      if (fresh) {
        this.#memberList.replaceChildren(...fresh.childNodes);
        this.#memberList.dispatchEvent(new CustomEvent('member-list:refresh'));
      }
    } catch (err) {
      if (err.name !== 'AbortError') {
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        status.textContent = 'Refreshing\u2026';
        this.#memberList.replaceChildren(status);
        window.location.href = link.href;
      }
    } finally {
      this.#memberList.removeAttribute('aria-busy');
      this.#fetchController = null;
      this.#ariaLiveTimer = setTimeout(
        () => this.#memberList.setAttribute('aria-live', 'off'),
        3_000
      );
    }
  };
}

// ─── Member Carousel ─────────────────────────────────────────────────

class MemberCarousel {
  static #instance = null;

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
   * @param {HTMLElement} carousel
   * @param {HTMLElement} memberList
   */
  constructor(carousel, memberList) {
    this.#memberList = memberList;
    this.#prevBtn = carousel.querySelector('button[data-dir="prev"]');
    this.#nextBtn = carousel.querySelector('button[data-dir="next"]');
    this.#desktop = window.matchMedia('(min-width: 701px)');
    this.#reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    this.#bind();
    this.#handleViewport(this.#desktop);
  }

  /** @returns {MemberCarousel|null} */
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
      left: direction * cardWidth * 3,
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
   * @param {HTMLElement} carousel
   * @param {HTMLElement} list
   */
  constructor(carousel, list) {
    this.#list = list;
    this.#prevBtn = carousel.querySelector('button[data-dir="prev"]');
    this.#nextBtn = carousel.querySelector('button[data-dir="next"]');
    this.#desktop = window.matchMedia('(min-width: 701px)');
    this.#reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    this.#bind();
    this.#handleViewport(this.#desktop);
  }

  /** @returns {PopularCarousel|null} */
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
      left: direction * cardWidth * 3,
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

EntranceAnimation.init();
CounterAnimation.init();

requestAnimationFrame(() => {
  LocationToggle.init();

  requestAnimationFrame(() => {
    PopularCarousel.init();
    MemberCarousel.init();
    NeighborCarousel.init();
  });
});
