<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Event;

class EventController extends BaseController
{
    private const int PER_PAGE = 12;

    /**
     * Community events listing with optional timing filter and pagination.
     *
     * Public page — no auth required. Queries upcoming_event_v which
     * computes days_until_event and event_timing labels (HAPPENING NOW,
     * THIS WEEK, THIS MONTH, UPCOMING).
     */
    public function index(): void
    {
        $timing = trim($_GET['timing'] ?? '');
        $timing = $timing !== '' ? strtoupper($timing) : null;
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $events        = Event::getUpcoming(timing: $timing, limit: self::PER_PAGE, offset: $offset);
            $totalCount    = Event::getUpcomingCount(timing: $timing);
            $timingCounts  = Event::getTimingCounts();
        } catch (\Throwable $e) {
            error_log('EventController::index — ' . $e->getMessage());
            $events       = [];
            $totalCount   = 0;
            $timingCounts = [];
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $filterParams = array_filter([
            'timing' => $timing,
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('events/index', [
            'title'        => 'Community Events — NeighborhoodTools',
            'description'  => 'Upcoming community events in the Asheville and Hendersonville areas.',
            'pageCss'      => ['event.css'],
            'events'       => $events,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
            'filterParams' => $filterParams,
            'timing'       => $timing,
            'timingCounts' => $timingCounts,
        ]);
    }
}
