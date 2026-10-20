<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Dao\Customer;

use App\Http\Model\Customer\File;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class FileDao extends BaseDao
{
    use ListSearchTrait;

    public function setModel(): string
    {
        return File::class;
    }

    /**
     * 修改图片分类.
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function move(array $data)
    {
        $where['id'] = $data['images'];
        return $this->search($where)->update(['cid' => $data['cid']]);
    }

    /**
     * 修改附件关联.
     * @param mixed $where
     * @param mixed $value
     * @param mixed $key
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateRelation($where, $value, $key)
    {
        return $this->search($where)->update([$key => $value]);
    }

    public function sumSize($entId)
    {
        return $this->getModel(false)->where('entid', $entId)->sum('att_size');
    }
}
