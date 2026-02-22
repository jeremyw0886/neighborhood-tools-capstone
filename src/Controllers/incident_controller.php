<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Borrow;
use App\Models\Incident;

class IncidentController extends BaseController
{
    private const int MAX_PHOTOS = 5;
    private const int MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    private const array ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const array MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    /**
     * Display the incident report form for a borrow transaction.
     *
     * Any authenticated party (borrower or lender) to the borrow may
     * file an incident. The view shows the incident type selector
     * populated from incident_type_ity and context about the borrow.
     */
    public function create(string $borrowId): void
    {
        $this->requireAuth();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findById($id);
        } catch (\Throwable $e) {
            error_log('IncidentController::create borrow lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        $isBorrower = (int) $borrow['borrower_id'] === $userId;
        $isLender   = (int) $borrow['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        try {
            $hasExisting = Incident::hasOpenIncident($id);
        } catch (\Throwable $e) {
            error_log('IncidentController::create incident check — ' . $e->getMessage());
            $hasExisting = false;
        }

        try {
            $incidentTypes = Incident::getTypes();
        } catch (\Throwable $e) {
            error_log('IncidentController::create types lookup — ' . $e->getMessage());
            $incidentTypes = [];
        }

        $errors = $_SESSION['incident_errors'] ?? [];
        $old    = $_SESSION['incident_old'] ?? [];
        unset($_SESSION['incident_errors'], $_SESSION['incident_old']);

        $this->render('incidents/create', [
            'title'         => 'Report an Incident — NeighborhoodTools',
            'description'   => 'File an incident report for a borrow transaction.',
            'pageCss'       => ['incident.css'],
            'borrow'        => $borrow,
            'hasExisting'   => $hasExisting,
            'incidentTypes' => $incidentTypes,
            'errors'        => $errors,
            'old'           => $old,
        ]);
    }

    /**
     * Validate and persist a new incident report with optional photos.
     *
     * Expects POST fields: csrf_token, borrow_id, incident_type, subject,
     * description, incident_date, incident_time, estimated_damage_amount.
     * Optional multi-file upload: photos[].
     *
     * On success, redirects to the dashboard with a flash notice.
     * On failure, redirects back to the create form with errors and sticky values.
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId    = (int) $_SESSION['user_id'];
        $borrowId  = (int) ($_POST['borrow_id'] ?? 0);
        $typeId    = (int) ($_POST['incident_type'] ?? 0);
        $subject   = trim($_POST['subject'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $date      = trim($_POST['incident_date'] ?? '');
        $time      = trim($_POST['incident_time'] ?? '');
        $amount    = trim($_POST['estimated_damage_amount'] ?? '');

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $oldInput = [
            'incident_type'           => $typeId,
            'subject'                 => $subject,
            'description'             => $desc,
            'incident_date'           => $date,
            'incident_time'           => $time,
            'estimated_damage_amount' => $amount,
        ];

        $errors = $this->validateIncidentInput($typeId, $subject, $desc, $date, $time, $amount);

        $hasPhotos    = $this->hasUploadedPhotos();
        $photoErrors  = $hasPhotos ? $this->validatePhotos() : [];

        if ($photoErrors !== []) {
            $errors['photos'] = $photoErrors['photos'];
        }

        if ($errors !== []) {
            $_SESSION['incident_errors'] = $errors;
            $_SESSION['incident_old']    = $oldInput;
            $this->redirect('/incidents/create/' . $borrowId);
        }

        try {
            $borrow = Borrow::findById($borrowId);
        } catch (\Throwable $e) {
            error_log('IncidentController::store borrow lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        $isBorrower = (int) $borrow['borrower_id'] === $userId;
        $isLender   = (int) $borrow['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        try {
            if (Incident::hasOpenIncident($borrowId)) {
                $_SESSION['incident_errors'] = ['general' => 'An open incident already exists for this transaction.'];
                $this->redirect('/incidents/create/' . $borrowId);
            }
        } catch (\Throwable $e) {
            error_log('IncidentController::store duplicate check — ' . $e->getMessage());
        }

        $occurredAt     = $date . ' ' . $time . ':00';
        $damageAmount   = $amount !== '' ? $amount : null;
        $photoFilenames = [];

        if ($hasPhotos) {
            $photoFilenames = $this->movePhotos();

            if ($photoFilenames === []) {
                $_SESSION['incident_errors'] = ['photos' => 'Failed to save the uploaded photos. Please try again.'];
                $_SESSION['incident_old']    = $oldInput;
                $this->redirect('/incidents/create/' . $borrowId);
            }
        }

        try {
            Incident::create(
                borrowId: $borrowId,
                reporterId: $userId,
                incidentTypeId: $typeId,
                subject: $subject,
                description: $desc,
                occurredAt: $occurredAt,
                estimatedDamageAmount: $damageAmount,
                photoFilenames: $photoFilenames,
            );

            $_SESSION['incident_success'] = 'Your incident report has been filed. An admin will review it shortly.';
            $this->redirect('/dashboard');
        } catch (\Throwable $e) {
            error_log('IncidentController::store — ' . $e->getMessage());

            $this->cleanupPhotos($photoFilenames);

            $_SESSION['incident_errors'] = ['general' => 'Something went wrong filing your report. Please try again.'];
            $_SESSION['incident_old']    = $oldInput;
            $this->redirect('/incidents/create/' . $borrowId);
        }
    }

    /**
     * Validate all non-file form fields.
     *
     * @return array  Field-keyed error messages (empty if valid)
     */
    private function validateIncidentInput(
        int $typeId,
        string $subject,
        string $desc,
        string $date,
        string $time,
        string $amount,
    ): array {
        $errors = [];

        if ($typeId < 1) {
            $errors['incident_type'] = 'Please select an incident type.';
        }

        if ($subject === '') {
            $errors['subject'] = 'A subject is required.';
        } elseif (mb_strlen($subject) > 255) {
            $errors['subject'] = 'Subject must be 255 characters or fewer.';
        }

        if ($desc === '') {
            $errors['description'] = 'Please describe the incident.';
        } elseif (mb_strlen($desc) > 5000) {
            $errors['description'] = 'Description must be 5,000 characters or fewer.';
        }

        if ($date === '') {
            $errors['incident_date'] = 'The date of the incident is required.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            $errors['incident_date'] = 'Please enter a valid date.';
        } elseif ($date > date('Y-m-d')) {
            $errors['incident_date'] = 'The incident date cannot be in the future.';
        }

        if ($time === '') {
            $errors['incident_time'] = 'The time of the incident is required.';
        } elseif (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            $errors['incident_time'] = 'Please enter a valid time.';
        }

        if ($amount !== '') {
            $numericAmount = (float) $amount;
            if ($numericAmount < 0) {
                $errors['estimated_damage_amount'] = 'Damage amount cannot be negative.';
            } elseif ($numericAmount > 99999.99) {
                $errors['estimated_damage_amount'] = 'Damage amount must be $99,999.99 or less.';
            }
        }

        return $errors;
    }

    /**
     * Check whether any photo files were submitted with the form.
     */
    private function hasUploadedPhotos(): bool
    {
        return isset($_FILES['photos'])
            && is_array($_FILES['photos']['error'])
            && array_any(
                $_FILES['photos']['error'],
                static fn(int $err): bool => $err !== UPLOAD_ERR_NO_FILE,
            );
    }

    /**
     * Validate uploaded photo files (MIME type, size, count).
     *
     * @return array  Keyed error messages (empty if valid)
     */
    private function validatePhotos(): array
    {
        $files  = $_FILES['photos'];
        $count  = 0;

        foreach ($files['error'] as $i => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $count++;

            if ($count > self::MAX_PHOTOS) {
                return ['photos' => 'You may upload a maximum of ' . self::MAX_PHOTOS . ' photos.'];
            }

            if ($error !== UPLOAD_ERR_OK) {
                return ['photos' => 'Photo upload failed. Please try again.'];
            }

            if ($files['size'][$i] > self::MAX_IMAGE_BYTES) {
                return ['photos' => 'Each photo must be 5 MB or smaller.'];
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($files['tmp_name'][$i]);

            if (!in_array($mime, self::ALLOWED_MIMES, true)) {
                return ['photos' => 'Photos must be JPEG, PNG, or WebP files.'];
            }
        }

        return [];
    }

    /**
     * Move validated photos to the uploads directory.
     *
     * @return array<string>  Filenames that were successfully moved
     */
    private function movePhotos(): array
    {
        $files     = $_FILES['photos'];
        $filenames = [];

        foreach ($files['error'] as $i => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($files['tmp_name'][$i]);
            $ext   = self::MIME_EXTENSIONS[$mime] ?? 'jpg';

            $filename    = uniqid('incident_', true) . '.' . $ext;
            $destination = BASE_PATH . '/public/uploads/incidents/' . $filename;

            if (!move_uploaded_file($files['tmp_name'][$i], $destination)) {
                $this->cleanupPhotos($filenames);
                return [];
            }

            $filenames[] = $filename;
        }

        return $filenames;
    }

    /**
     * Remove uploaded photo files from disk (cleanup on failure).
     *
     * @param  array<string> $filenames  Filenames to delete
     */
    private function cleanupPhotos(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $path = BASE_PATH . '/public/uploads/incidents/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
