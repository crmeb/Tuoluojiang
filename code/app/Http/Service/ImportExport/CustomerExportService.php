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

namespace App\Http\Service\ImportExport;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Dao\Admin\AdminDao;
use App\Http\Dao\Customer\CustomerDao;
use App\Http\Dao\Customer\LabelDao;
use App\Http\Dao\Customer\LiaisonDao;
use App\Http\Dao\Customer\OpportunityDao;
use App\Http\Dao\Customer\ProductCategoryDao;
use App\Http\Dao\Work\WorkClientDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Customer\PaymentService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\InvoiceService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\RecordService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Config\SalesmanCustomService;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\services\export\BaseExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;

/**
 * 客户导出.
 */
class CustomerExportService extends BaseExport
{
    protected array $customType = [
        ViewSearchEnum::VIEW_CUSTOMER      => CustomEnum::CUSTOMER,
        ViewSearchEnum::VIEW_CUSTOMER_SEAS => CustomEnum::CUSTOMER,
        ViewSearchEnum::VIEW_CONTRACT      => CustomEnum::CONTRACT,
        ViewSearchEnum::VIEW_LIAISON       => CustomEnum::LIAISON,
        ViewSearchEnum::VIEW_CLUE          => CustomEnum::CLUE,
        ViewSearchEnum::VIEW_CLUE_SEAS     => CustomEnum::CLUE,
        ViewSearchEnum::VIEW_ODDS          => CustomEnum::ODDS,
        ViewSearchEnum::VIEW_PRODUCT       => CustomEnum::PRODUCT,
    ];

    private array $selectField;

    private BaseDao $dao;

    public function __construct(protected int $uid, protected array $where = [], protected array $searchField = [], protected array $otherField = [], protected string $types = ViewSearchEnum::VIEW_CUSTOMER, protected mixed $service = null, protected int $recordId = 0)
    {
        $this->dao         = $this->service->dao;
        $this->selectField = collect($this->searchField)->filter(fn ($item) => ! in_array($item, ['contract_followed', 'followed', 'customer_followed']))->all();
        $this->moduleName  = match ($types) {
            ViewSearchEnum::VIEW_CUSTOMER      => '客户',
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => '公海客户',
            ViewSearchEnum::VIEW_CONTRACT      => '订单',
            ViewSearchEnum::VIEW_LIAISON       => '联系人',
            ViewSearchEnum::VIEW_CLUE          => '线索',
            ViewSearchEnum::VIEW_CLUE_SEAS     => '线索池',
            ViewSearchEnum::VIEW_ODDS          => '商机',
            ViewSearchEnum::VIEW_PRODUCT       => '产品',
            default                            => '未知',
        };
        parent::__construct($this->recordId);
    }

    public function setHeadings(): array
    {
        $fields = app(SalesmanCustomService::class)->salesmanCustomField($this->uid, $this->types);

        // 确保只对字符串和整型值进行翻转
        $listSelect = collect($fields['list_select'] ?? [])
            ->filter(function ($item) {
                return is_string($item) || is_int($item);
            })
            ->values()
            ->all();

        $fieldOrderMap = collect($listSelect)->flip()->toArray();

        $listField = collect($fields['list'] ?? [])->filter(fn ($item) => in_array($item['field'], $listSelect) && ! in_array($item['field'], ['contract_followed', 'followed', 'customer_followed']))
            ->sortBy(function ($item) use ($fieldOrderMap) {
                return $fieldOrderMap[$item['field']] ?? 9999; // 如果找不到排序值，则放到最后
            })->values();
        $this->selectField = $listField->pluck('field')->unique()->all();
        return $listField->pluck('name')->all();
    }

    public function setDataCallback(): callable
    {
        return function () {
            $lazyCollection = $this->dao->listSearch($this->where, uid: $this->uid)->select($this->searchField)->cursor();
            $dataId         = [];
            $externalUserid = [];
            $eid            = [];
            $oid            = [];
            $pid            = [];
            $customerLabel  = [];
            $lazyCollection->each(function ($row) use (&$dataId, &$externalUserid, &$eid, &$oid, &$pid, &$customerLabel) {
                $row['id'] && $dataId[]                      = $row['id'];
                $row['external_userid'] && $externalUserid[] = $row['external_userid'];
                $row['eid'] && $eid[]                        = $row['eid'];
                $row['oid'] && $oid[]                        = $row['oid'];
                $row['pid'] && $pid[]                        = $row['pid'];
                $row['customer_label'] && $customerLabel[]   = $row['customer_label'];
            });
            $dataId         = collect($dataId)->unique()->filter()->values()->all();
            $externalUserid = collect($externalUserid)->unique()->filter()->values()->all();
            $eid            = collect($eid)->unique()->filter()->values()->all();
            $oid            = collect($oid)->unique()->filter()->values()->all();
            $pid            = collect($pid)->unique()->filter()->values()->all();
            $customerLabel  = collect($customerLabel)->unique()->filter()->values()->all();
            unset($lazyCollection, $row);

            // 表单字段处理
            $formFields = collect(app(FormService::class)->getCustomDataByTypes($this->customType[$this->types], ['key', 'key_name', 'input_type', 'type', 'dict_ident']))
                ->filter(fn ($item) => ! in_array($item['input_type'], ['file', 'images', 'oawangeditor']))->all();
            $formFieldCollection = collect($formFields);
            $inputTypes          = $formFieldCollection->pluck('input_type', 'key')->all();
            $types               = $formFieldCollection->pluck('type', 'key')->all();
            // 字典字段
            $dictField = $formFieldCollection->pluck('dict_ident', 'key')->all();
            // 本地字段
            $localField = ['id', 'uid', 'userid', 'external_userid', 'creator_uid', 'before_uid', 'eid', 'surplus', 'pid'];
            $dataMap    = [];
            foreach (array_merge($this->otherField, $this->searchField) as $field) {
                switch ($field) {
                    case 'salesman':
                    case 'creator':
                    case 'before_salesman':
                    case 'member':
                        if (! isset($dataMap[$field])) {
                            $dataMap['salesman'] = $dataMap['creator'] = $dataMap['before_salesman'] = $dataMap['member'] = LazyCollection::make(app(AdminDao::class)->select(['status' => 1], ['id', 'name'], cursor: true))->pluck('name', 'id')->all();
                        }
                        break;
                    case 'work_customer':
                        $dataMap['work_customer'] = LazyCollection::make(app(WorkClientDao::class)->select(['external_userid' => $externalUserid], ['name', 'external_userid'], cursor: true))->pluck('name', 'external_userid')->all();
                        break;
                    case 'liaison_tel':
                        if ($this->types !== ViewSearchEnum::VIEW_LIAISON) {
                            $dataMap['liaison_tel'] = LazyCollection::make(app(LiaisonDao::class)->select(['eid' => $dataId], ['eid', 'liaison_name', 'liaison_tel'], cursor: true))->mapWithKeys(function ($item) {
                                $value                                                            = [];
                                isset($item['liaison_name']) && $item['liaison_name'] && $value[] = $item['liaison_name'];
                                isset($item['liaison_tel']) && $item['liaison_tel'] && $value[]   = $item['liaison_tel'];
                                return [$item['eid'] => $value ? implode(':', $value) : []];
                            })->all();
                        }
                        break;
                    case 'contract_customer':
                    case 'eid':
                        $dataMap['eid'] = $dataMap['contract_customer'] = LazyCollection::make(app(CustomerDao::class)->select(['id' => $eid], ['customer_name', 'id'], cursor: true))->pluck('customer_name', 'id')->all();
                        break;
                    case 'oid':
                        $dataMap['oid'] = LazyCollection::make(app(OpportunityDao::class)->select(['id' => $oid], ['odds_no', 'id'], cursor: true))->pluck('odds_no', 'id')->all();
                        break;
                    case 'bill_no':
                        $dataMap['bill_no'] = LazyCollection::make(app(PaymentService::class)->select(['cid' => $dataId], ['bill_no', 'cid', 'id'], cursor: true))->pluck('bill_no', 'cid')->all();
                        break;
                    case 'payment_time':
                        $dataMap['payment_time'] = LazyCollection::make(app(PaymentService::class)->select(['status' => 1, 'cid' => $dataId], ['date', 'cid'], cursor: true))->pluck('date', 'cid')->all();
                        break;
                    case 'last_follow_time':
                        $dataMap['last_follow_time'] = collect(app(FollowUpService::class)->getLastFollow(['eid' => $dataId, 'types' => 0, 'link_type' => 'customer']))
                            ->pluck('created_at', 'eid')->map(fn ($carbon) => $carbon instanceof Carbon ? $carbon->toDateTimeString() : '')->all();
                        break;
                    case 'un_followed_days':
                        $dataMap['un_followed_days'] = collect(app(FollowUpService::class)->getLastFollow(['eid' => $dataId, 'types' => 0, 'link_type' => 'customer']))
                            ->pluck('created_at', 'eid')->map(fn ($carbon) => $carbon instanceof Carbon ? $carbon->startOfDay()->diffInDays(now(), false) : '')->all();
                        break;
                    case 'amount_recorded':
                        $dataMap['amount_recorded'] = collect(app(PaymentService::class)->getBillSum(['eid' => $dataId, 'status' => 1, 'types' => [0, 1]]))->pluck('total', 'eid')->all();
                        break;
                    case 'amount_expend':
                        $dataMap['amount_expend'] = collect(app(PaymentService::class)->getBillSum(['eid' => $dataId, 'status' => 1, 'types' => 2]))->pluck('total', 'eid')->all();
                        break;
                    case 'invoiced_amount':
                        $dataMap['invoiced_amount'] = collect(app(InvoiceService::class)->getInvoiceNum(['eid' => $dataId, 'status' => [1, 3, 5, 6]]))->pluck('total', 'eid')->all();
                        break;
                    case 'invoice_num':
                        $dataMap['invoice_num'] = collect(app(InvoiceService::class)->getInvoiceNum(['eid' => $dataId, 'status' => [1, 3, 5, 6]], field: 'eid, COUNT(id) as count'))->pluck('count', 'eid')->all();
                        break;
                    case 'contract_num':
                        $dataMap['contract_num'] = collect(app(OrderService::class)->getContractNum(['eid' => $dataId]))->pluck('count', 'eid')->all();
                        break;
                    case 'attachment_num':
                        $dataMap['attachment_num'] = collect(app(AttachService::class)->getAttachNum(['relation_id' => $dataId, 'relation_type' => [2, 3, 4, 5, 6]]))->pluck('count', 'relation_id')->all();
                        break;
                    case 'return_reason':
                        $dataMap['return_reason'] = LazyCollection::make(app(RecordService::class)->select(['eid' => $dataId, 'type' => $this->customType[$this->types], 'link_type' => str_replace('_seas', '', $this->types)], ['reason', 'eid'], cursor: true))->pluck('reason', 'eid')->all();
                        break;
                    case 'path':
                        $dataMap['path'] = LazyCollection::make(app(ProductCategoryDao::class)->select(['id' => $pid], ['name', 'id'], cursor: true))->pluck('name', 'id')->all();
                        break;
                    case 'customer_label':
                        $dataMap['customer_label'] = LazyCollection::make(app(LabelDao::class)->select(['id' => collect($customerLabel ?? [])->flatten()->all()], ['name', 'id'], cursor: true))->pluck('name', 'id')->all();
                        break;
                }
            }
            $mainLazyCollection = $this->dao->listSearch($this->where, uid: $this->uid)->select($this->searchField)->cursor();
            foreach ($mainLazyCollection as $value) {
                $arr = [];
                foreach ($this->selectField as $field) {
                    $arr[$field] = $value[$field] ?? '';
                }
                $result = $this->processRowData($arr, $localField, $dataMap, $inputTypes, $types, $dictField, $value);

                yield $result;
            }
            unset($value,$mainLazyCollection);
        };
    }

    /**
     * 处理单行数据.
     * @param array $arr 原始字段数组
     * @param array $localField 本地字段
     * @param array $dataMap 预加载的关联数据
     * @param array $inputTypes 输入类型
     * @param array $types 字段类型
     * @param array $dictField 字典字段
     * @param array $value 原始行数据
     */
    private function processRowData(array $arr, array $localField, array $dataMap, array $inputTypes, array $types, array $dictField, mixed $value): array
    {
        $result = [];
        foreach ($arr as $key => $item) {
            // 1. 字段类型/字典处理（简化条件判断）
            if ((! in_array($key, $localField) && isset($inputTypes[$key]) && $key != 'followed') || $key == 'contract_category') {
                $inputType = strtolower($inputTypes[$key]);
                $type      = strtolower($types[$key]);
                $item      = $this->service->handleFieldValue($inputType, $type, $item);

                // 字典值处理（简化判断）
                if (! empty($dictField[$key])) {
                    $item = $this->service->handleDictValue($dictField[$key], $item, $type, $inputType);
                    $item = is_array($item) ? ($item['name'] ?? implode('/', $item)) : $item;
                }
                if ($item instanceof BaseModel) {
                    $item = $item->name ?? $item;
                }
                // 数组转字符串（简化集合操作）
                if (is_array($item)) {
                    $item = ! empty($item['name']) ? implode('、', array_column($item, 'name')) : json_encode($item, JSON_UNESCAPED_UNICODE);
                }
            }
            $item     = $this->mapFieldValue($key, $item, $dataMap, $value);
            if ($key === 'phone' && $this->types === ViewSearchEnum::VIEW_CLUE_SEAS) {
                $item = mask_phone(is_scalar($item) || $item === null ? (string) ($item ?? '') : '');
            }
            $item     = $this->formatValue($item);
            $result[] = $item;
        }
        return $result;
    }

    /**
     * 字段值映射.
     * @param string $key 字段名
     * @param mixed $item 原始值
     * @param array $dataMap 预加载数据
     * @param array $value 原始行数据
     */
    private function mapFieldValue(string $key, $item, array $dataMap, mixed $value): mixed
    {
        $map = [
            'customer_label'    => fn () => collect($dataMap[$key] ?? [])->only($value[$key])->values()->implode('、') ?: '',
            'liaison_tel'       => fn () => $this->types === ViewSearchEnum::VIEW_LIAISON ? $item : ($dataMap[$key][$value['id']] ?? ''),
            'un_followed_days'  => fn () => $dataMap[$key][$value['id']] ?? '',
            'last_follow_time'  => fn () => $dataMap[$key][$value['id']] ?? '',
            'return_reason'     => fn () => $dataMap[$key][$value['id']] ?? '',
            'bill_no'           => fn () => $dataMap[$key][$value['id']] ?? '',
            'amount_recorded'   => fn () => $dataMap[$key][$value['id']] ?? '0.00',
            'amount_expend'     => fn () => $dataMap[$key][$value['id']] ?? '0.00',
            'invoiced_amount'   => fn () => $dataMap[$key][$value['id']] ?? '0.00',
            'contract_num'      => fn () => $dataMap[$key][$value['id']] ?? 0,
            'invoice_num'       => fn () => $dataMap[$key][$value['id']] ?? 0,
            'attachment_num'    => fn () => $dataMap[$key][$value['id']] ?? 0,
            'customer_followed' => fn () => ($dataMap[$key][$value['id']] ?? false) ? '关注' : '未关注',
            'contract_followed' => fn () => ($dataMap[$key][$value['id']] ?? false) ? '关注' : '未关注',
            'followed'          => fn () => ($dataMap[$key][$value['id']] ?? false) ? '关注' : '未关注',
            'salesman'          => fn () => $dataMap[$key][$value['uid']] ?? '',
            'creator'           => fn () => $dataMap[$key][$value['creator_uid']] ?? '',
            'before_salesman'   => fn () => $dataMap[$key][$value['before_uid']] ?? '',
            'work_customer'     => fn () => $dataMap[$key][$value['external_userid']] ?? '',
            'contract_customer' => fn () => $dataMap[$key][$value['eid']] ?? '',
            'oid'               => fn () => $dataMap[$key][$value['oid']] ?? '',
            'eid'               => fn () => $dataMap[$key][$value['eid']] ?? '',
            'payment_time'      => fn () => $dataMap[$key][$value['id']] ?? '',
            'path'              => fn () => $dataMap[$key][$value['pid']] ?? '',
            'member'            => fn () => collect($dataMap[$key] ?? [])->only($value[$key])->values()->implode('、') ?: '',
            'payment_status'    => fn () => bccomp($value['surplus'], '0', 2) === 0 ? '已结清' : '未结清',
            'total_amount'      => function () use ($value) {
                $total = collect($value['product'] ?? [])->pluck('total_price')->sum();
                return number_format($total, 2, '.', '');
            },
            'created_at' => fn () => $item instanceof \Carbon\Carbon ? $item->toDateTimeString() : ($item ?? ''),
        ];
        return isset($map[$key]) ? $map[$key]() : $item;
    }

    private function formatValue($value)
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '是' : '否';
        }
        if ($value instanceof \Carbon\Carbon) {
            return $value->toDateTimeString();
        }
        if (is_object($value)) {
            $value = $value ? $value->toArray() : [];
        }
        if (is_array($value)) {
            return collect($value)->pluck('name')->implode('、') ?? json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }
}
