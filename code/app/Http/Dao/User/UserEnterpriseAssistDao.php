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

namespace App\Http\Dao\User;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Admin\AdminInfo;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;

/**
 * Class UserEnterpriseAssistDao.
 */
class UserEnterpriseAssistDao extends BaseDao
{
    use JoinSearchTrait;

    /**
     * 设置模型.
     * @return BaseModel
     * @throws BindingResolutionException
     */
    public function getModel(bool $need = true)
    {
        if ($need) {
            return $this->getJoinModel('card_id', 'id', '=', 'left');
        }
        return parent::getModel($need);
    }

    /**
     * 搜索.
     * @param array|int|string $where 搜索条件
     * @param null|bool $authWhere 是否加入默认搜索条件
     * @return BaseModel|BuildsQueries|mixed
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        if ($authWhere === null) {
            $authWhere = $this->authWhere;
        }
        return $this->getModel()->when(isset($where['entid']), function ($query) use ($where) {
            $query->where($this->getFiled('entid'), $where['entid']);
        }, function ($query) {
            $query->where($this->getFiled('entid'), 0);
        })->when($authWhere, function ($query) {
            $query->where($this->getDefaultWhereValue());
        })->when(isset($where['pid']) && $where['pid'], function ($query) use ($where) {
            $query->whereIn($this->getFiled('id', $this->aliasB), function ($query) use ($where) {
                $query->from('assist')->where('main_id', $where['pid'])->select(['aux_id']);
            });
        });
    }

    /**
     * 获取组织架构企业用户列表.
     * @return array
     * @throws BindingResolutionException
     */
    public function getUserList(array $where, int $page, int $limit, array $with = [])
    {
        return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->with($with)->select($this->getFileds(['*']))->get()->toArray();
    }

    /**
     * 设置模型A.
     * @return mixed|string
     */
    protected function setModel()
    {
        return Admin::class;
    }

    /**
     * 设置模型B.
     */
    protected function setModelB(): string
    {
        return AdminInfo::class;
    }
}
