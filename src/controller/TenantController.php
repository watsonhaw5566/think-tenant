<?php

declare(strict_types=1);

namespace think\tenant\controller;

use think\tenant\model\Tenant;
use think\Request;
use think\response\Json;

class TenantController
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(): Json
    {
        $name = $this->request->get('name');
        $query = Tenant::where('id', '>', 0);

        if ($name) {
            $query->whereLike('name', '%' . $name . '%');
        }

        $current  = max(1, (int)$this->request->get('current', 1));
        $pageSize = max(1, min(200, (int)$this->request->get('pageSize', 10)));

        $total = $query->count();
        $list  = $query->order('create_time', 'desc')
            ->page($current, $pageSize)
            ->select();

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => [
                'total' => $total,
                'list'  => $list,
            ],
        ]);
    }

    public function save(): Json
    {
        $data = $this->request->post();

        if (empty($data['name']) || empty($data['code'])) {
            return json([
                'errno' => 1,
                'code'  => 400,
                'msg'   => 'name and code are required',
                'data'  => null,
            ], 400);
        }

        $exists = Tenant::where('code', $data['code'])->find();
        if ($exists) {
            return json([
                'errno' => 1,
                'code'  => 400,
                'msg'   => 'tenant code already exists',
                'data'  => null,
            ], 400);
        }

        $tenant = Tenant::create($data);

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => $tenant,
        ]);
    }

    public function read(int $id): Json
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return json([
                'errno' => 1,
                'code'  => 404,
                'msg'   => 'tenant not found',
                'data'  => null,
            ], 404);
        }

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => $tenant,
        ]);
    }

    public function update(int $id): Json
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return json([
                'errno' => 1,
                'code'  => 404,
                'msg'   => 'tenant not found',
                'data'  => null,
            ], 404);
        }

        $data = $this->request->post();
        $tenant->save($data);

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => $tenant,
        ]);
    }

    public function delete(int $id): Json
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return json([
                'errno' => 1,
                'code'  => 404,
                'msg'   => 'tenant not found',
                'data'  => null,
            ], 404);
        }

        $tenant->delete();

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => null,
        ]);
    }
}