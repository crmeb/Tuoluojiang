<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudEvent;
use App\Http\Model\Crud\SystemCrudEventLog;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 触发器日志
 * Class SystemCrudEventLogDao.
 * @email 136327134@qq.com
 * @date 2024/3/14
 */
class SystemCrudEventLogDao extends BaseDao
{
    use JoinSearchTrait;

    /**
     * 关联搜索.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function joinSearch(array $where = [])
    {
        return $this->getJoinModel(joinL: 'event_id', joinR: 'id', type: 'left')
            ->when(isset($where['name']) && $where['name'] !== '', fn($q) => $q->where($this->getFiled('name', $this->aliasB), 'like', '%' . $where['name'] . '%'))
            ->when(isset($where['crud_id']) && $where['crud_id'] !== '', fn($q) => $q->where($this->getFiled('crud_id', $this->aliasB), $where['crud_id']));
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/3/14
     * @return string
     */
    protected function setModel()
    {
        return SystemCrudEventLog::class;
    }

    protected function setModelB(): string
    {
        return SystemCrudEvent::class;
    }
}
