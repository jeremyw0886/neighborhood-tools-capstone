<?php

declare(strict_types=1);

/**
 * Stripe webhook source IPs.
 *
 * Defense-in-depth allowlist enforced before signature verification in
 * PaymentController::stripeWebhook(). Stops drive-by probes and replay
 * traffic before any cryptographic work runs.
 *
 * Update from the published list whenever Stripe rotates their ranges:
 *   https://stripe.com/docs/ips#webhook-ips
 *
 * @return list<string>
 */
return [
    '3.18.12.63',
    '3.130.192.231',
    '13.235.14.237',
    '13.235.122.149',
    '18.211.135.69',
    '35.154.171.200',
    '52.15.183.38',
    '54.88.130.119',
    '54.88.130.237',
    '54.187.174.169',
    '54.187.205.235',
    '54.187.216.72',
];
