<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Event;
use App\Models\Neighborhood;

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

    /**
     * Show the event creation form.
     *
     * Admin-only — requires admin or super_admin role.
     * Loads neighborhoods grouped by city for the location selector.
     * Recovers flash data from a failed store() attempt.
     */
    public function create(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $neighborhoods = [];

        try {
            $neighborhoods = Neighborhood::allGroupedByCity();
        } catch (\Throwable $e) {
            error_log('EventController::create — ' . $e->getMessage());
        }

        $errors = $_SESSION['event_errors'] ?? [];
        $old    = $_SESSION['event_old'] ?? [];
        unset($_SESSION['event_errors'], $_SESSION['event_old']);

        $this->render('events/create', [
            'title'         => 'Create Event — NeighborhoodTools',
            'description'   => 'Schedule a new community event.',
            'pageCss'       => ['event.css'],
            'neighborhoods' => $neighborhoods,
            'errors'        => $errors,
            'old'           => $old,
        ]);
    }
}
