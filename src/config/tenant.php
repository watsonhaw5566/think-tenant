<?php

return [
    // 请求头中传递的租户标识（数字 ID 或 code）
    'tenant_header' => 'X-Tenant-Id',

    // 请求参数（GET/POST）中传递的租户标识
    'tenant_param'  => 'tenant_id',

    // 路由
    'route' => [
        'enable' => true,
        'prefix' => 'api',
    ],

    // 常驻进程（Swoole / Workerman）场景下：
    //  true  = 每次请求结束后自动调用 forgetCurrentTenant() 清理上下文
    //  false = 由业务层自行清理（若已启用 TenantMiddleware，其内部也会清理）
    'clean_on_finish' => true,
];