<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePermissionsTable extends Migrator
{
    public function change(): void
    {
        $table = $this->table('permissions', ['comment' => '权限节点']);
        $table->addColumn('name', 'string', ['default' => '', 'comment' => '权限名'])
            ->addColumn('code', 'string', ['default' => '', 'comment' => '权限编码(如 user:create)'])
            ->addColumn('module', 'string', ['default' => '', 'comment' => '所属模块'])
            ->addColumn('description', 'string', ['limit' => 500, 'default' => '', 'comment' => '描述'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('status', 'integer', ['default' => 1, 'comment' => '状态'])
            ->addColumn('create_time', 'datetime', ['default' => date('Y-m-d H:i:s'), 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => date('Y-m-d H:i:s'), 'comment' => '更新时间'])
            ->addColumn('delete_time', 'datetime', ['null' => true, 'default' => null, 'comment' => '删除时间'])
            ->addIndex(['code'], ['unique' => true])
            ->create();
    }
}