<?php

declare(strict_types=1);

/**
 * Per-action rate-limit configuration.
 *
 * Each entry maps an action key to a sliding-window policy enforced by
 * App\Core\RateLimiter. Lookup is per-IP — the limiter keys state files
 * by `"{ip}|{action}"`, so the limits below are per-source, not global.
 *
 * - `max_attempts`   — number of attempts allowed within the window
 *                      before `RateLimiter::tooManyAttempts()` returns true.
 * - `window_seconds` — sliding window length, measured from the first
 *                      recorded attempt. The counter resets on the next
 *                      `tooManyAttempts()` call once the window elapses,
 *                      so a quiet hour fully clears the IP's history.
 *
 * Counters increment via `RateLimiter::increment()` (called explicitly by
 * controllers after a failed/limit-relevant action) or via
 * `BaseController::checkRateLimit()` for the auth flows. A successful
 * action calls `RateLimiter::reset()` to drop prior failed attempts.
 */
return [
    'login' => [
        'max_attempts'   => 5,
        'window_seconds' => 900,
    ],
    'register' => [
        'max_attempts'   => 3,
        'window_seconds' => 3600,
    ],
    'borrow_request' => [
        'max_attempts'   => 10,
        'window_seconds' => 3600,
    ],
    'forgot_password' => [
        'max_attempts'   => 3,
        'window_seconds' => 900,
    ],
    'reset_password' => [
        'max_attempts'   => 5,
        'window_seconds' => 900,
    ],
    'profile_update' => [
        'max_attempts'   => 10,
        'window_seconds' => 900,
    ],
    'profile_image' => [
        'max_attempts'   => 10,
        'window_seconds' => 900,
    ],
];
