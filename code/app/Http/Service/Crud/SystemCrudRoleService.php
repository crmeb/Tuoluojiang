<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudRoleDao;
use App\Http\Service\BaseService;

/**
 * 实体数据权限.
 */
class SystemCrudRoleService extends BaseService
{
    public function __construct(SystemCrudRoleDao $dao)
    {
        $this->dao = $dao;
    }

    public function saveRoles($roleId, $data)
    {
        if (!$roleId) {
            throw $this->exception('缺少权限ID');
        }
        foreach ($data as $value) {
            $this->dao->updateOrCreate(['role_id' => $roleId, 'crud_id' => $value['crud_id']], [
                'role_id'        => $roleId,
                'crud_id'        => $value['crud_id'],
                'created'        => (int)$value['created'],
                'reade'          => (int)$value['reade'],
                'reade_frame'    => json_encode($value['reade_frame']),
                'updated'        => (int)$value['updated'],
                'updated_frame'  => json_encode($value['updated_frame']),
                'deleted'        => (int)$value['deleted'],
                'deleted_frame'  => json_encode($value['deleted_frame']),
                'transfer'       => (int)($value['transfer'] ?? 0),
                'transfer_frame' => json_encode($value['transfer_frame'] ?? []),
                'share'          => (int)($value['share'] ?? 0),
                'share_frame'    => json_encode($value['share_frame'] ?? []),
            ]);
        }
        return true;
    }
}
