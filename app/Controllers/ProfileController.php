<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\AuditLog;
use App\Models\User;

final class ProfileController extends Controller
{
    public function show(string $id = ''): void
    {
        $actor = Auth::user();
        if (!$actor) {
            $this->redirect('/login');
        }

        [$profile, $canEdit, $canManageUsers, $isForbidden] = $this->resolveTarget($actor, $id);
        if ($isForbidden) {
            http_response_code(403);
            $this->view('layouts/error', [
                'title' => 'Forbidden',
                'message' => 'You are not allowed to view this profile.',
            ]);
            return;
        }

        if (!$profile) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Profile not found',
                'message' => 'That user profile could not be found.',
            ]);
            return;
        }

        $this->renderProfile($actor, $profile, $canEdit, $canManageUsers, [], [], '');
    }

    public function update(string $id = ''): void
    {
        $actor = Auth::user();
        if (!$actor) {
            $this->redirect('/login');
        }

        [$profile, $canEdit, $canManageUsers, $isForbidden] = $this->resolveTarget($actor, $id);
        if ($isForbidden || !$canEdit) {
            http_response_code(403);
            $this->view('layouts/error', [
                'title' => 'Forbidden',
                'message' => 'You are not allowed to edit this profile.',
            ]);
            return;
        }

        if (!$profile) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Profile not found',
                'message' => 'That user profile could not be found.',
            ]);
            return;
        }

        if (!Csrf::isValid($this->input('csrf_token', ''))) {
            http_response_code(422);
            $this->renderProfile($actor, $profile, $canEdit, $canManageUsers, ['form' => 'Invalid form token. Please refresh and try again.'], [], '');
            return;
        }

        $name = trim((string) $this->input('name', ''));
        $email = strtolower(trim((string) $this->input('email', '')));
        $submittedRoles = $_POST['roles'] ?? [];
        $submittedRoles = is_array($submittedRoles) ? $submittedRoles : [$submittedRoles];
        $roles = User::normalizeRoles($submittedRoles);

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $userModel = new User();
        $existing = $email !== '' ? $userModel->findByEmail($email) : null;
        if ($existing && (int) $existing['id'] !== (int) $profile['id']) {
            $errors['email'] = 'That email is already in use.';
        }

        $canUpdateRoles = $canManageUsers;
        if (!$canUpdateRoles) {
            $roles = User::rolesFromUser($profile);
        }

        if ($errors) {
            $this->renderProfile($actor, $profile, $canEdit, $canManageUsers, $errors, ['name' => $name, 'email' => $email, 'roles' => $roles], '');
            return;
        }

        $old = [
            'name' => $profile['name'],
            'email' => $profile['email'],
            'roles' => User::rolesFromUser($profile),
        ];

        $userModel->updateProfile((int) $profile['id'], $name, $email, $canUpdateRoles ? $roles : null);

        (new AuditLog())->record(
            'profile_updated',
            'user',
            (int) $profile['id'],
            $old,
            [
                'name' => $name,
                'email' => $email,
                'roles' => $canUpdateRoles ? $roles : User::rolesFromUser($profile),
            ]
        );

        $refreshed = $userModel->find((int) $profile['id']) ?: $profile;
        $this->renderProfile($actor, $refreshed, $canEdit, $canManageUsers, [], [], 'Profile updated.');
    }

    public function updatePassword(string $id = ''): void
    {
        $actor = Auth::user();
        if (!$actor) {
            $this->redirect('/login');
        }

        [$profile, $canEdit, $canManageUsers, $isForbidden] = $this->resolveTarget($actor, $id);
        if ($isForbidden || !$canEdit) {
            http_response_code(403);
            $this->view('layouts/error', [
                'title' => 'Forbidden',
                'message' => 'You are not allowed to change this password.',
            ]);
            return;
        }

        if (!$profile) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Profile not found',
                'message' => 'That user profile could not be found.',
            ]);
            return;
        }

        if (!Csrf::isValid($this->input('csrf_token', ''))) {
            http_response_code(422);
            $this->renderProfile($actor, $profile, $canEdit, $canManageUsers, ['password_form' => 'Invalid form token. Please refresh and try again.'], [], '');
            return;
        }

        $currentPassword = (string) $this->input('current_password', '');
        $newPassword = (string) $this->input('new_password', '');
        $confirmPassword = (string) $this->input('confirm_password', '');

        $errors = [];

        $userModel = new User();
        if ((int) $actor['id'] === (int) $profile['id']) {
            if (!$userModel->verifyPassword($profile, $currentPassword)) {
                $errors['current_password'] = 'Current password is incorrect.';
            }
        }

        if (strlen($newPassword) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Password confirmation does not match.';
        }

        if ($errors) {
            $this->renderProfile($actor, $profile, $canEdit, $canManageUsers, $errors, [], '');
            return;
        }

        $userModel->updatePassword((int) $profile['id'], $newPassword);

        (new AuditLog())->record(
            'profile_password_updated',
            'user',
            (int) $profile['id'],
            null,
            ['by_user_id' => (int) $actor['id']]
        );

        $refreshed = $userModel->find((int) $profile['id']) ?: $profile;
        $this->renderProfile($actor, $refreshed, $canEdit, $canManageUsers, [], [], 'Password updated.');
    }

    private function resolveTarget(array $actor, string $id): array
    {
        $actorId = (int) $actor['id'];
        $isAdmin = User::hasRole($actor, 'admin');
        $targetId = $id !== '' ? (int) $id : $actorId;

        if (!$isAdmin && $targetId !== $actorId) {
            return [null, false, false, true];
        }

        $profile = (new User())->find($targetId);
        if (!$profile || (int) $profile['active'] !== 1) {
            return [null, false, $isAdmin, false];
        }

        return [$profile, true, $isAdmin, false];
    }

    private function renderProfile(
        array $actor,
        array $profile,
        bool $canEdit,
        bool $canManageUsers,
        array $errors,
        array $values,
        string $flash
    ): void {
        $this->view('layouts/app', [
            'title' => 'User Profile',
            'active' => 'settings',
            'content' => 'profile/show',
            'actor' => $actor,
            'profile' => $profile,
            'canEdit' => $canEdit,
            'canManageUsers' => $canManageUsers,
            'errors' => $errors,
            'values' => $values,
            'flash' => $flash,
            'csrfToken' => Csrf::token(),
            'availableRoles' => User::ROLE_LABELS,
        ]);
    }
}
