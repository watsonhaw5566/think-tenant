<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddTenantToUsersTable extends Migrator
{
    public function change(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $table = $this->table('users');

        if (!$table->hasColumn('tenant_id')) {
            $table->addColumn('tenant_id', 'integer', ['default' => 0, 'comment' => '租户ID'])
                ->addIndex(['tenant_id'])
                ->update();
        }
    }
}