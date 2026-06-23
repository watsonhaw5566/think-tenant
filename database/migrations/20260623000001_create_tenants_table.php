<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateTenantsTable extends Migrator
{
    public function change(): void
    {
        $table = $this->table('tenants', ['comment' => '租户']);
        $table->addColumn('name', 'string', ['default' => '', 'comment' => '租户名称'])
            ->addColumn('code', 'string', ['default' => '', 'comment' => '租户编码(唯一标识)'])
            ->addColumn('description', 'string', ['limit' => 500, 'default' => '', 'comment' => '描述'])
            ->addColumn('status', 'integer', ['default' => 1, 'comment' => '状态 1:启用 0:禁用'])
            ->addColumn('expire_time', 'datetime', ['null' => true, 'default' => null, 'comment' => '过期时间'])
            ->addColumn('create_time', 'datetime', ['default' => date('Y-m-d H:i:s'), 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => date('Y-m-d H:i:s'), 'comment' => '更新时间'])
            ->addColumn('delete_time', 'datetime', ['null' => true, 'default' => null, 'comment' => '删除时间'])
            ->addIndex(['code'], ['unique' => true])
            ->create();
    }
}