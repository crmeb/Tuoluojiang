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

namespace Tuoluojiang\Open\traits\dao;

use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 连表搜索
 * Trait JoinSearchTrait.
 * @mixin BaseDao
 */
trait JoinSearchTrait
{
    /**
     * 主表.
     * @var string
     */
    protected $aliasA;

    /**
     * 副表B.
     * @var string
     */
    protected $aliasB;

    /**
     * 副表C.
     * @var string
     */
    protected $aliasC;

    /**
     * 副表D.
     * @var string
     */
    protected $aliasD;

    public function getAliasA(): string
    {
        return $this->aliasA;
    }

    public function getAliasB(): string
    {
        return $this->aliasB;
    }

    public function getAliasC(): string
    {
        return $this->aliasC;
    }

    public function getAliasD(): string
    {
        return $this->aliasD;
    }

    /**
     * 获取链表后的字段(多个).
     * @return array
     */
    public function getFileds(array $field, ?string $alias = null)
    {
        if (! $alias) {
            $alias = $this->aliasA;
        }
        foreach ($field as &$value) {
            $value = $alias . '.' . $value;
        }
        return $field;
    }

    /**
     * 获取链表后的字段(单个).
     * @return string
     */
    public function getFiled(string $field, ?string $alias = null)
    {
        if (! $alias) {
            $alias = $this->aliasA;
        }
        return $alias . '.' . $field;
    }

    /**
     * 设置关联模型B.
     */
    abstract protected function setModelB(): string;

    protected function setModelC(): string
    {
        return '';
    }

    protected function setModelD(): string
    {
        return '';
    }

    /**
     * 获取关联模型.
     * @param null|mixed $model
     * @return BaseModel
     * @throws BindingResolutionException
     */
    protected function getJoinModel(?string $joinL = null, ?string $joinR = null, string $condition = '=', string $type = 'inner', $model = null)
    {
        /** @var BaseModel $joinModel */
        $joinModel = app()->get($this->setModelB());
        if (! $model) {
            /** @var BaseModel $model */
            $model = app()->get($this->setModel());
        }

        $this->aliasA = $model->getTable();
        $this->aliasB = $joinModel->getTable();

        if (! $joinL) {
            $joinL = $model->getKeyName();
        }

        if (! $joinR) {
            $joinR = $joinModel->getKeyName();
        }

        if ($this->timeField) {
            $model = $model->setTimeField($this->timeField);
        }

        return $model->join($joinModel->getTable(), $this->aliasA . '.' . $joinL, $condition, $this->aliasB . '.' . $joinR, $type);
    }
}
