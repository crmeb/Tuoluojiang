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

use App\Constants\CustomEnum\InvoiceEnum as CustomerInvoiceEnum;
use App\Http\Dao\Customer\InvoiceLogDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户发票操作日志
 * @mixin InvoiceLogDao.
 */
class InvoiceLogService extends BaseService
{
    use ResourceServiceTrait;

    public const TYPE_WITHDRAW = -1;
    public const TYPE_APPLY = 0;
    public const TYPE_APPROVED = 1;
    public const TYPE_REJECTED = 2;
    public const TYPE_APPLY_CANCEL = 3;
    public const TYPE_CANCEL_APPROVED = 4;
    public const TYPE_CANCEL_REJECTED = 5;
    public const TYPE_CANCEL_WITHDRAW = 6;
    public const TYPE_REAPPLY = 7;
    public const TYPE_INVOICED = 8;

    /**
     * @var array|string[] 发送方式
     */
    public array $invoiceType = [
        'mail'    => '邮件',
        'express' => '快递',
    ];

    /**
     * @var array|string[] 发票类型
     */
    public array $types = [
        1 => '个人普通发票',
        2 => '企业普通发票',
        3 => '企业专用发票',
    ];

    /**
     * @var array|string[] 操作记录
     */
    public array $operationType = [
        self::TYPE_WITHDRAW        => '撤回开票申请',
        self::TYPE_APPLY           => '申请开票',
        self::TYPE_APPROVED        => '审核开票申请',
        self::TYPE_REJECTED        => '审核开票申请',
        self::TYPE_APPLY_CANCEL    => '申请发票冲红',
        self::TYPE_CANCEL_APPROVED => '审核发票冲红',
        self::TYPE_CANCEL_REJECTED => '审核发票冲红',
        self::TYPE_CANCEL_WITHDRAW => '撤回发票冲红',
        self::TYPE_REAPPLY         => '重新申请开票',
        self::TYPE_INVOICED        => '记录开票结果',
    ];

    public function __construct(InvoiceLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param string $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'created_at', array $with = ['card']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $invoiceStatus  = $this->getInvoiceStatusMap($list);
        foreach ($list as &$item) {
            $invoiceId = $item['invoice_id'] ?? 0;
            $item      = $this->normalizeRecordForDisplay($item, $invoiceStatus[$invoiceId] ?? null);
        }
        $count = $this->dao->count($where);
        if ($page <= 1 && ($invoiceRecord = $this->makeMissingInvoiceResultRecord($where, $list))) {
            array_unshift($list, $invoiceRecord);
            ++$count;
        }
        return $this->listData($list, $count);
    }

    /**
     * 兼容历史日志展示.
     */
    public function normalizeRecordForDisplay(array $item, ?int $invoiceStatus = null): array
    {
        $type                  = (int) ($item['type'] ?? 0);
        $operation             = (array) ($item['operation'] ?? []);
        $item['operation']     = $this->normalizeOperation($type, $operation, $invoiceStatus);
        $item['operation_name'] = $this->resolveOperationName($type, $item['operation'], $invoiceStatus);
        return $item;
    }

    /**
     * 保存操作记录.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function saveRecord(int $entId, int $invoiceId, int $uid, int $type, array $param)
    {
        if (! isset($this->operationType[$type])) {
            throw $this->exception('操作类型错误');
        }

        $res = $this->dao->create([
            'uid'        => $uid,
            'type'       => $type,
            'entid'      => $entId,
            'invoice_id' => $invoiceId,
            'operation'  => $this->generatorOperation($invoiceId, $type, $param),
        ]);
        if (! $res) {
            throw $this->exception('操作记录添加失败');
        }
        return $res;
    }

    /**
     * 生成操作记录内容.
     * @throws BindingResolutionException
     */
    public function generatorOperation(int $invoiceId, int $type, array $param): array
    {
        return match ($type) {
            self::TYPE_WITHDRAW => [
                ['name' => '备注：', 'val' => $param['remark'] ?? ''],
            ],
            self::TYPE_APPLY => [],
            self::TYPE_APPROVED => [
                ['name' => '开票结果：', 'val' => '待开票'],
            ],
            self::TYPE_REJECTED => [
                ['name' => '开票结果：', 'val' => '拒绝'],
                ['name' => '拒绝原因：', 'val' => $param['remark'] ?? ''],
            ],
            self::TYPE_APPLY_CANCEL => [
                ['name' => '冲红原因：', 'val' => $param['card_remark'] ?? ''],
            ],
            self::TYPE_CANCEL_APPROVED => [
                ['name' => '审核结果：', 'val' => '已冲红'],
                ['name' => '备注：', 'val' => $param['finance_remark'] ?? ''],
            ],
            self::TYPE_CANCEL_REJECTED => [
                ['name' => '审核结果：', 'val' => '已拒绝'],
                ['name' => '拒绝原因：', 'val' => $param['finance_remark'] ?? ''],
            ],
            self::TYPE_CANCEL_WITHDRAW => [],
            self::TYPE_REAPPLY         => $this->getInvoiceChange($invoiceId, $param),
            self::TYPE_INVOICED        => $this->getInvoiceResult($invoiceId, $param),
            default => [],
        };
    }

    /**
     * 获取开票结果.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getInvoiceResult(int $invoiceId, array $param): array
    {
        $info = app(InvoiceService::class)->get($invoiceId, ['*'], ['attachs'])?->toArray() ?? [];

        $invoiceType    = $param['invoice_type'] ?? $info['invoice_type'] ?? $param['collect_type'] ?? $info['collect_type'] ?? '';
        $invoiceAddress = $param['invoice_address'] ?? '';
        if (! $invoiceAddress) {
            $invoiceAddress = $param['invoice_mail'] ?? '';
        }
        if (! $invoiceAddress) {
            $invoiceAddress = $invoiceType == 'mail'
                ? ($param['collect_email'] ?? $info['collect_email'] ?? '')
                : ($param['mail_address'] ?? $info['mail_address'] ?? '');
        }
        $record      = [
            ['name' => '开票结果：', 'val' => '已开票'],
        ];
        $this->appendOperationItem($record, '发票号码：', $param['invoice_num'] ?? $param['num'] ?? $info['num'] ?? '');
        $this->appendOperationItem($record, '发票流水号：', $param['invoice_serial_number'] ?? $param['serial_number'] ?? $info['serial_number'] ?? '');
        $this->appendOperationItem($record, '发票金额：', $info['amount'] ?? $param['amount'] ?? '');
        $this->appendOperationItem($record, '发票类型：', $this->types[(int) ($info['types'] ?? $param['types'] ?? 0)] ?? '');
        $this->appendOperationItem($record, '发票抬头：', $info['title'] ?? $param['title'] ?? '');
        $this->appendOperationItem($record, '纳税人识别号：', $info['ident'] ?? $param['ident'] ?? '');
        $this->appendOperationItem($record, '实际开票日期：', $info['real_date'] ?? $param['real_date'] ?? '');
        $this->appendOperationItem($record, '发送方式：', $this->invoiceType[$invoiceType] ?? '');

        if ($invoiceType == 'express') {
            $this->appendOperationItem($record, '联系人：', $param['collect_name'] ?? $info['collect_name'] ?? '');
            $this->appendOperationItem($record, '联系电话：', $param['collect_tel'] ?? $info['collect_tel'] ?? '');
        }
        $this->appendOperationItem($record, $invoiceType == 'mail' ? '邮箱地址：' : '邮寄地址：', $invoiceAddress);
        $this->appendOperationItem($record, '开票备注：', $param['remark'] ?? $info['remark'] ?? '');
        $attachIds = array_filter((array) ($param['attach_ids'] ?? []));
        if ($attachIds) {
            $attachs = app(AttachService::class)->select(['id' => $attachIds])?->toArray() ?? [];
        } elseif (array_key_exists('attach_ids', $param)) {
            $attachs = [];
        } else {
            $attachs = $info['attachs'] ?? [];
        }
        $file = collect($attachs)
            ->map(fn ($attach) => $attach['url'] ?? $attach['thumb_dir'] ?? $attach['att_dir'] ?? '')
            ->filter()
            ->values()
            ->all();
        if ($file) {
            $record[] = ['name' => '开票凭证：', 'val' => $file];
        }
        return $record;
    }

    private function appendOperationItem(array &$record, string $name, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $record[] = ['name' => $name, 'val' => $value];
    }

    /**
     * 获取日志对应发票状态.
     */
    private function getInvoiceStatusMap(array $list): array
    {
        $invoiceIds = collect($list)
            ->where('type', self::TYPE_APPROVED)
            ->pluck('invoice_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
        if (! $invoiceIds) {
            return [];
        }
        return app(InvoiceService::class)->column(['id' => $invoiceIds], 'status', 'id');
    }

    private function normalizeOperation(int $type, array $operation, ?int $invoiceStatus): array
    {
        if ($type === self::TYPE_APPROVED && ! $this->isInvoiceResultOperation($operation, $invoiceStatus)) {
            return [
                ['name' => '开票结果：', 'val' => '待开票'],
            ];
        }
        return $operation;
    }

    private function resolveOperationName(int $type, array $operation, ?int $invoiceStatus): string
    {
        if ($type === self::TYPE_APPROVED && $this->isInvoiceResultOperation($operation, $invoiceStatus)) {
            return $this->operationType[self::TYPE_INVOICED];
        }
        return $this->operationType[$type] ?? '';
    }

    private function isInvoiceResultOperation(array $operation, ?int $invoiceStatus): bool
    {
        if ($invoiceStatus === CustomerInvoiceEnum::STATUS_APPROVED) {
            return false;
        }
        return $this->operationValue($operation, '开票结果') === '已开票'
            && (
                $this->hasOperationValue($operation, '发送方式')
                || $this->operationValue($operation, '开票凭证') !== null
                || $this->hasOperationValue($operation, '邮箱地址')
                || $this->hasOperationValue($operation, '邮寄地址')
            );
    }

    private function hasOperationValue(array $operation, string $name): bool
    {
        $value = $this->operationValue($operation, $name);
        return $value !== null && $value !== '';
    }

    private function operationValue(array $operation, string $name): mixed
    {
        foreach ($operation as $item) {
            $itemName = trim((string) ($item['name'] ?? ''), "：: \t\n\r\0\x0B");
            if ($itemName === $name) {
                return $item['val'] ?? null;
            }
        }
        return null;
    }

    /**
     * 兼容已开票但历史上未生成开票结果日志的数据.
     */
    private function makeMissingInvoiceResultRecord(array $where, array $list): ?array
    {
        $invoiceId = (int) ($where['invoice_id'] ?? 0);
        if (! $invoiceId) {
            return null;
        }

        foreach ($list as $item) {
            $type      = (int) ($item['type'] ?? 0);
            $operation = (array) ($item['operation'] ?? []);
            if ($type === self::TYPE_INVOICED || $this->isInvoiceResultOperation($operation, null)) {
                return null;
            }
        }

        $invoice = app(InvoiceService::class)->get($invoiceId, ['*'], ['card', 'attachs'])?->toArray();
        if (! $invoice || (int) ($invoice['status'] ?? 0) !== CustomerInvoiceEnum::STATUS_INVOICED) {
            return null;
        }

        $createdAt = $invoice['real_date'] ?? $invoice['updated_at'] ?? $invoice['created_at'] ?? null;
        return [
            'id'             => 0,
            'uid'            => $invoice['uid'] ?? 0,
            'type'           => self::TYPE_INVOICED,
            'entid'          => $invoice['entid'] ?? 0,
            'invoice_id'     => $invoiceId,
            'operation'      => $this->getInvoiceResult($invoiceId, $invoice),
            'operation_name' => $this->operationType[self::TYPE_INVOICED],
            'created_at'     => $createdAt,
            'updated_at'     => $createdAt,
            'card'           => $invoice['card'] ?? null,
        ];
    }

    /**
     * 获取发票变更.
     * @throws BindingResolutionException
     */
    public function getInvoiceChange(int $invoiceId, array $before): ?array
    {
        $record = [];
        $info   = app(InvoiceService::class)->get($invoiceId, ['*'], ['category', 'clientBill'])->toArray();

        $beforeBillNo = array_column($before['client_bill'], 'bill_no');
        $afterBillNo  = array_column($info['client_bill'], 'bill_no');
        $beforeBillNo != $afterBillNo
        && $record[] = ['name' => '付款单号：', 'val' => implode('、', $beforeBillNo) . ' 改为 ' . implode('、', $afterBillNo)];

        $before['category_id'] != $info['category_id']
        && $record[] = ['name' => '发票类目：', 'val' => ($before['category']['name'] ?? '') . ' 改为 ' . ($info['category']['name'] ?? '')];

        $before['bill_date'] != $info['bill_date']
        && $record[] = ['name' => '期望开票日期：', 'val' => $before['bill_date'] . ' 改为 ' . $info['bill_date']];

        $before['price'] != $info['price']
        && $record[] = ['name' => '开票金额：', 'val' => $before['price'] . ' 改为 ' . $info['price']];

        $before['amount'] != $info['amount']
        && $record[] = ['name' => '发票金额：', 'val' => $before['amount'] . ' 改为 ' . $info['amount']];

        $before['types'] != $info['types']
        && $record[] = ['name' => '发票金额：', 'val' => ($this->types[$before['types']] ?? '') . ' 改为 ' . $this->types[$info['types']] ?? ''];

        $before['bank'] != $info['bank']
        && $record[] = ['name' => '开户行：', 'val' => $before['bank'] . ' 改为 ' . $info['bank']];

        $before['account'] != $info['account']
        && $record[] = ['name' => '开户账号：', 'val' => $before['account'] . ' 改为 ' . $info['account']];

        $before['address'] != $info['address']
        && $record[] = ['name' => '开票地址：', 'val' => $before['address'] . ' 改为 ' . $info['address']];

        $before['tel'] != $info['tel']
        && $record[] = ['name' => '电话：', 'val' => $before['tel'] . ' 改为 ' . $info['tel']];

        $before['title'] != $info['title']
        && $record[] = ['name' => '发票抬头：', 'val' => $before['title'] . ' 改为 ' . $info['title']];

        $before['ident'] != $info['ident']
        && $record[] = ['name' => '纳税人识别号：', 'val' => $before['ident'] . ' 改为 ' . $info['ident']];

        $before['collect_type'] != $info['collect_type']
        && $record[] = ['name' => '发送方式：', 'val' => $this->invoiceType[$before['collect_type']] . ' 改为 ' . $this->invoiceType[$info['collect_type']]];

        $before['collect_name'] != $info['collect_name']
        && $record[] = ['name' => '联系人：', 'val' => $before['collect_name'] . ' 改为 ' . $info['collect_name']];

        $before['collect_tel'] != $info['collect_tel']
        && $record[] = ['name' => '联系电话：', 'val' => $before['collect_tel'] . ' 改为 ' . $info['collect_tel']];

        $before['collect_email'] != $info['collect_email']
        && $record[] = ['name' => '邮箱地址：', 'val' => $before['collect_email'] . ' 改为 ' . $info['collect_email']];

        $before['mail_address'] != $info['mail_address']
        && $record[] = ['name' => '邮寄地址：', 'val' => $before['mail_address'] . ' 改为 ' . $info['mail_address']];
        return $record;
    }
}
