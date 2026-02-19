<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Borrow;
use App\Models\Notification;
use App\Models\Rating;

class RatingController extends BaseController
{
    /**
     * Display the rating form for a completed borrow.
     *
     * Both parties (lender and borrower) can rate each other. The
     * borrower can additionally rate the tool. If all applicable
     * ratings have already been submitted, redirects to dashboard.
     */
    public function show(string $borrowId): void
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
            error_log('RatingController::show lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        if ($borrow['borrow_status'] !== 'returned') {
            $_SESSION['rating_errors'] = ['general' => 'Ratings can only be submitted for completed borrows.'];
            $this->redirect('/dashboard');
        }

        $isBorrower = (int) $borrow['borrower_id'] === $userId;
        $isLender   = (int) $borrow['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        $hasRatedUser = Rating::hasUserRated($id, $userId);
        $hasRatedTool = $isBorrower ? Rating::hasToolRated($id, $userId) : true;

        if ($hasRatedUser && $hasRatedTool) {
            $_SESSION['rating_success'] = 'You have already submitted all ratings for this borrow.';
            $this->redirect('/dashboard');
        }

        $targetId   = $isBorrower ? (int) $borrow['lender_id'] : (int) $borrow['borrower_id'];
        $targetName = $isBorrower ? $borrow['lender_name'] : $borrow['borrower_name'];
        $raterRole  = $isBorrower ? 'borrower' : 'lender';

        $this->render('rating/show', [
            'title'        => 'Rate Your Experience — NeighborhoodTools',
            'description'  => 'Submit a rating for your borrow of ' . $borrow['tool_name_tol'] . '.',
            'pageCss'      => ['rating.css'],
            'borrow'       => $borrow,
            'isBorrower'   => $isBorrower,
            'targetId'     => $targetId,
            'targetName'   => $targetName,
            'raterRole'    => $raterRole,
            'hasRatedUser' => $hasRatedUser,
            'hasRatedTool' => $hasRatedTool,
        ]);
    }

    /**
     * Submit a user-to-user rating via sp_rate_user().
     *
     * Validates score 1–5 and optional review text (max 2000 chars),
     * then delegates to the SP. On success, sends a notification to
     * the rated user and redirects back to the rating page (where
     * the tool rating form may still be pending for borrowers).
     */
    public function rateUser(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId   = (int) $_SESSION['user_id'];
        $borrowId = (int) ($_POST['borrow_id'] ?? 0);
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $role     = trim($_POST['role'] ?? '');
        $score    = (int) ($_POST['user_score'] ?? 0);
        $review   = trim($_POST['user_review'] ?? '');

        if ($borrowId < 1 || $targetId < 1) {
            $this->abort(404);
        }

        if (!in_array($role, ['lender', 'borrower'], true)) {
            $this->abort(403);
        }

        $errors = [];

        if ($score < 1 || $score > 5) {
            $errors['user_score'] = 'Please select a rating between 1 and 5.';
        }

        if (mb_strlen($review) > 2000) {
            $errors['user_review'] = 'Review must be 2,000 characters or fewer.';
        }

        if ($review === '') {
            $review = null;
        }

        if ($errors !== []) {
            $_SESSION['rating_errors'] = $errors;
            $_SESSION['rating_old'] = [
                'user_score'  => $score,
                'user_review' => $review ?? '',
            ];
            $this->redirect('/rate/' . $borrowId);
        }

        try {
            $result = Rating::rateUser(
                borrowId: $borrowId,
                raterId: $userId,
                targetId: $targetId,
                role: $role,
                score: $score,
                reviewText: $review,
            );
        } catch (\Throwable $e) {
            error_log('RatingController::rateUser SP — ' . $e->getMessage());
            $_SESSION['rating_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/rate/' . $borrowId);
        }

        if ($result['error'] !== null) {
            $_SESSION['rating_errors'] = ['general' => $result['error']];
            $_SESSION['rating_old'] = [
                'user_score'  => $score,
                'user_review' => $review ?? '',
            ];
            $this->redirect('/rate/' . $borrowId);
        }

        try {
            $userName = $_SESSION['user_first_name'] ?? 'A user';
            Notification::send(
                accountId: $targetId,
                type: 'rating',
                title: 'New Rating Received',
                body: $userName . ' rated you ' . $score . '/5.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('RatingController::rateUser notification — ' . $e->getMessage());
        }

        $_SESSION['rating_success'] = 'User rating submitted.';
        $this->redirect('/rate/' . $borrowId);
    }

    /**
     * Submit a tool rating via sp_rate_tool().
     *
     * Only the borrower can rate the tool. Validates score 1–5
     * and optional review text (max 2000 chars). On success,
     * redirects to dashboard with a flash message.
     */
    public function rateTool(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId   = (int) $_SESSION['user_id'];
        $borrowId = (int) ($_POST['borrow_id'] ?? 0);
        $score    = (int) ($_POST['tool_score'] ?? 0);
        $review   = trim($_POST['tool_review'] ?? '');

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $errors = [];

        if ($score < 1 || $score > 5) {
            $errors['tool_score'] = 'Please select a rating between 1 and 5.';
        }

        if (mb_strlen($review) > 2000) {
            $errors['tool_review'] = 'Review must be 2,000 characters or fewer.';
        }

        if ($review === '') {
            $review = null;
        }

        if ($errors !== []) {
            $_SESSION['rating_errors'] = $errors;
            $_SESSION['rating_old'] = [
                'tool_score'  => $score,
                'tool_review' => $review ?? '',
            ];
            $this->redirect('/rate/' . $borrowId);
        }

        try {
            $result = Rating::rateTool(
                borrowId: $borrowId,
                raterId: $userId,
                score: $score,
                reviewText: $review,
            );
        } catch (\Throwable $e) {
            error_log('RatingController::rateTool SP — ' . $e->getMessage());
            $_SESSION['rating_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/rate/' . $borrowId);
        }

        if ($result['error'] !== null) {
            $_SESSION['rating_errors'] = ['general' => $result['error']];
            $_SESSION['rating_old'] = [
                'tool_score'  => $score,
                'tool_review' => $review ?? '',
            ];
            $this->redirect('/rate/' . $borrowId);
        }

        $_SESSION['rating_success'] = 'Tool rating submitted.';
        $this->redirect('/rate/' . $borrowId);
    }
}
