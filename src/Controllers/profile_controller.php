<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
use App\Models\Tool;

class ProfileController extends BaseController
{
    /** Results per page — divisible by 2, 3, and 4 for grid layouts. */
    private const int PER_PAGE = 12;

    private const int MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    private const array ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const array MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const array VALID_PREFERENCES = ['email', 'phone', 'both', 'app'];

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

        $hiddenStatuses = ['deleted', 'suspended'];

        if (in_array($account['account_status'], $hiddenStatuses, true)) {
            $this->abort(404);
        }

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

        try {
            $reputation = Account::getReputation($id);
        } catch (\Throwable $e) {
            error_log('ProfileController::show (reputation) — ' . $e->getMessage());
            $reputation = null;
        }

        $shared = $this->getSharedData();
        $isOwnProfile = $shared['isLoggedIn']
            && $shared['authUser']['id'] === $id;

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
     * Show the profile edit form pre-filled with the user's data.
     *
     * Pulls editable fields from account_acc, account_bio_abi,
     * account_image_aim, and contact_preference_cpr.
     */
    public function edit(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        try {
            $profile     = Account::getEditableProfile($userId);
            $preferences = Account::getContactPreferences();
            $meta        = Account::getAccountMeta($userId);
        } catch (\Throwable $e) {
            error_log('ProfileController::edit — ' . $e->getMessage());
            $this->abort(500);
        }

        if ($profile === null) {
            $this->abort(404);
        }

        $errors = $_SESSION['profile_errors'] ?? [];
        $old    = $_SESSION['profile_old'] ?? [];
        unset($_SESSION['profile_errors'], $_SESSION['profile_old']);

        $this->render('profile/edit', [
            'title'       => 'Edit Profile — NeighborhoodTools',
            'description' => 'Edit your NeighborhoodTools profile.',
            'pageCss'     => ['profile.css'],
            'profile'     => $profile,
            'preferences' => $preferences,
            'meta'        => $meta,
            'errors'      => $errors,
            'old'         => $old,
            'backUrl'     => '/profile/' . $userId,
        ]);
    }

    /**
     * Process profile update submission.
     *
     * Validates fields, handles optional avatar upload, updates the
     * account via model methods, then redirects to the public profile.
     */
    public function update(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];

        $firstName      = trim($_POST['first_name'] ?? '');
        $lastName       = trim($_POST['last_name'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $streetAddress  = trim($_POST['street_address'] ?? '');
        $zipCode        = trim($_POST['zip_code'] ?? '');
        $preference     = trim($_POST['contact_preference'] ?? '');
        $bio            = trim($_POST['bio'] ?? '');

        $oldInput = [
            'first_name'         => $firstName,
            'last_name'          => $lastName,
            'phone'              => $phone,
            'street_address'     => $streetAddress,
            'zip_code'           => $zipCode,
            'contact_preference' => $preference,
            'bio'                => $bio,
        ];

        $errors = $this->validateProfileInput($firstName, $lastName, $phone, $streetAddress, $zipCode, $preference);

        $hasImage = isset($_FILES['avatar'])
            && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasImage) {
            $imageErrors = $this->validateProfileImage($_FILES['avatar']);
            $errors = array_merge($errors, $imageErrors);
        }

        if ($errors !== []) {
            $_SESSION['profile_errors'] = $errors;
            $_SESSION['profile_old']    = $oldInput;
            $this->redirect('/profile/edit');
        }

        $imageFilename = null;

        if ($hasImage) {
            $imageFilename = $this->moveProfileImage($_FILES['avatar']);

            if ($imageFilename === null) {
                $_SESSION['profile_errors'] = ['avatar' => 'Failed to save the uploaded image. Please try again.'];
                $_SESSION['profile_old']    = $oldInput;
                $this->redirect('/profile/edit');
            }
        }

        try {
            Account::updateProfile($userId, [
                'first_name'         => $firstName,
                'last_name'          => $lastName,
                'phone'              => $phone !== '' ? $phone : null,
                'street_address'     => $streetAddress !== '' ? $streetAddress : null,
                'zip_code'           => $zipCode,
                'contact_preference' => $preference,
            ]);

            Account::upsertBio($userId, $bio !== '' ? $bio : null);

            if ($imageFilename !== null) {
                $altText = $firstName . ' ' . $lastName . "'s profile photo";

                $oldImage = Account::getPrimaryImage($userId);

                Account::saveProfileImage($userId, $imageFilename, $altText);

                if ($oldImage !== null) {
                    $oldPath = BASE_PATH . '/public/uploads/profiles/' . $oldImage;
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $_SESSION['user_avatar'] = $imageFilename;
            }

            $_SESSION['user_name']       = $firstName . ' ' . $lastName;
            $_SESSION['user_first_name'] = $firstName;
        } catch (\Throwable $e) {
            error_log('ProfileController::update — ' . $e->getMessage());

            if ($imageFilename !== null) {
                $uploaded = BASE_PATH . '/public/uploads/profiles/' . $imageFilename;
                if (is_file($uploaded)) {
                    unlink($uploaded);
                }
            }

            $_SESSION['profile_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $_SESSION['profile_old']    = $oldInput;
            $this->redirect('/profile/edit');
        }

        $_SESSION['profile_notice'] = 'Profile updated successfully.';
        $this->redirect('/profile/' . $userId);
    }

    /**
     * Validate profile form input fields.
     *
     * @return array<string, string> Error messages keyed by field name
     */
    private function validateProfileInput(
        string $firstName,
        string $lastName,
        string $phone,
        string $streetAddress,
        string $zipCode,
        string $preference,
    ): array {
        $errors = [];

        if ($firstName === '') {
            $errors['first_name'] = 'First name is required.';
        } elseif (mb_strlen($firstName) > 100) {
            $errors['first_name'] = 'First name must be 100 characters or fewer.';
        }

        if ($lastName === '') {
            $errors['last_name'] = 'Last name is required.';
        } elseif (mb_strlen($lastName) > 100) {
            $errors['last_name'] = 'Last name must be 100 characters or fewer.';
        }

        if ($phone !== '' && mb_strlen($phone) > 20) {
            $errors['phone'] = 'Phone number must be 20 characters or fewer.';
        }

        if ($streetAddress !== '' && mb_strlen($streetAddress) > 255) {
            $errors['street_address'] = 'Street address must be 255 characters or fewer.';
        }

        if ($zipCode === '') {
            $errors['zip_code'] = 'ZIP code is required.';
        } elseif (!preg_match('/^\d{5}(-\d{4})?$/', $zipCode)) {
            $errors['zip_code'] = 'Please enter a valid 5-digit ZIP code.';
        }

        if (!in_array($preference, self::VALID_PREFERENCES, true)) {
            $errors['contact_preference'] = 'Please select a valid contact preference.';
        }

        return $errors;
    }

    /**
     * Validate an uploaded avatar image file.
     *
     * @return array<string, string> Error messages (empty = valid)
     */
    private function validateProfileImage(array $file): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['avatar'] = 'Image upload failed. Please try again.';
            return $errors;
        }

        if ($file['size'] > self::MAX_IMAGE_BYTES) {
            $errors['avatar'] = 'Image must be 5 MB or smaller.';
            return $errors;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            $errors['avatar'] = 'Image must be a JPEG, PNG, or WebP file.';
        }

        return $errors;
    }

    /**
     * Move a validated avatar image to the uploads directory.
     *
     * Generates a unique filename to prevent collisions.
     */
    private function moveProfileImage(array $file): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = self::MIME_EXTENSIONS[$mime] ?? 'jpg';

        $filename    = uniqid('profile_', true) . '.' . $ext;
        $destination = BASE_PATH . '/public/uploads/profiles/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }

        return null;
    }
}
