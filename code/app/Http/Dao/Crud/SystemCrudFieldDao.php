<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Dao\Crud;

use App\Constants\Crud\CrudFormEnum;
use App\Http\Model\Crud\SystemCrudField;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SystemCrudFieldDao extends BaseDao
{
    use BatchSearchTrait;

    /**
     * 设置主展示字段恢复默认.
     * @return bool
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    public function updateMain(int $crudId, int $id)
    {
        return $this->getModel()->where('id', '<>', $id)->where('crud_id', $crudId)->update(['is_main' => 0]);
    }

    /**
     * 获取数据字典字段.
     * @return mixed
     */
    public function getDataDictIds(int $crudId)
    {
        return array_column($this->getModel()->where('data_dict_id', '<>', 0)->where('crud_id', $crudId)->select(['data_dict_id'])->get()->toArray(), 'data_dict_id');
    }

    /**
     * 修改.
     * @return bool
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    public function updateForm(array $crud)
    {
        foreach ($crud as $id => $fields) {
            $this->getModel()
                ->whereNotIn('field_name_en', $fields)
                ->where('crud_id', $id)
                ->update(['is_form' => 0]);
        }
    }

    /**
     * @return bool
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/6
     */
    public function updateIsForm(array $crud)
    {
        foreach ($crud as $id => $fields) {
            $this->getModel()
                ->whereIn('field_name_en', $fields)
                ->where('crud_id', $id)
                ->update(['is_form' => 1]);
        }
    }

    /**
     * 根据字段名获取表格列表头部展示字段信息.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    public function getFieldSelect(int $crudId, array $field, array $select = ['id', 'field_name_en', 'field_name', 'form_value', 'data_dict_id'])
    {
        return $this->getModel()->where('crud_id', $crudId)
            ->where('field_name_en', '<>', 'deleted_at')
            ->where('form_value', '<>', CrudFormEnum::FORM_FILE)
            ->whereIn('field_name_en', $field)
            ->select($select)
            ->get()->toArray();
    }

    /**
     * 获取默认的表单展示列.
     * @param array|string[] $select
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/12
     */
    public function getFieldTableShowRowSelect(int $crudId, array $select = ['id', 'field_name_en', 'field_name', 'form_value', 'data_dict_id'], bool $isFieldAll = false)
    {
        return $this->getModel()->where('crud_id', $crudId)
            ->where('field_name_en', '<>', 'deleted_at')
            ->when(! $isFieldAll, fn ($q) => $q->where('is_table_show_row', 1))
            ->where('form_value', '<>', CrudFormEnum::FORM_FILE)
            ->select($select)
            ->get()->toArray();
    }

    /**
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getFieldSearch(array $field)
    {
        return $this->getModel()->where('field_name_en', '<>', 'deleted_at')
            ->whereIn('field_name_en', $field)
            ->select([
                'id', 'field_name_en', 'field_name', 'form_value',
                'data_dict_id', 'association_crud_id', 'options',
            ])->get()->toArray();
    }

    /**
     * 获取设置表单的字段数据.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/13
     */
    public function getFormField(array|int $crudId)
    {
        return $this->getModel()->whereNotIn('field_name_en', ['deleted_at', 'id'])
            ->whereIn('crud_id', is_array($crudId) ? $crudId : [$crudId])
            ->where('is_form', 1)
            ->with(['crud' => fn ($q) => $q->select(['id', 'table_name_en'])])
            ->select(['id', 'crud_id', 'field_name_en', 'field_name', 'form_value', 'form_field_uniqid', 'is_default_value_not_null'])
            ->get()->toArray();
    }

    public function fieldByFieldLists(array|int $crudId, array $fields)
    {
        return $this->getModel()->whereNotIn('field_name_en', ['deleted_at', 'id'])
            ->whereIn('crud_id', is_array($crudId) ? $crudId : [$crudId])
            ->whereIn('field_name_en', $fields)
            ->select([
                'id', 'crud_id', 'field_name_en', 'data_dict_id', 'options',
                'association_crud_id', 'field_name', 'form_value', 'field_type',
                'form_field_uniqid', 'is_default_value_not_null', 'create_modify',
                'update_modify',
            ])
            ->get()->toArray();
    }

    public function fieldByList(array|int $crudId, array $fields, int $isForm = 1, bool $isIdShow = false)
    {
        return $this->getModel()->whereNotIn('field_name_en', $isIdShow ? ['deleted_at'] : ['deleted_at', 'id'])
            ->whereIn('crud_id', is_array($crudId) ? $crudId : [$crudId])
            ->whereIn('field_name_en', $fields)
            ->when($isForm, fn ($q) => $q->where('is_form', 1))
            ->with([
                'crud'        => fn ($q) => $q->select(['id', 'table_name_en']),
                'association' => fn ($q) => $q->select(['id', 'table_name_en']),
            ])
            ->select([
                'id', 'crud_id', 'field_name_en', 'data_dict_id', 'options',
                'association_crud_id', 'field_name', 'form_value', 'field_type',
                'form_field_uniqid', 'is_default_value_not_null', 'create_modify',
                'update_modify', 'is_uniqid', 'association_show_type', 'customize_items', 'data_type',
            ])
            ->get()->toArray();
    }

    /**
     * 获取能进行排序的字段.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    public function getOrderByFieldList(int $crudId, array $formValue)
    {
        return $this->getModel()->whereNotIn('field_name_en', ['deleted_at'])
            ->whereIn('form_value', $formValue)->where('crud_id', $crudId)
            ->select(['id', 'field_name_en', 'field_name', 'prev_field'])
            ->get()->toArray();
    }

    /**
     * 根据字段唯一名称查找字段值
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    public function formFieldUniqidByFieldList(array $formFieldUniqids, int $crudId = 0)
    {
        return $this->getModel()->whereNotIn('field_name_en', ['deleted_at', 'id'])
            ->whereIn('form_field_uniqid', $formFieldUniqids)
            ->when($crudId, fn ($q) => $q->where('crud_id', $crudId))
            ->select(['id', 'field_name_en', 'field_name', 'prev_field', 'crud_id', 'form_field_uniqid'])
            ->get()->toArray();
    }

    /**
     * 获取某个实体下关联的实体ID.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/21
     */
    public function getAssociationCrudId(int $crudId, int $isDefault = 0)
    {
        return $this->getModel()->when($isDefault == 0, fn ($q) => $q->where('is_default', 0))
            ->where('crud_id', $crudId)
            ->with(['association'])
            ->where('association_crud_id', '<>', 0)
            ->where('association_crud_id', '<>', $crudId)
            ->select(['association_crud_id', 'form_field_uniqid', 'field_name_en', 'id', 'field_name'])
            ->groupBy('association_crud_id')
            ->get()
            ->toArray();
    }

    /**
     * 根据当前的实体ID获取到一对一管理了当前实体的实体id集合.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/19
     */
    public function crudIdByAssociationCrudList(array $crudId, array $master, array $with)
    {
        return $this->getModel()
//            ->where('is_default', 0)
            ->whereNotIn('crud_id', $crudId)
            ->where('association_crud_id', $crudId)
            ->with([
                'crud' => fn ($q) => $q->with($with)->select($master),
            ])
            ->select(['crud_id', 'field_name'])
            ->groupBy('crud_id')
            ->get()
            ->toArray();
    }

    /**
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function fieldIdByAssociationInfo(array $fieldIds)
    {
        return $this->getModel()
            ->whereIn('id', $fieldIds)
            ->where('association_crud_id', '<>', 0)
            ->with(['association'])
            ->select(['association_crud_id', 'id'])
            ->get()
            ->toArray();
    }

    /**
     * 字段名是否存在.
     * @return bool
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/15
     */
    public function existsFieldName(int $crudId, int $id, string $fieldName)
    {
        return $this->getModel()->where('crud_id', $crudId)->where('id', '<>', $id)->where('field_name', $fieldName)->exists();
    }

    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2024/3/7
     */
    protected function setModel()
    {
        return SystemCrudField::class;
    }
}
