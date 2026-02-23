'use strict';

const form = document.getElementById('payment-form');
if (form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const messageEl = document.getElementById('payment-message');

    if (submitBtn && messageEl) {
        if (typeof Stripe === 'undefined') {
            messageEl.textContent = 'Payment system unavailable. Please refresh.';
            messageEl.hidden = false;
        } else {
            const stripe = Stripe(form.dataset.publishableKey);
            const elements = stripe.elements({ clientSecret: form.dataset.clientSecret });
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');

            let submitting = false;
            const btnLabel = submitBtn.textContent;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (submitting) return;
                submitting = true;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing\u2026';
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
                    submitBtn.textContent = btnLabel;
                    submitBtn.disabled = false;
                    submitting = false;
                }
            });
        }
    }
}
