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

use App\Http\Model\Crud\SystemCrudSeniorSearch;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class SystemCrudSeniorSearchDao extends BaseDao
{
    use TogetherSearchTrait;

    /**
     * @return int
     * @throws BindingResolutionException
     */
    public function destroy($id)
    {
        return $this->getModel(false)::destroy($id);
    }

    protected function setModel()
    {
        return SystemCrudSeniorSearch::class;
    }
}
