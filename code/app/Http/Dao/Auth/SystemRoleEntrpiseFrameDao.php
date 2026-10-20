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

namespace App\Http\Dao\Auth;

use App\Http\Model\Company\Assist;
use App\Http\Model\System\Role;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;

/**
 * 权限规则关联组织架构dao
 * Class SystemRoleEntrpiseFrameDao.
 */
class SystemRoleEntrpiseFrameDao extends BaseDao
{
    use JoinSearchTrait;

    /**
     * 获取模型.
     * @return BaseModel
     * @throws BindingResolutionException
     */
    public function getModel(bool $need = true)
    {
        if ($need) {
            return $this->getJoinModel('id', 'aux_id')->where($this->getFiled('type', $this->aliasB), 'SystemRule');
        }
        return parent::getModel($need);
    }

    /**
     * 搜索.
     * @param array|int|string $where
     * @return BaseModel|BuildsQueries|mixed
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        if ($authWhere === null) {
            $authWhere = $this->authWhere;
        }

        return $this->getModel()->when($authWhere, function ($query) {
            $query->where($this->getDefaultWhereValue());
        });
    }

    /**
     * 设置主表模型.
     * @return mixed|void
     */
    protected function setModel()
    {
        return Role::class;
    }

    /**
     * 设置副表模型.
     */
    protected function setModelB(): string
    {
        return Assist::class;
    }
}
