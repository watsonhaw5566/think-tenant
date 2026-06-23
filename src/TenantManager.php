<?php

declare(strict_types=1);

namespace think\tenant;

use think\tenant\exception\TenantException;
use think\tenant\model\Tenant;

class TenantManager
{
    protected ?int $currentTenantId = null;

    /**
     * 注意：不缓存 Tenant 模型对象。
     * 模型内部可能持有 DB 连接/查询状态，在 Swoole 长生命周期下不应持久化。
     */
    protected ?string $tenantCode = null;

    /**
     * 按 ID 设置当前租户
     */
    public function setCurrentTenant(int $tenantId): void
    {
        $this->currentTenantId = $tenantId;
        $this->tenantCode      = null;
    }

    /**
     * 按租户编码查找并设置当前租户
     *
     * @throws TenantException 当租户编码在数据库中不存在时抛出
     */
    public function setCurrentTenantByCode(string $code): void
    {
        try {
            $tenant = Tenant::where('code', $code)->find();
        } catch (\Throwable $e) {
            throw new TenantException('tenant lookup failed: ' . $e->getMessage(), 0, $e);
        }
        if (!$tenant) {
            throw new TenantException('tenant not found: ' . $code);
        }
        $this->tenantCode      = $code;
        $this->currentTenantId = (int) $tenant->id;
    }

    public function getCurrentTenantId(): ?int
    {
        return $this->currentTenantId;
    }

    /**
     * 按需查询并返回当前租户的模型实例（每次调用均重新查询，避免缓存 DB 状态）
     */
    public function getCurrentTenant(): ?Tenant
    {
        if ($this->currentTenantId === null) {
            return null;
        }
        try {
            return Tenant::find($this->currentTenantId) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 清除当前租户上下文（常驻进程下务必在请求结束时调用）
     */
    public function forgetCurrentTenant(): void
    {
        $this->currentTenantId = null;
        $this->tenantCode      = null;
    }

    public function hasTenant(): bool
    {
        return $this->currentTenantId !== null;
    }
}