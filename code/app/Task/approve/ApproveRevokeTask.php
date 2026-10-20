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

namespace App\Task\approve;

use App\Constants\ApproveEnum;
use App\Constants\Crud\CrudTriggerEnum;
use App\Constants\CustomEnum\InvoiceEnum;
use App\Constants\CustomEnum\SignEnum;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveService;
use App\Http\Service\Attendance\AttendanceApplyRecordService;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudApproveRecordService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Customer\ContractService;
use App\Http\Service\Customer\InvoiceService;
use App\Http\Service\Customer\PaymentService;
use App\Task\customer\InvoiceRecordTask;
use Tuoluojiang\Open\exceptions\ApiException;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 审批撤销后置事件
 * Class ApproveRevokeTask.
 */
class ApproveRevokeTask extends Task
{
    /**
     * 审批申请id.
     * ApprovedJob constructor.
     */
    public function __construct(protected int $applyId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $approveInfo = app()->get(ApproveApplyService::class)->get($this->applyId, ['approve_id', 'status', 'user_id', 'entid', 'crud_id', 'link_id'])?->toArray();
            if (! $approveInfo) {
                throw new ApiException('无效的审批信息');
            }
            $types = app()->get(ApproveService::class)->value($approveInfo['approve_id'], 'types');
            switch ($types) {
                case ApproveEnum::CUSTOMER_CONTRACT_RENEWAL:
                case ApproveEnum::CUSTOMER_CONTRACT_EXPENSES:
                case ApproveEnum::CUSTOMER_CONTRACT_PAYMENT:
                    app()->get(PaymentService::class)->revoke($this->applyId);
                    break;
                case ApproveEnum::CUSTOMER_INVOICE_ISSUANCE:
                    $service         = app()->get(InvoiceService::class);
                    $invoice         = $service->get(['link_id' => $this->applyId]);
                    $invoice->status = InvoiceEnum::STATUS_REVOKE;
                    $invoice->save();
                    app()->get(PaymentService::class)->update(['invoice_id' => $invoice->id], ['invoice_id' => 0]);
                    Task::deliver(new InvoiceRecordTask($approveInfo['entid'], $invoice->id, (int) $invoice->uid, InvoiceEnum::STATUS_REVOKE, toArray($invoice)));
                    break;
                case ApproveEnum::CUSTOMER_INVOICE_CANCELLATION:
                    $service         = app()->get(InvoiceService::class);
                    $invoice         = $service->get(['revoke_id' => $this->applyId]);
                    $invoice->status = InvoiceEnum::STATUS_INVOICED;
                    $invoice->save();
                    Task::deliver(new InvoiceRecordTask($approveInfo['entid'], $invoice->id, (int) $invoice->uid, InvoiceEnum::STATUS_INVOICED, toArray($invoice)));
                    break;
                case ApproveEnum::CUSTOMER_CONTRACT_SIGN:
                    app(ContractService::class)->approved($this->applyId, SignEnum::STATUS_WAIT_SIGN_REVOKE);
                    break;
                case ApproveEnum::PERSONNEL_HOLIDAY:
                case ApproveEnum::PERSONNEL_SIGN:
                case ApproveEnum::PERSONNEL_OVERTIME:
                case ApproveEnum::PERSONNEL_OUT:
                case ApproveEnum::PERSONNEL_TRIP:
                    $this->handleAttendanceRevoke($this->applyId);
                    break;
            }
            if ($approveInfo['crud_id']) {
                app()->get(SystemCrudApproveRecordService::class)->afterApproved($this->applyId);
                $crudInfo = app()->get(SystemCrudService::class)->get(
                    $approveInfo['crud_id'],
                    with: [
                        'field' => fn ($q) => $q->select(['crud_id', 'field_name_en', 'field_name', 'form_value', 'field_type', 'is_default']),
                        'child' => fn ($q) => $q->select(['crud_id', 'id']),
                    ]
                )?->toArray();
                $service      = app()->get(CrudModuleService::class);
                $masterData   = $service->model(crudId: $approveInfo['crud_id'])->withTrashed()->get($approveInfo['link_id'])?->toArray();
                $scheduleData = $crudInfo['child'] ? $service->model(crudId: $crudInfo['child']['id'])->get($approveInfo['link_id'])?->toArray() : [];
                $service->handleEvent(app()->get(SystemCrudService::class)->get($approveInfo['crud_id']), CrudTriggerEnum::TRIGGER_REVOKE, $approveInfo['link_id'], $masterData, $scheduleData);
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    /**
     * 处理考勤相关审批撤销
     * @param int $applyId 审批申请ID
     */
    protected function handleAttendanceRevoke(int $applyId): void
    {
        app()->get(AttendanceApplyRecordService::class)->revokeByApplyId($applyId);
    }
}
