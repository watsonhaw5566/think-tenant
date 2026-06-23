<?php

declare(strict_types=1);

namespace think\tenant\traits;

use think\tenant\PermissionManager;

trait HasPermission
{
    public function hasPermission(string $permission): bool
    {
        try {
            $permissionManager = app('tenant.permission');
        } catch (\Throwable) {
            return false;
        }

        if (!$permissionManager instanceof PermissionManager) {
            return false;
        }

        $role = $this->role ?? null;
        return $permissionManager->check($permission, $role === null ? null : (string) $role);
    }

    public function isAdmin(): bool
    {
        $role = $this->role ?? null;
        return $role !== null && (string) $role === 'admin';
    }
}