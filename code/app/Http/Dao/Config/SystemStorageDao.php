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

use App\Http\Model\Config\SystemStorage;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\HigherOrderWhenProxy;

/**
 * Class StorageDao.
 */
class SystemStorageDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * @return BaseModel|HigherOrderWhenProxy
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        return parent::search($where, $authWhere)->when(isset($where['type']), function ($query) use ($where) {
            $query->where('type', $where['type']);
        })->where('is_delete', 0)->when(isset($where['access_key']), function ($query) use ($where) {
            $query->where('access_key', $where['access_key']);
        })->when(! empty($where['id']), function ($query) use ($where) {
            $query->where('id', $where['id']);
        });
    }

    protected function setModel(): string
    {
        return SystemStorage::class;
    }
}
