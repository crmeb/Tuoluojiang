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

namespace App\Http\Dao\Config;

use App\Http\Model\Config\DictData;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 字典数据
 * Class GroupDao.
 */
class DictDataDao extends BaseDao
{
    use ListSearchTrait;
    use TogetherSearchTrait;
    use BatchSearchTrait;

    /**
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/3
     */
    public function idByValues(int $typeId, array $ids = [])
    {
        return $this->getModel()->where('type_id', $typeId)
            ->when($ids, function ($query, $ids) {
                $query->whereIn('id', $ids);
            })
            ->where('status', 1)->select(['name', 'id', 'value', 'color'])->get()->toArray();
    }

    /**
     * 使用IDS获取数据.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/7
     */
    public function idsByValues(array $ids)
    {
        return $this->getModel()->whereIn('id', $ids)
            ->where('status', 1)->select(['name', 'id', 'value'])->get()->toArray();
    }

    /**
     * 设置模型.
     * @return mixed|string
     */
    protected function setModel()
    {
        return DictData::class;
    }
}
