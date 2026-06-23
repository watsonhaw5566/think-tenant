<?php

declare(strict_types=1);

namespace think\tenant;

use think\tenant\model\Role;
use think\db\BaseQuery;

class PermissionManager
{
    /**
     * 当前用户解析器（可在宿主项目中替换，实现与 think-satoken 等框架解耦）
     *
     * @var callable|null 返回格式: [id => int|string, role => string] 或返回一个含 id/role 属性的对象
     */
    protected static $userResolver = null;

    /**
     * 设置当前用户解析器。返回值可以是：
     *   - 含 `id`、`role` 的对象（实现 __get / 公有属性）
     *   - 数组 ['id' => int|string|null, 'role' => string|null]
     *   - null（表示未登录）
     */
    public static function setUserResolver(callable $resolver): void
    {
        self::$userResolver = $resolver;
    }

    protected function resolveCurrentUser(): ?object
    {
        if (self::$userResolver !== null) {
            $result = (self::$userResolver)();
            if ($result === null) {
                return null;
            }
            if (is_array($result)) {
                return (object) $result;
            }
            if (is_object($result)) {
                return $result;
            }
            return null;
        }

        // 兼容默认：尝试调用 think-satoken 提供的 currentUser / currentId
        if (function_exists('currentUser')) {
            $user = currentUser();
            if (is_object($user) || is_array($user)) {
                return is_array($user) ? (object) $user : $user;
            }
        }
        return null;
    }

    protected function resolveCurrentId()
    {
        $user = $this->resolveCurrentUser();
        if ($user === null) {
            return function_exists('currentId') ? currentId() : null;
        }
        return $user->id ?? $user->user_id ?? null;
    }

    protected function resolveCurrentRole(): ?string
    {
        $user = $this->resolveCurrentUser();
        if ($user === null) {
            return null;
        }
        $role = $user->role ?? null;
        return $role === null ? null : (string) $role;
    }

    /**
     * 检查角色是否拥有指定权限节点
     *
     * @param string      $permission 权限节点，如 "user:create"
     * @param string|null $roleName   角色名；未传时尝试从当前登录用户读取
     */
    public function check(string $permission, ?string $roleName = null): bool
    {
        if ($roleName === null) {
            $roleName = $this->resolveCurrentRole();
            if ($roleName === null) {
                return false;
            }
        }

        if ($roleName === 'admin') {
            return true;
        }

        try {
            $role = Role::where('name', $roleName)->find();
        } catch (\Throwable) {
            // 未初始化数据库时，默认返回 false，避免在单元测试或 CLI 场景崩掉
            return false;
        }
        if (!$role) {
            return false;
        }

        $access = $role->access ?: [];
        return is_array($access) && in_array($permission, $access, true);
    }

    /**
     * 获取指定角色/当前用户的数据范围：all / tenant / self
     */
    public function getDataScope(?string $roleName = null): string
    {
        if ($roleName === null) {
            $roleName = $this->resolveCurrentRole();
            if ($roleName === null) {
                return 'self';
            }
            if ($roleName === 'admin') {
                return 'all';
            }
        }

        try {
            $role = Role::where('name', $roleName)->find();
        } catch (\Throwable) {
            return 'self';
        }
        return $role ? ($role->data_scope ?? 'self') : 'self';
    }

    /**
     * 将数据范围应用到查询构造器
     */
    public function applyDataScope(BaseQuery $query, string $ownerField = 'user_id'): BaseQuery
    {
        $scope = $this->getDataScope();
        if ($scope === 'all') {
            return $query;
        }

        if ($scope === 'tenant') {
            try {
                // 统一通过容器获取 TenantManager，避免对全局函数命名空间的误判
                $tenantManager = app('tenant.manager');
            } catch (\Throwable) {
                $tenantManager = null;
            }
            try {
                $tenantId = $tenantManager ? $tenantManager->getCurrentTenantId() : null;
                if ($tenantId !== null) {
                    $fields = $query->getModel()->getFields();
                    if (is_array($fields) && in_array('tenant_id', $fields, true)) {
                        $query->where('tenant_id', $tenantId);
                    }
                }
            } catch (\Throwable) {
            }
            return $query;
        }

        // self: 按 ownerField 过滤
        $userId = $this->resolveCurrentId();
        if ($userId === null || $userId === '') {
            return $query;
        }

        try {
            $fields = $query->getModel()->getFields();
            if (is_array($fields) && in_array($ownerField, $fields, true)) {
                $query->where($ownerField, $userId);
            }
        } catch (\Throwable) {
        }

        return $query;
    }
}