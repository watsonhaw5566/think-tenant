<?php

declare(strict_types=1);

namespace think\tenant\middleware;

use think\tenant\PermissionManager;
use think\Request;
use think\Response;

class PermissionMiddleware
{
    protected PermissionManager $permissionManager;

    public function __construct(PermissionManager $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    public function handle(Request $request, \Closure $next, ?string $permission = null): Response
    {
        if ($permission !== null && $permission !== '' && !$this->permissionManager->check($permission)) {
            return json([
                'errno' => 403,
                'code'  => 403,
                'msg'   => '无权访问',
                'data'  => null,
            ], 403);
        }

        return $next($request);
    }
}