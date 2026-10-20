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

use App\Constants\Crud\CrudAggregateEnum;
use App\Constants\Crud\CrudDashboardEnum;
use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudOperatorEnum;
use App\Http\Contract\System\MenusInterface;
use App\Http\Dao\Crud\SystemCrudDashboardDao;
use App\Http\Model\Crud\SystemCrudField;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\System\MenusService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class SystemCrudDashboardService.
 */
class SystemCrudDashboardService extends BaseService
{
    /**
     * SystemCrudDashboardService constructor.
     */
    public function __construct(SystemCrudDashboardDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 看板列表.
     * @param string $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        $field = ['id', 'name', 'user_id', 'update_user_id', 'status', 'created_at', 'updated_at'];
        return parent::getList($where, $field, $sort, array_merge($with, ['user', 'updateUser', 'menuNamePathData']));
    }

    /**
     * 保存看板
     * @return object
     * @throws BindingResolutionException
     */
    public function saveDashboard(array $data): mixed
    {
        $data['user_id'] = uuid_to_uid($this->uuId(false));
        $data['status'] = 1;
        if (!is_array($data['path'])) {
            $data['path'] = [];
        }
        return $this->transaction(function () use ($data) {
            $menuData = [
                'menu_name'   => $data['name'],
                'icon'        => $data['icon'],
                'uni_img'     => $data['uni_img'],
                'menu_type'   => 2,
                'sort'        => 0,
                'status'      => 0,
                'type'        => 'M',
                'unique_auth' => uniqid('dashboard'),
            ];

            $path = $menuData['paths'] = array_filter($data['path']);
            unset($data['path']);
            $menuData['uniqued'] = md5($menuData['unique_auth']);
            unset($data['icon'], $data['uni_img']);

            $res = $this->dao->create($data);

            $menuData['menu_path'] = '/crud/module/' . $res->id . '/dashboard';
            if ($path) {
                $menuData['pid'] = end($path);
                $menuData['level'] = count($path);
                if ($menuData['pid']) {
                    $menuData['parent_uniqued'] = app()->get(MenusInterface::class)->getModel()->where('id', $menuData['pid'])->value('unique_auth');
                }
            }
            if ($menuData['paths']) {
                $menuData['paths'] = implode('/', $menuData['paths']);
            }

            $menuData['crud_dashboard_id'] = $res->id;
            /** @var Model $menusModel */
            $menusModel = app()->get(MenusInterface::class)->getModel();
            if ($menusModel->where('crud_dashboard_id', $res->id)->value('id')) {
                unset($menuData['uniqued'], $menuData['unique_auth']);
                $menusModel->update(['crud_dashboard_id' => $res->id], $menuData);
            } else {
                $menuData['crud_dashboard_id'] = $res->id;
                $menusModel->create($menuData);
            }

            return $res;
        });
    }

    /**
     * 修改看板
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateDashboard(int $id, array $data): int
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $data['update_user_id'] = uuid_to_uid($this->uuId(false));


        $menuData = [
            'menu_name'   => $data['name'],
            'icon'        => $data['icon'],
            'uni_img'     => $data['uni_img'],
            'menu_type'   => 2,
            'sort'        => 0,
            'status'      => 0,
            'type'        => 'M',
            'unique_auth' => uniqid('dashboard'),
        ];

        if (!is_array($data['path'])) {
            $data['path'] = [];
        }
        $path = $menuData['paths'] = array_filter($data['path']);
        unset($data['path']);

        $menuData['uniqued'] = md5($menuData['unique_auth']);
        unset($data['icon'], $data['uni_img']);

        $res = $this->dao->update($id, $data);

        $menuData['menu_path'] = '/crud/module/' . $res->id . '/dashboard';
        if ($path) {
            $menuData['pid'] = end($path);
            $menuData['level'] = count($path);
            if ($menuData['pid']) {
                $menuData['parent_uniqued'] = app()->get(MenusInterface::class)->getModel()->where('id', $menuData['pid'])->value('unique_auth');
            }
        }

        /** @var Model $menusModel */
        $menusModel = app()->get(MenusInterface::class)->getModel();
        if ($menusModel->where('crud_dashboard_id', $res->id)->value('id')) {
            unset($menuData['uniqued'], $menuData['unique_auth']);
            $menusModel->update(['crud_dashboard_id' => $res->id], $menuData);
        } else {
            $menuData['crud_dashboard_id'] = $res->id;
            $menusModel->create($menuData);
        }
    }

    /**
     * 删除看板
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteDashboard(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        return $this->transaction(function () use ($id) {
            $menuService = app()->get(MenusService::class);
            $menuWhere = ['menu_type' => $id];
            if ($menuService->exists($menuWhere)) {
                $menuService->delete($menuWhere);
            }
            return $this->dao->delete($id);
        });
    }

    /**
     * 获取配置.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getConfigure(int $id): array
    {
        $info = toArray($this->dao->get($id, ['id', 'name', 'configure']));
        if (!$info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        return ['id' => $info['id'], 'name' => $info['name'], 'chartData' => $info['configure']];
    }

    /**
     * 设置配置.
     * @param array $configure
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setConfigure(int $id, mixed $configure): bool
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        $info->configure = $configure;
        return $info->save();
    }

    /**
     * 处理指标字段.
     */
    public function getIndicatorSelect(string $operator, string $fieldNameEn, string $aliasName): string
    {
        return match ($operator) {
            CrudAggregateEnum::AGGREGATE_COUNT => 'COUNT(' . $fieldNameEn . ') AS ' . $aliasName,
            CrudAggregateEnum::AGGREGATE_SUM => 'COALESCE(SUM(' . $fieldNameEn . '),0 ) AS ' . $aliasName,
            CrudAggregateEnum::AGGREGATE_UNIQID_COUNT => 'COUNT(DISTINCT ' . $fieldNameEn . ') AS ' . $aliasName,
            CrudAggregateEnum::AGGREGATE_AVG => 'COALESCE(AVG(' . $fieldNameEn . '),0 ) AS ' . $aliasName,
            CrudAggregateEnum::AGGREGATE_MAX => 'COALESCE(MAX(' . $fieldNameEn . '),0 ) AS ' . $aliasName,
            CrudAggregateEnum::AGGREGATE_MIN => 'COALESCE(MIN(' . $fieldNameEn . '),0 ) AS ' . $aliasName,
            default => $fieldNameEn,
        };
    }

    /**
     * 获取到所有表单的搜索表单.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getChartDataTemplateInfo(int $chartId, string $uniqued, string $type, string $chartData = '')
    {
        if ($chartData) {
            $configure = json_decode($chartData, true);
        } else {
            $info = $this->getConfigure($chartId);
            $configure = json_decode($info['chartData'], true);
        }
        $chartCon = $this->getChartDataTemplate($configure, $type);
        $searchList = [];
        foreach ($chartCon as $value) {
            if (in_array($uniqued, $value['options']['entityIds'])) {
                $searchList = $value['options']['searchList'];
            }
        }
        return $searchList;
    }

    /**
     * 获取图表统计的搜索条件.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getChartRequestViewSearch(array $search, int $chartId, string $uniqued, string $type, string $chartData = '')
    {
        $searchList = $this->getChartDataTemplateInfo($chartId, $uniqued, $type, $chartData);
        $viewSearch = [];
        foreach ($searchList as $value) {
            $val = $search[str_replace('.', '@', $value['field_name_en'])] ?? null;
            if ($val !== '' && $val !== null) {
                $viewSearch[] = [
                    'field_name' => $value['field_name_en'],
                    'form_value' => $value['form_value'],
                    'operator'   => in_array($value['form_value'], [CrudFormEnum::FORM_DATE_PICKER, CrudFormEnum::FORM_DATE_TIME_PICKER])
                        && (is_array($val) || (is_string($val) && str_contains($val, '-')))
                        ? CrudOperatorEnum::OPERATOR_BT
                        : CrudOperatorEnum::OPERATOR_IN,
                    'value'      => $val,
                ];
            }
        }

        return $viewSearch;
    }

    /**
     * 图表数据模板
     * @return array
     */
    public function getChartDataTemplate(array $configure, string $type)
    {
        $searchConfigure = [];
        foreach ($configure['widgetList'][0]['widgetList'] ?? [] as $value) {
            if ($value['type'] === 'tab') {
                foreach ($value['options']['tabList'] as $tabValue) {
                    foreach ($tabValue['designer']['widgetList'][0]['widgetList'] ?? [] as $widget) {
                        if ($type === $widget['type']) {
                            $searchConfigure[] = $widget;
                        }
                    }
                }
            } else {
                if ($type === $value['type']) {
                    $searchConfigure[] = $value;
                }
            }
        }

        return $searchConfigure;
    }

    /**
     * 图表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function chartQuery(array $queryWhere): array
    {
        if (count($queryWhere['dimension_list']) > 1) {
            throw $this->exception('维度设置过多');
        }

        $noPrivileges = (bool)$queryWhere['no_privileges'];
        if ($noPrivileges) {
            unset($queryWhere['user_id']);
        }

        if (empty($queryWhere['table_name_en']) && !empty($queryWhere['crud_id'])) {
            $queryWhere['table_name_en'] = app()->get(SystemCrudService::class)->getCrudTableNameEnCache($queryWhere['crud_id']);
        }

        $viewSearch = [];
        if (!empty($queryWhere['chart_id']) || !empty($queryWhere['chart_data'])) {
            $viewSearch = $this->getChartRequestViewSearch(search: $queryWhere['search'], chartId: (int)$queryWhere['chart_id'], uniqued: $queryWhere['uniqued'], type: 'search', chartData: $queryWhere['chart_data']);
        }

        return match ($queryWhere['type']) {
            // 进度条 统计数值
            CrudDashboardEnum::PROGRESS_BAR,
            CrudDashboardEnum::STATISTIC_NUMERIC => $this->aggregateQuery($queryWhere, $viewSearch),
            // 柱状图  条形图  折线图 雷达图
            CrudDashboardEnum::BAR_CHART,
            CrudDashboardEnum::COLUMN_BAR,
            CrudDashboardEnum::LINE_CHART,
            CrudDashboardEnum::RADAR_CHART => $this->barChartQuery($queryWhere, $viewSearch),
            //  漏斗图 饼图
            CrudDashboardEnum::FUNNEL_PLOT,
            CrudDashboardEnum::PIE_CHART => $this->funnelPlotQuery($queryWhere, $viewSearch),
            default => [],
        };
    }

    /**
     * 数值查询.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function aggregateQuery(array $queryWhere, array $viewSearch = []): array
    {
        $indicator = $queryWhere['indicator_list'][0] ?? [];
        $fieldNameEn = $indicator['field_name_en'] ?? '';
        if (!$fieldNameEn) {
            throw $this->exception('没有查询到字段信息');
        }

        $sort = $indicator['sort'] ?? '';
        $fieldName = $indicator['field_name'] ?? '';
        $operator = $indicator['operator'] ?? 'count';
        $crudModuleService = app()->get(CrudModuleService::class);
        $systemCrudService = app()->get(SystemCrudService::class);

        $crud = $systemCrudService->getCrudInfoCache($queryWhere['table_name_en']);
        if (!$this->allowAuth($crudModuleService->getFieldAssociation($crud->field->toArray(), $crud->table_name_en))) {
            unset($queryWhere['user_id']);
        }

        if (!str_contains($fieldNameEn, '.')) {
            $fieldNameEn = $crud->table_name_en . '.' . $fieldNameEn;
        }

        $viewSearch = array_merge($viewSearch, $this->getViewSearch($queryWhere, $crud->id, $crud->table_name_en));
        $model = $crudModuleService->model(crudId: $crud->id)
            ->setJoin($systemCrudService->getJoinCrudData($crud->id, 1))
            ->viewSearch($viewSearch, null, $queryWhere['additional_search_boolean'] == 0 ? 'or' : 'and');

        if (isset($queryWhere['user_id'])) {
            $model->whereIn($queryWhere['table_name_en'] . '.user_id', $queryWhere['user_id']);
        }

        if ($sort) {
            $model->orderBy($fieldNameEn, $sort);
        }

        $value = match ($operator) {
            CrudAggregateEnum::AGGREGATE_SUM => $model->sum($fieldNameEn),
            CrudAggregateEnum::AGGREGATE_UNIQID_COUNT => $model->distinct()->count($fieldNameEn),
            CrudAggregateEnum::AGGREGATE_AVG => $model->avg($fieldNameEn),
            CrudAggregateEnum::AGGREGATE_MAX => $model->max($fieldNameEn),
            CrudAggregateEnum::AGGREGATE_MIN => $model->min($fieldNameEn),
            default => $model->count($fieldNameEn),
        };
        return ['field_name' => $fieldName, 'value' => $value];
    }

    /**
     * 获取分组字段.
     * @return string
     */
    public function getGroupByClause(?string $startDate, ?string $endDate, string $fieldNameEn)
    {
        if (empty($startDate) || empty($endDate)) {
            return "DATE_FORMAT({$fieldNameEn}, '%Y-%m-%d') as crud_group_time";
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $diffInDays = $start->diffInDays($end);

        $query = '';
        if ($diffInDays >= 730) {
            // 大于24个月，按年分组
            $query = "DATE_FORMAT({$fieldNameEn}, '%Y') as crud_group_time";
        } elseif ($diffInDays >= 90) {
            // 大于90天，按月分组
            $query = "DATE_FORMAT({$fieldNameEn}, '%Y-%m') as crud_group_time";
        } elseif ($diffInDays > 31) {
            // 大于31天，按周分组
            $query = "CONCAT('Week ', FLOOR(DATEDIFF({$fieldNameEn}, '{$start}')/7) + 1) as crud_group_time";
        } else {
            // 小于等于31天，按天分组
            $query = "DATE_FORMAT({$fieldNameEn}, '%Y-%m-%d') as crud_group_time";
        }

        return $query;
    }

    /**
     * 获取分组字段.
     * @return string
     */
    public function getGroupByClauseByType(string $dateColumn, string $groupingType)
    {
        $query = '';
        if ($groupingType === 'year') {
            $query = "DATE_FORMAT({$dateColumn}, '%Y') as crud_group_time";
        } elseif ($groupingType === 'month') {
            $query = "DATE_FORMAT({$dateColumn}, '%Y-%m') as crud_group_time";
        } elseif ($groupingType === 'week') {
            $query = "CONCAT('Week ', WEEK({$dateColumn})) as crud_group_time";
        } elseif ($groupingType === 'day') {
            $query = "DATE_FORMAT({$dateColumn}, '%Y-%m-%d') as crud_group_time";
        }

        return $query;
    }

    /**
     * 柱状图查询.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function barChartQuery(array $queryWhere, array $viewSearch = []): array
    {
        $crudModuleService = app()->get(CrudModuleService::class);
        $systemCrudService = app()->get(SystemCrudService::class);
        $systemFieldCrudService = app()->get(SystemCrudFieldService::class);
        $dictDataService = app()->get(DictDataService::class);

        $series = []; // 结果集
        $xAxis = []; // x 轴
        $yAxis = []; // y 轴
        $sort = $group = $dimAlias = '';
        $select = $yAxisFields = $withField = $xAxisTmp = $other = [];
        $crud = $systemCrudService->getCrudInfoCache($queryWhere['table_name_en']);
        $prefix = Config::get('database.connections.mysql.prefix');
        $fields = $crudModuleService->getFieldAssociation($crud->field->toArray(), $crud->table_name_en);
        if (!$this->allowAuth($fields)) {
            unset($queryWhere['user_id']);
        }

        // 级联数据
        $cascadeRadio = ['data_dict_id' => 0, 'value' => 0, 'field' => '', 'field_as' => '', 'discard' => []];

        // 自动分组
        $dataGroupType = null;
        $groupFieldName = $groupAlias = '';

        foreach ($queryWhere['dimension_list'] as $item) {
            $crudId = $crud->id;
            $tableNameEn = $crud->table_name_en;
            $fieldNameEn = $item['field_name_en'];
            $dimAlias = $crud->table_name_en . '_' . $item['field_name_en'];
            $fullName = '`' . $prefix . $crud->table_name_en . '`.`' . $item['field_name_en'] . '`';
            if (str_contains($item['field_name_en'], '.')) {
                $tmpName = explode('.', $item['field_name_en']);
                $tableNameEn = $tmpName[0];
                if ($tmpName[0] != $crud->table_name_en) {
                    $fieldNameEn = $tmpName[1];
                    $crudTmp = $systemCrudService->getCrudInfoCache($tmpName[0])->toArray();
                    $crudId = $crudTmp['id'];
                    foreach ($crudTmp['field'] as $crudField) {
                        if ($crudField['field_name_en'] == $tmpName[1]) {
                            $crudField['table_name_en'] = $crudTmp['table_name_en'];
                            $fields[] = $crudField;
                        }
                    }
                }
                $dimAlias = $tmpName[0] . '_' . $tmpName[1];
                $fullName = '`' . $prefix . $tmpName[0] . '`.`' . $tmpName[1] . '`';
            }

            $sort = $this->getQuerySort($item, $fullName);
            $association = $this->getAssociationCrudField($crudId, $fieldNameEn);
            if (in_array($queryWhere['type'], [CrudDashboardEnum::BAR_CHART, CrudDashboardEnum::COLUMN_BAR])
                && $association
                && $association['data_dict_id'] > 0
                && $association['form_value'] == CrudFormEnum::FORM_CASCADER_RADIO) {
                $other['dim_value'] = [];
                $cascadeRadio['data_dict_id'] = $association['data_dict_id'];
                $cascadeRadio['value'] = intval($item['value'] ?? 0);
                $cascadeRadio['field'] = $tableNameEn . '.' . $fieldNameEn;
                $cascadeRadio['field_as'] = $dimAlias;
            }

            if (!isset($withField[$crudId]) && isset($association['association'])) {
                $withField[$crudId] = $this->getWithField($association, $item, $crud->table_name_en);
            }

            // 级联数据处理
            if ($cascadeRadio['data_dict_id'] > 0) {
                $group = $dimAlias;
                $position = $dictDataService->value(['type_id' => $cascadeRadio['data_dict_id'], 'status' => 1, 'value' => $cascadeRadio['value']], 'level') + 2;
                $select[] = "SUBSTRING_INDEX(`eb_{$tableNameEn}` . `{$fieldNameEn}` ,'/', {$position}) AS " . $dimAlias;
            } else {
                // 处理字段是否是时间格式，需要进行自动分组
                if (isset($crudTmp, $tmpName)) {
                    $fieldInfo = $systemFieldCrudService->getCrudTableFieldAllCache($crudTmp['id'], $tmpName[1]);
                    $groupFieldName = $tmpName[1];
                    $groupAlias = $crudTmp['table_name_en'];
                } else {
                    $fieldInfo = $systemFieldCrudService->getCrudTableFieldAllCache($crud->id, $fieldNameEn);
                    $groupFieldName = $fieldNameEn;
                    $groupAlias = $crud->table_name_en;
                }
                if (in_array($fieldInfo['field_type'], ['timestamp', 'datetime', 'date'])) {
                    $dataGroupType = $item['group_type'] ?? 'auto';
                }
                unset($fieldInfo);
                $group = $fullName;
                $select[] = $fullName . ' AS ' . $dimAlias;
            }
        }

        foreach ($queryWhere['indicator_list'] as $key => $item) {
            if (!str_contains($item['field_name_en'], '.')) {
                $aliasName = $crud->table_name_en . '_' . $item['field_name_en'];
                $item['field_name_en'] = '`' . $prefix . $crud->table_name_en . '`.`' . $item['field_name_en'] . '`';
            } else {
                $tmpName = explode('.', $item['field_name_en']);
                $aliasName = $tmpName[0] . '_' . $tmpName[1];
                $item['field_name_en'] = '`' . $prefix . $tmpName[0] . '`.`' . $tmpName[1] . '`';
            }
            $yAxisFields[] = $aliasName;
            $sort = $this->getQuerySort($item, '`' . $aliasName . '`');
            $select[] = $this->getIndicatorSelect($item['operator'], $item['field_name_en'], $aliasName);
            $yAxis[] = $series[$key]['name'] = $item['field_name'] ?? '';
        }

        // 附加条件
        $viewSearch = array_merge($viewSearch, $this->getViewSearch($queryWhere, $crud->id, $crud->table_name_en));
        $model = $crudModuleService->model(crudId: $crud->id)
            ->setJoin($systemCrudService->getJoinCrudData($crud->id, 1))
            ->viewSearch($viewSearch, null, $queryWhere['additional_search_boolean'] == 0 ? 'or' : 'and');

        // 处理纬度排序
        if (isset($queryWhere['dimension_list'][0], $queryWhere['dimension_list'][0]['sort']) && $queryWhere['dimension_list'][0]['sort']) {
            $dimension = $queryWhere['dimension_list'][0];
            if (str_contains($dimension['field_name_en'], '.')) {
                $tmpName = explode('.', $dimension['field_name_en']);
                $fullName = '`' . $prefix . $tmpName[0] . '`.`' . $tmpName[1] . '`';
            } else {
                $fullName = '`' . $prefix . $crud->table_name_en . '`.`' . $dimension['field_name_en'] . '`';
            }
            $strStore = $dimension['sort'] === 'asc' ? 'ASC' : 'DESC';
            if ($sort) {
                $sort .= ',' . $fullName . ' ' . $strStore;
            } else {
                $sort = $fullName . ' ' . $strStore;
            }
        }

        if (isset($queryWhere['user_id'])) {
            $model->whereIn($queryWhere['table_name_en'] . '.user_id', $queryWhere['user_id']);
        }

        if ($cascadeRadio['value'] > 0) {
            $model->where($cascadeRadio['field'], 'like', '%/' . $cascadeRadio['value'] . '/%');
        }

        // 根据日期进行自动分组
        if ($queryWhere['type'] !== CrudDashboardEnum::RADAR_CHART && $groupFieldName && $dataGroupType) {
            if ($dataGroupType === 'auto') {
                $startTime = $model->min($groupAlias . '.' . $groupFieldName);
                $endTime = $model->max($groupAlias . '.' . $groupFieldName);
                $select[] = $this->getGroupByClause($startTime, $endTime, '`' . $prefix . $groupAlias . '`.`' . $groupFieldName . '`');
                if ($sort) {
                    $sort .= ',' . $prefix . $groupAlias . '.' . $groupFieldName . ' ASC';
                } else {
                    $sort = $prefix . $groupAlias . '.' . $groupFieldName . ' ASC';
                }
            } else {
                $select[] = $this->getGroupByClauseByType('`' . $prefix . $groupAlias . '`.`' . $groupFieldName . '`', $dataGroupType);
            }
            $group = 'crud_group_time';
        }

        if ($select) {
            $model->selectRaw(implode(',', $select));
        }
        if ($sort) {
            $model->orderByRaw($sort);
        }

        if ($group) {
            $model->groupByRaw($group);
        }

        $result = $model->get()->toArray();

        foreach ($result as $key => $item) {
            if ($cascadeRadio['data_dict_id'] > 0) {
                $val = $this->getDimValue($item, $cascadeRadio);
                if (!$val) {
                    $cascadeRadio['discard'][$key] = 1;
                    continue;
                }
                $other['dim_value'][] = $val;
                $result[$key][$cascadeRadio['field_as']] = $val;
                $xAxisTmp[] = [$dimAlias => $val];
            } else {
                $val = $item[$dimAlias] ?? '';
                if (!$val) {
                    continue;
                }
                $xAxisTmp[] = [$dimAlias => $val];
            }
        }

        $xAxisTmp && $xAxisTmp = $crudModuleService->otherWithAliasList(array_map(function ($i) {
            return $i;
        }, $withField), $xAxisTmp, $fields);

        $isRadar = $queryWhere['type'] == CrudDashboardEnum::RADAR_CHART;

        // 组合数据
        foreach ($result as $key => $item) {
            $isDiscard = isset($cascadeRadio['discard'][$key]);
            if (!$isDiscard) {
                $value = $xAxisTmp[$key][$dimAlias] ?? '未知';
                if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $value)) {
                    $value = date('Y-m-d', datetime_timestamp($value));
                }
                $xAxis[] = $value;
            }
            foreach ($yAxisFields as $yaKey => $yAxisFieldEn) {
                // 级联筛选过滤
                if ($isDiscard) {
                    $series[$yaKey]['data'] = [];
                    continue;
                }

                if (isset($item[$yAxisFieldEn])) {
                    $series[$yaKey]['data'][] = $isRadar ? (string)$item[$yAxisFieldEn] : $item[$yAxisFieldEn];
                }
            }
        }

        return ['series' => $series, 'xAxis' => $xAxis, 'yAxis' => $yAxis, 'other' => $other];
    }

    /**
     * 漏斗图.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function funnelPlotQuery(array $queryWhere, array $viewSearch = []): array
    {
        if ($queryWhere['type'] == CrudDashboardEnum::PIE_CHART && count($queryWhere['indicator_list']) > 1) {
            throw $this->exception('指标设置过多');
        }

        $crudModuleService = app()->get(CrudModuleService::class);
        $systemCrudService = app()->get(SystemCrudService::class);
        $dictDataService = app()->get(DictDataService::class);

        $sort = $group = $dimAlias = '';
        $crud = $systemCrudService->getCrudInfoCache($queryWhere['table_name_en']);
        $data = $select = $aliasNames = $fieldNames = $withField = $xAxisTmp = $dimValue = [];
        $prefix = Config::get('database.connections.mysql.prefix');
        $fields = $crudModuleService->getFieldAssociation($crud->field->toArray(), $crud->table_name_en);
        if (!$this->allowAuth($fields)) {
            unset($queryWhere['user_id']);
        }

        // 级联数据
        $cascadeRadio = ['data_dict_id' => 0, 'value' => 0, 'field' => '', 'field_as' => '', 'discard' => []];

        foreach ($queryWhere['dimension_list'] as $item) {
            $crudId = $crud->id;
            $tableNameEn = $crud->table_name_en;
            $fieldNameEn = $item['field_name_en'];
            $dimAlias = $crud->table_name_en . '_' . $item['field_name_en'];
            $fullName = '`' . $prefix . $crud->table_name_en . '`.`' . $item['field_name_en'] . '`';
            if (str_contains($item['field_name_en'], '.')) {
                $tmpName = explode('.', $item['field_name_en']);
                $tableNameEn = $tmpName[0];
                if ($tmpName[0] != $crud->table_name_en) {
                    $fieldNameEn = $tmpName[1];
                    $crudTmp = $systemCrudService->getCrudInfoCache($tmpName[0])->toArray();
                    $crudId = $crudTmp['id'];
                    foreach ($crudTmp['field'] as $crudField) {
                        if ($crudField['field_name_en'] == $tmpName[1]) {
                            $crudField['table_name_en'] = $crudTmp['table_name_en'];
                            $fields[] = $crudField;
                        }
                    }
                }
                $dimAlias = $tmpName[0] . '_' . $tmpName[1];
                $fullName = '`' . $prefix . $tmpName[0] . '`.`' . $tmpName[1] . '`';
            }

            $sort = $this->getQuerySort($item, $fullName);
            $association = $this->getAssociationCrudField($crudId, $fieldNameEn);
            if ($queryWhere['type'] == CrudDashboardEnum::PIE_CHART
                && $association['data_dict_id'] > 0
                && $association['form_value'] == CrudFormEnum::FORM_CASCADER_RADIO) {
                $cascadeRadio['data_dict_id'] = $association['data_dict_id'];
                $cascadeRadio['value'] = intval($item['value'] ?? 0);
                $cascadeRadio['field'] = $tableNameEn . '.' . $fieldNameEn;
                $cascadeRadio['field_as'] = $tableNameEn . '_' . $fieldNameEn;
            }

            if (!isset($withField[$crudId]) && isset($association['association'])) {
                $withField[$crudId] = $this->getWithField($association, $item, $crud->table_name_en);
            }

            // 级联数据处理
            if ($cascadeRadio['data_dict_id'] > 0) {
                $group = $dimAlias;
                $position = $dictDataService->value(['type_id' => $cascadeRadio['data_dict_id'], 'status' => 1, 'value' => $cascadeRadio['value']], 'level') + 2;
                $select[] = "SUBSTRING_INDEX(`eb_{$tableNameEn}` . `{$fieldNameEn}` ,'/', {$position}) AS " . $dimAlias;
            } else {
                $group = $fullName;
                $select[] = $fullName . ' AS ' . $dimAlias;
            }
        }

        foreach ($queryWhere['indicator_list'] as $item) {
            if (!str_contains($item['field_name_en'], '.')) {
                $aliasName = $crud->table_name_en . '_' . $item['field_name_en'];
                $item['field_name_en'] = '`' . $prefix . $crud->table_name_en . '`.`' . $item['field_name_en'] . '`';
            } else {
                $tmpName = explode('.', $item['field_name_en']);
                $aliasName = $tmpName[0] . '_' . $tmpName[1];
                $item['field_name_en'] = '`' . $prefix . $tmpName[0] . '`.`' . $tmpName[1] . '`';
            }
            $aliasNames[] = $aliasName;
            $fieldNames[] = $item['field_name'];
            $sort = $this->getQuerySort($item, '`' . $aliasName . '`');
            $select[] = $this->getIndicatorSelect($item['operator'], $item['field_name_en'], $aliasName);
        }

        // 附加条件
        $viewSearch = array_merge($viewSearch, $this->getViewSearch($queryWhere, $crud->id, $crud->table_name_en));
        $model = $crudModuleService->model(crudId: $crud->id)
            ->setJoin($systemCrudService->getJoinCrudData($crud->id, 1))
            ->viewSearch($viewSearch, null, $queryWhere['additional_search_boolean'] == 0 ? 'or' : 'and');

        if (isset($queryWhere['user_id'])) {
            $model->whereIn($queryWhere['table_name_en'] . '.user_id', $queryWhere['user_id']);
        }

        if ($cascadeRadio['value'] > 0) {
            $model->where($cascadeRadio['field'], 'like', '%/' . $cascadeRadio['value'] . '/%');
        }

        if ($select) {
            $model->selectRaw(implode(',', $select));
        }
        if ($sort) {
            $model->orderByRaw($sort);
        }

        if ($group) {
            $model->groupByRaw($group);
        }

        $result = $model->get()->toArray();
        if ($queryWhere['dimension_list']) {
            foreach ($result as $key => $item) {
                if ($cascadeRadio['data_dict_id'] > 0) {
                    $val = $this->getDimValue($item, $cascadeRadio);
                    if (!$val) {
                        $cascadeRadio['discard'][$key] = 1;
                        $xAxisTmp[] = [$dimAlias => ''];
                        continue;
                    }
                    $dimValue[$key] = $val;

                    $result[$key][$cascadeRadio['field_as']] = $val;
                    $xAxisTmp[] = [$dimAlias => $val];
                } else {
                    $xAxisTmp[] = [$dimAlias => $item[$dimAlias] ?? ''];
                }
            }
        }

        $xAxisTmp && $xAxisTmp = $crudModuleService->otherWithAliasList(array_map(function ($i) {
            return $i;
        }, $withField), $xAxisTmp, $fields);

        // 组合数据
        foreach ($result as $key => $item) {
            $isDiscard = isset($cascadeRadio['discard'][$key]);
            if ($isDiscard) {
                continue;
            }
            $tmp = [
                'name'      => $xAxisTmp ? ($xAxisTmp[$key][$dimAlias] ?? '未知') : $fieldNames[$key],
                'dim_value' => $dimValue[$key] ?? '',
            ];
            foreach ($aliasNames as $yaKey => $aliasName) {
                if (isset($item[$aliasName])) {
                    $tmp['other'][$fieldNames[$yaKey] ?? '未知'] = $item[$aliasName];
                    if ($yaKey == 0) {
                        $tmp['value'] = $item[$aliasName];
                    }
                }
            }
            $data[] = $tmp;
        }

        return $data;
    }

    /**
     * 数据列表查询.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dataListQuery(array $queryWhere): array
    {
        if (!empty($queryWhere['crud_id'])) {
            $crud = app()->get(SystemCrudService::class)->getCrudInfoCache(crudId: $queryWhere['crud_id']);
        } else {
            $crud = app()->get(SystemCrudService::class)->getCrudInfoCache($queryWhere['table_name_en']);
        }

        $defaultWhere['user_id'] = $queryWhere['user_id'];
        $defaultWhere['uid'] = auth('admin')->id();
        if ($queryWhere['system_user_id'] && $queryWhere['scope_frame'] === 'all') {
            $defaultWhere['user_id'] = array_merge($defaultWhere['user_id'], $queryWhere['system_user_id']);
        }

        $page = (int)$queryWhere['page'] ?: 1;
        $limit = (int)$queryWhere['limit'] ?: 20;
        $showField = $queryWhere['show_field'];

        $viewSearch = [];
        if (!empty($queryWhere['chart_id']) || !empty($queryWhere['chart_data'])) {
            $viewSearch = $this->getChartRequestViewSearch(search: $queryWhere['search'], chartId: (int)$queryWhere['chart_id'], uniqued: $queryWhere['uniqued'], type: 'search', chartData: $queryWhere['chart_data']);
        }

        $viewSearch = array_merge($queryWhere['additional_search'], $viewSearch);
        $searchBool = (int)$queryWhere['additional_search_boolean'];
        $noPrivileges = (bool)$queryWhere['no_privileges'];
        if ($noPrivileges) {
            unset($defaultWhere['user_id']);
        }
        return app()->get(CrudModuleService::class)->getListQuery($showField, $crud, $defaultWhere, [], $viewSearch, $searchBool, $page, $limit);
    }

    /**
     * 获取实体搜索字段展示.
     * @throws BindingResolutionException|\ReflectionException
     */
    public function viewSearchField(int $crudId): array
    {
        $list = [];
        $viewSearch = app()->get(SystemCrudService::class)->getEventCrud($crudId)['field'];
        $fieldIds = array_column($viewSearch, 'id');
        $association = app()->get(SystemCrudFieldService::class)->fieldIdByAssociationInfo($fieldIds);
        $associationData = [];
        foreach ($association as $item) {
            $associationData[$item['id']] = $item['association'];
        }
        foreach ($viewSearch as $search) {
            $isUser = !empty($associationData[$search['id']]) && $associationData[$search['id']]['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_USER;
            $isFrame = !empty($associationData[$search['id']]) && $associationData[$search['id']]['table_name_en'] === SystemCrudService::SYSTEM_TABLE_TABLE_FRAME;
            $list[] = [
                'type'                  => $search['form_value'],
                'field'                 => $search['field_name_en'],
                'title'                 => $search['label'],
                'id'                    => $search['id'],
                'is_user'               => $isUser,
                'is_frame'              => $isFrame,
                'options'               => $search['data_dict'],
                'crud_id'               => $search['crud_id'],
                'association_show_type' => $search['association_show_type'],
            ];
        }
        return $list;
    }

    /**
     * 整合视图数据.
     */
    public function combinationAdditionalSearch(array $additionalSearch, string $tableNameEn): array
    {
        $list = [];
        foreach ($additionalSearch as $additional) {
            $fieldNameEn = $additional['form_field'] ?? '';
            if (!$fieldNameEn) {
                continue;
            }
            if (!str_contains($fieldNameEn, '.')) {
                $fieldNameEn = $tableNameEn . '.' . $fieldNameEn;
            }
            $list[] = [
                'field_name' => $fieldNameEn,
                'operator'   => $additional['operator'],
                'value'      => $additional['value'],
            ];
        }
        return $list;
    }

    /**
     * 获取追加withField.
     */
    public function getWithField(SystemCrudField $crudField, array $item, string $tableNameEn = ''): array
    {
        return [
            'association_crud_id'            => $crudField['association']['id'],
            'field_name_en'                  => $item['field_name_en'],
            'field_name_as'                  => !str_contains($item['field_name_en'], '.') ? $tableNameEn . '_' . $item['field_name_en'] : str_replace('.', '_', $item['field_name_en']),
            'association_table_name_en'      => $crudField['association']['table_name_en'],
            'association_field_main_name_en' => $crudField['association']['field'][0]['field_name_en'] ?? '',
            'association_field_id_name_en'   => $item['field_name_en'],
        ];
    }

    /**
     * 获取关联数据.
     * @return null|array|Model
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAssociationCrudField(int $crudId, string $fieldNameEn = ''): mixed
    {
        return app()->get(SystemCrudFieldService::class)->get(
            ['field_name_en' => $fieldNameEn, 'crud_id' => $crudId],
            ['association_crud_id', 'field_name_en', 'form_value', 'data_dict_id'],
            ['association' => fn($q) => $q->with(['field' => fn($q) => $q->where('is_main', 1)->select(['field_name_en', 'crud_id'])])]
        );
    }

    /**
     * 获取维度级联value.
     */
    public function getDimValue(array $item, array $cascadeRadio): string
    {
        $origin = (string)$item[$cascadeRadio['field_as']];
        $index = strripos($origin, ($cascadeRadio['value'] > 0 ? $cascadeRadio['value'] : '') . '/');
        if ($index) {
            $val = substr($origin, strripos($origin, $cascadeRadio['value'] . '/') + 3);
        } else {
            $arr = array_filter(explode('/', $origin));
            $val = (string)array_pop($arr);
        }
        return $val;
    }

    /**
     * 获取排序数据.
     */
    public function getQuerySort(array $item, string $field = ''): string
    {
        $sort = '';
        if (isset($item['sort']) && $item['sort'] && in_array(strtolower($item['sort']), ['asc', 'desc'])) {
            $sort = $field . ' ' . $item['sort'];
        }
        return $sort;
    }

    /**
     * 获取视图数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getViewSearch(array $queryWhere, int $crudId, string $tableNameEn): array
    {
        $search = $this->combinationAdditionalSearch($queryWhere['additional_search'], $tableNameEn);
        $search = app()->get(CrudModuleService::class)->setViewSearch($search, $crudId);
        foreach ($search as &$item) {
            if (($item['operator'] ?? '') === CrudOperatorEnum::OPERATOR_IN
                && in_array($item['form_value'] ?? '', [CrudFormEnum::FORM_DATE_PICKER, CrudFormEnum::FORM_DATE_TIME_PICKER])
                && (is_array($item['value'] ?? null) || (is_string($item['value'] ?? null) && str_contains($item['value'], '-')))) {
                $item['operator'] = CrudOperatorEnum::OPERATOR_BT;
            }
        }
        return $search;
    }

    /**
     * 允许权限查询.
     */
    public function allowAuth(array $fields, string $fieldName = 'user_id'): bool
    {
        return in_array($fieldName, array_column($fields, 'field_name_en'));
    }
}
