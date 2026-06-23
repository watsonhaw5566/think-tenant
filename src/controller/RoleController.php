<?php

declare(strict_types=1);

namespace think\tenant\controller;

use think\tenant\model\Role;
use think\Request;
use think\response\Json;

class RoleController
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(): Json
    {
        $name = $this->request->get('name');
        $query = Role::where('id', '>', 0);

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

        if (empty($data['name'])) {
            return json([
                'errno' => 1,
                'code'  => 400,
                'msg'   => 'name is required',
                'data'  => null,
            ], 400);
        }

        $exists = Role::where('name', $data['name'])->find();
        if ($exists) {
            return json([
                'errno' => 1,
                'code'  => 400,
                'msg'   => 'role name already exists',
                'data'  => null,
            ], 400);
        }

        if (!isset($data['data_scope']) || !in_array($data['data_scope'], ['all', 'tenant', 'self'], true)) {
            $data['data_scope'] = 'self';
        }

        if (!isset($data['access']) || !is_array($data['access'])) {
            $data['access'] = [];
        }

        $role = Role::create($data);

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => $role,
        ]);
    }

    public function read(int $id): Json
    {
        $role = Role::find($id);
        if (!$role) {
            return json([
                'errno' => 1,
                'code'  => 404,
                'msg'   => 'role not found',
                'data'  => null,
            ], 404);
        }

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => $role,
        ]);
    }

    public function update(int $id): Json
    {
        $role = Role::find($id);
        if (!$role) {
            return json([
                'errno' => 1,
                'code'  => 404,
                'msg'   => 'role not found',
                'data'  => null,
            ], 404);
        }

        $data = $this->request->post();
        if (isset($data['access']) && !is_array($data['access'])) {
            $data['access'] = [];
        }
        if (isset($data['data_scope']) && !in_array($data['data_scope'], ['all', 'tenant', 'self'], true)) {
            $data['data_scope'] = 'self';
        }

        $role->save($data);

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => $role,
        ]);
    }

    public function delete(int $id): Json
    {
        $role = Role::find($id);
        if (!$role) {
            return json([
                'errno' => 1,
                'code'  => 404,
                'msg'   => 'role not found',
                'data'  => null,
            ], 404);
        }

        $role->delete();

        return json([
            'errno' => 0,
            'code'  => 200,
            'msg'   => 'success',
            'data'  => null,
        ]);
    }
}