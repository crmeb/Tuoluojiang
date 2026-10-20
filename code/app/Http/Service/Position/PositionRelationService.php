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

namespace App\Http\Service\Position;

use App\Http\Dao\Position\PositionRelationDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService as Form;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 职级体系图.
 */
class PositionRelationService extends BaseService
{
    public $dao;

    public function __construct(PositionRelationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取tree.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRankTypeTree()
    {
        return $this->dao->getList(['status' => 1], ['name as label', 'id as value'], 0, 0, 'id');
    }

    /**
     * 创建职级获取表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加职级类型', $this->getRankFormRule(collect([])), '/ent/enterprise/rankType');
    }

    /**
     * 修改职级获取表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankInfo = $this->dao->get($id);
        if (! $rankInfo) {
            throw $this->exception('修改的职级类型不存在');
        }
        return $this->createElementForm('修改职级类型', $this->getRankFormRule(collect($rankInfo->toArray())), '/ent/enterprise/rankType/' . $id, 'put');
    }

    /**
     * 修改职级类型.
     * @param int $id
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $data = array_filter($data, function ($val) {
            return (int) $val;
        });
        return $this->transaction(function () use ($id, $data) {
            Cache::tags(['Rank'])->flush();
            if (! $id) {
                $this->dao->create($data);
            } else {
                $this->dao->update($id, $data);
            }
            return true;
        });
    }

    /**
     * 删除职级类型.
     * @return mixed|void
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (! $this->dao->exists(['id' => $id])) {
            throw $this->exception('暂无删除权限！');
        }
        return $this->transaction(function () use ($id, $key) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->delete($id, $key);
        });
    }

    /**
     * 获取未使用职级.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getFreeRank($cate_id, $levelIds)
    {
        $rankIds = $this->dao->column(['cate_id' => $cate_id, 'level_id' => $levelIds], 'rank_id') ?? [];
        $data    = app()->get(PositionService::class)->select(['cate_id' => $cate_id, 'notid' => $rankIds], ['id', 'name', 'alias']);
        return $data ? $data->toArray() : [];
    }

    /**
     * 获取职级类型表单.
     * @return array
     */
    protected function getRankFormRule(Collection $collection)
    {
        return [
            Form::input('name', '职级类型', $collection->get('name')),
        ];
    }
}
