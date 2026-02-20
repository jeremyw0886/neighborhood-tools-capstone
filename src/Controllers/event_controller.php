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
        $this->requireAuth();

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

        $eventSuccess = $_SESSION['event_success'] ?? '';
        unset($_SESSION['event_success']);

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
            'eventSuccess' => $eventSuccess,
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

    /**
     * Validate and persist a new community event.
     *
     * Expects POST fields: csrf_token, event_name, event_description,
     * start_date, start_time, end_date, end_time, neighborhood_id.
     * Combines date + time pairs into TIMESTAMP values for the DB.
     * On success redirects to /events with a flash notice.
     * On failure redirects back to /events/create with field-keyed errors.
     */
    public function store(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $name           = trim($_POST['event_name'] ?? '');
        $description    = trim($_POST['event_description'] ?? '');
        $startDate      = trim($_POST['start_date'] ?? '');
        $startTime      = trim($_POST['start_time'] ?? '');
        $endDate        = trim($_POST['end_date'] ?? '');
        $endTime        = trim($_POST['end_time'] ?? '');
        $neighborhoodId = trim($_POST['neighborhood_id'] ?? '');

        $old = [
            'event_name'        => $name,
            'event_description' => $description,
            'start_date'        => $startDate,
            'start_time'        => $startTime,
            'end_date'          => $endDate,
            'end_time'          => $endTime,
            'neighborhood_id'   => $neighborhoodId,
        ];

        $errors = [];

        if ($name === '') {
            $errors['event_name'] = 'Event name is required.';
        } elseif (mb_strlen($name) > 255) {
            $errors['event_name'] = 'Event name must be 255 characters or fewer.';
        }

        if ($description !== '' && mb_strlen($description) > 5000) {
            $errors['event_description'] = 'Description must be 5,000 characters or fewer.';
        }

        $validStartDate = false;
        if ($startDate === '') {
            $errors['start_date'] = 'Start date is required.';
        } else {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
            if ($parsed === false || $parsed->format('Y-m-d') !== $startDate) {
                $errors['start_date'] = 'Start date is not valid.';
            } elseif ($startDate < date('Y-m-d')) {
                $errors['start_date'] = 'Start date cannot be in the past.';
            } else {
                $validStartDate = true;
            }
        }

        $validStartTime = false;
        if ($startTime === '') {
            $errors['start_time'] = 'Start time is required.';
        } else {
            $parsed = \DateTimeImmutable::createFromFormat('H:i', $startTime);
            if ($parsed === false || $parsed->format('H:i') !== $startTime) {
                $errors['start_time'] = 'Start time is not valid.';
            } else {
                $validStartTime = true;
            }
        }

        if ($endDate !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);
            if ($parsed === false || $parsed->format('Y-m-d') !== $endDate) {
                $errors['end_date'] = 'End date is not valid.';
            }
        }

        if ($endTime !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('H:i', $endTime);
            if ($parsed === false || $parsed->format('H:i') !== $endTime) {
                $errors['end_time'] = 'End time is not valid.';
            }
        }

        $startAt = null;
        $endAt   = null;

        if ($validStartDate && $validStartTime) {
            $startAt = $startDate . ' ' . $startTime . ':00';
        }

        $hasEnd = $endDate !== '' || $endTime !== '';

        if ($hasEnd && $startAt !== null && !isset($errors['end_date']) && !isset($errors['end_time'])) {
            $resolvedEndDate = $endDate !== '' ? $endDate : $startDate;
            $resolvedEndTime = $endTime !== '' ? $endTime : $startTime;
            $endAt = $resolvedEndDate . ' ' . $resolvedEndTime . ':00';

            if ($endAt <= $startAt) {
                $errors['end_date'] = 'End date/time must be after the start.';
                $endAt = null;
            }
        }

        $nbhId = $neighborhoodId !== '' ? (int) $neighborhoodId : null;

        if ($nbhId !== null && $nbhId < 1) {
            $errors['neighborhood_id'] = 'Invalid neighborhood selected.';
            $nbhId = null;
        }

        if ($errors !== []) {
            $_SESSION['event_errors'] = $errors;
            $_SESSION['event_old']    = $old;
            $this->redirect('/events/create');
        }

        try {
            Event::create(
                name:           $name,
                description:    $description !== '' ? $description : null,
                startAt:        $startAt,
                endAt:          $endAt,
                neighborhoodId: $nbhId,
                creatorId:      (int) $_SESSION['user_id'],
            );

            $_SESSION['event_success'] = 'Event created successfully.';
            $this->redirect('/events');
        } catch (\Throwable $e) {
            error_log('EventController::store — ' . $e->getMessage());

            $_SESSION['event_errors'] = ['general' => 'Something went wrong creating the event. Please try again.'];
            $_SESSION['event_old']    = $old;
            $this->redirect('/events/create');
        }
    }

    /**
     * Display a single event with full details and metadata.
     *
     * Public page — no auth required. Fetches from event_evt with
     * neighborhood/creator joins (not limited to upcoming_event_v)
     * so past events are still viewable.
     */
    public function show(string $id): void
    {
        $this->requireAuth();

        $eventId = (int) $id;

        if ($eventId < 1) {
            $this->abort(404);
        }

        try {
            $event = Event::findById($eventId);
        } catch (\Throwable $e) {
            error_log('EventController::show — ' . $e->getMessage());
            $event = null;
        }

        if ($event === null) {
            $this->abort(404);
        }

        try {
            $meta = Event::getMeta($eventId);
        } catch (\Throwable $e) {
            error_log('EventController::show meta — ' . $e->getMessage());
            $meta = [];
        }

        $isAdmin = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'], true);

        $this->render('events/show', [
            'title'       => htmlspecialchars($event['event_name_evt']) . ' — NeighborhoodTools',
            'description' => 'Event details for ' . htmlspecialchars($event['event_name_evt']),
            'pageCss'     => ['event.css'],
            'event'       => $event,
            'meta'        => $meta,
            'isAdmin'     => $isAdmin,
        ]);
    }
}
