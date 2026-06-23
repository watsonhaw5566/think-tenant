<?php

declare(strict_types=1);

namespace think\tenant\tests;

use PHPUnit\Framework\TestCase;
use think\tenant\TenantManager;

/**
 * 模拟 Swoole/Workerman 下多请求场景：同个进程处理完一个请求后，
 * 租户信息必须被显式清理，不会污染到下一个请求。
 */
class TenantIsolationTest extends TestCase
{
    protected function tearDown(): void
    {
        (new TenantManager())->forgetCurrentTenant();
    }

    public function testInstanceStateIsolatedAfterForget(): void
    {
        // 请求 1
        $manager = new TenantManager();
        $manager->setCurrentTenant(100);
        self::assertSame(100, $manager->getCurrentTenantId());
        self::assertTrue($manager->hasTenant());

        // 中间件请求结束清理
        $manager->forgetCurrentTenant();

        // 请求 2：新的 manager 对象不应有前一次请求痕迹
        $nextManager = new TenantManager();
        self::assertNull($nextManager->getCurrentTenantId());
        self::assertFalse($nextManager->hasTenant());

        // 即使在同一 manager 上也不会泄漏
        self::assertNull($manager->getCurrentTenantId());
        self::assertFalse($manager->hasTenant());
    }

    public function testTwoRequestsUseSameSharedManagerButForgettingBetween(): void
    {
        $shared = new TenantManager();

        // 请求 A
        $shared->setCurrentTenant(1);
        self::assertSame(1, $shared->getCurrentTenantId());
        $shared->forgetCurrentTenant();

        // 请求 B（未传递租户）
        self::assertFalse($shared->hasTenant());
        self::assertNull($shared->getCurrentTenantId());

        // 请求 C：设置租户 99
        $shared->setCurrentTenant(99);
        self::assertSame(99, $shared->getCurrentTenantId());
    }
}