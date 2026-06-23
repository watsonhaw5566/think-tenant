<?php

declare(strict_types=1);

namespace think\tenant\model;

use think\Model;

class Role extends Model
{
    protected $name = 'roles';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $json = ['access'];

    public function getDataScopeTextAttr(): string
    {
        $map = [
            'all'    => '全部',
            'tenant' => '租户',
            'self'   => '仅自己',
        ];
        return $map[$this->data_scope] ?? '仅自己';
    }

    public function getDataScopeOptions(): array
    {
        return [
            ['value' => 'all', 'label' => '全部'],
            ['value' => 'tenant', 'label' => '租户'],
            ['value' => 'self', 'label' => '仅自己'],
        ];
    }
}