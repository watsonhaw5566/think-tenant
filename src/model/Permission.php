<?php

declare(strict_types=1);

namespace think\tenant\model;

use think\Model;

class Permission extends Model
{
    protected $name = 'permissions';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';
}