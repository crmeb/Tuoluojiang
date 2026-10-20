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

namespace App\Http\Service\Storage;

use App\Http\Dao\Storage\StorageCategoryDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 物资分类services.
 * Class StorageCategoryService.
 */
class StorageCategoryService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * 消耗物资.
     */
    public const CURRENT_ASSETS = 0;

    /**
     * 固定资产.
     */
    public const FIXED_ASSETS = 1;

    public function __construct(StorageCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = []): array
    {
        return Cache::tags(['storage_cate'])->remember(md5(json_encode($where)), (int)sys_config('system_cache_ttl', 3600), function () use ($where, $sort) {
            return get_tree_children($this->dao->getList($where, ['*', 'id as value', 'cate_name as label'], sort: $sort));
        });
    }

    /**
     * 新建表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('新增物资分类', $this->getFormRule(collect($other)), '/ent/storage/cate');
    }

    /**
     * 新建保存.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $path = array_values(array_filter($data['path'] ?? []));
        if ($path) {
            $data['pid'] = $path[count($path) - 1];
        }
        if (!$data['pid']) {
            $data['pid'] = 0;
        }
        if ($this->dao->exists(['pid' => $data['pid'], 'cate_name' => $data['cate_name'], 'type' => $data['type'], 'entid' => 1])) {
            throw $this->exception('分类已存在，请勿重复添加');
        }
        $data['path']  = $path;
        $data['level'] = count($path) + 1;
        Cache::tags(['storage_cate'])->flush();
        return $this->dao->create($data);
    }

    /**
     * 编辑表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw $this->exception('修改的物资分类不存在');
        }
        return $this->createElementForm('修改物资分类', $this->getFormRule(collect($info->toArray())), '/ent/storage/cate/' . $id, 'PUT');
    }

    /**
     * 修改.
     * @param int $id
     * @return bool
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $path = array_values(array_filter($data['path'] ?? []));
        if ($path) {
            $data['pid'] = $path[count($path) - 1];
        }
        if (!$data['pid']) {
            $data['pid'] = 0;
        }
        $data['path']  = $path;
        $data['level'] = count($path) + 1;
        $this->dao->update($id, $data);
        Cache::tags(['storage_cate'])->flush();
        return true;
    }

    /**
     * 删除.
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(StorageService::class)->count(['cid' => $id])) {
            throw $this->exception('请先删除关联物资,再尝试删除');
        }
        if ($this->dao->exists(['pid' => $id])) {
            throw $this->exception('请先删除关联分类,再尝试删除');
        }
        Cache::tags(['storage_cate'])->flush();
        return $this->dao->delete($id, $key);
    }

    /**
     * 获取表单树形数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCateTree(array $where): array
    {
        return get_tree_children($this->dao->getList($where, ['*', 'id as value', 'cate_name as label']), 'children', 'value');
    }

    /**
     * 获取无限下级分类.
     * @param array|int $cateId
     * @param array $ids
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSubCates($cateId, $ids = [])
    {
        if (!is_array($cateId)) {
            $cateId = [$cateId];
        }
        $cate_id = $this->dao->column(['pid' => $cateId], 'id') ?? [];
        $ids     = array_merge($ids, $cate_id);
        if (count($cate_id) && $this->dao->exists(['pid' => $cate_id])) {
            return $this->getSubCates($cate_id, $ids);
        }
        return $ids;
    }

    /**
     * 表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getFormRule(Collection $collection)
    {
        $id         = (int) $collection->get('id', 0);
        $excludeIds = $id ? array_merge([$id], $this->getSubCates($id)) : [];
        $path       = (array) $collection->get('path', [0]) ?: [0];

        return [
            Form::input('type', '物资类型', $collection->get('type', 0))->hiddenStatus(true),
            Form::cascader('path', '父级类别')
                ->options(array_merge([['value' => 0, 'label' => '顶级分类']], $this->getCateTree(['entid' => 1, 'not_id' => $excludeIds, 'type' => $collection->get('type') ?? 0])))
                ->appendRule('value', $path)->props(['props' => ['checkStrictly' => true]]),
            Form::input('cate_name', '类别名称', $collection->get('cate_name'))->required()->maxlength(15)->showWordLimit(true),
            Form::number('sort', '排序', $collection->get('sort', 0))->min(0)->max(999999)->precision(0),
        ];
    }
}
