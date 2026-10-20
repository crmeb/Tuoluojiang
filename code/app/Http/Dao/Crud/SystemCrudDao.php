<?php

/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------.
 */

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

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrud;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Traits\Conditionable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SystemCrudDao extends BaseDao
{
    /**
     * 获取已经删除的实体表名.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getDelTables()
    {
        return $this->getModel()->withTrashed()->where('deleted_at', null)->select(['id', 'table_name_en'])->get()->toArray();
    }

    /**
     * 获取分类id.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/16
     */
    public function crudIdByCateIds(array $ids)
    {
        return $this->getModel()->whereIn('id', $ids)->select(['id', 'cate_ids'])->get()->toArray();
    }

    /**
     * 获取一对一关联表.
     * @return mixed[]
     * @email 136327134@qq.com
     * @date 2024/2/26
     * @throws BindingResolutionException
     */
    public function getAssociationList(string $tableNameEn, int $page, int $limit)
    {
        return $this->getModel()->where('table_name_en', '<>', $tableNameEn)
            ->forPage($page, $limit)->select(['id', 'table_name', 'table_name_en'])->get()->toArray();
    }

    /**
     * @return BaseModel|Conditionable|HigherOrderWhenProxy|mixed
     * @email 136327134@qq.com
     * @date 2024/2/26
     * @throws BindingResolutionException
     */
    public function getSearchModel(array $where = [])
    {
        return $this->getModel()
            ->when(! empty($where['table_name']), fn ($q) => $q->where(
                fn ($qq) => $qq->where('table_name_en', 'like', '%' . $where['table_name'] . '%')
                    ->orWhere('table_name', 'like', '%' . $where['table_name'] . '%')
            ))
            ->when(! empty($where['cate_id']) && $where['cate_id'], fn ($q) => $q->where('cate_ids', 'like', '%/' . $where['cate_id'] . '/%'))
            ->when(isset($where['crud_id']) && $where['crud_id'] !== '', fn ($q) => $q->where('crud_id', $where['crud_id']));
    }

    /**
     * 根据id获取实体列表.
     * @return mixed[]
     * @email 136327134@qq.com
     * @date 2024/3/14
     * @throws BindingResolutionException
     */
    public function getCrudList(array $crudIds, array $select = ['id', 'table_name', 'table_name_en'], array $with = [])
    {
        return $this->getModel()->whereIn('id', $crudIds)
            ->when($with, fn ($q) => $q->with($with))
            ->select($select)->get()->toArray();
    }

    /**
     * 检测表明.
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @email 136327134@qq.com
     * @date 2024/4/12
     */
    public function existsTable(int $id, string $name)
    {
        return $this->getModel()->where('id', '<>', $id)->where('table_name', $name)->exists();
    }

    /**
     * 查询包含伪删除表.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/13
     */
    public function withTrashedTable(string $name)
    {
        return $this->getModel()->withTrashed()->where('table_name_en', $name)->exists();
    }

    /**
     * 获取子级信息.
     * @return mixed
     */
    public function getSubordinateCrudInfo(int $parentId)
    {
        return $this->getModel()->where('crud_id', $parentId)->first()?->toArray();
    }

    /**
     * 获取分类下的实体数量.
     * @return mixed
     */
    public function getCateCrudNum(array|int $cateId)
    {
        return $this->getModel()->when(
            ! is_array($cateId),
            fn ($q) => $q->where('cate_ids', 'like', '%/' . $cateId . '/%'),
            function ($q) use ($cateId) {
                foreach ($cateId as $id) {
                    $q->orWhere('cate_ids', 'like', '%/' . $id . '/%');
                }
            }
        )->count();
    }

    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2024/2/23
     */
    protected function setModel()
    {
        return SystemCrud::class;
    }
}
