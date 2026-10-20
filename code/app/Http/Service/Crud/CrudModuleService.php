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

namespace App\Http\Service\Crud;

use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudLogTypeEnum;
use App\Constants\Crud\CrudOperatorEnum;
use App\Constants\Crud\CrudTriggerEnum;
use App\Http\Dao\Crud\CrudModuleDao;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\LabelService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\DictTypeService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\System\RolesService;
use App\Jobs\Crud\CrudLogJob;
use App\Jobs\Crud\SystemCrudImportDataJob;
use App\Task\message\StatusChangeTask;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 实体虚拟模型
 * Class CrudModuleService.
 * @email 136327134@qq.com
 * @date 2024/3/1
 * @mixin CrudModuleDao
 */
class CrudModuleService extends BaseService
{
    /**
     * CrudModuleService constructor.
     */
    public function __construct(CrudModuleDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 设置模型表名.
     * @return CrudModuleDao
     * @email 136327134@qq.com
     * @date 2024/3/1
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function model(string $tableName = '', int $crudId = 0)
    {
        if (!$tableName && !$crudId) {
            throw $this->exception('缺少参数');
        }
        $service = app()->get(SystemCrudService::class);
        if ($tableName && !$service->tableNameEnByCrudIdCache($tableName)) {
            throw $this->exception('没有查询到表名');
        }
        $tableName = $crudId ? $service->getCrudTableNameEnCache($crudId) : $tableName;
        if (!$tableName) {
            throw $this->exception('没有查询到表名');
        }
        return (new CrudModuleDao())->setTableName($tableName);
    }

    /**
     * 获取表格展示字段缓存.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getShowTableFieldCache(int $crudId, int $uid = 0, bool $association = false, bool $isFieldAll = false)
    {
        return Cache::tags(SystemCrudService::TAG_NAME)->remember('crud_show_table_field_' . $crudId . '_' . $uid . '_' . $association . '_' . $isFieldAll, null, function () use ($crudId, $uid, $association, $isFieldAll) {
            return $this->getShowTableField($crudId, $uid, $association, $isFieldAll);
        });
    }

    /**
     * 获取表格展示字段.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    public function getShowTableField(int $crudId, int $uid = 0, bool $association = false, bool $isFieldAll = false)
    {
        $select = [
            'id',
            'field_name_en',
            'field_name',
            'form_value',
            'association_crud_id',
            'data_dict_id',
            'customize_items',
            'data_type',
        ];

        $fieldService = app()->get(SystemCrudFieldService::class);
        $showField = [];

        if ($isFieldAll === false) {
            // 查找用户自己保存的默认展示字段
            if ($uid) {
                $field = app()->get(SystemCrudTableUserService::class)->value(['crud_id' => $crudId, 'user_id' => $uid], 'show_field');
                if ($field) {
                    $showFieldList = $fieldService->getFieldSelect($crudId, $field, $select);
                    $showField = [];
                    foreach ($field as $item) {
                        foreach ($showFieldList as $value) {
                            if ($item === $value['field_name_en']) {
                                $showField[] = $value;
                            }
                        }
                    }
                }
                unset($field);
            }

            // 如果用户没有保存，查找实体默认展示字段
            if (!$showField) {
                $field = app()->get(SystemCrudTableService::class)->value(['crud_id' => $crudId, 'is_index' => 1], 'show_field');
                if ($field) {
                    $showFieldList = $fieldService->getFieldSelect($crudId, $field, $select);
                    $showField = [];
                    foreach ($field as $item) {
                        foreach ($showFieldList as $value) {
                            if ($item === $value['field_name_en']) {
                                $showField[] = $value;
                            }
                        }
                    }
                }
                unset($field);
            }
        }

        // 如果实体没有保存，查找实体的全部字段展示出来
        if (!$showField) {
            $showField = $fieldService->getFieldTableShowRowSelect($crudId, $select, $isFieldAll);
        }

        return $association ? $this->getFieldAssociation($showField) : $showField;
    }

    /**
     * 获取一对一关联实体的相关信息.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getFieldAssociation(array $showField, string $tableNameEn = '')
    {
        foreach ($showField as $key => $item) {
            // 需要查询关联数据信息
            if ($item['association_crud_id']) {
                $crudInfo = app()->get(SystemCrudService::class)->get($item['association_crud_id'], ['id', 'table_name_en']);
                if ($crudInfo) {
                    $item['association_crud'] = $crudInfo->toArray();
                    $item['association_crud']['field_main_name_en'] = app()->get(SystemCrudFieldService::class)
                        ->value(['crud_id' => $crudInfo->id, 'is_main' => 1], 'field_name_en');
                }
            }

            if (!isset($item['association_crud'])) {
                $item['association_crud'] = null;
            }

            if ($tableNameEn) {
                $item['table_name_en'] = $tableNameEn;
            }

            $showField[$key] = $item;
        }

        return $showField;
    }

    /**
     * 获取展示的自定义搜索.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getShowSearchFieldCache(int $crudId, int $uid = 0, bool $dataDict = true)
    {
        return Cache::tags(SystemCrudService::TAG_NAME)->remember(__FUNCTION__ . $crudId . '_' . $uid . '_' . ((int)$dataDict), null, function () use ($crudId, $uid, $dataDict) {
            return $this->getShowSearchField($crudId, $uid, $dataDict);
        });
    }

    /**
     * 获取展示的自定义搜索.
     * @return array
     * @throws BindingResolutionException|\ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getShowSearchField(int $crudId, int $uid = 0, bool $dataDict = true)
    {
        $select = [
            'id',
            'field_name_en',
            'field_name',
            'form_value',
            'data_dict_id',
            'association_show_type',
            'data_type',
            'customize_items',
        ];

        $fieldService = app()->get(SystemCrudFieldService::class);

        $seniorSearch = [];
        // 查找用户自己保存的搜索字段信息
        if ($uid) {
            $field = app()->get(SystemCrudTableUserService::class)->value(['crud_id' => $crudId, 'user_id' => $uid], 'senior_search');
            if ($field) {
                $seniorSearch = $fieldService->getFieldSelect($crudId, $field, $select);
            }
            unset($field);
        }

        if (!$seniorSearch) {
            $field = app()->get(SystemCrudTableService::class)->value(['crud_id' => $crudId, 'is_index' => 1], 'senior_search');
            if ($field) {
                $seniorSearch = $fieldService->getFieldSelect($crudId, $field, $select);
            }
            unset($field);
        }

        if (!$seniorSearch) {
            return [];
        }

        if ($dataDict) {
            foreach ($seniorSearch as $key => $search) {
                // 需要查询数据字段
                if ($search['data_type'] == 1) {
                    $search['data_dict'] = $search['customize_items'];
                } elseif ($search['data_dict_id']) {
                    $search['data_dict'] = app()->get(DictDataService::class)->getTreeData(['type_id' => $search['data_dict_id']]);
                } else {
                    $search['data_dict'] = [];
                }
                $seniorSearch[$key] = $search;
            }
        }

        return $seniorSearch;
    }

    /**
     * 获取列表上能进行排序的字段.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    public function getOrderByField(int $crudId)
    {
        $orderByformValue = [
            CrudFormEnum::FORM_DATE_TIME_PICKER,
            CrudFormEnum::FORM_DATE_PICKER,
            CrudFormEnum::FORM_CASCADER_RADIO,
            CrudFormEnum::FORM_RADIO,
            CrudFormEnum::FORM_INPUT_PERCENTAGE,
            CrudFormEnum::FORM_INPUT_PRICE,
            CrudFormEnum::FORM_INPUT_FLOAT,
            CrudFormEnum::FORM_INPUT_NUMBER,
            CrudFormEnum::FORM_SWITCH,
        ];

        $fieldService = app()->get(SystemCrudFieldService::class);

        $list = $fieldService->getOrderByFieldListCache($crudId, $orderByformValue);
        return $fieldService->mergeSortField($list);
    }

    /**
     * 获取默认的搜索字段.
     * @return array
     */
    public function getkeywordDefaultField(array $fields)
    {
        $field = [];
        foreach ($fields as $item) {
            if (in_array($item['form_value'], [CrudFormEnum::FORM_INPUT, CrudFormEnum::FORM_TEXTAREA])) {
                $field[] = $item['field_name_en'];
            }
        }
        return $field;
    }

    /**
     * 列表查询.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getModuleList(Request $request, SystemCrud $crud, array $defaultWhere, array $orderBy = [], array $viewSearch = [], string $keywordDefault = '', int $viewSearchBoolean = 0, int $crudId = 0, int $crudValue = 0, bool $uni = false, bool $isFieldAll = false, array $options = [])
    {
        $page = (int)$request->post('page', 1);
        $limit = (int)$request->post('limit', 10);

        $crudService = app()->get(SystemCrudService::class);
        $crudFieldService = app()->get(SystemCrudFieldService::class);

        //分组相关的字段和实体验证
        $groupField = $groupTableNameEn = $groupCrudId = $childrenPage = $childrenLimit = $childrenId = null;

        if (!empty($options['group']) && !empty($options['children_limit'])) {
            $childrenPage = $options['children_page'];
            $childrenLimit = $options['children_limit'];
            $childrenId = $options['children_id'];
            if (str_contains($options['group'], '.')) {
                [$groupTableNameEn, $groupFielddNameEn] = explode('.', $options['group']);

                $groupCrudId = $crudService->tableNameEnByCrudIdCache($groupTableNameEn);
                if (!$groupCrudId) {
                    throw $this->exception('分组的实体信息不存在');
                }

                $groupField = $crudFieldService->getCrudTableFieldAllCache($groupCrudId, $groupFielddNameEn);
            } else {
                $groupTableNameEn = $crud->table_name_en;
                $groupField = $crudFieldService->getCrudTableFieldAllCache($crud->id, $options['group']);
            }

            if (!$groupField) {
                throw $this->exception('分组的字段信息不存在');
            }
        }

        if (in_array($crud->table_name_en, SystemCrudService::SYSTEM_TABLE_TABLE)) {
            unset($defaultWhere['user_id'], $defaultWhere['show_search_type']);
        }

        $where = [];

        // 是否在系统设置页面进行调用查询
        $isSystem = $defaultWhere['is_system'] ?? 0;
        unset($defaultWhere['is_system']);
        // 获取视图搜索
        $showSearch = $this->getShowSearchFieldCache($crud->id, $isSystem ? 0 : $defaultWhere['uid'] ?? 0, false);
        foreach ($showSearch as $search) {
            $value = $request->get($search['field_name_en'], '');

            if ($value !== '') {
                $when = [
                    'field_name' => $search['field_name_en'],
                    'form_value' => $search['form_value'],
                    'value'      => $value,
                ];
                $where[] = $when;
            }
        }

        // 默认的字段搜索
        $mainKey = $crudFieldService->getCrudMainFieldNameEnCache($crud->id);
        if ($keywordDefault !== '' && $mainKey) {
            $where[] = [
                'field_name' => $mainKey,
                'form_value' => CrudFormEnum::FORM_INPUT,
                'value'      => $keywordDefault,
            ];
            $searchFields = $this->getkeywordDefaultField($crud->field->toArray());
            foreach ($searchFields as $key) {
                $where[] = [
                    'field_name' => $key,
                    'form_value' => CrudFormEnum::FORM_INPUT,
                    'value'      => $keywordDefault,
                ];
            }
        }

        // 详情中的关联列表展示
        if ($crudValue && $crudId) {
            $associationKey = app()->get(SystemCrudFieldService::class)->getAssociationCrudFieldNameEnCache($crud->id, $crudId);
            if ($associationKey) {
                $where[] = [
                    'field_name' => $associationKey,
                    'form_value' => CrudFormEnum::FORM_RADIO,
                    'value'      => $crudValue,
                ];
            }
        }

        // 实体字段表的展示字段
        $fieldSelect = [
            'id',
            'field_name_en',
            'field_name',
            'form_value',
            'association_crud_id',
            'data_dict_id',
            'data_type',
            'customize_items',
        ];

        // 获取搜索列表展示字段
        $showField = $this->getShowTableFieldCache($crud->id, $isSystem ? 0 : $defaultWhere['uid'] ?? 0, true, $isFieldAll);
        if ($uni) {

            $fields = array_column($showField, 'field_name_en');
            if (!in_array('created_at', $fields)) {
                $fieldInfo = $crudFieldService->getCrudTableFieldAllCache($crud->id, 'created_at', $fieldSelect);
                if ($fieldInfo) {
                    array_push($showField, $fieldInfo);
                }
            }
            if (!in_array('owner_user_id', $fields)) {
                $fieldInfo = $crudFieldService->getCrudTableFieldAllCache($crud->id, 'owner_user_id', $fieldSelect);
                if ($fieldInfo) {
                    array_push($showField, $fieldInfo);
                }
            }
        }

        [$select, $with, $otherWith] = $this->getFieldAndWith($showField);

        array_unshift($select, 'id');

        // 默认ID倒叙
        $orderBy = $orderBy ?: 'id';

        $join = app()->get(SystemCrudService::class)->getJoinCrudData($crud->id, 1);

        $defaultWhere['share_ids'] = app()->get(SystemCrudDataShareService::class)->getUserShareDataIds($crud->id, $defaultWhere['uid'] ?? 0);

        if (isset($defaultWhere['show_search_type'])) {
            switch ($defaultWhere['show_search_type']) {
                case 3:// 共享给我的
                    unset($defaultWhere['user_id']);
                    if (empty($defaultWhere['share_ids'])) {
                        $defaultWhere['share_ids'] = [0];
                    }
                    break;
                case 4:// 我共享的
                    unset($defaultWhere['user_id']);
                    if ($defaultWhere['uid']) {
                        $shareIds = app()->get(SystemCrudShareService::class)->column(['operate_user_id' => $defaultWhere['uid'], 'crud_id' => $crud->id], 'id');
                        $defaultWhere['share_ids'] = app()->get(SystemCrudDataShareService::class)->shareIdByDataIds($crud->id, $shareIds);
                    }
                    if (empty($defaultWhere['share_ids'])) {
                        $defaultWhere['share_ids'] = [0];
                    }
                    break;
            }
        }

        if ($viewSearch) {
            $viewSearch = $this->setViewSearch($viewSearch, $crud->id);
        }
        $model = $this->model(crudId: $crud->id)->setJoin($join)->searchWhere($defaultWhere, $where, $viewSearch, $orderBy, $viewSearchBoolean === 0 ? 'or' : 'and');

        if ($groupTableNameEn && $groupField) {
            $model = $model->groupBy($groupTableNameEn . '.' . $groupField['field_name_en']);
        }

        $count = $model->count();

        if ($join) {
            foreach ($select as &$field) {
                if (!str_contains($field, $crud->table_name_en)) {
                    $field = $crud->table_name_en . '.' . $field;
                } else {
                    $field = $field . ' as ' . str_replace('.', '_', $field);
                }
            }
        }

        if (!$select) {
            $select = [$crud->table_name_en . '.*'];
        }

        if ($groupTableNameEn && $groupField) {
            $groupSelectField = $groupTableNameEn . '.' . $groupField['field_name_en'];

            // 分组字段
            if ($childrenId) {

                $groupValue = $this->model(crudId: $crud->id)
                    ->setJoin($join)
                    ->searchWhere(defaultWhere: $defaultWhere, where: $where, viewSearch: $viewSearch, orderBy: $orderBy, boolean: $viewSearchBoolean === 0 ? 'or' : 'and')
                    ->where($crud->table_name_en . '.id', $childrenId)
                    ->value($groupSelectField);

                $itemList = $this->model(crudId: $crud->id)
                    ->setJoin($join)
                    ->searchWhere(defaultWhere: $defaultWhere, where: $where, viewSearch: $viewSearch, orderBy: $orderBy, boolean: $viewSearchBoolean === 0 ? 'or' : 'and')
                    ->when(count($with), fn($q) => $q->with($with))
                    ->where($groupTableNameEn . '.' . $groupField['field_name_en'], $groupValue)
                    ->select($select)
                    ->forPage($childrenPage, $childrenLimit)
                    ->get()
                    ->toArray();

                return $this->otherWithList(otherWith: $otherWith, list: $itemList, formField: $showField, uni: $uni, shareIds: $defaultWhere['share_ids'], crudTableNameEn: $crud->table_name_en);
            }

            $groupIds = $model->when(count($with), fn($q) => $q->with($with))->select([$groupSelectField . ' as group_id'])->forPage($page, $limit)->get()->toArray();

            // 检测展示的字段中有没有当前分组的字段
            $fields = array_column($showField, 'field_name_en');
            if (in_array($groupSelectField, $fields)) {
                $fieldInfo = $crudFieldService->getCrudTableFieldAllCache($groupCrudId, $groupField['field_name_en'], $fieldSelect);
                if ($fieldInfo) {
                    array_push($showField, $fieldInfo);
                }
            }

            $groupedData = [];

            foreach ($groupIds as $groupId) {

                $modelItem = $this->model(crudId: $crud->id)->setJoin($join)->searchWhere(defaultWhere: $defaultWhere, where: $where, viewSearch: $viewSearch, orderBy: $orderBy, boolean: $viewSearchBoolean === 0 ? 'or' : 'and')->where($groupTableNameEn . '.' . $groupField['field_name_en'], $groupId['group_id']);
                $itemCount = $modelItem->count();

                $itemList = $modelItem->when(count($with), fn($q) => $q->with($with))->select($select)->forPage(1, $childrenLimit)->get()->toArray();
                $itemList = $this->otherWithList(otherWith: $otherWith, list: $itemList, formField: $showField, uni: $uni, shareIds: $defaultWhere['share_ids'], crudTableNameEn: $crud->table_name_en);

                $item = $itemList[0] ?? [];
                $item['children'] = $itemList;
                $item['count'] = $itemCount;

                $groupedData[] = $item;
                unset($modelItem);
            }
            $list = $groupedData;
        } else {
            $list = $model->when(count($with), fn($q) => $q->with($with))->select($select)->forPage($page, $limit)->get()->toArray();

            $list = $this->otherWithList(otherWith: $otherWith, list: $list, formField: $showField, uni: $uni, shareIds: $defaultWhere['share_ids'], crudTableNameEn: $crud->table_name_en);
        }


        return $this->listData($list, $count);
    }

    /**
     * 获取字段信息.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getFieldAndWith(array $showField, string $tableNameEn = '', bool $withTableName = false)
    {
        $select = $with = $otherWith = [];

        foreach ($showField as $item) {
            $select[] = $withTableName ? ($tableNameEn == $item['table_name_en'] ? '' : $item['table_name_en'] . '.') . $item['field_name_en'] : $item['field_name_en'];
            switch ($item['field_name_en']) {
                case 'frame_id':
                    $with['ownerFrame'] = fn($q) => $q->select(['id', 'name']);
                    break;
                case 'owner_user_id':
                    $with['ownerUser'] = fn($q) => $q->select(['id', 'uid', 'name', 'avatar'])->with([
                        'info' => fn($q) => $q->select(['uid', 'type']),
                    ])->withTrashed();
                    break;
                case 'update_user_id':
                    $with['updateUser'] = fn($q) => $q->select(['id', 'name', 'avatar'])->with([
                        'info' => fn($q) => $q->select(['uid', 'type']),
                    ])->withTrashed();
                    break;
                case 'user_id':
                    $with['createUser'] = fn($q) => $q->select(['id', 'name', 'avatar'])->with([
                        'info' => fn($q) => $q->select(['uid', 'type']),
                    ])->withTrashed();
                    break;
            }

            if (isset($item['association_crud'])) {
                $otherWith[] = [
                    'table_name_en'                  => $item['table_name_en'] ?? '',
                    'association_crud_id'            => $item['association_crud_id'],
                    'field_name_en'                  => $item['field_name_en'],
                    'association_table_name_en'      => $item['association_crud']['table_name_en'],
                    'association_field_main_name_en' => $item['association_crud']['field_main_name_en'],
                    'association_field_id_name_en'   => $item['association_crud']['table_name_en'],
                ];
            }
        }

        return [$select, $with, $otherWith];
    }

    /**
     * 获取数据.
     * @email 136327134@qq.com
     * @date 2024/3/20
     * @param mixed $value
     * @return array|mixed
     */
    public function getValueAttribute(string $formValue, $value, string $crudTableNameEn = '')
    {
        switch ($formValue) {
            case CrudFormEnum::FORM_TAG:
            case CrudFormEnum::FORM_CHECKBOX:
            case CrudFormEnum::FORM_CASCADER_ADDRESS:
            case CrudFormEnum::FORM_CASCADER_RADIO:
                // 兼容系统表的字段展示
                if (in_array($formValue, [CrudFormEnum::FORM_CASCADER_RADIO, CrudFormEnum::FORM_CASCADER_ADDRESS]) && $crudTableNameEn && in_array($crudTableNameEn, SystemCrudService::SYSTEM_TABLE_TABLE)) {
                    $value = $value ? json_decode($value, true) : [];
                } else {
                    $value = $value ? array_merge(
                        array_map(
                            fn($vv) => preg_match('/^\d+$/', $vv) && !in_array($formValue, [CrudFormEnum::FORM_CASCADER_RADIO, CrudFormEnum::FORM_CHECKBOX]) ? intval($vv) : $vv,
                            array_filter(
                                explode('/', $value)
                            )
                        )
                    ) : [];
                }
                break;
            case CrudFormEnum::FORM_IMAGE:
            case CrudFormEnum::FORM_FILE:
                $value = $value ? json_decode($value, true) : null;
                break;
            case CrudFormEnum::FORM_CASCADER:
                if (!is_null($value)) {
                    if (preg_match('/\/(\d+\/)+$/', $value)) {
                        $value = explode(',', $value);
                        $dataValue = [];
                        foreach ($value as $item) {
                            $dataValue[] = array_merge(array_filter(explode('/', $item)));
                        }
                        $value = $dataValue;
                    } else {
                        $value = $value ? json_decode($value, true) : null;
                    }
                }
                break;
            case CrudFormEnum::FORM_DATE_PICKER:
            case CrudFormEnum::FORM_DATE_TIME_PICKER:
                if (in_array($value, ['0000-00-00', '0000-00-00 00:00:00'])) {
                    $value = '';
                }
                break;
            case CrudFormEnum::FORM_RICH_TEXT:
                $value = $value ? htmlspecialchars_decode($value) : '';
                break;
        }

        return $value;
    }

    /**
     * 设置数据.
     * @email 136327134@qq.com
     * @date 2024/3/20 /1/1/1/
     * @param mixed $value
     * @return false|mixed|string
     */
    public function setValueAttribute(string $formValue, $value)
    {
        switch ($formValue) {
            case CrudFormEnum::FORM_TAG:
            case CrudFormEnum::FORM_CHECKBOX:
            case CrudFormEnum::FORM_CASCADER_ADDRESS:
            case CrudFormEnum::FORM_CASCADER_RADIO:
                $value = $value ? '/' . implode('/', is_array($value) ? $value : [$value]) . '/' : '';
                break;
            case CrudFormEnum::FORM_IMAGE:
            case CrudFormEnum::FORM_FILE:
                $value = json_encode($value);
                break;
            case CrudFormEnum::FORM_CASCADER:
                $impodeVal = [];
                foreach ((array)$value as $item) {
                    if ($item) {
                        $impodeVal[] = '/' . implode('/', is_array($item) ? $item : []) . '/';
                    }
                }
                $value = implode(',', $impodeVal);
                break;
            case CrudFormEnum::FORM_INPUT_SELECT:
                $value = isset($value[0]) && is_array($value) ? (array_values($value)[0] ?? $value) : ($value['id'] ?? $value);
                break;
            case CrudFormEnum::FORM_RICH_TEXT:
                $value = htmlspecialchars($value);
                break;
        }

        return $value;
    }

    /**
     * 设置值制空.
     * @return int|string
     * @email 136327134@qq.com
     * @date 2024/3/26
     */
    public function setValueEmptyAttribute(string $formValue)
    {
        $value = '';
        switch ($formValue) {
            case CrudFormEnum::FORM_RADIO:
            case CrudFormEnum::FORM_INPUT_PRICE:
            case CrudFormEnum::FORM_INPUT_PERCENTAGE:
            case CrudFormEnum::FORM_INPUT_FLOAT:
            case CrudFormEnum::FORM_INPUT_NUMBER:
            case CrudFormEnum::FORM_SWITCH:
            case CrudFormEnum::FORM_SELECT:
            case CrudFormEnum::FORM_INPUT_SELECT:
                $value = 0;
                break;
        }

        return $value;
    }

    /**
     * 获取创建表单信息.
     * @param int $crudId 关联添加的实体ID
     * @param int $crudValue 关联添加的实体数据的ID
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    public function getCreateForm(SystemCrud $crud, int $crudId = 0, int $crudValue = 0, int $id = 0, bool $uni = false)
    {
        $crudFormService = app()->get(SystemCrudFormService::class);
        $formInfo = $crudFormService->get(where: ['crud_id' => $crud->id, 'is_index' => 1], with: ['crud.children'])?->toArray();
        if (!$formInfo) {
            throw $this->exception('没有查询到表单信息');
        }
        $formInfo['table_name_en'] = $formInfo['crud']['table_name_en'];
        $formInfo['children_table_name_en'] = $formInfo['crud']['children'][0]['table_name_en'] ?? '';
        $formInfo['children_crud_id'] = $formInfo['crud']['children'][0]['id'] ?? 0;
        $fieldName = null;
        $defaultValue = (object)[];
        if ($crudId && $crudValue) {
            $service = app()->get(SystemCrudFieldService::class);
            $fieldName = $service->getAssociationCrudFieldNameEnCache($crud->id, $crudId);
            $mainFieldName = $service->getCrudMainFieldNameEnCache($crudId);
            if ($mainFieldName) {
                $name = $this->model(crudId: $crudId)->viewSearch(viewSearch: [
                    [
                        'field_name' => 'id',
                        'operator'   => CrudOperatorEnum::OPERATOR_EQ,
                        'value'      => $crudValue,
                    ],
                ])->value($mainFieldName);
                $defaultValue = ['id' => $crudValue, 'name' => $name];
            }
        }

        $fieldList = $this->getFormField($crud->id);
        $column = [];

        $dictDataService = app()->get(DictDataService::class);
        $dictData = [];
        if ($fieldList && $formInfo['options']) {
            foreach ($fieldList as $item) {
                $key = $item['crud']['id'] != $crud->id ? $item['crud']['table_name_en'] . '.' . $item['field_name_en'] : $item['field_name_en'];
                if ($fieldName == $key) {
                    $item['disabled'] = true;
                    if (!$id) {
                        $item['defaultValue'] = $defaultValue;
                    }
                }

                if ($item['data_type'] == 1) {
                    $item['data_dict'] = $item['customize_items'];
                } elseif ($item['data_dict_id']) {
                    if (!isset($dictData[$item['data_dict_id']])) {
                        $typeName = app()->get(DictTypeService::class)->value($item['data_dict_id'], 'ident');
                        $dictData[$item['data_dict_id']] = $dictDataService->getTreeData(['type_id' => $item['data_dict_id'], 'type_name' => $typeName]);
                    }
                    $item['data_dict'] = $dictData[$item['data_dict_id']];
                }

                if (!$item['create_modify'] && !$id) {
                    $item['disabled'] = true;
                }
                if (!$item['update_modify'] && $id) {
                    $item['disabled'] = true;
                }
                $item['data_id'] = $id;
                $column[$key] = $item;
            }

            $formInfo['options'] = $crudFormService->getFormOptions($formInfo['options'], $column);
        }

        if ($uni) {
            $columnnew = [];
            $fields = app()->get(SystemCrudFormService::class)->getFormFields($formInfo['options']);
            foreach ($fields as $key) {
                foreach ($column as $item) {
                    if ($key === $item['field_name_en']) {
                        $item['field_name_en'] = $item['crud']['id'] != $crud->id ? $item['crud']['table_name_en'] . '.' . $item['field_name_en'] : $item['field_name_en'];
                        $item['field_name_en'] = str_replace('.', '@', $item['field_name_en']);
                        $columnnew[] = $item;
                    }
                }
            }
            if ($crud->is_form_table && $formInfo['children_crud_id'] && $crud->table_field && $formInfo['children_table_name_en']) {
                $childrenFields = app()->make(SystemCrudFieldService::class)->fieldByListCache($formInfo['children_crud_id'], $crud->table_field, 0);
                foreach ($childrenFields as $index => $item) {
                    if ($item['data_type'] == 1) {
                        $item['data_dict'] = $item['customize_items'];
                    } elseif ($item['data_dict_id']) {
                        if (!isset($dictData[$item['data_dict_id']])) {
                            $typeName = app()->get(DictTypeService::class)->value($item['data_dict_id'], 'ident');
                            $dictData[$item['data_dict_id']] = $dictDataService->getTreeData(['type_id' => $item['data_dict_id'], 'type_name' => $typeName]);
                        }
                        $item['data_dict'] = $dictData[$item['data_dict_id']];
                    }
                    $childrenFields[$index] = $item;
                }
                $columnnew[] = [
                    'id'            => 0,
                    'crud_id'       => 0,
                    'field_name_en' => $crud->table_name_en . '_' . $formInfo['children_table_name_en'],
                    'column'        => $childrenFields,
                    'form_value'    => CrudFormEnum::FORM_TABLE,
                ];
            }
            $column = $columnnew;
        }

        return [
            'form_info'  => $formInfo,
            'crud'       => $crud->toArray(),
            'field_name' => $fieldName,
            'column'     => $column,
        ];
    }

    /**
     * 获取实体列表字段展示和搜索字段展示.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/4/7
     */
    public function getCrudInfo(SystemCrud $crud, int $uid, int $systemCrudId = 0, bool $uni = false, bool $isFieldAll = false)
    {
        $showField = $this->getShowTableField($crud->id, $systemCrudId ? 0 : $uid, false, $isFieldAll);
        $seniorSearch = $this->getShowSearchField($crud->id, $systemCrudId ? 0 : $uid);
        $orderByField = $this->getOrderByField($crud->id);
        $viewSearch = app()->get(SystemCrudService::class)->getEventCrud($crud->id)['field'];
        $crudInfo = $crud->toArray();

        $mainFieldName = '';
        $crudInfo['systemField'] = $crudInfo['customField'] = [];
        $crudInfo['systemListField'] = $crudInfo['customListField'] = [];
        if ($crudInfo['field']) {
            $systemField = [];
            $customField = [];
            $systemListField = [];
            $customListField = [];
            foreach ($crudInfo['field'] as $item) {
                if ($item['is_main']) {
                    $mainFieldName = $item['field_name_en'];
                }
                if ($item['is_default']) {
                    if (!in_array($item['field_name_en'], ['user_id', 'deleted_at'])) {
                        if (!in_array($item['form_value'], [CrudFormEnum::FORM_RICH_TEXT])) {
                            $systemListField[] = $item;
                        }
                        if (!in_array($item['form_value'], [CrudFormEnum::FORM_IMAGE, CrudFormEnum::FORM_FILE, CrudFormEnum::FORM_INPUT, CrudFormEnum::FORM_TEXTAREA, CrudFormEnum::FORM_RICH_TEXT])) {
                            $systemField[] = $item;
                        }
                    }
                } else {
                    if (!in_array($item['form_value'], [CrudFormEnum::FORM_RICH_TEXT])) {
                        $customListField[] = $item;
                    }
                    if (!in_array($item['form_value'], [CrudFormEnum::FORM_IMAGE, CrudFormEnum::FORM_FILE, CrudFormEnum::FORM_INPUT, CrudFormEnum::FORM_TEXTAREA, CrudFormEnum::FORM_RICH_TEXT])) {
                        $customField[] = $item;
                    }
                }
            }
            $crudInfo['systemField'] = $systemField;
            $crudInfo['customField'] = $customField;
            $crudInfo['systemListField'] = $systemListField;
            $crudInfo['customListField'] = $customListField;
        }

        if ($uni) {
            $newShowField = [];
            $mainItem = [];
            foreach ($showField as $item) {
                if ($item['field_name_en'] === $mainFieldName) {
                    $mainItem = $item;
                } else {
                    $newShowField[] = $item;
                }
            }
            if ($mainItem) {
                array_unshift($newShowField, $mainItem);
            }
            $showField = $newShowField;
        }

        $newShowField = [];
        foreach ($showField as $item) {
            if (!in_array($item['form_value'], [CrudFormEnum::FORM_RICH_TEXT])) {
                $newShowField[] = $item;
            }
        }

        $showField = $newShowField;

        $crudInfo['main_field_name'] = $mainFieldName;

        if ($systemCrudId) {
            // 用户系统保存的配置信息用来回显
            $userOptions = app()->get(SystemCrudTableService::class)->get([
                'crud_id'  => $crudInfo['id'],
                'is_index' => 1,
            ])?->toArray();
        } else {
            // 用户自己保存的配置信息用来回显
            $userOptions = app()->get(SystemCrudTableUserService::class)->get([
                'crud_id' => $crudInfo['id'],
                'user_id' => $uid,
            ])?->toArray();

            if (!$userOptions) {
                $userOptions = app()->get(SystemCrudTableService::class)->get([
                    'crud_id'  => $crudInfo['id'],
                    'is_index' => 1,
                ])?->toArray();
            }
        }

        $fieldService = app()->get(SystemCrudFieldService::class);
        $crudService = app()->get(SystemCrudService::class);

        if (!$userOptions) {
            $userOptions = (object)[];
        } else {
            $associationIds = $fieldService->crudByAssociationIds($crud->id);

            if (!empty($userOptions['options']['tab'])) {
                $ids = array_column($userOptions['options']['tab'], 'id');
                $column = $crudService->getCrudTableAll($ids);
                $tab = [];
                foreach ($userOptions['options']['tab'] as $option) {
                    if (isset($column[$option['id']]) && in_array($option['id'], $associationIds)) {
                        $tab[] = $option;
                    }
                }
                $userOptions['options']['tab'] = $tab;
            }
            if (!empty($userOptions['options']['create'])) {
                $ids = array_column($userOptions['options']['create'], 'id');
                $column = $crudService->getCrudTableAll($ids);
                $create = [];
                foreach ($userOptions['options']['create'] as $option) {
                    if (isset($column[$option['id']]) && in_array($option['id'], $associationIds)) {
                        $create[] = $option;
                    }
                }
                $userOptions['options']['create'] = $create;
            }
        }

        $fieldIds = array_column($viewSearch, 'id');
        $association = app()->get(SystemCrudFieldService::class)->fieldIdByAssociationInfo($fieldIds);
        $associationData = [];
        foreach ($association as $item) {
            $associationData[$item['id']] = $item['association'];
        }
        $newViewSearch = [];
        foreach ($viewSearch as $search) {
            $isUser = !empty($associationData[$search['id']]) && $associationData[$search['id']]['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_USER;
            $isFrame = !empty($associationData[$search['id']]) && $associationData[$search['id']]['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_FRAME;
            $newViewSearch[] = [
                'type'                  => $search['form_value'],
                'field'                 => $search['field_name_en'],
                'id'                    => $search['id'],
                'is_user'               => $isUser,
                'is_frame'              => $isFrame,
                'options'               => $search['data_dict'],
                'title'                 => $search['label'],
                'crud_id'               => $search['crud_id'],
                'is_city_show'          => $search['is_city_show'],
                'association_show_type' => $search['association_show_type'] ?? 1,
                'customize_items'       => $search['customize_items'] ?? [],
                'data_type'             => $search['data_type'] ?? 0,
            ];
        }

        $viewSearch = $newViewSearch;

        $associationIds = $fieldService->crudByAssociationIds($crud->id);
        // 关联表信息
        $associationTable = $crudService->getCrudList($associationIds);

        return compact('crudInfo', 'userOptions', 'showField', 'seniorSearch', 'orderByField', 'viewSearch', 'associationTable');
    }

    /**
     * 获取一对一关联展示字段.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getAssociationField(int $fieldId)
    {
        $fieldService = app()->get(SystemCrudFieldService::class);
        $fieldInfo = $fieldService->get($fieldId);
        if (!$fieldInfo) {
            throw $this->exception('没有查询到实体字段信息信息');
        }
        if (!$fieldInfo->association_crud_id) {
            throw $this->exception('没有查询到一对一关联实体信息');
        }

        // 获取主字段展示
        $indexFieldName = $fieldService->value(['crud_id' => $fieldInfo->association_crud_id, 'is_main' => 1], 'field_name_en');

        $associationFieldNames = $fieldInfo->association_field_names;
        if (!is_array($associationFieldNames)) {
            $associationFieldNames = [];
        }
        if (!in_array('id', $associationFieldNames)) {
            array_unshift($associationFieldNames, $indexFieldName, 'id');
        } else {
            array_unshift($associationFieldNames, $indexFieldName);
        }

        // 展示
        $associationField = $fieldService->getFieldSelect($fieldInfo->association_crud_id, $associationFieldNames);

        $indexfieldInfo = [];
        $fields = [];
        foreach ($associationField as $item) {
            if ($indexFieldName != $item['field_name_en']) {
                $fields[] = $item;
            } else {
                $indexfieldInfo = $item;
            }
        }

        array_unshift($fields, $indexfieldInfo);

        return [
            'index_field_name'  => $indexFieldName,
            'association_field' => $fields,
        ];
    }

    /**
     * 获取一对一关联展示列表.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function getAssociationList(int $fieldId, string $keyword = '')
    {
        $fieldService = app()->get(SystemCrudFieldService::class);
        $fieldInfo = $fieldService->get($fieldId, ['*'], ['crud' => fn($q) => $q->select(['id', 'table_name_en'])]);
        if (!$fieldInfo) {
            throw $this->exception('没有查询到实体字段信息信息');
        }
        if (!$fieldInfo->association_crud_id) {
            throw $this->exception('没有查询到一对一关联实体信息');
        }
        if (!$tableName = app()->get(SystemCrudService::class)->getCrudTableNameCache((int)$fieldInfo->association_crud_id)) {
            throw $this->exception('没有查询到一对一关联实体信息');
        }
        // 获取主字段展示
        $indexFieldName = $fieldService->value(['crud_id' => $fieldInfo->association_crud_id, 'is_main' => 1], 'field_name_en');
        if (!$indexFieldName) {
            throw $this->exception('请先前往开发中设置[' . $tableName . ']实体的主字段展示');
        }

        $selectField = [
            'id',
            'field_name_en',
            'field_name',
            'form_value',
            'association_crud_id',
            'data_dict_id',
            'data_type',
            'customize_items',
        ];

        $fieldSelect = $fieldService->getFieldSelect($fieldInfo->association_crud_id, is_array($fieldInfo->association_field_names) ? $fieldInfo->association_field_names : [], $selectField);
        // 获取字段相关关联实体
        $fieldSelect = $this->getFieldAssociation($fieldSelect);

        [$select, $with, $otherWith] = $this->getFieldAndWith($fieldSelect);

        array_push($select, 'id', $indexFieldName);

        [$page, $limit] = $this->getPageValue();

        $where = $this->getSystemTableWhere($fieldInfo->crud->table_name_en);

        $where[] = [
            'form_value' => CrudFormEnum::FORM_INPUT,
            'field_name' => $indexFieldName,
            'value'      => $keyword,
        ];

        $model = $this->model(crudId: $fieldInfo->association_crud_id)->searchWhere(where: $where, orderBy: 'id');

        $count = $model->count();
        $list = $model->when($page && $limit, fn($q) => $q->forPage($page, $limit))
            ->when(count($with), fn($q) => $q->with($with))
            ->select($select)->get()->toArray();

        $list = $this->otherWithList($otherWith, $list, $fieldSelect, indexField: $indexFieldName);

        return compact('list', 'count');
    }

    /**
     * 获取表单提交的字段信息.
     * @email 136327134@qq.com
     * @date 2024/3/13
     * @param mixed $crudId
     * @param mixed $addendumInfo
     * @param null|mixed $crud
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getFormField(int $crudId, string $fieldNameEn = '', $addendumInfo = [], $crud = null)
    {
        if ($crud) {
            $crudId = $crud->id;
        }
        $crudInfo = app()->get(SystemCrudService::class)->getCrudInfoCache(crudId: $crudId);
        $formFields = $crudInfo['form_fields'];
        $tableFields = $crudInfo['table_field'];
        if (!$formFields && (!$tableFields && $crudInfo['is_form_table'])) {
            return [];
        }

        if ($fieldNameEn && !in_array($fieldNameEn, $formFields) && !$crudInfo['is_form_table']) {
            return [];
        }

        $scheduleId = [];
        foreach ($formFields as $field) {
            if ($fieldNameEn && $fieldNameEn != $field) {
                continue;
            }
            if (strstr($field, '.') !== false) {
                [$tableName, $fieldName] = explode('.', $field);
                $cid = app()->get(SystemCrudService::class)->tableNameEnByCrudIdCache($tableName);
                $scheduleId[$cid][] = $fieldName;
            } else {
                $scheduleId[$crudId][] = $field;
            }
        }

        $list = [];
        foreach ($scheduleId as $k => $fields) {
            $res = app()->get(SystemCrudFieldService::class)->fieldByListCache((int)$k, $fields);
            if ($res) {
                $list = array_merge($list, $res);
            }
        }

        if ($crudInfo['is_form_table'] && $tableFields && $addendumInfo) {
            $addendumName = $crud->table_name_en . '_' . $addendumInfo['table_name_en'];
            foreach ($tableFields as $field) {
                $res = app()->get(SystemCrudFieldService::class)->fieldByListCache((int)$addendumInfo['id'], [$field]);
                if ($res) {
                    $merge = [];
                    foreach ($res as $item) {
                        if ($fieldNameEn && $addendumName != $fieldNameEn && $fieldNameEn != $item['field_name_en']) {
                            continue;
                        }
                        $merge[] = $item;
                    }
                    $list = array_merge($list, $merge);
                }
            }
        }

        return $list;
    }

    /**
     * 获取表单字段信息.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getTableFieldFormField(int $crudId)
    {
        $formFields = app()->get(SystemCrudService::class)->getCrudInfoCache(crudId: $crudId)['table_field'];
        if (!$formFields) {
            return [];
        }
        $subordinateCrud = app()->get(SystemCrudService::class)->getSubordinateCrudInfoCache(parentId: $crudId);
        if (!$subordinateCrud) {
            return [];
        }
        $formFields = array_merge(array_unique(array_merge($formFields, ['id'])));
        $fields = app()->get(SystemCrudFieldService::class)->fieldByListCache((int)$subordinateCrud['id'], $formFields, 0, true);

        return [
            'fields'           => $fields,
            'crud_table_name'  => $subordinateCrud['table_name_en'],
            'subordinate_crud' => $subordinateCrud,
        ];
    }

    /**
     * 根据字段获取数据字段详情.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/9
     */
    public function fieldsByFieldInfo(array $fields, int $crudId)
    {
        $scheduleId = [];
        foreach ($fields as $field) {
            if (strstr($field, '.') !== false) {
                [$tableName, $fieldName] = explode('.', $field);
                $cid = app()->get(SystemCrudService::class)->value(['table_name_en' => $tableName], 'id');
                $scheduleId[$cid][] = $fieldName;
            } else {
                $scheduleId[$crudId][] = $field;
            }
        }

        $list = [];
        foreach ($scheduleId as $k => $fields) {
            $res = app()->get(SystemCrudFieldService::class)->fieldByList((int)$k, $fields, 0);
            if ($res) {
                $list = array_merge($list, $res);
            }
        }

        return $list;
    }

    /**
     * 设置视图搜索.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/9
     */
    public function setViewSearch(array $viewSearch, int $crudId, string $keyName = 'field_name')
    {
        $fields = array_column($viewSearch, $keyName);
        if (!$fields) {
            return [];
        }

        $fieldList = $this->fieldsByFieldInfo($fields, $crudId);
        foreach ($viewSearch as &$search) {
            foreach ($fieldList as $item) {
                $key = $item['crud']['id'] == $crudId
                    ? (str_contains($search[$keyName], '.') ? $item['crud']['table_name_en'] . '.' . $item['field_name_en'] : $item['field_name_en'])
                    : $item['crud']['table_name_en'] . '.' . $item['field_name_en'];
                if ($key === $search[$keyName]) {
                    $search['field_type'] = $item['field_type'];
                    $search['form_value'] = $item['form_value'];
                    break;
                }
            }
            if ($keyName !== 'field_name') {
                $search['field_name'] = $search[$keyName];
                unset($search[$keyName]);
            }
        }
        return $viewSearch;
    }

    /**
     * 保存数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/13
     */
    public function saveModule(SystemCrud $crud, array $data, array $systemData = [], int $crudId = 0, int $crudValue = 0)
    {
        $masterData = [];
        $scheduleData = [];
        $subordinateData = [];
        $subordinateInfo = [];

        $model = $this->model(crudId: $crud->id);

        foreach ($data as $item) {
            if ($item['subordinate']) {
                foreach ($item['value'] as $index => $subordinate) {
                    foreach ($subordinate as $k => $v) {
                        $item['value'][$index][$k] = $v = $this->setValueAttribute($item['subordinate'][$k]['form_value'], $v);
                        if ($item['subordinate'][$k]['is_uniqid'] && $model->count([$k => $v])) {
                            throw $this->exception('明细字段' . $k . '的值不能重复');
                        }
                    }
                }
                $value = $item['value'];
                $subordinateData[$item['item']['table_name_en']] = $value;
                $subordinateInfo = $item['item'];
            } else {
                $value = $this->setValueAttribute($item['item']['form_value'], $item['value']);

                if ($item['item']['crud_id'] === $crud->id) {
                    $masterData[$item['item']['field_name_en']] = $value;

                    if ($item['item']['is_uniqid'] && $model->count([$item['item']['field_name_en'] => $value])) {
                        throw $this->exception('字段' . $item['item']['field_name'] . '的值不能重复');
                    }
                } else {
                    $scheduleData[$item['item']['crud']['table_name_en']][$item['item']['field_name_en']] = $value;
                }
            }
        }

        if (!empty($systemData['ip'])) {
            $masterData[$crud->table_name_en . '_ip'] = $systemData['ip'];
        }

        $masterData['user_id'] = $systemData['uid'];
        $masterData['created_at'] = date('Y-m-d H:i:s');
        unset($masterData['update_user_id']);
        if (empty($masterData['owner_user_id'])) {
            $masterData['owner_user_id'] = $systemData['uid'];
        }

        if (empty($masterData['frame_id'])) {
            $masterData['frame_id'] = app()->get(FrameAssistService::class)->value(['user_id' => $systemData['uid'], 'is_mastart' => 1], 'frame_id');
        }
        if (is_null($masterData['frame_id'])) {
            $masterData['frame_id'] = 0;
        }

        // 数据验证事件处理
        $this->handleEvent(crud: $crud, action: CrudTriggerEnum::TRIGGER_CREATED, data: $data, scheduleData: $scheduleData, isDataCheck: true);

        // 有关联的主体在详情中进行添加
        if ($crudId && $crudValue) {
            $fieldName = app()->get(SystemCrudFieldService::class)->getAssociationCrudFieldNameEnCache($crud->id, $crudId);
            if (!$fieldName) {
                throw $this->exception('关联字段没有查询到字段名');
            }
            $masterData[$fieldName] = $crudValue;
        }

        [$masterId, $scheduleData, $subordinateData] = $this->transaction(function () use ($model, $systemData, $crud, $masterData, $scheduleData, $subordinateData, $subordinateInfo) {
            $masterId = $model->getIncId($masterData);

            if (!$subordinateInfo) {
                foreach ($scheduleData as $tableName => &$item) {
                    $item[$crud->table_name_en . '_id'] = $masterId;

                    if (empty($item['user_id'])) {
                        $item['user_id'] = $systemData['uid'];
                    }
                    if (empty($item['owner_user_id'])) {
                        $item['owner_user_id'] = $systemData['uid'];
                    }
                    if (empty($item['created_at'])) {
                        $item['created_at'] = date('Y-m-d H:i:s');
                    }

                    DB::table($tableName)->insert($item);
                }
            } else {
                unset($item);
                foreach ($subordinateData as $tableName => &$item) {
                    foreach ($item as $index => $subordinate) {
                        $subordinate[$crud->table_name_en . '_id'] = $masterId;

                        if (empty($item['user_id'])) {
                            $subordinate['user_id'] = $systemData['uid'];
                        }
                        if (empty($subordinate['owner_user_id'])) {
                            $subordinate['owner_user_id'] = $systemData['uid'];
                        }
                        if (empty($subordinate['created_at'])) {
                            $subordinate['created_at'] = date('Y-m-d H:i:s');
                        }

                        $item[$index] = $subordinate;
                    }

                    DB::table($tableName)->insert($item);
                }
            }

            return [$masterId, $scheduleData, $subordinateData];
        });
        // 处理事件
        $this->handleEvent($crud, CrudTriggerEnum::TRIGGER_CREATED, $masterId, $masterData, $scheduleData);

        // 操作日志
        CrudLogJob::dispatch([
            'uid'          => $systemData['uid'],
            'crud_id'      => $crud->id,
            'data_crud_id' => $crud->id,
            'data_id'      => $masterId,
            'log_type'     => CrudLogTypeEnum::CREATE_TYPE,
        ]);
    }

    /**
     * 更新数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/13
     */
    public function updateModule(SystemCrud $crud, int $id, array $data, array $systemData = [])
    {
        $masterData = [];
        $scheduleData = [];
        $scheduleField = [];
        $subordinateData = [];
        $subordinateDataids = [];
        $scheduleTableName = null;
        $scheduleCrudId = null;

        $model = $this->model(crudId: $crud->id);

        foreach ($data as $item) {
            if ($item['subordinate']) {
                foreach ($item['value'] as $index => $subordinate) {
                    foreach ($subordinate as $k => $v) {
                        if ($k !== 'id') {
                            $item['value'][$index][$k] = $v = $this->setValueAttribute($item['subordinate'][$k]['form_value'], $v);

                            if ($item['subordinate'][$k]['is_uniqid'] && $model->viewSearch(
                                    [
                                        [
                                            'field_name' => 'id',
                                            'operator'   => CrudOperatorEnum::OPERATOR_NOT_EQ,
                                            'value'      => $id,
                                        ],
                                        [
                                            'field_name' => $k,
                                            'operator'   => CrudOperatorEnum::OPERATOR_EQ,
                                            'value'      => $v,
                                        ],
                                    ]
                                )->count()) {
                                throw $this->exception('明细字段' . $k . '的值不能重复');
                            }
                        } else {
                            $subordinateDataids[] = $v;
                        }
                    }
                }
                $value = $item['value'];
                // 附表名
                $scheduleTableName = $item['item']['table_name_en'];
                // 附表crud_id
                $scheduleCrudId = $item['item']['id'];
                // 附表字段
                $scheduleField = array_keys($item['subordinate']);
                // 附表数据
                $subordinateData = $value;
            } else {
                $value = $this->setValueAttribute($item['item']['form_value'], $item['value']);

                if ($item['item']['crud_id'] === $crud->id) {
                    $masterData[$item['item']['field_name_en']] = $value;

                    if ($item['item']['is_uniqid'] && $model->viewSearch([
                            [
                                'field_name' => 'id',
                                'operator'   => CrudOperatorEnum::OPERATOR_NOT_EQ,
                                'value'      => $id,
                            ],
                            [
                                'field_name' => $item['item']['field_name_en'],
                                'operator'   => CrudOperatorEnum::OPERATOR_EQ,
                                'value'      => $value,
                            ],
                        ])->count()) {
                        throw $this->exception('字段' . $item['item']['field_name'] . '的值不能重复');
                    }
                } else {
                    // 附表名
                    $scheduleTableName = $item['item']['crud']['table_name_en'];
                    // 附表crud_id
                    $scheduleCrudId = $item['item']['crud']['id'];
                    // 附表字段
                    $scheduleField[] = $item['item']['field_name_en'];
                    // 附表数据
                    $scheduleData[$item['item']['field_name_en']] = $value;
                }
            }
        }

        $masterData['update_user_id'] = $systemData['uid'];

        $dataInfo = $scheduleInfo = null;
        // 主表的查询
        if ($masterData) {
            $dataInfo = $model->get($id, array_keys($masterData));
            if (!$dataInfo) {
                throw $this->exception('没有查询到数据无法进行修改');
            }
        }
        // 附表的查询
        $scheduleList = [];
        $scheduleInfo = [];
        $list = [];
        if ($scheduleTableName) {
            if ($subordinateDataids) {
                $scheduleList = $this->model(tableName: $scheduleTableName)->searchWhere([
                    [
                        'field_name' => 'id',
                        'operator'   => CrudOperatorEnum::OPERATOR_IN,
                        'value'      => $subordinateDataids,
                    ],
                ])->select($scheduleField)->get()->toArray();
                $list = $this->model(tableName: $scheduleTableName)->searchWhere([
                    [
                        'field_name' => $crud->table_name_en . '_id',
                        'operator'   => CrudOperatorEnum::OPERATOR_EQ,
                        'value'      => $id,
                    ],
                ])->select(['id'])->get()->toArray();
            } else {
                $scheduleInfo = $this->model(tableName: $scheduleTableName)->get([$crud->table_name_en . '_id' => $id], $scheduleField);
            }
        }
        // 数据验证事件处理
        $this->handleEvent(crud: $crud, action: CrudTriggerEnum::TRIGGER_UPDATED, id: $id, data: $data, scheduleData: $scheduleData, isDataCheck: true);

        unset($masterData['user_id'], $masterData['created_at'], $masterData['updated_at']);

        [$masterRes, $scheduleData] = $this->transaction(function () use ($model, $crud, $systemData, $id, $masterData, $scheduleTableName, $scheduleData, $subordinateData, $list) {
            if ($masterData) {
                $masterRes = $model->update($id, $masterData);
            } else {
                $masterRes = true;
            }

            if ($scheduleTableName) {
                if ($subordinateData) {
                    $insertData = [];
                    $ids = [];
                    foreach ($subordinateData as $item) {
                        $exists = false;
                        if (!empty($item['id'])) {
                            $exists = DB::table($scheduleTableName)->where('id', $item['id'])->exists();
                            $ids[] = $item['id'];
                        }
                        if ($exists) {
                            $update = $item;
                            unset($update['id']);
                            DB::table($scheduleTableName)->where('id', $item['id'])->update($update);
                        } else {
                            $item[$crud->table_name_en . '_id'] = $id;
                            $item['update_user_id'] = $systemData['uid'];
                            $item['created_at'] = date('Y-m-d H:i:s');
                            $insertData[] = $item;
                        }
                    }
                    if ($ids) {
                        $baerIds = array_column($list, 'id');
                        $deleteIds = array_diff($baerIds, $ids);
                        if ($deleteIds) {
                            DB::table($scheduleTableName)->whereIn('id', $deleteIds)->delete();
                        }
                    }
                    if ($insertData) {
                        DB::table($scheduleTableName)->insert($insertData);
                    }
                    $scheduleRes = true;
                } else {
                    $exists = DB::table($scheduleTableName)->where($crud->table_name_en . '_id', $id)->exists();

                    // 不存在但字段更新或者附表的数据不存在的时候进行增加
                    if (!isset($systemData['single_field_name']) || !$exists) {
                        $scheduleData['update_user_id'] = $systemData['uid'];
                        $scheduleData['created_at'] = date('Y-m-d H:i:s');
                    }

                    if ($exists) {
                        $scheduleRes = DB::table($scheduleTableName)->where($crud->table_name_en . '_id', $id)->update($scheduleData);
                    } else {
                        $scheduleData[$crud->table_name_en . '_id'] = $id;
                        DB::table($scheduleTableName)->insert($scheduleData);
                        $scheduleRes = true;
                    }
                }
            } else {
                $scheduleRes = true;
            }
            if ((!$masterRes || !$scheduleRes) && isset($systemData['single_field_name'])) {
                throw $this->exception('修改失败或修改的数据没有变化');
            }

            return [$masterData, $scheduleData];
        });

        // 其他事件处理
        $this->handleEvent($crud, CrudTriggerEnum::TRIGGER_UPDATED, $id, $masterData, $scheduleData, $dataInfo?->toArray() ?: [], $scheduleInfo ? $scheduleInfo?->toArray() ?: [] : $scheduleList);

        // 记录日志
        if (isset($systemData['single_field_name'])) {
            $beforeValue = $dataInfo[$systemData['single_field_name']] ?? '';
            $afterValue = $masterData[$systemData['single_field_name']] ?? '';
            if (json_encode($beforeValue) !== json_encode($afterValue)) {
                $crudId = $crud->id;
                if ($scheduleData && $scheduleCrudId) {
                    $beforeValue = $scheduleInfo[$systemData['single_field_name']] ?? '';
                    $afterValue = $scheduleData[$systemData['single_field_name']] ?? '';
                    $crudId = $scheduleCrudId;
                }
                CrudLogJob::dispatch([
                    'uid'                  => $systemData['uid'],
                    'crud_id'              => $crudId,
                    'data_crud_id'         => $crudId,
                    'data_id'              => $id,
                    'change_field_name_en' => $systemData['single_field_name'],
                    'before_value'         => $beforeValue,
                    'after_value'          => $afterValue,
                    'log_type'             => CrudLogTypeEnum::UPDATE_TYPE,
                ]);
            }
        }
    }

    /**
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFindUniModule(SystemCrud $crud, int $id, int $uid = 0)
    {
        $crudService = app()->get(SystemCrudService::class);
        $fieldService = app()->get(SystemCrudFieldService::class);
        $formField = $this->getFormField($crud->id);

        $select = [];
        $fieldColumn = [];
        foreach ($formField as $item) {
            if ($item['crud']['id'] == $crud->id) {
                $select[] = $item['field_name_en'];
                $fieldColumn[$item['field_name_en']] = [
                    'form_value'  => $item['form_value'],
                    'field_name'  => $item['field_name'],
                    'association' => $item['association'] ?? null,
                ];
            }
        }

        array_push($select, 'id');

        $moduleInfo = $this->model(crudId: $crud->id)->get($id, $select)?->toArray();
        if (!$moduleInfo) {
            throw $this->exception('没有查询到' . $crud->table_name . '信息');
        }

        /** @var AdminService $adminService */
        $adminService = app()->get(AdminService::class)->getModel(false);
        $frameService = app()->get(FrameService::class);

        foreach ($moduleInfo as $key => $value) {
            $formValue = $fieldColumn[$key] ?? null;
            if (!$formValue) {
                continue;
            }

            switch ($key) {
                case 'user_id':
                case 'update_user_id':
                case 'owner_user_id':
                    if ($value) {
                        $moduleInfo[$key] = $adminService->where('id', $value)->select(['id', 'name'])->first()?->toArray();
                    }
                    break;
                case 'frame_id':
                    if ($value) {
                        $moduleInfo[$key] = $frameService->get($value, ['id', 'name'])?->toArray();
                    }
                    break;
            }

            if ($formValue['association']) {
                $fieldName = $fieldService->value(['crud_id' => $formValue['association']['id'], 'is_main' => 1], 'field_name_en');
                if (!$fieldName) {
                    continue;
                }
                $name = $this->dao->setTableName($formValue['association']['table_name_en'])->value($value, $fieldName);
                if ($name) {
                    $moduleInfo[$key] = ['id' => $value, 'name' => $name];
                }
            }
        }

        unset($formValue, $key, $value);

        $detailsList = [];
        // 获取附表信息
        $scheduleInfo = $this->getTableFieldFormField($crud->id);
        if ($scheduleInfo) {
            $select = ['crud_id', 'is_form', 'data_dict_id', 'options', 'id', 'field_name_en', 'field_name', 'form_value'];
            $scheduleCrud = $crudService->get($scheduleInfo['subordinate_crud']['id'], ['id', 'table_name_en'], [
                'field' => fn($q) => $q->where('is_form', 1)->select($select),
            ])?->toArray();

            if (!empty($scheduleCrud['field'])) {
                $field = array_column($scheduleCrud['field'], 'field_name_en');

                foreach ($scheduleCrud['field'] as $item) {
                    $fieldColumn[$item['field_name_en']] = [
                        'form_value'  => $item['form_value'],
                        'field_name'  => $item['field_name'],
                        'association' => $item['association'] ?? null,
                    ];
                }

                if ($crud->table_field) {
                    $field = array_unique(array_merge($field, ['id']));
                    $scheduleList = $scheduleNewList = $this->model(crudId: $scheduleCrud['id'])->select([$crud->table_name_en . '_id' => $id], $field)?->toArray();
                    foreach ($scheduleList as $index => $scheduleData) {
                        $scheduleDataNew = [];
                        foreach ($scheduleData as $key => $datum) {
                            $formValue = $fieldColumn[$key] ?? null;
                            if ($key !== 'id') {
                                if (!$formValue) {
                                    continue;
                                }
                                $datum = $this->getValueAttribute($formValue['form_value'], $datum);
                            }

                            switch ($key) {
                                case 'user_id':
                                case 'update_user_id':
                                case 'owner_user_id':
                                    if ($datum) {
                                        $scheduleDataNew[$key] = app()->get(AdminService::class)->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                case 'frame_id':
                                    if ($datum) {
                                        $scheduleDataNew[$key] = $frameService->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                default:
                                    $scheduleDataNew[$key] = $datum;
                                    break;
                            }
                        }
                        $scheduleList[$index] = $scheduleDataNew;
                    }
                    $name = $crud->table_name_en . '_' . $scheduleInfo['crud_table_name'];
                    $moduleInfo[$name] = $scheduleList;
                    $moduleScheduValue = $this->otherWithList([], $scheduleNewList, $scheduleInfo['fields']);
                    foreach ($moduleScheduValue as $key => $value) {
                        $details = [];
                        foreach ($value as $fieldKey => $item) {
                            $formValue = $fieldColumn[$fieldKey] ?? null;
                            if ($formValue) {
                                $details[$fieldKey] = [
                                    'title'      => $formValue['field_name'],
                                    'form_value' => $formValue['form_value'],
                                    'value'      => $item,
                                ];
                            }
                        }
                        $detailsList[$key] = $details;
                    }
                } else {
                    $scheduleData = $this->model(crudId: $scheduleCrud['id'])->get([$crud->table_name_en . '_id' => $id], $field)?->toArray();

                    if ($scheduleData) {
                        foreach ($scheduleData as $key => $datum) {
                            $formValue = $fieldColumn[$key]['form_value'] ?? null;
                            if (!$formValue) {
                                continue;
                            }
                            $datum = $this->getValueAttribute($formValue, $datum);

                            switch ($key) {
                                case 'user_id':
                                case 'update_user_id':
                                case 'owner_user_id':
                                    if ($datum) {
                                        $moduleInfo[$scheduleCrud['table_name_en'] . '@' . $key] = app()->get(AdminService::class)->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                case 'frame_id':
                                    if ($datum) {
                                        $moduleInfo[$scheduleCrud['table_name_en'] . '@' . $key] = $frameService->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                default:
                                    $moduleInfo[$scheduleCrud['table_name_en'] . '@' . $key] = $datum;
                                    break;
                            }
                        }
                    }
                }
            }
        }

        $associationIds = $fieldService->crudByAssociationIds($crud->id);
        // 关联表信息
        $associationTable = $crudService->getCrudList($associationIds);

        $moduleValue = $this->otherWithList([], [$moduleInfo], $formField)[0];

        $values = [];
        foreach ($moduleValue as $key => $value) {
            if (isset($fieldColumn[$key])) {
                $values[] = [
                    'title'      => $fieldColumn[$key]['field_name'],
                    'form_value' => $fieldColumn[$key]['form_value'],
                    'value'      => $value,
                ];
            }
        }

        foreach ($moduleInfo as $key => $value) {
            $formValue = $fieldColumn[$key] ?? null;
            if (!$formValue) {
                continue;
            }
            $moduleInfo[$key] = $this->getValueAttribute($formValue['form_value'], $value);
        }

        if ($detailsList) {
            $values[] = [
                'field_name' => '明细数据',
                'form_value' => CrudFormEnum::FORM_TABLE,
                'value'      => $detailsList,
            ];
        }

        return [
            'values'            => $values,
            'module_info'       => $moduleInfo,
            'association_table' => $associationTable,
        ];
    }

    /**
     * 获取实体数据.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    public function getFindModule(SystemCrud $crud, int $id)
    {
        $crudService = app()->get(SystemCrudService::class);
        $fieldService = app()->get(SystemCrudFieldService::class);
        $formField = $this->getFormField($crud->id);

        $select = [];
        $fieldColumn = [];
        foreach ($formField as $item) {
            if ($item['crud']['id'] == $crud->id) {
                $select[] = $item['field_name_en'];
                $fieldColumn[$item['field_name_en']] = [
                    'form_value'  => $item['form_value'],
                    'association' => $item['association'] ?? null,
                ];
            }
        }

        array_push($select, 'id');

        $moduleInfo = $this->model(crudId: $crud->id)->get($id, $select)?->toArray();
        if (!$moduleInfo) {
            throw $this->exception('没有查询到' . $crud->table_name . '信息');
        }

        /** @var AdminService $adminService */
        $adminService = app()->get(AdminService::class)->getModel(false);
        $frameService = app()->get(FrameService::class);

        foreach ($moduleInfo as $key => $value) {
            $formValue = $fieldColumn[$key] ?? null;
            if (!$formValue) {
                continue;
            }
            $moduleInfo[$key] = $this->getValueAttribute($formValue['form_value'], $value);

            switch ($key) {
                case 'user_id':
                case 'update_user_id':
                case 'owner_user_id':
                    if ($value) {
                        $moduleInfo[$key] = $adminService->where('id', $value)->select(['id', 'name'])->first()?->toArray();
                    }
                    break;
                case 'frame_id':
                    if ($value) {
                        $moduleInfo[$key] = $frameService->get($value, ['id', 'name'])?->toArray();
                    }
                    break;
            }
            switch ($formValue['form_value']) {
                case CrudFormEnum::FORM_IMAGE:
                case CrudFormEnum::FORM_FILE:
                    if (is_null($moduleInfo[$key])) {
                        $moduleInfo[$key] = [];
                    }
                    break;
            }

            if ($formValue['association']) {
                $fieldName = $fieldService->value(['crud_id' => $formValue['association']['id'], 'is_main' => 1], 'field_name_en');
                if (!$fieldName) {
                    continue;
                }
                $name = $this->dao->setTableName($formValue['association']['table_name_en'])->value($value, $fieldName);
                if ($name) {
                    $moduleInfo[$key] = ['id' => $value, 'name' => $name];
                }
            }
        }
        unset($formValue, $key, $value);

        // 获取附表信息
        $scheduleInfo = $this->getTableFieldFormField($crud->id);
        if ($scheduleInfo) {
            $select = ['crud_id', 'is_form', 'data_dict_id', 'options', 'id', 'field_name_en', 'field_name', 'form_value'];
            $scheduleCrud = $crudService->get($scheduleInfo['subordinate_crud']['id'], ['id', 'table_name_en'], [
                'field' => fn($q) => $q->where('is_form', 1)->select($select),
            ])?->toArray();

            if (!empty($scheduleCrud['field'])) {
                $field = array_column($scheduleCrud['field'], 'field_name_en');

                $fieldColumn = [];
                foreach ($scheduleCrud['field'] as $item) {
                    $fieldColumn[$item['field_name_en']] = $item['form_value'];
                }

                if ($crud->table_field) {
                    $field = array_unique(array_merge($field, ['id']));
                    $scheduleList = $this->model(crudId: $scheduleCrud['id'])->select([$crud->table_name_en . '_id' => $id], $field)?->toArray();
                    foreach ($scheduleList as $index => $scheduleData) {
                        $scheduleDataNew = [];
                        foreach ($scheduleData as $key => $datum) {
                            $formValue = $fieldColumn[$key] ?? null;
                            if ($key !== 'id') {
                                if (!$formValue) {
                                    continue;
                                }
                                $datum = $this->getValueAttribute($formValue, $datum);
                            }

                            switch ($key) {
                                case 'user_id':
                                case 'update_user_id':
                                case 'owner_user_id':
                                    if ($datum) {
                                        $scheduleDataNew[$scheduleInfo['crud_table_name'] . '@' . $key] = app()->get(AdminService::class)->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                case 'frame_id':
                                    if ($datum) {
                                        $scheduleDataNew[$scheduleInfo['crud_table_name'] . '@' . $key] = $frameService->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                default:
                                    $scheduleDataNew[$scheduleInfo['crud_table_name'] . '@' . $key] = $datum;
                                    break;
                            }
                        }
                        $scheduleList[$index] = $scheduleDataNew;
                    }
                    $name = $crud->table_name_en . '_' . $scheduleInfo['crud_table_name'];
                    $moduleInfo[$name] = $scheduleList;
                } else {
                    $scheduleData = $this->model(crudId: $scheduleCrud['id'])->get([$crud->table_name_en . '_id' => $id], $field)?->toArray();

                    if ($scheduleData) {
                        foreach ($scheduleData as $key => $datum) {
                            $formValue = $fieldColumn[$key] ?? null;
                            if (!$formValue) {
                                continue;
                            }
                            $datum = $this->getValueAttribute($formValue, $datum);

                            switch ($key) {
                                case 'user_id':
                                case 'update_user_id':
                                case 'owner_user_id':
                                    if ($datum) {
                                        $moduleInfo[$scheduleCrud['table_name_en'] . '@' . $key] = app()->get(AdminService::class)->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                case 'frame_id':
                                    if ($datum) {
                                        $moduleInfo[$scheduleCrud['table_name_en'] . '@' . $key] = $frameService->get($datum, ['id', 'name'])?->toArray();
                                    }
                                    break;
                                default:
                                    $moduleInfo[$scheduleCrud['table_name_en'] . '@' . $key] = $datum;
                                    break;
                            }
                        }
                    }
                }
            }
        }

        $associationIds = $fieldService->crudByAssociationIds($crud->id);
        // 关联表信息
        $associationTable = $crudService->getCrudList($associationIds);

        // 获取审批
        // $approve = app()->get(SystemCrudApproveService::class)->getCrudApproveList($crud->id);

        return [
            'module_info'       => $moduleInfo,
            'association_table' => $associationTable,
            // 'approve'           => $approve
        ];
    }

    /**
     * 删除数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    public function deleteModule(SystemCrud $crud, int $id, array $systemUserIds = [])
    {
        $model = $this->model(crudId: $crud->id);

        $moduleInfo = $model->get($id);
        if (!$moduleInfo) {
            throw $this->exception('没有查询到' . $crud->table_name . '信息');
        }

        if (isset($moduleInfo->owner_user_id) && !in_array($moduleInfo->owner_user_id, $systemUserIds)) {
            throw $this->exception('暂无权限在' . $crud->table_name . '表中删除数据');
        }

        //        $this->handleEvent(crud: $crud, action: CrudTriggerEnum::TRIGGER_DELETED, id: $id, isDataCheck: true);

        // 处理事件
        $this->handleEvent($crud, CrudTriggerEnum::TRIGGER_DELETED, id: $id);

        $shareService = app()->get(SystemCrudDataShareService::class);

        $subordinateInfo = app()->get(SystemCrudService::class)->getSubordinateCrudInfoCache($crud->id);

        $this->transaction(function () use ($moduleInfo, $shareService, $crud, $id, $subordinateInfo) {
            $moduleInfo->delete();

            $shareService->delete(['crud_id' => $crud->id, 'data_id' => $id]);

            if ($subordinateInfo) {
                $this->model(crudId: $subordinateInfo['id'])->getModel()->where($crud->table_name_en . '_id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            }
        });

        Task::deliver(new StatusChangeTask([MessageType::SYSTEM_CRUD_TYPE], -1, $this->entId(false), $id));
    }

    /**
     * 批量删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/4/7
     */
    public function batchDeleteModule(SystemCrud $crud, array $ids)
    {
        $model = $this->model(crudId: $crud->id);

        $moduleList = $model->viewSearch(viewSearch: [
            [
                'field_name' => 'id',
                'operator'   => CrudOperatorEnum::OPERATOR_IN,
                'value'      => $ids,
            ],
        ])->select('id')->get()->toArray();
        if (!$moduleList) {
            throw $this->exception('没有查询到' . $crud->table_name . '信息');
        }

        foreach ($moduleList as $item) {
            $this->handleEvent(crud: $crud, action: CrudTriggerEnum::TRIGGER_DELETED, id: $item['id'], isDataCheck: true);

            $model->searchWhere(where: [
                [
                    'field_name' => 'id',
                    'form_value' => CrudFormEnum::FORM_SWITCH,
                    'value'      => $item['id'],
                ],
            ])->delete();

            // 处理事件
            $this->handleEvent($crud, CrudTriggerEnum::TRIGGER_DELETED, $item['id']);
        }

        $subordinateInfo = app()->get(SystemCrudService::class)->getSubordinateCrudInfoCache($crud->id);

        if ($subordinateInfo) {
            $this->model(crudId: $subordinateInfo['id'])->getModel()->whereIn($crud->table_name_en . '_id', $ids)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function runImportData(SystemCrud $crud, array $datas, int $uid)
    {
        if (count($datas) > 500) {
            $res = [];
            collect($datas)->chunk(500)->each(function ($item, $key) use ($crud, $uid, &$res) {
                if ($key === 0) {
                    // 看是否有报错
                    $res = $this->importData($crud, $item->toArray(), $uid);
                } else {
                    SystemCrudImportDataJob::dispatch($crud, $item->toArray(), $uid);
                }
            });
            return [
                'sumCount'      => count($datas),
                'errorCount'    => $res['errorCount'] ?? 0,
                'successCount'  => count($datas),
                'errorMessages' => $res['errorMessages'] ?? '',
            ];
        }
        return $this->importData($crud, $datas, $uid);
    }

    /**
     * 导入数据.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function importData(SystemCrud $crud, array $datas, int $uid)
    {
        $datas = array_merge($datas);
        $crudInfo = $crud->toArray();
        $fieldsAll = $crudInfo['field'];
        $keys = array_keys($datas[0]);

        $secondaryKeys = $masterField = [];
        foreach ($keys as $key) {
            if (str_contains($key, '.')) {
                $secondaryKeys[] = substr($key, strpos($key, '.') + 1, -1);
            } elseif ($key) {
                $masterField[] = $key;
            }
        }

        if ($secondaryKeys) {
            $secondaryId = app()->get(SystemCrudService::class)->value(['crud_id' => $crud->id], 'id');
            if (!$secondaryId) {
                throw $this->exception('没有查询到副表的信息');
            }
            $select = ['crud_id', 'field_name_en', 'is_main', 'field_name',
                       'form_value', 'field_type', 'is_default', 'is_uniqid', 'data_dict_id', 'association_crud_id'];
            $secondaryFieldList = app()->get(SystemCrudFieldService::class)->getFieldSelect($secondaryId, $secondaryKeys, $select);
            $secondaryFields = array_column($secondaryFieldList, 'field_name_en');
            if (array_diff($secondaryFields, $secondaryKeys)) {
                throw $this->exception('导入的字段不存在无法导入');
            }

            $fieldsAll = array_merge($fieldsAll, $secondaryFieldList);
        }

        $fields = array_column($crudInfo['field'], 'field_name_en');
        foreach ($masterField as $item) {
            if (!in_array($item, $fields)) {
                throw $this->exception('导入的字段不存在，无法导入！');
            }
        }

        $model = $this->model(tableName: $crud->table_name_en);

        $fieldNameEns = array_column($fieldsAll, 'field_name_en');
        $errorCount = $successCount = 0;

        $errorMessages = [];
        // 修改的数据权限
        $systemUserId = app()->get(RolesService::class)->getDataUids($uid, $crud->table_name_en, 3);
        foreach ($datas as $data) {
            if (count(array_diff($keys, array_keys($data)))) {
                ++$errorCount;
                continue;
            }
            $uniqidField = null;
            foreach ($data as $key => $value) {
                if (!$key) {
                    unset($data[$key]);
                    continue;
                }
                if ($value === '--') {
                    $value = '';
                }
                $index = array_search($key, $fieldNameEns);
                if ($index === false) {
                    break;
                }
                $fieldInfo = $fieldsAll[$index] ?? null;
                if (!$fieldInfo) {
                    break;
                }
                if ($fieldInfo['is_uniqid']) {
                    $uniqidField[$key] = $value;
                }

                switch ($fieldInfo['form_value']) {
                    case CrudFormEnum::FORM_TAG:
                        if ($fieldInfo['data_dict_id']) {
                            $data[$key] = app()->get(DictDataService::class)
                                ->getValuesByIds($value, $fieldInfo['data_dict_id']);
                        } else {
                            $value = array_filter(explode('/', $value));
                            $data[$key] = app()->get(LabelService::class)->getIdsByName($value);
                        }
                        break;
                    case CrudFormEnum::FORM_CHECKBOX:
                    case CrudFormEnum::FORM_CASCADER_ADDRESS:
                    case CrudFormEnum::FORM_CASCADER_RADIO:
                        // 复选吧点转为斜杠
                        if ($fieldInfo['form_value'] === CrudFormEnum::FORM_CHECKBOX) {
                            $value = str_replace('、', '/', $value);
                        }
                        $data[$key] = app()->get(DictDataService::class)
                            ->getValuesByIds($value, $fieldInfo['data_dict_id']);
                        break;
                    case CrudFormEnum::FORM_RADIO:
                        $data[$key] = app()->get(DictDataService::class)
                                          ->getValuesByIds($value, $fieldInfo['data_dict_id'])[0] ?? 0;
                        break;
                    case CrudFormEnum::FORM_CASCADER:
                        $data[$key] = [app()->get(DictDataService::class)
                                           ->getValuesByIds($value, $fieldInfo['data_dict_id'])];
                        break;
                    case CrudFormEnum::FORM_DATE_PICKER:
                    case CrudFormEnum::FORM_DATE_TIME_PICKER:
                        $data[$key] = str_replace('/', '-', $value);
                        break;
                }
                if (in_array($fieldInfo['form_value'], [CrudFormEnum::FORM_FILE, CrudFormEnum::FORM_IMAGE, CrudFormEnum::FORM_RICH_TEXT])) {
                    // 图片和附件禁止导入
                    unset($data[$key]);
                } else {
                    $data[$key] = $this->setValueAttribute($fieldInfo['form_value'], $data[$key]);
                }
                // 关联查询
                if (!empty($fieldInfo['association_crud_id']) && $fieldInfo['form_value'] === CrudFormEnum::FORM_INPUT_SELECT) {
                    if ($value) {
                        $tableNameEnValue = app()->get(SystemCrudService::class)->value(['id' => $fieldInfo['association_crud_id']], 'table_name_en');
                        switch ($tableNameEnValue) {
                            case SystemCrudService::SYSTEM_TABLE_TABLE_USER:
                                $userId = app()->get(AdminService::class)->getModel()->where('name', $value)->value('id');
                                if ($userId) {
                                    $data[$key] = $userId;
                                } else {
                                    unset($data[$key]);
                                }
                                break;
                            case SystemCrudService::SYSTEM_TABLE_TABLE_FRAME:
                                $frameId = app()->get(FrameService::class)->value(['name' => $value], 'id');
                                if ($frameId) {
                                    $data[$key] = $frameId;
                                } else {
                                    unset($data[$key]);
                                }
                                break;
                            default:
                                $value = (int)$value;
                                if ($value === 0) {
                                    unset($data[$key]);
                                    break;
                                }
                                $indexFieldName = app()->get(SystemCrudFieldService::class)->value(['crud_id' => $fieldInfo['association_crud_id'], 'is_main' => 1], 'field_name_en');
                                if (!$indexFieldName) {
                                    break;
                                }
                                $associationId = $this->model(tableName: $tableNameEnValue)->value([$indexFieldName => $value], 'id');
                                if ($associationId) {
                                    $data[$key] = $associationId;
                                } else {
                                    unset($data[$key]);
                                }
                                break;
                        }
                    } else {
                        unset($data[$key]);
                    }
                }
            }

            try {
                if ($uniqidField && $dataInfo = $model->get($uniqidField, ['owner_user_id', 'id'])) {
                    if (isset($data['id'])) {
                        unset($data['id']);
                    }
                    if (in_array($dataInfo->owner_user_id, $systemUserId)) {
                        $model->update($dataInfo->id, $data);
                    }
                } else {
                    if (!empty($data['id']) && $dataInfo = $model->get($data['id'], ['owner_user_id', 'id'])) {
                        if (isset($data['id'])) {
                            unset($data['id']);
                        }
                        if (in_array($dataInfo->owner_user_id, $systemUserId)) {
                            $model->update($dataInfo->id, $data);
                        }
                    } else {
                        if (isset($data['id'])) {
                            unset($data['id']);
                        }
                        if (!isset($data['user_id']) && in_array('user_id', $fieldNameEns)) {
                            $data['user_id'] = $uid;
                        }
                        if (!isset($data['owner_user_id']) && in_array('owner_user_id', $fieldNameEns)) {
                            $data['owner_user_id'] = $uid;
                        }
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        $model->create($data);
                    }
                }
                ++$successCount;
            } catch (\Throwable $e) {
                $errorMessages[] = $e->getMessage();
                ++$errorCount;
            }
        }

        return [
            'sumCount'      => count($datas),
            'errorCount'    => $errorCount,
            'successCount'  => $successCount,
            'errorMessages' => $errorMessages,
        ];
    }

    /**
     * 转移数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function transferData(SystemCrud $crud, array $ids, int $userId, array $systemUserId, int $adminUserId)
    {
        $model = $this->model(tableName: $crud->table_name_en);
        $dataList = $model->getModel()->whereIn('id', $ids)->whereIn('owner_user_id', $systemUserId)->select(['id', 'owner_user_id'])->get()->toArray();
        $ids = array_column($dataList, 'id');
        if (!$ids) {
            throw $this->exception('选择的数据没有可以进行转移的权限');
        }

        $res = $model->getModel()->whereIn('id', $ids)->update([
            'owner_user_id' => $userId,
            'user_id'       => $userId,
        ]);

        foreach ($dataList as $item) {
            CrudLogJob::dispatch([
                'uid'                  => $adminUserId,
                'crud_id'              => $crud->id,
                'data_crud_id'         => $crud->id,
                'data_id'              => $item['id'],
                'change_field_name_en' => '',
                'before_value'         => $item['owner_user_id'],
                'after_value'          => $userId,
                'log_type'             => CrudLogTypeEnum::TRANSFER_TYPE,
            ]);
        }

        return $res;
    }

    /**
     * 分享数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function shareData(SystemCrud $crud, array $ids, array $userIds, int $roleType, array $systemUserId, int $operateUserId)
    {
        $model = $this->model(tableName: $crud->table_name_en);
        $dataList = $model->getModel()->whereIn('id', $ids)->whereIn('owner_user_id', $systemUserId)->select(['id'])->get()->toArray();
        $ids = array_column($dataList, 'id');
        if (!$ids) {
            throw $this->exception('选择的数据没有可以进行分享的权限');
        }

        $shareService = app()->get(SystemCrudShareService::class);
        $dataShareService = app()->get(SystemCrudDataShareService::class);

        foreach ($userIds as $userId) {
            $res = $shareService->create([
                'crud_id'         => $crud->id,
                'user_id'         => $userId,
                'role_type'       => $roleType,
                'operate_user_id' => $operateUserId,
            ]);
            if (!$res) {
                throw $this->exception('分享失败');
            }
            foreach ($ids as $id) {
                $info = $dataShareService->get(['crud_id' => $crud->id, 'data_id' => $id, 'user_id' => $userId]);
                if ($info) {
                    $beforeMsg = [];
                    $afterMsg = [];
                    if ($info['is_show']) {
                        $beforeMsg[] = '可查看';
                    }
                    if ($info['is_update']) {
                        $beforeMsg[] = '可编辑';
                    }
                    if ($info['is_delete']) {
                        $beforeMsg[] = '可删除';
                    }

                    $info->share_id = $res->id;
                    $info->is_update = $roleType === 1 ? 1 : $info->is_update;
                    $info->is_delete = $roleType === 2 ? 1 : $info->is_delete;
                    $info->save();

                    if ($info['is_show']) {
                        $afterMsg[] = '可查看';
                    }
                    if ($info['is_update']) {
                        $afterMsg[] = '可编辑';
                    }
                    if ($info['is_delete']) {
                        $afterMsg[] = '可删除';
                    }

                    CrudLogJob::dispatch([
                        'uid'                  => $operateUserId,
                        'crud_id'              => $crud->id,
                        'data_crud_id'         => $crud->id,
                        'data_id'              => $info['data_id'],
                        'change_field_name_en' => $info['user_id'],
                        'before_value'         => implode(',', $beforeMsg),
                        'after_value'          => implode(',', $afterMsg),
                        'log_type'             => CrudLogTypeEnum::SHARE_UPDATE_TYPE,
                    ]);
                } else {
                    $dataShare = [
                        'share_id'  => $res->id,
                        'crud_id'   => $crud->id,
                        'data_id'   => $id,
                        'user_id'   => $userId,
                        'is_show'   => 1,
                        'is_update' => $roleType === 1 ? 1 : 0,
                        'is_delete' => $roleType === 2 ? 1 : 0,
                    ];
                    $dataShareService->create($dataShare);

                    $afterMsg = [];
                    if ($dataShare['is_show']) {
                        $afterMsg[] = '可查看';
                    }
                    if ($dataShare['is_update']) {
                        $afterMsg[] = '可编辑';
                    }
                    if ($dataShare['is_delete']) {
                        $afterMsg[] = '可删除';
                    }

                    CrudLogJob::dispatch([
                        'uid'                  => $operateUserId,
                        'crud_id'              => $crud->id,
                        'data_crud_id'         => $crud->id,
                        'data_id'              => $id,
                        'change_field_name_en' => '',
                        'before_value'         => $userId,
                        'after_value'          => implode(',', $afterMsg),
                        'log_type'             => CrudLogTypeEnum::SHARE_CREATE,
                    ]);
                }
            }
        }
    }

    public function shareUpdate(SystemCrud $crud, int $id, int $roleType, int $operateUserId)
    {
        $shareService = app()->get(SystemCrudShareService::class);
        $dataShareService = app()->get(SystemCrudDataShareService::class);
        $shareService->update($id, [
            'role_type'       => $roleType,
            'operate_user_id' => $operateUserId,
        ]);

        $data = $dataShareService->select(['share_id' => $id], ['id', 'user_id', 'is_show', 'is_update', 'is_delete', 'data_id'])->toArray();
        if ($data) {
            foreach ($data as $info) {
                $update = [
                    'is_show'   => 1,
                    'is_update' => $roleType === 1 ? 1 : 0,
                    'is_delete' => $roleType === 2 ? 1 : 0,
                ];
                $dataShareService->update($info['id'], $update);

                $beforeMsg = [];
                $afterMsg = [];
                if ($info['is_show']) {
                    $beforeMsg[] = '可查看';
                }
                if ($info['is_update']) {
                    $beforeMsg[] = '可编辑';
                }
                if ($info['is_delete']) {
                    $beforeMsg[] = '可删除';
                }

                if ($update['is_show']) {
                    $afterMsg[] = '可查看';
                }
                if ($update['is_update']) {
                    $afterMsg[] = '可编辑';
                }
                if ($update['is_delete']) {
                    $afterMsg[] = '可删除';
                }

                CrudLogJob::dispatch([
                    'uid'                  => $operateUserId,
                    'crud_id'              => $crud->id,
                    'data_crud_id'         => $crud->id,
                    'data_id'              => $info['data_id'],
                    'change_field_name_en' => $info['user_id'],
                    'before_value'         => implode(',', $beforeMsg),
                    'after_value'          => implode(',', $afterMsg),
                    'log_type'             => CrudLogTypeEnum::SHARE_UPDATE_TYPE,
                ]);
            }
        }
        return true;
    }

    /**
     * 删除共享.
     * @throws BindingResolutionException
     */
    public function shareDelete(SystemCrud $crud, int $id, int $userId, int $dataId)
    {
        $shareService = app()->get(SystemCrudShareService::class);
        $dataShareService = app()->get(SystemCrudDataShareService::class);

        $data = $dataShareService->get(['share_id' => $id, 'data_id' => $dataId], ['id', 'user_id', 'data_id']);
        if ($data) {
            $data->delete();
            CrudLogJob::dispatch([
                'uid'                  => $userId,
                'crud_id'              => $crud->id,
                'data_crud_id'         => $crud->id,
                'data_id'              => $dataId,
                'change_field_name_en' => '',
                'before_value'         => '',
                'after_value'          => $data['user_id'],
                'log_type'             => CrudLogTypeEnum::SHARE_DELETE_TYPE,
            ]);

            if (!$dataShareService->count(['share_id' => $id])) {
                $shareService->delete($id);
            }
        }
    }

    /**
     * 用户取消共享.
     * @throws BindingResolutionException
     */
    public function cancelShare(SystemCrud $crud, int $dataId, int $userId)
    {
        $shareService = app()->get(SystemCrudShareService::class);
        $dataShareService = app()->get(SystemCrudDataShareService::class);

        $data = $dataShareService->select(['data_id' => $dataId], ['id', 'user_id', 'data_id', 'share_id'])->toArray();

        foreach ($data as $item) {
            $dataShareService->delete($item['id']);
            CrudLogJob::dispatch([
                'uid'                  => $userId,
                'crud_id'              => $crud->id,
                'data_crud_id'         => $crud->id,
                'data_id'              => $item['data_id'],
                'change_field_name_en' => '',
                'before_value'         => '',
                'after_value'          => $item['user_id'],
                'log_type'             => CrudLogTypeEnum::SHARE_DELETE_TYPE,
            ]);

            if (!$dataShareService->count(['share_id' => $item['share_id']])) {
                $shareService->delete($item['share_id']);
            }
        }
    }

    /**
     * 获取验证规则.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/4/23
     */
    public function getValidationRule(array $item, string $fieldName)
    {
        $message = [];
        $ruleData = [];
        //        if (!empty($item['options']['maxlength'])) {
        //            $ruleData[] = 'max:' . $item['options']['maxlength'];
        //        }
        //        if (!empty($item['options']['minlength'])) {
        //            $ruleData[] = 'min:' . $item['options']['minlength'];
        //        }
        if (!$item['is_default_value_not_null']) {
            $ruleData[] = 'required';
        }
        if (in_array($item['form_value'], [CrudFormEnum::FORM_FILE, CrudFormEnum::FORM_IMAGE])) {
            $ruleData[] = 'array';
            if (!empty($item['options']['max'])) {
                //                $ruleData[] = 'max:' . $item['options']['max'];
            }
        }
        if ($ruleData) {
            $validatorFieldName = str_replace('.', '_', $fieldName);

            foreach ($ruleData as $datum) {
                $msg = '';
                if ($datum == 'required') {
                    $msg = $item['field_name'] . '不能为空';
                } elseif (str_contains($datum, 'max')) {
                    [$var, $num] = explode(':', $datum);
                    $msg = $item['field_name'] . '最大长度：' . $num;
                } elseif (str_contains($datum, 'min')) {
                    [$var, $num] = explode(':', $datum);
                    $msg = $item['field_name'] . '最小长度：' . $num;
                } elseif ($datum === 'array') {
                    $msg = $item['field_name'] . '必须为数组';
                }

                $message[$validatorFieldName . '.' . ($var ?? $datum)] = $msg;
                unset($var);
            }
        }

        return [$message, $ruleData];
    }

    /**
     * 进行验证数据返回验证规则.
     * @return array
     * @throws BindingResolutionException
     */
    public function checkData(SystemCrud $crudInfo, Request $request, string $fieldNameEn = '', mixed $fieldValue = null)
    {
        $crudList = $this->getFormField($crudInfo->id, $fieldNameEn);

        if ($fieldNameEn && count($crudList) > 1) {
            throw $this->exception('字段存在重复，请联系管理员');
        }

        $data = $validatorData = $rule = $message = [];

        foreach ($crudList as $item) {
            $fieldName = $item['crud']['id'] === $crudInfo->id
                ? $item['field_name_en']
                : $item['crud']['table_name_en'] . '.' . $item['field_name_en'];

            $default = '';
            $association = false;
            if ($item['association_crud_id']) {
                $default = [];
                $association = true;
            } elseif (in_array($item['field_name_en'], [SystemCrud::USER_ID_KEY, SystemCrud::UPDATE_USER_ID_KEY, SystemCrud::FRAME_ID_KEY, SystemCrud::OWNER_USER_ID_KEY])) {
                $default = [];
                $association = true;
            }

            $postValue = $request->post(str_replace('.', '@', $fieldName), $default);
            if ($association) {
                $value = is_array($postValue) ? $postValue['id'] ?? 0 : $postValue;
            } else {
                $value = $postValue;
            }

            [$itemMsg, $ruleData] = $this->getValidationRule($item, $fieldName);

            if ($ruleData) {
                $validatorFieldName = str_replace('.', '_', $fieldName);
                $validatorData[$validatorFieldName] = $value;
                $rule[$validatorFieldName] = implode('|', $ruleData);
                $message = array_merge($message, $itemMsg);
            }

            $data[$fieldName] = [
                'value'       => $value,
                'post'        => $postValue,
                'item'        => $item,
                'subordinate' => [],
            ];
        }

        $subordinate = $this->getTableFieldFormField($crudInfo->id);

        if (!$crudList && !$subordinate) {
            throw $this->exception('字段不存在');
        }

        if ($subordinate && !$fieldValue) {
            $postName = $crudInfo->table_name_en . '_' . $subordinate['crud_table_name'];
            $postData = $request->post($postName, []);
            if (!is_array($postData)) {
                throw $this->exception('表单明细提交数据类型错误');
            }
            if (!$postData) {
                throw $this->exception('表单明细数据不能为空');
            }
            $column = [];
            foreach ($subordinate['fields'] as $item) {
                $column[$item['field_name_en']] = $item;
            }
            $subordinateData = [];
            foreach ($postData as $item) {
                $newitem = [];
                foreach ($item as $fieldName => $value) {
                    if (str_contains($fieldName, '@')) {
                        $fieldName = str_replace($subordinate['crud_table_name'] . '@', '', $fieldName);
                    }
                    $fieldInfo = $column[$fieldName] ?? null;
                    if (!$fieldInfo) {
                        throw $this->exception($fieldName . '字段不存在');
                    }
                    if (!$fieldInfo['is_default_value_not_null'] && !$value) {
                        throw $this->exception($fieldInfo['field_name'] . '不能为空');
                    }
                    if (in_array($fieldName, [SystemCrud::USER_ID_KEY, SystemCrud::UPDATE_USER_ID_KEY, SystemCrud::FRAME_ID_KEY, SystemCrud::OWNER_USER_ID_KEY])) {
                        $newitem[$fieldName] = is_array($value) ? $value['id'] ?? 0 : $value;
                    } else {
                        $newitem[$fieldName] = $value;
                    }
                }
                $subordinateData[] = $newitem;
            }
            $data[$postName] = [
                'value'       => $subordinateData,
                'subordinate' => $column,
                'post'        => $subordinateData,
                'item'        => $subordinate['subordinate_crud'],
            ];
        }

        if ($validatorData && $rule && $message) {
            $validator = Validator::make($validatorData, $rule, $message);

            if ($validator->fails()) {
                // 验证失败的处理逻辑
                throw $this->exception($validator->errors()->first());
            }
        }

        return $data;
    }

    /**
     * 执行事件.
     * @return false
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    public function handleEvent(SystemCrud $crud, string $action, int $id = 0, array $data = [], array $scheduleData = [], array $originalData = [], array $originalScheduleData = [], bool $isDataCheck = false)
    {
        $eventService = app()->get(SystemCrudEventService::class);

        $eventIds = $eventService->getEventActionIds($crud->id, $isDataCheck);

        if ($eventIds) {
            $eventId = array_shift($eventIds);
            $event = $eventService->get($eventId);
            if (!$event) {
                return false;
            }

            $eventService->runEvent($crud->id, $action, $event->toArray(), $eventIds, $id, $data, $scheduleData, $originalData, $originalScheduleData);
        }
    }

    /**
     * 获取看板表格展示字段.
     * @throws BindingResolutionException
     */
    public function getListQueryShowTableField(SystemCrud $crud, array $field = []): array
    {
        if (!$field) {
            return [];
        }

        $fieldNameList = app()->get(SystemCrudFieldService::class)->fieldNameByFieldCrud($field, $crud->table_name_en);

        $fieldList = [];
        foreach ($fieldNameList as $item) {
            foreach ($item['field'] as $field) {
                $fieldList[] = [
                    'crud_id'             => $item['crud_info']['id'],
                    'is_main'             => $field['is_main'],
                    'field_name'          => $field['field_name'],
                    'data_type'           => $field['data_type'],
                    'customize_items'     => $field['customize_items'],
                    'form_value'          => $field['form_value'],
                    'is_default'          => $field['is_default'],
                    'data_dict_id'        => $field['data_dict_id'],
                    'field_name_en'       => $field['field_name_en'],
                    'association_crud_id' => $field['association_crud_id'],
                    'association_crud'    => $field['association'] ? [
                        'id'                 => $field['association']['id'],
                        'table_name_en'      => $field['association']['table_name_en'],
                        'field_main_name_en' => app()->get(SystemCrudFieldService::class)->value(['crud_id' => $field['association']['id'], 'is_main' => 1], 'field_name_en'),
                    ] : null,
                    'table_name_en'       => $item['crud_info']['table_name_en'],
                ];
            }
        }
        return $fieldList;
    }

    /**
     * 列表查询.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getListQuery(array $showField, SystemCrud $crud, array $defaultWhere, array $orderBy = [], array $viewSearch = [], int $viewSearchBoolean = 0, int $page = 1, int $limit = 10): array
    {
        if (in_array($crud->table_name_en, SystemCrudService::SYSTEM_TABLE_TABLE) && isset($defaultWhere['user_id'])) {
            unset($defaultWhere['user_id']);
        }

        // 获取关联表字段
        $joinField = [];
        foreach ($showField as $item) {
            if (str_contains($item, '.')) {
                $joinField[] = $item;
            }
        }

        $where = [];
        // 获取搜索列表展示字段
        $showField = $this->getListQueryShowTableField($crud, $showField);
        if (!$showField) {
            return [];
        }

        [$select, $with, $otherWith] = $this->getFieldAndWith($showField, $crud->table_name_en, true);

        foreach ($otherWith as &$item) {
            $item['field_name_as'] = $item['table_name_en'] . '_' . $item['field_name_en'];
        }

        // 默认ID倒叙
        $orderBy = $orderBy ?: 'id';

        $join = app()->get(SystemCrudService::class)->getJoinCrudData($crud->id, 1);
        // 附加条件
        if ($viewSearch) {
            $viewSearch = $this->setViewSearch($viewSearch, $crud->id, 'form_field');
        }

        $model = $this->model(crudId: $crud->id)
            ->setJoin($join)
            ->searchWhere($defaultWhere, $where, $viewSearch, $orderBy, $viewSearchBoolean === 0 ? 'or' : 'and');

        $count = $model->count();

        // 拼接表名
        $select = array_map(function ($item) use ($crud) {
            if (str_contains($item, '.')) {
                $tmp = explode('.', $item);
                return $item . ' as ' . $tmp[0] . '_' . $tmp[1];
            }
            return $crud->table_name_en . '.' . $item . ' as ' . $crud->table_name_en . '_' . $item;
        }, array_merge($select, $joinField));

        if (!$select) {
            $select = [$crud->table_name_en . '.*'];
        }

        $list = $model->when(count($with), fn($q) => $q->with($with))->select($select)->forPage($page, $limit)->get()->toArray();

        $list = $this->otherWithAliasList($otherWith, $list, $showField);
        return $this->listData($list, $count);
    }

    /**
     * 关联字段展示查询和数据转换.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    public function otherWithAliasList(array $otherWith, array $list, array $formField = [])
    {
        foreach ($otherWith as $newWith) {
            $idName = $newWith['field_name_as'] ?? $newWith['field_name_en'];
            if (!$idName) {
                continue;
            }

            if (!$newWith['association_field_main_name_en']) {
                continue;
            }

            $ids = array_column($list, $idName);
            $mainName = $newWith['association_field_main_name_en'];

            $withList = DB::table($newWith['association_table_name_en'])->whereIn('id', $ids)
                ->select(['id', $mainName])->get()->toArray();

            $withData = [];

            foreach ($withList as $item) {
                $item = (array)$item;
                $withData[$item['id']] = $item[$mainName];
            }

            foreach ($list as $index => $value) {
                $value[$idName] = $withData[$value[$idName]] ?? '';
                $list[$index] = $value;
            }
        }

        $column = [];
        foreach ($formField as $item) {
            $column[$item['table_name_en'] . '_' . $item['field_name_en']] = [
                'form_value'      => $item['form_value'],
                'data_dict_id'    => $item['data_dict_id'],
                'table_name_en'   => $item['table_name_en'] ?? '',
                'data_type'       => $item['data_type'],
                'customize_items' => $item['customize_items'],
            ];
        }

        foreach ($list as $index => $value) {
            // 处理字段数据类型
            foreach ($value as $k => $v) {
                if (isset($column[$k])) {
                    $value[$k] = $this->getValueAttribute($column[$k]['form_value'], $value[$k]);
                    if ($column[$k]['data_type'] == 1 && $column[$k]['form_value'] === CrudFormEnum::FORM_RADIO) {
                        foreach ($column[$k]['customize_items'] as $item) {
                            if (($item['value'] ?? null) == $value[$k]) {
                                $value[$k] = $item['name'] ?? ($item['label'] ?? $value[$k]);
                                break;
                            }
                        }
                    } elseif ($column[$k]['data_dict_id']) {
                        switch ($column[$k]['form_value']) {
                            case CrudFormEnum::FORM_CHECKBOX:
                            case CrudFormEnum::FORM_TAG:
                            case CrudFormEnum::FORM_CASCADER_RADIO:
                                $dataDict = app()->get(DictDataService::class)
                                    ->idByValues($column[$k]['data_dict_id']);

                                $data = [];
                                if ($column[$k]['form_value'] === CrudFormEnum::FORM_TAG) {
                                    foreach ($value[$k] as $itemId) {
                                        foreach ($dataDict as $item) {
                                            if ($itemId == $item['id']) {
                                                $data[] = $item['name'];
                                            }
                                        }
                                    }
                                } else {
                                    foreach ($dataDict as $item) {
                                        if (is_array($value[$k]) && in_array($item['value'], $value[$k])) {
                                            $data[] = $item['name'];
                                        }
                                    }
                                    // 级联单选只保留最后一个
                                    //                                    if (CrudFormEnum::FORM_CASCADER_RADIO === $column[$k]['form_value']) {
                                    //                                        $data = array_pop($data);
                                    //                                    }
                                }
                                $value[$k] = implode('/', $data);
                                break;
                            case CrudFormEnum::FORM_RADIO:
                                $dataDict = app()->get(DictDataService::class)
                                    ->idByValues($column[$k]['data_dict_id']);

                                $newValue = null;
                                foreach ($dataDict as $item) {
                                    if ($item['value'] == $value[$k]) {
                                        $newValue = $item['name'];
                                    }
                                }
                                $value[$k] = $newValue;
                                break;
                            case CrudFormEnum::FORM_CASCADER:
                                if ($value[$k]) {
                                    $dataDict = app()->get(DictDataService::class)
                                        ->idByValues($column[$k]['data_dict_id']);

                                    $data = [];
                                    foreach ($value[$k] as $v) {
                                        $vv = [];
                                        foreach ($dataDict as $item) {
                                            if (in_array($item['value'], $v)) {
                                                $vv[] = $item['name'];
                                            }
                                        }
                                        $data[] = implode('/', $vv);
                                    }

                                    $value[$k] = $data;
                                }
                                break;
                        }
                    } elseif ($column[$k]['form_value'] == CrudFormEnum::FORM_CASCADER_ADDRESS && is_array($value[$k])) {
                        $dataDict = app()->get(DictDataService::class)
                            ->idsByValues($value[$k]);

                        $data = [];
                        foreach ($value[$k] as $itemId) {
                            foreach ($dataDict as $item) {
                                if ($itemId == $item['id']) {
                                    $data[] = $item['name'];
                                }
                            }
                        }

                        $value[$k] = $data;
                    } elseif ($column[$k]['form_value'] == CrudFormEnum::FORM_TAG && !$column[$k]['data_dict_id']) {
                        $value[$k] = app()->get(LabelService::class)->idByValue($value[$k]);
                    } else {
                        switch ($k) {
                            case 'user_id':
                                $value[$k] = $value['create_user']['name'] ?? $value[$k];
                                break;
                            case 'update_user_id':
                                $value[$k] = $value['update_user']['name'] ?? $value[$k];
                                break;
                            case 'frame_id':
                                $value[$k] = $value['owner_frame']['name'] ?? $value[$k];
                                break;
                            case 'owner_user_id':
                                $value[$k] = $value['owner_user']['name'] ?? $value[$k];
                                break;
                        }
                    }
                }
            }
            $list[$index] = $value;
        }

        return $list;
    }

    /**
     * 拆分别名.
     * @return array|string[]
     */
    public function splitAliasName(string $field): array
    {
        if (substr_count($field, '_') > 1) {
            if (preg_match('/^([^_]*)_([^_]*.*)$/', $field, $matches)) {
                return [$matches[1], $matches[2]];
            }
        }
        return explode('_', $field);
    }

    /**
     * 获取自定义的数据解析为对应的value.
     * @param mixed $value
     * @return array
     */
    public function getCustomizeChildrenItem(array $customizeChildrenItems, $value, string $formValue)
    {
        $itemValue = [];
        foreach ($customizeChildrenItems as $item) {
            if (in_array($item['value'], $value)) {
                $itemValue[] = $item['name'];
            }
            if (!empty($item['children'])) {
                $itemValue = array_merge($itemValue, $this->getCustomizeChildrenItem($item['children'], $value, $formValue));
            }
        }

        return $itemValue;
    }

    /**
     * 获取自定义的数据解析为对应的value.
     * @param mixed $value
     * @return array
     */
    public function getCustomizeItem(array $customizeItems, $value, string $formValue)
    {
        $itemValue = [];
        if ($formValue === CrudFormEnum::FORM_CASCADER) {
            foreach ($value as $v) {
                $vv = [];
                foreach ($customizeItems as $item) {
                    if (in_array($item['value'], (array)$v)) {
                        $vv[] = $item['name'];
                    }
                    if (!empty($item['children'])) {
                        $vv = array_merge($vv, $this->getCustomizeChildrenItem($item['children'], $v, $formValue));
                    }
                }
                $itemValue[] = implode('/', $vv);
            }
            return $itemValue;
        }
        foreach ($customizeItems as $item) {
            switch ($formValue) {
                case CrudFormEnum::FORM_CHECKBOX:
                    if (is_array($value) && in_array($item['value'], $value)) {
                        $itemValue[] = ['name' => $item['name'], 'color' => $item['color'] ?? ''];
                    }
                    break;
                case CrudFormEnum::FORM_CASCADER_RADIO:
                    if (is_array($value) && in_array($item['value'], $value)) {
                        $itemValue[] = $item['name'];
                    }
                    break;
                case CrudFormEnum::FORM_RADIO:
                    if ($item['value'] == $value) {
                        $itemValue = ['name' => $item['name'], 'color' => $item['color'] ?? ''];
                    }
                    break;
            }
        }

        return $itemValue;
    }

    /**
     * 处理字段数据类型.
     * @return array
     * @throws BindingResolutionException
     */
    public function getDataAttrValue(array $value, array $column, string $indexField = '', string $crudTableNameEn = '')
    {
        $dataDictService = app()->get(DictDataService::class);
        foreach ($value as $k => $v) {
            if (isset($column[$k])) {
                $value[$k] = $this->getValueAttribute($column[$k]['form_value'], $value[$k], $crudTableNameEn);
                if ($k === $indexField && ($value[$k] == null || $value[$k] == '')) {
                    $value[$k] = '--';
                    continue;
                }
                if ($value[$k] == null) {
                    continue;
                }
                if ($column[$k]['data_type'] == 1 && in_array($column[$k]['form_value'], [
                        CrudFormEnum::FORM_CASCADER,
                        CrudFormEnum::FORM_CHECKBOX,
                        CrudFormEnum::FORM_CASCADER_RADIO,
                        CrudFormEnum::FORM_RADIO,
                    ])) {
                    $value[$k] = $this->getCustomizeItem($column[$k]['customize_items'], $value[$k], $column[$k]['form_value']);
                } elseif ($column[$k]['data_dict_id']) {
                    switch ($column[$k]['form_value']) {
                        case CrudFormEnum::FORM_TAG:
                        case CrudFormEnum::FORM_CASCADER_RADIO:
                            $dataDict = $dataDictService->idByCacheValues($column[$k]['data_dict_id']);

                            $data = [];
                            if ($column[$k]['form_value'] === CrudFormEnum::FORM_TAG) {
                                foreach ($value[$k] as $itemId) {
                                    foreach ($dataDict as $item) {
                                        if ($itemId == $item['id']) {
                                            $data[] = $item['name'];
                                        }
                                    }
                                }
                            } else {
                                foreach ($dataDict as $item) {
                                    if (is_array($value[$k]) && in_array($item['value'], $value[$k])) {
                                        $data[] = $item['name'];
                                    }
                                }
                            }
                            $value[$k] = $data;
                            break;
                        case CrudFormEnum::FORM_RADIO:
                            $dataDict = $dataDictService->idByCacheValues($column[$k]['data_dict_id']);

                            $newValue = null;
                            foreach ($dataDict as $item) {
                                if ($item['value'] == $value[$k]) {
                                    $newValue['name'] = $item['name'];
                                    $newValue['color'] = $item['color'];
                                }
                            }
                            $value[$k] = $newValue;
                            break;
                        case CrudFormEnum::FORM_CHECKBOX:
                            $dataDict = $dataDictService->idByCacheValues($column[$k]['data_dict_id']);
                            $data = [];
                            if ($column[$k]['form_value'] === CrudFormEnum::FORM_TAG) {
                                foreach ($value[$k] as $itemId) {
                                    foreach ($dataDict as $item) {
                                        if ($itemId == $item['id']) {
                                            $data[] = $item['name'];
                                        }
                                    }
                                }
                            } else {
                                foreach ($dataDict as $item) {
                                    if (is_array($value[$k]) && in_array($item['value'], $value[$k])) {
                                        $data[] = [
                                            'name'  => $item['name'],
                                            'color' => $item['color'],
                                        ];
                                    }
                                }
                            }
                            $value[$k] = $data;
                            break;
                        case CrudFormEnum::FORM_CASCADER:
                            if ($value[$k]) {
                                $dataDict = $dataDictService->idByCacheValues($column[$k]['data_dict_id']);

                                $data = [];
                                foreach ($value[$k] as $v) {
                                    $vv = [];
                                    foreach ($dataDict as $item) {
                                        if (in_array($item['value'], (array)$v)) {
                                            $vv[] = $item['name'];
                                        }
                                    }
                                    $data[] = implode('/', $vv);
                                }

                                $value[$k] = $data;
                            }
                            break;
                    }
                } elseif ($column[$k]['form_value'] == CrudFormEnum::FORM_CASCADER_ADDRESS && is_array($value[$k])) {
                    $dataDict = $dataDictService->idsByCacheValues($value[$k]);

                    $data = [];
                    foreach ($value[$k] as $itemId) {
                        foreach ($dataDict as $item) {
                            if ($itemId == $item['id']) {
                                $data[] = $item['name'];
                            }
                        }
                    }

                    $value[$k] = $data;
                } elseif ($column[$k]['form_value'] == CrudFormEnum::FORM_TAG && !$column[$k]['data_dict_id']) {
                    $value[$k] = app()->get(LabelService::class)->idByCacheValue($value[$k]);
                } else {
                    switch ($k) {
                        case 'user_id':
                            $value[$k] = [
                                'name' => $value['create_user']['name'] ?? $value[$k],
                                'type' => ($value['create_user']['info']['type'] ?? 0) === 4 ? '离职' : '',
                            ];
                            break;
                        case 'update_user_id':
                            $value[$k] = [
                                'name' => $value['update_user']['name'] ?? $value[$k],
                                'type' => ($value['update_user']['info']['type'] ?? 0) === 4 ? '离职' : '',
                            ];
                            break;
                        case 'frame_id':
                            $value[$k] = $value['owner_frame']['name'] ?? $value[$k];
                            break;
                        case 'owner_user_id':
                            $value[$k] = [
                                'name' => $value['owner_user']['name'] ?? $value[$k],
                                'type' => ($value['owner_user']['info']['type'] ?? 0) === 4 ? '离职' : '',
                            ];
                            break;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * 获取系统默认的搜索条件.
     * @return array
     */
    protected function getSystemTableWhere(string $tableName)
    {
        $where = [];
        switch ($tableName) {
            case SystemCrudService::SYSTEM_TABLE_TABLE_USER:
            case SystemCrudService::SYSTEM_TABLE_TABLE_FRAME:
                $where[] = [
                    'field_name' => 'status',
                    'form_value' => CrudFormEnum::FORM_INPUT_NUMBER,
                    'value'      => 1,
                ];
                break;
        }

        return $where;
    }

    /**
     * 关联字段展示查询和数据转换.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    protected function otherWithList(array $otherWith, array $list, array $formField = [], bool $uni = false, array $shareIds = [], string $indexField = '', string $crudTableNameEn = '')
    {
        // 处理一对一关联字段展示
        foreach ($otherWith as $newWith) {
            $idName = $newWith['field_name_en'];
            $ids = array_column($list, $idName);
            $mainName = $newWith['association_field_main_name_en'];
            if (!$mainName) {
                continue;
            }

            $select = ['id', $mainName];
            if ($newWith['association_table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_USER) {
                array_push($select, ['avatar']);
            }
            $withList = DB::table($newWith['association_table_name_en'])->whereIn('id', $ids)
                ->select(['id', $mainName])->get()->toArray();

            $withData = [];
            foreach ($withList as $item) {
                $item = (array)$item;
                if ($uni) {
                    $withData[$item['id']] = $newWith['association_table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_USER ? $item : $item[$mainName];
                } else {
                    $withData[$item['id']] = $item[$mainName];
                }
            }

            foreach ($list as $index => $value) {
                $value[$idName] = $withData[$value[$idName]] ?? '';
                $list[$index] = $value;
            }
        }

        $column = [];
        foreach ($formField as $item) {
            $column[$item['field_name_en']] = [
                'form_value'      => $item['form_value'],
                'data_dict_id'    => $item['data_dict_id'],
                'data_type'       => $item['data_type'],
                'customize_items' => $item['customize_items'],
            ];
        }

        foreach ($list as $index => $value) {
            // 处理字段数据类型
            $value = $this->getDataAttrValue($value, $column, $indexField, $crudTableNameEn);

            if ($uni && isset($value['created_at'])) {
                $value['created_at'] = date('Y/m/d', datetime_timestamp($value['created_at']));
            }

            if (isset($value['id'])) {
                $value['is_share'] = in_array($value['id'], $shareIds);
            } else {
                $value['is_share'] = false;
            }

            $list[$index] = $value;
        }

        return $list;
    }
}
