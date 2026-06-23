# think-tenant

ThinkPHP 8 多租户 & 权限管理扩展包

## 功能特性

- 多租户支持（基于 tenant_id 字段隔离）
- RBAC 角色权限管理
- 数据范围控制（全部 / 仅自己）
- 中间件自动识别租户
- Trait 快速接入现有模型（可独立使用，也可组合使用）
- `HasSecurityScope` 组合 Trait，一步完成租户 + 数据范围双重过滤

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

## 配置

安装完成后，在 `config/tenant.php` 中自定义配置：

```php
return [
    'tenant_header' => 'X-Tenant-Id',
    'tenant_param'  => 'tenant_id',
    'route' => [
        'enable' => true,
        'prefix' => 'api',
    ],
];
```

ThinkPHP 8 会自动通过 `extra.think` 注册服务和配置。

## 数据迁移

执行迁移创建数据表：

```bash
php think migrate:run
```

> 注意：迁移文件位于 `think-tenant/database/migrations/`，需确保 ThinkPHP 能识别它们。
> 或者手动将迁移文件复制到项目的 `database/migrations/` 目录。

## 使用

### 1. 模型中使用多租户

```php
namespace app\model;

use think\tenant\traits\BelongsToTenant;

class YourModel extends \think\Model
{
    use BelongsToTenant;

    protected $tenantField = 'tenant_id';
}

// 查询时自动过滤当前租户
$list = YourModel::tenant()->select();
```

### 2. 模型中使用数据权限

```php
namespace app\model;

use think\tenant\traits\HasDataScope;

class YourModel extends \think\Model
{
    use HasDataScope;

    protected $ownerField = 'user_id';
}

// 按当前用户数据范围过滤
$list = YourModel::dataScope()->select();
```

### 3. 组合使用：租户 + 数据范围（推荐）

```php
namespace app\model;

use think\tenant\traits\HasSecurityScope;

class YourModel extends \think\Model
{
    use HasSecurityScope;

    protected $tenantField = 'tenant_id';
    protected $ownerField  = 'user_id';
}

// 一步完成：先过滤当前租户，再按数据范围过滤当前用户
$list = YourModel::security()->select();

// 等价于分步写法
$list = YourModel::tenant()->dataScope()->select();

// 如需临时绕过某一层
$model = new YourModel();
$model->disableTenantScope();     // 临时关闭租户过滤
$model->disableDataScope();        // 临时关闭数据范围过滤
$list = $model->db()->security()->select();
$model->enableTenantScope();       // 重新开启
```

### 4. 用户模型使用权限判断

```php
namespace app\model;

use think\tenant\traits\HasPermission;

class Users extends \think\Model
{
    use HasPermission;

    protected $role = 'admin';
}

// 检查权限
$user = Users::find(1);
if ($user->hasPermission('user:create')) {
    // 有创建用户权限
}

// 判断是否管理员
if ($user->isAdmin()) {
    // 管理员
}
```

### 5. 中间件

在 `route/app.php` 中使用：

```php
use think\tenant\middleware\TenantMiddleware;
use think\tenant\middleware\PermissionMiddleware;

Route::group(function () {
    // 需要租户识别的路由
})->middleware(TenantMiddleware::class);

Route::post('user/save', 'User/save')
    ->middleware(PermissionMiddleware::class, 'user:create');
```

### 6. 全局辅助函数

```php
// 获取当前租户管理器
$tenant = tenant();

// 获取当前租户 ID
$tid = tenant_id();

// 检查权限
if (has_permission('user:delete')) {
    // 有权限
}

// 获取权限管理器
$pm = permission();
```

## API 路由

默认注册的路由（前缀 `api`）：

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/tenant` | 租户列表 |
| POST | `/api/tenant/save` | 新增租户 |
| GET | `/api/tenant/read/:id` | 读取租户 |
| POST | `/api/tenant/update/:id` | 更新租户 |
| POST | `/api/tenant/delete/:id` | 删除租户 |
| GET | `/api/role` | 角色列表 |
| POST | `/api/role/save` | 新增角色 |
| GET | `/api/role/read/:id` | 读取角色 |
| POST | `/api/role/update/:id` | 更新角色 |
| POST | `/api/role/delete/:id` | 删除角色 |

## 目录结构

```
think-tenant/
├── src/
│   ├── TenantService.php          # 服务注册
│   ├── TenantManager.php           # 租户管理器
│   ├── PermissionManager.php       # 权限管理器
│   ├── helper.php                   # 全局辅助函数
│   ├── config/
│   │   └── tenant.php              # 默认配置
│   ├── controller/
│   │   ├── TenantController.php    # 租户控制器
│   │   └── RoleController.php      # 角色控制器
│   ├── model/
│   │   ├── Tenant.php              # 租户模型
│   │   ├── Role.php                # 角色模型
│   │   └── Permission.php          # 权限节点模型
│   ├── middleware/
│   │   ├── TenantMiddleware.php    # 租户识别中间件
│   │   └── PermissionMiddleware.php # 权限校验中间件
│   ├── traits/
│   │   ├── BelongsToTenant.php     # 多租户 Trait
│   │   ├── HasDataScope.php        # 数据范围 Trait
│   │   ├── HasPermission.php       # 权限判断 Trait
│   │   └── HasSecurityScope.php    # 组合 Trait（租户 + 数据范围）
│   ├── exception/
│   │   └── TenantException.php     # 异常类
│   └── routes/
│       └── api.php                 # 包自带路由
├── database/
│   └── migrations/
│       ├── 20260623000001_create_tenants_table.php
│       ├── 20260623000002_create_roles_table.php
│       ├── 20260623000003_create_permissions_table.php
│       └── 20260623000004_add_tenant_to_users_table.php
├── tests/
│   └── TenantManagerTest.php
├── composer.json
└── README.md
```

## License

Apache-2.0