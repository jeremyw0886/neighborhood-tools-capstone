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
   * @param {HTMLFormElement} form - The payment form element
   * @param {HTMLButtonElement} submitBtn - The submit button inside the form
   * @param {HTMLElement} messageEl - Element used to display payment status messages
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

  /**
   * Mount the Stripe Payment Element and intercept form submit.
   *
   * @returns {PaymentForm|null}
   */
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

  /**
   * Detach listeners and clear the singleton.
   */
  destroy() {
    this.#abortController.abort();
    PaymentForm.#instance = null;
  }

  #handleSubmit = async (e) => {
    e.preventDefault();

    if (this.#submitting) return;
    this.#submitting = true;

    this.#submitBtn.disabled = true;
    this.#submitBtn.textContent = 'Processing\u2026';
    this.#messageEl.hidden = true;

    let error = null;
    try {
      ({ error } = await this.#stripe.confirmPayment({
        elements: this.#elements,
        confirmParams: {
          return_url: `${window.location.origin}/payments/complete`,
        },
      }));
    } catch {
      error = { message: 'Payment failed. Please try again.' };
    }

    if (error) {
      this.#messageEl.textContent = error.message ?? 'Payment failed. Please try again.';
      this.#messageEl.hidden = false;
      this.#submitBtn.textContent = this.#btnLabel;
      this.#submitBtn.disabled = false;
      this.#submitting = false;
    }
    // No else: on success Stripe redirects to return_url; keep the button
    // disabled so the user doesn't retrigger during the navigation tick.
  };
}

PaymentForm.init();
