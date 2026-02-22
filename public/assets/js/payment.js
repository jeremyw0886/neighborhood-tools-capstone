'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('payment-form');
    if (!form) return;

    const stripe = Stripe(form.dataset.publishableKey);
    const elements = stripe.elements({ clientSecret: form.dataset.clientSecret });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    const submitBtn = form.querySelector('button[type="submit"]');
    const messageEl = document.getElementById('payment-message');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        submitBtn.disabled = true;
        messageEl.hidden = true;

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.origin + '/payments/complete',
            },
        });

        if (error) {
            messageEl.textContent = error.message;
            messageEl.hidden = false;
            submitBtn.disabled = false;
        }
    });
});
