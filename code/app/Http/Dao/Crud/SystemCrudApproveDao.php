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

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudApprove;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class SystemCrudApproveDao.
 * @email 136327134@qq.com
 * @date 2024/2/28
 */
class SystemCrudApproveDao extends BaseDao
{
    use ListSearchTrait;

    public function idByApproveList(array $crudId)
    {
        return $this->getModel();
    }

    /**
     * 触发器获取当前实体的流程.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/21
     */
    public function getEventApproveList(int $crudId)
    {
        return $this->getModel()->where('crud_id', $crudId)->where('status', 1)->select(['id', 'name'])->get()->toArray();
    }

    /**
     * 获取当前实体的审批.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/2
     */
    public function getCrudApprove(int $crudId, array $crudApproveId = [])
    {
        return $this->getModel()->where('crud_id', $crudId)
            ->when($crudApproveId, fn ($q) => $q->whereNotIn('id', $crudApproveId))
            ->select(['id', 'name'])->get()->toArray();
    }

    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2024/2/28
     */
    protected function setModel()
    {
        return SystemCrudApprove::class;
    }
}
