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

namespace Tuoluojiang\Open\traits\service;

use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait ResourceServiceTrait.
 * @mixin BaseService
 */
trait ResourceServiceTrait
{
    /**
     * 保存数据.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        return $this->dao->create($data);
    }

    /**
     * 更新数据.
     * @param array|int $id
     * @return int
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        return $this->dao->update($id, $data);
    }

    /**
     * 删除数据.
     * @param int $id
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        return $this->dao->delete($id, $key);
    }

    /**
     * 修改状态
     * @param mixed $id
     * @return mixed
     */
    public function resourceShowUpdate($id, array $data)
    {
        return $this->showUpdate($id, $data);
    }
}
