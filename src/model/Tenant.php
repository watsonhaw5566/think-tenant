<?php

declare(strict_types=1);

namespace think\tenant\model;

use think\Model;

class Tenant extends Model
{
    protected $name = 'tenants';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    public function getStatusTextAttr(): string
    {
        return $this->status ? '正常' : '禁用';
    }
}