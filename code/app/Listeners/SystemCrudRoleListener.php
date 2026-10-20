<?php

namespace App\Listeners;

use App\Http\Service\Admin\AdminService;
use App\Http\Service\Crud\SystemCrudRoleService;

/**
 * 创建角色权限
 */
class SystemCrudRoleListener
{
    /**
     * 创建角色权限
     * @param $adminId
     * @param $id
     * @param $tableName
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle($adminId, $id, $tableName)
    {
        $roles = app()->make(AdminService::class)->value($adminId, 'roles');
        $data = [];
        foreach ($roles as $roleid) {
            $data[] = [
                'role_id'        => $roleid,
                'crud_id'        => $id,
                'crud_name'      => $tableName,
                'created'        => 1,
                'reade'          => 4,
                'reade_frame'    => '[]',
                'updated'        => 4,
                'updated_frame'  => '[]',
                'deleted'        => 4,
                'deleted_frame'  => '[]',
                'transfer'       => 4,
                'transfer_frame' => '[]',
                'share'          => 4,
                'share_frame'    => '[]',
            ];
        }
        app()->make(SystemCrudRoleService::class)->insert($data);
    }
}
