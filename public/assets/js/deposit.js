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

  /**
   * @param {HTMLFormElement} form - The deposit-process form
   * @param {HTMLFieldSetElement} forfeitFieldset - The fieldset whose visibility tracks the selected action
   */
  constructor(form, forfeitFieldset) {
    this.#form = form;
    this.#forfeitFieldset = forfeitFieldset;
    this.#forfeitInputs = forfeitFieldset.querySelectorAll('input, textarea');
    this.#form.addEventListener('change', this.#handleChange, { signal: this.#abortController.signal });
    this.#sync();
  }

  /**
   * Wire up the forfeit-details fieldset on the deposit-process form.
   *
   * @returns {DepositActionForm|null}
   */
  static init() {
    if (DepositActionForm.#instance) return DepositActionForm.#instance;
    const form = document.querySelector('#deposit-detail form[action^="/payments/deposit/"]');
    if (!form) return null;
    const forfeitFieldset = form.querySelector('fieldset[data-forfeit-fields]');
    const hasRadios = form.querySelectorAll('input[name="action"]').length > 0;
    if (!forfeitFieldset || !hasRadios) return null;
    return (DepositActionForm.#instance = new DepositActionForm(form, forfeitFieldset));
  }

  /**
   * Detach listeners and clear the singleton.
   */
  destroy() {
    this.#abortController.abort();
    DepositActionForm.#instance = null;
  }

  #handleChange = (e) => {
    if (e.target instanceof HTMLInputElement && e.target.name === 'action') this.#sync();
  };

  #sync() {
    const isForfeit = this.#form.querySelector('input[name="action"]:checked')?.value === 'forfeit';
    this.#forfeitFieldset.hidden = !isForfeit;
    for (const el of this.#forfeitInputs) el.disabled = !isForfeit;
  }
}

DepositActionForm.init();
