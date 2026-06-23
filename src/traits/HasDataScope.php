<?php

declare(strict_types=1);

namespace think\tenant\traits;

use think\tenant\PermissionManager;
use think\db\BaseQuery;

trait HasDataScope
{
    protected string $ownerField = 'user_id';

    public function scopeDataScope(BaseQuery $query): void
    {
        try {
            $permissionManager = app('tenant.permission');
        } catch (\Throwable) {
            return;
        }

        if ($permissionManager instanceof PermissionManager) {
            $permissionManager->applyDataScope($query, $this->ownerField);
        }
    }
}