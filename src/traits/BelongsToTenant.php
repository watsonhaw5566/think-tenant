<?php

declare(strict_types=1);

namespace think\tenant\traits;

use think\tenant\TenantManager;
use think\db\BaseQuery;

trait BelongsToTenant
{
    protected string $tenantField = 'tenant_id';

    public function scopeTenant(BaseQuery $query): void
    {
        try {
            $tenantManager = app('tenant.manager');
        } catch (\Throwable) {
            return;
        }

        if ($tenantManager instanceof TenantManager && $tenantManager->hasTenant()) {
            $tenantId = $tenantManager->getCurrentTenantId();
            try {
                $fields = $this->getFields();
                if (is_array($fields) && in_array($this->tenantField, $fields, true)) {
                    $query->where($this->tenantField, $tenantId);
                }
            } catch (\Throwable) {
                // 模型未初始化字段时静默忽略
                $query->where($this->tenantField, $tenantId);
            }
        }
    }

    public function setTenantId(int $tenantId): void
    {
        $this->setAttr($this->tenantField, $tenantId);
    }
}