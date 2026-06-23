<?php

declare(strict_types=1);

namespace think\tenant\middleware;

use think\tenant\TenantManager;
use think\Request;
use think\Response;

/**
 * 租户识别中间件
 *
 * - 请求前：从 Header / Param 读取租户标识并写入 TenantManager
 * - 请求后：**强制清空**，避免在 Swoole / Workerman 等常驻进程下发生跨请求串扰
 */
class TenantMiddleware
{
    protected TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    public function handle(Request $request, \Closure $next): Response
    {
        // 请求前：解析租户
        $config = config('tenant') ?? [];
        if (!is_array($config)) {
            $config = [];
        }

        $tenantId = null;

        $tenantHeader = $config['tenant_header'] ?? null;
        if ($tenantHeader !== null && $tenantHeader !== '') {
            $headerValue = $request->header($tenantHeader);
            if ($headerValue !== null && $headerValue !== '') {
                $tenantId = $headerValue;
            }
        }

        if ($tenantId === null) {
            $tenantParam = $config['tenant_param'] ?? null;
            if ($tenantParam !== null && $tenantParam !== '') {
                $paramValue = $request->param($tenantParam);
                if ($paramValue !== null && $paramValue !== '') {
                    $tenantId = $paramValue;
                }
            }
        }

        if ($tenantId !== null && $tenantId !== '') {
            if (is_numeric($tenantId)) {
                $this->tenantManager->setCurrentTenant((int) $tenantId);
            } else {
                try {
                    $this->tenantManager->setCurrentTenantByCode((string) $tenantId);
                } catch (\Throwable) {
                    // 未知租户编码不中断请求，交由业务层判断
                    $this->tenantManager->forgetCurrentTenant();
                }
            }
        }

        // 执行后续中间件/控制器
        $response = $next($request);

        // 请求后：清理上下文（Swoole / Workerman 常驻进程必须）
        $this->tenantManager->forgetCurrentTenant();

        return $response;
    }
}