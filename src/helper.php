<?php

use think\tenant\TenantManager;
use think\tenant\PermissionManager;

if (!function_exists('tenant')) {
    function tenant(): ?TenantManager
    {
        try {
            return app('tenant.manager');
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        $manager = tenant();
        return $manager?->getCurrentTenantId();
    }
}

if (!function_exists('permission')) {
    function permission(): ?PermissionManager
    {
        try {
            return app('tenant.permission');
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('has_permission')) {
    function has_permission(string $permission): bool
    {
        $manager = permission();
        return $manager && $manager->check($permission);
    }
}