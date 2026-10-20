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
use App\Constants\CustomEnum\ClueEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ConfigEnum;
use App\Constants\System\ViewSearchEnum;
use App\Events\SystemMessageEvent;
use App\Http\Dao\Customer\LeadDao;
use App\Http\Model\Customer\Lead;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\Work\WorkMemberService;
use App\Jobs\Customer\MergeCustomerJob;
use App\Jobs\Work\CustomerLabelToWorkJob;
use App\Jobs\Work\WorkClientSetLabelJob;
use App\Jobs\Work\WorkWithClueJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\traits\service\ServicesTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 线索Service.
 * @mixin LeadDao
 */
class LeadService extends BaseService
{
    use CustomerTrait;
    use ResourceServiceTrait;
    use ServicesTrait;

    public $dao;

    public function __construct(LeadDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 标签列表.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCustomerLabelList(array $labelIds = []): array
    {
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
        return app(FollowUpService::class)->getLastFollowTime($id, ViewSearchEnum::VIEW_CLUE);
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
        return ['followed'];
    }

    public function followUpField(): string
    {
        return 'followed';
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
        return app(SubscribeService::class)->getSubscribeStatusWithEid($uid, $ids, CustomEnum::CLUE);
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
        $field[]  = ['statistics_type', ''];
        $field[]  = ['types', ''];
        $field[]  = ['uid', ''];
        $fieldSet = app(FormService::class)->getCustomDataByTypes(CustomEnum::CLUE, ['key as field', 'input_type']);
        $fieldSet = match ($customType) {
            ViewSearchEnum::VIEW_CLUE => array_merge($fieldSet, ClueEnum::CLUE_SEARCH_FIELD, ClueEnum::CLUE_CHARGE_SEARCH_FIELD),
            default                   => array_merge($fieldSet, ClueEnum::CLUE_SEARCH_FIELD, ClueEnum::CLUE_SEAS_SEARCH_FIELD),
        };
        foreach ($fieldSet as $value) {
            $field[] = [$value['field'], ''];
        }
        $field[] = ['scope_frame', ''];
        return $field;
    }

    /**
     * 领取.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function claim(array $data, int $uid): void
    {
        $this->uncompletedDetection($uid);
        $list = collect($this->dao->select(['id' => $data, 'uid' => 0], ['id', 'uid', 'status', 'return_num']));
        if ($list->isEmpty()) {
            throw $this->exception('未找到数据');
        }
        $salesman = app(AdminService::class)->get($uid, ['id', 'name']);
        if (! $salesman) {
            throw $this->exception(__('common.not.exist', ['attr' => '业务员']));
        }
        $ids           = collect();
        $recordService = app(RecordService::class);
        $list->each(function ($customer) use (&$ids, $uid, $salesman, $recordService) {
            $this->transaction(function () use ($uid, $recordService, $customer, $salesman, &$ids) {
                $ids->push($customer->id);
                $res = $this->dao->update($customer->id, [
                    'uid'        => $uid,
                    'claim_time' => now()->toDateTimeString(),
                ]);
                $recordService->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                    'eid'            => $customer->id,
                    'type'           => ClueEnum::OPERATE_RECEIVE,
                    'creator_uid'    => $uid,
                    'record_version' => $customer->return_num,
                    'reason'         => '“' . $salesman->name . '”从线索池领取',
                ]);
                return $res;
            });
        });
        if ($ids->isNotEmpty()) {
            app(SubscribeService::class)->delete(['eid' => $ids->all(), 'types' => CustomEnum::CLUE]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        }
    }

    /**
     * 修改客户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateClue(array $data, int $id): mixed
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }
        $formService = app(FormService::class);
        $attaches    = [];
        // TODO:待优化
        $list = $formService->getFormDataList(CustomEnum::CLUE);
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
        $uid      = $info->uid;
        $attaches = array_filter($attaches);
        return $this->transaction(function () use ($id, $uid, $data, $attaches, $info) {
            if ($data['customer_label']) {
                $data['customer_label'] = is_string($data['customer_label']) ? json_decode($data['customer_label'], true) : $data['customer_label'];
            } else {
                $data['customer_label'] = null;
            }
            $res = $this->dao->update($id, $data);
            if (! $res) {
                throw $this->exception(__('common.operation.fail'));
            }
            $this->clueLabelToWork($info?->toArray(), (array) $data['customer_label']);
            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $id, 'relation_type' => AttachEnum::RELATION_TYPE[ViewSearchEnum::VIEW_CLUE]]);
            }
            if (isset($data['followed'])) {
                $status = $data['followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($uid, $id, $status, CustomEnum::CLUE);
            }

            return $res;
        });
    }

    public function getSearchField()
    {
        return [
            ['eid', ''],
            ['name', '', 'name_like'],
            ['salesman_id', '', 'uid'],
            ['time', ''],
            ['signing_status', ''],
            ['abnormal', '', 'contract_status'],
        ];
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelectList(int $uid): array
    {
        $userIds = app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER);
        return $this->dao->getList(['uid' => $userIds], ['id', 'eid', 'name as title'], 0, 0, 'id');
    }

    /**
     * 转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function shift(array $ids, int $toUid, int $uid): mixed
    {
        $this->uncompletedDetection($toUid, count($ids));
        if (! $toUid) {
            throw $this->exception(__('common.empty.attr', ['attr' => '转移人ID']));
        }
        $list = $this->dao->select(['id' => $ids], ['id', 'uid', 'return_num'])?->toArray();
        if (! $list) {
            return true;
        }
        $adminService  = app(AdminService::class);
        $recordService = app(RecordService::class);
        $salesman      = $adminService->get($toUid, ['id', 'name'])?->toArray();
        if (! $salesman) {
            throw $this->exception('交接人员不存在');
        }
        foreach ($list as $customer) {
            if ($customer['uid'] < 1) {
                $reason = '此线索从线索池移交给“' . $salesman['name'] . '”负责';
            } else {
                $beforeSalesman = $adminService->get($customer['uid'], ['id', 'name']);
                $reason         = '此线索从“' . $beforeSalesman?->name . '”负责移交给“' . $salesman['name'] . '”负责';
            }
            $recordService->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                'eid'            => $customer['id'],
                'type'           => ClueEnum::OPERATE_SHIFT,
                'reason'         => $reason,
                'record_version' => $customer['return_num'],
                'uid'            => $toUid,
                'creator_uid'    => $uid,
            ]);
        }
        return $this->dao->search(['id' => $ids])->update(['uid' => $toUid]);
    }

    /**
     * 保存线索.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveClue(array $data, int $uid, string $customType): mixed
    {
        $this->uncompletedDetection($uid);
        $formService = app(FormService::class);
        $attaches    = [];
        // TODO:待优化
        $list = $formService->getFormDataList(CustomEnum::CLUE);
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
        $data['creator_uid'] = $uid;
        if ($customType == ViewSearchEnum::VIEW_CLUE) {
            $data['uid'] = $uid;
        }
        $attaches = array_filter($attaches);
        return $this->transaction(function () use ($data, $attaches) {
            $data['claim_time'] = now()->toDateTimeString();
            $res                = $this->dao->create($data);
            if (! $res->id) {
                throw $this->exception(__('common.insert.fail'));
            }
            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $res->id, 'relation_type' => AttachEnum::RELATION_TYPE[ViewSearchEnum::VIEW_CLUE]]);
            }
            if (isset($data['followed'])) {
                $status = $data['followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($data['creator_uid'], $res->id, $status, CustomEnum::CLUE);
            }
            // save record
            $recordRes = app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                'eid'            => $res->id,
                'type'           => ClueEnum::OPERATE_CREATE,
                'creator_uid'    => $data['creator_uid'],
                'record_version' => 0,
                'reason'         => '新添加线索“' . $data['name'] . '”',
            ]);
            if (! $recordRes) {
                throw $this->exception('操作记录保存失败');
            }

            return $res->id;
        });
    }

    /**
     * 退回线索池.
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

        // 查询并过滤掉已关联企微客户的线索（external_userid 不为空表示已关联企微客户）
        $list = $this->dao->select(['id' => $data, 'not_uid' => 0], ['id', 'uid', 'status', 'return_num', 'external_userid'])
            ->filter(function ($clue) {
                return empty($clue->external_userid);
            });
        if ($list->isEmpty()) {
            return true;
        }

        $res = $this->transaction(function () use ($uid, $reason, $list) {
            $ids = [];
            foreach ($list as $customer) {
                $customer->before_uid = $customer->uid;
                $customer->uid        = 0;
                $customer->claim_time = null;
                ++$customer->return_num;
                if (! $customer->save()) {
                    throw $this->exception(__('common.operation.fail'));
                }
                $ids[] = $customer->id;
                app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                    'eid'            => $customer->id,
                    'type'           => ClueEnum::OPERATE_BACK,
                    'creator_uid'    => $uid,
                    'record_version' => $customer->return_num,
                    'reason'         => $reason,
                ]);
            }
            // clear customer subscribe status
            $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CLUE]);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 未转客户自动退回线索池.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function autoReturnForCycle(): bool
    {
        $config = (int) sys_config(ConfigEnum::RETURN_CLUE_DATE['key'], 0);
        if ($config > 0) {
            $returnCache = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('autoReturnForCycle'));
            $page        = $returnCache['page'] ?? 1;
            $list        = collect($this->dao->setTimeField('claim_time')->select(['time' => 'before_days' . $config, 'external_userid' => '', 'not_uid' => 0], page: $page, limit: 50));
            if ($list->isEmpty()) {
                Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('autoReturnForCycle'));
                return true;
            }
            Cache::tags([CacheEnum::TAG_OTHER])->put(md5('autoReturnForCycle'), ['page' => $page + 1]);
            $res = $this->transaction(function () use ($list) {
                $ids           = collect();
                $recordService = app(RecordService::class);
                $list->each(function (Lead $clue) use ($recordService, $ids) {
                    $ids->push($clue->id);
                    $clue->before_uid = $clue->uid;
                    $clue->uid        = 0;
                    $clue->claim_time = null;
                    ++$clue->return_num;
                    if (! $clue->save()) {
                        throw $this->exception('未转客户自动退回异常,状态修改失败');
                    }
                    $recordService->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                        'eid'            => $clue->id,
                        'type'           => ClueEnum::OPERATE_BACK,
                        'record_version' => $clue->return_num,
                        'reason'         => '未及时转客户，系统自动退回线索池',
                    ]);
                });
                $ids->isNotEmpty() && app(SubscribeService::class)->delete(['eid' => $ids->all(), 'types' => CustomEnum::CLUE]);
                return true;
            });
            return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        }
        return true;
    }

    /**
     * 未跟进线索池自动退回.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function autoReturnForStatus(): bool
    {
        $config = (int) sys_config(ConfigEnum::RETURN_CLUE_CYCLE['key'], 0);
        if ($config > 0) {
            $returnCache = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('autoReturnForStatus'));
            $page        = $returnCache['page'] ?? 1;
            $cycle       = $returnCache['cycle'] ?? $config;
            $list        = collect($this->dao->notFollowedSearch($cycle, $page, 50));
            if ($list->isEmpty()) {
                Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('autoReturnForStatus'));
                return true;
            }
            Cache::tags([CacheEnum::TAG_OTHER])->put(md5('autoReturnForStatus'), ['page' => $page + 1, 'cycle' => $config]);
            $res = $this->transaction(function () use ($list) {
                $nowTime       = now()->toDateTimeString();
                $recordService = app(RecordService::class);
                $ids           = [];
                $list->each(function ($clue) use ($recordService, $nowTime, &$ids) {
                    $clue->uid = 0;
                    ++$clue->return_num;
                    if (! $clue->save()) {
                        throw $this->exception('未跟进自动退回异常,线索状态修改失败');
                    }
                    $ids[] = $clue->id;
                    $recordService->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                        'eid'            => $clue->id,
                        'type'           => ClueEnum::OPERATE_BACK,
                        'reason'         => '未及时跟进，系统自动退回线索池',
                        'record_version' => $clue->return_num,
                        'created_at'     => $nowTime,
                    ]);
                });
                $ids && app(SubscribeService::class)->delete(['eid' => $ids, 'types' => CustomEnum::CLUE]);
                return true;
            });
            return $res && Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        }
        return true;
    }

    /**
     * 删除线索.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteClue(int $id): int
    {
        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }
            //            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CLIENT_DELETE_NOTICE, CustomerNoticeEnum::CLIENT_DELETE, 1, (int) $id));
            //            app(ScheduleInterface::class)->delScheduleByLinkId((int) $id, [ScheduleEnum::TYPE_CLUE_TRACK]);
            app(FollowUpService::class)->delete(['eid' => $id, 'link_type' => ViewSearchEnum::VIEW_CLUE]);
            app(FollowUpService::class)->delete(['eid' => $id, 'link_type' => ViewSearchEnum::VIEW_CLUE_SEAS]);

            // clear customer subscribe status
            app(SubscribeService::class)->delete(['eid' => $id, 'types' => CustomEnum::CLUE]);
            return $res;
        });
    }

    /**
     * 批量删除线索.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchDeleteClue(array $ids): bool
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) {
            throw $this->exception('请选择线索池线索');
        }
        if ($this->dao->count(['id' => $ids, 'uid' => 0, 'external_userid' => '']) !== count($ids)) {
            throw $this->exception('只能删除线索池线索');
        }

        return $this->transaction(function () use ($ids) {
            foreach ($ids as $id) {
                $this->deleteClue($id);
            }
            return true;
        });
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
     * 企微线索标签同步到企业微信.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function clueLabelToWork(array $customer, array $labelIds): bool
    {
        $clientLabelService = app(LabelService::class);
        $labelIds           = $this->normalizeCustomerLabelIds($labelIds);
        $workTagIds         = $clientLabelService->idByWorkTagId($labelIds);
        if (empty($customer['userid']) || empty($customer['external_userid'])) {
            Log::warning('线索标签同步到企业微信：跳过无企微客户身份的数据', [
                'id'              => $customer['id'] ?? 0,
                'userid'          => $customer['userid'] ?? '',
                'external_userid' => $customer['external_userid'] ?? '',
                'label_ids'       => $labelIds,
            ]);
            return true;
        }
        $beforeLabelIds = $this->normalizeCustomerLabelIds($customer['customer_label'] ?? []);
        $removeTag      = [];
        $diff           = array_diff($beforeLabelIds, $labelIds);
        if ($diff) {
            $removeTag = $clientLabelService->idByWorkTagId($diff);
        }
        Log::warning('线索标签同步到企业微信：准备修改客户企微标签', [
            'id'               => $customer['id'] ?? 0,
            'userid'           => $customer['userid'] ?? '',
            'external_userid'  => $customer['external_userid'] ?? '',
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
        return true;
    }

    /**
     * 同步修改企微线索标签.
     * @param mixed $userid
     * @param mixed $external_userid
     * @param mixed $labelIds
     * @param mixed $createtime
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveClueLabel($userid, $external_userid, $labelIds, $createtime)
    {
        $edit['createtime']     = date('Y-m-d H:i:s', $createtime);
        $edit['customer_label'] = $labelIds ? app(LabelService::class)->column(['work_tag_id' => $labelIds], 'id') ?: [] : [];
        Log::warning('企业微信客户标签同步到平台线索：写入线索标签', [
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

    public function saveWorkClue($externalContact)
    {
        try {
            $work_member_id  = app(WorkMemberService::class)->value(['userid' => $externalContact['userid']], 'id');
            $uid             = app(AdminService::class)->value(['work_member_id' => $work_member_id], 'id') ?: 0;
            $customerService = app(CustomerService::class);
            $customer        = $customerService->get(['external_userid' => $externalContact['external_userid']], ['id', 'uid', 'member'])?->toArray();

            if ($customer) {
                // 客户已存在：更新协作者，创建联系人
                if ($customer['uid'] != $uid) {
                    $currentMembers = $customer['member'] ?: [];
                    if (! in_array($uid, $currentMembers)) {
                        $customerService->update($customer['id'], ['member' => array_values(array_unique(array_merge($currentMembers, [$uid])))]);
                    }
                }
                app(LiaisonService::class)->firstOrCreate(
                    ['eid' => $customer['id'], 'external_userid' => $externalContact['external_userid']],
                    [
                        'uid'             => $uid,
                        'liaison_name'    => $externalContact['name'] ?? '',
                        'userid'          => $externalContact['userid'] ?? '',
                        'external_userid' => $externalContact['external_userid'] ?? '',
                    ]
                );
                // 创建一条软删除的线索记录（标记此员工已关联该客户）
                $this->dao->firstOrCreate(
                    [
                        'external_userid' => $externalContact['external_userid'],
                        'userid'          => $externalContact['userid'],
                    ],
                    [
                        'name'       => $externalContact['name'],
                        'source'     => 'wework',
                        'uid'        => $uid,
                        'createtime' => date('Y-m-d H:i:s', $externalContact['createtime']),
                        'created_at' => date('Y-m-d H:i:s'),
                        'deleted_at' => now(), // 直接软删除
                    ]
                );
            } else {
                // 客户不存在：查找或创建线索（同一员工+同一企微客户只能有一条活跃线索）
                $this->dao->firstOrCreate(
                    [
                        'external_userid' => $externalContact['external_userid'],
                        'userid'          => $externalContact['userid'],
                    ],
                    [
                        'name'       => $externalContact['name'],
                        'source'     => 'wework',
                        'uid'        => $uid,
                        'createtime' => date('Y-m-d H:i:s', $externalContact['createtime']),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error(__FUNCTION__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => mb_substr($e->getTraceAsString(), 0, 8000)]);
        }
    }

    /**
     * 搜索线索.
     * @param mixed $where
     * @return null|mixed[]
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function search($where)
    {
        return $this->dao->select($where, ['id as value', 'name as label', 'phone', 'name'], limit: 30)?->toArray();
    }

    /**
     * 线索转客户.
     * @param mixed $id
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function toCustomer($id)
    {
        $info = $this->dao->get($id, with: ['customer' => fn ($query) => $query->select(['id', 'uid', 'external_userid', 'member'])]);
        if (! $info || ! $info->customer) {
            throw $this->exception('客户不存在或线索不存在');
        }
        $this->transaction(function () use ($info) {
            if ($info->uid != $info->customer->uid) {
                $currentMembers = $info->customer->member ?: [];
                $newMembers     = array_values(array_unique(array_merge($currentMembers, [$info->uid])));
                if ($currentMembers != $newMembers) {
                    app(CustomerService::class)->update($info->customer->id, ['member' => $newMembers]);
                }
            }
            $externalUserid = $info->external_userid ?? null;
            if ($externalUserid) {
                MergeCustomerJob::dispatch($externalUserid);
            }
            return $info->delete();
        });
        return $info->customer->id;
    }

    /**
     * 线索未转客户退回提醒定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function unCustomerAndReturnRemind(): void
    {
        $remindCache   = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('unCustomerAndReturnRemind'));
        $page          = $remindCache['page'] ?? 1;
        $reminderCycle = $remindCache['reminderCycle'] ?? (int) sys_config(ConfigEnum::RETURN_CLUE_REMIND['key']);
        $unsettedCycle = $remindCache['unsettedCycle'] ?? (int) sys_config(ConfigEnum::RETURN_CLUE_DATE['key']);
        if ($reminderCycle < 1 || $reminderCycle >= $unsettedCycle) {
            Log::error('线索未转客户提醒异常,请检查提醒/退回周期设置');
            return;
        }
        $list = collect($this->dao->setTimeField('claim_time')->select(['is_work' => 0, 'time' => 'before_days' . $reminderCycle, 'not_uid' => 0], with: ['admin'], page: $page, limit: 50));
        if ($list->isEmpty()) {
            Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('unCustomerAndReturnRemind'));
            return;
        }
        Cache::tags([CacheEnum::TAG_OTHER])->put(md5('unCustomerAndReturnRemind'), [
            'page'          => $page + 1,
            'reminderCycle' => $reminderCycle,
            'unsettedCycle' => $unsettedCycle,
        ], 3600);
        $now         = now();
        $type        = 'clue_return_remind';
        $timeFormat  = 'Y/m/d H:i:s';
        $remindTime  = $now->format('H:i');
        $startTime   = (clone $now)->endOfDay()->subDays($unsettedCycle)->startOfDay();
        $surplus     = $unsettedCycle - $reminderCycle;
        $noticeWhere = [
            'template_type' => $type,
            'time'          => $startTime->format($timeFormat) . '-' . (clone $now)->endOfDay()->format($timeFormat),
        ];
        $noticeService = app(NoticeRecordService::class);
        $list->each(function ($item) use ($noticeService, $noticeWhere, $remindTime, $type, $surplus) {
            if (! $noticeService->count(array_merge(['link_id' => $item->id], $noticeWhere))) {
                $this->sendRemindMessage($item, $remindTime, $type, $surplus);
            }
        });
    }

    /**
     * 线索未跟进退回提醒定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function unFollowedAndReturnRemind(): void
    {
        $remindCache   = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('unFollowedAndReturnRemind'));
        $page          = $remindCache['page'] ?? 1;
        $reminderCycle = $remindCache['reminderCycle'] ?? (int) sys_config(ConfigEnum::RETURN_CLUE_REMIND['key']);
        $unFollowCycle = $remindCache['unFollowCycle'] ?? (int) sys_config(ConfigEnum::RETURN_CLUE_CYCLE['key']);
        if ($reminderCycle < 1 || $reminderCycle >= $unFollowCycle) {
            Log::error('线索未跟进退回提醒异常,请检查提醒/退回周期设置');
            return;
        }
        $clueId = app(FollowUpService::class)->column(['link_type' => ViewSearchEnum::VIEW_CLUE, 'time' => 'lately' . $unFollowCycle], 'eid');
        $list   = collect($this->dao->select(['not_id' => $clueId, 'is_work' => 0, 'not_uid' => 0], with: ['admin'], page: $page, limit: 50));
        if ($list->isEmpty()) {
            Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('unCustomerAndReturnRemind'));
            return;
        }
        Cache::tags([CacheEnum::TAG_OTHER])->put(md5('unCustomerAndReturnRemind'), [
            'page'          => $page + 1,
            'reminderCycle' => $reminderCycle,
            'unFollowCycle' => $unFollowCycle,
        ], 3600);

        $now         = now();
        $type        = 'clue_unfollowed_return';
        $timeFormat  = 'Y/m/d H:i:s';
        $remindTime  = $now->format('H:i');
        $startTime   = (clone $now)->endOfDay()->subDays($unFollowCycle)->startOfDay();
        $surplus     = $unFollowCycle - $reminderCycle;
        $noticeWhere = [
            'template_type' => $type,
            'time'          => $startTime->format($timeFormat) . '-' . (clone $now)->endOfDay()->format($timeFormat),
        ];
        $noticeService = app(NoticeRecordService::class);
        $list->each(function ($item) use ($noticeService, $noticeWhere, $remindTime, $type, $surplus) {
            if (! $noticeService->count(array_merge(['link_id' => $item->id], $noticeWhere))) {
                $this->sendRemindMessage($item, $remindTime, $type, $surplus);
            }
        });
    }

    /**
     * 跟进提醒定时任务.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function followRemindTimer(): void
    {
        $remindCache = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('followRemindTimer'));
        $page        = $remindCache['page'] ?? 1;
        $followCycle = $remindCache['followCycle'] ?? (int) sys_config(ConfigEnum::CLUE_FOLLOW_DATE['key']);
        if ($followCycle < 1) {
            return;
        }
        $clueId = app(FollowUpService::class)->column(['link_type' => ViewSearchEnum::VIEW_CLUE, 'time' => 'lately' . $followCycle], 'eid');
        $list   = collect($this->dao->select(['not_id' => $clueId, 'is_work' => 0, 'not_uid' => 0], with: ['admin'], page: $page, limit: 50));
        if ($list->isEmpty()) {
            Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('followRemindTimer'));
            return;
        }
        Cache::tags([CacheEnum::TAG_OTHER])->put(md5('followRemindTimer'), [
            'page'        => $page + 1,
            'followCycle' => $followCycle,
        ]);
        $noticeService = app(NoticeRecordService::class);
        $nowObj        = now();
        $remindTime    = $nowObj->format('H:i');
        $nowTime       = (clone $nowObj)->startOfDay();
        $type          = 'clue_unfollow_remind';
        $typeWhere     = ['template_type' => $type];
        $list->each(function ($customer) use ($noticeService, $typeWhere, $remindTime, $type, $nowTime, $followCycle) {
            $notice = $noticeService->get(array_merge(['link_id' => $customer->id], $typeWhere), ['created_at'], sort: 'created_at');
            if (($notice ? $notice->created_at : $customer->created_at)->endOfDay()->addDays($followCycle)->lt($nowTime)) {
                $this->sendRemindMessage($customer, $remindTime, $type);
            }
        });
    }

    /**
     * 企微绑定处理线索池.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function clueConnectWork(int $uid, string $userId, int $page = 1, ?CustomerService $customerService = null, ?LiaisonService $liaisonService = null, ?RecordService $recordService = null, array $customerFieldMap = []): void
    {
        $pageSize      = 100;
        $configKey     = 'wechat_work_client_radio';
        $configDefault = 'clue';
        $targetType    = 'customer';
        $viewType      = ViewSearchEnum::VIEW_CUSTOMER;
        $operateType   = CustomerEnum::OPERATE_CREATE;
        // 服务实例复用
        $customerService ??= app(CustomerService::class);
        $liaisonService ??= app(LiaisonService::class);
        $recordService ??= app(RecordService::class);
        // 查询线索
        $clues = $this->dao->select(['userid' => $userId, 'uid' => 0], page: $page, limit: $pageSize) ?: collect();
        if ($clues->isEmpty()) {
            return;
        }
        // 预加载客户字段映射
        if (! $customerFieldMap && sys_config($configKey, $configDefault) === $targetType) {
            $formService      = app(FormService::class);
            $customerFieldMap = $formService->dataDao->column(['link_type' => CustomEnum::CLUE], 'key', 'link_field');
            // 过滤无效字段，企微线索同步为客户时不继承客户状态类系统字段.
            $customerFieldMap = Arr::where($customerFieldMap, function ($val) {
                return ! empty($val) && ! in_array($val, ['customer_followed', 'customer_status'], true);
            });
        }
        // 批量处理线索
        $this->transaction(function () use ($clues, $customerService, $liaisonService, $recordService, $uid, $userId, $configKey, $configDefault, $targetType, $viewType, $operateType, $customerFieldMap) {
            $clues->each(function ($clue) use ($customerService, $liaisonService, $recordService, $uid, $userId, $configKey, $configDefault, $targetType, $viewType, $operateType, $customerFieldMap) {
                // 跳过无效线索（外部用户ID为空）
                if (empty($clue->external_userid)) {
                    $clue->delete(); // 无效线索直接删除
                    return;
                }
                // 查询客户
                $customer = $customerService->get(['external_userid' => $clue->external_userid], ['id', 'uid', 'member']);
                if ($customer) {
                    // 更新客户成员
                    $currentMembers = $customer->member ?: [];
                    $newMembers     = array_values(array_unique(array_merge($currentMembers, [$uid])));
                    if ($customer->uid != $uid && $currentMembers !== $newMembers) {
                        $customerService->update($customer->id, ['member' => $newMembers]);
                    }
                    // 创建/更新联系人
                    $liaisonService->firstOrCreate(
                        ['eid' => $customer->id, 'external_userid' => $clue->external_userid],
                        [
                            'eid'             => $customer->id,
                            'uid'             => $uid,
                            'liaison_name'    => $clue->name ?? '',
                            'liaison_tel'     => $clue->phone ?? '',
                            'userid'          => $clue->userid ?? '',
                            'external_userid' => $clue->external_userid,
                        ]
                    );

                    $clue->delete();
                } elseif (sys_config($configKey, $configDefault) === $targetType) {
                    // 创建客户
                    $customerSaveData = [
                        'external_userid' => $clue->external_userid,
                        'userid'          => $userId,
                        'uid'             => $uid,
                        'customer_no'     => $customerService->generateNo(),
                        'creator_uid'     => $uid,
                        'customer_status' => 0,
                    ];
                    // 批量映射字段，避免循环内多次判断
                    foreach ($customerFieldMap as $key => $val) {
                        if (in_array($val, ['customer_followed', 'customer_status'], true)) {
                            continue;
                        }
                        if ($key && $val) {
                            $customerSaveData[$val] = $clue->{$key} ?? '';
                        }
                    }
                    $customer = $customerService->create($customerSaveData);
                    // 保存客户记录
                    $customerName = $customer->customer_name ?? '未知客户';
                    $recordService->saveRecord($viewType, [
                        'eid'            => $customer->id,
                        'type'           => $operateType,
                        'creator_uid'    => $uid,
                        'record_version' => 0,
                        'reason'         => "企微同步客户“{$customerName}”",
                    ]);
                    // 创建联系人
                    $liaisonService->firstOrCreate(
                        ['eid' => $customer->id, 'external_userid' => $clue->external_userid],
                        [
                            'eid'             => $customer->id,
                            'uid'             => $uid,
                            'liaison_name'    => $clue->name ?? '',
                            'liaison_tel'     => $clue->phone ?? '',
                            'userid'          => $clue->userid ?? '',
                            'external_userid' => $clue->external_userid,
                        ]
                    );
                    $clue->delete();
                } else {
                    // 更新线索UID
                    $clue->uid = $uid;
                    $clue->save();
                }
            });
        });
        // 递归分发任务,满页才继续分页
        if ($clues->count() === $pageSize) {
            WorkWithClueJob::dispatch($uid, $userId, $page + 1, $customerService, $liaisonService, $recordService, $customerFieldMap);
        }
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
            foreach ($data as $id) {
                $this->dao->update($id, $update);
            }
            return true;
        });
        CustomerLabelToWorkJob::dispatch($beforeLabels, $label, ViewSearchEnum::VIEW_CLUE);
        return $res;
    }

    /**
     * 商机下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCurrentSelect(int $id, int $uid, array $where = []): array
    {
        $field = ['id as value', 'name as label', 'name as text'];
        $where = array_merge($where, [
            'uid' => [$uid],
        ]);
        $info = $id ? $this->dao->get(['id' => $id], ['id', 'uid', 'name'])?->toArray() : [];
        if ($info) {
            $list[] = ['value' => $info['id'], 'label' => $info['name'], 'text' => $info['name'], 'disabled' => $info['uid'] < 1];
        } else {
            $list = $this->dao->select($where, $field)?->toArray();
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
     * 保单验证
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function uncompletedDetection(int $uid, int $count = 1): void
    {
        $switch   = (int) sys_config(ConfigEnum::CLUE_POLICY_SWITCH['key']);
        $sumCount = (int) sys_config(ConfigEnum::CLUE_POLICY_COUNT['key']);
        if ($switch <= 0 || $sumCount <= 0) {
            return;
        }
        if ($sumCount <= $this->dao->count(['uid' => $uid, 'is_work' => 0]) + $count) {
            throw $this->exception('已到达线索保单数量，无法获取新线索');
        }
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
        switch ((int) $where['view_search']) {
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
            case 4:// 急需跟进
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'urgent_follow_up';
                break;
            case 5:// 线索池
                $where['uid']             = 0;
                $where['external_userid'] = '';
                break;
            case 10:// 我创建的
                $where['creator_uid'] = $uid;
                break;
            default:
                isset($where['types']) && $where['types'] == ViewSearchEnum::VIEW_CLUE_SEAS && $where['uid'] = 0;
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
    private function sendRemindMessage(Lead $clue, string $remindTime, string $type, int $surplus = 0): void
    {
        $message = app(MessageService::class)->getMessageContent(1, $type);
        if (! $message['template_time'] || ! $message['remind_time'] || $remindTime != $message['remind_time']) {
            return;
        }
        event(new SystemMessageEvent(
            type: $type,
            params: array_merge(['线索名称' => $clue->name], $surplus > 0 ? ['剩余天数' => $surplus] : []),
            receiverIds: (int) $clue->uid,
            other: ['id' => $clue->id, 'phone' => $clue->admin?->phone ?? ''],
            linkId: $clue->id,
        ));
    }
}
