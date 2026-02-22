'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('payment-form');
    if (!form) return;

    var stripe = Stripe(form.dataset.publishableKey);
    var elements = stripe.elements({ clientSecret: form.dataset.clientSecret });
    var paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    var submitBtn = form.querySelector('button[type="submit"]');
    var messageEl = document.getElementById('payment-message');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitBtn.disabled = true;
        messageEl.hidden = true;

        stripe.confirmPayment({
            elements: elements,
            confirmParams: {
                return_url: window.location.origin + '/payments/complete',
            },
        }).then(function (result) {
            if (result.error) {
                messageEl.textContent = result.error.message;
                messageEl.hidden = false;
                submitBtn.disabled = false;
            }
        });
    });
});
