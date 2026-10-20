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
use App\Constants\CustomerNoticeEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\ModuleEnum;
use App\Constants\ScheduleEnum;
use App\Constants\System\ConfigEnum;
use App\Constants\System\ViewSearchEnum;
use App\Constants\TodoEnum;
use App\Constants\UserAgentEnum;
use App\Events\BusinessDataChangeEvent;
use App\Events\SystemMessageEvent;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Dao\Customer\CustomerDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Assess\UserScopeService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\Work\WorkMemberService;
use App\Jobs\Customer\MergeCustomerJob;
use App\Jobs\Work\CustomerLabelToWorkJob;
use App\Jobs\Work\WorkClientSetLabelJob;
use App\Task\message\StatusChangeTask;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\CoreBusinessService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\traits\service\ServicesTrait;
use Tuoluojiang\Open\utils\Date;
use Tuoluojiang\Open\utils\Statistics;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webpatser\Uuid\Uuid;

/**
 * 客户列表.
 * @method getFollowExpire(array $where): mixed
 * @mixin CustomerDao
 */
class CustomerService extends BaseService
{
    use ResourceServiceTrait;
    use ServicesTrait;
    use CustomerTrait;

    public $dao;

    /**
     * 核心业务服务
     */
    protected CoreBusinessService $coreService;

    public function __construct(CustomerDao $dao, CoreBusinessService $coreService)
    {
        $this->dao         = $dao;
        $this->coreService = $coreService;
    }

    public function getWorkData(int $id)
    {
        $info = $this->dao->getModel()->where('id', $id)->select(['id', 'userid', 'external_userid'])->first();
        if ($info && $info['userid'] && $info['external_userid']) {
            return [
                'userid'          => $info['userid'],
                'external_userid' => $info['external_userid'],
            ];
        }
        return [];
    }

    /**
     * 保存客户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveCustomer(array $data, int $uid, string $customType, int $linkId = 0): mixed
    {
        // 使用核心服务验证客户数据
        if (! $this->coreService->validateCustomerData($data, $customType)) {
            throw $this->exception('客户数据验证失败');
        }

        if (! in_array($customType, [ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS])) {
            throw $this->exception('业务类型错误');
        }
        // uncompleted customer detection
        $this->uncompletedDetection($uid);
        $formService = app(FormService::class);
        $attaches    = [];
        $list        = $formService->getFormDataList(CustomEnum::CUSTOMER);
        foreach ($list as $item) {
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            foreach ($data as $key => &$datum) {
                if ($item['key'] == $key) {
                    if (in_array($inputType, ['file', 'images'])) {
                        $attaches = array_merge($attaches, (array) $datum);
                    }
                    $datum = $formService->getFormValue($type, $inputType, $datum, $key);
                    $datum = is_array($datum) ? array_filter($datum) : (is_empty((string) $datum) ? null : $datum);
                }
            }
        }
        $data['customer_no']     = $this->generateNo();
        $data['creator_uid']     = $uid;
        $data['customer_status'] = 0;
        if ($customType == ViewSearchEnum::VIEW_CUSTOMER) {
            $data['uid'] = $uid;
        }
        $linkId = $linkId ?: ($data['clue_id'] ?? 0);
        unset($data['clue_id']);
        $attaches = array_filter($attaches);
        return $this->transaction(function () use ($data, $attaches, $linkId) {
            $res = $this->dao->create($data);
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }
            // 附件更新
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $res->id, 'relation_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_CUSTOMER]]);
            }
            if (isset($data['customer_followed'])) {
                $status = $data['customer_followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($data['creator_uid'], $res->id, $status);
                unset($data['customer_followed']);
            }
            if ($linkId) {// 线索转客户
                $clueService          = app(LeadService::class);
                $clue                 = $clueService->get(['id' => $linkId], ['id', 'userid', 'external_userid', 'uid', 'name']);
                $res->uid             = $clue->uid ?? $res->uid;
                $res->userid          = $clue->userid ?? '';
                $res->external_userid = $clue->external_userid ?? '';
                $res->save();
                app(FollowUpService::class)->update(['link_type' => ViewSearchEnum::VIEW_CLUE, 'eid' => $linkId], [
                    'eid'       => $res->id,
                    'link_type' => ViewSearchEnum::VIEW_CUSTOMER,
                ]);
                $recordRes = app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $res->id,
                    'type'           => CustomerEnum::OPERATE_CONVERT,
                    'creator_uid'    => $data['creator_uid'],
                    'record_version' => 0,
                    'reason'         => '线索“' . ($clue->name ?? '') . '”转客户“' . $data['customer_name'] . '”',
                ]);
                $clue->delete();
                $res->external_userid && MergeCustomerJob::dispatch($res->external_userid);
            } else {
                $recordRes = app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $res->id,
                    'type'           => CustomerEnum::OPERATE_CREATE,
                    'creator_uid'    => $data['creator_uid'],
                    'record_version' => 0,
                    'reason'         => '新添加客户“' . $data['customer_name'] . '”',
                ]);
            }
            if (! $recordRes) {
                throw $this->exception('操作记录保存失败');
            }
            return $res;
        });
    }

    /**
     * 生成客户编号.
     */
    public function generateNo(int $len = 6): string
    {
        do {
            $no = Str::random($len);
        } while ($this->exists(['customer_no' => $no]));
        return $no;
    }

    public function getSearchField()
    {
        return [
            ['statistics_type', ''],
        ];
    }

    /**
     * 修改客户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateCustomer(array $data, int $id): mixed
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $label = $data['customer_label'] ?? [];

        $formService = app(FormService::class);

        $attaches = [];

        // TODO:待优化
        $list = $formService->getFormDataList(CustomEnum::CUSTOMER);
        foreach ($list as $item) {
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            foreach ($data as $key => &$datum) {
                if ($item['key'] == $key) {
                    if (in_array($inputType, ['file', 'images'])) {
                        $attaches = array_merge($attaches, (array) $datum);
                    }
                    $datum = $formService->getFormValue($type, $inputType, $datum, $key);
                }
            }
        }
        if (! $info['customer_no']) {
            $data['customer_no'] = $this->generateNo();
        }
        $uid      = $info->uid;
        $attaches = array_filter($attaches);
        unset($data['customer_status']);
        $labelsService = app(LabelService::class);
        $beforeLabelId = $labelsService->column(['eid' => $id], 'label_id');

        $res = $this->transaction(function () use ($id, $uid, $label, $data, $attaches, $labelsService) {
            // 同步线索客户
            if (isset($data['clue_id']) && $data['clue_id']) {
                $clueService                      = app(LeadService::class);
                $clue                             = $clueService->get(['id' => $data['clue_id']], ['userid', 'external_userid', 'uid', 'name'])?->toArray();
                $clue && $data['userid']          = $clue['userid'];
                $clue && $data['external_userid'] = $clue['external_userid'];
                $clueService->delete($data['clue_id']);
                $clue && app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $id,
                    'type'           => CustomerEnum::OPERATE_CONVERT,
                    'creator_uid'    => $uid,
                    'record_version' => 0,
                    'reason'         => '客户“' . $data['customer_name'] . '”关联线索“' . $clue['name'] . '”',
                ]);
                $clue && $clue['external_userid'] && MergeCustomerJob::dispatch($clue['external_userid']);
            }
            unset($data['clue_id']);
            $res = $this->dao->update($id, $data);
            if (! $res) {
                throw $this->exception(__('common.operation.fail'));
            }
            if (is_array($label)) {
                $labelsService->delete(['eid' => $id]);
                foreach ($label as $v) {
                    $labelsService->create(['eid' => $id, 'label_id' => $v]);
                }
            }
            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $id, 'relation_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_CUSTOMER]]);
            }
            if (isset($data['customer_followed'])) {
                $status = $data['customer_followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($uid, $id, $status);
            }
            return $res;
        });

        CustomerLabelToWorkJob::dispatch([$id => $beforeLabelId], $label, ViewSearchEnum::VIEW_CUSTOMER);

        $res && $this->syncCustomerTodo($id, [(int) $info->uid]);
        return $res;
    }

    /**
     * 列表统计
     * @param mixed $userIds
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getListStatistics(int $customType, string $uid, array $userIds): array
    {
        $concernWhere = match ($customType) {
            CustomerEnum::CUSTOMER_CHARGE => ['eid' => $this->dao->column(['uid' => $uid], 'id')],
            default                       => [],
        };

        return [
            'total'            => $this->dao->count(['uid' => $userIds]),
            'concern'          => app(SubscribeService::class)->count(array_merge(['uid' => $uid, 'subscribe_status' => 1, 'types' => CustomEnum::CUSTOMER], $concernWhere)),
            'unsettled'        => $this->dao->count(['customer_status' => 0, 'uid' => $userIds]),
            'traded'           => $this->dao->count(['customer_status' => 1, 'uid' => $userIds]),
            'urgent_follow_up' => $this->dao->getUrgentFollowUpCount(['uid' => $userIds]),
            'lost'             => $this->dao->count(['customer_status' => 2, 'uid' => $userIds]),
        ];
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelectList(array $where, int $uid): array
    {
        $uid   = array_merge(app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER), [0]);
        $field = [
            'id as value',
            'customer_name as ' . ($this->getPlatform() == UserAgentEnum::ADMIN_AGENT ? ' label' : 'text'),
        ];
        [$page, $limit] = $this->getPageValue();
        return $this->dao->getList(array_merge($where, ['uid' => $uid]), $field, $page, $limit, 'id');
    }

    /**
     * 无需同步字段.
     * @return string[]
     */
    public function getOutOfSyncField(): array
    {
        return [];
    }

    /**
     * 流失.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function lost(array $data, int $uid): bool
    {
        $list = $this->dao->select(['id' => $data, 'uid' => 0], ['id', 'uid', 'customer_status', 'return_num']);
        if ($list->isEmpty()) {
            return true;
        }
        $recordService = app(RecordService::class);
        return $this->transaction(function () use ($uid, $recordService, $list) {
            $ids         = [];
            $dictService = app(DictDataService::class);
            $lostName    = $dictService->getNameByValue('customer_status', '2');
            foreach ($list as $customer) {
                $status                    = $customer->customer_status;
                $customer->customer_status = 2;
                if (! $customer->save()) {
                    throw $this->exception(__('common.operation.fail'));
                }
                $ids[]      = $customer->id;
                $statusName = $dictService->getNameByValue('customer_status', $status);
                $recordService->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $customer->id,
                    'type'           => CustomerEnum::OPERATE_LOST,
                    'creator_uid'    => $uid,
                    'record_version' => $customer->return_num,
                    'reason'         => '客户状态由“' . $statusName . '”变为“' . $lostName . '”',
                ]);
            }
            // clear customer subscribe status
            $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CUSTOMER]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return true;
        });
    }

    /**
     * 退回公海.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function returnHighSeas(array $data, string $reason, int $uid): bool
    {
        if (! $reason) {
            throw $this->exception('请填写说明原因');
        }
        $list = $this->dao->select(['id' => $data, 'not_uid' => 0], ['id', 'uid', 'customer_status', 'return_num']);
        if ($list->isEmpty()) {
            return true;
        }
        $recordService = app(RecordService::class);
        $res           = $this->transaction(function () use ($recordService, $uid, $reason, $list) {
            $ids = [];
            foreach ($list as $customer) {
                $customer->before_uid = $customer->uid;
                $customer->uid        = 0;
                ++$customer->return_num;
                if (! $customer->save()) {
                    throw $this->exception(__('common.operation.fail'));
                }

                $ids[] = $customer->id;
                $recordService->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $customer->id,
                    'type'           => CustomerEnum::OPERATE_BACK,
                    'uid'            => $customer->before_uid,
                    'creator_uid'    => $uid,
                    'record_version' => $customer->return_num,
                    'reason'         => $reason,
                ]);
            }
            // clear customer subscribe status
            $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CUSTOMER]);
            $ids && app(OpportunityService::class)->update(['eid' => $ids], ['uid' => 'CustomEnum::CUSTOMER']);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 取消流失.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     */
    public function cancelLost(int $id, int $uid): bool
    {
        $info = $this->dao->get($id, ['id', 'uid', 'customer_name', 'customer_status', 'return_num']);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        if ($info->uid !== 0) {
            throw $this->exception('客户【' . $info->customer_name . '】存在负责人, 不能进行取消流失操作');
        }
        if ($info->customer_status != 2) {
            throw $this->exception('客户【' . $info->customer_name . '】状态异常, 不能进行取消流失操作');
        }

        return $this->transaction(function () use ($uid, $id, $info) {
            $tradedNum = app(PaymentService::class)->count(['eid' => $id, 'types' => [0, 1], 'status' => 1]);
            $status    = $tradedNum > 0 ? 1 : 0;
            $res       = $this->dao->update($id, ['customer_status' => $status]);
            if (! $res) {
                throw $this->exception('客户状态更新失败');
            }

            $dictService = app(DictDataService::class);
            $lostName    = $dictService->getNameByValue('customer_status', '2');
            $cancelName  = $dictService->getNameByValue('customer_status', (string) $status);

            // save cancel lost record
            $recordRes = app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                'eid'            => $id,
                'type'           => CustomerEnum::OPERATE_BACK_LOST,
                'uid'            => $info->uid,
                'creator_uid'    => $uid,
                'record_version' => $info->return_num,
                'reason'         => '客户状态由“' . $lostName . '”变为“' . $cancelName . '”',
            ]);
            if (! $recordRes) {
                throw $this->exception('操作记录保存失败');
            }

            // clear customer subscribe status
            app(SubscribeService::class)->delete(['eid' => $id, 'types' => CustomEnum::CUSTOMER]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return true;
        });
    }

    /**
     * 领取.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function claim(array $data, int $uid): bool
    {
        // claim customer detection
        $this->uncompletedDetection($uid, 'claim');
        $list = $this->dao->select(['id' => $data, 'uid' => 0, 'customer_status_lt' => 2], ['id', 'uid', 'customer_status', 'return_num']);
        if ($list->isEmpty()) {
            throw $this->exception('未找到客户数据或客户状态异常');
        }
        $salesman = app(AdminService::class)->get($uid, ['id', 'name']);
        if (! $salesman) {
            throw $this->exception(__('common.not.exist', ['attr' => '负责人']));
        }

        $billService   = app(PaymentService::class);
        $recordService = app(RecordService::class);
        $res           = $this->transaction(function () use ($recordService, $uid, $list, $billService, $salesman) {
            $ids = [];
            foreach ($list as $customer) {
                $customer->uid             = $uid;
                $customer->customer_status = $billService->count(['eid' => $customer->id, 'types' => [0, 1], 'status' => 1]) > 0 ? 1 : 0;
                if (! $customer->save()) {
                    throw $this->exception(__('common.operation.fail'));
                }
                $ids[] = $customer->id;
                $recordService->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $customer->id,
                    'type'           => CustomerEnum::OPERATE_RECEIVE,
                    'uid'            => $uid,
                    'creator_uid'    => $uid,
                    'record_version' => $customer->return_num,
                    'reason'         => '“' . $salesman->name . '”从公海领取',
                ]);
            }
            // clear customer subscribe status
            $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CUSTOMER]);
            $ids && app(OpportunityService::class)->update(['eid' => $ids], ['uid' => $uid]);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 自动退回定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function autoReturnTimer(): void
    {
        $returnEvents = ['unsettled_cycle', 'unfollowed_cycle'];
        foreach ($returnEvents as $event) {
            $cycle = (int) sys_config($event, 0);
            if ($cycle < 1) {
                continue;
            }

            match ($event) {
                'unsettled_cycle'  => $this->uncompletedAutoReturn($cycle),
                'unfollowed_cycle' => $this->unfollowedAutoReturn($cycle),
                'default'          => '',
            };
        }
    }

    /**
     * 客户未成交退回提醒定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function uncompletedReturnRemindTimer(): void
    {
        $reminderCycle = (int) sys_config('advance_cycle');
        if ($reminderCycle < 1) {
            return;
        }

        $unsettedCycle = (int) sys_config('unsettled_cycle');
        if ($reminderCycle >= $unsettedCycle) {
            Cache::tags([CacheEnum::TAG_CONFIG])->remember(md5('unsettled_return_remind'), 864000, function () {
                Log::error('客户未成交提醒异常,请检查提醒/退回周期设置');
            });
            return;
        }

        $list = $this->getReturnCycleList(['not_uid' => 0, 'customer_status' => '0']);
        if ($list->isEmpty()) {
            return;
        }

        $recordService = app(RecordService::class);
        $noticeService = app(NoticeRecordService::class);

        $now         = now();
        $type        = 'unsettled_return_remind';
        $timeFormat  = 'Y/m/d H:i:s';
        $remindTime  = $now->format('H:i');
        $startTime   = (clone $now)->endOfDay()->subDays($unsettedCycle)->startOfDay();
        $endTime     = (clone $now)->endOfDay()->subDays($unsettedCycle - $reminderCycle);
        $surplus     = $unsettedCycle - $reminderCycle;
        $noticeWhere = [
            'template_type' => $type,
            'time'          => $startTime->format($timeFormat) . '-' . (clone $now)->endOfDay()->format($timeFormat),
        ];

        foreach ($list as $customer) {
            $recordWhere = [
                'type'           => [CustomerEnum::CUSTOMER_CHARGE, CustomerEnum::OPERATE_SHIFT],
                'eid'            => $customer->id,
                'record_version' => $customer->return_num,
                'link_type'      => ViewSearchEnum::VIEW_CUSTOMER,
            ];
            $record = $recordService->get($recordWhere, ['id', 'created_at'], sort: 'created_at');
            if ($endTime->gt($record ? $record->created_at : $customer->created_at)
                && ! $noticeService->count(array_merge(['link_id' => $customer->id], $noticeWhere))) {
                $this->sendRemindMessage($customer, $remindTime, $type, $surplus);
            }
        }
    }

    /**
     * 客户未跟进退回提醒定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function unfollowedReturnRemindTimer(): void
    {
        $reminderCycle = (int) sys_config('advance_cycle');
        if ($reminderCycle < 1) {
            return;
        }

        $unFollowCycle = (int) sys_config('unfollowed_cycle');
        if ($reminderCycle >= $unFollowCycle) {
            Log::error('客户未跟进提醒异常,请检查提醒/跟进周期设置');
            return;
        }

        $list = $this->getReturnCycleList(['not_uid' => 0]);
        if ($list->isEmpty()) {
            return;
        }

        $noticeService = app(NoticeRecordService::class);
        $followService = app(FollowUpService::class);
        $recordService = app(RecordService::class);

        $now         = now();
        $type        = 'unfollowed_return_remind';
        $timeFormat  = 'Y/m/d H:i:s';
        $remindTime  = $now->format('H:i');
        $startTime   = (clone $now)->endOfDay()->subDays($unFollowCycle)->startOfDay();
        $endTime     = (clone $now)->endOfDay()->subDays($unFollowCycle - $reminderCycle);
        $surplus     = $unFollowCycle - $reminderCycle;
        $noticeWhere = [
            'template_type' => $type,
            'time'          => $startTime->format($timeFormat) . '-' . (clone $now)->endOfDay()->format($timeFormat),
        ];
        foreach ($list as $customer) {
            $compTime    = null;
            $followWhere = ['types' => 0, 'eid' => $customer->id, 'follow_version' => $customer->return_num];
            $follow      = $followService->get($followWhere, ['id', 'created_at'], sort: 'created_at');
            if ($follow) {
                $compTime = $follow->created_at;
            } else {
                $recordWhere = [
                    'type'           => [CustomerEnum::CUSTOMER_CHARGE, CustomerEnum::OPERATE_SHIFT],
                    'eid'            => $customer->id,
                    'record_version' => $customer->return_num,
                    'link_type'      => ViewSearchEnum::VIEW_CUSTOMER,
                ];
                $record = $recordService->get($recordWhere, ['id', 'created_at'], sort: 'created_at');
                if ($record) {
                    $compTime = $record->created_at;
                }
            }
            if ($endTime->gt($compTime ?: $customer->created_at)
                && ! $noticeService->count(array_merge(['link_id' => $customer->id], $noticeWhere))) {
                $this->sendRemindMessage($customer, $remindTime, $type, $surplus);
            }
        }
    }

    /**
     * 删除客户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteCustomer(int $id, int $uid): int
    {
        $info = $this->dao->get($id, ['uid', 'member'])?->toArray();
        if (! $info) {
            throw $this->exception('未找到该客户信息');
        }
        if ($info['uid'] && $uid != $info['uid']) {
            if (! in_array($uid, $info['member'])) {
                throw $this->exception('无权限删除该客户');
            }
            return $this->dao->update($id, ['member' => array_diff($info['member'], [$uid])]);
        }
        if (app(PaymentService::class)->count(['eid' => $id, 'entid' => 1, 'status' => 1])) {
            throw $this->exception('当前客户存在审核通过的回款、续费数据, 不能删除');
        }
        if (app(OrderService::class)->count(['eid' => $id])) {
            throw $this->exception('当前客户存在合同订单数据, 不能删除');
        }
        if (app(InvoiceService::class)->count(['eid' => $id])) {
            throw $this->exception('当前客户存在发票数据, 不能删除');
        }
        if (app(ContractService::class)->count(['eid' => $id])) {
            throw $this->exception('当前客户存在合同签约数据, 不能删除');
        }
        $res = $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }
            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CUSTOMER_DELETE_NOTICE, CustomerNoticeEnum::CUSTOMER_DELETE, 1, (int) $id));
            app(ScheduleInterface::class)->delScheduleByLinkId($id, [ScheduleEnum::TYPE_CLIENT_TRACK]);

            // clear customer subscribe status
            app(SubscribeService::class)->delete(['eid' => $id, 'types' => CustomEnum::CUSTOMER]);
            return $res;
        });
        $res && $this->syncCustomerTodo($id, [(int) $info['uid']], 'delete');
        return $res;
    }

    /**
     * 获取业务员列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSalesman(int $uid): array
    {
        $userIds = app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER);
        if (! $userIds) {
            return [];
        }
        return app(AdminService::class)->select(['id' => $userIds, 'status' => 1], ['id', 'name'])?->toArray();
    }

    /**
     * 批量修改标签.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function label(array $data, array $label): bool
    {
        if (! $data) {
            return true;
        }
        $beforeLabels = [];
        foreach ($data as $item) {
            $beforeLabels[$item] = $this->dao->column(['id' => $item], 'customer_label')[0] ?? [];
        }

        $res = $this->transaction(function () use ($data, $label) {
            // 直接传递数组，由模型的 mutator 处理 JSON 编码
            $update = ['customer_label' => $label];
            if (count($data) > 1) {
                foreach ($data as $id) {
                    $this->dao->update($id, $update);
                }
            } else {
                $this->dao->update($data[0], $update);
            }
            return true;
        });
        CustomerLabelToWorkJob::dispatch($beforeLabels, $label, ViewSearchEnum::VIEW_CUSTOMER);
        return $res;
    }

    /**
     *  转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function shift(array $data, int $toUid, int $invoice = 0, int $contract = 0, int $uid = 0, bool $force = false): mixed
    {
        if (! $toUid) {
            throw $this->exception(__('common.empty.attr', ['attr' => '转移人ID']));
        }

        $list = $this->dao->select(['id' => $data, 'customer_status_lt' => 2], ['id', 'uid', 'customer_status', 'return_num']);
        if ($list->isEmpty()) {
            return true;
        }

        $adminService = app(AdminService::class);
        $salesman     = $adminService->get($toUid, ['id', 'name']);
        if (! $salesman) {
            throw $this->exception('交接人员不存在');
        }
        $billService   = app(PaymentService::class);
        $recordService = app(RecordService::class);
        $dataUids      = app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER);
        $res           = $this->transaction(function () use ($recordService, $data, $toUid, $invoice, $contract, $uid, $salesman, $adminService, $billService, $list, $force, $dataUids) {
            foreach ($list as $customer) {
                if (! $force && ! in_array($customer->uid, $dataUids) && $uid != $customer->uid && $customer->uid) {
                    $data = array_diff($data, [$customer->id]);
                    continue;
                }
                if ($customer->uid < 1) {
                    $reason = '此客户从公海移交给“' . $salesman->name . '”负责';
                } else {
                    $beforeSalesman = $adminService->dao->setTrashed()->get($customer->uid, ['id', 'name']);
                    $reason         = '此客户从“' . $beforeSalesman?->name . '”负责移交给“' . $salesman->name . '”负责';
                }
                $customer->before_uid      = $customer->uid;
                $customer->uid             = $toUid;
                $customer->customer_status = $billService->count(['eid' => $customer->id, 'types' => [0, 1], 'status' => 1]) > 0 ? 1 : 0;
                if (! $customer->save()) {
                    throw $this->exception(__('common.operation.fail'));
                }
                $recordService->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $customer->id,
                    'type'           => CustomerEnum::OPERATE_SHIFT,
                    'reason'         => $reason,
                    'record_version' => $customer->return_num,
                    'uid'            => $toUid,
                    'creator_uid'    => $uid,
                ]);
            }
            // clear customer subscribe status
            app(SubscribeService::class)->delete(['eid' => $data, 'types' => CustomEnum::CUSTOMER]);
            // 转移合同订单
            if ($contract) {
                app(OrderService::class)->search(['eid' => $data])->update(['uid' => $toUid]);
            }
            // 转移发票
            if ($invoice) {
                $invoiceService = app(InvoiceService::class);
                $invoiceIds     = $invoiceService->column(['eid' => $data], 'id');
                $invoiceIds && $invoiceService->shift($invoiceIds, $toUid);
            }
            // 转移联系人
            app(LiaisonService::class)->search(['eid' => $data])->update(['uid' => $toUid]);

            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 客户下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCurrentSelect(int $id, int $uid, array $where = []): array
    {
        $field = ['id as value', 'customer_name as label', 'customer_name as text'];
        $where = array_merge($where, [
            'uid'    => [$uid],
            'member' => $uid,
        ]);
        $info = $id ? $this->dao->get(['id' => $id], ['id', 'uid', 'customer_name'])?->toArray() : [];
        if ($info) {
            $list[] = ['value' => $info['id'], 'label' => $info['customer_name'], 'text' => $info['customer_name'], 'disabled' => $info['uid'] < 1];
        } else {
            $list = $this->dao->selectData($where)->select($field)->get()?->toArray();
            foreach ($list as &$item) {
                $item['disabled'] = false;
                if ($item['value'] == $id) {
                    $id = 0;
                }
                $item['value'] = (int) $item['value'];
            }
        }
        return $list;
    }

    /**
     * 新增客户统计
     */
    public function getRingRatioCount(string $searchTime, array $userIds, string $ratioTime = ''): array
    {
        $ratio = 0;
        $count = $this->count(['time' => $searchTime, 'uid' => $userIds]);
        if (! $ratioTime) {
            return compact('count', 'ratio');
        }
        $ratioCount = $this->count(['time' => $ratioTime, 'uid' => $userIds]);
        $ratio      = Statistics::ringRatio($count, $ratioCount);
        return compact('count', 'ratio');
    }

    /**
     * 业绩统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getStatistics(string $time, array $userIds, array $categoryIds = []): array
    {
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $billService              = app(PaymentService::class);
        return array_merge(
            ['new_customer' => $this->getRingRatioCount($searchTime, $userIds, $ratioTime)],
            $billService->performanceStatistics($searchTime, $userIds, $ratioTime, $categoryIds),
            app(OrderService::class)->performanceStatistics($searchTime, $userIds, $ratioTime, $categoryIds)
        );
    }

    /**
     * 获取用户客户数据量.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserClientData(string $uuid): array
    {
        return app(PaymentService::class)->getIncome(['uid' => uuid_to_uid($uuid), 'types' => -1]);
    }

    /**
     * 保单验证
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function uncompletedDetection(int $uid, string $detection = 'insert'): void
    {
        $switch       = (int) sys_config('client_policy_switch');
        $clientNumber = (int) sys_config('unsettled_client_number');
        if ($switch < 1 || $clientNumber < 1) {
            return;
        }

        if ($clientNumber <= $this->dao->count(['uid' => $uid, 'customer_status' => 0])) {
            throw $this->exception(match ($detection) {
                'claim' => '到达保单数量，不能进行公海客户领取',
                default => '您未成交的客户数量已达系统设置的保单数量，请先将其他客户退回公海！'
            });
        }
    }

    /**
     * 跟进提醒定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function followUpRemindTimer(): void
    {
        $cache          = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('followUpRemindTimer'));
        $followUpStatus = (array) sys_config(ConfigEnum::FOLLOW_UP_STATUS['key'], []);
        if (empty($followUpStatus)) {
            return;
        }
        $now            = now();
        $followUpStatus = array_map('intval', $followUpStatus);
        $followType     = [1 => 'follow_up_traded', 2 => 'follow_up_unsettled'];
        $remindType     = [1 => 'traded_follow_up_remind', 2 => 'unsettled_follow_up_remind'];
        $page           = [];
        foreach ($followUpStatus as $status) {
            $cycle = (int) sys_config($followType[$status] ?? '', 0);
            if ($cycle < 1) {
                continue;
            }
            $page[$status] = $cache[$status] ?? 1;
            $list          = LazyCollection::make($this->dao->select(['not_uid' => 0, 'customer_status' => $status == 1 ? '1' : '0'], ['id', 'uid', 'customer_name', 'customer_status', 'return_num', 'created_at'], ['salesman'], page: $page[$status], limit: 50, cursor: true));
            if ($list->isEmpty()) {
                unset($page[$status]);
                Cache::tags([CacheEnum::TAG_OTHER])->put(md5('followUpRemindTimer'), $page);
                continue;
            }
            ++$page[$status];
            Cache::tags([CacheEnum::TAG_OTHER])->put(md5('followUpRemindTimer'), $page);
            $noticeService = app(NoticeRecordService::class);
            $remindTime    = $now->format('H:i');
            $nowTime       = (clone $now)->startOfDay();
            $typeWhere     = ['template_type' => $remindType[$status]];
            $list->each(function ($customer) use ($noticeService, $typeWhere, $remindTime, $nowTime, $cycle, $remindType, $status) {
                $notice = $noticeService->get(array_merge(['link_id' => $customer->id], $typeWhere), ['created_at'], sort: 'created_at');
                if (($notice ? $notice->created_at : $customer->created_at)->endOfDay()->addDays($cycle)->lt($nowTime)) {
                    $this->sendRemindMessage($customer, $remindTime, $remindType[$status]);
                }
            });
        }
    }

    /**
     * 获取用户部门数据量.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserFrameData(string $uuid, array $userIds): array
    {
        $data    = ['auth' => 1, 'income' => '0', 'today_income' => '0', 'yesterday_income' => '0'];
        $userId  = uuid_to_uid($uuid);
        $isAdmin = app(FrameAssistService::class)->exists(['user_id' => $userId, 'is_admin' => 1]) ?: 0;
        $cardId  = uuid_to_card_id($uuid);
        // 管理权限
        if ($isAdmin < 1 && app(UserScopeService::class)->count(['uid' => $cardId, 'entid' => 1]) < 1) {
            $data['auth'] = 0;
            return $data;
        }

        // 收入
        return app(PaymentService::class)->getIncome(['uid' => $userIds, 'types' => -1]);
    }

    /**
     * 业绩排行.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRanking(string $time, array $userIds, array $categoryIds = [], int $entId = 1): array
    {
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $categoryIds              = app(OrderService::class)->getStatisticsCategoryIds($categoryIds);
        $where                    = ['date' => $searchTime, 'entid' => $entId, 'uid' => $userIds, 'contract_category' => $categoryIds, 'types' => [0, 1]];
        return app(PaymentService::class)->getRankList($where);
    }

    /**
     * 部门业绩排行.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFrameRanking(string $time, array $userIds, array $categoryIds = []): array
    {
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $categoryIds              = app(OrderService::class)->getStatisticsCategoryIds($categoryIds);
        $where                    = ['date' => $searchTime, 'entid' => 1, 'uid' => $userIds, 'contract_category' => $categoryIds, 'types' => [0, 1]];
        return app(PaymentService::class)->getFrameRankList($where);
    }

    /**
     * 合同订单类型分析统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getContractRankWithNotRatio(string $time, array $userIds, int $categoryId): array
    {
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        return app(OrderService::class)->getCategoryRank($searchTime, $userIds, [], $categoryId);
    }

    /**
     * 获取绩效考核筛选用户数据.
     * @param array $member
     * @return array|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getIdsByType(int $uid, mixed $member, int $type, int $entId = 1): mixed
    {
        $userIds = [];
        $member  = array_filter(is_string($member) ? explode(',', $member) : $member);
        switch ($type) {
            case 1:
                $userIds[] = $uid;
                break;
            case 2:
                $userIds = app(FrameService::class)->getFrameSubUids($uid, false);
                break;
            case 3:
                $userIds = app(FrameService::class)->getIdsByFrameIds($uid, $member, false);
                break;
            case 4:
                $userIds = $member;
                break;
            default:
                $userIds = app(FrameService::class)->subUserInfo($uid, $entId, false, false, false, withAdmin: true, withSelf: true);
        }
        return $userIds;
    }

    /**
     * 业绩简报统计
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getBriefStatistics(string $time, array $userIds, int $entId = 1): array
    {
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $billService              = app(PaymentService::class);
        $contractService          = app(OrderService::class);
        return [
            'income'             => sprintf('%.2f', $billService->getSum(['date' => $searchTime, 'entid' => $entId, 'uid' => $userIds, 'types' => -1])),
            'renew'              => sprintf('%.2f', $billService->getSum(['date' => $searchTime, 'entid' => $entId, 'uid' => $userIds, 'types' => 1])),
            'new_customer'       => $this->dao->count(['time' => $searchTime, 'uid' => $userIds]),
            'new_contract'       => $contractService->count(['start_date' => $searchTime, 'uid' => $userIds]),
            'new_contract_price' => sprintf('%.2f', $contractService->sum(['start_date' => $searchTime, 'uid' => $userIds], 'contract_price')),
            'uncollected_price'  => sprintf('%.2f', $contractService->sum(['uid' => $userIds, 'pay_status' => 0, 'signing_status_lt' => 2], 'surplus')),
        ];
    }

    /**
     * 业务员业绩排行.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSalesmanRank(string $time, array $userIds, int $entId = 1): array
    {
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        return app(PaymentService::class)->getRankList(['date' => $searchTime, 'entid' => $entId, 'uid' => $userIds, 'types' => [0, 1]]);
    }

    /**
     * 导入
     * TODO:待优化.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchImport(array $data, array $uids, int $uid): mixed
    {
        $fieldMap = $fieldNameKeyMap = [];
        $fields   = app(FormService::class)->getExportField(CustomEnum::CUSTOMER);
        foreach ($fields as $field) {
            if ($field['key'] != 'customer_label') {
                $fieldMap[$field['key']] = $field;
            }
            $fieldNameKeyMap[$field['key_name']] = $field['key'];
        }

        $adminService = app(AdminService::class);

        // 业务员
        $salesmanMap = $adminService->column(['id' => $uids, 'name_eq' => array_column($data, '业务员')], 'id', 'name');
        return $this->transaction(function () use ($data, $fieldNameKeyMap, $fieldMap, $salesmanMap, $uid) {
            foreach ($data as $customer) {
                $insert   = [];
                $isCreate = false;
                foreach ($customer as $key => $item) {
                    if (! isset($fieldNameKeyMap[$key])) {
                        if ($key == '业务员') {
                            if ($item == '') {
                                $insert['uid']             = $uid;
                                $insert['customer_status'] = 0;
                            } else {
                                $insert['uid'] = $salesmanMap[$item] ?? $uid;
                            }
                        } elseif ($key == '客户编号') {
                            $insert['customer_no'] = $item;
                        }
                        continue;
                    }

                    $value     = $item;
                    $field     = $fieldNameKeyMap[$key];
                    $formField = $fieldMap[$field] ?? [];

                    // 标签
                    if ($field == 'customer_label') {
                        $value = app(LabelService::class)->getIdsByName(array_filter(explode('/', $item)));
                    }

                    // 字典
                    if ($formField && $formField['dict_ident']) {
                        $value = app(DictDataService::class)->getValuesByType($item, $formField['dict_ident'], $formField['input_type'], $formField['type']);
                        if ($formField['input_type'] == 'select' && $formField['type'] == 'single') {
                            if ($formField['dict_ident'] !== 'area_cascade') {
                                $value = $value[0] ?? '';
                            }
                        }
                    }

                    // 状态
                    if ($field == 'customer_status' && is_array($value)) {
                        $value = $value[0] ?? 0;
                    }
                    $insert[$field] = $value;
                }
                // 编号不符合
                if (mb_strlen($insert['customer_no']) > 6) {
                    $insert['customer_no'] = '';
                }

                // 不存在 || 找不到则新增
                if (! isset($insert['customer_no'])
                    || ! $insert['customer_no']
                    || ! $this->dao->exists(['customer_no' => $insert['customer_no']])
                ) {
                    $isCreate = true;
                    if (! $insert['customer_no']) {
                        $insert['customer_no'] = $this->generateNo();
                    }
                    $insert['creator_uid'] = $uid;
                }

                $label    = $insert['customer_label'] ?? [];
                $followed = $insert['customer_followed'] ?? false;
                if ($isCreate) {
                    foreach ($insert as $field => $value) {
                        if (is_array($value)) {
                            $insert[$field] = json_encode($value);
                        }
                    }

                    $res = $this->dao->create($insert);
                    if (! $res) {
                        throw $this->exception(__('common.insert.fail'));
                    }

                    if ($label) {
                        $labelsService = app(LabelService::class);
                        foreach ($label as $v) {
                            $labelsService->create(['eid' => $res->id, 'label_id' => $v]);
                        }
                    }

                    if ($followed !== false) {
                        app(SubscribeService::class)->subscribe($uid, $res->id, $followed < 1 ? 0 : 1);
                    }
                } else {
                    if (isset($insert['customer_status'])) {
                        unset($insert['customer_status']);
                    }
                    $info = $this->dao->get(['customer_no' => $insert['customer_no']], ['id']);
                    $res  = $this->dao->update($info->id, $insert);
                    if (! $res) {
                        throw $this->exception(__('common.operation.fail'));
                    }

                    if ($label) {
                        $labelsService = app(LabelService::class);
                        $labelsService->delete(['eid' => $info->id]);
                        foreach ($label as $v) {
                            $labelsService->create(['eid' => $info->id, 'label_id' => $v]);
                        }
                    }

                    if ($followed !== false) {
                        app(SubscribeService::class)->subscribe($uid, $info->id, $followed < 1 ? 0 : 1);
                    }
                }
            }
            return true;
        });
    }

    /**
     * 客户标签同步到企业微信.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function authCustomerLabelToWork(array $beforeLabels, array $labelIds, string $type)
    {
        $LabelService = app(LabelService::class);
        $labelIds     = $this->normalizeCustomerLabelIds($labelIds);
        $workTagIds   = $LabelService->idByWorkTagId($labelIds);

        $ids = array_keys($beforeLabels);
        if ($type == ViewSearchEnum::VIEW_CUSTOMER) {
            $list = $this->dao->getCustomerList($ids);
        }
        if ($type == ViewSearchEnum::VIEW_CLUE) {
            $list = app(LeadService::class)->getCustomerClueList($ids);
        }
        foreach ($list as $customer) {
            if (! isset($beforeLabels[$customer['id']])) {
                continue;
            }
            $beforeLabelIds = $this->normalizeCustomerLabelIds($beforeLabels[$customer['id']]);
            $removeTag      = [];
            $diff           = array_diff($beforeLabelIds, $labelIds);
            if ($diff) {
                $removeTag = $LabelService->idByWorkTagId($diff);
            }

            Log::warning('客户标签同步到企业微信：准备修改客户企微标签', [
                'type'             => $type,
                'id'               => $customer['id'],
                'userid'           => $customer['userid'],
                'external_userid'  => $customer['external_userid'],
                'before_label_ids' => $beforeLabelIds,
                'new_label_ids'    => $labelIds,
                'add_tag'          => $workTagIds,
                'remove_tag'       => $removeTag,
            ]);
            WorkClientSetLabelJob::dispatch([
                'userid'          => $customer['userid'],
                'external_userid' => $customer['external_userid'],
                'add_tag'         => $workTagIds,
                'remove_tag'      => $removeTag,
            ]);
        }
    }

    /**
     * 手动合并客户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function toMergeCustomer(int $mainId, array $ids, int $uid): void
    {
        $externalUserid = $this->dao->value(['id' => $mainId], 'external_userid');
        if (! $externalUserid) {
            $externalUserid = substr(str_replace('-', '', (string) Uuid::generate(4)), 0, 15);
        }
        if (count($ids) < 2) {
            throw $this->exception('至少需要两个客户进行合并');
        }
        $this->dao->update(['id' => $ids], ['external_userid' => $externalUserid]);
        app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
            'eid'            => $mainId,
            'type'           => CustomerEnum::OPERATE_MERGE,
            'reason'         => '客户信息合并',
            'record_version' => 0,
            'creator_uid'    => $uid,
        ]);
        MergeCustomerJob::dispatchSync($externalUserid, $mainId);
    }

    /**
     * 保存客户协作者.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveMember(int $id, array $data, int $uid)
    {
        $info = $this->dao->get($id, ['id', 'uid', 'member']);
        if (! $info) {
            throw $this->exception('未找到客户信息');
        }
        if ($info->uid != $uid) {
            throw $this->exception('无权限操作');
        }
        $this->transaction(function () use ($data, $info) {
            $info->member = array_values(array_unique(array_map('intval', $data)));
            $info->save();
            return true;
        });
    }

    /**
     * 保存企微客户.
     * @param mixed $externalContact
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveWorkCustomer($externalContact)
    {
        try {
            $work_member_id = app(WorkMemberService::class)->value(['userid' => $externalContact['userid']], 'id');
            $uid            = app(AdminService::class)->value(['work_member_id' => $work_member_id], 'id') ?: 0;
            $customer       = $this->dao->get(['external_userid' => $externalContact['external_userid']], ['id', 'uid', 'member']);
            if ($customer) {
                if ($customer['uid'] != $uid) {
                    $customer->member = array_values(array_unique(array_merge($customer->member ?: [], [$uid])));
                    $customer->save();
                }
            } else {
                $field = [
                    'name'       => $externalContact['name'],
                    'source'     => 'wework',
                    'createtime' => date('Y-m-d H:i:s', $externalContact['createtime']),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $customerFieldMap = app(FormService::class)->dataDao->column(['link_type' => CustomEnum::CLUE, 'link_field' => array_keys($field)], 'key', 'link_field');
                $customerSaveData = [
                    'external_userid' => $externalContact['external_userid'],
                    'userid'          => $externalContact['userid'],
                    'uid'             => $uid,
                    'customer_no'     => $this->generateNo(),
                    'creator_uid'     => $uid,
                    'customer_status' => 0,
                ];
                foreach ($customerFieldMap as $key => $val) {
                    $customerSaveData[$val] = $field[$key];
                }
                $customer = $this->dao->create($customerSaveData);
                app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $customer->id,
                    'type'           => CustomerEnum::OPERATE_CREATE,
                    'creator_uid'    => $uid,
                    'record_version' => 0,
                    'reason'         => "企微同步客户“{$externalContact['name']}”",
                ]);
            }
            app(LiaisonService::class)->firstOrCreate([
                'eid'             => $customer->id,
                'external_userid' => $externalContact->external_userid,
            ], [
                'eid'             => $customer->id,
                'uid'             => $uid,
                'liaison_name'    => $externalContact->name ?? '',
                'userid'          => $externalContact->userid ?? '',
                'external_userid' => $externalContact->external_userid ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error(__FUNCTION__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    /**
     * 同步修改企微客户标签.
     * @param mixed $userid
     * @param mixed $external_userid
     * @param mixed $labelIds
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveCustomerLabel($userid, $external_userid, $labelIds)
    {
        $edit = [
            'customer_label' => $labelIds ? app(LabelService::class)->column(['work_tag_id' => $labelIds], 'id') ?: [] : [],
        ];
        Log::warning('企业微信客户标签同步到平台客户：写入客户标签', [
            'userid'          => $userid,
            'external_userid' => $external_userid,
            'work_tag_ids'    => $labelIds,
            'label_ids'       => $edit['customer_label'],
        ]);
        $this->dao->update([
            'userid'          => $userid,
            'external_userid' => $external_userid,
        ], $edit);
    }

    /**
     * 获取客户基础信息.
     * @return BaseModel|BuildsQueries|mixed|Model|object
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function baseInfo(int $id, int $uid)
    {
        $info = $this->dao->get($id, ['id', 'uid', 'customer_name', 'customer_tel', 'area_cascade', 'member'])?->toArray();
        if (! $info) {
            throw $this->exception('未找到客户信息');
        }
        if ($uid != $info['uid'] && ! in_array($uid, $this->getScopeUid($uid, 'all'), true) && ! in_array($uid, $info['member'], true)) {
            throw $this->exception('无权限访问');
        }
        $area_cascade         = $info['area_cascade'] ? $this->handleDictValue('area_cascade', $info['area_cascade']) : [];
        $info['area_cascade'] = $area_cascade ? implode('', $area_cascade) : '';
        $info['customer_tel'] = $info['customer_tel'] ?: '';
        return $info;
    }

    /**
     * 标签列表.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCustomerLabelList(array $labelIds = []): array
    {
        // TODO 加载优化
        return app(LabelService::class)->select(['id' => $labelIds], ['id', 'name'])->toArray();
    }

    /**
     * 获取业务员数据.
     */
    public function getLiaisonTel(array|int $id): array
    {
        $liaisonMap = [];
        if (empty($id)) {
            return $liaisonMap;
        }
        // TODO 加载优化
        $list = app(LiaisonService::class)->select(['eid' => $id], ['eid', 'liaison_name', 'liaison_tel'])->toArray();
        foreach ($list as $item) {
            $liaisonMap[$item['eid']] = $item;
        }

        return $liaisonMap;
    }

    /**
     * 已入账金额.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAmountRecorded(int $id): string
    {
        $billService = app(PaymentService::class);
        return sprintf('%.2f', $billService->getSum(['eid' => $id, 'types' => [0, 1]]));
    }

    /**
     * 已支出金额.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAmountExpend(int $id): string
    {
        $billService = app(PaymentService::class);
        return sprintf('%.2f', $billService->getSum(['eid' => $id, 'types' => 2]));
    }

    /**
     * 已开票金额.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getInvoicedAmount(int $id): string
    {
        $billService = app(PaymentService::class);
        return sprintf('%.2f', $billService->sum(['eid' => $id, 'status' => [1, 3, 5, 6]], 'num'));
    }

    /**
     * 合同订单数量.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getContractNum(int $id): int
    {
        return app(OrderService::class)->count(['eid' => $id]);
    }

    /**
     * 发票数量.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getInvoiceNum(int $id): int
    {
        return app(InvoiceService::class)->count(['eid' => $id]);
    }

    /**
     * 附件数量.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAttachmentNum(int $id): int
    {
        return app(AttachService::class)->getCountByRelationType(AttachService::RELATION_TYPE_CLIENT, $id);
    }

    /**
     * 最后跟进时间.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getLastFollowTime(int $id): string
    {
        return app(FollowUpService::class)->getLastFollowTime($id);
    }

    /**
     * 获取退回原因.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getReturnReason(int $id): string
    {
        return app(RecordService::class)->getLastReasonByEid($id, 1);
    }

    /**
     * 字段过滤.
     * @return string[]
     */
    public function dictFilterField(): array
    {
        return ['customer_followed'];
    }

    public function followUpField(): string
    {
        return 'customer_followed';
    }

    public function followUpService(): string
    {
        return SubscribeService::class;
    }

    /**
     * 关注状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSubscribeStatus(int $uid, array $ids): array
    {
        return app(SubscribeService::class)->column(['uid' => $uid, 'eid' => $ids, 'subscribe_status' => 1, 'types' => CustomEnum::CUSTOMER], 'subscribe_status', 'eid');
    }

    /**
     * 关注状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getOdds(array $ids): array
    {
        return app(OpportunityService::class)->column(['id' => $ids], 'name', 'id');
    }

    /**
     * 获取未跟进天数.
     * @param mixed $nowObj
     * @throws BindingResolutionException
     */
    public function getUnFollowedDays(int $id, string $tz, $nowObj, string $followTime = ''): int
    {
        if (! $followTime) {
            $followTime = $this->getLastFollowTime($id);
        }
        return Carbon::parse($followTime, $tz)->startOfDay()->diffInDays($nowObj, false);
    }

    /**
     * 获取用户设置的搜索列表.
     * @param mixed $customType
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchField($customType)
    {
        $field[] = ['statistics_type', ''];
        $field[] = ['types', ''];
        $field[] = ['uid', ''];

        $fieldSet = app(FormService::class)->getCustomDataByTypes(CustomEnum::CUSTOMER, ['key as field', 'input_type']);
        $fieldSet = match ($customType) {
            default                            => array_merge($fieldSet, CustomerEnum::CUSTOMER_SEARCH_FIELD, CustomerEnum::CUSTOMER_VIEWER_SEARCH_FIELD),
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => array_merge($fieldSet, CustomerEnum::CUSTOMER_SEARCH_FIELD, CustomerEnum::CUSTOMER_HEIGHT_SEAS_SEARCH_FIELD),
        };
        foreach ($fieldSet as $value) {
            $field[] = [$value['field'], ''];
        }
        $field[] = ['scope_frame', ''];
        return $field;
    }

    private function normalizeCustomerLabelIds(mixed $labelIds): array
    {
        if (is_string($labelIds)) {
            $decoded  = json_decode($labelIds, true);
            $labelIds = json_last_error() === JSON_ERROR_NONE ? (is_array($decoded) ? $decoded : [$decoded]) : [$labelIds];
        }

        if (! is_array($labelIds)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static fn ($item) => (int) $item, $labelIds))));
    }

    private function syncCustomerTodo(int $customerId, array $userIds = [], string $action = 'update'): void
    {
        if (! $customerId) {
            return;
        }

        try {
            BusinessDataChangeEvent::dispatch(TodoEnum::TYPE_CUSTOMER, $customerId, $action, $userIds);
        } catch (\Throwable) {
        }
    }

    /**
     * 获取客户列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function getReturnCycleList(array $where): mixed
    {
        return Cache::tags([CacheEnum::TAG_CUSTOMER])->remember(md5(json_encode($where)), 3600, function () use ($where) {
            return $this->dao->select($where, ['id', 'uid', 'customer_name', 'customer_status', 'return_num', 'created_at'], ['salesman']);
        });
    }

    /**
     * 未成交自动退回.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function uncompletedAutoReturn(int $cycle): void
    {
        $list = $this->getReturnCycleList(['not_uid' => 0, 'customer_status' => '0']);
        if ($list->isEmpty()) {
            return;
        }

        $now     = now();
        $nowTime = $now->toDateTimeString();
        $endTime = (clone $now)->endOfDay()->subDays($cycle);
        $this->transaction(function () use ($list, $endTime, $nowTime) {
            $records       = $ids = [];
            $recordService = app(RecordService::class);
            foreach ($list as $customer) {
                $recordWhere = ['type' => [2, 5], 'eid' => $customer->id, 'record_version' => $customer->return_num];
                $record      = $recordService->get($recordWhere, ['id', 'created_at'], sort: 'created_at');
                if ($endTime->gt($record ? $record->created_at : $customer->created_at)) {
                    $customer->uid = 0;
                    ++$customer->return_num;
                    if (! $customer->save()) {
                        throw $this->exception('未成交自动退回异常,客户状态修改失败');
                    }
                    $ids[]     = $customer->uid;
                    $records[] = [
                        'eid'            => $customer->id,
                        'type'           => 1,
                        'reason'         => '未及时成交，系统自动退回公海',
                        'record_version' => $customer->return_num,
                        'created_at'     => $nowTime,
                    ];
                }
            }

            if (! $records) {
                return;
            }

            // save return record
            if (! app(RecordService::class)->insert($records)) {
                throw $this->exception('未成交自动退回异常,操作记录保存失败');
            }

            // clear customer subscribe status
            $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CUSTOMER]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        });
    }

    /**
     * 未跟进自动退回.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function unfollowedAutoReturn(int $cycle): void
    {
        $list = $this->getReturnCycleList(['not_uid' => 0]);
        if ($list->isEmpty()) {
            return;
        }

        $now     = now();
        $nowTime = $now->toDateTimeString();
        $endTime = (clone $now)->endOfDay()->subDays($cycle);
        $this->transaction(function () use ($list, $endTime, $nowTime) {
            $records       = $ids = [];
            $recordService = app(RecordService::class);
            $followService = app(FollowUpService::class);
            foreach ($list as $customer) {
                $compTime    = null;
                $followWhere = ['types' => 0, 'eid' => $customer->id, 'follow_version' => $customer->return_num];
                $follow      = $followService->get($followWhere, ['id', 'created_at'], sort: 'created_at');
                if ($follow) {
                    $compTime = $follow->created_at;
                } else {
                    $recordWhere = ['type' => [2, 5], 'eid' => $customer->id, 'record_version' => $customer->return_num];
                    $record      = $recordService->get($recordWhere, ['id', 'created_at'], sort: 'created_at');
                    if ($record) {
                        $compTime = $record->created_at;
                    }
                }

                if ($endTime->gt($compTime ?: $customer->created_at)) {
                    $customer->uid = 0;
                    ++$customer->return_num;
                    if (! $customer->save()) {
                        throw $this->exception('未跟进自动退回异常,客户状态修改失败');
                    }
                    $ids[]     = $customer->id;
                    $records[] = [
                        'eid'            => $customer->id,
                        'type'           => 1,
                        'reason'         => '未及时跟进，系统自动退回公海',
                        'record_version' => $customer->return_num,
                        'created_at'     => $nowTime,
                    ];
                }
            }

            if (! $records) {
                return;
            }

            // save return record
            if (! app(RecordService::class)->insert($records)) {
                throw $this->exception('未跟进自动退回异常,操作记录保存失败');
            }

            // clear customer subscribe status
            $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CUSTOMER]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        });
    }

    /**
     * 获取列表搜索条件.
     */
    private function viewSearchWhere(array $where, int $uid): array
    {
        if (! isset($where['view_search'])) {
            unset($where['scope_frame']);
            return $where;
        }
        $scopeFrame = isset($where['scope_frame']) && $where['scope_frame'] ? (is_array($where['scope_frame']) ? end($where['scope_frame']) : $where['scope_frame']) : 'all';
        switch ($where['view_search']) {
            case 1:// 我负责的
                $where['uid'] = $uid;
                break;
            case 2:// 我查看的
                $where = $this->applyScopeWhere($where, $uid, $scopeFrame);
                break;
            case 3:// 我关注的
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'concern';
                break;
            case 4:// 未成交
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'unsettled';
                break;
            case 5:// 已成交
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'traded';
                break;
            case 6:// 急需跟进
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'urgent_follow_up';
                break;
            case 7:// 客户公海
                $where['uid'] = 0;
                break;
            case 9:// 我协作的
                $where['member'] = $uid;
                break;
            case 10:// 我创建的
                $where['creator_uid'] = $uid;
                break;
            case 11:// 我参与的
                $where['involved'] = $uid;
                break;
            default:
                isset($where['types']) && $where['types'] == ViewSearchEnum::VIEW_CUSTOMER_SEAS && $where['uid'] = 0;
        }
        unset($where['view_search'], $where['scope_frame']);
        return $where;
    }

    /**
     * 退回提醒.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function sendRemindMessage(mixed $customer, string $remindTime, string $type, int $surplus = 0): void
    {
        $message = app(MessageService::class)->getMessageContent(1, $type);
        if (! $message['template_time'] || ! $message['remind_time'] || $remindTime != $message['remind_time']) {
            return;
        }
        event(new SystemMessageEvent(
            type: $type,
            params: array_merge(['客户名称' => $customer->customer_name], $surplus > 0 ? ['剩余天数' => $surplus] : []),
            receiverIds: (int) $customer->uid,
            other: ['id' => $customer->id, 'phone' => $customer->salesman?->phone ?? ''],
            linkId: $customer->id,
        ));
    }
}
