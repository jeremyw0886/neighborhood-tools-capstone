'use strict';

class TurnstileGuard {
  static #instance = null;

  /** @type {HTMLFormElement} */
  #form;
  /** @type {HTMLButtonElement} */
  #submit;
  #verified = false;
  #timerId = null;

  /**
   * @param {HTMLFormElement} form
   * @param {HTMLButtonElement} submit
   */
  constructor(form, submit) {
    this.#form = form;
    this.#submit = submit;

    const jsFlag = document.createElement('input');
    jsFlag.type = 'hidden';
    jsFlag.name = 'js_enabled';
    jsFlag.value = '1';
    this.#form.appendChild(jsFlag);

    this.#submit.disabled = true;
    this.#submit.setAttribute('aria-busy', 'true');

    window.onTurnstileVerify = this.#handleVerify;
    window.onTurnstileExpire = this.#handleExpire;
    window.onTurnstileError = this.#handleError;

    this.#timerId = setTimeout(() => {
      if (!this.#verified) this.#enable();
    }, 5000);
  }

  /** @returns {TurnstileGuard|null} */
  static init() {
    if (TurnstileGuard.#instance) return TurnstileGuard.#instance;

    const widget = document.querySelector('.cf-turnstile');
    if (!widget) return null;

    const form = widget.closest('form');
    if (!form) return null;

    const submit = form.querySelector('[type="submit"]');
    if (!submit) return null;

    const existing = form.querySelector('[name="cf-turnstile-response"]');
    if (existing && existing.value) return null;

    return (TurnstileGuard.#instance = new TurnstileGuard(form, submit));
  }

  destroy() {
    clearTimeout(this.#timerId);
    window.onTurnstileVerify = null;
    window.onTurnstileExpire = null;
    window.onTurnstileError = null;
    TurnstileGuard.#instance = null;
  }

  #enable() {
    this.#submit.disabled = false;
    this.#submit.removeAttribute('aria-busy');
  }

  #handleVerify = () => {
    this.#verified = true;
    this.#enable();
  };

  #handleExpire = () => {
    this.#verified = false;
    this.#submit.disabled = true;
    this.#submit.setAttribute('aria-busy', 'true');
  };

  #handleError = () => {
    this.#enable();
  };
}

TurnstileGuard.init();
