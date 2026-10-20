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

namespace App\Http\Service\Finance;

use App\Http\Dao\Finance\BillCategoryDao;
use App\Http\Service\Customer\ContractCategoryService;
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
 * 资金流水分类service
 * Class BillCategoryService.
 */
class BillCategoryService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(BillCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['entid', 'id as value', 'id', 'name', 'cate_no', 'sort', 'name as label', 'path', 'pid', 'types'], $sort = ['sort'], array $with = []): array
    {
        return get_tree_children($this->dao->getList($where, $field, 0, 0, $sort, $with));
    }

    /**
     * 获取财务流水分类tree型数据.
     * @param mixed $type
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getBillCateTree($type, $notId = 0)
    {
        return get_tree_children($this->dao->getList(['types' => $type, 'not_path' => $notId, 'notid' => $notId], ['name as label', 'id as value', 'pid', 'types'], 0, 0, 'id'), 'children', 'value');
    }

    /**
     * 路径整理.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getParentPath(array $path = [], int $pid = 0): array
    {
        if ($pid && !$path) {
            $info = $this->dao->get(['id' => $pid], ['id', 'path']);
            if ($info) {
                $path   = $info->getAttribute('path') ?? [];
                $path[] = $pid;
            }
        }
        return $path;
    }

    /**
     * 获取修改财务流水类别表单.
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankCateInfo = $this->dao->get($id);
        if (!$rankCateInfo) {
            throw $this->exception('修改的财务流水类别不存在');
        }
        return $this->createElementForm('修改财务流水类别', $this->getBillCateFormRule(collect($rankCateInfo->toArray())), '/ent/bill_cate/' . $id, 'PUT');
    }

    /**
     * 获取创建财务流水类别表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加财务流水类别', $this->getBillCateFormRule(collect($other)), '/ent/bill_cate');
    }

    /**
     * 保存数据.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $path = array_values(array_filter(array_map('intval', $data['path'] ?? [])));
        if ($path) {
            $data['pid'] = $path[count($path) - 1];
        }
        if (!$data['pid']) {
            $data['pid'] = 0;
        }
        if ($this->dao->count(['pid' => $data['pid'], 'name' => $data['name']])) {
            throw $this->exception('分类已存在，请勿重复添加');
        }

        if ($this->count(['pid' => $data['pid']]) >= 99) {
            throw $this->exception(($data['pid'] ? '直属' : '一级') . '分类数量到达上限');
        }

        $data['path']  = $path;
        $data['level'] = count($path) + 1;
        if ($data['level'] > 5) {
            throw $this->exception('账目分类最多为5级');
        }
        $data['cate_no'] = $this->generateNo((int)$data['pid']);
        Cache::tags('bill_category')->flush();
        return $this->dao->create($data);
    }

    /**
     * 修改数据.
     * @param int $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $path = array_values(array_filter(array_map('intval', $data['path'] ?? [])));
        if ($path) {
            $data['pid'] = $path[count($path) - 1];
        }
        if (!$data['pid']) {
            $data['pid'] = 0;
        }
        if ($data['pid'] == $id) {
            throw $this->exception('前置分类不能为自己');
        }

        $info = $this->dao->get($id, ['id', 'cate_no', 'path', 'types'])?->toArray();
        if (!$info) {
            throw $this->exception('分类信息获取异常');
        }

        if ($this->dao->count(['notid' => $id, 'pid' => $data['pid'], 'name' => $data['name'], 'types' => $info['types']])) {
            throw $this->exception('分类已存在，请勿重复添加');
        }

        if ($this->count(['pid' => $data['pid']]) >= 99) {
            throw $this->exception(($data['pid'] ? '直属' : '一级') . '分类数量到达上限');
        }

        $data['path']  = $path;
        $data['level'] = count($path) + 1;
        if ($data['level'] > 5) {
            throw $this->exception('账目分类最多为5级');
        }
        sort($data['path']) && sort($info['path']);
        unset($data['types']);

        if (!$info['cate_no'] || $data['path'] != $info['path']) {
            $data['cate_no'] = $this->generateNo((int)$data['pid']);
        }
        $res = $this->transaction(function () use ($id, $data, $info) {
            $this->dao->update($id, $data);
            $children = $this->dao->select(['path' => $id], ['id', 'pid', 'path'])?->toArray();
            foreach ($children as $child) {
                $key = array_search($id,  $child['path']);
                $childPath = array_merge($data['path'],array_slice($child['path'], $key));
                $this->dao->update($child['id'], ['path' => $childPath]);
            }
            return true;
        });
        $res && Cache::tags('bill_category')->flush();
        return true;
    }

    /**
     * 删除财务流水类别.
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(BillService::class)->count(['cate_id' => $id])) {
            throw $this->exception('该财务分类已经被使用，不可删除！');
        }

        if (app()->get(ContractCategoryService::class)->count(['bill_cate_id' => $id])) {
            throw $this->exception('该财务分类已经被使用，不可删除！');
        }
        Cache::tags('bill_category')->flush();
        return $this->dao->delete($id, $key);
    }

    /**
     * 缓存获取无限下级分类.
     */
    public function getSubCateIdByCache(int $cateId, bool $isContain = false): mixed
    {
        $data = Cache::tags('bill_category')->remember('bill_category:' . $cateId, 86400, function () use ($cateId) {
            //            return $this->dao->column(['path' => $cateId], 'id') ?: [];
            return $this->getChildIds($cateId);
        });
        $isContain && $cateId && $data[] = $cateId;
        return array_unique($data);
    }

    /**
     * 获取自己和下级信息.
     * @return array|Collection|\App\Http\Model\BaseModel[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\HigherOrderWhenProxy[]
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelfAndChild(array $where, array $field = ['id', 'name'])
    {
        $entId = 1;
        return $this->dao->search($where)->select($field)->when(isset($where['pid']) && $where['pid'], function ($query) use ($where, $field, $entId) {
            $query->union(function ($query) use ($where, $field, $entId) {
                $where['id']    = $where['pid'];
                $where['entid'] = $entId;
                unset($where['pid']);
                $query->from('bill_category')->where($where)->select($field);
            }, true);
        })->get();
    }

    /**
     * 生成分类ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function generateNo(int $pid = 0): string
    {
        $parentNo = '';
        if ($pid) {
            $info = $this->dao->get(['id' => $pid], ['id', 'name', 'path', 'cate_no']);
            if (!$info) {
                throw $this->exception('分类信息获取异常');
            }
            $parentNo = $info->cate_no;
        }

        for ($i = 0; $i < 99; ++$i) {
            $no = $parentNo . sprintf('%02d', $i + 1);
            if (!$this->dao->count(['pid' => $pid, 'cate_no' => $no])) {
                break;
            }
            $no = '';
        }
        if (!$no) {
            throw $this->exception('分类ID生成失败');
        }
        return $no;
    }

    /**
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(array $where, array $field = ['*'], array $with = [])
    {
        $info = $this->dao->get($where, $field, $with);
        if (!$info) {
            throw $this->exception('分类数据不存在');
        }
        return $info->toArray();
    }

    public function getSearchCate($cateIds, $types)
    {
        if (!$cateIds) {
            return $this->dao->select(['pid' => 0, 'types' => $types])?->toArray() ?: [];
        }
        $cats = $this->dao->select(['pid' => $cateIds, 'types' => $types])?->toArray() ?: [];
        if (!$cats) {
            $cats = $this->dao->select(['id' => $cateIds, 'types' => $types])?->toArray() ?: [];
        }
        return $cats;
    }

    public function getChildIds($parentId, $ids = [])
    {
        $cateIds = $this->dao->column(['pid' => $parentId], 'id') ?: [];
        $ids     = array_merge($ids, $cateIds);
        if ($cateIds) {
            return $this->getChildIds($cateIds, $ids);
        }
        return $ids;
    }

    /**
     * 获取财务流水类别表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getBillCateFormRule(Collection $collection)
    {
        $path = $this->getParentPath((array)$collection->get('path', []), (int)$collection->get('pid', 0)) ?: [0];
        return [
            Form::radio('types', '分类类型', (int)($collection->get('types') ?? 0))->disabled(true)->options([['value' => 1, 'label' => '收入'], ['value' => 0, 'label' => '支出']])->control([
                [
                    'value' => 0,
                    'rule'  => [
                        Form::cascader('path', '前置分类')->options(array_merge([['value' => 0, 'label' => '顶级分类']], $this->getBillCateTree(0, (int)$collection->get('id', 0))))->appendRule('value', $path)->props(['props' => ['checkStrictly' => true]])->clearable(true),
                    ],
                ], [
                    'value' => 1,
                    'rule'  => [
                        Form::cascader('path', '前置分类')->options(array_merge([['value' => 0, 'label' => '顶级分类']], $this->getBillCateTree(1, (int)$collection->get('id', 0))))->appendRule('value', $path)->props(['props' => ['checkStrictly' => true]])->clearable(true),
                    ],
                ],
            ]),
            Form::input('name', '分类名称', $collection->get('name'))->required(),
            Form::number('sort', '排序', $collection->get('sort', 0))->min(0)->max(999999)->precision(0),
        ];
    }
}
