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

use App\Http\Dao\Position\PositionCategoryDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 职级类别.
 */
class PositionCateService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(PositionCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        return parent::getList($where, ['*', 'name as label', 'id as value']);
    }

    /**
     * 获取tree.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRankCateTree()
    {
        return $this->dao->getList([], ['name as label', 'id as value'], 0, 0, 'id');
    }

    /**
     * 获取修改职级类别表单.
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankCateInfo = $this->dao->get($id);
        if (! $rankCateInfo) {
            throw $this->exception('修改的职级类别不存在');
        }
        return $this->createElementForm('修改职级类别', $this->getRankCateFormRule(collect($rankCateInfo->toArray())), '/ent/rank_cate/' . $id, 'PUT');
    }

    /**
     * 获取创建职级类别表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加职级类别', $this->getRankCateFormRule(collect([])), '/ent/rank_cate');
    }

    /**
     * 保存数据.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($this->exists(['name' => $data['name'], 'entid' => $data['entid']])) {
            throw $this->exception('已存在相同类别！');
        }
        return $this->transaction(function () use ($data) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->create($data);
        });
    }

    /**
     * 修改数据.
     * @param int $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($this->exists(['name' => $data['name'], 'entid' => $data['entid'], 'not_id' => $id])) {
            throw $this->exception('已存在相同类别！');
        }
        return $this->transaction(function () use ($id, $data) {
            $res = $this->dao->update($id, $data);
            Cache::tags(['Rank'])->flush();
            return $res;
        });
    }

    /**
     * 删除职级类别.
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $service = app()->get(PositionService::class);
        if ($service->count(['cate_id' => $id])) {
            throw $this->exception('请先删除关联职级,再尝试删除');
        }
        if (app()->get(PositionRelationService::class)->exists(['cate_id' => $id, 'entid' => 1])) {
            throw $this->exception('请先取消职位体系图关联，再尝试删除');
        }
        return $this->transaction(function () use ($id, $key) {
            $res = $this->dao->delete($id, $key);
            Cache::tags(['Rank'])->flush();
            return $res;
        });
    }

    /**
     * 获取职级类别表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getRankCateFormRule(Collection $collection)
    {
        return [
            Form::input('name', '类别名称', $collection->get('name', ''))->required(),
        ];
    }
}
