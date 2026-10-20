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

namespace App\Task\approve;

use App\Constants\ApproveEnum;
use App\Constants\Crud\CrudTriggerEnum;
use App\Constants\CustomEnum\InvoiceEnum;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Customer\PaymentService;
use App\Http\Service\Customer\InvoiceService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\RemindService;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Finance\PaytypeService;
use App\Task\customer\InvoiceRecordTask;
use App\Task\financial\FinancialRecordTask;
use Tuoluojiang\Open\exceptions\ApiException;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 审批保存后置事件
 * Class ApplySavedTask.
 */
class ApplySavedTask extends Task
{
    public function __construct(protected int $applyId, protected array $forms) {}

    public function handle(): void
    {
        try {
            $applyService = app()->get(ApproveApplyService::class);
            $approveInfo  = $applyService->get($this->applyId, ['approve_id', 'status', 'user_id', 'entid', 'crud_id', 'link_id'])?->toArray();
            if (! $approveInfo) {
                throw new ApiException('无效的审批信息');
            }
            $types       = app()->get(ApproveService::class)->value($approveInfo['approve_id'], 'types');
            $billService = app()->get(PaymentService::class);
            $save        = $attachs = [];
            switch ($types) {
                case ApproveEnum::CUSTOMER_CONTRACT_PAYMENT:// 回款
                    foreach ($this->forms as $form) {
                        if ($form['symbol']) {
                            if (ApproveEnum::CUSTOMER_FORM_PAYMENT[$form['symbol']] === 'attach') {
                                $attachs = $form['value'];
                            } else {
                                $save[ApproveEnum::CUSTOMER_FORM_PAYMENT[$form['symbol']]] = $form['value'];
                            }
                        }
                    }
                    if ($save) {
                        $save['bill_no']      = $billService->generateNo($this->applyId);
                        $save['bill_types']   = 1;
                        $save['types']        = 0;
                        $save['entid']        = $approveInfo['entid'];
                        $save['uid']          = $approveInfo['user_id'];
                        $save['eid']          = app()->get(OrderService::class)->value($save['cid'], 'eid') ?: 0;
                        $save['status']       = $approveInfo['status'];
                        $save['apply_id']     = $this->applyId;
                        $save['bill_cate_id'] = is_array($save['bill_cate_id']) ? end($save['bill_cate_id']) : $save['bill_cate_id'];
                        $save['pay_type']     = isset($save['type_id']) ? app()->get(PaytypeService::class)->value($save['type_id'], 'name') : '';
                        $bill                 = $billService->create($save);
                        $this->completeRemind((int) $approveInfo['link_id'], 0);
                        $attach               = app()->get(AttachService::class);
                        $attachs && $attach->update(['id' => array_column($attachs, 'id')], ['relation_id' => $bill->id, 'relation_type' => 2]);
                        if ($approveInfo['status'] == 1) {
                            $billService->approveSuccess($this->applyId);
                            Task::deliver(new FinancialRecordTask($bill->id));
                        }
                    }
                    break;
                case ApproveEnum::CUSTOMER_CONTRACT_RENEWAL:// 续费
                    foreach ($this->forms as $form) {
                        if ($form['symbol']) {
                            if (ApproveEnum::CUSTOMER_FORM_RENEWAL[$form['symbol']] === 'attach') {
                                $attachs = $form['value'];
                            } else {
                                $save[ApproveEnum::CUSTOMER_FORM_RENEWAL[$form['symbol']]] = $form['value'];
                            }
                        }
                    }
                    if ($save) {
                        $save['bill_no']      = $billService->generateNo($this->applyId);
                        $save['bill_types']   = 1;
                        $save['types']        = 1;
                        $save['entid']        = $approveInfo['entid'];
                        $save['uid']          = $approveInfo['user_id'];
                        $save['eid']          = app()->get(OrderService::class)->value($save['cid'], 'eid');
                        $save['status']       = $approveInfo['status'];
                        $save['apply_id']     = $this->applyId;
                        $save['bill_cate_id'] = is_array($save['bill_cate_id']) ? end($save['bill_cate_id']) : $save['bill_cate_id'];
                        $save['pay_type']     = isset($save['type_id']) ? app()->get(PaytypeService::class)->value($save['type_id'], 'name') : '';
                        $bill                 = $billService->create($save);
                        $this->completeRemind((int) $approveInfo['link_id'], 1);
                        $attach               = app()->get(AttachService::class);
                        $attachs && $attach->update(['id' => array_column($attachs, 'id')], ['relation_id' => $bill->id, 'relation_type' => 2]);
                        if ($approveInfo['status'] == 1) {
                            Task::deliver(new FinancialRecordTask($bill->id));
                        }
                    }
                    break;
                case ApproveEnum::CUSTOMER_CONTRACT_EXPENSES:// 支出
                    foreach ($this->forms as $form) {
                        if ($form['symbol']) {
                            if (ApproveEnum::CUSTOMER_FORM_EXPENDITURE[$form['symbol']] === 'attach') {
                                $attachs = $form['value'];
                            } else {
                                $save[ApproveEnum::CUSTOMER_FORM_EXPENDITURE[$form['symbol']]] = $form['value'];
                            }
                        }
                    }
                    if ($save) {
                        $save['bill_no']      = $billService->generateNo($this->applyId);
                        $save['pay_type']     = isset($save['type_id']) ? app()->get(PaytypeService::class)->value($save['type_id'], 'name') : '';
                        $save['entid']        = $approveInfo['entid'];
                        $save['bill_types']   = 0;
                        $save['types']        = 2;
                        $save['uid']          = $approveInfo['user_id'];
                        $save['eid']          = app()->get(OrderService::class)->value($save['cid'], 'eid');
                        $save['status']       = $approveInfo['status'];
                        $save['apply_id']     = $this->applyId;
                        $save['bill_cate_id'] = is_array($save['bill_cate_id']) ? end($save['bill_cate_id']) : $save['bill_cate_id'];
                        $bill                 = $billService->create($save);
                        $attach               = app()->get(AttachService::class);
                        $attachs && $attach->update(['id' => array_column($attachs, 'id')], ['relation_id' => $bill->id, 'relation_type' => 2]);
                        if ($approveInfo['status'] == 1) {
                            Task::deliver(new FinancialRecordTask($bill->id));
                        }
                    }
                    break;
                case ApproveEnum::CUSTOMER_INVOICE_ISSUANCE:// 开票
                    $billIds = [];
                    foreach ($this->forms as $form) {
                        if ($form['symbol']) {
                            if (ApproveEnum::CUSTOMER_FORM_ISSUEINVOICE[$form['symbol']] === 'bill_id') {
                                $billIds = $form['value'];
                            } else {
                                $save[ApproveEnum::CUSTOMER_FORM_ISSUEINVOICE[$form['symbol']]] = $form['value'];
                            }
                        }
                    }
                    if ($save) {
                        $save['entid']     = $approveInfo['entid'];
                        $save['uid']       = $approveInfo['user_id'];
                        $save['link_id']   = $this->applyId;
                        $save['link_bill'] = json_encode($billIds);
                        if ($billIds) {
                            $save['cid'] = json_encode(app()->get(PaymentService::class)->column(['id' => $billIds], 'cid'));
                        }
                        $save['status'] = match ((int) $approveInfo['status']) {
                            1       => InvoiceEnum::STATUS_APPROVED,
                            2       => InvoiceEnum::STATUS_REJECT,
                            default => InvoiceEnum::STATUS_AUDIT
                        };
                        $save['unique'] = md5(json_encode($save) . time());
                        $invoice        = app()->get(InvoiceService::class)->create($save);
                        Task::deliver(new InvoiceRecordTask($approveInfo['entid'], $invoice->id, (int) $save['uid'], $save['status'], $invoice->toArray()));
                        if ($billIds && $invoice && $save['status'] != 2) {
                            $billService->update(['id' => $billIds], ['invoice_id' => $invoice->id]);
                        }
                    }
                    break;
                case ApproveEnum::CUSTOMER_INVOICE_CANCELLATION:// 发票冲红
                    foreach ($this->forms as $form) {
                        $form['symbol'] && $save[ApproveEnum::CUSTOMER_FORM_VOIDEDINVOICE[$form['symbol']]] = $form['value'];
                    }
                    if ($save) {
                        $invoiceService = app()->get(InvoiceService::class);
                        switch ($approveInfo['status']) {
                            case 1:// 审核通过，直接冲红
                                $status = InvoiceEnum::STATUS_CANCEL;
                                app()->get(PaymentService::class)->update(['invoice_id' => $save['invoice_id']], ['invoice_id' => 0]);
                                break;
                            case 2:// 审核拒绝，返回原状态
                                $status = InvoiceEnum::STATUS_INVOICED;
                                break;
                            default:// 待审核
                                $status = InvoiceEnum::STATUS_APPLY_CANCEL;
                        }
                        $invoiceService->update($save['invoice_id'], [
                            'revoke_id'   => $this->applyId,
                            'status'      => $status,
                            'card_remark' => $save['mark'],
                        ]);
                    }
                    break;
            }

            if ($approveInfo['crud_id']) {
                $crudInfo = app()->get(SystemCrudService::class)->get(
                    $approveInfo['crud_id'],
                    with: [
                        'field' => fn ($q) => $q->select(['crud_id', 'field_name_en', 'field_name', 'form_value', 'field_type', 'is_default']),
                        'child' => fn ($q) => $q->select(['crud_id', 'id']),
                    ]
                )?->toArray();
                $service      = app()->get(CrudModuleService::class);
                $masterData   = $service->model(crudId: $approveInfo['crud_id'])->get($approveInfo['link_id'])?->toArray();
                $scheduleData = $crudInfo['child'] ? $service->model(crudId: $crudInfo['child']['id'])->get($approveInfo['link_id'])?->toArray() : [];
                $service->handleEvent(app()->get(SystemCrudService::class)->get($approveInfo['crud_id']), CrudTriggerEnum::TRIGGER_SAVED, $approveInfo['link_id'], $masterData, $scheduleData);
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    /**
     * 付款提醒发起回款/续费审批后，同步标记提醒已处理.
     */
    private function completeRemind(int $remindId, int $types): void
    {
        if (! $remindId) {
            return;
        }

        $remindService = app()->get(RemindService::class);
        if ($remindService->exists(['id' => $remindId, 'types' => $types, 'status' => 0])) {
            $remindService->updateStatus($remindId, 2);
        }
    }
}
