<?php

declare(strict_types=1);

namespace think\tenant\traits;

use think\db\BaseQuery;

trait HasSecurityScope
{
    use BelongsToTenant, HasDataScope {
        BelongsToTenant::scopeTenant as private tenantScopeOnly;
        HasDataScope::scopeDataScope as private dataScopeOnly;
    }

    protected bool $enableTenantScope = true;

    protected bool $enableDataScope = true;

    public function scopeSecurity(BaseQuery $query): void
    {
        if ($this->enableTenantScope && method_exists($this, 'tenantScopeOnly')) {
            $this->tenantScopeOnly($query);
        }

        if ($this->enableDataScope && method_exists($this, 'dataScopeOnly')) {
            $this->dataScopeOnly($query);
        }
    }

    public function scopeTenant(BaseQuery $query): void
    {
        if ($this->enableTenantScope) {
            $this->tenantScopeOnly($query);
        }
    }

    public function scopeDataScope(BaseQuery $query): void
    {
        if ($this->enableDataScope) {
            $this->dataScopeOnly($query);
        }
    }

    public function disableTenantScope(): void
    {
        $this->enableTenantScope = false;
    }

    public function enableTenantScope(): void
    {
        $this->enableTenantScope = true;
    }

    public function disableDataScope(): void
    {
        $this->enableDataScope = false;
    }

    public function enableDataScope(): void
    {
        $this->enableDataScope = true;
    }
}