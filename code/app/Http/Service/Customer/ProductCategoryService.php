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

namespace App\Http\Service\Customer;

use App\Constants\CacheEnum;
use App\Http\Dao\Customer\ProductCategoryDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 产品分类Service.
 */
class ProductCategoryService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(ProductCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        return get_tree_children($list);
    }

    public function resourceCreate(array $other = []): array
    {
        if (isset($other['pid'])) {
            $other['path']   = $this->dao->value($other['pid'], 'path') ?: [];
            $other['path'][] = (int) $other['pid'];
        }
        return $this->createElementForm('新增分类', $this->getFormRule(collect($other)), '/ent/client/product/cate');
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $info = $this->dao->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('修改的分类不存在');
        }
        return $this->createElementForm('修改分类', $this->getFormRule(collect($info)), '/ent/client/product/cate/' . $id, 'PUT');
    }

    public function resourceSave(array $data)
    {
        $data['path']  = array_filter($data['path']);
        $data['level'] = count($data['path']) + 1;
        $data['pid']   = end($data['path']);
        if ($this->dao->count(['pid' => $data['pid'], 'name' => $data['name']])) {
            throw $this->exception('分类已存在，请勿重复添加');
        }
        return $this->dao->create($data) && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    public function resourceUpdate($id, array $data)
    {
        $path          = $this->dao->value($id, 'path');
        $data['path']  = array_filter($data['path']);
        $path          = implode('/', $path);
        $newPath       = implode('/', $data['path']);
        $data['level'] = count($data['path']) + 1;
        $data['pid']   = end($data['path']);
        if ($this->dao->count(['not_id' => $id, 'pid' => $data['pid'], 'name' => $data['name']])) {
            throw $this->exception('分类已存在，请勿重复添加');
        }
        if ($path != $newPath) {
            $this->dao->setPathField('path')->updatePath((int) $id, $path, $newPath);
        }
        return $this->dao->update($id, $data) && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 删除.
     * @param mixed $id
     * @return null|bool|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if ($this->dao->exists(['pid' => $id])) {
            throw $this->exception('存在子分类，请先删除子分类');
        }
        return $this->dao->delete($id) && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 获取下拉列表.
     * @param null|mixed $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelect(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        $list = $this->dao->getList($where, $field, 0, 0, $sort, $with);
        return get_tree_children($list);
    }

    public function resourceShowUpdate($id, array $data)
    {
        $data['status'] = (int) $data['status'];
        $childId        = $this->getChildId((int) $id);
        $result         = $this->transaction(function () use ($id, $data, $childId) {
            $childId && $this->dao->update(['id' => $childId], ['status' => $data['status']]);
            $this->showUpdate($id, $data);
            return true;
        });
        return $result && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    private function getChildId(array|int $id, array $result = [])
    {
        $childId = $this->dao->column(['pid' => $id], 'id');
        if ($childId) {
            $result = array_unique(array_merge($result, $childId));
            return $this->getChildId($childId, $result);
        }
        return $result;
    }

    /**
     * 获取表单规则.
     * @return array
     */
    private function getFormRule(Collection $collection)
    {
        $list = $this->dao->setDefaultSort('sort')->select(['not_path' => $collection->get('id', ''), 'level_lt' => 5, 'not_id' => $collection->get('id', '')], ['id as value', 'pid', 'name as label'])?->toArray();
        $list = get_tree_children(array_merge([['value' => 0, 'pid' => 0, 'label' => '顶级分类']], $list), keyName: 'value');
        return [
            FormService::cascader('path', '父级分类')
                ->options($list)->appendRule('value', (array) $collection->get('path', [0]) ?: [0])
                ->props(['props' => ['checkStrictly' => true]])->clearable(true),
            FormService::input('name', '分类名称', $collection->get('name', ''))->required(),
            FormService::radio('status', '状态', (int) $collection->get('status', 1))->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]),
            FormService::number('sort', '排序', (int) $collection->get('sort', 0))->min(0)->max(9999999)->required(),
        ];
    }
}
