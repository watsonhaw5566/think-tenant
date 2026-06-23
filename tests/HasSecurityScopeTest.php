<?php

declare(strict_types=1);

namespace think\tenant\tests;

use PHPUnit\Framework\TestCase;
use function class_uses;

class HasSecurityScopeTest extends TestCase
{
    public function testTraitExists(): void
    {
        $this->assertTrue(
            trait_exists('think\tenant\traits\HasSecurityScope'),
            'HasSecurityScope trait should exist'
        );
    }

    public function testUsedTraits(): void
    {
        $expected = [
            'think\tenant\traits\BelongsToTenant',
            'think\tenant\traits\HasDataScope',
        ];

        $uses = class_uses('think\tenant\traits\HasSecurityScope');

        foreach ($expected as $trait) {
            $this->assertArrayHasKey(
                $trait,
                $uses,
                "HasSecurityScope should use {$trait}"
            );
        }
    }
}