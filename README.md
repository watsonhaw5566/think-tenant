# think-tenant

ThinkPHP 8 多租户 & 权限管理扩展包

> ✅ 已适配常驻进程运行环境（Swoole / Workerman）：请求级状态自动清理，不跨请求串扰。

## 功能特性

- 多租户支持（基于 `tenant_id` 字段隔离）
- RBAC 角色权限管理（角色名 + 权限节点数组 + 数据范围）
- 数据范围控制（`all` 全部 / `tenant` 租户内 / `self` 仅自己）
- 中间件自动识别租户，并在请求结束时强制清理上下文
- Trait 快速接入现有模型（可独立使用，也可组合使用）
- `HasSecurityScope` 组合 Trait，一步完成「租户 + 数据范围」双重过滤
- 通过容器统一管理 `TenantManager` / `PermissionManager`，支持依赖注入
- 可插拔的用户解析器：与 `watsonhaw/think-satoken` 默认兼容，也可自定义

## 安装

### 方式一：通过 Composer（发布到 Packagist 后）

```bash
composer require watsonhaw/think-tenant
```

### 方式二：本地开发（path repository）

在项目根目录的 `composer.json` 中添加：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "think-tenant"
        }
    ],
    "require": {
        "watsonhaw/think-tenant": "@dev"
    }
}
```

然后执行：

```bash
composer update watsonhaw/think-tenant
```

ThinkPHP 8 会自动通过 `composer.json` 的 `extra.think` 注册服务与默认配置。

## 配置

可在项目的 `config/tenant.php` 中覆盖默认配置（未提供时使用扩展包内置默认值）：

```php
return [
    // 请求头中传递的租户标识（数字 ID 或 code 字符串）
    'tenant_header' => 'X-Tenant-Id',

    // 请求参数（GET/POST）中传递的租户标识
    'tenant_param'  => 'tenant_id',

    // 路由
    'route' => [
        'enable' => true,         // 是否注册扩展包自带的 API 路由
        'prefix' => 'api',        // 路由前缀
    ],

    // 常驻进程（Swoole / Workerman）场景下：
    // true = 每次请求结束后自动调用 forgetCurrentTenant() 清理上下文
    'clean_on_finish' => true,
];
```

## 数据迁移

执行迁移创建所需数据表：

```bash
php think migrate:run
```

> 迁移文件位于 `think-tenant/database/migrations/`。若 ThinkPHP 未能识别 path 包中的迁移文件，可手动复制到项目的 `database/migrations/` 目录。

涉及的表：

- `tenants` — 租户（id, code, name, status, create_time, update_time, delete_time）
- `roles` — 角色（id, name, access[JSON 权限节点数组], data_scope, create_time, update_time, delete_time）
- `permissions` — 权限节点
- `users` — 追加 `tenant_id` 字段

## 使用

### 1. 模型中使用多租户

```php
namespace app\model;

use think\tenant\traits\BelongsToTenant;

class YourModel extends \think\Model
{
    use BelongsToTenant;

    protected string $tenantField = 'tenant_id'; // 默认值，可省略
}

// 查询时自动过滤当前租户（当前租户由 TenantMiddleware 识别，或手动调用 tenant()->setCurrentTenant($id)）
$list = YourModel::tenant()->select();
```

### 2. 模型中使用数据权限

```php
namespace app\model;

use think\tenant\traits\HasDataScope;

class YourModel extends \think\Model
{
    use HasDataScope;

    protected string $ownerField = 'user_id'; // 默认值，可省略
}

// 按当前用户的数据范围过滤（admin=全部，其余按 role.data_scope）
$list = YourModel::dataScope()->select();
```

### 3. 组合使用：租户 + 数据范围（推荐）

```php
namespace app\model;

use think\tenant\traits\HasSecurityScope;

class YourModel extends \think\Model
{
    use HasSecurityScope;

    protected string $tenantField = 'tenant_id';
    protected string $ownerField  = 'user_id';
}

// 一步完成：先过滤当前租户，再按数据范围过滤当前用户
$list = YourModel::security()->select();

// 等价于分步写法
$list = YourModel::tenant()->dataScope()->select();

// 如需临时绕过某一层
$model = new YourModel();
$model->disableTenantScope();    // 临时关闭租户过滤
$model->disableDataScope();       // 临时关闭数据范围过滤
$list = $model->db()->security()->select();
$model->enableTenantScope();      // 重新开启
```

### 4. 用户模型使用权限判断

```php
namespace app\model;

use think\tenant\traits\HasPermission;

class Users extends \think\Model
{
    use HasPermission;

    protected $role = 'admin'; // 角色名：与 roles.name 对应
}

$user = Users::find(1);
if ($user->hasPermission('user:create')) {
    // 拥有 user:create 权限节点
}

if ($user->isAdmin()) {
    // 角色为 admin 时拥有全部权限
}
```

### 5. 自定义用户解析器（推荐用于 Swoole / Workerman 场景）

默认情况下，`PermissionManager` 会尝试通过 `currentUser()` / `currentId()`（来自 `watsonhaw/think-satoken`）获取当前登录用户。若宿主项目的登录机制不同，可在服务提供者或中间件中注入自定义解析器：

```php
// 在 AppInit 或中间件中调用一次即可（可写在公共中间件里）
\think\tenant\PermissionManager::setUserResolver(function () {
    // 返回数组：必须包含 role；数据范围为 self 时还需要 id
    return [
        'id'   => 1001,
        'role' => 'editor',
    ];

    // 或返回含公有属性 id/role 的对象；未登录返回 null
});
```

### 6. 中间件

```php
use think\tenant\middleware\TenantMiddleware;
use think\tenant\middleware\PermissionMiddleware;

// 在 route/app.php 中：
Route::group(function () {
    // 需要租户识别的路由
})->middleware(TenantMiddleware::class);

Route::post('user/save', 'User/save')
    ->middleware(PermissionMiddleware::class, 'user:create');
```

`TenantMiddleware` 行为：

1. **请求前**：从 `X-Tenant-Id` 头或 `tenant_id` 参数读取租户标识（数字 → 按 ID；字符串 → 按 `code` 查找），并写入 `TenantManager`
2. **请求后**：调用 `forgetCurrentTenant()` **强制清空上下文**，避免在 Swoole / Workerman 常驻进程下跨请求串扰

### 7. 手动操作租户管理器

```php
use think\tenant\TenantManager;

$manager = app('tenant.manager');

$manager->setCurrentTenant(42);            // 按 ID 设置
$manager->setCurrentTenantByCode('acme');  // 按 code 查找并设置（未找到抛 TenantException）

$manager->getCurrentTenantId();            // 当前租户 ID
$manager->getCurrentTenant();              // 当前租户模型（每次调用重新查询）
$manager->hasTenant();                     // 是否已设置

$manager->forgetCurrentTenant();           // 清空（Swoole 场景下请务必在请求结束后调用）
```

### 8. 全局辅助函数

```php
// 获取当前租户管理器
$tenant = tenant();

// 获取当前租户 ID
$tid = tenant_id();

// 获取权限管理器
$pm = permission();

// 检查权限（基于当前登录用户的 role）
if (has_permission('user:delete')) {
    // 有权限
}
```

## API 路由

默认注册的路由（前缀 `api`，可通过 `route.enable` 关闭或通过 `route.prefix` 自定义）：

| 方法   | 路径                       | 说明           |
|--------|----------------------------|----------------|
| GET    | `/api/tenant`              | 租户列表       |
| POST   | `/api/tenant/save`         | 新增租户       |
| GET    | `/api/tenant/read/:id`     | 读取租户       |
| POST   | `/api/tenant/update/:id`   | 更新租户       |
| POST   | `/api/tenant/delete/:id`   | 删除租户       |
| GET    | `/api/role`                | 角色列表       |
| POST   | `/api/role/save`           | 新增角色       |
| GET    | `/api/role/read/:id`       | 读取角色       |
| POST   | `/api/role/update/:id`     | 更新角色       |
| POST   | `/api/role/delete/:id`     | 删除角色       |

> 路由组会自动附加 `TenantMiddleware`。

## Swoole / Workerman 运行注意事项

扩展包已为常驻进程场景做了以下处理，**使用时无需额外代码**：

- ✅ `TenantManager` 的全部状态保存在**实例属性**中（已移除 `static` 变量）
- ✅ `TenantMiddleware` 在**请求结束时自动调用 `forgetCurrentTenant()`**
- ✅ `TenantService` 监听 `swoole.response` / `workerman.response` / `Response` 事件，作为兜底清理
- ✅ `TenantManager` **不再缓存** `Tenant` 模型对象（避免模型持有 DB 连接的状态污染）
- ✅ 事件监听器使用 `static $eventsRegistered` 守卫，避免重复注册

如你自行手动调用了 `tenant()->setCurrentTenant()` 而未走 `TenantMiddleware`，**请务必在请求结束后**手动调用：

```php
tenant()?->forgetCurrentTenant();
```

## 容器绑定

`TenantService::register()` 会自动向容器注册以下绑定：

| 绑定键                     | 实际类                        | 说明                         |
|----------------------------|-------------------------------|------------------------------|
| `tenant.manager`           | `think\tenant\TenantManager`  | 租户管理器（共享实例）        |
| `tenant.permission`        | `think\tenant\PermissionManager` | 权限管理器（共享实例）     |
| `PermissionManager::class` | `think\tenant\PermissionManager` | 通过类名直接注入            |

## 目录结构

```
think-tenant/
├── src/
│   ├── TenantService.php           # 服务注册 + 容器绑定 + 路由加载 + 请求结束清理
│   ├── TenantManager.php           # 租户管理器（实例级别状态，Swoole 安全）
│   ├── PermissionManager.php       # 权限 / 数据范围管理器（支持 setUserResolver）
│   ├── helper.php                  # 全局辅助函数：tenant() / tenant_id() / permission() / has_permission()
│   ├── config/
│   │   └── tenant.php              # 默认配置
│   ├── controller/
│   │   ├── TenantController.php    # 租户 CRUD
│   │   └── RoleController.php      # 角色 CRUD
│   ├── model/
│   │   ├── Tenant.php              # 租户模型
│   │   ├── Role.php                # 角色模型
│   │   └── Permission.php          # 权限节点模型
│   ├── middleware/
│   │   ├── TenantMiddleware.php    # 租户识别中间件（请求前识别，请求后清理）
│   │   └── PermissionMiddleware.php# 权限节点校验中间件
│   ├── traits/
│   │   ├── BelongsToTenant.php     # 多租户 Trait（scopeTenant）
│   │   ├── HasDataScope.php        # 数据范围 Trait（scopeDataScope）
│   │   ├── HasPermission.php       # 权限判断 Trait（hasPermission / isAdmin）
│   │   └── HasSecurityScope.php    # 组合 Trait（tenant + dataScope，支持临时绕过）
│   ├── exception/
│   │   └── TenantException.php     # 异常类
│   └── routes/
│       └── api.php                 # 扩展包自带路由（可通过配置关闭）
├── database/
│   └── migrations/
│       ├── 20260623000001_create_tenants_table.php
│       ├── 20260623000002_create_roles_table.php
│       ├── 20260623000003_create_permissions_table.php
│       └── 20260623000004_add_tenant_to_users_table.php
├── tests/
│   ├── TenantManagerTest.php       # 基础 API 测试
│   ├── TenantIsolationTest.php     # Swoole 场景下的隔离性测试
│   ├── HasSecurityScopeTest.php    # Trait 存在性测试
│   └── PermissionManagerTest.php   # 权限管理器与用户解析器测试
├── composer.json
└── README.md
```

## License

Apache-2.0