<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------.
 */

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

use App\Http\Model\Crud\SystemCrudEvent;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class SystemCrudEventDao.
 * @email 136327134@qq.com
 * @date 2024/2/28
 */
class SystemCrudEventDao extends BaseDao
{
    use BatchSearchTrait;
    use ListSearchTrait;

    /**
     * 获取某个实体下的事件.
     * @param array|string[] $select
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    public function getEventList(int $crudId = 0, array $select = ['*'])
    {
        return $this->getModel()->orderByDesc('sort')
            ->orderBy('id')
            ->when($crudId, fn ($q) => $q->where('crud_id', $crudId))
            ->where('action', '<>', '')
            ->where('status', 1)
            ->select($select)->get()->toArray();
    }

    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2024/2/28
     */
    protected function setModel()
    {
        return SystemCrudEvent::class;
    }
}
