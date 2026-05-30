<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Env;
use App\Models\AuditLog;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\Mailer;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        if ((new User())->count() === 0) {
            $this->redirect('/setup');
        }
        $this->renderLogin('', '', false);
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $email = strtolower(trim((string) $this->input('email', '')));
        $password = (string) $this->input('password', '');
        $userModel = new User();
        $user = $email !== '' ? $userModel->findByEmail($email) : null;

        if (!$user || (int) $user['active'] !== 1 || !$userModel->verifyPassword($user, $password)) {
            // Failed attempt: re-render with the Forgot Password link visible
            // and the email pre-filled so the user can trigger a reset with
            // one click.
            $this->renderLogin('Invalid email or password.', $email, true);
            return;
        }

        Auth::login((int) $user['id']);
        $userModel->recordLogin((int) $user['id']);

        (new AuditLog())->record('user_logged_in', 'user', (int) $user['id'], null, [
            'email' => $user['email'],
        ]);

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $userId = Auth::userId();
        if ($userId) {
            (new AuditLog())->record('user_logged_out', 'user', $userId, null, null);
        }
        Auth::logout();
        $this->redirect('/login');
    }

    public function showSetup(): void
    {
        if ((new User())->count() > 0) {
            $this->redirect('/login');
        }
        $this->renderSetup([], []);
    }

    public function setup(): void
    {
        if ((new User())->count() > 0) {
            $this->redirect('/login');
        }

        $data = [
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'name' => trim((string) $this->input('name', '')),
            'password' => (string) $this->input('password', ''),
            'password_confirm' => (string) $this->input('password_confirm', ''),
        ];

        $errors = [];
        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address';
        }
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Passwords do not match';
        }
        if ($data['name'] === '') {
            $errors['name'] = 'Enter your name';
        }

        if ($errors) {
            $this->renderSetup($errors, $data);
            return;
        }

        $userModel = new User();
        $userId = $userModel->create($data['email'], $data['password'], $data['name'], 'admin');
        Auth::login($userId);
        $userModel->recordLogin($userId);

        (new AuditLog())->record('user_created', 'user', $userId, null, [
            'email' => $data['email'],
            'role' => 'admin',
            'via' => 'first_run_setup',
        ]);

        $this->redirect('/dashboard');
    }

    /**
     * Handle the "Forgot password?" click from the login page.
     *
     * Receives the email that was just typed into the (failed) login form,
     * emails a reset link if that email belongs to an active user, and shows
     * the same confirmation page either way so we don't leak which addresses
     * are registered.
     */
    public function requestPasswordReset(): void
    {
        $email = strtolower(trim((string) $this->input('email', '')));
        $emailValid = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        if ($emailValid) {
            $user = (new User())->findByEmail($email);
            if ($user && (int) $user['active'] === 1) {
                $rawToken = PasswordResetToken::generateRawToken();
                (new PasswordResetToken())->issue(
                    (int) $user['id'],
                    $rawToken,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );

                (new AuditLog())->record('password_reset_requested', 'user', (int) $user['id'], null, [
                    'email' => $user['email'],
                ]);

                $resetUrl = $this->buildResetUrl($rawToken);
                $name = trim((string) ($user['name'] ?? '')) !== '' ? $user['name'] : $user['email'];
                $body = "Hello {$name},\n\n"
                    . "We received a request to reset the password for your Solo Roadside account ({$user['email']}).\n\n"
                    . "Click the link below within the next hour to choose a new password:\n\n"
                    . $resetUrl . "\n\n"
                    . "If you didn't request this, you can ignore this email — your password won't change.\n";

                (new Mailer())->send(
                    $user['email'],
                    'Reset your Solo Roadside password',
                    $body
                );
            }
        }

        $this->view('layouts/blank', [
            'title' => 'Check your email',
            'content' => 'auth/forgot-password',
            'email' => $email,
        ]);
    }

    public function showResetPassword(): void
    {
        $token = (string) $this->query('token', '');
        if ($token === '' || !(new PasswordResetToken())->findActiveByRaw($token)) {
            $this->view('layouts/blank', [
                'title' => 'Reset link invalid',
                'content' => 'auth/reset-password-invalid',
            ]);
            return;
        }
        $this->renderResetForm($token, [], '');
    }

    public function applyPasswordReset(): void
    {
        $token = (string) $this->input('token', '');
        // Don't trim — preserve any leading/trailing spaces the user actually wants.
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        $tokens = new PasswordResetToken();
        $tokenRow = $tokens->findActiveByRaw($token);

        if (!$tokenRow) {
            $this->view('layouts/blank', [
                'title' => 'Reset link invalid',
                'content' => 'auth/reset-password-invalid',
            ]);
            return;
        }

        $errors = [];
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match';
        }
        if ($errors) {
            $this->renderResetForm($token, $errors, '');
            return;
        }

        $userId = (int) $tokenRow['user_id'];
        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user || (int) $user['active'] !== 1) {
            // User vanished or was deactivated between issue and use; treat
            // exactly like an expired link.
            $tokens->markUsed((int) $tokenRow['id']);
            $this->view('layouts/blank', [
                'title' => 'Reset link invalid',
                'content' => 'auth/reset-password-invalid',
            ]);
            return;
        }

        $userModel->updatePassword($userId, $password);
        $tokens->markUsed((int) $tokenRow['id']);

        (new AuditLog())->record('password_reset_completed', 'user', $userId, null, [
            'via' => 'forgot_password_email',
        ]);

        Auth::login($userId);
        $userModel->recordLogin($userId);

        $this->redirect('/dashboard');
    }

    private function renderLogin(string $error, string $email, bool $showForgotLink): void
    {
        $this->view('layouts/blank', [
            'title' => 'Sign in',
            'content' => 'auth/login',
            'error' => $error,
            'email' => $email,
            'showForgotLink' => $showForgotLink,
        ]);
    }

    private function renderSetup(array $errors, array $values): void
    {
        $this->view('layouts/blank', [
            'title' => 'Set Up First Operator',
            'content' => 'auth/setup',
            'errors' => $errors,
            'values' => $values,
        ]);
    }

    private function renderResetForm(string $token, array $errors, string $error): void
    {
        $this->view('layouts/blank', [
            'title' => 'Choose a new password',
            'content' => 'auth/reset-password',
            'token' => $token,
            'errors' => $errors,
            'error' => $error,
        ]);
    }

    private function buildResetUrl(string $rawToken): string
    {
        // Prefer the live request origin so the link uses whatever
        // scheme/host/port the user actually hit. Fall back to APP_URL only
        // if the request didn't carry a Host header (e.g. CLI tests).
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || (($_SERVER['SERVER_PORT'] ?? '') === '443');
            $origin = ($isHttps ? 'https' : 'http') . '://' . $host;
        } else {
            $origin = rtrim((string) (Env::get('APP_URL') ?? 'http://localhost'), '/');
        }

        return $origin . '/reset-password?token=' . urlencode($rawToken);
    }
}
