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

namespace App\Http\Service\Crud;

use App\Constants\Crud\CrudFormEnum;
use App\Http\Dao\Crud\SystemCrudFieldDao;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\DictTypeService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\SmsService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 实体字段
 * Class SystemCrudFormService.
 * @email 136327134@qq.com
 * @date 2024/2/24
 * @mixin SystemCrudFieldDao
 */
class SystemCrudFieldService extends BaseService
{
    /**
     * SystemCrudFormService constructor.
     */
    public function __construct(SystemCrudFieldDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 根据字段唯一值获取实体信息和字段信息.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    public function formFieldUniqidByFieldCrud(array $formFieldUniqids)
    {
        $list = $this->dao->formFieldUniqidByFieldList($formFieldUniqids);

        if (!$list) {
            return [];
        }

        $crudIds = array_merge(array_unique(array_column($list, 'crud_id')));

        $crudList = app()->get(SystemCrudService::class)->getCrudList($crudIds);

        $crudData = [];
        foreach ($crudList as $item) {
            $crudData[$item['id']] = $item;
        }

        $data = [];
        foreach ($list as $item) {
            $data[$item['crud_id']]['crud_info'] = $crudData[$item['id']];
            $data[$item['crud_id']]['field'][] = $item;
        }

        return $data;
    }

    /**
     * 根据字段获取实体信息.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/28
     */
    public function fieldNameByFieldCrud(array $fields, string $tableName)
    {
        $crudIds = [];
        foreach ($fields as $field) {
            [$table, $name] = strstr($field, '.') !== false ? explode('.', $field) : [$tableName, $field];

            $id = app()->get(SystemCrudService::class)->value(['table_name_en' => $table], 'id');

            $crudIds[$id][] = $name;
        }

        if (!$crudIds) {
            return [];
        }

        $data = [];
        foreach ($crudIds as $id => $item) {
            $crudList = app()->get(SystemCrudService::class)->getCrudList(
                crudIds: [$id],
                with: [
                    'field' => fn($q) => $q->with(['association'])->whereIn('field_name_en', $item),
                ]
            );

            if ($crudList) {
                $crudInfo = $crudList[0];
                $value = $crudInfo['field'];
                unset($crudInfo['field']);

                $data[$id] = [
                    'crud_info' => $crudInfo,
                    'field'     => $value,
                ];
            }
        }

        return $data;
    }

    /**
     * 获取表字段列表.
     * @return mixed[]
     * @email 136327134@qq.com
     * @date 2024/2/27
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getTableFieldList(int $crudId, string $name = '')
    {
        [$page, $limit] = $this->getPageValue();
        $fieldList = $this->dao->setDefaultSort(['id' => 'asc'])->select(
            where: ['field_name' => $name, 'crud_id' => $crudId],
            page: $page,
            limit: $limit
        )->toArray();
        $associationCrudId = array_filter(array_column($fieldList, 'association_crud_id'));
        $column = app()->get(SystemCrudService::class)->getCrudTableAll($associationCrudId, true);

        $reTable = '';
        $crudService = app()->get(SystemCrudService::class);
        $reTablecrudId = $crudService->value(['id' => $crudId], 'crud_id');
        if ($reTablecrudId) {
            $reTable = $crudService->value(['id' => $reTablecrudId], 'table_name_en');
            if ($reTable) {
                $reTable .= '_id';
            }
        }

        $dataDictId = array_column($fieldList, 'data_dict_id');
        $dataColumn = app()->get(DataDictService::class)->column(['id' => $dataDictId], 'name', 'id');
        foreach ($fieldList as &$item) {
            $item['association_crud_table_name_en'] = $column[$item['association_crud_id']]['table_name_en'] ?? '';
            $item['association_crud_table_name'] = $column[$item['association_crud_id']]['table_name'] ?? '';
            if ($reTable && $item['field_name_en'] === $reTable) {
                $item['is_re_table'] = 1;
            } else {
                $item['is_re_table'] = 0;
            }
            $item['data_dict_name'] = $item['data_dict_id'] ? $dataColumn[$item['data_dict_id']] ?? '' : '';
        }

        return $fieldList;
    }

    /**
     * 获取某个表下的字段和关联字段
     * 当前主表的字段和一对一关联的字段.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/7
     */
    public function getCrudField(int $crudId, bool $approve = false)
    {
        $notField = ['deleted_at', 'id'];
        $fieldInfo = $this->dao->select(
            where: ['crud_id' => $crudId, 'not_field' => $notField],
            field: [
                'field_name', 'field_name_en', 'crud_id', 'id',
                'association_crud_id', 'data_dict_id', 'prev_field', 'form_value', 'customize_items', 'data_type',
            ],
            with: [
                'association' => fn($q) => $q->with([
                    'field' => fn($q) => $q->whereNotIn('field_name_en', $notField)
                        ->select([
                            'field_name', 'field_name_en', 'crud_id',
                            'prev_field', 'data_dict_id', 'form_value',
                            'id', 'data_type', 'customize_items',
                        ]),
                ])->select(['table_name', 'table_name_en', 'id']),
            ]
        )->toArray();

        $fieldListData = $this->mergeSortField($fieldInfo);

        $dataDictId = array_merge(array_filter(array_column($fieldInfo, 'data_dict_id')));
        foreach ($fieldInfo as $item) {
            if (!empty($item['association']['field'])) {
                foreach ($item['association']['field'] as &$value) {
                    $value['field_name'] = $item['association']['table_name'] . '.' . $value['field_name'];
                    $value['field_name_en'] = $item['association']['table_name_en'] . '.' . $value['field_name_en'];
                }
                $fieldListData = array_merge($fieldListData, $this->mergeSortField($item['association']['field']));
                $dataDictId = array_merge($dataDictId, array_merge(array_filter(array_column($item['association']['field'], 'data_dict_id'))));
            }
        }

        // 获取数据字段数据
        $dictDataService = app()->get(DictDataService::class);
        $dictData = [];
        foreach ($dataDictId as $id) {
            $typeName = app()->get(DictTypeService::class)->value($id, 'ident');
            $dictData[$id] = $dictDataService->getTreeData(['type_id' => $id, 'type_name' => $typeName]);
        }

        if ($approve) {
            foreach ($fieldListData as $key => $item) {
                $newItem = [];
                if ($item['data_type'] == 1) {
                    $newItem['data_dict'] = $item['customize_items'];
                } elseif ($item['data_dict_id'] && isset($dictData[$item['data_dict_id']])) {
                    $newItem['options'] = $dictData[$item['data_dict_id']] ?? [];
                } else {
                    $newItem['options'] = [];
                }

                $newItem['field'] = $item['field_name_en'];
                $newItem['title'] = $item['field_name'];
                $newItem['type'] = $item['form_value'];
                $newItem['id'] = $item['id'];
                $newItem['crud_id'] = $item['crud_id'];
                $newItem['is_user'] = !empty($item['association']['table_name_en']) && $item['association']['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_USER;
                $newItem['is_frame'] = !empty($item['association']['table_name_en']) && $item['association']['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_FRAME;
                $fieldListData[$key] = $newItem;
            }
        } else {
            foreach ($fieldListData as $key => $item) {
                if ($item['data_type'] == 1) {
                    $item['data_dict_list'] = $item['customize_items'];
                } elseif ($item['data_dict_id'] && isset($dictData[$item['data_dict_id']])) {
                    $item['data_dict_list'] = $dictData[$item['data_dict_id']];
                } else {
                    $item['data_dict_list'] = [];
                }
                $item['is_user'] = !empty($item['association']['table_name_en']) && $item['association']['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_USER;
                $item['is_frame'] = !empty($item['association']['table_name_en']) && $item['association']['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_FRAME;
                $fieldListData[$key] = $item;
            }
        }

        return $fieldListData;
    }

    /**
     * 合并排序结果.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/7
     */
    public function mergeSortField(array $data)
    {
        [$emptyPrevFieldData, $prevFieldData] = $this->sortField($data);
        return array_merge($emptyPrevFieldData, $prevFieldData);
    }

    /**
     * 排序字段.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/7
     */
    public function sortField(array $data, string $aname = 'prev_field', string $bname = 'field_name_en')
    {
        $emptyPrevFieldData = [];
        $prevFieldData = [];
        foreach ($data as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            if ($item[$aname]) {
                $prevFieldData[] = $item;
            } else {
                $emptyPrevFieldData[] = $item;
            }
        }

        $length = count($prevFieldData);
        for ($i = 0; $i < $length - 1; ++$i) {
            for ($j = 0; $j < $length - $i - 1; ++$j) {
                // 比较相邻的元素并交换顺序
                if ($prevFieldData[$j][$aname] == $prevFieldData[$j + 1][$bname]) {
                    $temp = $data[$j];
                    $prevFieldData[$j] = $prevFieldData[$j + 1];
                    $prevFieldData[$j + 1] = $temp;
                }
            }
        }

        return [$emptyPrevFieldData, $prevFieldData];
    }

    /**
     * 获取某个实体下一对一关联的所有实体ID.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    public function getAssociationIds(int $crudId, string $field = 'association_crud_id', string $key = '')
    {
        return $this->dao->column(['crud_id' => $crudId], $field, $key);
    }

    /**
     * 根据当前的实体获取到当前实体一对一关联的实体id,不包含从表内添加的默认字段.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/4/2
     */
    public function crudByAssociationIds(int $crudId)
    {
        // 排除掉附表能在tab中展示的问题
        $lowerId = app()->get(SystemCrudService::class)->value(['crud_id' => $crudId], 'id');
        return $this->dao->column(['not_lower_id' => $lowerId, 'association_crud_id' => $crudId, 'is_default' => 0], 'crud_id');
    }

    /**
     * 获取排序字段列表缓存.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getOrderByFieldListCache(int $crudId, array $formValue)
    {
        return Cache::tags(SystemCrudService::TAG_NAME)->remember('crud_order_by_field_list_' . $crudId . '_' . md5(json_encode($formValue)), null, function () use ($crudId, $formValue) {
            return $this->dao->getOrderByFieldList($crudId, $formValue);
        });
    }

    /**
     * 获取某个实体下关联的实体ID.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getAssociationCrudIdCache(int $crudId, int $isDefault = 0)
    {
        return Cache::tags(SystemCrudService::TAG_NAME)->remember(__FUNCTION__ . '_' . $crudId . '_' . $isDefault, null, function () use ($crudId, $isDefault) {
            return $this->dao->getAssociationCrudId($crudId, $isDefault);
        });
    }

    /**
     * 获取某个实体下的所有字段.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCrudTableFieldAllCache(int $crudId, string $fieldNameEn = '', array $select = [])
    {
        $fieldAllList = Cache::tags(SystemCrudService::TAG_NAME)->remember(__FUNCTION__ . '_' . $crudId, null, function () use ($crudId) {
            return $this->dao->select(['crud_id' => $crudId]);
        });

        if ($fieldNameEn) {
            $fieldInfo = [];
            foreach ($fieldAllList as $item) {
                if ($item['field_name_en'] === $fieldNameEn) {
                    $fieldInfo = $item;
                    break;
                }
            }

            if ($fieldInfo && $select) {
                $data = [];
                foreach ($select as $key) {
                    $data[$key] = $fieldInfo[$key] ?? null;
                }
                return $data;
            }

            return $fieldInfo;
        }

        return $fieldAllList;
    }

    /**
     * 获取某个实体下的关联字段名称.
     * @return null|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAssociationCrudFieldNameEnCache(int $crudId, int $associationCrudId)
    {
        $fieldNameEn = null;
        $fieldAllList = $this->getCrudTableFieldAllCache($crudId);
        foreach ($fieldAllList as $item) {
            if ($item['association_crud_id'] === $associationCrudId) {
                $fieldNameEn = $item['field_name_en'];
                break;
            }
        }

        return $fieldNameEn;
    }

    /**
     * 获取某个实体下的主字段名称.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCrudMainFieldNameEnCache(int $crudId)
    {
        $fieldNameEn = null;
        $fieldAllList = $this->getCrudTableFieldAllCache($crudId);
        foreach ($fieldAllList as $item) {
            if ($item['is_main']) {
                $fieldNameEn = $item['field_name_en'];
                break;
            }
        }

        return $fieldNameEn;
    }

    /**
     * 获取某个实体下的字段列表.
     * @return mixed
     */
    public function fieldByListCache(array|int $crudId, array $fields, int $isForm = 1, bool $isIdShow = false)
    {
        return Cache::tags(SystemCrudService::TAG_NAME)->remember(__FUNCTION__ . '_' . md5(json_encode([$crudId, $fields, $isForm, $isIdShow])), null, function () use ($crudId, $fields, $isForm, $isIdShow) {
            return $this->dao->fieldByList($crudId, $fields, $isForm, $isIdShow);
        });
    }

    /**
     * 获取一对一关联选择表的数据表字段.
     * @return null|array|Model
     * @email 136327134@qq.com
     * @date 2024/2/26
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getAssociationCrudField(int $id)
    {
        return $this->dao->select(['crud_id' => $id, 'is_default' => 0], ['field_name', 'id', 'field_name_en'])->toArray();
    }

    /**
     * 添加字段.
     * @param string $value 选择的表单类型
     * @param string $fieldName 字段名中文
     * @param string $fieldNameEn 字段名英文
     * @param bool $isDefaultValueNotNull 是否允许空值
     * @param bool $isTableShowRow 是否在列表中默认显示
     * @param string $comment 说明可为空
     * @param int $dataDictId 数据字典ID
     * @param array $fieldNames 关联字段
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function addField(int $crudId, string $value, string $fieldName, string $fieldNameEn, bool $isDefaultValueNotNull = false, bool $isTableShowRow = true, string $comment = '', int $dataDictId = 0, int $associationCrudId = 0, array $fieldNames = [], array $options = [], int $createModify = 1, int $updateModify = 1, bool $isUniqid = false, int $dataType = 0)
    {
        $crudService = app()->get(SystemCrudService::class);
        $crudInfo = $crudService->get($crudId, ['id', 'table_name_en', 'form_fields'], ['field' => fn($q) => $q->orderBy('id')->select(['id', 'crud_id', 'field_name_en', 'prev_field'])]);
        if (!$crudInfo) {
            throw $this->exception('没有查询到数据');
        }
        //        if (in_array($crudInfo->table_name_en, self::SYSTEM_TABLE_TABLE)) {
        //            throw $this->exception('系统表不允许添加字段');
        //        }

        if ($this->dao->count(['crud_id' => $crudId, 'field_name_en' => $fieldNameEn])) {
            throw $this->exception('字段已存在');
        }
        if ($this->dao->count(['crud_id' => $crudId, 'field_name' => $fieldName])) {
            throw $this->exception('字段名已存在');
        }
        if ($crudInfo->crud_id && $isUniqid) {
            throw $this->exception('附表不允许添加唯一字段');
        }
        if ($isUniqid && app()->get(CrudModuleService::class)->model(tableName: $crudInfo->table_name_en)->count()) {
            throw $this->exception('当前表已存在数据，无法设置唯一字段');
        }

        $field = $crudInfo['field']?->toArray();
        $fields = $this->mergeSortField($field ?: []);
        $newFields = [];
        foreach ($fields as $item) {
            if (!in_array($item['field_name_en'], ['created_at', 'updated_at', 'deleted_at'])) {
                $newFields[] = $item;
            }
        }
        $count = count($newFields);
        $prevFiled = $newFields[$count - 1]['field_name_en'] ?? 'frame_id';

        $formInfo = null;

        foreach (SystemCrudService::FORM_TYPE as $item) {
            if (!empty($item['value'])) {
                if ($item['value'] === $value) {
                    $formInfo = $item;
                    break;
                }
            } else {
                foreach ($item['options'] as $option) {
                    if ($option['value'] === $value) {
                        $formInfo = $option;
                        break;
                    }
                }
            }
        }

        if ($formInfo === null) {
            throw $this->exception('选择的表单类型不存在');
        }

        if ($formInfo['value'] === CrudFormEnum::FORM_INPUT_SELECT) {
            if (empty($fieldNames)) {
                throw $this->exception('一对一关联必须选择展示字段');
            }
            if (!$associationCrudId) {
                throw $this->exception('请选择关联数据表');
            }
        }

        $isMain = 0;
        if ($value === CrudFormEnum::FORM_INPUT) {
            $isMain = $this->dao->count(['crud_id' => $crudId, 'is_main' => 1, 'form_value' => CrudFormEnum::FORM_INPUT]) ? 0 : 1;
        }

        $isDefault = 0;
        if (in_array($crudInfo->table_name_en, SystemCrudService::SYSTEM_TABLE_TABLE)) {
            $columns = Schema::getColumnListing($crudInfo->table_name_en);
            $isDefault = in_array($fieldNameEn, $columns) ? 1 : 0;
        }
        $data = [
            'crud_id'                   => $crudId,
            'field_name'                => $fieldName,
            'field_name_en'             => $fieldNameEn,
            'form_value'                => $value,
            'field_type'                => $formInfo['type'],
            'is_default_value_not_null' => $isDefaultValueNotNull ? 1 : 0,
            'is_table_show_row'         => $isTableShowRow ? 1 : 0,
            'comment'                   => $comment,
            'prev_field'                => $prevFiled,
            'data_dict_id'              => $dataDictId,
            'association_crud_id'       => $associationCrudId,
            'association_field_names'   => $fieldNames,
            'options'                   => $options,
            'create_modify'             => $createModify,
            'update_modify'             => $updateModify,
            'form_field_uniqid'         => uniqid($crudInfo->table_name_en),
            'is_main'                   => $isMain,
            'is_form'                   => 1,
            'is_default'                => $isDefault,
            'is_uniqid'                 => $isUniqid ? 1 : 0,
            'data_type'                 => $dataType,
        ];


        $message = null;
        $addAlter = true;
        try {
            $defaulType = $isDefaultValueNotNull ? '2' : '1';
            $limit = $formInfo['limit'] ?: '0';
            if ($value === CrudFormEnum::FORM_DATE_PICKER) {
                $defaulType = '2';
                $limit = null;
            } elseif ($value === CrudFormEnum::FORM_DATE_TIME_PICKER) {
                $defaulType = '3';
                $limit = null;
            }
            if (!in_array($crudInfo->table_name_en, SystemCrudService::SYSTEM_TABLE_TABLE)) {
                $this->addAlter(
                    tableName: $crudInfo->table_name_en,
                    field: $fieldNameEn,
                    prevFiled: $prevFiled,
                    type: $formInfo['type'],
                    limit: $limit,
                    default: (string)$formInfo['default'],
                    comment: $fieldName,
                    options: [
                        'default_type' => $defaulType,
                    ]
                );

                if ($isUniqid) {
                    $this->addIndex(
                        tableName: $crudInfo->table_name_en,
                        field: $fieldNameEn
                    );
                }
            }
        } catch (\Throwable $e) {
            $addAlter = false;
            $message = $e->getMessage();
        }

        $this->transaction(function () use (
            $data,
            $crudId,
            $fieldNameEn,
            $addAlter,
            $message,
            $crudInfo
        ) {
            $crudField = $this->dao->create($data);
            $this->dao->update([
                'crud_id'       => $crudId,
                'field_name_en' => 'created_at',
            ], [
                'prev_field' => $fieldNameEn,
            ]);

            app()->make(SystemCrudFormService::class)->saveDefaultValue($crudId, $crudField, $crudInfo);
            if (!$addAlter) {
                throw $this->exception($message);
            }
        });

        event('system.crud');
    }

    /**
     * 修改字段.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function updateField(int $id, string $value, string $fieldName, bool $isDefaultValueNotNull = false, bool $isTableShowRow = true, int $dataDictId = 0, array $fieldNames = [], array $options = [], int $createModify = 1, int $updateModify = 1, bool $isUniqid = false, int $dataType = 0)
    {
        $crudService = app()->get(SystemCrudService::class);
        $fieldInfo = $this->dao->get($id);
        if (!$fieldInfo) {
            throw $this->exception('修改的字段不存在');
        }
        if ($fieldInfo->field_name != $fieldName && $this->dao->existsFieldName($fieldInfo->crud_id, $id, $fieldName)) {
            throw $this->exception('字段名已存在');
        }
        if (!$fieldInfo->is_uniqid && $isUniqid) {
            throw $this->exception('当前字段不能设置为唯一字段');
        }
        $fieldInfo->field_name = $fieldName;
        $fieldInfo->is_default_value_not_null = $isDefaultValueNotNull ? 1 : 0;
        $fieldInfo->is_table_show_row = $isTableShowRow ? 1 : 0;
        $fieldInfo->data_dict_id = $dataDictId;
        $fieldInfo->association_field_names = $fieldNames;
        $fieldInfo->options = $options;
        $fieldInfo->create_modify = $createModify;
        $fieldInfo->update_modify = $updateModify;
        $fieldInfo->data_type = $dataType;

        if ($value) {
            $fieldInfo->form_value = $value;
        }

        $formInfo = null;
        foreach (SystemCrudService::FORM_TYPE as $item) {
            if (!empty($item['value'])) {
                if ($item['value'] === $fieldInfo->form_value) {
                    $formInfo = $item;
                    break;
                }
            } else {
                foreach ($item['options'] as $option) {
                    if ($option['value'] === $fieldInfo->form_value) {
                        $formInfo = $option;
                        break;
                    }
                }
            }
        }

        if ($formInfo === null) {
            throw $this->exception('选择的表单类型不存在,建议删除此字段');
        }

        $tableName = $crudService->value($fieldInfo->crud_id, 'table_name_en');
        if (!$tableName) {
            throw $this->exception('没有查询到表名');
        }

        $message = null;
        $updateAlter = true;
        try {
            $defaulType = $isDefaultValueNotNull ? '2' : '1';
            $limit = $formInfo['limit'] ?: '0';
            if ($fieldInfo->form_value === CrudFormEnum::FORM_DATE_PICKER) {
                $defaulType = '2';
                $limit = null;
            } elseif ($fieldInfo->form_value === CrudFormEnum::FORM_DATE_TIME_PICKER) {
                $defaulType = '3';
                $limit = null;
            }

            if (!in_array($tableName, SystemCrudService::SYSTEM_TABLE_TABLE)) {
                $this->updateAlter($tableName, $fieldInfo->field_name_en, $fieldInfo->field_name_en, $fieldInfo->prev_field, $formInfo['type'], $limit, (string)$formInfo['default'], $fieldName, [
                    'default_type' => $defaulType,
                ]);
                if ($fieldInfo->is_uniqid && !$isUniqid) {
                    $this->deleteIndex($tableName, $fieldInfo->field_name_en);
                    $fieldInfo->is_uniqid = 0;
                }
            }
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $updateAlter = false;
        }

        $this->transaction(function () use (
            $fieldInfo,
            $message,
            $updateAlter
        ) {
            $fieldInfo->save();

            if (!$updateAlter) {
                throw $this->exception($message);
            }
        });

        event('system.crud');
    }

    /**
     * 删除字段.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|\ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function deleteField(int $id)
    {
        $crudService = app()->get(SystemCrudService::class);
        $fieldInfo = $this->dao->get($id);
        if (!$fieldInfo) {
            throw $this->exception('没有查到字段信息');
        }

        $crudInfo = $crudService->get($fieldInfo->crud_id, ['id', 'table_name_en', 'form_fields'], ['field' => fn($q) => $q->orderBy('id')->select(['id', 'crud_id', 'field_name_en', 'prev_field'])]);
        if (!$crudInfo) {
            throw $this->exception('没有查到实体数据');
        }

        $formFields = [];
        if (in_array($fieldInfo->field_name_en, $crudInfo->form_fields)) {
            $formFields = $crudInfo->form_fields;
            $key = array_search($fieldInfo->field_name_en, $formFields);
            if ($key !== false) {
                unset($formFields[$key]);
            }
            $formFields = array_values($formFields);
        }

        $tableName = $crudInfo->table_name_en;
        if (!$tableName) {
            throw $this->exception('没有查到实体名称');
        }

        if ($fieldInfo->is_form) {
            throw $this->exception('请先在表单中移除对应表单');
        }

        if ($fieldInfo->association_crud_id
            && app()->get(SystemCrudEventService::class)
                ->count([
                    'crud_id'        => $fieldInfo->crud_id,
                    'target_crud_id' => $fieldInfo->association_crud_id,
                ])
        ) {
            throw $this->exception('当前字段已经有触发器，请先解除触发器中的目标实体关联');
        }

        $field = $crudInfo['field']?->toArray();
        $field = $field ?: [];

        // 默认放在
        $prevFiled = 'frame_id';

        foreach ($field as $index => $item) {
            if ($item['field_name_en'] === $fieldInfo->field_name_en && isset($field[$index - 1])) {
                $prevFiled = $field[$index - 1]['field_name_en'];
                break;
            }
        }

        $prevFieldId = $this->dao->value(['crud_id' => $fieldInfo->crud_id, 'prev_field' => $fieldInfo->field_name_en], 'id');

        $message = null;
        $deleteAlter = true;
        try {
            if (!in_array($tableName, SystemCrudService::SYSTEM_TABLE_TABLE)) {
                $this->deleteAlter($tableName, $fieldInfo->field_name_en);
            }
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $deleteAlter = false;
        }

        $this->transaction(function () use ($fieldInfo, $message, $deleteAlter, $prevFiled, $prevFieldId, $formFields, $crudService) {
            $fieldInfo->delete();
            $this->dao->update($prevFieldId, ['prev_field' => $prevFiled]);

            if ($formFields) {
                $crudService->update($fieldInfo->crud_id, ['form_fields' => $formFields]);
            }

            if (!$deleteAlter) {
                throw $this->exception($message);
            }
        });

        event('system.crud');
    }

    /**
     * 批量添加字段.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function batchAddField(array $fields, int $adminId, int $crudId = 0, string $tableName = '', string $tableNameEn = '', array $cateIds = [])
    {
        $crudService = app()->get(SystemCrudService::class);
        if (!$crudId) {
            if (in_array($tableNameEn, SystemCrudService::SYSTEM_TABLE_TABLE)) {
                throw $this->exception('系统表名存在不允许重复');
            }
            $crudId = $crudService->saveCrudTable([
                'table_name'      => $tableName,
                'table_name_en'   => $tableNameEn,
                'cate_ids'        => $cateIds,
                'crud_id'         => 0,
                'is_update_form'  => 0,
                'is_update_table' => 0,
                'info'            => '',
                'show_comment'    => 0,
                'comment_title'   => '',
                'show_log'        => 0,
            ], $adminId, false);
        }

        $crudInfo = $crudService->get($crudId, ['id', 'table_name_en'], ['field' => fn($q) => $q->orderBy('id')->select(['id', 'crud_id', 'field_name_en', 'prev_field'])]);
        if (!$crudInfo) {
            throw $this->exception('没有查询到数据');
        }
        if (in_array($crudInfo->table_name_en, SystemCrudService::SYSTEM_TABLE_TABLE)) {
            throw $this->exception('系统表不允许添加字段');
        }

        $crudFields = $crudInfo['field']?->toArray();
        $fieldNames = array_column($crudFields, 'field_name');
        $fieldNameEns = array_column($crudFields, 'field_name_en');

        $isMain = 0;

        $columns = [];
        if (in_array($crudInfo->table_name_en, SystemCrudService::SYSTEM_TABLE_TABLE)) {
            $columns = Schema::getColumnListing($crudInfo->table_name_en);
        }

        $field = $crudInfo['field']?->toArray();
        $newFields = [];
        foreach ($this->mergeSortField($field ?: []) as $item) {
            if (!in_array($item['field_name_en'], ['created_at', 'updated_at', 'deleted_at'])) {
                $newFields[] = $item;
            }
        }
        $count = count($newFields);
        $prevFiled = $newFields[$count - 1]['field_name_en'] ?? 'frame_id';

        $data = [];
        $batchFieldNames = [];
        $batchFieldNameEns = [];
        foreach ($fields as $i => $item) {
            if (in_array($item['field_name'], $fieldNames)) {
                throw $this->exception('字段名已存在：' . $item['field_name']);
            }
            if (in_array($item['field_name_en'], $fieldNameEns)) {
                throw $this->exception('字段名已存在:' . $item['field_name_en']);
            }
            if (in_array($item['field_name'], $batchFieldNames)) {
                throw $this->exception('批量字段中存在重复字段名：' . $item['field_name']);
            }
            if (in_array($item['field_name_en'], $batchFieldNameEns)) {
                throw $this->exception('批量字段中存在重复字段名：' . $item['field_name_en']);
            }
            $batchFieldNames[] = $item['field_name'];
            $batchFieldNameEns[] = $item['field_name_en'];
            if ($item['is_uniqid'] && app()->get(CrudModuleService::class)->model(tableName: $crudInfo->table_name_en)->count()) {
                throw $this->exception('当前表已存在数据，无法设置唯一字段');
            }

            $formInfo = null;

            foreach (SystemCrudService::FORM_TYPE as $itemType) {
                if (!empty($itemType['value'])) {
                    if ($itemType['value'] === $item['value']) {
                        $formInfo = $itemType;
                        break;
                    }
                } else {
                    foreach ($itemType['options'] as $option) {
                        if ($option['value'] === $item['value']) {
                            $formInfo = $option;
                            break;
                        }
                    }
                }
            }

            if ($formInfo === null) {
                throw $this->exception('选择的表单类型不存在');
            }

            if ($formInfo['value'] === CrudFormEnum::FORM_INPUT_SELECT) {
                if (empty($item['association_field_names'])) {
                    throw $this->exception('一对一关联必须选择展示字段');
                }
                if (!$item['association_crud_id']) {
                    throw $this->exception('请选择关联数据表');
                }
            }

            if ($item['value'] === CrudFormEnum::FORM_INPUT && !$isMain) {
                $isMain = $this->dao->count(['crud_id' => $crudId, 'is_main' => 1, 'form_value' => CrudFormEnum::FORM_INPUT]) ? 0 : 1;
                $isMainIndex = $i;
            }

            $isDefault = 0;
            if ($columns) {
                $isDefault = in_array($item['field_name_en'], $columns) ? 1 : 0;
            }

            $data[] = [
                'crud_id'                   => $crudId,
                'field_name'                => $item['field_name'],
                'field_name_en'             => $item['field_name_en'],
                'form_value'                => $item['value'],
                'field_type'                => $formInfo['type'],
                'is_default_value_not_null' => (bool)$item['is_default_value_not_null'] ? 1 : 0,
                'is_table_show_row'         => 1,
                'comment'                   => $item['comment'] ?: $item['field_name'],
                'prev_field'                => $i ? $fields[$i - 1]['field_name_en'] : $prevFiled,
                'data_dict_id'              => $item['data_dict_id'],
                'association_crud_id'       => $item['association_crud_id'],
                'association_field_names'   => json_encode($item['association_field_names']),
                'options'                   => json_encode($item['options']),
                'create_modify'             => $item['create_modify'],
                'update_modify'             => $item['update_modify'],
                'form_field_uniqid'         => uniqid($crudInfo->table_name_en),
                'is_main'                   => 0,
                'is_default'                => $isDefault,
                'is_uniqid'                 => $item['is_uniqid'] ? 1 : 0,
            ];
        }

        foreach ($data as $i => $item) {
            if ($item['form_value'] === CrudFormEnum::FORM_INPUT) {
                $item['is_main'] = $this->dao->count(['crud_id' => $crudId, 'is_main' => 1, 'form_value' => CrudFormEnum::FORM_INPUT]) ? 0 : 1;
                $data[$i] = $item;
                break;
            }
        }

        Schema::table($crudInfo->table_name_en, function (Blueprint $table) use ($fields, $crudInfo) {
            foreach ($fields as $item) {
                if (Schema::hasColumn($crudInfo->table_name_en, $item['field_name_en'])) {
                    continue;
                }
                switch ($item['value']) {
                    case CrudFormEnum::FORM_INPUT:
                    case CrudFormEnum::FORM_RADIO:
                    case CrudFormEnum::FORM_CASCADER_ADDRESS:
                    case CrudFormEnum::FORM_CHECKBOX:
                    case CrudFormEnum::FORM_TAG:
                    case CrudFormEnum::FORM_CASCADER:
                    case CrudFormEnum::FORM_CASCADER_RADIO:
                        $table->string($item['field_name_en'], 255)->default('')->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_INPUT_PRICE:
                    case CrudFormEnum::FORM_INPUT_FLOAT:
                        $table->decimal($item['field_name_en'], 10, 2)->default(0)->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_INPUT_PERCENTAGE:
                    case CrudFormEnum::FORM_INPUT_NUMBER:
                    case CrudFormEnum::FORM_INPUT_SELECT:
                    case CrudFormEnum::FORM_SELECT:
                        $table->integer($item['field_name_en'])->default(0)->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_TEXTAREA:
                    case CrudFormEnum::FORM_IMAGE:
                    case CrudFormEnum::FORM_FILE:
                        $table->text($item['field_name_en'])->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_RICH_TEXT:
                        $table->longText($item['field_name_en'])->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_SWITCH:
                        $table->tinyInteger($item['field_name_en'])->default(0)->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_DATE_TIME_PICKER:
                        $table->dateTime($item['field_name_en'])->comment($item['comment'] ?: $item['field_name']);
                        break;
                    case CrudFormEnum::FORM_DATE_PICKER:
                        $table->date($item['field_name_en'])->comment($item['comment'] ?: $item['field_name']);
                        break;
                }
            }
        });

        $this->transaction(function () use ($crudId, $data) {
            $this->dao->insert($data);
            $this->dao->update([
                'crud_id'       => $crudId,
                'field_name_en' => 'created_at',
            ], [
                'prev_field' => $data[count($data) - 1]['field_name_en'],
            ]);
        });

        event('system.crud');
    }

    /**
     * 删除字段.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    protected function deleteAlter(string $tableName, string $field)
    {
        $tableName = app()->make(SystemCrudService::class)->getTableName($tableName);
        $field = addslashes($field);
        $sql = "ALTER TABLE `{$tableName}` DROP `{$field}`";
        return DB::select($sql);
    }

    /**
     * 修改字段.
     * @param string $tableName
     * @param string $field
     * @param string $changeFiled
     * @param string $prevFiled
     * @param string $type
     * @param string $limit
     * @param string $default
     * @param string $comment
     * @param array $options
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    protected function updateAlter(string $tableName, string $field, string $changeFiled, string $prevFiled, string $type, $limit = '', string $default = '', string $comment = '', array $options = [])
    {
        $tableName = app()->make(SystemCrudService::class)->getTableName($tableName);
        $comment = addslashes($comment);
        $field = addslashes($field);
        $changeFiled = addslashes($changeFiled);
        $prevFiled = addslashes($prevFiled);
        $type = addslashes($type);
        $default = addslashes($default);
        if ($prevFiled) {
            $after = "AFTER `{$prevFiled}`";
        } else {
            $after = '';
        }
        if (isset($options['default_type'])) {
            switch ($options['default_type']) {
                case ' - 1':
                    $default = 'NULL';
                    break;
                case '1':// 自定义
                    $default = "NOT NULL DEFAULT '{$default}'";
                    break;
                case '2':// 为null
                    $default = 'NULL default NULL';
                    break;
                case '3':// 时间
                    $default = 'NULL default CURRENT_TIMESTAMP';
                    break;
            }
        }
        if (in_array(strtolower($type), ['text', 'longtext', 'tinytext'])) {
            $sql = "ALTER TABLE `{$tableName}` CHANGE `{$field}` `{$changeFiled}` {$type} CHARACTER SET utf8mb4 COLLATE " . SystemCrudService::TABLR_COLLATION . " NULL COMMENT '{$comment}' {$after};";
        } elseif (strtolower($type) == 'enum') {
            $enum = [];
            foreach ($options['options'] as $option) {
                $enum[] = "'{$option}'";
            }
            $enumStr = implode(',', $enum);
            $sql = "ALTER TABLE `{$tableName}` CHANGE `{$field}` `{$changeFiled}` {$type}({$enumStr}) {$default} COMMENT '{$comment}' {$after};";
        } else {
            if (is_null($limit)) {
                $type = "{$type}";
            } else {
                $type = "{$type}({$limit})";
            }

            // 处理时间字段默认值
            if (in_array(strtolower($type), ['datetime', 'timestamp', 'time', 'date', 'year'])) {
                switch ($field) {
                    case 'deleted_at':
                        $default = 'NULL default NULL';
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $default = 'NOT NULL default CURRENT_TIMESTAMP';
                        break;
                }
            }
            $sql = "ALTER TABLE `{$tableName}` CHANGE `{$field}` `{$changeFiled}` {$type} {$default} COMMENT '{$comment}' {$after};";
        }
        return DB::select($sql);
    }

    /**
     * 删除索引.
     * @param string $tableName
     * @param string $field
     * @return array
     * @throws BindingResolutionException
     */
    protected function deleteIndex(string $tableName, string $field)
    {
        $tableName = app()->make(SystemCrudService::class)->getTableName($tableName);
        $field = addslashes($field);
        return DB::select("ALTER TABLE `{$tableName}` DROP INDEX `{$field}`;");
    }

    /**
     * 新增字段.
     * @param string $tableName
     * @param string $field
     * @param string $prevFiled
     * @param string $type
     * @param string $limit
     * @param string $default
     * @param string $comment
     * @param array $options
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    protected function addAlter(string $tableName, string $field, string $prevFiled, string $type, $limit = '', string $default = '', string $comment = '', array $options = [])
    {
        $tableName = app()->make(SystemCrudService::class)->getTableName($tableName);
        $comment = addslashes($comment);
        $field = addslashes($field);
        $prevFiled = addslashes($prevFiled);
        $type = addslashes($type);
        $default = addslashes($default);
        if ($prevFiled) {
            $after = "AFTER `{$prevFiled}`";
        } else {
            $after = '';
        }
        if (isset($options['default_type'])) {
            switch ($options['default_type']) {
                case ' - 1':
                    $default = 'NULL';
                    break;
                case '1':// 自定义
                    $default = "NOT NULL DEFAULT '{$default}'";
                    break;
                case '2':// 为null
                    $default = 'NULL default NULL';
                    break;
                case '3':// 时间
                    $default = 'NULL default CURRENT_TIMESTAMP';
                    break;
            }
        }
        if (in_array(strtolower($type), ['text', 'longtext', 'tinytext'])) {
            $sql = "ALTER TABLE `{$tableName}` ADD `{$field}` {$type} NULL COMMENT '{$comment}' {$after};";
        } else {
            $defaultSql = $default;
            // 处理时间字段默认值
            if (in_array(strtolower($type), ['datetime', 'timestamp', 'time', 'date', 'year'])) {
                switch ($field) {
                    case 'deleted_at':
                        $defaultSql = 'NULL default NULL';
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $defaultSql = 'NOT NULL default CURRENT_TIMESTAMP';
                        break;
                }
            }

            // 兼容枚举字段
            if (strtolower($type) == 'enum') {
                $enum = [];
                foreach ($options['options'] as $option) {
                    $enum[] = "'{$option}'";
                }
                $enumStr = implode(',', $enum);

                $limitSql = $enumStr ? '(' . $enumStr . ')' : '';
            } else {
                $limitSql = $limit ? '(' . $limit . ')' : '';
            }

            $sql = "ALTER TABLE `{$tableName}` ADD `{$field}` {$type}{$limitSql} {$defaultSql} COMMENT '{$comment}' {$after};";
        }
        return DB::select($sql);
    }

    /**
     * AI 对话生成字段列表.
     *
     * @param string $message 用户自然语言需求
     * @param int $crudId 实体 ID，>0 时注入表结构并过滤已存在字段
     * @param array $fields 当前会话已生成字段，用于增量修改上下文
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function aiDialog(string $message, int $crudId = 0, array $fields = []): array
    {
        $tableContent = '';
        if ($crudId) {
            $table       = app()->get(SystemCrudService::class)->value(['id' => $crudId], 'table_name_en');
            $tablePrefix = config('database.connections.mysql.prefix');
            $sql         = (array) DB::select("SHOW CREATE TABLE `{$tablePrefix}{$table}`");
            $tableContent = "数据表的原始表结构为:\n\n" . $sql[0]->{'Create Table'} . "\n\n已存在表内的字段不用生成，只生成不存在的字段数据 。\n\n";
        }

        $systemContent = <<<EOT
你是一个专业的数据库设计助手，请根据用户描述的业务需求，生成合适的表字段列表。字段规格如下：

字段类型说明：
- switch：布尔值
- input_number：整数
- input_float：精度小数
- input_percentage：百分比
- input_price：金额
- input：文本
- textarea：长文本
- rich_text：富文本
- radio：单选项
- cascader_radio：级联单选 下拉选项
- cascader_address：地址选择
- checkbox：复选项
- tag：标签组
- cascader：级联复选
- date_picker：日期
- date_time_picker：日期时间
- image：图片
- file：文件
- input_select：一对一关联

{$tableContent}

输出要求：
1. 必须生成JSON数组格式
2. 每个字段包含四个属性：
   - field_name：字段中文名
   - field_name_en：字段英文名（使用小写蛇形命名，如user_name）
   - value：字段类型（从上述规格中选择）
   - comment：字段描述

示例格式：
[
  {"field_name": "用户名", "field_name_en": "username", "value": "input", "comment": "用户的名称"},
  {"field_name": "年龄", "field_name_en": "age", "value": "input_number", "comment": "用户的年龄"}
]

请根据用户描述的业务需求，生成合适的字段列表。只需返回JSON数组，不要包含任何解释性文字。
EOT;
        $messages = [
            [
                'content' => $systemContent,
                'role'    => 'system',
            ],
        ];
        if ($fields) {
            $fieldsContent = '目前已经生成的字段为:' . json_encode($fields) . "\n\n如果用户提出修改一生成的字段请在当前字段内修改，返回全量数据";
            $messages[]    = [
                'content' => $fieldsContent,
                'role'    => 'assistant',
            ];
        }
        $messages[] = [
            'content' => $message,
            'role'    => 'user',
        ];

        $res          = (new SmsService())->chat($messages);
        $filesContent = $res['data']['data']['choices'][0]['message']['content'] ?? '';
        if (! $filesContent) {
            throw $this->exception($res['data']['msg'] ?? '生成字段列表失败');
        }

        $fields = json_decode($filesContent, true);
        if (! is_array($fields)) {
            throw $this->exception('生成字段列表失败');
        }

        if ($crudId) {
            $fieldAll     = $this->getCrudTableFieldAllCache($crudId);
            $fieldAllName = array_column($fieldAll->toArray(), 'field_name_en');
            $diffFields   = [];
            foreach ($fields as $item) {
                if (! in_array($item['field_name_en'] ?? '', $fieldAllName, true)) {
                    $diffFields[] = $item;
                }
            }
            $fields = $diffFields;
        }

        return $fields;
    }

    /**
     * 添加索引.
     * @return array
     */
    protected function addIndex(string $tableName, string $field, string $type = 'UNIQUE')
    {
        $tableName = app()->make(SystemCrudService::class)->getTableName($tableName);
        $field = addslashes($field);
        return DB::select("ALTER TABLE `{$tableName}` ADD {$type} `{$field}` (`{$field}`);");
    }
}
