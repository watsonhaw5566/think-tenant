<?php

declare(strict_types=1);

namespace think\tenant\tests;

use PHPUnit\Framework\TestCase;
use think\tenant\PermissionManager;

/**
 * 验证 PermissionManager 在无宿主框架依赖时能正常工作
 * （通过自定义 setUserResolver 替代 currentUser/currentId）
 */
class PermissionManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        PermissionManager::setUserResolver(function (): ?object {
            return null;
        });
    }

    public function testCheckWithoutUserReturnsFalse(): void
    {
        $manager = new PermissionManager();
        self::assertFalse($manager->check('user:create'));
    }

    public function testCheckAdminRoleAlwaysTrue(): void
    {
        PermissionManager::setUserResolver(function (): object {
            return (object) ['id' => 1, 'role' => 'admin'];
        });

        $manager = new PermissionManager();
        self::assertTrue($manager->check('anything:goes'));
    }

    public function testGetDataScopeDefaultsToSelf(): void
    {
        $manager = new PermissionManager();
        self::assertSame('self', $manager->getDataScope());
    }

    public function testUserResolverReturnsArray(): void
    {
        PermissionManager::setUserResolver(function (): array {
            return ['id' => 42, 'role' => 'editor'];
        });

        $manager = new PermissionManager();
        self::assertFalse($manager->check('unknown:node'));
    }
}