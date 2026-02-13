<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
use App\Models\Neighborhood;
use App\Models\ZipCode;

class AuthController extends BaseController
{
    /**
     * Show the login form.
     */
    public function showLogin(): void
    {
        if (!empty($_SESSION['logged_in'])) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [
            'title'       => 'Log In — NeighborhoodTools',
            'description' => 'Log in to your NeighborhoodTools account to borrow and lend tools in your community.',
            'pageCss'     => ['auth.css'],
            'error'       => $_SESSION['auth_error'] ?? null,
            'oldEmail'    => $_SESSION['auth_old_email'] ?? '',
        ]);

        unset($_SESSION['auth_error'], $_SESSION['auth_old_email']);
    }

    /**
     * Process login form submission.
     *
     * Validates CSRF, looks up account by email, verifies password,
     * then populates session and redirects to dashboard.
     */
    public function login(): void
    {
        $this->validateCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $honeypot = $_POST['website'] ?? '';

        // Bot detection — honeypot field should be empty
        if ($honeypot !== '') {
            $this->redirect('/login');
        }

        // Validate required fields
        if ($email === '' || $password === '') {
            $this->loginFailed(
                email: $email,
                message: 'Please enter both your email and password.',
            );
        }

        // Look up account
        $account = Account::findByEmail($email);

        if ($account === null || !Account::verifyPassword(input: $password, hash: $account['password_hash_acc'])) {
            $this->loginFailed(
                email: $email,
                message: 'Invalid email or password. Please try again.',
            );
        }

        // Check account status — only active accounts may log in
        $status = $account['account_status'];

        $statusMessage = match ($status) {
            'active'    => null,
            'pending'   => 'Your account is pending approval. Please check back soon.',
            'suspended' => 'Your account has been suspended. Please contact support.',
            default     => 'Unable to log in. Please contact support.',
        };

        if ($statusMessage !== null) {
            $this->loginFailed(email: $email, message: $statusMessage);
        }

        // Success — populate session
        $this->setSessionFromAccount($account);

        // Regenerate session ID to prevent fixation
        session_regenerate_id(delete_old_session: true);

        $this->redirect('/dashboard');
    }

    /**
     * Show the registration form.
     */
    public function showRegister(): void
    {
        if (!empty($_SESSION['logged_in'])) {
            $this->redirect('/dashboard');
        }

        try {
            $neighborhoods = Neighborhood::allGroupedByCity();
        } catch (\Throwable) {
            $neighborhoods = [];
            error_log('AuthController::showRegister — failed to load neighborhoods');
        }

        $this->render('auth/register', [
            'title'         => 'Sign Up — NeighborhoodTools',
            'description'   => 'Join NeighborhoodTools to share and borrow tools with your neighbors in the Asheville and Hendersonville areas.',
            'pageCss'       => ['auth.css'],
            'neighborhoods' => $neighborhoods,
            'errors'        => $_SESSION['register_errors'] ?? [],
            'old'           => $_SESSION['register_old'] ?? [],
        ]);

        unset($_SESSION['register_errors'], $_SESSION['register_old']);
    }

    /**
     * Process registration form submission.
     *
     * Validates input, creates account, auto-logs in, redirects to dashboard.
     */
    public function register(): void
    {
        $this->validateCsrf();

        $honeypot = $_POST['website'] ?? '';

        if ($honeypot !== '') {
            $this->redirect('/register');
        }

        $data = [
            'first_name'      => trim($_POST['first_name'] ?? ''),
            'last_name'       => trim($_POST['last_name'] ?? ''),
            'username'        => strtolower(trim($_POST['username'] ?? '')),
            'email'           => trim($_POST['email'] ?? ''),
            'password'        => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'street_address'  => trim($_POST['street_address'] ?? ''),
            'zip_code'        => trim($_POST['zip_code'] ?? ''),
            'neighborhood_id' => ($_POST['neighborhood_id'] ?? '') !== '' ? (int) $_POST['neighborhood_id'] : null,
        ];

        $errors = $this->validateRegistration($data);

        if ($errors !== []) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old'] = [
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'username'        => $data['username'],
                'email'           => $data['email'],
                'street_address'  => $data['street_address'],
                'zip_code'        => $data['zip_code'],
                'neighborhood_id' => $data['neighborhood_id'],
            ];
            $this->redirect('/register');
        }

        // Check for existing email or username
        $uniqueErrors = [];

        if (Account::findByEmail($data['email']) !== null) {
            $uniqueErrors['email'] = 'An account with this email already exists.';
        }

        if (Account::usernameExists($data['username'])) {
            $uniqueErrors['username'] = 'This username is already taken.';
        }

        if ($uniqueErrors !== []) {
            $_SESSION['register_errors'] = $uniqueErrors;
            $_SESSION['register_old'] = [
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'username'        => $data['username'],
                'email'           => $data['email'],
                'street_address'  => $data['street_address'],
                'zip_code'        => $data['zip_code'],
                'neighborhood_id' => $data['neighborhood_id'],
            ];
            $this->redirect('/register');
        }

        // Ensure ZIP code exists in database (geocode via Google API if necessary)
        try {
            ZipCode::ensureExists($data['zip_code']);
        } catch (\Throwable $e) {
            error_log('AuthController::register — ZIP geocoding failed: ' . $e->getMessage());

            $_SESSION['register_errors'] = [
                'zip_code' => 'Unable to validate ZIP code. Please verify it is a valid US ZIP code and try again.',
            ];
            $_SESSION['register_old'] = [
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'username'        => $data['username'],
                'email'           => $data['email'],
                'street_address'  => $data['street_address'],
                'zip_code'        => $data['zip_code'],
                'neighborhood_id' => $data['neighborhood_id'],
            ];
            $this->redirect('/register');
        }

        // Auto-resolve neighborhood from street address if none selected
        if ($data['neighborhood_id'] === null && $data['street_address'] !== '') {
            try {
                $data['neighborhood_id'] = Neighborhood::resolveFromAddress(
                    address: $data['street_address'],
                    zipCode: $data['zip_code'],
                );
            } catch (\Throwable $e) {
                error_log('AuthController::register — neighborhood resolution failed: ' . $e->getMessage());
            }
        }

        // Create account
        try {
            $passwordHash = password_hash(
                password: $data['password'],
                algo: PASSWORD_BCRYPT,
                options: ['cost' => 12],
            );

            $newId = Account::create([
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'username'        => $data['username'],
                'email'           => $data['email'],
                'password_hash'   => $passwordHash,
                'street_address'  => $data['street_address'] !== '' ? $data['street_address'] : null,
                'zip_code'        => $data['zip_code'],
                'neighborhood_id' => $data['neighborhood_id'],
            ]);
        } catch (\Throwable $e) {
            error_log('AuthController::register — ' . $e->getMessage());
            $_SESSION['register_errors'] = ['general' => 'Registration failed. Please try again.'];
            $_SESSION['register_old'] = [
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'username'        => $data['username'],
                'email'           => $data['email'],
                'street_address'  => $data['street_address'],
                'zip_code'        => $data['zip_code'],
                'neighborhood_id' => $data['neighborhood_id'],
            ];
            $this->redirect('/register');
        }

        // Auto-login — new accounts start as 'pending' with 'member' role
        $_SESSION['logged_in']        = true;
        $_SESSION['user_id']          = $newId;
        $_SESSION['user_name']        = $data['first_name'] . ' ' . $data['last_name'];
        $_SESSION['user_first_name']  = $data['first_name'];
        $_SESSION['user_role']        = 'member';
        $_SESSION['user_avatar']      = null;

        session_regenerate_id(delete_old_session: true);

        $this->redirect('/dashboard');
    }

    /**
     * Log the user out.
     *
     * Validates CSRF, destroys session, redirects home.
     */
    public function logout(): void
    {
        $this->validateCsrf();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                name: session_name(),
                value: '',
                expires_or_options: [
                    'expires'  => time() - 3600,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly'  => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ],
            );
        }

        session_destroy();

        $this->redirect('/');
    }

    // -------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------

    /**
     * Flash an error and redirect back to the login form.
     */
    private function loginFailed(string $email, string $message): never
    {
        $_SESSION['auth_error']     = $message;
        $_SESSION['auth_old_email'] = $email;
        $this->redirect('/login');
    }

    /**
     * Populate session variables from a verified account row.
     *
     * @param array{id_acc: int, first_name_acc: string, last_name_acc: string,
     *              role_name_rol: string, avatar: ?string} $account
     */
    private function setSessionFromAccount(array $account): void
    {
        $_SESSION['logged_in']       = true;
        $_SESSION['user_id']         = $account['id_acc'];
        $_SESSION['user_name']       = $account['first_name_acc'] . ' ' . $account['last_name_acc'];
        $_SESSION['user_first_name'] = $account['first_name_acc'];
        $_SESSION['user_role']       = $account['role_name_rol'];
        $_SESSION['user_avatar']     = $account['avatar'];
    }

    /**
     * Validate registration form data.
     *
     * @return array<string, string>  Field name => error message (empty if valid)
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if ($data['first_name'] === '') {
            $errors['first_name'] = 'First name is required.';
        } elseif (mb_strlen($data['first_name']) > 50) {
            $errors['first_name'] = 'First name must be 50 characters or fewer.';
        }

        if ($data['last_name'] === '') {
            $errors['last_name'] = 'Last name is required.';
        } elseif (mb_strlen($data['last_name']) > 50) {
            $errors['last_name'] = 'Last name must be 50 characters or fewer.';
        }

        if ($data['username'] === '') {
            $errors['username'] = 'Username is required.';
        } elseif (mb_strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters.';
        } elseif (mb_strlen($data['username']) > 30) {
            $errors['username'] = 'Username must be 30 characters or fewer.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $data['username'])) {
            $errors['username'] = 'Username must start with a letter and contain only lowercase letters, numbers, and underscores.';
        }

        if ($data['email'] === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if ($data['password'] === '') {
            $errors['password'] = 'Password is required.';
        } elseif (mb_strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (mb_strlen($data['password']) > 72) {
            $errors['password'] = 'Password must be 72 characters or fewer.';
        }

        if ($data['password_confirm'] === '') {
            $errors['password_confirm'] = 'Please confirm your password.';
        } elseif ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if ($data['street_address'] !== '' && mb_strlen($data['street_address']) > 255) {
            $errors['street_address'] = 'Street address must be 255 characters or fewer.';
        }

        if ($data['zip_code'] === '') {
            $errors['zip_code'] = 'Zip code is required.';
        } elseif (!preg_match('/^\d{5}$/', $data['zip_code'])) {
            $errors['zip_code'] = 'Please enter a valid 5-digit zip code.';
        }

        return $errors;
    }
}
