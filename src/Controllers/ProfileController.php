<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
use App\Models\Tool;

class ProfileController extends BaseController
{
    /** Results per page — divisible by 2, 3, and 4 for grid layouts. */
    private const PER_PAGE = 12;

    /**
     * Show a user's public profile.
     *
     * Fetches profile data from account_profile_v and supplementary
     * reputation detail from user_reputation_fast_v. Listed tools are
     * paginated using the same pattern as ToolController::index().
     *
     * Privacy: sensitive fields (email, phone, street address) from
     * account_profile_v are NOT passed to the view.
     */
    public function show(string $id): void
    {
        $id = (int) $id;

        if ($id < 1) {
            $this->abort(404);
        }

        try {
            $account = Account::findById($id);
        } catch (\Throwable $e) {
            error_log('ProfileController::show — ' . $e->getMessage());
            $this->abort(500);
        }

        if ($account === null) {
            $this->abort(404);
        }

        // Hide deleted and suspended accounts from public view
        $hiddenStatuses = ['deleted', 'suspended'];

        if (in_array($account['account_status'], $hiddenStatuses, true)) {
            $this->abort(404);
        }

        // Build public-safe profile — strip sensitive fields
        $profile = [
            'id'                 => (int) $account['id_acc'],
            'full_name'          => $account['full_name'],
            'first_name'         => $account['first_name_acc'],
            'primary_image'      => $account['primary_image'],
            'image_alt_text'     => $account['image_alt_text'],
            'bio'                => $account['bio_text_abi'],
            'neighborhood'       => $account['neighborhood_name_nbh'],
            'city'               => $account['city_name_nbh'],
            'state'              => $account['state_code_sta'],
            'member_since'       => $account['created_at_acc'],
            'lender_rating'      => $account['lender_rating'],
            'borrower_rating'    => $account['borrower_rating'],
            'active_tool_count'  => (int) $account['active_tool_count'],
        ];

        // Supplementary reputation detail (rating counts, completed borrows)
        try {
            $reputation = Account::getReputation($id);
        } catch (\Throwable $e) {
            error_log('ProfileController::show (reputation) — ' . $e->getMessage());
            $reputation = null;
        }

        // Detect own-profile for future "Edit Profile" link
        $shared = $this->getSharedData();
        $isOwnProfile = $shared['isLoggedIn']
            && $shared['authUser']['id'] === $id;

        // Paginated tool listing
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $tools      = Tool::getByOwner(ownerId: $id, limit: self::PER_PAGE, offset: $offset);
            $totalTools = Tool::getCountByOwner(ownerId: $id);
        } catch (\Throwable $e) {
            error_log('ProfileController::show (tools) — ' . $e->getMessage());
            $tools      = [];
            $totalTools = 0;
        }

        $totalPages = (int) ceil($totalTools / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->render('profile/show', [
            'title'        => htmlspecialchars($profile['full_name']) . ' — NeighborhoodTools',
            'description'  => 'View ' . htmlspecialchars($profile['first_name']) . "'s profile, tools, and ratings on NeighborhoodTools.",
            'pageCss'      => ['profile.css'],
            'profile'      => $profile,
            'reputation'   => $reputation,
            'isOwnProfile' => $isOwnProfile,
            'tools'        => $tools,
            'totalTools'   => $totalTools,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
        ]);
    }

    /**
     * Show the profile edit form.
     *
     * Stub — profile editing is not yet implemented.
     */
    public function edit(): void
    {
        $this->requireAuth();

        $this->render('profile/show', [
            'title'        => 'Edit Profile — NeighborhoodTools',
            'description'  => 'Edit your NeighborhoodTools profile.',
            'pageCss'      => ['profile.css'],
            'profile'      => null,
            'reputation'   => null,
            'isOwnProfile' => true,
            'tools'        => [],
            'totalTools'   => 0,
            'page'         => 1,
            'totalPages'   => 1,
            'perPage'      => self::PER_PAGE,
            'stubMessage'  => 'Profile editing is coming soon.',
        ]);
    }

    /**
     * Process profile update submission.
     *
     * Stub — profile editing is not yet implemented.
     */
    public function update(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $_SESSION['profile_notice'] = 'Profile editing is coming soon.';
        $this->redirect('/profile/edit');
    }
}
