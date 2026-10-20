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

namespace App\Http\Service\Config;

use App\Constants\CacheEnum;
use App\Constants\CodeEnum;
use App\Constants\CustomEnum\ClueEnum;
use App\Constants\CustomEnum\ContractEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\CustomEnum\LiaisonEnum;
use App\Constants\CustomEnum\OddsEnum;
use App\Constants\CustomEnum\ProductEnum;
use App\Constants\System\ViewSearchEnum;
use App\Constants\UserAgentEnum;
use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Dao\Config\FormCateDao;
use App\Http\Dao\Config\FormDataDao;
use App\Http\Model\Config\FormData;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\LeadService;
use App\Http\Service\Customer\LiaisonService;
use App\Http\Service\Customer\OpportunityService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\ProductCategoryService;
use App\Http\Service\Customer\ProductService;
use App\Http\Service\Warehouse\WarehouseService;
use App\Jobs\Customer\FormUpdateJob;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FormService extends BaseService
{
    public FormDataDao $dataDao;

    public function __construct(FormCateDao $dao, FormDataDao $dataDao)
    {
        $this->dao     = $dao;
        $this->dataDao = $dataDao;
    }

    /**
     * 列表.
     * @param string $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'title', 'sort', 'types', 'status', 'ident'], $sort = 'sort', array $with = ['data']): array
    {
        $types = $where['types'] ?? 0;
        return $this->dao->getList($where, $field, 0, 0, $sort, $with, function ($list) use ($types) {
            $field = match ((int) $types) {
                CustomEnum::CUSTOMER => CustomerEnum::CUSTOMER_NOT_ALLOW_DELETE_FIELD,
                CustomEnum::CONTRACT => ContractEnum::CONTRACT_NOT_ALLOW_DELETE_FIELD,
                CustomEnum::LIAISON  => LiaisonEnum::LIAISON_NOT_ALLOW_DELETE_FIELD,
                CustomEnum::CLUE     => ClueEnum::CLUE_NOT_ALLOW_DELETE_FIELD,
                CustomEnum::ODDS     => OddsEnum::ODDS_NOT_ALLOW_DELETE_FIELD,
                CustomEnum::PRODUCT  => ProductEnum::PRODUCT_NOT_ALLOW_DELETE_FIELD,
                default              => []
            };
            foreach ($list as $item) {
                foreach ($item->data as $data) {
                    $data->enable_delete = 1;
                    if (in_array($data->key, $field)) {
                        $data->enable_delete = 0;
                    }
                }
            }
            $this->appendProductListField($list, (int) $types);
        });
    }

    /**
     * 保存表单分类.
     * @throws BindingResolutionException
     */
    public function saveCate(int $types, array $data): BaseModel
    {
        $data['types'] = $types;
        $res           = $this->dao->create($data);
        if ($res) {
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        }
        return $res;
    }

    /**
     * 更新表单分类.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function updateCate(int $id, array $data)
    {
        $res = $this->transaction(function () use ($id, $data) {
            return $this->dao->update($id, $data);
        });
        return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 删除表单分类.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteCate(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }

            if ($this->dataDao->count(['cate_id' => $id])) {
                $dataRes = $this->dataDao->delete(['cate_id' => $id]);
                if (! $dataRes) {
                    throw $this->exception(__('common.delete.fail'));
                }

                Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            }
            return true;
        });
    }

    /**
     * 更新状态
     * @throws BindingResolutionException
     */
    public function updateStatus(int $id, int $status): bool
    {
        return $this->dao->update($id, ['status' => $status != 0 ? 1 : 0]) && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 更新排序.
     * @throws BindingResolutionException
     */
    public function updateSort(int $types, array $data): bool
    {
        if (empty($data)) {
            throw $this->exception('参数错误');
        }

        return $this->transaction(function () use ($types, $data) {
            $sort = range(count($data), 1);
            foreach ($data as $key => $datum) {
                $this->dao->update(['types' => $types, 'id' => (int) $datum], ['sort' => $sort[$key] ?? 0]);
            }
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return true;
        });
    }

    /**
     * 保存表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function saveData(int $types, array $data): void
    {
        $cateIds = $this->dao->column(['types' => $types]);
        if (array_diff(array_column($data, 'cate_id'), $cateIds)) {
            throw $this->exception('分组数据异常');
        }
        foreach ($data as $item) {
            if (empty($item['data'])) {
                $this->dataDao->delete(['cate_id' => $item['cate_id']]);
                continue;
            }
            $cateDataIds = $this->dataDao->column(['cate_id' => $item['cate_id']], 'key', 'id');
            $num         = count($item['data']);
            foreach ($item['data'] as $form) {
                if (empty($form)) {
                    continue;
                }
                $tmpForm = [
                    'key_name'      => $form['key_name'] ?? '',
                    'type'          => $form['type'] ?? '',
                    'sort'          => $num,
                    'max'           => $form['max'] ?? 0,
                    'min'           => $form['min'] ?? 0,
                    'status'        => $form['status'] ?? 2,
                    'value'         => $form['value'] ? (is_array($form['value']) ? json_encode($form['value'], JSON_UNESCAPED_UNICODE) : $form['value']) : '',
                    'param'         => $form['param'] ?? '',
                    'uniqued'       => $form['uniqued'] ?? '',
                    'required'      => $form['required'] ?? '',
                    'input_type'    => $form['input_type'] ?? '',
                    'upload_type'   => $form['upload_type'] ?? 0,
                    'placeholder'   => $form['placeholder'] ?? '',
                    'dict_ident'    => $form['dict_ident'] ?? '',
                    'decimal_place' => $form['decimal_place'] ?? 0,
                    'cate_id'       => $item['cate_id'] ?? 0,
                    'key'           => $form['key'] ?? $this->getUniKey(),
                ];
                $this->dataDao->updateOrCreate(['id' => $form['id'] ?? 0], $tmpForm);
                unset($cateDataIds[$form['id'] ?? 0]);
                --$num;
            }
            if ($cateDataIds && ! $this->dataDao->delete(['cate_id' => $item['cate_id'], 'id' => array_keys($cateDataIds)])) {
                throw $this->exception('保存失败');
            }
        }
        FormUpdateJob::dispatch($types, $cateIds) && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 移动分组.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function moveData(int $types, int $id, int $cateId): bool
    {
        $cateIds = $this->dao->column(['types' => $types]);
        if (! in_array($cateId, $cateIds)) {
            throw $this->exception('分组数据异常');
        }

        $info = $this->dataDao->get($id);
        if (! $info) {
            throw $this->exception('移动失败');
        }
        if (! in_array($info->cate_id, $cateIds)) {
            throw $this->exception('分组数据异常');
        }

        $info->cate_id = $cateId;
        return $info->save() && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 获取 key 标识.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUniKey(): string
    {
        while (true) {
            $tmpKey = substr('c' . md5(uniqid(microtime(), true) . mt_rand(0, 99999)), 0, 8);
            if (! $this->dataDao->exists(['key' => $tmpKey])) {
                $key = $tmpKey;
                break;
            }
        }
        return $key;
    }

    /**
     * 获取自定义表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCustomDataByTypes(int $types, array $field = ['*'], array $with = []): array
    {
        $cateIds = $this->dao->column(['types' => $types], 'id');
        return $this->dataDao->getTreeStructure(['cate_id' => $cateIds, 'status' => 1], $field, $with);
    }

    /**
     * 判断自定义字段是否需要以 JSON/array 形式存储.
     * @param mixed $field
     */
    public function isJsonCustomField($field, ?int $dictLevel = null): bool
    {
        $type      = strtolower((string) ($field['type'] ?? ''));
        $inputType = strtolower((string) ($field['input_type'] ?? ''));

        if ($type === 'multiplemember' || ($type === 'multiple' && $inputType === 'select')) {
            return true;
        }

        if (in_array($inputType, ['checked', 'file', 'images'], true)) {
            return true;
        }

        return $type === 'single' && $inputType === 'select' && $dictLevel !== null && $dictLevel > 1;
    }

    /**
     * 更新业务表字段.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function reloadCustomTableField(int $types, array $cateIds): bool
    {
        $field      = ['id', 'key', 'key_name', 'type', 'input_type', 'value', 'max', 'decimal_place', 'dict_ident'];
        $dataFields = $this->dataDao->select(['cate_id' => $cateIds], $field);
        if ($dataFields->isEmpty()) {
            return true;
        }
        $dictService = app()->get(DictDataService::class);
        $service     = $this->getCustomServiceByTypes($types);
        try {
            $table                = $service->getTable();
            $outOfSyncField       = $service->getOutOfSyncField();
            $columns              = array_diff(Schema::getColumnListing($table), ['updated_at', 'deleted_at', 'deleted_at']);
            $jsonArrayIndexFields = [];

            Schema::table($table, function (Blueprint $table) use ($dataFields, $columns, $outOfSyncField, $dictService, &$jsonArrayIndexFields) {
                foreach ($dataFields as $dataField) {
                    if ($outOfSyncField && in_array($dataField['key'], $outOfSyncField)) {
                        continue;
                    }
                    $inputType = strtolower($dataField['input_type']);
                    $fieldKey  = $dataField['key'];

                    // 目标类型判断
                    $isJsonType     = false;
                    $isSingleMember = false;
                    $dictIdent      = $dataField['dict_ident'] ?? '';
                    $dictLevel      = $dictIdent ? (int) $dictService->max(['type_name' => $dictIdent], 'level') : 0;
                    if ($this->isJsonCustomField($dataField, $dictLevel)) {
                        $isJsonType = true;
                    } elseif ($dataField['type'] == 'singleMember') {
                        $isSingleMember = true;
                    }

                    if ($isJsonType && $this->isJsonArrayIndexableCustomField($dataField, $dictLevel)) {
                        $jsonArrayIndexFields[] = $fieldKey;
                    }

                    // 已有列不走 doctrine change：业务表存在 JSON 多值函数索引时 DBAL 会崩溃，
                    // 且 MySQL DDL 隐式提交会导致“新列已建成、后续 change 失败”的半成品状态。
                    if (in_array($fieldKey, $columns, true)) {
                        continue;
                    }

                    // 自定义字段允许为空，必填由业务层校验
                    if ($isSingleMember) {
                        $obj = $table->unsignedInteger($fieldKey)->nullable();
                    } elseif ($inputType == 'date') {
                        $obj = $table->date($fieldKey)->nullable();
                    } elseif ($inputType == 'datetime') {
                        $obj = $table->timestamp($fieldKey)->nullable();
                    } elseif ($inputType == 'oawangeditor') {
                        $obj = $table->text($fieldKey)->nullable();
                    } elseif ($dataField['decimal_place']) {
                        $place = intval($dataField['decimal_place']);
                        $obj   = $table->decimal($fieldKey, 10, min(6, $place))->nullable();
                    } elseif ($dataField['type'] == 'textarea') {
                        $obj = $table->text($fieldKey)->nullable();
                    } elseif ($isJsonType) {
                        $obj = $table->json($fieldKey)->nullable();
                    } else {
                        $obj = $table->string($fieldKey, 255)->nullable();
                    }
                    $obj->comment($dataField['key_name']);
                }
            });
            // 兜底修复历史已建 NOT NULL 自定义列（含新建时漏设 nullable、或 change 失败留下的存量字段）
            $this->ensureCustomColumnsNullable($table, $dataFields->pluck('key')->filter()->values()->all());
            if ($this->supportsJsonArrayIndex()) {
                foreach (array_unique($jsonArrayIndexFields) as $fieldKey) {
                    $this->addJsonArrayIndex($table, $fieldKey);
                }
            }
            Schema::getConnection()->commit();
            return true;
        } catch (\Throwable $e) {
            Log::error('业务表字段更新失败:' . json_encode([
                'file'    => $e->getFile(),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]));
            // Schema 失败时仍尝试修复可空，避免客户创建持续 1048
            try {
                if (isset($table, $dataFields) && is_string($table) && $dataFields) {
                    $this->ensureCustomColumnsNullable($table, $dataFields->pluck('key')->filter()->values()->all());
                }
            } catch (\Throwable $fixError) {
                Log::error('自定义字段可空修复失败:' . $fixError->getMessage());
            }
            return false;
        }
    }

    /**
     * 重新同步指定业务（或全部）自定义字段列结构，并修复 NOT NULL 列.
     *
     * @return array<int, bool>
     */
    public function fixCustomTableFieldsNullable(?int $types = null): array
    {
        $typeList = $types !== null ? [$types] : [
            CustomEnum::CUSTOMER,
            CustomEnum::CONTRACT,
            CustomEnum::LIAISON,
            CustomEnum::CLUE,
            CustomEnum::PRODUCT,
            CustomEnum::ODDS,
        ];

        $results = [];
        foreach ($typeList as $type) {
            $cateIds         = $this->dao->column(['types' => $type], 'id') ?: [];
            $results[$type]  = $this->reloadCustomTableField($type, $cateIds);
        }

        return $results;
    }

    /**
     * 表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFormDataList(int $types, array $with = [], array $field = ['*']): array
    {
        return $this->dataDao->setDefaultSort('sort')->select(['cate_exists' => $types, 'status' => 1], $field, with: $with)?->toArray();
    }

    public function isFormFieldRequired(int $types, string $key, array $list = []): bool
    {
        $list = $list ?: $this->getFormDataList($types);

        return collect($list)->contains(fn ($item) => ($item['key'] ?? '') === $key && (int) ($item['required'] ?? 0) === 1);
    }

    /**
     * 表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getDataList(string $customType): array
    {
        $customType = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => CustomEnum::CUSTOMER,
            ViewSearchEnum::VIEW_CONTRACT => CustomEnum::CONTRACT,
            ViewSearchEnum::VIEW_LIAISON  => CustomEnum::LIAISON,
            ViewSearchEnum::VIEW_CLUE     => CustomEnum::CLUE,
            ViewSearchEnum::VIEW_PRODUCT  => CustomEnum::PRODUCT,
            ViewSearchEnum::VIEW_ODDS     => CustomEnum::ODDS,
            default                       => 0
        };
        $cateIds = $this->dao->column(['types' => $customType, 'status' => 1]);
        if (! $cateIds) {
            return [];
        }
        $field = ['id', 'key', 'key_name', 'type', 'input_type', 'decimal_place', 'required', 'dict_ident', 'value', 'min', 'max', 'sort', 'uniqued'];
        return $this->dataDao->getList(['cate_id' => $cateIds, 'status' => 1], $field, 0, 0, 'sort');
    }

    /**
     * 业务表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFormDataWithType(int|string $customType, bool $withOptions = true, string $platform = UserAgentEnum::ADMIN_AGENT, int $associationId = 0, int $linkId = 0, int $oddsId = 0, array $hidden = []): array
    {
        if (is_string($customType)) {
            $customType = match ($customType) {
                ViewSearchEnum::VIEW_CUSTOMER => CustomEnum::CUSTOMER,
                ViewSearchEnum::VIEW_CONTRACT => CustomEnum::CONTRACT,
                ViewSearchEnum::VIEW_LIAISON  => CustomEnum::LIAISON,
                ViewSearchEnum::VIEW_CLUE     => CustomEnum::CLUE,
                ViewSearchEnum::VIEW_PRODUCT  => CustomEnum::PRODUCT,
                ViewSearchEnum::VIEW_ODDS     => CustomEnum::ODDS,
                default                       => 0
            };
        }
        $formList = $this->getList(['types' => $customType, 'status' => 1], with: ['data' => fn ($q) => $q->where(['status' => 1])]);
        $field    = ['name as label', 'name as text', 'value', 'pid'];
        // 预加载服务（避免循环内重复获取）
        $dictTypeService = app(DictTypeService::class);
        $dictService     = app(DictDataService::class);
        $adminId         = auth('admin')->id();
        foreach ($formList as $index => &$item) {
            $info = [];
            // 关联ID存在时，处理关联数据和字典自动创建
            if ($linkId) {
                // 提取唯一的关联类型
                $linkType = array_unique(array_column($item['data'] ?? [], 'link_type'));
                $linkType = array_filter($linkType);
                $linkType = end($linkType) ?: null;
                // 根据关联类型获取对应服务
                $service = $linkType ? match ($linkType) {
                    CustomEnum::CUSTOMER => app(CustomerService::class),
                    CustomEnum::CONTRACT => app(OrderService::class),
                    CustomEnum::PRODUCT  => app(ProductService::class),
                    CustomEnum::CLUE     => app(LeadService::class),
                    CustomEnum::ODDS     => app(OpportunityService::class),
                    default              => null
                } : null;
                // 获取关联信息数组
                $info = $service ? ($service->get($linkId)?->toArray() ?? []) : [];
                // 1. 提取关联字典标识（key => dict_ident）
                $linkDictIdent = collect($this->dao->select(
                    ['types' => $linkType],
                    with: ['data' => function ($q) use ($item) {
                        $linkFields = collect($item['data'] ?? [])->pluck('link_field')->filter()->all();
                        $q->whereIn('form_data.key', $linkFields)->select(['key', 'dict_ident', 'cate_id']);
                    }]
                )?->toArray() ?? [])->flatMap(fn ($val) => $val['data'] ?? [])->pluck('dict_ident', 'key')->filter();
                // 2. 提取新字典映射关系（link_field => key）
                $newDict = collect($item['data'] ?? [])->pluck('key', 'link_field')->filter(fn ($val, $key) => $key && $linkDictIdent->has($key))->toArray();
                // 3. 自动创建不存在的字典数据（排除followed）
                $linkDictIdent->reject(fn ($v, $key) => $key === 'followed')
                    ->each(function ($v, $k) use ($dictService, $info, $newDict) {
                        // 校验必要数据是否存在
                        if (! isset($newDict[$k]) || ! isset($info[$k])) {
                            return;
                        }
                        // 检查字典是否已存在
                        $existingDict = $dictService->get(['value' => $info[$k], 'type_name' => $newDict[$k]])?->toArray();
                        if ($existingDict) {
                            return;
                        }
                        // 组装新字典数据
                        $originDict  = $dictService->get(['type_name' => $v, 'value' => $info[$k]])?->toArray() ?? [];
                        $typeId      = $dictService->value(['type_name' => $newDict[$k]], 'type_id');
                        $newDictData = collect($originDict)->except(['id', 'pid', 'level'])->merge(['type_name' => $newDict[$k], 'type_id' => $typeId])->toArray();
                        // 创建新字典（非空判断）
                        if (! empty($newDictData)) {
                            $dictService->create($newDictData);
                        }
                    });
                // 清空字典缓存
                Cache::tags([CacheEnum::TAG_DICT])->flush();
            }
            // 处理表单单项数据（筛选、补全选项、关联值）
            foreach ($item['data'] as $key => $datum) {
                // 移除指定无用字段
                $excludeKeys = ['followed', 'customer_followed', 'contract_followed'];
                if (in_array($datum['key'], $excludeKeys) || in_array($datum['key'], $hidden)) {
                    unset($item['data'][$key]);
                    continue;
                }
                $level   = 0;
                $options = [];
                // 处理字典类型字段的选项和值格式化
                if (! empty($datum['dict_ident'])) {
                    $level = $dictTypeService->value(['ident' => $datum['dict_ident']], 'level') ?: 0;
                    // 单选框（一级字典）值格式化
                    if ($level === 1 && $datum['type'] === 'single') {
                        $formList[$index]['data'][$key]['value'] = is_array($datum['value']) ? end($datum['value']) : $datum['value'];
                    }
                    // 多选框值格式化
                    elseif ($datum['type'] === 'multiple') {
                        $value = $datum['value'] ?? [];
                        if (is_array($value) && is_array(end($value))) {
                            $value = call_user_func_array('array_merge', $value);
                        }
                        $formList[$index]['data'][$key]['value'] = $value;
                    } else {
                        $formList[$index]['data'][$key]['value'] = is_array($datum['value']) ? $datum['value'] : (string) $datum['value'];
                    }
                    // 加载字典选项（如需返回选项）
                    if ($withOptions) {
                        $options = $dictService->getTreeData(['type_name' => $datum['dict_ident'], 'status' => 1], $field);
                    }
                }
                // 业务规则集合（匹配对应字段的特殊处理）
                $ruleCollection = collect([
                    // 订单-客户ID
                    [
                        'condition' => fn () => $customType === CustomEnum::CONTRACT && $datum['key'] === 'contract_customer',
                        'level'     => 1,
                        'handle'    => function () use ($oddsId, $platform, $associationId, $adminId, &$formList, $index, $key) {
                            $customerService = app(CustomerService::class)->setPlatform($platform);
                            // 商机ID存在时，优先获取对应企业ID
                            if ($oddsId) {
                                $eid = app(OpportunityService::class)->value($oddsId, 'eid');
                                if ($eid) {
                                    $formList[$index]['data'][$key]['value'] = (int) $eid;
                                    return $customerService->getCurrentSelect($associationId, $adminId, ['id' => $eid]);
                                }
                            }
                            // associationId存在时，设置默认选中客户
                            if ($associationId > 0) {
                                $formList[$index]['data'][$key]['value'] = $associationId;
                            }
                            return $customerService->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                    // 订单-商机ID
                    [
                        'condition' => fn () => $customType === CustomEnum::CONTRACT && $datum['key'] === 'oid',
                        'level'     => 1,
                        'handle'    => function () use ($oddsId, $platform, $associationId, $adminId, &$formList, $index, $key) {
                            $customerOddsService = app(OpportunityService::class)->setPlatform($platform);

                            if ($oddsId) {
                                $formList[$index]['data'][$key]['value'] = $oddsId;
                                return $customerOddsService->getCurrentSelect($associationId, $adminId, ['id' => $oddsId]);
                            }
                            return $customerOddsService->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                    [
                        'condition' => fn () => $customType === CustomEnum::CONTRACT && $datum['key'] === 'outbound_warehouse_id',
                        'level'     => 1,
                        'handle'    => fn () => collect(app(WarehouseService::class)->setDefaultSort('sort')->select(['status' => WarehouseEnum::STATUS_ENABLE], ['id', 'name'])?->toArray() ?: [])
                            ->map(fn ($warehouse) => [
                                'value' => $warehouse['id'],
                                'label' => $warehouse['name'],
                                'text'  => $warehouse['name'],
                            ])->values()->all(),
                    ],
                    // 客户-商机ID
                    [
                        'condition' => fn () => $customType === CustomEnum::CUSTOMER && $datum['key'] === 'oid',
                        'level'     => 1,
                        'handle'    => function () use ($platform, $associationId, $adminId) {
                            return app(OpportunityService::class)
                                ->setPlatform($platform)
                                ->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                    // 客户-线索ID
                    [
                        'condition' => fn () => $customType === CustomEnum::CUSTOMER && $datum['key'] === 'clue_id' && ! empty($info),
                        'level'     => 1,
                        'handle'    => function () use ($info, &$formList, $index, $key) {
                            $formList[$index]['data'][$key]['value'] = $info['id'];
                            return [['value' => $info['id'], 'label' => $info['name'], 'text' => $info['name']]];
                        },
                    ],
                    // 产品-路径
                    [
                        'condition' => fn () => $customType === CustomEnum::PRODUCT && $datum['key'] === 'path',
                        'level'     => 2,
                        'handle'    => function () {
                            return app(ProductCategoryService::class)->getSelect(
                                ['status' => 1],
                                ['id as value', 'id', 'name as label', 'name as text', 'pid']
                            );
                        },
                    ],
                    // 商机-企业ID
                    [
                        'condition' => fn () => $customType === CustomEnum::ODDS && $datum['key'] === 'odds_customer',
                        'level'     => 1,
                        'handle'    => function () use ($platform, $associationId, $adminId, &$formList, $index, $key) {
                            if ($associationId > 0) {
                                $formList[$index]['data'][$key]['value'] = $associationId;
                            }
                            return app(CustomerService::class)
                                ->setPlatform($platform)
                                ->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                    // 联系人-企业ID
                    [
                        'condition' => fn () => $customType === CustomEnum::LIAISON && $datum['key'] === 'eid',
                        'level'     => 1,
                        'handle'    => function () use ($platform, $associationId, $adminId, &$formList, $index, $key) {
                            if ($associationId > 0) {
                                $formList[$index]['data'][$key]['value'] = $associationId;
                            }
                            return app(CustomerService::class)
                                ->setPlatform($platform)
                                ->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                    // 联系人-企业ID
                    [
                        'condition' => fn () => $customType === CustomEnum::LIAISON && $datum['key'] === 'liaison_name' && in_array('eid', $hidden),
                        'level'     => 1,
                        'handle'    => function () use ($platform, $associationId, $adminId, &$formList, $index, $key) {
                            if ($associationId > 0) {
                                $formList[$index]['data'][$key]['value'] = app(LeadService::class)->value($associationId, 'name');
                            }
                            return app(LeadService::class)->setPlatform($platform)->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                    // 联系人-企业ID
                    [
                        'condition' => fn () => $customType === CustomEnum::LIAISON && $datum['key'] === 'liaison_tel' && in_array('eid', $hidden),
                        'level'     => 1,
                        'handle'    => function () use ($platform, $associationId, $adminId, &$formList, $index, $key) {
                            if ($associationId > 0) {
                                $formList[$index]['data'][$key]['value'] = app(LeadService::class)->value($associationId, 'phone');
                            }
                            return app(LeadService::class)->setPlatform($platform)->getCurrentSelect($associationId, $adminId);
                        },
                    ],
                ]);
                // 匹配并执行对应业务规则
                $matchedRule = $ruleCollection->first(fn ($rule) => $rule['condition']());
                if ($matchedRule) {
                    $level   = $matchedRule['level'];
                    $options = $matchedRule['handle']();
                }
                // 补全表单数据的附加字段
                $formList[$index]['data'][$key]['options']       = $options;
                $formList[$index]['data'][$key]['options_level'] = $level;
                // 禁用字段逻辑：当associationId存在时，客户关联字段不可修改
                $formList[$index]['data'][$key]['disabled'] = false;
                if ($associationId > 0) {
                    $customerFieldMap = [
                        CustomEnum::CONTRACT => ['contract_customer'],
                        CustomEnum::ODDS     => ['odds_customer'],
                        CustomEnum::CUSTOMER => ['clue_id'],
                        CustomEnum::LIAISON  => ['eid'],
                    ];
                    if ($oddsId && $datum['key'] === 'oid') {
                        $formList[$index]['data'][$key]['disabled'] = true;
                    } elseif (isset($customerFieldMap[$customType]) && in_array($datum['key'], $customerFieldMap[$customType])) {
                        $formList[$index]['data'][$key]['disabled'] = true;
                    }
                }
                // 填充关联数据的值
                if (! empty($info) && ! empty($datum['link_field'])) {
                    $formList[$index]['data'][$key]['value'] = $info[$datum['link_field']] ?? '';
                }
                if ($customType === CustomEnum::CONTRACT && $datum['key'] === 'outbound_warehouse_id') {
                    $formList[$index]['data'][$key]['value'] = $options[0]['value'] ?? '';
                }
                // 追加隐藏关联ID字段（最后一个项）
                if (! empty($info) && $key === count($item['data']) - 1) {
                    $formList[$index]['data'][count($item['data'])] = [
                        'id'         => time(),
                        'key'        => 'link_id',
                        'input_type' => 'hidden',
                        'value'      => $linkId,
                        'type'       => 'text',
                    ];
                }
            }
            // 重置数组索引（避免unset后索引混乱）
            $formList[$index]['data'] = array_values($formList[$index]['data']);
        }
        return $formList;
    }

    /**
     * 获取字段状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFieldStatus(array|string $field): array
    {
        return $this->dataDao->column(['key' => $field, 'status' => 1], 'status', 'key');
    }

    /**
     * 自定义字段回显数据.
     * @param string $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTypeDataList(array $where, array $field = ['id', 'title', 'sort', 'types', 'status'], $sort = 'sort'): array
    {
        return $this->dao->getList($where, $field, 0, 0, $sort, ['data' => function ($query) {
            $query->select(['id', 'cate_id', 'key', 'key_name', 'type', 'input_type', 'dict_ident']);
        }]);
    }

    /**
     * 提取字段.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRequestFields(int $types): array
    {
        $fields = [];
        $list   = $this->getCustomDataByTypes($types);
        foreach ($list as $item) {
            $fields[] = [$item['key'], $item['value']];
        }
        return $fields;
    }

    /**
     * 获取业务类型.
     */
    public function getCustomServiceByTypes(int $types): null|CustomerService|LeadService|LiaisonService|OpportunityService|OrderService|ProductService
    {
        $service = match ($types) {
            CustomEnum::CUSTOMER => CustomerService::class,
            CustomEnum::CONTRACT => OrderService::class,
            CustomEnum::LIAISON  => LiaisonService::class,
            CustomEnum::CLUE     => LeadService::class,
            CustomEnum::PRODUCT  => ProductService::class,
            CustomEnum::ODDS     => OpportunityService::class,
            default              => null
        };
        if (! $service) {
            throw $this->exception('业务类型异常');
        }
        return app($service);
    }

    /**
     * 字段数据验证
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function fieldValueCheck(array $data, int $types, int $id, array $list = [], int $force = 0): void
    {
        if (! $list) {
            $list = $this->getFormDataList($types);
        }

        $tz      = config('app.timezone');
        $service = $this->getCustomServiceByTypes($types);
        foreach ($list as $item) {
            $min       = $item['min'] ?? '';
            $max       = $item['max'] ?? '';
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            $required  = (int) ($item['required'] ?? 0) === 1;

            foreach ($data as $key => $value) {
                if ($item['key'] == $key) {
                    $len = 0;
                    if ($inputType == 'input') {
                        $len = mb_strlen((string) $value);
                        if ($required && ! $len) {
                            throw $this->exception('请输入' . $item['key_name']);
                        }

                        if (empty($value)) {
                            continue;
                        }

                        $text = $type == 'number' ? '数字' : '字';
                        if ($len > $max) {
                            throw $this->exception(sprintf('%s最多输入%d个%s', $item['key_name'], $max, $text));
                        }
                        if ($len < $min) {
                            throw $this->exception(sprintf('%s最少输入%d个%s', $item['key_name'], $min, $text));
                        }
                    }

                    if (in_array($inputType, ['select', 'checked', 'file'])) {
                        if ($type != 'single') {
                            is_array($value) && $len = count($value);
                            if ($required && (! $value || ! $len)) {
                                throw $this->exception('请选择' . $item['key_name']);
                            }
                        } else {
                            $len = 1;
                            if ($required && ! $value) {
                                throw $this->exception('请选择' . $item['key_name']);
                            }
                        }

                        if (empty($value)) {
                            continue;
                        }

                        if ($len > $max) {
                            throw $this->exception(sprintf('%s最多选择数量%d', $item['key_name'], $max));
                        }
                        if ($len < $min) {
                            throw $this->exception(sprintf('%s最少选择数量%d', $item['key_name'], $min));
                        }
                    }

                    if ($inputType == 'radio') {
                        if ($required && $value === '') {
                            throw $this->exception('请选择' . $item['key_name']);
                        }
                    }

                    if ($inputType == 'date') {
                        if ($required && $value === '') {
                            throw $this->exception('请选择' . $item['key_name']);
                        }

                        if (empty($value)) {
                            continue;
                        }

                        if ($max && Carbon::parse($value, $tz)->gt(Carbon::parse($max, $tz))) {
                            throw $this->exception(sprintf('%s不能晚于%s', $item['key_name'], $value));
                        }

                        if ($min && Carbon::parse($value, $tz)->lt(Carbon::parse($min, $tz))) {
                            throw $this->exception(sprintf('%s不能早于%s', $item['key_name'], $value));
                        }
                    }

                    if ($inputType == 'oawangeditor') {
                        if ($required && $value === '') {
                            throw $this->exception('请输入' . $item['key_name']);
                        }

                        if (empty($value)) {
                            continue;
                        }

                        $len = mb_strlen((string) $value);
                        if ($len > 65535) {
                            throw $this->exception(sprintf('最多输入65535个字'));
                        }

                        if ($len < $min) {
                            throw $this->exception(sprintf('最少输入字数%d', $min));
                        }
                    }

                    if ($item['uniqued']) {
                        if ($inputType == 'select' && $type == 'single') {
                            $value = intval(is_array($value) ? ($value[0] ?? 0) : $value);
                        } elseif ($inputType == 'radio') {
                            $value = (int) $value;
                        } elseif (! in_array($inputType, ['date', 'input', 'oawangeditor', 'datetime'])) {
                            sort($value);
                            $value = json_encode($value);
                        }

                        $where = [$key => $value];
                        if ($id) {
                            $where['not_id'] = $id;
                        }
                        if ($service->exists($where)) {
                            throw $this->exception($item['key_name'] . '已存在');
                        }
                    }

                    // save customer notice
                    if (! $force && in_array($key, ['customer_name', 'customer_tel'])) {
                        $where = [$key => $value];
                        if ($id) {
                            $where['not_id'] = $id;
                        }
                        if ($service->exists($where)) {
                            $msg = $item['key_name'] . '已存在，是否继续' . ($id ? '修改客户' : '添加客户');
                            throw $this->exception($msg, CodeEnum::VERIFY_CODE);
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取表单写入值.
     *
     * @param string $fieldKey 字段 key；传入后仅对「系统字段」做 null→''（自定义生成列保持 NULL）
     */
    public function getFormValue(string $type, string $inputType, mixed $value, string $fieldKey = ''): mixed
    {
        // 处理人员选择内容
        if ($inputType === 'member') {
            if ($type === 'singlemember') {
                return is_array($value) ? end($value) : $value;
            }
            if (! $value) {
                return null;
            }
            return json_encode(is_array($value) ? $value : [$value], JSON_UNESCAPED_UNICODE);
        }
        if ($inputType === 'oawangeditor') {
            return htmlspecialchars(preg_replace('/<p>\s*<br\s*\/?>\s*<\/p>/i', '', htmlspecialchars_decode($value ?? '')));
        }
        if ($type == 'single' && ! is_array($value)) {
            return is_string($value) ? $this->restoreEscapedData($value) : $value;
        }
        if (($inputType == 'date' || $inputType == 'datetime') && empty($value)) {
            return null;
        }
        // 系统文本列多为 NOT NULL DEFAULT ''（如 liaison_job）；自定义列按 reloadCustomTableField 建成可空，保留 null
        if (
            $fieldKey !== ''
            && ! $this->isGeneratedCustomColumn($fieldKey)
            && in_array($inputType, ['input', 'radio'], true)
            && $value === null
            && $type !== 'number'
        ) {
            return '';
        }
        if (! in_array($inputType, ['date', 'datetime', 'input', 'radio'])) {
            if (! is_array($value)) {
                // 尝试解析字符串值，返回数组或null，由模型访问器自动转为JSON
                if (is_string($value) && $value !== '') {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $value = $decoded;
                    } else {
                        $value = null;
                    }
                } else {
                    $value = null;
                }
            }
        }
        return $value;
    }

    /**
     * 获取自定义表单导出数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getExportField(int $types): array
    {
        $where  = ['types' => $types];
        $field  = ['id', 'title', 'sort', 'types', 'status'];
        $list   = $this->dao->getList($where, $field, 0, 0, 'sort', ['data' => fn ($q) => $q->where('status', 1)->whereNotIn('type', ['file', 'oaWangeditor', 'images'])]);
        $fields = [];
        foreach ($list as $item) {
            $fields = array_merge($fields, $item['data']);
        }
        return $fields;
    }

    /**
     * 批量保存转换.
     * @return true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveConvert(array $data, string $customType, int $linkType = 4)
    {
        $types = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => 1,
            ViewSearchEnum::VIEW_CONTRACT => 2,
            ViewSearchEnum::VIEW_LIAISON  => 3,
            ViewSearchEnum::VIEW_CLUE     => 4,
            ViewSearchEnum::VIEW_ODDS     => 5,
        };
        $cateId = $this->dao->column(['types' => $types], 'id');
        $this->dataDao->update(['cate_id' => $cateId], [
            'link_type'  => 0,
            'link_field' => '',
        ]);
        foreach ($data as $value) {
            if (! $value['related']) {
                continue;
            }
            $this->dataDao->update(['key' => $value['related'], 'cate_id' => $cateId], [
                'link_type'  => $linkType,
                'link_field' => $value['field'],
            ]);
        }
        return true;
    }

    /**
     * 获取导入模板
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws IOException
     * @throws NotFoundExceptionInterface
     * @throws WriterNotOpenedException
     * @throws \ReflectionException
     */
    public function getImportTemp(int $customer): string
    {
        return Cache::tags([CacheEnum::TAG_CUSTOMER])->remember(md5('import_temp_' . $customer), 86400, function () use ($customer) {
            $formData = collect($this->getFormDataList($customer) ?? [])
                ->filter(fn ($item) => ! in_array($item['input_type'], ['file', 'images', 'oaWangeditor']) && ! in_array($item['key'], ['customer_followed', 'customer_status', 'followed', 'contract_followed']));
            $title = match ($customer) {
                CustomEnum::CUSTOMER => '客户',
                CustomEnum::CONTRACT => '订单',
                CustomEnum::LIAISON  => '联系人',
                CustomEnum::CLUE     => '线索',
                CustomEnum::ODDS     => '商机',
                default              => ''
            };
            $filePath = public_path('exports/' . $title . '导入模板(' . now()->format('YmdHis') . ').xlsx');
            $writer   = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($filePath);
            $writer->addRow(WriterEntityFactory::createRowFromArray($formData->pluck('key_name')->all()));
            //            $writer->addRow(WriterEntityFactory::createRowFromArray($formData->pluck('value')->all()));
            $writer->close();
            return link_file('/exports/' . $title . '导入模板(' . now()->format('YmdHis') . ').xlsx');
        });
    }

    /**
     * 获取枚举字段.
     * @param mixed $viewTypes
     */
    public function getEnumField(int $viewTypes): array
    {
        return match ($viewTypes) {
            CustomEnum::CUSTOMER => collect(CustomerEnum::CUSTOMER_SEARCH_FIELD)->filter(fn ($item) => isset($item['dict_ident']))->all(),
            CustomEnum::CONTRACT => collect(ContractEnum::CONTRACT_SEARCH_FIELD)->filter(fn ($item) => isset($item['dict_ident']))->all(),
            CustomEnum::LIAISON  => collect(LiaisonEnum::LIAISON_SEARCH_FIELD)->filter(fn ($item) => isset($item['dict_ident']))->all(),
            CustomEnum::CLUE     => collect(ClueEnum::CLUE_SEARCH_FIELD)->filter(fn ($item) => isset($item['dict_ident']))->all(),
            CustomEnum::ODDS     => collect(OddsEnum::ODDS_SEARCH_FIELD)->filter(fn ($item) => isset($item['dict_ident']))->all(),
            default              => []
        };
    }

    protected function restoreEscapedData(string $data): string
    {
        $prev    = $data;
        $changed = true;
        // 循环执行反转义，直到数据不再变化（无冗余转义）
        while ($changed) {
            // 步骤1：HTML实体反转义（处理 &amp; → &、&quot; → " 等）
            $current = htmlspecialchars_decode($prev, ENT_QUOTES);
            // 步骤2：JSON反转义（处理 \\ → \、\" → " 等）
            $current = json_decode('"' . $current . '"', true) ?? $current;
            // 检查是否还有变化
            if ($current === $prev) {
                $changed = false;
            } else {
                $prev = $current;
            }
        }
        return str_replace('""', '', $prev);
    }

    private function appendProductListField(iterable $list, int $types): void
    {
        if (! in_array($types, [CustomEnum::CONTRACT, CustomEnum::ODDS], true)) {
            return;
        }

        foreach ($list as $item) {
            if (($item->ident ?? '') !== 'product') {
                continue;
            }
            if ($item->data->contains('key', 'products')) {
                return;
            }

            $field = new FormData();
            $field->setRawAttributes([
                'id'            => 0,
                'key'           => 'products',
                'key_name'      => '产品列表',
                'type'          => 'multiple',
                'input_type'    => 'product',
                'cate_id'       => $item->id,
                'param'         => '',
                'decimal_place' => 0,
                'upload_type'   => 0,
                'required'      => 0,
                'placeholder'   => '',
                'max'           => 0,
                'min'           => 0,
                'dict_ident'    => '',
                'value'         => '',
                'uniqued'       => 0,
                'desc'          => '',
                'link_type'     => 0,
                'link_field'    => '',
                'sort'          => 0,
                'status'        => 1,
                'enable_delete' => 0,
            ]);
            $item->data->push($field);
            return;
        }
    }

    /**
     * 文件/图片字段也以 JSON 存储，但内容通常是对象，不适合 MySQL 多值索引.
     * @param mixed $field
     */
    private function isJsonArrayIndexableCustomField($field, ?int $dictLevel = null): bool
    {
        $inputType = strtolower((string) ($field['input_type'] ?? ''));

        if (in_array($inputType, ['file', 'images'], true)) {
            return false;
        }

        return $this->isJsonCustomField($field, $dictLevel);
    }

    private function addJsonArrayIndex(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column) || $this->columnType($table, $column) !== 'json') {
            return;
        }

        $index = 'idx_' . $column;
        if ($this->indexExists($table, $index) || ! $this->hasOnlyJsonScalarArrays($table, $column)) {
            return;
        }

        $fullTable = DB::getTablePrefix() . $table;
        $column    = $this->quoteIdentifier($column);
        DB::statement("ALTER TABLE `{$fullTable}` ADD INDEX `{$index}` ((CAST({$column} AS CHAR(20) ARRAY)))");
    }

    /**
     * 将业务表中仍为 NOT NULL 的自定义字段列改为可空.
     *
     * 仅处理生成式自定义列（c+hex / 历史 hex / l+hex），不改动系统字段；
     * 并尽量保留原 DEFAULT，避免 MODIFY 丢失默认值.
     *
     * @param array<int, string> $fieldKeys 当前表单字段（可选，用于兼容调用方）
     */
    private function ensureCustomColumnsNullable(string $table, array $fieldKeys = []): void
    {
        $fullTable = DB::getTablePrefix() . $table;
        $columns   = DB::select("SHOW FULL COLUMNS FROM `{$fullTable}`");
        if ($columns === []) {
            return;
        }

        foreach ($columns as $column) {
            $fieldKey = (string) ($column->Field ?? '');
            if ($fieldKey === '' || ! $this->isGeneratedCustomColumn($fieldKey)) {
                continue;
            }
            if (strtoupper((string) ($column->Null ?? '')) === 'YES') {
                continue;
            }
            if (stripos((string) ($column->Extra ?? ''), 'auto_increment') !== false) {
                continue;
            }

            $type        = (string) ($column->Type ?? '');
            $comment     = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) ($column->Comment ?? ''));
            $defaultSql  = $this->buildColumnDefaultClause($column);
            $quoted      = $this->quoteIdentifier($fieldKey);
            DB::statement("ALTER TABLE `{$fullTable}` MODIFY COLUMN {$quoted} {$type} NULL{$defaultSql} COMMENT '{$comment}'");
        }
    }

    /**
     * 是否为自定义表单生成的列名（含历史规则）.
     */
    private function isGeneratedCustomColumn(string $fieldKey): bool
    {
        return (bool) preg_match('/^(c[0-9a-f]{7}|[0-9a-f]{8}|l[0-9a-f]{6,})$/i', $fieldKey);
    }

    /**
     * 根据 SHOW FULL COLUMNS 结果拼装 DEFAULT 子句.
     */
    private function buildColumnDefaultClause(object $column): string
    {
        if (! property_exists($column, 'Default') || $column->Default === null) {
            return '';
        }

        $default = (string) $column->Default;
        if (strcasecmp($default, 'CURRENT_TIMESTAMP') === 0) {
            return ' DEFAULT CURRENT_TIMESTAMP';
        }

        return ' DEFAULT ' . DB::getPdo()->quote($default);
    }

    private function hasOnlyJsonScalarArrays(string $table, string $column): bool
    {
        $fullTable = DB::getTablePrefix() . $table;
        $column    = 't.' . $this->quoteIdentifier($column);

        $invalid = DB::selectOne(
            "SELECT 1 FROM `{$fullTable}` AS t
            WHERE {$column} IS NOT NULL
              AND (
                JSON_TYPE({$column}) <> 'ARRAY'
                OR EXISTS (
                    SELECT 1
                    FROM JSON_TABLE({$column}, '$[*]' COLUMNS (`item` JSON PATH '$')) AS jt
                    WHERE jt.`item` IS NULL
                       OR JSON_TYPE(jt.`item`) NOT IN ('INTEGER', 'DOUBLE', 'STRING', 'BOOLEAN')
                )
              )
            LIMIT 1"
        );

        return $invalid === null;
    }

    private function indexExists(string $table, string $index): bool
    {
        $fullTable = DB::getTablePrefix() . $table;
        $index     = DB::getPdo()->quote($index);

        return ! empty(DB::select("SHOW INDEX FROM `{$fullTable}` WHERE Key_name = {$index}"));
    }

    private function supportsJsonArrayIndex(): bool
    {
        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
        if (stripos($version, 'mariadb') !== false) {
            return false;
        }

        $version = preg_replace('/[^0-9.].*$/', '', $version) ?: '0.0.0';
        return version_compare($version, '8.0.17', '>=');
    }

    private function columnType(string $table, string $column): string
    {
        $fullTable = DB::getTablePrefix() . $table;
        $column    = DB::getPdo()->quote($column);
        $result    = DB::selectOne("SHOW COLUMNS FROM `{$fullTable}` LIKE {$column}");

        return strtolower((string) ($result->Type ?? ''));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
