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

namespace App\Http\Service\Rank;

use App\Http\Dao\Position\PositionDao;
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
 * 企业职级
 * Class RankService.
 */
class RankService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    /**
     * RankService constructor.
     */
    public function __construct(PositionDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with + [
            'cate' => function ($query) {
                $query->select(['id', 'name']);
            },
            'card' => function ($query) {
                $query->select(['id', 'name']);
            },
        ], function ($item) {
            if ($item['alias']) {
                $item['label'] = $item['name'] . '(' . $item['alias'] . ')';
            } else {
                $item['label'] = $item['name'];
            }
            $item['value'] = $item['id'];
        });
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 创建职级获取表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加职级', $this->getRankRuleForm(collect($other)), '/ent/enterprise/rank');
    }

    /**
     * 修改职级获取表单信息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankInfo = $this->dao->get($id);
        if (! $rankInfo) {
            throw $this->exception('修改的职级不存在');
        }
        return $this->createElementForm('编辑职级', $this->getRankRuleForm(collect($rankInfo->toArray())), '/ent/enterprise/rank/' . $id, 'put');
    }

    /**
     * 创建职级.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if (! $data['cate_id']) {
            throw $this->exception('请选择职级类别');
        }
        if (isset($data['uuid']) && $data['uuid']) {
            $data['card_id'] = uuid_to_card_id($data['uuid'], $data['entid']);
        } else {
            $data['card_id'] = 0;
        }
        unset($data['uuid']);
        return $this->transaction(function () use ($data) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->create($data);
        });
    }

    /**
     * 修改职级.
     * @param int $id
     * @return bool
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if (! $data['cate_id']) {
            throw $this->exception('请选择职级类别');
        }
        if (isset($data['uuid']) && $data['uuid']) {
            $data['card_id'] = uuid_to_card_id($data['uuid'], $data['entid']);
        } else {
            $data['card_id'] = 0;
        }
        unset($data['uuid']);
        return $this->transaction(function () use ($id, $data) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->update($id, $data);
        });
    }

    /**
     * 删除.
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        /** @var RankJobService $jobService */
        $jobService = app()->get(RankJobService::class);
        /** @var RankRelationService $relationService */
        $relationService = app()->get(RankRelationService::class);
        $entid           = 1;
        $res             = $this->transaction(function () use ($id, $entid, $jobService, $relationService) {
            if (str_contains($id, ',')) {
                foreach (explode(',', $id) as $value) {
                    if ($jobService->exists(['rank_id' => $value])) {
                        throw $this->exception('请先删除关联职位,再尝试删除');
                    }
                    if ($relationService->exists(['rank_id' => $value, 'entid' => $entid])) {
                        throw $this->exception('请先取消职位体系图关联，再尝试删除');
                    }
                }
                $this->dao->delete(['id' => explode(',', $id)]);
                return true;
            }
            if ($jobService->exists(['rank_id' => $id])) {
                throw $this->exception('请先删除关联职位,再尝试删除');
            }
            if ($relationService->exists(['rank_id' => $id, 'entid' => $entid])) {
                throw $this->exception('请先取消职位体系图关联，再尝试删除');
            }
            return $this->dao->delete($id, 'id');
        });
        Cache::tags(['Rank'])->flush();
        return $res;
    }

    /**
     * 获取职级表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getRankRuleForm(Collection $collection)
    {
        $cateService = app()->get(RankCategoryService::class);
        return [
            Form::cascader('cate_id', '职级类别')
                ->options($cateService->getRankCateTree())->appendRule('value', (int) $collection->get('cate_id', 0))
                ->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])
                ->validate([Form::validateNum()->required()->message('请选择职级类别')]),
            Form::input('name', '职级名称', $collection->get('name'))->required()->maxlength(20)->showWordLimit(true),
            Form::input('alias', '职级别名', $collection->get('alias'))->required()->maxlength(20)->showWordLimit(true),
            Form::textarea('info', '职级描述', $collection->get('info'))->rows(5),
        ];
    }
}
