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

namespace App\Http\Dao\Category;

use App\Http\Model\Category\MessageCategory;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class MessageCategoryDao extends BaseDao
{
    use ListSearchTrait;
    use BatchSearchTrait;

    /**
     * 分级排序列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTierList($where, array $field = ['*']): array
    {
        return $this->search($where)->orderBy('sort', 'desc')->get($field)->toArray();
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return MessageCategory::class;
    }
}
