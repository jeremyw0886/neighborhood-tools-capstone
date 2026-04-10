<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\RateLimiter;

class CspController
{
    private const int MAX_REPORTS_PER_MINUTE = 30;
    private const int RATE_WINDOW_SECONDS = 60;
    private const int MAX_FIELD_LENGTH = 500;
    private const int MAX_BODY_BYTES = 10_240;

    private const array ACCEPTED_CONTENT_TYPES = [
        'application/csp-report',
        'application/reports+json',
    ];

    /**
     * Accept CSP and Trusted Types violation reports and log them.
     */
    public function report(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accepted = array_any(
            self::ACCEPTED_CONTENT_TYPES,
            static fn(string $type): bool => str_starts_with($contentType, $type)
        );

        if (!$accepted) {
            http_response_code(400);
            exit;
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $ip = $forwarded !== '' ? explode(',', $forwarded, 2)[0] : ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $ip = trim($ip);
        $rateLimitKey = 'csp-report:' . $ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_REPORTS_PER_MINUTE, self::RATE_WINDOW_SECONDS)) {
            http_response_code(429);
            exit;
        }

        RateLimiter::increment($rateLimitKey);

        $body = file_get_contents('php://input', length: self::MAX_BODY_BYTES + 1);
        if ($body === '' || $body === false) {
            http_response_code(400);
            exit;
        }

        if (strlen($body) > self::MAX_BODY_BYTES) {
            http_response_code(413);
            exit;
        }

        if (!json_validate($body)) {
            http_response_code(400);
            exit;
        }

        $report = json_decode($body, true);

        if (isset($report['csp-report'])) {
            self::logCspReport($report['csp-report'], $ip);
        } elseif (is_array($report) && isset($report[0]['type'])) {
            foreach ($report as $entry) {
                if (($entry['type'] ?? '') === 'csp-violation') {
                    self::logReportToEntry($entry['body'] ?? [], $ip);
                }
            }
        } elseif (isset($report['type']) && $report['type'] === 'tt-default-fallback') {
            self::logTrustedTypesFallback($report, $ip);
        } else {
            http_response_code(400);
            exit;
        }

        http_response_code(204);
        exit;
    }

    /**
     * Log a report-uri format CSP violation.
     */
    private static function logCspReport(array $violation, string $ip): void
    {
        error_log(sprintf(
            'CSP violation: ip=%s directive=%s blocked=%s source=%s page=%s',
            $ip,
            self::sanitizeLogValue($violation['violated-directive'] ?? 'unknown'),
            self::sanitizeLogValue($violation['blocked-uri'] ?? 'unknown'),
            self::sanitizeLogValue($violation['source-file'] ?? 'unknown'),
            self::sanitizeLogValue($violation['document-uri'] ?? 'unknown'),
        ));
    }

    /**
     * Log a report-to format CSP violation.
     */
    private static function logReportToEntry(array $body, string $ip): void
    {
        error_log(sprintf(
            'CSP violation: ip=%s directive=%s blocked=%s source=%s page=%s',
            $ip,
            self::sanitizeLogValue($body['effectiveDirective'] ?? 'unknown'),
            self::sanitizeLogValue($body['blockedURL'] ?? 'unknown'),
            self::sanitizeLogValue($body['sourceFile'] ?? 'unknown'),
            self::sanitizeLogValue($body['documentURL'] ?? 'unknown'),
        ));
    }

    /**
     * Log a Trusted Types default policy fallback beacon.
     */
    private static function logTrustedTypesFallback(array $report, string $ip): void
    {
        error_log(sprintf(
            'TT-default fallback: ip=%s sink=%s page=%s url=%s',
            $ip,
            self::sanitizeLogValue($report['sink'] ?? 'unknown'),
            self::sanitizeLogValue($report['page'] ?? 'unknown'),
            self::sanitizeLogValue($report['url'] ?? 'n/a'),
        ));
    }

    /**
     * Strip control characters and truncate to prevent log injection.
     */
    private static function sanitizeLogValue(mixed $value): string
    {
        if (!is_string($value)) {
            return 'unknown';
        }

        $clean = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        return mb_substr($clean, 0, self::MAX_FIELD_LENGTH);
    }
}
