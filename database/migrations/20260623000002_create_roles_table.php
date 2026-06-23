<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateRolesTable extends Migrator
{
    public function change(): void
    {
        $table = $this->table('roles', ['comment' => '角色']);
        $table->addColumn('name', 'string', ['default' => '', 'comment' => '角色名(唯一标识)'])
            ->addColumn('title', 'string', ['default' => '', 'comment' => '角色显示名'])
            ->addColumn('description', 'string', ['limit' => 500, 'default' => '', 'comment' => '角色描述'])
            ->addColumn('data_scope', 'string', ['limit' => 20, 'default' => 'self', 'comment' => '数据范围 all:全部 tenant:租户 self:仅自己'])
            ->addColumn('access', 'text', ['null' => true, 'comment' => '权限列表 JSON'])
            ->addColumn('status', 'integer', ['default' => 1, 'comment' => '状态'])
            ->addColumn('tenant_id', 'integer', ['default' => 0, 'comment' => '租户ID 0表示全局'])
            ->addColumn('create_time', 'datetime', ['default' => date('Y-m-d H:i:s'), 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => date('Y-m-d H:i:s'), 'comment' => '更新时间'])
            ->addColumn('delete_time', 'datetime', ['null' => true, 'default' => null, 'comment' => '删除时间'])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['tenant_id'])
            ->create();
    }
}