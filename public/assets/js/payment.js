'use strict';

class PaymentForm {
  static #instance = null;

  /** @type {HTMLFormElement} */
  #form;
  /** @type {HTMLButtonElement} */
  #submitBtn;
  /** @type {HTMLElement} */
  #messageEl;
  #stripe;
  #elements;
  #btnLabel;
  #submitting = false;
  #abortController = new AbortController();

  /**
   * @param {HTMLFormElement} form
   * @param {HTMLButtonElement} submitBtn
   * @param {HTMLElement} messageEl
   */
  constructor(form, submitBtn, messageEl) {
    this.#form = form;
    this.#submitBtn = submitBtn;
    this.#messageEl = messageEl;
    this.#btnLabel = submitBtn.textContent;

    this.#stripe = Stripe(form.dataset.publishableKey);
    this.#elements = this.#stripe.elements({ clientSecret: form.dataset.clientSecret });
    this.#elements.create('payment').mount('#payment-element');

    this.#form.addEventListener('submit', this.#handleSubmit, { signal: this.#abortController.signal });
  }

  /** @returns {PaymentForm|null} */
  static init() {
    if (PaymentForm.#instance) return PaymentForm.#instance;

    const form = document.getElementById('payment-form');
    if (!form) return null;

    const submitBtn = form.querySelector('button[type="submit"]');
    const messageEl = document.getElementById('payment-message');
    if (!submitBtn || !messageEl) return null;

    if (typeof Stripe === 'undefined') {
      messageEl.textContent = 'Payment system unavailable. Please refresh.';
      messageEl.hidden = false;
      return null;
    }

    return (PaymentForm.#instance = new PaymentForm(form, submitBtn, messageEl));
  }

  destroy() {
    this.#abortController.abort();
    PaymentForm.#instance = null;
  }

  /** @param {SubmitEvent} e */
  #handleSubmit = async (e) => {
    e.preventDefault();

    if (this.#submitting) return;
    this.#submitting = true;

    this.#submitBtn.disabled = true;
    this.#submitBtn.textContent = 'Processing\u2026';
    this.#messageEl.hidden = true;

    const { error } = await this.#stripe.confirmPayment({
      elements: this.#elements,
      confirmParams: {
        return_url: `${window.location.origin}/payments/complete`,
      },
    });

    if (error) {
      this.#messageEl.textContent = error?.message ?? 'Payment failed. Please try again.';
      this.#messageEl.hidden = false;
      this.#submitBtn.textContent = this.#btnLabel;
      this.#submitBtn.disabled = false;
      this.#submitting = false;
    }
  };
}

PaymentForm.init();
