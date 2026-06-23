<?php

declare(strict_types=1);

namespace think\tenant;

use think\Service;

class TenantService extends Service
{
    /** @var bool 确保事件监听器在常驻进程下只注册一次 */
    protected static bool $eventsRegistered = false;

    public function register(): void
    {
        $defaultConfig = require __DIR__ . '/config/tenant.php';
        $userConfig    = $this->app->config->get('tenant', []);

        $this->app->config->set(
            array_replace_recursive($defaultConfig, $userConfig),
            'tenant'
        );

        // 以共享实例方式绑定管理器
        $this->app->bind('tenant.manager', TenantManager::class);
        $this->app->bind('tenant.permission', PermissionManager::class);
        $this->app->bind(PermissionManager::class, PermissionManager::class);
    }

    public function boot(): void
    {
        $config = $this->app->config->get('tenant');
        if (!is_array($config)) {
            $config = [];
        }

        if (!empty($config['route']['enable'])) {
            $routeFile = __DIR__ . '/routes/api.php';
            if (is_file($routeFile)) {
                include $routeFile;
            }
        }

        // 常驻进程下：响应结束后自动清理上下文
        if (!empty($config['clean_on_finish']) && $this->app->exists('event') && !self::$eventsRegistered) {
            self::$eventsRegistered = true;

            $candidates = [
                'swoole.response',
                'swoole.request_end',
                'workerman.response',
                'Response',
                'response',
            ];

            $cleaner = function (): void {
                try {
                    $manager = $this->app->get('tenant.manager');
                    if ($manager instanceof TenantManager) {
                        $manager->forgetCurrentTenant();
                    }
                } catch (\Throwable) {
                }
            };

            foreach ($candidates as $eventName) {
                try {
                    $this->app->event->listen($eventName, $cleaner);
                } catch (\Throwable) {
                    // 忽略不支持的事件名
                }
            }
        }
    }
}