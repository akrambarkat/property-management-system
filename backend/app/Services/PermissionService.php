<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Role => permissions matrix. Kept in sync with PermissionSeeder.
     */
    private const ROLE_PERMISSIONS = [
        'super_admin' => [
            'view-settings',
            'edit-settings',
            'view-sms',
            'edit-sms-settings',
            'send-sms',
            'view-sms-logs',
            'manage-templates',
            'manage-providers',
            'export-logs',
            'manage-users',
        ],
        'employee' => [
            'view-settings',
            'view-sms',
            'view-sms-logs',
            'send-sms',
            'manage-templates',
            'export-logs',
        ],
        'guard' => [
            'view-sms-logs',
        ],
    ];

    public function allPermissions(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))));
    }

    public function permissionsForRole(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    public function userCan(User $user, string $permission): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }
        return in_array($permission, $this->permissionsForRole($user->role), true);
    }

    /**
     * Gate resolver: authorizes any of the app's known permissions by role.
     * Unknown abilities are deferred (return null) so Laravel policies can still run.
     */
    public function gateResolver(User $user, string $ability): ?bool
    {
        if (!in_array($ability, $this->allPermissions(), true)) {
            return null;
        }
        return $this->userCan($user, $ability);
    }
}
