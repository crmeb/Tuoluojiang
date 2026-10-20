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

use App\Constants\CacheEnum;
use App\Constants\Crud\CrudFormEnum;
use App\Http\Dao\Crud\SystemCrudApproveDao;
use App\Http\Service\Approve\ApproveProcessService;
use App\Http\Service\Customer\LabelService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class SystemCrudApproveService.
 * @email 136327134@qq.com
 * @date 2024/3/21
 * @mixin SystemCrudApproveDao
 */
class SystemCrudApproveService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    private int $cacheTtl;

    public function __construct(SystemCrudApproveDao $dao)
    {
        $this->dao      = $dao;
        $this->cacheTtl = (int) sys_config('system_cache_ttl', 3600);
    }

    /**
     * 列表.
     * @param mixed $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        $cateService = app()->get(SystemCrudCateService::class);
        $listData    = parent::getList($where, $field, $sort, [
            'card' => function ($query) {
                $query->select(['id', 'avatar', 'name', 'phone']);
            },
            'process' => function ($query) {
                $query->select(['info->nodeUserList as info', 'approve_id']);
            },
            'crud' => fn ($q) => $q->select(['id', 'table_name', 'cate_ids']),
        ]);
        foreach ($listData['list'] as &$list) {
            if ($list['crud']) {
                $names             = $cateService->column(['ids' => $list['crud']['cate_ids']], 'name');
                $list['cate_name'] = $names ? implode('、', $names) : '';
            } else {
                $list['cate_name'] = '';
            }
            if (isset($list['process']['info']['userList']) && $list['process']['info']['userList']) {
                $list['userList'] = implode('、', array_column($list['process']['info']['userList'], 'name'));
            } else {
                $list['userList'] = '';
            }
            unset($list['process']);
        }
        return $listData;
    }

    /**
     * 保存配置信息.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $userId         = uuid_to_uid($this->uuId(false));
        $processService = app()->get(SystemCrudApproveProcessService::class);
        $ruleService    = app()->get(SystemCrudApproveRuleService::class);
        $baseConfig     = $this->checkBaseConfig($data, $userId);
        $processConfig  = $processService->checkProcessConfig($data, $userId);
        $ruleConfig     = $ruleService->checkRuleConfig($data, $userId);
        $res            = $this->transaction(function () use ($baseConfig, $processConfig, $ruleConfig, $processService, $ruleService) {
            $res1 = $this->dao->create($baseConfig);
            if (! $res1) {
                throw $this->exception('保存基础配置失败');
            }
            $res2 = $processService->saveMore($processConfig, $res1->id);
            if (! $res2) {
                throw $this->exception('保存流程配置失败');
            }
            $ruleConfig['approve_id'] = $res1->id;
            $res3                     = $ruleService->create($ruleConfig);
            if (! $res3) {
                throw $this->exception('保存规则配置失败');
            }
            return $res1;
        });
        Cache::tags([CacheEnum::TAG_APPROVE])->flush();
        return $res;
    }

    /**
     * 获取配置信息详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        return Cache::tags([CacheEnum::TAG_APPROVE])->remember('approve_config_' . $id, $this->cacheTtl, function () use ($id) {
            $baseConfig    = toArray($this->dao->get($id));
            $processConfig = [];
            $ruleConfig    = [];
            if ($baseConfig) {
                $processConfig = app()->get(SystemCrudApproveProcessService::class)->value(['approve_id' => $id, 'is_initial' => 1], 'info');
                $processConfig = app()->get(ApproveProcessService::class)->enrichProcessConfigInfo($processConfig);
                $ruleConfig    = toArray(app()->get(SystemCrudApproveRuleService::class)->get(['approve_id' => $id], ['*'], [
                    'abCard' => function ($query) {
                        $query->withTrashed()->select(['id', 'name', 'avatar', 'status']);
                    },
                ]));
                if ($ruleConfig && empty($ruleConfig['ab_card']) && (int) ($ruleConfig['abnormal'] ?? 0) > 0) {
                    $ruleConfig['ab_card'] = app()->get(ApproveProcessService::class)
                        ->getProcessUserProfile((int) $ruleConfig['abnormal']);
                }
            }
            return compact('baseConfig', 'processConfig', 'ruleConfig');
        });
    }

    /**
     * 保存配置信息.
     * @param mixed $id
     * @return mixed
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     */
    public function resourceUpdate($id, array $data)
    {
        if (! $this->dao->exists($id)) {
            throw $this->exception('修改的记录不存在，请确认后重试');
        }
        $userId         = uuid_to_uid($this->uuId(false));
        $processService = app()->get(SystemCrudApproveProcessService::class);
        $ruleService    = app()->get(SystemCrudApproveRuleService::class);
        $baseConfig     = $this->checkBaseConfig($data, $userId);
        $processConfig  = $processService->checkProcessConfig($data, $userId);
        $ruleConfig     = $ruleService->checkRuleConfig($data, $userId);
        $res            = $this->transaction(function () use ($id, $baseConfig, $processConfig, $ruleConfig, $processService, $ruleService) {
            // 保存基础配置
            $res1 = $this->dao->update(['id' => $id], $baseConfig);
            if (! $res1) {
                throw $this->exception('保存基础配置失败');
            }
            // 保存流程配置
            $processService->delete(['not_uniqued' => array_column($processConfig, 'uniqued'), 'approve_id' => $id]);
            if ($processConfig) {
                $res3 = $processService->saveMore($processConfig, $id);
                if (! $res3) {
                    throw $this->exception('保存流程配置失败');
                }
            }
            // 保存规则配置
            $ruleService->update(['approve_id' => $id], $ruleConfig);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_APPROVE])->flush();
    }

    /**
     * 删除.
     * @param mixed $id
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (! $this->dao->exists($id)) {
            throw $this->exception('删除的记录不存在');
        }
        return $this->dao->delete($id, $key) && Cache::tags([CacheEnum::TAG_APPROVE])->flush();
    }

    /**
     * 修改状态
     * @param mixed $id
     * @return mixed
     */
    public function resourceShowUpdate($id, array $data)
    {
        if ($res = $this->showUpdate($id, $data)) {
            Cache::tags([CacheEnum::TAG_APPROVE])->flush();
        }
        return $res;
    }

    /**
     * 处理基础配置.
     * @param mixed $userId
     * @param mixed $data
     * @param mixed $type
     * @return mixed
     */
    public function checkBaseConfig($data, $userId, $type = 'baseConfig'): array
    {
        return [
            'crud_id' => $data[$type]['crud_id'],
            'name'    => $data[$type]['name'],
            'icon'    => $data[$type]['icon'],
            'color'   => $data[$type]['color'],
            'info'    => $data[$type]['info'],
            'user_id' => $userId,
            'sort'    => $data[$type]['sort'] ?? 1,
        ];
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 获取审批内容.
     * @return array
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     */
    public function getContent(int $crudId, int $linkId, int $applyId = 0)
    {
        // 获取服务实例
        $services = collect([
            'field'  => app()->get(SystemCrudFieldService::class),
            'module' => app()->get(CrudModuleService::class),
            'record' => app()->get(SystemCrudApproveRecordService::class),
            'dict'   => app()->get(DictDataService::class),
            'label'  => app()->get(LabelService::class),
        ]);
        // 获取字段列表并转为集合
        $fields = collect($services['field']->select(['crud_id' => $crudId, 'is_form' => 1], ['field_name', 'field_name_en', 'form_value', 'field_type', 'form_field_uniqid', 'options', 'data_dict_id', 'association_crud_id'], ['association'])?->toArray());
        // 获取记录数据
        $record = $services['record']->value(['approve_id' => $applyId, 'crud_id' => $crudId], 'data');
        // 检查数据表是否存在
        $model = $services['module']->model(crudId: $crudId);
        if (! Schema::hasTable($model->getTable())) {
            return [];
        }
        // 获取主数据
        $data = collect($services['module']->model(crudId: $crudId)->setTrashed()->get(
            $linkId,
            $fields->pluck('field_name_en')->all(),
            [
                'ownerUser'  => fn ($q) => $q->select(['id', 'name']),
                'ownerFrame' => fn ($q) => $q->select(['id', 'name']),
                'updateUser' => fn ($q) => $q->select(['id', 'name']),
                'createUser' => fn ($q) => $q->select(['id', 'name'])]
        )?->toArray());
        // 处理内容数据
        return $fields->filter(function ($field) use ($data, $record) {
            return $data->isNotEmpty() || ($record && isset($record[$field['field_name_en']]));
        })->map(function ($field) use ($services, $data, $record) {
            // 合并记录数据到主数据
            $fieldData = $data->all();
            if (isset($record[$field['field_name_en']])) {
                $fieldData[$field['field_name_en']] = $record[$field['field_name_en']];
            }
            // 基础信息
            $base = ['uniqued' => $field['form_field_uniqid'], 'label' => $field['field_name'], 'type' => $field['form_value']];
            // 处理关联数据
            $value = null;
            if ($field['association_crud_id'] && $field['association']) {
                $tableName = $services['field']->value(['crud_id' => $field['association_crud_id'], 'is_main' => 1], 'field_name_en');
                if ($tableName) {
                    $value = $services['module']->setTableName($field['association']['table_name_en'])->value($fieldData[$field['field_name_en']], $tableName);
                }
            }
            // 根据字段类型处理值
            return array_merge($base, match ($field['form_value']) {
                CrudFormEnum::FORM_IMAGE, CrudFormEnum::FORM_FILE => ['type' => 'uploadFrom', 'value' => isset($fieldData[$field['field_name_en']]) ? json_decode($fieldData[$field['field_name_en']], true) : []],
                CrudFormEnum::FORM_INPUT_PERCENTAGE => ['value' => $fieldData[$field['field_name_en']] . '/' . ($field['options']['max'] ?? 100)],
                CrudFormEnum::FORM_RADIO            => ['value' => $services['dict']->value(['value' => $fieldData[$field['field_name_en']], 'type_id' => $field['data_dict_id']], 'name')],
                CrudFormEnum::FORM_CASCADER_RADIO   => ['value' => implode('/', $fieldData[$field['field_name_en']] ? $services['dict']->column(['values' => explode('/', $fieldData[$field['field_name_en']]), 'type_id' => $field['data_dict_id']], 'name') : [])],
                CrudFormEnum::FORM_CHECKBOX         => ['value' => implode('、', $fieldData[$field['field_name_en']] ? $services['dict']->column(['values' => explode('/', $fieldData[$field['field_name_en']]), 'type_id' => $field['data_dict_id']], 'name') : [])],
                CrudFormEnum::FORM_TAG              => $field['data_dict_id'] ? ['value' => implode('、', $fieldData[$field['field_name_en']] ? $services['dict']->column(['id' => explode('/', $fieldData[$field['field_name_en']]), 'type_id' => $field['data_dict_id']], 'name') : [])]
                    : ['value' => implode('、', $fieldData[$field['field_name_en']] ? $services['label']->idByValue(explode('/', $fieldData[$field['field_name_en']])) : [])],
                CrudFormEnum::FORM_INPUT_SELECT => match ($field['field_name_en']) {
                    'user_id'        => ['value' => $data->get('create_user.name', '')],
                    'update_user_id' => ['value' => $data->get('update_user.name', '')],
                    'frame_id'       => ['value' => $data->get('owner_frame.name', '')],
                    'owner_user_id'  => ['value' => $data->get('owner_user.name', '')],
                    default          => ['value' => $value ?? $fieldData[$field['field_name_en']] ?? '']
                },
                CrudFormEnum::FORM_CASCADER         => ['value' => isset($fieldData[$field['field_name_en']]) ? $this->getCascaderDictData((string) $fieldData[$field['field_name_en']], (int) $field['data_dict_id']) : []],
                CrudFormEnum::FORM_CASCADER_ADDRESS => ['value' => implode('/', $fieldData[$field['field_name_en']] ? $services['dict']->column(['id' => explode('/', $fieldData[$field['field_name_en']]), 'type_id' => $field['data_dict_id']], 'name') : [])],
                CrudFormEnum::FORM_SWITCH           => ['value' => $fieldData[$field['field_name_en']] ? '开启' : '关闭'],
                CrudFormEnum::FORM_RICH_TEXT        => ['value' => isset($fieldData[$field['field_name_en']]) ? htmlspecialchars_decode($fieldData[$field['field_name_en']]) : ''],
                default                             => ['value' => $fieldData[$field['field_name_en']] ?? '']
            });
        })->all();
    }

    /**
     * 获取审批.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/2
     */
    public function getCrudApproveList(int $crudId)
    {
        $crudApproveId = app()->get(SystemCrudEventService::class)
            ->column(['crud_id' => $crudId, 'status' => 1], 'crud_approve_id');
        $crudApproveId = array_merge(array_unique(array_filter($crudApproveId)));
        return $this->dao->getCrudApprove($crudId, $crudApproveId);
    }

    /**
     * 获取tag内容.
     * @return string
     * @throws BindingResolutionException
     */
    private function getCascaderDictData(string $data, int $dictId)
    {
        $data = explode(',', $data);
        if (! $data) {
            return '';
        }
        $dataDict = app()->get(DictDataService::class)->idByValues($dictId);
        $res      = [];
        foreach ($data as $v) {
            $v  = array_filter(explode('/', $v));
            $vv = [];
            foreach ($dataDict as $item) {
                if (in_array($item['value'], $v)) {
                    $vv[] = $item['name'];
                }
            }
            $res[] = implode('/', $vv);
        }
        return implode('、', $res);
    }
}
