'use strict';

class DepositActionForm {
  static #instance = null;

  /** @type {HTMLFormElement} */
  #form;
  /** @type {HTMLFieldSetElement} */
  #forfeitFieldset;
  /** @type {NodeListOf<HTMLInputElement|HTMLTextAreaElement>} */
  #forfeitInputs;
  #abortController = new AbortController();

  /** @param {HTMLFormElement} form */
  constructor(form) {
    this.#form = form;
    this.#forfeitFieldset = form.querySelectorAll('fieldset')[1];
    this.#forfeitInputs = this.#forfeitFieldset.querySelectorAll('input, textarea');
    this.#form.addEventListener('change', this.#handleChange, { signal: this.#abortController.signal });
    this.#sync();
  }

  static init() {
    if (DepositActionForm.#instance) return DepositActionForm.#instance;
    const form = document.querySelector('#deposit-detail form[action^="/payments/deposit/"]');
    if (!form) return null;
    const hasFieldsets = form.querySelectorAll('fieldset').length >= 2;
    const hasRadios = form.querySelectorAll('input[name="action"]').length > 0;
    if (!hasFieldsets || !hasRadios) return null;
    return (DepositActionForm.#instance = new DepositActionForm(form));
  }

  destroy() {
    this.#abortController.abort();
    DepositActionForm.#instance = null;
  }

  /** @param {Event} e */
  #handleChange = (e) => {
    if (e.target.name === 'action') this.#sync();
  };

  #sync() {
    const isForfeit = this.#form.querySelector('input[name="action"]:checked')?.value === 'forfeit';
    this.#forfeitFieldset.hidden = !isForfeit;
    for (const el of this.#forfeitInputs) el.disabled = !isForfeit;
  }
}

DepositActionForm.init();
