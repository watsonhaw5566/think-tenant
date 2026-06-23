<?php

declare(strict_types=1);

namespace think\tenant\tests;

use PHPUnit\Framework\TestCase;
use think\tenant\TenantManager;

class TenantManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        (new TenantManager())->forgetCurrentTenant();
    }

    public function testSetAndGetTenantId(): void
    {
        $manager = new TenantManager();
        $manager->setCurrentTenant(123);

        $this->assertSame(123, $manager->getCurrentTenantId());
        $this->assertTrue($manager->hasTenant());
    }

    public function testForgetTenant(): void
    {
        $manager = new TenantManager();
        $manager->setCurrentTenant(123);
        $manager->forgetCurrentTenant();

        $this->assertNull($manager->getCurrentTenantId());
        $this->assertFalse($manager->hasTenant());
    }

    public function testInitialState(): void
    {
        $manager = new TenantManager();
        $this->assertNull($manager->getCurrentTenantId());
        $this->assertFalse($manager->hasTenant());
    }
}