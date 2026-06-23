<?php

use think\facade\Route;
use think\tenant\controller\TenantController;
use think\tenant\controller\RoleController;
use think\tenant\middleware\TenantMiddleware;

$config = config('tenant') ?? [];
if (!is_array($config)) {
    $config = [];
}
$prefix = $config['route']['prefix'] ?? 'api';

Route::group($prefix, function () {
    Route::group('tenant', function () {
        Route::get('', [TenantController::class, 'index']);
        Route::post('save', [TenantController::class, 'save']);
        Route::get('read/:id', [TenantController::class, 'read']);
        Route::post('update/:id', [TenantController::class, 'update']);
        Route::post('delete/:id', [TenantController::class, 'delete']);
    });

    Route::group('role', function () {
        Route::get('', [RoleController::class, 'index']);
        Route::post('save', [RoleController::class, 'save']);
        Route::get('read/:id', [RoleController::class, 'read']);
        Route::post('update/:id', [RoleController::class, 'update']);
        Route::post('delete/:id', [RoleController::class, 'delete']);
    });
})->middleware(TenantMiddleware::class);