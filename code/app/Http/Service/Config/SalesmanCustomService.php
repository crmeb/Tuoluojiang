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
use App\Constants\CustomEnum\ClueEnum;
use App\Constants\CustomEnum\ContractEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\CustomEnum\LiaisonEnum;
use App\Constants\CustomEnum\OddsEnum;
use App\Constants\CustomEnum\ProductEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Dao\Config\SalesmanCustomDao;
use App\Http\Service\Customer\ProductCategoryService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 业务自定义数据
 * Class SalesmanCustomService.
 */
class SalesmanCustomService extends BaseService
{
    public function __construct(SalesmanCustomDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户自定义表单.
     */
    public function salesmanCustomFullField(string $customType): array
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => [
                array_merge(CustomerEnum::CUSTOMER_LIST_FIELD, CustomerEnum::CUSTOMER_VIEWER_LIST_FIELD),
                array_merge(CustomerEnum::CUSTOMER_SEARCH_FIELD, CustomerEnum::CUSTOMER_VIEWER_SEARCH_FIELD),
            ],
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => [
                array_merge(CustomerEnum::CUSTOMER_LIST_FIELD, CustomerEnum::CUSTOMER_HEIGHT_SEAS_LIST_FIELD),
                array_merge(CustomerEnum::CUSTOMER_SEARCH_FIELD, CustomerEnum::CUSTOMER_HEIGHT_SEAS_SEARCH_FIELD),
            ],
            ViewSearchEnum::VIEW_CONTRACT => [
                array_merge(ContractEnum::CONTRACT_LIST_FIELD, ContractEnum::CONTRACT_VIEWER_LIST_FIELD),
                array_merge(ContractEnum::CONTRACT_SEARCH_FIELD, ContractEnum::CONTRACT_VIEWER_SEARCH_FIELD),
            ],
            ViewSearchEnum::VIEW_CLUE => [
                array_merge(ClueEnum::CLUE_LIST_FIELD, ClueEnum::CLUE_VIEWER_LIST_FIELD),
                array_merge(ClueEnum::CLUE_SEARCH_FIELD, ClueEnum::CLUE_VIEWER_SEARCH_FIELD),
            ],
            ViewSearchEnum::VIEW_CLUE_SEAS => [
                ClueEnum::CLUE_SEAS_LIST_FIELD,
                array_merge(ClueEnum::CLUE_SEARCH_FIELD, ClueEnum::CLUE_HEIGHT_SEAS_SEARCH_FIELD),
            ],
            ViewSearchEnum::VIEW_PRODUCT => [ProductEnum::PRODUCT_LIST_FIELD, ProductEnum::PRODUCT_SEARCH_FIELD],
            ViewSearchEnum::VIEW_ODDS    => [OddsEnum::ODDS_LIST_FIELD, OddsEnum::ODDS_SEARCH_FIELD],
            ViewSearchEnum::VIEW_LIAISON => [LiaisonEnum::LIAISON_LIST_FIELD, LiaisonEnum::LIAISON_SEARCH_FIELD],
            default                      => [[], []]
        };
    }

    /**
     * 获取业务列表默认数据.
     */
    public function getListDefaultFieldByCustomType(string $customType): array
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER      => CustomerEnum::CUSTOMER_CHARGE_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => CustomerEnum::CUSTOMER_HEIGHT_SEAS_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CONTRACT      => ContractEnum::CONTRACT_VIEWER_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CLUE          => ClueEnum::CLUE_VIEWER_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CLUE_SEAS     => ClueEnum::CLUE_SEAS_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_ODDS          => OddsEnum::ODDS_VIEWER_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_PRODUCT       => ProductEnum::PRODUCT_VIEWER_LIST_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_LIAISON       => LiaisonEnum::LIAISON_VIEWER_LIST_DEFAULT_FIELD,
            default                            => []
        };
    }

    /**
     * 获取业务搜索默认数据.
     */
    public function getSearchDefaultFieldByCustomType(string $customType): array
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER      => CustomerEnum::CUSTOMER_VIEWER_SEARCH_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => CustomerEnum::CUSTOMER_HEIGHT_SEAS_SEARCH_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CONTRACT      => ContractEnum::CONTRACT_VIEWER_SEARCH_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS => ClueEnum::CLUE_VIEWER_SEARCH_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_ODDS    => OddsEnum::ODDS_VIEWER_SEARCH_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_PRODUCT => ProductEnum::PRODUCT_VIEWER_SEARCH_DEFAULT_FIELD,
            ViewSearchEnum::VIEW_LIAISON => LiaisonEnum::LIAISON_VIEWER_SEARCH_DEFAULT_FIELD,
            default                      => []
        };
    }

    /**
     * 默认数据.
     */
    public function getDefaultField(string $customType, string $selectType): array
    {
        return match ($selectType) {
            CustomEnum::LIST_SELECT   => $this->getListDefaultFieldByCustomType($customType),
            CustomEnum::SEARCH_SELECT => $this->getSearchDefaultFieldByCustomType($customType),
            default                   => []
        };
    }

    /**
     * 业务字段.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function salesmanCustomField(int $uid, string $customType): array
    {
        $cacheVersion = 'system_no_v4';
        $result = Cache::tags([CacheEnum::TAG_CUSTOMER])->remember(
            md5($uid . '_' . $customType . '_' . $cacheVersion),
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($uid, $customType) {
                // 获取自定义字段
                $fields = app()->get(FormService::class)->getCustomDataByTypes(
                    $this->getTypesByCustomerType($customType),
                    ['key as field', 'key_name as name', 'type', 'input_type', 'dict_ident'],
                );
                // 获取列表和搜索字段基础数据
                [$list, $search] = $this->salesmanCustomFullField($customType);
                // 合并并过滤列表字段
                $list = collect($list)->concat(in_array($customType, [ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS]) ? $this->filterFields($fields, ['file', 'oawangeditor', 'images'], ['clue_id']) : $this->filterFields($fields, ['images', 'file', 'oawangeditor']))->all();
                $searchFilterFields = ['contract_followed', 'customer_followed'];
                if (in_array($customType, [ViewSearchEnum::VIEW_CONTRACT, ViewSearchEnum::VIEW_ODDS])) {
                    $searchFilterFields[] = 'products';
                }
                // 合并并过滤搜索字段，确保唯一性
                $search = collect($search)->concat($this->filterFields($fields, ['images', 'file', 'oawangeditor'], $searchFilterFields))->unique('name')->values()->all();
                // 提取字段键名
                $listFieldKeys   = collect($list)->pluck('field')->all();
                $searchFieldKeys = collect($search)->pluck('field')->all();
                $sortFieldKeys   = collect($fields)->filter(fn ($item) => in_array($item['type'], ['number', 'date', 'datetime']))->map(fn ($item) => ['field' => $item['field'], 'name' => $item['name']])
                    ->concat([['field' => 'created_at', 'name' => '创建时间'], ['field' => 'updated_at', 'name' => '修改时间']])->values()->all();
                $sortValues = collect([['field' => 'asc', 'name' => '升序'], ['field' => 'desc', 'name' => '降序']])->values()->all();
                // 处理搜索字段特殊情况及字典数据填充
                $dictDataService = app()->get(DictDataService::class);
                $idents          = [];
                $search          = collect($search)->map(function ($item) use ($customType, $dictDataService, &$idents) {
                    switch ($customType) {
                        case ViewSearchEnum::VIEW_CONTRACT:
                            if ($item['field'] == 'contract_customer') {
                                return ['field' => $item['field'], 'name' => $item['name'], 'input_type' => 'input'];
                            }
                            break;
                        case ViewSearchEnum::VIEW_PRODUCT:
                            if ($item['field'] == 'path') {
                                $item['dict'] = app()->get(ProductCategoryService::class)->getSelect(
                                    ['status' => 1],
                                    ['id as value', 'name as label', 'pid', 'id']
                                );
                            }
                            break;
                    }
                    if (! empty($item['dict_ident'])) {
                        $idents[] = $item['dict_ident'];
                    }
                    // 根据 dict_ident 填充字典数据
                    if (! empty($item['dict_ident']) && $item['dict_ident'] !== 'area_cascade') {
                        $item['dict'] = $dictDataService->getTreeData(
                            ['type_name' => $item['dict_ident'], 'status' => 1],
                            ['name as label', 'name', 'value', 'type_name', 'pid']
                        );
                    }
                    return $item;
                })->all();
                // 处理列表选择字段
                $listSelect = $this->dao->value(['uid' => $uid, 'custom_type' => $customType . '_' . CustomEnum::LIST_SELECT], 'field_list');
                $listSelect = $listSelect ? $this->filterSelectData($listSelect, $listFieldKeys) : $this->getListDefaultFieldByCustomType($customType);
                // 处理搜索选择字段
                $searchSelect = $this->dao->value(['uid' => $uid, 'custom_type' => $customType . '_' . CustomEnum::SEARCH_SELECT], 'field_list');
                $searchSelect = $searchSelect ? $this->filterSelectData($searchSelect, $searchFieldKeys) : $this->filterSelectData($this->getSearchDefaultFieldByCustomType($customType), $searchFieldKeys);
                $dictCate     = $idents ? app(DictTypeService::class)->select(['ident' => $idents])?->toArray() : [];
                return json_encode([
                    'list'          => $list,
                    'search'        => $search,
                    'list_select'   => $listSelect,
                    'search_select' => $searchSelect,
                    'sort_field'    => $sortFieldKeys,
                    'sort_value'    => $sortValues,
                    'dict_cate'     => $dictCate,
                ], JSON_UNESCAPED_UNICODE);
            }
        );
        return json_decode($result, true);
    }

    /**
     * 过滤选择数据.
     */
    public function filterSelectData(array $data, array $listFields): array
    {
        return array_values(array_filter($data, fn ($item) => in_array($item, $listFields)));
    }

    /**
     * 保存业务自定义字段.
     * @param mixed $customType
     * @throws BindingResolutionException
     */
    public function saveSalesmanCustomField(int $uid, string $customType, string $selectType, array $data): mixed
    {
        if (! in_array($selectType, [CustomEnum::LIST_SELECT, CustomEnum::SEARCH_SELECT])) {
            throw $this->exception('业务类型错误');
        }
        $data = array_unique($data);
        if (count($data) < 3) {
            throw $this->exception('至少选中3个字段');
        }
        $res = $this->transaction(function () use ($uid, $customType, $selectType, $data) {
            $where = ['uid' => $uid, 'custom_type' => $customType . '_' . $selectType];
            if ($this->dao->exists(['uid' => $uid, 'custom_type' => $customType . '_' . $selectType])) {
                return $this->dao->update($where, ['field_list' => $data]);
            }
            return $this->dao->create(array_merge($where, ['field_list' => $data]));
        });
        return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 获取业务类型获取表单类型.
     */
    public function getTypesByCustomerType(string $customType): int
    {
        return match (true) {
            in_array($customType, ['customer', 'customer_seas']) => CustomEnum::CUSTOMER,
            $customType == 'contract'                            => CustomEnum::CONTRACT,
            $customType == 'liaison'                             => CustomEnum::LIAISON,
            in_array($customType, ['clue', 'clue_seas'])         => CustomEnum::CLUE,
            $customType == 'odds'                                => CustomEnum::ODDS,
            $customType == 'product'                             => CustomEnum::PRODUCT,
            default                                              => 0
        };
    }

    /**
     * 获取表单类型获取业务类型.
     */
    public function getCustomTypesByTypes(int $types): array
    {
        return match ($types) {
            CustomEnum::CUSTOMER => CustomerEnum::CUSTOMER_TYPE,
            CustomEnum::CONTRACT => ContractEnum::CONTRACT_TYPE,
            CustomEnum::LIAISON  => LiaisonEnum::LIAISON_TYPE,
            default              => []
        };
    }

    /**
     * 移除业务自定义数据.
     * @throws BindingResolutionException
     */
    public function forgetCustomTableField(int $types): void
    {
        $customTypes = [];
        foreach ($this->getCustomTypesByTypes($types) as $item) {
            $customTypes[] = $item . CustomEnum::LIST_SELECT;
            $customTypes[] = $item . CustomEnum::SEARCH_SELECT;
        }

        $customTypes && $this->dao->update(['custom_type' => $customTypes], ['field_list' => []]);
    }

    /**
     * 获取业务自定义数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCustomField(int $uid, string $customType, string $selectType): array
    {
        if (! in_array($selectType, [CustomEnum::LIST_SELECT, CustomEnum::SEARCH_SELECT])) {
            throw $this->exception('业务类型错误');
        }
        $field = $this->dao->get(['uid' => $uid, 'custom_type' => $customType . '_' . CustomEnum::LIST_SELECT], ['field_list']);
        if ($field) {
            return array_merge($field->field_list, $customType == ViewSearchEnum::VIEW_CLUE ? ['customer'] : ['salesman']);
        }
        return $this->getDefaultField($customType, $selectType);
    }

    /**
     * 过滤指定字段.
     */
    public function filterFields(array $fields, array $filterTypes = [], array $filterFields = []): array
    {
        return collect($fields)->filter(function ($value) use ($filterTypes, $filterFields) {
            // 检查输入类型是否需要过滤
            $isTypeValid = empty($filterTypes) || ! in_array(strtolower($value['input_type'] ?? ''), $filterTypes);
            // 检查字段名是否需要过滤
            $isFieldValid = empty($filterFields) || ! in_array(strtolower($value['field'] ?? ''), $filterFields);
            return $isTypeValid && $isFieldValid;
        })->values()->all();
    }
}
