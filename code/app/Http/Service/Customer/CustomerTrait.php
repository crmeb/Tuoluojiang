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

use App\Constants\AttachEnum;
use App\Constants\CacheEnum;
use App\Constants\CodeEnum;
use App\Constants\CommonEnum;
use App\Constants\CustomEnum\ClueEnum;
use App\Constants\CustomEnum\ContractEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\CustomEnum\LiaisonEnum;
use App\Constants\CustomEnum\OddsEnum;
use App\Constants\CustomEnum\ProductEnum;
use App\Constants\DataPermissionLevelEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Constants\UserAgentEnum;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Config\SalesmanCustomService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\ImportExport\CustomerExportService;
use App\Http\Service\ImportExport\CustomerImportService;
use App\Http\Service\ImportExport\RecordService as ImportRecordService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\Warehouse\WarehouseStockService;
use App\Http\Service\Work\WorkClientService;
use App\Jobs\Customer\MergeCustomerJob;
use App\Jobs\Work\CustomerLabelToWorkJob;
use App\Http\Model\BaseModel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户服务公共基类.
 */
trait CustomerTrait
{
    /**
     * 全部.
     */
    protected string $all = 'all';

    /**
     * 仅本人.
     */
    protected string $self = 'self';

    /**
     * 本部门(含无限下级).
     */
    protected string $department = 'dep';

    /**
     * 直属下级.
     */
    protected string $subordinate = 'sub';

    /**
     * 本人+直属下级.
     */
    protected string $team = 'team';

    protected string $platform = UserAgentEnum::ADMIN_AGENT;

    /**
     * 系统自动生成且不允许手动修改的字段.
     */
    protected function getSystemReadonlyFields(string $customType): array
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CONTRACT => [
                [
                    'key'      => 'contract_no',
                    'key_name' => '订单编号',
                ],
            ],
            ViewSearchEnum::VIEW_ODDS => [
                [
                    'key'      => 'odds_no',
                    'key_name' => '商机编号',
                ],
            ],
            default => [],
        };
    }

    protected function getSystemReadonlyFieldKeys(string $customType): array
    {
        return collect($this->getSystemReadonlyFields($customType))->pluck('key')->all();
    }

    public function getPlatform(): string
    {
        if (! $this->platform) {
            $this->platform = UserAgentEnum::ADMIN_AGENT;
        }
        return $this->platform;
    }

    /**
     * 获取列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getListByType(array $where, int $uid = 0, array $uniField = []): array
    {
        $customType     = $where['types'];
        $viewTypes      = $this->getCustomType($customType);
        [$page, $limit] = $this->getPageValue();
        $lastId          = (int) request()->input('last_id', 0);
        $skipCountInput  = request()->input('skip_count', null);
        $hasSkipCount    = request()->has('skip_count');
        $autoSkipCount   = ! $hasSkipCount && $this->shouldSkipExpensiveListCount($where);
        $skipCount       = $hasSkipCount
            ? filter_var($skipCountInput, FILTER_VALIDATE_BOOLEAN)
            : $autoSkipCount;
        $isCursorPage    = $lastId > 0;
        $needsMoreProbe  = ($isCursorPage || $skipCount) && $limit > 0;
        $queryLimit      = $needsMoreProbe ? $limit + 1 : $limit;
        // 增加系统视图查询条件
        $where = $this->viewSearchWhere($where, $uid);
        // 表单字段处理
        $formFields = collect(app(FormService::class)->getFormDataList($viewTypes, field: ['key', 'key_name', 'input_type', 'type', 'dict_ident']))
            ->filter(fn ($item) => ! in_array($item['input_type'], ['file', 'images', 'oawangeditor']))->all();
        $formFieldCollection = collect($formFields);
        $enumField           = collect(app(FormService::class)->getEnumField($viewTypes));
        $inputTypes          = $formFieldCollection->pluck('input_type', 'key')->merge($enumField->pluck('input_type', 'field'))->all();
        $types               = $formFieldCollection->pluck('type', 'key')->merge($enumField->pluck('type', 'field'))->all();
        $formFieldKeys       = $formFieldCollection->pluck('key')->merge($enumField->pluck('field'))->all();
        // 表格字段
        $tableColumns = Schema::getColumnListing($this->dao->getTable());
        // 字典字段
        $dictField = $formFieldCollection->pluck('dict_ident', 'key')->merge($enumField->pluck('dict_ident', 'field'))->all();
        // 本地字段（含系统自动生成的只读编号，避免其依赖表单配置或用户表头配置）
        $localField = array_merge(
            ['id', 'uid', 'userid', 'external_userid', 'creator_uid', 'before_uid', 'eid', 'oid', 'surplus', 'pid', 'end_date'],
            $this->getSystemReadonlyFieldKeys($customType)
        );
        // 用户自定义字段
        $customFields = app(SalesmanCustomService::class)->getCustomField($uid, $customType, CustomEnum::LIST_SELECT);
        if (! in_array('products', array_merge($customFields, $uniField), true)) {
            $formFieldKeys = array_values(array_diff($formFieldKeys, ['products']));
        }
        // 优化数据库查询字段计算逻辑
        $mergedKeys  = array_merge($formFieldKeys, $customFields);
        $searchField = array_values(array_unique(array_merge(
            array_intersect($tableColumns, $mergedKeys),
            array_intersect($tableColumns, $localField)
        )));
        // 计算其他字段
        if ($uniField) {
            $otherField = $uniField;
        } else {
            $otherField = array_diff($mergedKeys, $tableColumns);
        }
        $otherField[] = 'is_sign';
        // 数据查询
        $listQuery = $this->dao->listSearch($where, $isCursorPage ? 0 : $page, $queryLimit, uid: $uid)
            ->when($isCursorPage, fn ($query) => $query->where($this->dao->getTable() . '.id', '<', $lastId));
        $list    = $this->getLightweightListRows($listQuery, $searchField);
        $hasMore = false;
        if ($needsMoreProbe && count($list) > $limit) {
            $hasMore = true;
            $list    = array_slice($list, 0, $limit);
        }
        $dataMap = [];
        foreach (array_merge($otherField, $searchField) as $field) {
            switch ($field) {
                case 'salesman':
                case 'creator':
                case 'before_salesman':
                    if (! isset($dataMap[$field])) {
                        $dataIds = collect($list)->flatMap(function ($item) {
                            return [
                                $item['uid'] ?? null,
                                $item['creator_uid'] ?? null,
                                $item['before_uid'] ?? null,
                            ];
                        })->filter()->unique()->values()->all();
                        $dataMap['before_salesman'] = $dataMap['creator'] = $dataMap['salesman'] = $this->getAdminMapForList($dataIds);
                    }
                    break;
                case 'work_customer':
                    $dataIds                  = collect($list)->pluck('external_userid')->all();
                    $dataMap['work_customer'] = $this->getWorkCustomerMapForList($dataIds);
                    break;
                case 'customer_followed':
                case 'contract_followed':
                case 'followed':
                    if (! isset($dataMap[$field])) {
                        $dataIds                      = collect($list)->pluck('id')->all();
                        $dataMap['customer_followed'] = $dataMap['contract_followed'] = $dataMap['followed'] = $this->getSubscribeMapForList($uid, $dataIds, $viewTypes);
                    }
                    break;
                case 'liaison_tel':
                    $dataIds                = collect($list)->pluck('id')->all();
                    $dataMap['liaison_tel'] = $this->getLiaisonTelMapForList($dataIds);
                    break;
                case 'eid':
                    $dataIds        = collect($list)->pluck('eid')->all();
                    $dataMap['eid'] = $this->getCustomerNameMapForList($dataIds);
                    break;
                case 'contract_customer':
                case 'odds_customer':
                    $dataIds                      = collect($list)->pluck('eid')->all();
                    $dataMap['contract_customer'] = $dataMap['odds_customer'] = $this->getCustomerNameMapForList($dataIds);
                    break;
                case 'oid':
                    $dataIds        = collect($list)->pluck('oid')->map(fn ($value) => $this->normalizeRelationId($value))->filter()->unique()->values()->all();
                    $dataMap['oid'] = $this->getOddsNoMapForList($dataIds);
                    break;
                case 'customer_label':
                    $dataIds                   = collect($list)->pluck('customer_label')->flatMap(fn ($value) => (array) $this->decodeFieldJsonValue($value))->filter()->unique()->values()->all();
                    $dataMap['customer_label'] = $this->getCustomerLabelMapForList($dataIds);
                    break;
                case 'bill_no':
                    $dataIds            = collect($list)->pluck('id')->all();
                    $dataMap['bill_no'] = collect(app(PaymentService::class)->select(['cid' => $dataIds], ['bill_no', 'cid', 'id'])?->toArray())->groupBy('cid')->all();
                    break;
                case 'payment_time':
                    $dataIds                 = collect($list)->pluck('id')->all();
                    $dataMap['payment_time'] = app(PaymentService::class)->column(['status' => 1, 'cid' => $dataIds], 'date', 'cid');
                    break;
                case 'last_follow_time':
                    $dataIds                     = collect($list)->pluck('id')->all();
                    $dataMap['last_follow_time'] = collect(app(FollowUpService::class)->getLastFollow(['eid' => $dataIds, 'types' => 0, 'link_type' => $customType]))
                        ->pluck('created_at', 'eid')->map(fn ($carbon) => $carbon instanceof Carbon ? $carbon->toDateTimeString() : '')->all();
                    break;
                case 'un_followed_days':
                    $dataIds                     = collect($list)->pluck('id')->all();
                    $dataMap['un_followed_days'] = collect(app(FollowUpService::class)->getLastFollow(['eid' => $dataIds, 'types' => 0, 'link_type' => $customType]))
                        ->pluck('created_at', 'eid')->map(fn ($carbon) => $carbon instanceof Carbon ? $carbon->startOfDay()->diffInDays(now(), false) : '')->all();
                    break;
                case 'amount_recorded':
                    $dataIds = collect($list)->pluck('id')->all();
                    $group   = $customType == ViewSearchEnum::VIEW_CONTRACT ? 'cid' : 'eid';
                    $dataMap['amount_recorded'] = collect(app(PaymentService::class)->getBillSum([$group => $dataIds, 'status' => 1, 'types' => [0, 1]], group: $group))->pluck('total', $group)->all();
                    break;
                case 'amount_expend':
                    $dataIds = collect($list)->pluck('id')->all();
                    $group   = $customType == ViewSearchEnum::VIEW_CONTRACT ? 'cid' : 'eid';
                    $dataMap['amount_expend'] = collect(app(PaymentService::class)->getBillSum([$group => $dataIds, 'status' => 1, 'types' => 2], group: $group))->pluck('total', $group)->all();
                    break;
                case 'invoiced_amount':
                    $dataIds                    = collect($list)->pluck('id')->all();
                    $dataMap['invoiced_amount'] = collect(app(InvoiceService::class)->getInvoiceNum(['eid' => $dataIds, 'status' => [1, 3, 5, 6]]))->pluck('total', 'eid')->all();
                    break;
                case 'invoice_num':
                    $dataIds                = collect($list)->pluck('id')->all();
                    $dataMap['invoice_num'] = collect(app(InvoiceService::class)->getInvoiceNum(['eid' => $dataIds, 'status' => [1, 3, 5, 6]], field: 'eid, COUNT(id) as count'))->pluck('count', 'eid')->all();
                    break;
                case 'contract_num':
                    $dataIds                 = collect($list)->pluck('id')->all();
                    $dataMap['contract_num'] = collect(app(OrderService::class)->getContractNum(['eid' => $dataIds]))->pluck('count', 'eid')->all();
                    break;
                case 'attachment_num':
                    $dataIds                   = collect($list)->pluck('id')->all();
                    $dataMap['attachment_num'] = collect(app(AttachService::class)->getAttachNum(['relation_id' => $dataIds, 'relation_type' => [2, 3, 4, 5, 6]]))->pluck('count', 'relation_id')->all();
                    break;
                case 'return_reason':
                    $dataIds                  = collect($list)->pluck('id')->all();
                    $dataMap['return_reason'] = collect(app(RecordService::class)->getLastRecord(['eid' => $dataIds, 'type' => $viewTypes, 'link_type' => str_replace('_seas', '', $customType)]))->pluck('reason', 'eid')->all();
                    break;
                case 'path':
                    $dataIds         = collect($list)->pluck('pid')->all();
                    $dataMap['path'] = app(ProductCategoryService::class)->column(['id' => $dataIds], 'name', 'id');
                    break;
                case 'total_amount':
                    if ($customType == ViewSearchEnum::VIEW_ODDS) {
                        $dataIds                 = collect($list)->pluck('id')->all();
                        $dataMap['total_amount'] = $this->getOddsProductTotalAmountMapForList($dataIds);
                        break;
                    }
                    $dataMap['total_amount'] = collect($list)->pluck('product', 'id')->all();
                    break;
                case 'products':
                    if (in_array($customType, [ViewSearchEnum::VIEW_ODDS, ViewSearchEnum::VIEW_CONTRACT])) {
                        $dataIds             = collect($list)->pluck('id')->all();
                        $dataMap['products'] = $this->getProductsMapForList(
                            $dataIds,
                            $customType == ViewSearchEnum::VIEW_CONTRACT ? CustomEnum::CONTRACT : CustomEnum::ODDS
                        );
                    }
                    break;
                case 'customer':
                    $dataIds             = collect($list)->pluck('external_userid')->filter()->unique()->all();
                    $dataMap['customer'] = $this->getCustomerNameMapByExternalUseridForList($dataIds);
                    break;
                case 'is_sign':
                    $dataIds            = collect($list)->pluck('id')->all();
                    $dataMap['is_sign'] = $this->getSignedRelationIdsForList($viewTypes, $dataIds);
                    break;
                case 'fail_days':
                    $dataMap['fail_days'] = collect($list)->map(function ($item) {
                        $item['fail_days'] = '';
                        if ($item['end_date'] && Carbon::parse($item['end_date'])->isAfter(now())) {
                            $item['fail_days'] = Carbon::parse($item['end_date'])->diffInDays(now()->startOfDay()) . '天';
                        }
                        return $item;
                    })->pluck('fail_days', 'id')->all();
                    break;
            }
        }
        // 数据处理（保持map逻辑，优化内部数组操作）
        $data = collect($list)->map(function ($value) use ($dictField, $inputTypes, $types, $localField, $otherField, $dataMap, $customType) {
            // 补充其他字段默认值
            collect($otherField)->each(function ($val) use (&$value) {
                $value[$val] = $value[$val] ?? '';
            });
            // 字段值处理
            foreach ($value as $key => &$item) {
                if ((! in_array($key, $localField) && isset($inputTypes[$key]) && $key != 'followed') || (isset($inputTypes[$key]) && $key == 'contract_category')) {
                    $inputType = strtolower($inputTypes[$key]);
                    $type      = strtolower($types[$key]);
                    $item      = $this->handleFieldValue($inputType, $type, $item);
                    if (in_array($key, $dictField) || (in_array($key, array_keys($dictField)) && $dictField[$key])) {
                        $item = $this->handleDictValue($dictField[$key], $item, $type, $inputType);
                    }
                }
                // 字段匹配处理
                $item = match ($key) {
                    'customer_label' => $item ? collect((array) $item)->map(fn ($id) => $dataMap[$key][$id] ?? null)->filter()->values()->all() : [],
                    'liaison_tel'    => $customType == ViewSearchEnum::VIEW_LIAISON ? $item : $dataMap[$key][$value['id']] ?? [],
                    'un_followed_days', 'last_follow_time', 'return_reason', 'bill_no', 'fail_days' => $dataMap[$key][$value['id']] ?? '',
                    'amount_recorded', 'amount_expend', 'invoiced_amount' => $dataMap[$key][$value['id']] ?? '0.00',
                    'contract_num', 'invoice_num', 'attachment_num', 'customer_followed', 'contract_followed', 'followed' => $dataMap[$key][$value['id']] ?? 0,
                    'salesman'        => $dataMap[$key][$value['uid']] ?? [],
                    'creator'         => $dataMap[$key][$value['creator_uid']] ?? [],
                    'before_salesman' => $dataMap[$key][$value['before_uid']] ?? [],
                    'work_customer'   => $dataMap[$key][$value['external_userid']] ?? [],
                    'contract_customer', 'odds_customer' => $dataMap[$key][$value['eid']] ?? '',
                    'oid'            => $dataMap[$key][$this->normalizeRelationId($value['oid'] ?? 0)] ?? '',
                    'eid'            => $customType == ViewSearchEnum::VIEW_LIAISON ? ($dataMap[$key][$value['eid']] ?? '') : $value['eid'],
                    'payment_status' => bccomp($value['surplus'], '0', 2) === 0 ? 1 : 0,
                    'payment_time'   => $dataMap[$key][$value['id']] ?? null,
                    'path'           => $dataMap[$key][$value['pid']] ?? '',
                    'customer'       => (bool) ($dataMap[$key][$value['external_userid']] ?? ''),
                    'is_sign'        => in_array($value['id'], $dataMap[$key]),
                    'products'       => $dataMap[$key][$value['id']] ?? [],
                    'total_amount'   => $customType == ViewSearchEnum::VIEW_ODDS
                        ? ($dataMap[$key][$value['id']] ?? '0.00')
                        : number_format(collect($dataMap[$key][$value['id']] ?? [])->pluck('total_price')->sum(), 2, '.', ''),
                    default          => $item
                };
            }
            return $value;
        })->all();
        if ($customType === ViewSearchEnum::VIEW_CLUE_SEAS) {
            $data = collect($data)->map(function ($row) {
                if (array_key_exists('phone', $row)) {
                    $row['phone'] = mask_phone(is_scalar($row['phone']) || $row['phone'] === null ? (string) ($row['phone'] ?? '') : '');
                }
                return $row;
            })->all();
        }
        $count  = $skipCount ? $this->getSkippedCountValue($autoSkipCount, $page, $limit, count($data), $hasMore) : $this->dao->listSearch($where, uid: $uid)->count();
        $other  = [];
        if ($isCursorPage || $skipCount) {
            $nextId = (int) (collect($data)->last()['id'] ?? 0);
            $other  = [
                'has_more' => $hasMore,
                'next_id'  => $nextId,
            ];
        }
        return $this->listData($data, $count, $other);
    }

    private function shouldSkipExpensiveListCount(array $where): bool
    {
        $expensiveFields = [
            'area_cascade',
        ];

        foreach ($expensiveFields as $field) {
            if (isset($where[$field]) && $where[$field] !== '' && $where[$field] !== []) {
                return true;
            }
        }

        return false;
    }

    private function getSkippedCountValue(bool $autoSkipCount, int $page, int $limit, int $listCount, bool $hasMore): int
    {
        if (! $autoSkipCount) {
            return -1;
        }

        $offset = $page > 0 && $limit > 0 ? ($page - 1) * $limit : 0;
        return $offset + $listCount + (int) $hasMore;
    }

    private function getLightweightListRows($query, array $fields): array
    {
        $arrayCastFields = $this->getListArrayCastFields($fields);

        return $query->select($fields)
            ->toBase()
            ->get()
            ->map(function ($item) use ($arrayCastFields) {
                $row = (array) $item;
                foreach ($arrayCastFields as $field) {
                    if (array_key_exists($field, $row)) {
                        $row[$field] = $this->decodeFieldJsonValue($row[$field]);
                    }
                }
                return $row;
            })
            ->all();
    }

    private function getListArrayCastFields(array $fields): array
    {
        try {
            $casts = $this->dao->getModel(false)->getCasts();
        } catch (\Throwable) {
            return [];
        }

        return collect($casts)
            ->filter(fn ($cast) => $this->isListArrayCast($cast))
            ->keys()
            ->intersect($fields)
            ->values()
            ->all();
    }

    private function isListArrayCast(mixed $cast): bool
    {
        if (! is_string($cast)) {
            return false;
        }

        $cast = strtolower($cast);

        return in_array($cast, ['array', 'json', 'collection', 'encrypted:array', 'encrypted:json', 'encrypted:collection'], true);
    }

    private function getAdminMapForList(array $ids): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        return DB::table('admin')
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->select(['id', 'avatar', 'name'])
            ->get()
            ->keyBy('id')
            ->map(fn ($item) => (array) $item)
            ->all();
    }

    private function getWorkCustomerMapForList(array $externalUserIds): array
    {
        $externalUserIds = $this->filterListIds($externalUserIds);
        if (! $externalUserIds) {
            return [];
        }

        return DB::table('work_client')
            ->whereIn('external_userid', $externalUserIds)
            ->select(['name', 'avatar', 'external_userid', 'type', 'corp_full_name as corp_name'])
            ->get()
            ->keyBy('external_userid')
            ->map(fn ($item) => (array) $item)
            ->all();
    }

    private function getSubscribeMapForList(int $uid, array $eids, int $viewTypes): array
    {
        $eids = $this->filterListIds($eids);
        if (! $eids) {
            return [];
        }

        return DB::table('client_subscribe')
            ->where('uid', $uid)
            ->where('types', $viewTypes)
            ->where('subscribe_status', 1)
            ->whereIn('eid', $eids)
            ->pluck('subscribe_status', 'eid')
            ->all();
    }

    private function getOddsProductTotalAmountMapForList(array $ids): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        return DB::table('customer_product_assist')
            ->where('link_type', CustomEnum::ODDS)
            ->whereIn('link_id', $ids)
            ->selectRaw('link_id, SUM(total_price) as total_amount')
            ->groupBy('link_id')
            ->pluck('total_amount', 'link_id')
            ->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))
            ->all();
    }

    private function getProductsMapForList(array $ids, int $linkType): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        $products = DB::table('customer_product_assist')
            ->where('link_type', $linkType)
            ->whereIn('link_id', $ids)
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => (array) $item)
            ->all();

        if ($linkType === CustomEnum::CONTRACT) {
            $products = $this->appendProductSkuIds($products);
        }

        return collect($products)
            ->groupBy('link_id')
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }

    private function getLiaisonTelMapForList(array $eids): array
    {
        $eids = $this->filterListIds($eids);
        if (! $eids) {
            return [];
        }

        return DB::table('customer_liaison')
            ->whereNull('deleted_at')
            ->whereIn('eid', $eids)
            ->select(['eid', 'liaison_name', 'liaison_tel'])
            ->get()
            ->keyBy('eid')
            ->map(fn ($item) => (array) $item)
            ->all();
    }

    private function getCustomerNameMapForList(array $ids): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        return DB::table('customer')
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->pluck('customer_name', 'id')
            ->all();
    }

    private function getCustomerNameMapByExternalUseridForList(array $externalUserIds): array
    {
        $externalUserIds = $this->filterListIds($externalUserIds);
        if (! $externalUserIds) {
            return [];
        }

        return DB::table('customer')
            ->whereNull('deleted_at')
            ->whereIn('external_userid', $externalUserIds)
            ->pluck('customer_name', 'external_userid')
            ->all();
    }

    private function getOddsNoMapForList(array $ids): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        return DB::table('customer_odds')
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->pluck('odds_no', 'id')
            ->all();
    }

    private function getCustomerLabelMapForList(array $ids): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        return DB::table('client_label')
            ->whereIn('id', $ids)
            ->select(['id', 'name'])
            ->get()
            ->keyBy('id')
            ->map(fn ($item) => (array) $item)
            ->all();
    }

    private function getSignedRelationIdsForList(int $viewTypes, array $ids): array
    {
        $ids = $this->filterListIds($ids);
        if (! $ids) {
            return [];
        }

        $query = DB::table('contract_doc')
            ->whereNull('deleted_at')
            ->whereIn('status', [1, 2, 3]);

        return match ($viewTypes) {
            CustomEnum::CUSTOMER => $query->whereIn('eid', $ids)->pluck('eid')->unique()->values()->all(),
            CustomEnum::ODDS     => $this->pluckJsonRelationIds($query, 'oid', $ids),
            default              => $this->pluckJsonRelationIds($query, 'cid', $ids),
        };
    }

    private function pluckJsonRelationIds($query, string $field, array $ids): array
    {
        $query->where(function ($subQuery) use ($field, $ids) {
            foreach ($ids as $id) {
                $subQuery->orWhereJsonContains($field, $id)
                    ->orWhereJsonContains($field, (string) $id);
            }
        });

        return $query->pluck($field)
            ->flatMap(fn ($value) => (array) $this->decodeFieldJsonValue($value))
            ->map(fn ($value) => is_numeric($value) ? (int) $value : $value)
            ->intersect($ids)
            ->unique()
            ->values()
            ->all();
    }

    private function filterListIds(array $ids): array
    {
        return collect($ids)
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 业务员或创建人.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCreatorAndSalesman(array $intersects, array $list): array
    {
        $ids = $userMap = [];
        if (in_array('salesman', $intersects) || in_array('creator', $intersects)) {
            $ids = array_merge(array_column($list, 'uid'), array_column($list, 'creator_uid'));
        }
        if (in_array('before_salesman', $intersects)) {
            $ids = array_merge(array_column($list, 'before_uid'), $ids);
        }
        if (empty($ids)) {
            return $userMap;
        }
        $list = app(AdminService::class)->select(['id' => array_unique($ids)], ['id', 'avatar', 'name'])->toArray();
        foreach ($list as $item) {
            $userMap[$item['id']] = $item;
        }
        return $userMap;
    }

    /**
     * 处理字典数据.
     */
    public function handleDataValue(mixed $value): array|int
    {
        return is_array($value) ? array_map(fn ($v) => is_array($v) ? array_map('intval', $v) : (int) $v, $value) : (int) $value;
    }

    /**
     * 处理字段数据.
     * @return array|int|mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handleFieldValue(string $inputType, string $type, mixed $value, int $scene = CustomEnum::SCENE_LIST): mixed
    {
        // 处理富文本编辑器内容：反转义HTML特殊字符
        if ($inputType === 'oawangeditor') {
            return $value ? stripslashes(htmlspecialchars_decode($value)) : '';
        }
        // 处理人员选择内容
        if ($inputType === 'member') {
            if (! $value) {
                return [];
            }
            $value = is_string($value) ? json_decode($value, true) : $value;
            if (collect($value)->filter(fn ($v) => is_array($v))->count()) {
                return $value;
            }
            return match ($scene) {
                CustomEnum::SCENE_LIST => $value ? app(AdminService::class)->select(['id' => is_array($value) ? array_unique($value) : $value], ['id', 'avatar', 'name'])?->toArray() : [],
                CustomEnum::SCENE_INFO => $value ? array_column(app(AdminService::class)->select(['id' => is_array($value) ? array_unique($value) : $value], ['id', 'avatar', 'name'])?->toArray(), 'name') : [],
                CustomEnum::SCENE_EDIT => $value ?: [],
            };
        }
        // 处理单值类型：尝试JSON解码，解码失败则返回原始值
        if ($type === 'single') {
            $decodedValue = $this->decodeFieldJsonValue($value);
            return is_array($decodedValue) && ! empty($decodedValue) ? $decodedValue : $value;
        }
        // 无需特殊处理的输入类型，直接返回原始值
        $passthroughInputs = ['date', 'datetime', 'input', 'radio'];
        if (in_array($inputType, $passthroughInputs, true)) {
            return $value;
        }
        // 处理文件/图片类型：通过handleDataValue方法处理解码后的值
        $fileInputs = ['file', 'images'];
        if (in_array($inputType, $fileInputs, true) && $value) {
            return $this->handleDataValue($this->decodeFieldJsonValue($value));
        }
        // 默认处理：值存在时，非数组则JSON解码，否则直接返回；值不存在则返回空数组
        if ($value) {
            return $this->decodeFieldJsonValue($value);
        }
        return [];
    }

    /**
     * 获取字典字段.
     */
    public function getDictField(array $fields): array
    {
        $dictField = array_filter(array_column($fields, 'dict_ident', 'key'));
        $callback  = function ($dictField) {
            $filterField = $this->dictFilterField();
            if ($filterField) {
                foreach ($filterField as $field) {
                    if (array_key_exists($field, $dictField)) {
                        unset($dictField[$field]);
                    }
                }
            }
            return $dictField;
        };
        return $this->getPlatform() == UserAgentEnum::ADMIN_AGENT && $dictField ? $callback($dictField) : $dictField;
    }

    /**
     * 获取字典回显.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNameListByIdent(string $ident, mixed $value): mixed
    {
        return app(DictDataService::class)->getNamesByValue($ident, $value);
    }

    /**
     * 时间查询.
     * @param mixed $dao
     * @param mixed $field
     * @param mixed $value
     * @return mixed|void
     */
    public function getDateSearch($dao, $field, $value)
    {
        if (! $value) {
            return $dao;
        }

        $dateRange = $this->parseSearchDateRange($value);
        if ($dateRange) {
            [$startTime, $endTime] = $dateRange;
            if (! str_contains($startTime, ':') && ! str_contains($endTime, ':')) {
                if ($startTime && $endTime) {
                    $endDate = Carbon::parse($endTime)->timezone(config('app.timezone'))->addDay()->toDateString();
                    return $dao->whereDate($field, '>=', $startTime)->whereDate($field, '<', $endDate);
                }
                if (! $startTime && $endTime) {
                    $endDate = Carbon::parse($endTime)->timezone(config('app.timezone'))->addDay()->toDateString();
                    return $dao->whereDate($field, '<', $endDate);
                }
                if ($startTime && ! $endTime) {
                    return $dao->whereDate($field, '>=', $startTime);
                }
            }
            if ($startTime && $endTime && $startTime != $endTime) {
                return $dao->whereBetween($field, [$startTime, $endTime]);
            }
            if ($startTime && $endTime && $startTime == $endTime) {
                return $dao->whereBetween($field, [$startTime, date('Y-m-d H:i:s', datetime_timestamp($endTime) + 86400)]);
            }
            if (! $startTime && $endTime) {
                return $dao->whereTime($field, '<', $endTime);
            }
            if ($startTime && ! $endTime) {
                return $dao->whereTime($field, '>=', $startTime);
            }
        }

        if (is_array($value)) {
            $value = array_values(array_filter($value, fn ($item) => $item !== '' && $item !== null))[0] ?? '';
        }
        $value = trim((string) $value);
        if (preg_match('/^lately+[1-9]{1,3}/', $value)) {
            // 最近天数 lately[1-9] 任意天数
            $day = (int) str_replace('lately', '', $value);
            if ($day) {
                return $dao->whereBetween($field, [Carbon::today()->subDays($day)->toDateTimeString(), Carbon::today()->toDateTimeString()]);
            }
        }

        if (preg_match('/^\d{4}-\d{1,2}$/', $value)) {
            $startTime = Carbon::createFromFormat('Y-m', $value)->startOfMonth()->toDateString();
            $endTime   = Carbon::createFromFormat('Y-m', $value)->endOfMonth()->toDateString();
            return $dao->whereDate($field, '>=', $startTime)->whereDate($field, '<=', $endTime);
        }

        return $dao->whereDate($field, $value);
    }

    /**
     * 解析日期区间，避免把单个 YYYY-MM-DD 里的连字符误判为区间分隔符。
     */
    protected function parseSearchDateRange(mixed $value): array
    {
        if (is_array($value)) {
            $value = array_values($value);
            if (count($value) < 2) {
                return [];
            }

            $range = [
                str_replace('/', '-', trim((string) $value[0])),
                str_replace('/', '-', trim((string) $value[1])),
            ];

            return $range[0] !== '' || $range[1] !== '' ? $range : [];
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $datePattern     = '\d{4}[\/-]\d{1,2}(?:[\/-]\d{1,2})?(?:\s+\d{1,2}:\d{1,2}(?::\d{1,2})?)?';
        $timePattern     = '\d{1,2}:\d{1,2}(?::\d{1,2})?';
        $endpointPattern = '(?:' . $datePattern . '|' . $timePattern . ')';
        if (preg_match('/^\s*(' . $endpointPattern . ')?\s*(?:-|~|至|到)\s*(' . $endpointPattern . ')?\s*$/u', $value, $matches)) {
            $range = [
                str_replace('/', '-', trim($matches[1] ?? '')),
                str_replace('/', '-', trim($matches[2] ?? '')),
            ];

            return $range[0] !== '' || $range[1] !== '' ? $range : [];
        }

        return [];
    }

    /**
     * 获取列表客户数据.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCustomerData(array $list): array
    {
        return $list ? app(CustomerService::class)->column(['id' => array_column($list, 'eid')], 'customer_name', 'id') : [];
    }

    /**
     * 获取列表商机数据.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getOddsData(array $list): array
    {
        $ids = collect($list)->pluck('oid')->map(fn ($value) => $this->normalizeRelationId($value))->filter()->unique()->values()->all();
        return $ids ? app(OpportunityService::class)->column(['id' => $ids], 'odds_no', 'id') : [];
    }

    /**
     * 兼容表单单选返回数组、JSON 数组和历史脏数据，统一取业务关联 ID。
     */
    protected function normalizeRelationId(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return 0;
            }
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeRelationId($decoded);
            }
            return is_numeric($value) ? (int) $value : $value;
        }

        if (is_array($value)) {
            foreach (['value', 'id'] as $key) {
                if (array_key_exists($key, $value)) {
                    return $this->normalizeRelationId($value[$key]);
                }
            }

            $value = array_values(array_filter($value, fn ($item) => $item !== '' && $item !== null));
            if (! $value) {
                return 0;
            }
            return $this->normalizeRelationId(end($value));
        }

        return is_numeric($value) ? (int) $value : ($value ?: 0);
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * 获取附件字段.
     */
    public function getAttachField(): array
    {
        return match ($this->platform) {
            UserAgentEnum::ADMIN_AGENT, UserAgentEnum::UNI_AGENT => ['id', 'att_dir as url', 'real_name as name', 'att_size as size', 'att_type as type'],
            default => ['id', 'att_dir', 'att_size', 'real_name', 'att_dir as url', 'real_name as name', 'att_size as size', 'att_type as type'],
        };
    }

    /**
     * 获取字典数据回显.
     * @return array|mixed|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function handleDictValue(string $ident, mixed $data, string $type = 'text', string $inputType = ''): mixed
    {
        if (is_dimensional_data($data)) {
            $val = collect($data)->map(function ($itemVal) use ($ident) {
                return $this->getNameListByIdent($ident, $itemVal);
            })->all();
            if ($type == 'multiple') {
                return collect($val)->map(function ($item) {
                    return is_array($item) ? implode('/', $item) : (string) $item;
                })->implode('、');
            } else {
                return $val;
            }
        }
        return $inputType == 'radio' ? app(DictDataService::class)->getRadio($ident, $data) : $this->getNameListByIdent($ident, $data);
    }

    /**
     * 获取权限用户.
     * @return array|int[]|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getScopeUid(int $userId, array|string $scope, bool $normal = true)
    {
        $result = Cache::tags([CacheEnum::TAG_FRAME, CacheEnum::TAG_ROLE])->remember(md5($userId . json_encode($scope) . (int) $normal), (int) sys_config('system_cache_ttl', 3600), function () use ($userId, $scope, $normal) {
            $modulePermissionService = app(ModulePermissionService::class);
            $roleUid                 = $modulePermissionService->getAccessibleUserIds($userId, ModuleEnum::CUSTOMER, $normal);
            $frameAssist             = app(FrameAssistService::class);
            switch ($scope) {
                case $this->self:
                    $uid = array_intersect([$userId], $roleUid);
                    break;
                case $this->department:
                    $info = $frameAssist->setTrashed(! $normal)->get(['user_id' => $userId, 'is_mastart' => 1], ['frame_id', 'is_admin', 'entid']);
                    if ($info['is_admin']) {
                        $uid = app(FrameService::class)->scopeUser((int) $info['frame_id'], $normal);
                    } else {
                        $uid = $frameAssist->setTrashed(! $normal)->column(['frame_id' => $info['frame_id'], 'is_mastart' => 1, 'is_admin' => 0], 'user_id');
                    }
                    $uid = array_intersect($uid, $roleUid);
                    break;
                case $this->subordinate:
                    $uid = $frameAssist->getSubUid($userId, $normal);
                    $uid = array_intersect($uid, $roleUid);
                    break;
                case $this->team:
                    $uid = array_merge($frameAssist->getSubUid($userId, $normal), [$userId]);
                    $uid = array_intersect($uid, $roleUid);
                    break;
                case $this->all:
                    $uid = $roleUid;
                    break;
                default:
                    $frameId  = app(FrameService::class)->scopeFrames($scope);
                    $frameUid = $frameAssist->setTrashed(! $normal)->column(['frame_id' => $frameId, 'is_mastart' => 1], 'user_id');
                    $uid      = array_intersect($frameUid, $roleUid);
            }
            if (in_array($userId, array_map('intval', $roleUid), true)) {
                $uid[] = $userId;
            }
            $uid = array_values(array_unique(array_filter(array_map('intval', $uid))));
            return json_encode($uid, JSON_UNESCAPED_UNICODE);
        });
        return $result ? json_decode($result, true) : [];
    }

    /**
     * 大数据列表中避免把“全部数据”权限展开为所有员工ID。
     */
    protected function applyScopeWhere(array $where, int $uid, string $scopeFrame): array
    {
        if ($scopeFrame === $this->all) {
            $permission = app(ModulePermissionService::class)->getUserModulePermission($uid, ModuleEnum::CUSTOMER);
            if (($permission['data_level'] ?? DataPermissionLevelEnum::NONE) === DataPermissionLevelEnum::ALL) {
                $where['uid_scope'] = 'all_active';
                unset($where['uid']);
                return $where;
            }
        }

        $where['uid'] = $this->getScopeUid($uid, $scopeFrame);
        return $where;
    }

    /**
     * 内部范围条件：全部权限场景用 active admin exists 替代超长 uid in (...)。
     */
    protected function applyUidScopeCondition($query, string $table, string $value, string $uidColumn = 'uid')
    {
        if ($value !== 'all_active') {
            return $query;
        }

        return $query->whereExists(function ($subQuery) use ($table, $uidColumn) {
            $subQuery->selectRaw('1')
                ->from('admin')
                ->whereColumn('admin.id', $table . '.' . $uidColumn)
                ->where('admin.status', 1)
                ->whereNull('admin.deleted_at');
        });
    }

    /**
     * “急需跟进”使用数据库内 exists 判断，避免先查出海量业务ID再 whereIn。
     */
    protected function applyUrgentFollowUpCondition($query, string $table, int|array|null $uid = null, string $uidColumn = 'uid')
    {
        $remindDay = Carbon::now(config('app.timezone'))->toDateString();

        return $query->whereExists(function ($subQuery) use ($table, $uid, $uidColumn, $remindDay) {
            $subQuery->selectRaw('1')
                ->from('client_follow')
                ->join('schedule_remind', 'client_follow.uniqued', '=', 'schedule_remind.uniqued')
                ->leftJoin('schedule_task', 'schedule_remind.sid', '=', 'schedule_task.pid')
                ->whereColumn('client_follow.eid', $table . '.id')
                ->where('client_follow.types', 1)
                ->where($table . '.' . $uidColumn, '<>', 0)
                ->whereDate('client_follow.time', '<', $remindDay)
                ->whereNull('client_follow.deleted_at')
                ->whereNull('schedule_remind.deleted_at')
                ->where(function ($query) {
                    $query->where('schedule_task.status', '<>', 3)
                        ->orWhereNull('schedule_task.id');
                });

            if (is_array($uid)) {
                if (! $uid) {
                    $subQuery->whereRaw('0 = 1');
                } else {
                    $subQuery->whereIn($table . '.' . $uidColumn, $uid);
                }
            } elseif ($uid !== null && $uid !== 0) {
                $subQuery->where($table . '.' . $uidColumn, $uid);
            }
        });
    }

    /**
     * 更新数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateData(array $data, int $id, int $uid, string $customType): mixed
    {
        $data        = collect($data)->except($this->getSystemReadonlyFieldKeys($customType));
        if ($data->isEmpty()) {
            return false;
        }
        $info        = $id ? $this->dao->get($id) : [];
        $collectInfo = collect($info);
        $formService = app(FormService::class);
        $formFields  = collect($formService->getDataList($customType))->keyBy('key'); // 按字段key建立索引
        $this->fieldValidate($data->all(), $id, $formFields->all());
        $data->map(function ($value, $key) use ($id, $customType, $info) {
            if (in_array($key, ['product', 'products'], true)) {
                $totalPrice = app(ProductAssistService::class)->saveProducts($value, $id, $customType == ViewSearchEnum::VIEW_ODDS ? CustomEnum::ODDS : CustomEnum::CONTRACT);
                if ($customType == ViewSearchEnum::VIEW_CONTRACT) {
                    $info->contract_price = $totalPrice;
                    $info->save();
                }
            }
        });
        // 获取需要比较的字段（存在于表单结构且在数据中有出现）
        $comparableFields = $formFields->keys()
            ->intersect($data->keys()) // 只处理表单中定义且有提交数据的字段
            ->reject(function ($key) {
                // 排除不需要追踪的系统字段
                return in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at']);
            });
        if ($comparableFields->isEmpty()) {
            return false; // 没有需要比较的字段
        }
        // 检测实际变更
        $attaches    = collect();
        $oldAttaches = collect();
        $clue        = collect();
        $followed    = collect();
        $changes     = $comparableFields->reduce(function (&$carry, $fieldKey) use ($data, $collectInfo, $formFields, $formService, $attaches, $info, $followed, $oldAttaches) {
            $fieldInfo = $formFields->get($fieldKey);
            $type      = strtolower($fieldInfo['type']);
            $inputType = strtolower($fieldInfo['input_type']);
            // 获取新旧值（处理原始数据可能不存在该字段的情况）
            $newValue = $data->get($fieldKey);
            $oldValue = $collectInfo->get($fieldKey);
            // 根据字段类型进行针对性比较
            if (! $this->compareValues($oldValue, $newValue, $type)) {
                $carry[$fieldKey] = [
                    'field_name' => $fieldInfo['key_name'] ?? $fieldKey, // 使用表单定义的字段名
                    'save_value' => $formService->getFormValue($type, $inputType, $newValue, $fieldKey),
                    'old_value'  => is_string($oldValue) ? json_decode($oldValue, true) : $oldValue,
                    'new_value'  => $newValue,
                    'field_type' => $inputType,
                    'type'       => $type,
                    'input_type' => $inputType,
                    'dict_ident' => $fieldInfo['dict_ident'],
                ];
                if (in_array($inputType, ['file', 'images'])) {
                    $info->{$fieldKey} = $newValue;
                    $oldValue          = is_string($oldValue) ? json_decode($oldValue, true) : $oldValue;
                    $newValue          = is_string($newValue) ? json_decode($newValue, true) : $newValue;
                    $attaches->push(array_diff($newValue, $oldValue ?: []));
                    $oldAttaches->push(array_diff($oldValue ?: [], $newValue));
                }
                if (in_array($fieldKey, ['customer_followed', 'followed', 'contract_followed'])) {
                    $followed->put('followed', (int) $newValue);
                }
            }
            return $carry;
        }, []);
        collect($changes)->filter(function ($value) {
            return $this->isRichTextField($value['field_type']);
        })->each(function ($value, $key) use ($info) {
            $info->{$key} = $value['save_value'];
        });

        $record = collect($changes)->filter(function ($value) {
            return $this->shouldRecordFieldChange($value['field_type']);
        })->map(function ($value, $key) use ($info, &$clue) {
            if ($key == 'contract_customer') {
                $key = 'eid';
            }
            $info->{$key} = $value['save_value'];
            if ($value['dict_ident']) {
                $value['old_value'] = $this->handleDictValue($value['dict_ident'], $value['old_value'], $value['type'], $value['input_type']);
                $value['new_value'] = $this->handleDictValue($value['dict_ident'], $value['new_value'], $value['type'], $value['input_type']);
                if ($value['type'] == 'radio') {
                    $value['old_value'] = $value['old_value'] ? reset($value['old_value']) : '空';
                    $value['new_value'] = $value['new_value'] ? reset($value['new_value']) : '空';
                } else {
                    if ($value['old_value'] instanceof BaseModel) {
                        $value['old_value'] = $value['old_value']->name;
                    } else {
                        $value['old_value'] = $value['old_value'] ?: '空';
                    }
                    if ($value['new_value'] instanceof BaseModel) {
                        $value['new_value'] = $value['new_value']->name;
                    } else {
                        $value['new_value'] = $value['new_value'] ?: '空';
                    }
                }
            }
            if ($value['type'] == 'multiplemember') {
                $adminService       = app(AdminService::class);
                $value['old_value'] = $value['old_value'] ? collect($adminService->column(['id' => $value['old_value']], 'name'))->implode('、') : '空';
                $value['new_value'] = $value['new_value'] ? collect($adminService->column(['id' => $value['new_value']], 'name'))->implode('、') : '空';
            }
            if ($value['type'] == 'singlemember') {
                $adminService       = app(AdminService::class);
                $value['old_value'] = $value['old_value'] ? $adminService->value(['id' => $value['old_value']], 'name') : '空';
                $value['new_value'] = $value['new_value'] ? $adminService->value(['id' => $value['new_value']], 'name') : '空';
            }
            if ($key == 'customer_label') {
                $labelService       = app(LabelService::class);
                $value['old_value'] = $value['old_value'] ? collect($labelService->column(['id' => $value['old_value']], 'name'))->implode('、') : '空';
                $value['new_value'] = $value['new_value'] ? collect($labelService->column(['id' => $value['new_value']], 'name'))->implode('、') : '空';
            }
            if ($key == 'clue_id') {
                $clueService        = app(LeadService::class);
                $value['old_value'] = $value['old_value'] ? $clueService->dao->setTrashed()->value($value['old_value'], 'name') : '空';
                $value['new_value'] = $value['new_value'] ? $clueService->dao->setTrashed()->value($value['new_value'], 'name') : '空';
                $clue               = collect($clueService->get($value['save_value'], ['userid', 'external_userid', 'name'])?->toArray());
            }
            if ($key == 'eid') {
                $customerService    = app(CustomerService::class);
                $value['old_value'] = $value['old_value'] ? $customerService->dao->setTrashed()->value($value['old_value'], 'customer_name') : '空';
                $value['new_value'] = $value['new_value'] ? $customerService->dao->setTrashed()->value($value['new_value'], 'customer_name') : '空';
            }
            $newValue = is_array($value['new_value']) ? implode(',', $value['new_value']) : $value['new_value'];
            $oldValue = is_array($value['old_value']) ? implode(',', $value['old_value']) : $value['old_value'];
            return $value['field_name'] . '：由【' . ($oldValue ?: '空') . '】修改为【' . ($newValue ?: '空') . '】';
        })->implode('; ');
        if (! collect($changes)->isEmpty() || ! $attaches->isEmpty()) {
            $info->save();
            $record && app(RecordService::class)->saveRecord(
                $customType,
                [
                    'eid'            => $id,
                    'type'           => CustomEnum::OPERATE_CHANGE,
                    'uid'            => (int) ($info->uid ?? $uid),
                    'creator_uid'    => $uid,
                    'record_version' => 0,
                    'reason'         => $record,
                ]
            );
        }
        // 保存附件关联
        if (! $attaches->isEmpty()) {
            $info->save();
            $attaches->each(function ($item) use ($id, $customType) {
                $item = is_string($item) ? json_decode($item, true) : $item;
                app(AttachService::class)->update(['id' => $item], ['relation_id' => $id, 'relation_type' => AttachEnum::RELATION_TYPE[$customType]]);
            });
        }
        if (! $oldAttaches->isEmpty()) {
            $oldAttaches->each(function ($item) {
                app(AttachService::class)->update(['id' => $item], ['relation_id' => 0, 'relation_type' => 0]);
            });
        }
        // 保存线索关联
        if (! $clue->isEmpty()) {
            $info->save();
            app(LeadService::class)->delete(['id' => $data['clue_id']]);
            app(RecordService::class)->saveRecord(
                $customType,
                [
                    'eid'            => $id,
                    'type'           => CustomerEnum::OPERATE_CONVERT,
                    'creator_uid'    => $uid,
                    'record_version' => 0,
                    'reason'         => '客户“' . $info->customer_name . '”关联线索“' . $clue->get('name') . '”',
                ]
            );
            $clue->get('external_userid') && MergeCustomerJob::dispatch($clue->get('external_userid'));
        }
        // 处理关注状态
        if (! $followed->isEmpty()) {
            app(SubscribeService::class)->setSubscribe($uid, $id, (int) $followed->get('followed'), $customType);
        }
        // 线索标签同步到客户标签
        if (isset($changes['customer_label'])) {
            CustomerLabelToWorkJob::dispatch([$id => $changes['customer_label']['old_value']], $changes['customer_label']['new_value'], $customType);
        }

        return true;
    }

    /**
     * 数据详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function detail(int|string $id, int $uid, string $customType, string $platform = CommonEnum::ORIGIN_WEB): mixed
    {
        $shouldMaskPhone = $customType === ViewSearchEnum::VIEW_CLUE_SEAS
            || (string) request()->input('types', '') === ViewSearchEnum::VIEW_CLUE_SEAS;
        if ($customType === ViewSearchEnum::VIEW_CLUE_SEAS) {
            $customType = ViewSearchEnum::VIEW_CLUE;
        }
        if (in_array($customType, [ViewSearchEnum::VIEW_ODDS, ViewSearchEnum::VIEW_CONTRACT])) {
            $with = ['product'];
        }
        $info = is_numeric($id) ? $this->dao->get($id, with: $with ?? [])?->toArray() : $this->dao->get(['external_userid' => $id], with: $with ?? [])?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }
        $product = $info['product'] ?? [];
        if ($customType === ViewSearchEnum::VIEW_CONTRACT) {
            $product = $this->appendProductSkuIds($product);
        }
        $attachField   = $this->getAttachField();
        $attachService = app(AttachService::class);
        $dictValue     = collect();
        $enumField     = collect(app(FormService::class)->getEnumField($this->getCustomType($customType)));
        $form          = collect(app(FormService::class)->getFormDataWithType($customType, platform: $this->getPlatform(), associationId: (int) ($info['eid'] ?? 0), oddsId: (int) ($info['oid'] ?? 0)))
            ->map(function ($item) use ($info, $product, $attachField, $attachService, $uid, &$dictValue, $customType, $platform, $enumField) {
                $item['data'] = collect($item['data'])->merge($enumField->map(function ($enum) {
                    $enum['key']      = $enum['field'];
                    $enum['key_name'] = $enum['name'];
                    return $enum;
                }))->map(function ($datum) use ($info, $product, $attachField, $attachService, $uid, &$dictValue, $customType, $platform, $enumField) {
                    if ($datum['key'] === 'products') {
                        $datum['value']    = $product;
                        $datum['disabled'] = $datum['disabled'] ?? false;
                        return $datum;
                    }
                    // 处理字段值
                    if (array_key_exists($datum['key'], $info)) {
                        $type           = strtolower($datum['type']);
                        $inputType      = strtolower($datum['input_type']);
                        $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], $platform == CommonEnum::ORIGIN_WEB ? CustomEnum::SCENE_EDIT : CustomEnum::SCENE_LIST);
                        // 处理会员选项
                        if ($inputType == 'member') {
                            $datum['options'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']]);
                        }
                        // 处理文件和图片
                        if (in_array($inputType, ['file', 'images'])) {
                            $datum['files'] = empty($datum['value']) ? [] : $attachService->getListByRelationType($customType, $datum['value'], $attachField);
                        }
                        // 处理关注状态
                        if (in_array($datum['key'], ['customer_followed', 'followed', 'contract_followed'])) {
                            $datum['value'] = (string) app(SubscribeService::class)->getSubscribe($uid, $info['id'], $customType);
                        }
                        if ($datum['dict_ident']) {
                            $dictValue->put($datum['key'], $this->handleDictValue($datum['dict_ident'], $datum['value'], $type, $inputType));
                            if ($platform == CommonEnum::ORIGIN_UNI) {
                                $datum['value'] = $this->handleDictValue($datum['dict_ident'], $datum['value'], $type, $inputType);
                            }
                            if ($datum['value'] === 'null') {
                                $datum['value'] = [];
                            }
                        }
                        if ($datum['key'] === 'customer_label') {
                            $datum['options'] = app(LabelService::class)->getLabelOptions([]);
                        }
                    }
                    if (in_array($datum['key'], ['oid', 'cid', 'eid'])) {
                        $datum['value'] = (int) $datum['value'];
                    }
                    // 处理关联客户
                    if (in_array($datum['key'], ['contract_customer', 'odds_customer']) && $info['eid']) {
                        $datum['value'] = $platform != CommonEnum::ORIGIN_UNI ? $info['eid'] : app(CustomerService::class)->value($info['eid'], 'customer_name');
                    }
                    // 处理关联客户
                    if ($datum['key'] == 'oid' && $info['oid']) {
                        $datum['value'] = $platform != CommonEnum::ORIGIN_UNI ? $info['oid'] : app(OpportunityService::class)->value($info['oid'], 'odds_no');
                    }
                    // 处理线索ID
                    if ($datum['key'] == 'clue_id') {
                        if ($info['external_userid'] || $datum['value']) {
                            if ($info['external_userid']) {
                                $where = ['userid' => $info['userid'], 'external_userid' => $info['external_userid']];
                            } else {
                                $where['id'] = $datum['value'];
                            }
                            $clue = app(LeadService::class)->dao->setTrashed()->get($where, ['id as value', 'name as label'])?->toArray();
                            if ($clue) {
                                $datum['value']   = $platform == CommonEnum::ORIGIN_WEB ? $clue['value'] : $clue['label'];
                                $datum['options'] = [$clue];
                            }
                        }
                    }
                    if (in_array($datum['key'], $enumField->pluck('field')->all())) {
                        return [];
                    }
                    $datum['disabled'] = $datum['disabled'] ?? false;
                    return $datum;
                })->filter()->values()->all();
                return $item;
            })->all();
        $form = $this->prependSystemReadonlyFields($form, $info, $customType);
        $form = $this->appendDetailProductListField($form, $product, $customType);
        $form = $this->applyDetailFieldDisabledStatus($form, $customType);
        $field = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => ['id', 'customer_name', 'customer_status', 'salesman', 'customer_no', 'work_customer', 'customer_label', 'member', 'created_at', 'customer_followed', 'customer_tel', 'area_cascade'],
            ViewSearchEnum::VIEW_CLUE     => ['id', 'name', 'status', 'salesman', 'work_customer', 'customer_label', 'created_at', 'followed', 'phone'],
            ViewSearchEnum::VIEW_ODDS     => ['id', 'name', 'odds_no', 'status', 'salesman', 'customer_name', 'eid', 'followed', 'last_follow_time', 'work_customer', 'total_amount'],
            ViewSearchEnum::VIEW_CONTRACT => ['id', 'contract_name', 'contract_no', 'contract_status', 'salesman', 'customer_name', 'surplus', 'start_date', 'end_date', 'eid', 'contract_followed', 'created_at'],
            ViewSearchEnum::VIEW_LIAISON  => ['id', 'liaison_name'],
            default                       => ['id'],
        };
        $data = collect();
        collect($field)->map(function ($item) use ($info, $dictValue, &$data, $customType, $uid) {
            if ($dictValue->has($item)) {
                $data->put($item, $dictValue->get($item));
            } else {
                $data->put($item, match ($item) {
                    'salesman'      => app(AdminService::class)->value($info['uid'], 'name'),
                    'customer_name' => $info[$item] ?? app(CustomerService::class)->value($info['eid'], 'customer_name'),
                    'work_customer' => $info['external_userid'] ? app(WorkClientService::class)->get(['external_userid' => $info['external_userid']], ['name', 'avatar', 'external_userid', 'type', 'corp_full_name as corp_name']) : [],
                    'member'        => $info['member'] ? (
                        collect($info['member'])->filter(fn ($v) => is_array($v))->count()
                        ? $info['member']
                        : app(AdminService::class)->select(['id' => $info['member']], ['uid', 'id', 'name', 'avatar'])?->toArray()
                    ) : [],
                    'customer_label' => app(LabelService::class)->getWithParent($info['customer_label']),
                    'followed','customer_followed','contract_followed' => (string) app(SubscribeService::class)->getSubscribe($uid, $info['id'], $customType),
                    'total_amount'     => number_format(collect($info['product'] ?? [])->pluck('total_price')->sum(), 2, '.', '') ?? '0.00',
                    'last_follow_time' => collect(app(FollowUpService::class)->getLastFollow(['eid' => $info['id'], 'types' => 0, 'link_type' => $customType]))->pluck('created_at', 'eid')->map(fn ($carbon) => $carbon instanceof Carbon ? $carbon->toDateTimeString() : '')->first(),
                    default            => $info[$item],
                });
            }
            return $item;
        })->all();
        $data['price'] = collect($info['product'] ?? [])->sum(function ($item) {
            return $item['price'] * $item['count'];
        });
        $data  = $data->all();
        $count = $this->assistCount($info['id'], $customType);
        if ($shouldMaskPhone) {
            [$form, $data] = $this->maskClueSeasPhoneInDetail($form, $data);
        }
        return compact('form', 'data', 'product', 'count');
    }

    /**
     * 线索池详情电话脱敏.
     */
    private function maskClueSeasPhoneInDetail(array $form, array $data): array
    {
        $form = collect($form)->map(function ($item) {
            if (! isset($item['data']) || ! is_array($item['data'])) {
                return $item;
            }
            $item['data'] = collect($item['data'])->map(function ($datum) {
                if (($datum['key'] ?? '') === 'phone') {
                    $datum['value'] = mask_phone(is_scalar($datum['value'] ?? null) || ($datum['value'] ?? null) === null ? (string) ($datum['value'] ?? '') : '');
                }
                return $datum;
            })->all();
            return $item;
        })->all();
        if (array_key_exists('phone', $data)) {
            $data['phone'] = mask_phone(is_scalar($data['phone']) || $data['phone'] === null ? (string) ($data['phone'] ?? '') : '');
        }
        return [$form, $data];
    }

    protected function appendProductSkuIds(array $product): array
    {
        $uniques = collect($product)->pluck('unique')->filter()->unique()->values()->all();
        if (! $uniques) {
            return collect($product)->map(function ($item) {
                $item['sku_id'] = 0;
                return $item;
            })->all();
        }

        $skuMap = app(ProductAttrValueService::class)->column(['unique' => $uniques], 'id', 'unique');
        return collect($product)->map(function ($item) use ($skuMap) {
            $item['sku_id'] = (int) ($skuMap[$item['unique'] ?? ''] ?? 0);
            return $item;
        })->all();
    }

    /**
     * 列表导出.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dataExport(array $where, string $types, int $uid = 0)
    {
        $viewTypes = match ($types) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => 1,
            ViewSearchEnum::VIEW_CONTRACT => 2,
            ViewSearchEnum::VIEW_LIAISON  => 3,
            ViewSearchEnum::VIEW_CLUE,ViewSearchEnum::VIEW_CLUE_SEAS => 4,
            ViewSearchEnum::VIEW_ODDS    => 5,
            ViewSearchEnum::VIEW_PRODUCT => 6,
            default                      => 0,
        };
        // 增加系统视图查询条件
        $where = $this->viewSearchWhere($where, $uid);
        // 表单字段处理
        $formFields = collect(app(FormService::class)->getFormDataList($viewTypes, field: ['key', 'key_name', 'input_type', 'type', 'dict_ident']))
            ->filter(fn ($item) => ! in_array($item['input_type'], ['file', 'images', 'oawangeditor']))->all();
        $formFieldCollection = collect($formFields);
        $formFieldKeys       = $formFieldCollection->pluck('key')->all();
        // 表格字段
        $tableColumns = Schema::getColumnListing($this->dao->getTable());
        // 本地字段
        $localField = ['id', 'uid', 'userid', 'external_userid', 'creator_uid', 'before_uid', 'eid', 'surplus', 'pid', 'end_date'];
        // 用户自定义字段
        $customFields = app(SalesmanCustomService::class)->getCustomField($uid, $types, CustomEnum::LIST_SELECT);
        // 优化数据库查询字段计算逻辑
        $mergedKeys  = array_merge($formFieldKeys, $customFields);
        $searchField = array_merge(
            array_intersect($tableColumns, $mergedKeys),
            array_intersect($tableColumns, $localField)
        );
        // 计算其他字段
        $otherField = array_diff($mergedKeys, $tableColumns);
        $record     = app(ImportRecordService::class)->create([
            'uid'    => $uid,
            'types'  => 0,
            'module' => $types,
            'name'   => '',
        ]);
        new CustomerExportService($uid, $where, $searchField, $otherField, $types, $this, $record->id);
    }

    /**
     * 导入数据.
     */
    public function dataImport(string $customType, int $fileId, int $uid = 0): void
    {
        $file = app(AttachService::class)->get($fileId);
        if (! $file) {
            throw $this->exception('文件不存在');
        }
        $record = app(ImportRecordService::class)->create([
            'uid'       => $uid,
            'types'     => 1,
            'module'    => $customType,
            'file_path' => $file->att_dir,
            'name'      => $file->real_name,
        ]);
        $file->relation_id   = $record->id;
        $file->relation_type = AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_CUSTOMER_IMPORT];
        $file->save();
        if ($file->up_type != 1) {
            $stream   = fopen($file->att_dir, 'r');
            $fileName = 'storage/exports/' . $file->real_name;
            file_put_contents($fileName, stream_get_contents($stream));
        } else {
            $fileName = 'public/' . $file->getAttributes()['att_dir'];
        }
        new CustomerImportService($uid, $fileName, $customType, $record->id);
    }

    /**
     * 仅对字符串字段做 JSON 解码，兼容模型 array cast 后的数组值.
     */
    protected function decodeFieldJsonValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decodedValue = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value;
    }

    protected function filterStr($str)
    {
        if (! is_string($str)) {
            return $str;
        }
        $bad = ['null', 'NULL', 'Null', '[]', '[ ]'];
        return str_replace($bad, '', $str);
    }

    /**
     * tab数量辅助统计.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function assistCount(int $id, string $customType): array
    {
        $follow_count   = app(FollowUpService::class)->count(['eid' => $id, 'link_type' => $customType]);
        $record_count   = app(RecordService::class)->count(['eid' => $id, 'link_type' => $customType]);
        $liaisons_count = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(LiaisonService::class)->count(['eid' => $id, 'uid' => 0]),
            ViewSearchEnum::VIEW_CUSTOMER      => app(LiaisonService::class)->count(['eid' => $id]),
            default                            => 0,
        };
        $contract_count = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(OrderService::class)->count(['eid' => $id]),
            ViewSearchEnum::VIEW_ODDS => app(OrderService::class)->count(['oid' => $id]),
            default                   => 0,
        };
        $odds_count = app(OpportunityService::class)->count(['eid' => $id]);
        $bill_count = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(PaymentService::class)->count(['eid' => $id]),
            ViewSearchEnum::VIEW_CONTRACT => app(PaymentService::class)->count(['cid' => $id]),
            default                       => 0,
        };
        $remind_count  = app(RemindService::class)->count(['eid' => $id]);
        $invoice_count = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(InvoiceService::class)->count(['eid' => $id]),
            ViewSearchEnum::VIEW_CONTRACT => app(InvoiceService::class)->count(['cid' => $id]),
            default                       => 0,
        };
        $contract_doc_count = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(ContractService::class)->count(['eid' => $id]),
            default => 0,
        };
        $stock_record_count = match ($customType) {
            ViewSearchEnum::VIEW_CONTRACT => app(WarehouseStockService::class)->recordCount($id),
            default                       => 0,
        };
        $file_count = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(AttachService::class)->getListCountByRelation(['relation_type' => [2, 3, 4, 5, 6], 'eid' => $id]),
            default => 0,
        };
        return compact('follow_count', 'record_count', 'liaisons_count', 'contract_count', 'odds_count', 'bill_count', 'remind_count', 'invoice_count', 'contract_doc_count', 'stock_record_count', 'file_count');
    }

    /**
     * 根据字段类型比较值是否不同.
     *
     * @param mixed $oldValue 旧值
     * @param mixed $newValue 新值
     * @param string $fieldType 字段类型
     * @return bool 是否相同（true=未变更，false=已变更）
     */
    protected function compareValues(mixed $oldValue, mixed $newValue, string $fieldType): bool
    {
        // 处理空值情况
        if (is_null($oldValue) && is_null($newValue)) {
            return true;
        }
        // 针对不同字段类型进行特殊处理
        switch ($fieldType) {
            case 'single':
                $oldValue = is_array($oldValue) ? end($oldValue) : $oldValue;
                $newValue = is_array($newValue) ? end($newValue) : $newValue;
                // 单选框字段，比较值
                return $oldValue == $newValue;
            case 'checkbox':
            case 'multiple':
            case 'select':
                // 数组类型字段（如多选框），排序后比较
                $old = collect((array) $oldValue)->sort()->values()->all();
                $new = collect((array) $newValue)->sort()->values()->all();
                return $old == $new;
            case 'datetime':
            case 'date':
                // 日期时间类型，标准化后比较
                return $this->normalizeDate($oldValue) === $this->normalizeDate($newValue);
            case 'file':
            case 'image':
            case 'images':
                return $oldValue == $newValue;
            default:
                // 普通字段直接比较
                return $oldValue == $newValue;
        }
    }

    /**
     * 判断字段变更是否需要写入动态记录.
     */
    protected function shouldRecordFieldChange(string $inputType): bool
    {
        return ! in_array($inputType, ['images', 'files', 'file', 'oawangeditor']);
    }

    /**
     * 判断是否为富文本字段.
     */
    protected function isRichTextField(string $inputType): bool
    {
        return $inputType === 'oawangeditor';
    }

    /**
     * 判断详情表单字段是否禁用.
     */
    protected function shouldDisableDetailField(array $field, string $customType): bool
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CONTRACT => in_array($field['key'] ?? '', ['outbound_warehouse_id'], true),
            ViewSearchEnum::VIEW_LIAISON  => in_array($field['key'] ?? '', ['eid'], true),
            default                     => false,
        };
    }

    /**
     * 应用详情表单字段禁用状态.
     */
    protected function applyDetailFieldDisabledStatus(array $form, string $customType): array
    {
        return collect($form)->map(function ($item) use ($customType) {
            $item['data'] = collect($item['data'] ?? [])->map(function ($field) use ($customType) {
                $field['disabled'] = (bool) ($field['disabled'] ?? false) || $this->shouldDisableDetailField($field, $customType);
                return $field;
            })->values()->all();
            return $item;
        })->all();
    }

    /**
     * 将产品列表补进详情表单展示，使其不依赖产品分组的自定义表单配置.
     */
    private function appendDetailProductListField(array $form, array $product, string $customType): array
    {
        if (! in_array($customType, [ViewSearchEnum::VIEW_ODDS, ViewSearchEnum::VIEW_CONTRACT], true)) {
            return $form;
        }
        $hasProductField = collect($form)
            ->flatMap(fn ($item) => $item['data'] ?? [])
            ->contains(fn ($field) => ($field['key'] ?? '') === 'products');
        if ($hasProductField) {
            return $form;
        }
        $productField = [
            'id'            => 0,
            'key'           => 'products',
            'key_name'      => '产品列表',
            'type'          => 'multiple',
            'input_type'    => 'product',
            'value'         => $product,
            'placeholder'   => '',
            'required'      => 0,
            'max'           => 0,
            'min'           => 0,
            'decimal_place' => 0,
            'dict_ident'    => '',
            'options'       => [],
            'options_level' => 0,
            'disabled'      => false,
            'system_field'  => true,
        ];

        if (! $form) {
            return [[
                'title'  => '产品清单',
                'ident'  => 'product',
                'status' => 1,
                'data'   => [$productField],
            ]];
        }

        $lastIndex                = array_key_last($form);
        $form[$lastIndex]['data'] = array_values(array_merge($form[$lastIndex]['data'] ?? [], [$productField]));
        return $form;
    }

    /**
     * 将系统编号补进详情表单展示，但保持只读且不写入自定义表单配置.
     */
    private function prependSystemReadonlyFields(array $form, array $info, string $customType): array
    {
        $readonlyFields = collect($this->getSystemReadonlyFields($customType))
            ->filter(fn ($field) => array_key_exists($field['key'], $info))
            ->map(fn ($field) => [
                'id'            => 0,
                'key'           => $field['key'],
                'key_name'      => $field['key_name'],
                'type'          => 'text',
                'input_type'    => 'input',
                'value'         => $info[$field['key']] ?? '',
                'placeholder'   => '',
                'required'      => 0,
                'max'           => 0,
                'min'           => 0,
                'decimal_place' => 0,
                'dict_ident'    => '',
                'options'       => [],
                'options_level' => 0,
                'disabled'      => true,
                'readonly'      => true,
                'system_field'  => true,
            ])->values()->all();

        if (! $readonlyFields) {
            return $form;
        }

        if (! $form) {
            return [[
                'title'  => '基本信息',
                'ident'  => 'base',
                'status' => 1,
                'data'   => $readonlyFields,
            ]];
        }

        $form[0]['data'] = array_values(array_merge($readonlyFields, $form[0]['data'] ?? []));
        return $form;
    }

    /**
     * 标准化日期格式.
     *
     * @param mixed $date 日期值
     * @return string 标准化后的日期字符串
     */
    protected function normalizeDate($date): string
    {
        if (is_null($date)) {
            return '';
        }
        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return (string) $date;
        }
    }

    /**
     * 生成唯一编号.
     */
    protected function getUniqueNo(string $prefix = ''): string
    {
        $microtime  = microtime(true);
        $timePart   = date('mdHis', (int) $microtime) . sprintf('%02d', (int) (fmod($microtime, 1) * 100));
        $randomPart = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
        return $prefix . $timePart . $randomPart;
    }

    /**
     * 获取业务类型.
     */
    private function getCustomType(string $customType): int
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => CustomEnum::CUSTOMER,
            ViewSearchEnum::VIEW_CONTRACT => CustomEnum::CONTRACT,
            ViewSearchEnum::VIEW_LIAISON  => CustomEnum::LIAISON,
            ViewSearchEnum::VIEW_CLUE,ViewSearchEnum::VIEW_CLUE_SEAS => CustomEnum::CLUE,
            ViewSearchEnum::VIEW_ODDS    => CustomEnum::ODDS,
            ViewSearchEnum::VIEW_PRODUCT => CustomEnum::PRODUCT,
            default                      => 0,
        };
    }

    /**
     * 字段验证
     */
    private function fieldValidate(array $data, int $id, array $list = [], int $force = 1): void
    {
        $list = collect($list);
        $tz   = config('app.timezone');
        $data = collect($data);
        $list->each(function ($item) use ($data, $tz, $id, $force) {
            $key = $item['key'];
            if (! $data->has($key)) {
                return;
            }
            $value     = $data->get($key);
            $inputType = strtolower($item['input_type']);
            $type      = strtolower($item['type']);
            $min       = $item['min'] ?? '';
            $max       = $item['max'] ?? '';
            $required  = (int) ($item['required'] ?? 0) === 1;
            // 1. 验证必填项
            if ($required && ($value === '' || $value === [])) {
                $action = collect(['select', 'checked', 'file', 'radio', 'date'])->contains($inputType) ? '选择' : '输入';
                throw $this->exception("请{$action}{$item['key_name']}");
            }
            if (empty($value) && ! $required) {
                return;
            }
            // 2. 计算长度（修复闭包调用问题）
            $lengthCalculators = [
                'input' => function () use ($value) {
                    return is_array($value) ? count($value) : mb_strlen((string) $value);
                },
                'select' => function () use ($value) {
                    return is_array($value) ? count($value) : 1;
                },
                'checked' => function () use ($value) {
                    return is_array($value) ? count($value) : 1;
                },
                'file' => function () use ($value) {
                    return is_array($value) ? count($value) : 1;
                },
                'oawangeditor' => function () use ($value) {
                    return is_array($value) ? count($value) : mb_strlen((string) $value);
                },
            ];
            // 获取计算器并执行
            $calculator = $lengthCalculators[$inputType] ?? function () use ($value) {
                return is_array($value) ? count($value) : mb_strlen((string) $value);
            };
            $len = $calculator(); // 显式调用闭包获取长度值
            // 3. 长度验证
            $validators = [
                'input' => function () use ($item, $len, $min, $max, $type) {
                    $text = $type === 'number' ? '数字' : '字';
                    if ($max && $len > $max) {
                        throw $this->exception(sprintf('%s最多输入%d个%s', $item['key_name'], $max, $text));
                    }
                    if ($min && $len < $min) {
                        throw $this->exception(sprintf('%s最少输入%d个%s', $item['key_name'], $min, $text));
                    }
                },
                //                'select' => function () use ($item, $len, $min, $max) {
                //                    $this->handleSelectLikeValidation($item, $len, $min, $max);
                //                },
                //                'checked' => function () use ($item, $len, $min, $max) {
                //                    $this->handleSelectLikeValidation($item, $len, $min, $max);
                //                },
                'file' => function () use ($item, $len, $min, $max) {
                    $this->handleSelectLikeValidation($item, $len, $min, $max);
                },
                'oawangeditor' => function () use ($item, $len, $min) {
                    if ($len > 65535) {
                        throw $this->exception('最多输入65535个字');
                    }
                    if ($min && $len < $min) {
                        throw $this->exception(sprintf('%s最少输入字数%d', $item['key_name'], $min));
                    }
                },
            ];
            // 执行验证器
            $validator = $validators[$inputType] ?? function () {};
            $validator();
            // 4. 日期验证
            if ($inputType === 'date' || $type === 'datetime') {
                $date = Carbon::parse($value, $tz);
                //                if ($max && $date->gt(Carbon::parse($max, $tz))) {
                //                    throw $this->exception(sprintf('%s不能晚于%s', $item['key_name'], $max));
                //                }
                //                if ($min && $date->lt(Carbon::parse($min, $tz))) {
                //                    throw $this->exception(sprintf('%s不能早于%s', $item['key_name'], $min));
                //                }
            }
            // 5. 唯一性验证
            if ($item['uniqued'] && ! collect(['customer_name', 'customer_tel'])->contains($key)) {
                $uniqueValue = match ($inputType) {
                    'select' => $type === 'single'
                        ? intval(is_array($value) ? ($value[0] ?? 0) : $value)
                        : $value,
                    'radio' => (int) $value,
                    default => in_array($inputType, ['date', 'input', 'oawangeditor'])
                        ? $value
                        : json_encode(collect($value)->sort()->all())
                };
                $where = collect(['not_id' => $id ?: null])->filter()->prepend($uniqueValue, $key)->all();
                if ($this->dao->exists($where)) {
                    throw $this->exception($item['key_name'] . '已存在');
                }
            }
            // 6. 客户提示验证
            if (! $force && collect(['customer_name', 'customer_tel'])->contains($key)) {
                $where = collect(['not_id' => $id ?: null])->filter()->prepend($value, $key)->all();
                if ($this->dao->exists($where)) {
                    $msg = $item['key_name'] . '已存在，是否继续' . ($id ? '修改客户' : '添加客户');
                    throw $this->exception($msg, CodeEnum::VERIFY_CODE);
                }
            }
        });
    }

    /**
     * 处理选择类验证
     * @param mixed $min
     * @param mixed $max
     */
    private function handleSelectLikeValidation(array $item, int $len, $min, $max): void
    {
        if ($max && $len > $max) {
            throw $this->exception(sprintf('%s最多选择数量%d', $item['key_name'], $max));
        }
        if ($min && $len < $min) {
            throw $this->exception(sprintf('%s最少选择数量%d', $item['key_name'], $min));
        }
    }

    private function getWhere($where)
    {
        $viewTypes = match ($where['types']) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => 1,
            ViewSearchEnum::VIEW_CONTRACT => 2,
            ViewSearchEnum::VIEW_LIAISON  => 3,
            ViewSearchEnum::VIEW_CLUE,ViewSearchEnum::VIEW_CLUE_SEAS => 4,
            ViewSearchEnum::VIEW_ODDS    => 5,
            ViewSearchEnum::VIEW_PRODUCT => 6,
            default                      => 1,
        };
        $baseFields = collect(app(FormService::class)->getCustomDataByTypes($viewTypes, ['key as field', 'input_type', 'type']));
        // 根据类型合并字段集
        $mergedFields = match ($where['types']) {
            ViewSearchEnum::VIEW_CUSTOMER      => $baseFields->concat(CustomerEnum::CUSTOMER_SEARCH_FIELD)->concat(CustomerEnum::CUSTOMER_VIEWER_SEARCH_FIELD),
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => $baseFields->concat(CustomerEnum::CUSTOMER_SEARCH_FIELD)->concat(CustomerEnum::CUSTOMER_HEIGHT_SEAS_SEARCH_FIELD),
            ViewSearchEnum::VIEW_CONTRACT      => $baseFields->concat(ContractEnum::CONTRACT_SEARCH_FIELD),
            ViewSearchEnum::VIEW_CLUE          => $baseFields->concat(ClueEnum::CLUE_SEARCH_FIELD)->concat(ClueEnum::CLUE_HEIGHT_SEARCH_FIELD),
            ViewSearchEnum::VIEW_CLUE_SEAS     => $baseFields->concat(ClueEnum::CLUE_SEARCH_FIELD)->concat(ClueEnum::CLUE_SEAS_SEARCH_FIELD),
            ViewSearchEnum::VIEW_ODDS          => $baseFields->concat(OddsEnum::ODDS_SEARCH_FIELD),
            ViewSearchEnum::VIEW_PRODUCT       => $baseFields->concat(ProductEnum::PRODUCT_CHARGE_SEARCH_FIELD),
            ViewSearchEnum::VIEW_LIAISON       => $baseFields->concat(LiaisonEnum::LIAISON_SEARCH_FIELD),
            default                            => $baseFields,
        };
        unset($where['types']);
        // 处理查询条件
        $whereKeys = collect(array_keys($where))->filter(fn ($key) => $key !== 'types');
        $mergedFields->filter(fn ($fieldInfo) => $whereKeys->contains($fieldInfo['field'] ?? null))
            ->each(function ($fieldInfo) use (&$where) {
                $fieldName = $fieldInfo['field'];
                if (! isset($where[$fieldName])) {
                    return;
                }
                // 移除空值条件
                if ($where[$fieldName] === '') {
                    unset($where[$fieldName]);
                    return;
                }
                // 处理字段值格式
                $fieldType         = $fieldInfo['type'] ?? '';
                $where[$fieldName] = [
                    'input_type' => $fieldInfo['input_type'] ?? '',
                    'value'      => $this->normalizeSearchFieldValue($fieldName, $where[$fieldName], $fieldType),
                    'type'       => $fieldType,
                ];
            });
        return $where;
    }

    private function normalizeSearchFieldValue(string $fieldName, mixed $value, string $fieldType): mixed
    {
        if ($fieldName === 'area_cascade') {
            return $this->normalizeAreaCascadeSearchValues($value);
        }

        if ($fieldType !== 'multiple' || ! is_array($value)) {
            return $value;
        }

        return collect($value)
            ->flatMap(fn ($item) => is_array($item) ? $item : [$item])
            ->values()
            ->all();
    }

    private function getInputSearch($dao, $field, $value)
    {
        $value = is_array($value) ? $value['value'] : $value;
        if (method_exists($this, 'search' . Str::studly($field))) {
            return $this->{'search' . Str::studly($field)}($dao, $value);
        }
        return $dao->where($field, 'like', "%{$value}%");
    }

    private function getSelectSearch($dao, $field, $value)
    {
        if (method_exists($this, 'search' . Str::studly($field))) {
            return $this->{'search' . Str::studly($field)}($dao, $value);
        }
        return is_array($value) ? $dao->whereIn($field, $value) : $dao->where($field, $value);
    }

    private function getPersonnelSearch($dao, $field, $value)
    {
        if (method_exists($this, 'search' . Str::studly($field))) {
            return $this->{'search' . Str::studly($field)}($dao, $value);
        }
        return is_array($value) ? $dao->whereIn($field, $value) : $dao->where($field, $value);
    }

    /**
     * 省市区查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     */
    public function searchAreaCascade($dao, $value)
    {
        $value = $this->normalizeAreaCascadeSearchValues($value);

        return $value ? $dao->where(function ($query) use ($value) {
            foreach ($value as $item) {
                $query->orWhereJsonContains('area_cascade', (string) $item);
                if (is_numeric($item)) {
                    $query->orWhereJsonContains('area_cascade', (int) $item);
                }
            }
        }) : $dao;
    }

    private function normalizeAreaCascadeSearchValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value   = json_last_error() === JSON_ERROR_NONE ? $decoded : [$value];
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->map(function ($item) {
                if (is_string($item)) {
                    $decoded = json_decode($item, true);
                    $item    = json_last_error() === JSON_ERROR_NONE ? $decoded : $item;
                }

                return is_array($item) ? collect($item)->filter(fn ($val) => $val !== null && $val !== '')->last() : $item;
            })
            ->filter(fn ($item) => $item !== null && $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function getMemberSearch($dao, $field, $value)
    {
        if (method_exists($this, 'search' . Str::studly($field))) {
            return $this->{'search' . Str::studly($field)}($dao, $value);
        }
        if (is_array($value)) {
            $dao->where(function ($query) use ($value, $field) {
                foreach ($value as $v) {
                    $query->orWhereJsonContains($field, (string) $v)->orWhereJsonContains($field, $v);
                }
            });
        } else {
            $dao->where(function ($query) use ($value, $field) {
                $query->orWhereJsonContains($field, (string) $value)->orWhereJsonContains($field, $value);
            });
        }
        return $dao;
    }

    private function getMoreSelectSearch(mixed $dao, int|string $field, mixed $value, mixed $type)
    {
        if ($type == 'multiple') {
            return $this->getMultipleSelectSearch($dao, $field, $value);
        }
        return $this->getSelectSearch($dao, $field, $value);
    }

    private function getMultipleSelectSearch($dao, $field, $value)
    {
        if (method_exists($this, 'search' . Str::studly($field))) {
            return $this->{'search' . Str::studly($field)}($dao, $value);
        }
        if (is_array($value)) {
            $dao->where(function ($query) use ($value, $field) {
                foreach ($value as $v) {
                    $query->orWhereJsonContains($field, (string) $v)->orWhereJsonContains($field, $v);
                }
            });
        } else {
            $dao->where(function ($query) use ($value, $field) {
                $query->orWhereJsonContains($field, (string) $value)->orWhereJsonContains($field, $value);
            });
        }
        return $dao;
    }
}
