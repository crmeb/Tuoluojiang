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

use App\Constants\CacheEnum;
use App\Constants\CustomerNoticeEnum;
use App\Constants\CommonEnum;
use App\Events\SystemMessageEvent;
use App\Http\Dao\Customer\PaymentDao;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Finance\BillService;
use App\Http\Service\Finance\PaytypeService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\Open\OpenapiRuleService;
use App\Http\Service\User\UserRemindLogService;
use App\Task\customer\BillRemindTask;
use App\Task\message\StatusChangeTask;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\utils\MessageType;
use Tuoluojiang\Open\utils\Statistics;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 回款/续费记录.
 * @method getHaveList($where, $group, $having, $field = ['*'])
 * @method unInvoiceList(array $param, int $page = 0, int $limit = 0, array $sort = ['date', 'id'])
 * @mixin PaymentDao
 */
class PaymentService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(PaymentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取当前企业id.
     * @return array|mixed
     */
    public function getEntCacheList()
    {
        return Cache::tags([CacheEnum::TAG_CUSTOMER])->remember('client_list', (int) sys_config('system_cache_ttl', 3600), fn () => $this->dao->getHaveList([], 'entid', false, ['entid'])->toArray());
    }

    /**
     * 获取列表.
     * @param array $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = ['date', 'id'], array $with = ['renew', 'card', 'treaty', 'client', 'attachs', 'invoice']): array
    {
        if (isset($where['sort']) && is_string($where['sort']) && str_contains($where['sort'], ' ')) {
            [$key, $val] = explode(' ', $where['sort']);
            $sort        = [$key => $val];
        }

        if (isset($where['time_data']) && in_array($where['time_field'], ['time', 'date'])) {
            $where[$where['time_field']] = $where['time_data'];
        }
        unset($where['time_field'], $where['time_data'], $where['sort']);
        $contractService = app(OrderService::class);
        if (! $where['cid'] && $where['eid']) {
            $where['cid'] = $contractService->column(['eid' => $where['eid']], 'id');
            unset($where['eid']);
        }
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->setDefaultSort($sort)->setTimeField('date')->select($where, $field, $with + [
            'approveRule' => fn ($query) => $query->select(['approve_rule.approve_id', 'approve_rule.recall']),
        ], $page, $limit)?->toArray();
        $count = $this->dao->setTimeField('date')->count($where);
        unset($where['status']);
        $census = [
            'income'        => $this->dao->sum(array_merge($where, ['bill_types' => 1, 'status' => 1]), 'num'),
            'expend'        => $this->dao->sum(array_merge($where, ['bill_types' => 0, 'status' => 1]), 'num'),
            'review_income' => $this->dao->sum(array_merge($where, ['bill_types' => 1, 'status' => 0]), 'num'),
            'review_expend' => $this->dao->sum(array_merge($where, ['bill_types' => 0, 'status' => 0]), 'num'),
        ];
        unset($where['type_id'], $where['entid'], $where['types']);
        if ($where['cid']) {
            $contractWhere['id']      = $where['cid'];
            $contractData             = app(OrderService::class)->get($contractWhere, ['contract_price', 'received', 'surplus']);
            $census['contract_price'] = $contractData['contract_price'] ?? 0;
            $census['received']       = $contractData['received'] ?? 0;
            $census['surplus']        = $contractData['surplus'] ?? 0;
        } else {
            $census['contract_price'] = $census['received'] = $census['surplus'] = 0;
        }
        $recalls = $this->revoked(array_column($list, 'apply_id'));
        foreach ($list as &$item) {
            $item['recall'] = $item['apply_id'] ? $recalls[$item['apply_id']] : 0;
        }
        return compact('list', 'count', 'census');
    }

    /**
     * 获取组合列表.
     * @param array|string[] $field
     * @param string $sort
     * @param array|string[] $with
     */
    public function getBillList(array $where, array $field = ['*'], $sort = 'date', array $with = ['renew', 'card', 'treaty', 'client', 'invoice']): array
    {
        unset($where['field_key'], $where['name']);
        if ($where['eid'] > 0) {
            $where['cid'] = app(OrderService::class)->column(['eid' => $where['eid']], 'id');
            unset($where['eid']);
        }
        [$page, $limit] = $this->getPageValue();
        $with           = $with + [
            'attachs'     => fn ($query) => $query->select(['id', 'att_dir as src', 'relation_id', 'real_name']),
            'approveRule' => fn ($query) => $query->select(['approve_rule.approve_id', 'approve_rule.recall']),
        ];
        $list    = $this->dao->setDefaultSort($sort)->select($where, $field, $with, $page, $limit)?->toArray();
        $recalls = $this->revoked(array_column($list, 'apply_id'));
        $list    = collect($list)->map(function ($item) use ($recalls) {
            $item['recall'] = $item['apply_id'] ? $recalls[$item['apply_id']] : 0;
            return $item;
        })->all();
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 凭证上传.
     * @param mixed $attachIds
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function attachSave($attachIds, string $uid = '', int $id = 0)
    {
        app(AttachService::class)->saveRelation($attachIds, $uid ?: $this->uuId(false), $id, AttachService::RELATION_TYPE_BILL);
    }

    /**
     * 保存记录.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceSave(array $data)
    {
        $uid           = $data['uid'];
        $data['uid']   = uuid_to_uid($data['uid']);
        $data['entid'] = 1;

        $attachIds = is_array($data['attach_ids']) ? $data['attach_ids'] : explode(',', $data['attach_ids']);
        if (count($attachIds) > 1) {
            throw $this->exception('凭证不支持上传多图');
        }
        unset($data['attach_ids']);
        $data['bill_no']  = $this->generateNo(1);
        $data['pay_type'] = app(PaytypeService::class)->getTypeName((int) $data['type_id'], (int) $data['entid']);

        if ($data['types'] == 0 || ($data['types'] > 0 && ! $data['end_date'])) {
            $data['end_date'] = null;
        }

        // 完成之前的付款提醒
        $remindId = (int) ($data['remind_id'] ?? 0);
        unset($data['remind_id']);

        $res = $this->transaction(function () use ($data, $attachIds, $uid, $remindId) {
            $res = $this->dao->create($data);
            if (! $res) {
                throw $this->exception('付款单添加失败');
            }

            if ($data['types'] == 1 && $data['cid'] && ! app(OrderService::class)->update($data['cid'], ['renew' => 1])) {
                throw $this->exception('合同订单信息更新失败');
            }

            if ($remindId && ! app(RemindService::class)->updateStatus($remindId, 2)) {
                throw $this->exception('付款提醒关联失败');
            }
            $this->attachSave($attachIds, $uid, (int) $res->id);
            return $res;
        });

        // 自动审批
        if ($res && (int) sys_config(match ((int) $data['types']) {
            1       => 'contract_renew_switch',
            2       => 'contract_disburse_switch',
            default => 'contract_refund_switch'
        }, 0) == 0) {
            $this->autoApprove($res->id, $res);
        }

        Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        // 合同订单回款/续费提醒
        switch ($data['types']) {
            case 0:
                $this->sendMessage($res->load(['renew', 'client', 'treaty', 'card'])->toArray(), 1, MessageType::CONTRACT_RETURN_MONEY_TYPE);
                break;
            case 1:
                $this->sendMessage($res->load(['renew', 'client', 'treaty', 'card'])->toArray(), 1, MessageType::CONTRACT_RENEW_TYPE);
                break;
            case 2:
                $this->sendMessage($res->load(['renew', 'client', 'treaty', 'card'])->toArray(), 1, MessageType::CONTRACT_EXPEND_TYPE);
                break;
        }
        return $res;
    }

    /**
     * 修改记录.
     * @param mixed $id
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data, bool $isFinance = false)
    {
        $id   = (int) $id;
        $uid  = $data['uid'];
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $data['status'] = 0;
        if ($isFinance) {
            unset($data['status']);
        } else {
            if ($info->status == 1) {
                throw $this->exception('付款单已审核，无法修改');
            }
        }
        unset($data['uid'], $data['remind_id']);
        $data['entid'] = 1;

        $attachIds = is_array($data['attach_ids']) ? $data['attach_ids'] : explode(',', $data['attach_ids']);
        if (count($attachIds) > 1) {
            throw $this->exception('凭证不支持上传多图');
        }
        unset($data['attach_ids']);

        if (! $info->bill_no) {
            $data['bill_no'] = $this->generateNo($info->entid);
        }

        $data['pay_type'] = app(PaytypeService::class)->getTypeName((int) $data['type_id'], (int) $data['entid']);
        $contract         = app(OrderService::class)->get($info->cid);
        if (! $contract) {
            throw $this->exception('合同订单信息获取异常');
        }

        return $this->transaction(function () use ($id, $data, $uid, $attachIds, $info, $contract, $isFinance) {
            if ($data['types'] == 0 || ($data['types'] > 0 && ! $data['end_date'])) {
                $data['end_date'] = null;
            }
            $res = $this->dao->update($id, $data);
            if (! $res) {
                return $res;
            }

            if ($data['types'] == 1 && $contract->renew != 1) {
                $contract->renew = 1;
                if (! $contract->save()) {
                    throw $this->exception('合同订单信息更新失败');
                }
            }

            if (! $this->contractPrice($info->cid, $contract)) {
                throw $this->exception('合同订单信息更新失败');
            }

            if (! $this->invoicePrice($info->invoice_id, $data['entid'])) {
                throw $this->exception('发票信息更新失败');
            }

            $this->attachSave($attachIds, $uid, $id);

            if ($isFinance && $info->status == 1) {
                app(BillService::class)->saveOrUpdate($data['entid'], $id, 'client', [
                    'entid'     => $data['entid'],
                    'uid'       => $info->uid,
                    'num'       => $data['num'],
                    'edit_time' => $data['date'],
                    'types'     => $info->types == 2 ? 0 : 1,
                    'mark'      => $data['mark'],
                    'link_id'   => $id,
                    'link_cate' => 'client',
                    'type_id'   => (int) $data['type_id'],
                    'pay_type'  => $data['pay_type'],
                ]);
            }

            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return $res;
        });
    }

    /**
     * 删除记录.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        return $this->transaction(function () use ($id, $info) {
            $res = $info->delete();
            if (! $res) {
                throw $this->exception(__('common.operation.fail'));
            }

            if (! $this->invoicePrice($info->invoice_id, $info->entid)) {
                throw $this->exception('发票信息更新失败');
            }

            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CUSTOMER_RETURN_MONEY_NOTICE, CommonEnum::STATUS_DELETE, $info->entid, $id));
            return $res;
        });
    }

    /**
     * 财务审核.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceStatusUpdate($id, array $data)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        if (! in_array($data['status'], [-1, 1, 2])) {
            throw $this->exception('审核状态异常');
        }

        if ($data['status'] == 2 && $data['fail_msg'] == '') {
            throw $this->exception(__('common.empty.attr', ['attr' => '备注信息']));
        }

        if ($data['status'] == -1 && $info->invoice_id > 0) {
            throw $this->exception('已经关联发票的付款审核不可撤回');
        }

        $billCateId = 0;
        $attachIds  = [];
        if ($data['status'] == 1) {
            $billCateId = is_array($data['bill_cate_id']) ? $data['bill_cate_id'][0] ?? 0 : $data['bill_cate_id'];
            if ($billCateId < 1) {
                throw $this->exception('请选择账目分类');
            }

            $attachIds = is_array($data['attach_ids']) ? $data['attach_ids'] : explode(',', $data['attach_ids']);
            if (count($attachIds) > 1) {
                throw $this->exception('凭证不支持上传多图');
            }
            unset($data['attach_ids']);

            $data['pay_type'] = app(PaytypeService::class)->getTypeName((int) $data['type_id'], (int) $data['entid']);
        }

        $data['date'] = $data['date'] ? Carbon::make($data['date'])->toDateTimeString() : null;
        $billServices = app(BillService::class);
        $res          = $this->transaction(function () use ($id, $data, $billServices, $info, $attachIds, $billCateId) {
            $res1 = $res2 = $res3 = true;
            if ($data['status'] == 1) {
                unset($data['fail_msg'], $data['bill_cate_id']);
                $uuid        = $data['uid'];
                $data['uid'] = $info->uid;
                $res1        = $this->dao->update(['id' => $id], $data);
                $res1 && $this->attachSave($attachIds, $uuid, (int) $id);
                $res2 = $res1 && $billServices->saveOrUpdate($data['entid'], $id, 'client', [
                    'entid'     => $data['entid'],
                    'uid'       => $info->uid,
                    'cate_id'   => $billCateId,
                    'num'       => $data['num'],
                    'edit_time' => $data['date'],
                    'types'     => $data['types'] == 2 ? 0 : 1,
                    'mark'      => $data['mark'],
                    'link_id'   => $id,
                    'link_cate' => 'client',
                    'type_id'   => (int) $data['type_id'],
                    'pay_type'  => $data['pay_type'],
                ]);

                $info->num  = $data['num'];
                $info->mark = $data['mark'];

                // reload customer status
                if ($info->types != 2 && $info->eid > 0) {
                    $this->reloadCustomerStatus($info->eid);
                }

                // 生成续费提醒
                if ($info->types == 1 && $info->end_date && $info->end_date != '0000-00-00') {
                    Task::deliver(new BillRemindTask(1, (int) $id, (int) $info->uid));
                }
            } else {
                $res1 = $this->dao->update(['id' => $id], $data['status'] == 2 ? ['status' => $data['status'], 'fail_msg' => $data['fail_msg']] : ['status' => 0]);
                if ($data['status'] == -1) {
                    $billServices->delete(['link_id' => $id, 'link_cate' => 'client']);
                }
            }

            if (! $this->contractPrice($info->cid)) {
                throw $this->exception('合同订单信息更新失败');
            }

            if (! $this->invoicePrice($info->invoice_id, $data['entid'])) {
                throw $this->exception('发票信息更新失败');
            }
            return $res1 && $res2 && $res3;
        });

        if ($res) {
            // 财务审核已通过提醒
            if ($data['status'] == 1) {
                event('finance.verifySuccess.remind', [1, $info->load(['treaty', 'client', 'card', 'renew'])->toArray()]);
            }

            // 财务审核未通过提醒
            if ($data['status'] == 2) {
                $info['mark'] = $data['fail_msg'];
                event('finance.verifyFail.remind', [1, $info->load(['treaty', 'client', 'card', 'renew'])->toArray()]);
            }
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        }
        return $res;
    }

    /**
     * 修改备注信息.
     * @param mixed $id
     * @param mixed $mark
     * @return bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function setMark($id, $mark)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        $info->mark = $mark;
        return $info->save();
    }

    /**
     * 检查权限.
     * @param mixed $id
     * @return BaseModel|BuildsQueries|mixed|Model|object
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function checkAuth($id)
    {
        $uid  = uuid_to_uid((string) $this->uuId(false));
        $uids = app(FrameService::class)->getLevelSub($uid);
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        if ($info->uid != $uid && ! in_array($info->uid, $uids)) {
            throw $this->exception('common.operation.noPermission');
        }
        return $info;
    }

    /**
     * 获取续费合同订单ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRenewIds(int $type, int $entId = 1): array
    {
        $entId = $entId ?: 1;
        $date  = Carbon::now()->toDateString();
        switch ($type) {
            case 1:
                $endDate = Carbon::now()->addDays(30)->toDateString();
                $data    = $this->dao->getGroupList(
                    ['types' => 1, 'status' => 1, 'entid' => $entId, 'end_date' => [$date, $endDate]],
                    ['cate_id', 'cid'],
                    [DB::raw('max(date) as date'), DB::raw('max(end_date) as end_date'), 'cate_id', 'cid']
                )->each(function ($value) {
                    if ($this->count(['cid' => $value['cid'], 'cate_id' => $value['cate_id'], 'end_date_gt' => $value['end_date']])) {
                        $value['cid'] = 0;
                    }
                })->toArray();
                break;
            case 2:
                $data = $this->dao->getGroupList(
                    ['types' => 1, 'status' => 1, 'entid' => $entId, 'end_date_it' => $date],
                    ['cate_id', 'cid'],
                    [DB::raw('max(date) as date'), DB::raw('max(end_date) as end_date'), 'cate_id', 'cid']
                )->each(function ($value) use ($date) {
                    if ($this->count(['cid' => $value['cid'], 'cate_id' => $value['cate_id'], 'end_date_gt' => $date])) {
                        $value['cid'] = 0;
                    }
                })->toArray();
                break;
            case 3:
                $date = Carbon::now()->addDays(30)->toDateString();
                $data = $this->dao->getHaveList(['types' => 1, 'type' => 3, 'status' => 1, 'entid' => $entId], ['cid', 'cate_id'], $date, ['cid', 'date', 'cate_id', 'end_date'])->toArray();
                break;
            default:
                $data = [];
        }
        return array_filter(array_unique(array_column($data, 'cid')));
    }

    /**
     * 查找当前需要合同订单续费条数.
     * @return array|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function clientBillCountCache(int $entid)
    {
        return Cache::tags([CacheEnum::TAG_CUSTOMER])->remember(
            'client_bill_count_' . $entid,
            (int) sys_config('system_cache_ttl', 3600),
            fn () => $this->dao->selectModel(
                ['entid' => $entid, 'types' => 1],
            )->count()
        );
    }

    /**
     * 消息提醒.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function timer(int $entid, int $page, int $limit)
    {
        $billList = Cache::tags([CacheEnum::TAG_CUSTOMER])->remember(
            'client_bill_list_' . $entid . '_' . $page . '_' . $limit,
            (int) sys_config('system_cache_ttl', 3600),
            fn () => $this->dao->selectModel(
                ['entid' => $entid, 'types' => 1, 'status' => 1],
                ['id', 'uid', 'date', 'end_date', 'cid', 'eid', 'entid', 'cate_id'],
                ['treaty', 'client', 'card', 'renew'],
            )->forPage($page, $limit)->get()->toArray()
        );

        if (! $billList) {
            return;
        }

        foreach ($billList as $item) {
            $dateTimer = datetime_timestamp($item['end_date']);
            $nowTimer  = time();
            // 当前设置时间
            $dateNow = now()->setTimeFromTimeString(date('Y-m-d H:i:s', $dateTimer));
            // 过期后一天
            $dateAddDay = now()->setTimeFromTimeString(date('Y-m-d H:i:s', $dateTimer))->addDays(1);
            if ($dateTimer < $nowTimer && $dateAddDay->year == now()->year && $dateAddDay->day == now()->day && $dateAddDay->month == now()->month) {
                // 过期后1天
                $this->sendMessage($item, $entid, MessageType::CONTRACT_OVERDUE_REMIND_TYPE);
            } elseif ($dateTimer > $nowTimer) {
                // 前30天提醒
                // 转换成天
                $subTime = (int) (($dateTimer - $nowTimer) / 60 / 60 / 24);
                // 30天前
                if ($subTime == 30) {
                    $this->sendMessage($item, $entid, MessageType::CONTRACT_URGENT_RENEW_TYPE);
                }
            } elseif ($dateNow->year == now()->year && $dateNow->day == now()->day && $dateNow->month == now()->month) {
                // 结束当天
                $this->sendMessage($item, $entid, MessageType::CONTRACT_DAY_REMIND_TYPE);
            } else {
                continue;
            }
        }
    }

    /**
     * 发送消息.
     */
    public function sendMessage(array $item, int $entid, string $type)
    {
        if (empty($item['card']['user_id'])) {
            return;
        }
        $userRemindLogService = app(UserRemindLogService::class);
        // 发送过不再发送
        $exists = $userRemindLogService->exists([
            'entid'       => $entid,
            'user_id'     => $item['card']['user_id'],
            'year'        => now()->year,
            'month'       => now()->month,
            'day'         => now()->day,
            'remind_type' => $type,
            'relation_id' => $item['id'],
        ]);

        if ($exists) {
            return;
        }

        $message = app(MessageService::class)->getMessageContent($entid, $type);
        if ($message['template_time']) {
            if ($message['remind_time'] && date('H:i') == $message['remind_time']) {
                $res = true;
            } else {
                $res = false;
            }
        } else {
            $res = true;
        }

        if (! $res) {
            return;
        }
        event(new SystemMessageEvent(
            type: $type,
            params: [
                '合同订单名称' => $item['treaty']['title'] ?? '',
                '续费类型'     => $item['renew']['title'] ?? '',
                '合同订单金额' => $item['treaty']['price'] ?? '',
                '续费到期时间' => $item['end_date'] ?? '',
                '客户名称'     => $item['client']['name'] ?? '',
                '业务员'       => $item['card']['name'] ?? '',
            ],
            receiverIds: (int) $item['card']['user_id'],
            other: ['id' => $item['treaty']['id']],
            linkId: $item['treaty']['id']
        ));
        // 写入消息提醒记录数据
        $userRemindLogService->create([
            'remind_type' => $type,
            'user_id'     => $item['uid'],
            'relation_id' => $item['id'],
            'entid'       => $entid,
            'year'        => now()->year,
            'day'         => now()->day,
            'month'       => now()->month,
            'week'        => now()->week,
            'quarter'     => now()->quarter,
        ]);
    }

    /**
     * 排行榜.
     * @param mixed $where
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRankList($where): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getRankList($where, $page, $limit);
        $count          = $this->dao->getRankCount($where);
        return $this->listData($list, $count);
    }

    /**
     * 部门业绩排行榜.
     * @param mixed $where
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getFrameRankList($where): array
    {
        $frameService  = app(FrameService::class);
        $assistService = app(FrameAssistService::class);
        $targetService = app(TargetService::class);
        $frames        = $frameService->select(['is_show' => 1])?->toArray();
        $data          = [];
        $bills         = $this->dao->getRankList($where, 0, 0);
        [$start, $end] = explode('-', $where['date']);
        foreach ($frames as $frame) {
            $frameIds = array_unique(array_merge(array_column(array_filter($frames, function ($item) use ($frame) {
                return in_array((int) $frame['id'], $this->normalizeFramePath($item['path'] ?? []));
            }), 'id'), [$frame['id']]));
            $childUid      = array_unique($assistService->dao->setTrashed()->column(['frame_id' => $frameIds, 'is_mastart' => 1], 'user_id'));
            $childUid      = array_values(array_intersect($childUid, (array) ($where['uid'] ?? $childUid)));
            $filteredBills = array_filter($bills, function ($bill) use ($childUid) {
                return in_array($bill['uid'], $childUid);
            });
            // 使用 bcadd 累加保证精度
            $frame['price']  = array_reduce(array_column($filteredBills, 'price'), fn ($carry, $item) => bcadd((string) $carry, (string) ($item ?? 0), 2), '0');
            $frame['expend'] = array_reduce(array_column($filteredBills, 'expend'), fn ($carry, $item) => bcadd((string) $carry, (string) ($item ?? 0), 2), '0');
            if ($frame['price']) {
                $sum                = $targetService->getTargetFormMonth($this->getMonth($start, $end), $frame['id'], 1);
                $data[$frame['id']] = [
                    'id'         => $frame['id'],
                    'price'      => $frame['price'],
                    'expend'     => $frame['expend'],
                    'name'       => $frame['name'],
                    'ratio'      => (float) $sum ? bcmul(bcdiv((string) $frame['price'], (string) $sum, 4), '100', 1) : 100.00,
                    'net_amount' => bcsub((string) $frame['price'], (string) $frame['expend'], 2),
                ];
            }
        }
        usort($data, function ($a, $b) {
            return bccomp($b['price'], $a['price'], 2);
        });
        return $data;
    }

    private function normalizeFramePath(array|string|null $path): array
    {
        if (is_array($path)) {
            return array_map('intval', $path);
        }
        return array_values(array_filter(array_map('intval', explode('/', trim((string) $path, '/')))));
    }

    /**
     * 收入环比.
     * @throws BindingResolutionException
     */
    public function getRingRatioIncome(string $searchTime, array $userIds, string $ratioTime, array|int $types, array $categoryIds = []): array
    {
        $ratio = 0;
        $where = ['entid' => 1, 'uid' => $userIds, 'types' => $types];

        if ($categoryIds) {
            $where['contract_category'] = $categoryIds;
        }
        $price = sprintf('%.2f', $this->dao->getJoinSum(array_merge($where, ['date' => $searchTime])));
        if (! $ratioTime) {
            return compact('price', 'ratio');
        }
        $ratioPrice = sprintf('%.2f', $this->dao->getJoinSum(array_merge($where, ['date' => $ratioTime])));
        $ratio      = Statistics::ringRatio($price, $ratioPrice);
        return compact('price', 'ratio');
    }

    /**
     * 付款单号.
     */
    public function generateNo(int $entId = 0, int $len = 2): string
    {
        $date = date('ymd');
        if (! $entId) {
            $entId = 1;
        }
        do {
            $no = $date . '_' . Str::random($len);
        } while ($this->count(['bill_no' => $no, 'entid' => $entId]));
        return $no;
    }

    /**
     * 累计付款金额/审核中金额.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function priceStatistics(int $entId, int $eid = 0, int $cid = 0): array
    {
        $where = ['entid' => $entId];
        if ($eid) {
            $where['eid'] = $eid;
        }

        if ($cid) {
            $where['cid'] = $cid;
        }

        $unpaidPrice  = $contractPrice = '0.00';
        $auditPrice   = $this->dao->getSum($where, 0);
        $paymentPrice = $this->dao->getSum($where);

        $where['types'] = 0;
        $returnPrice    = $this->dao->getSum($where);

        if ($cid) {
            $contract = app(OrderService::class)->get($cid, ['id', 'price']);
            if ($contract) {
                $contractPrice = $contract->price;
            }

            $unpaidPrice = bcsub((string) $contractPrice, (string) $returnPrice, 2);
            if (bccomp($unpaidPrice, '0', 2) < 0) {
                $unpaidPrice = '0.00';
            }
        }

        return [
            'payment_price'  => sprintf('%.2f', $paymentPrice),
            'unpaid_price'   => sprintf('%.2f', $unpaidPrice),
            'audit_price'    => sprintf('%.2f', $auditPrice),
            'contract_price' => sprintf('%.2f', $contractPrice),
        ];
    }

    /**
     * 撤回.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function withdraw(int $id, int $entId): bool
    {
        $info = $this->dao->get(['id' => $id, 'entid' => $entId]);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        if ($info->status !== 0) {
            throw $this->exception('撤回状态异常');
        }

        return $this->transaction(function () use ($info, $entId) {
            $info->status = -1;
            $res          = $info->save();
            if (! $res) {
                throw $this->exception(__('common.operation.fail'));
            }

            if (! $this->contractPrice($info->cid)) {
                throw $this->exception('合同订单信息更新失败');
            }

            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CONTRACT_NOTICE, -1, $entId, $info->id));
            app(BillService::class)->delete(['link_id' => $info->id, 'link_cate' => 'client']);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return $res;
        });
    }

    /**
     * 获取合同订单金额.
     */
    public function getInvoicePrice(array $ids, int $entId = 1): string
    {
        $price = '0.00';
        if (! $ids) {
            return $price;
        }
        $list = $this->dao->select(['id' => $ids, 'entid' => $entId ?: 1, 'status' => 1], ['id', 'num', 'invoice_id'], ['invoice'])
            ->each(function ($item) use (&$price) {
                if (isset($item->invoice->status) && ! in_array($item->invoice->status, [-1, 2, 4])) {
                    throw $this->exception('申请失败,请核对付款单状态');
                }

                $price = bcadd((string) $price, (string) $item->num, 2);
            });

        if ($list->isEmpty()) {
            throw $this->exception('申请失败,请核对付款单');
        }
        return $price;
    }

    /**
     * 财务删除.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function financeDelete(int $id, int $entId): mixed
    {
        $info = $this->dao->get(['id' => $id, 'entid' => $entId]);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        if (! in_array($info->status, [1, 2])) {
            throw $this->exception(__('common.operation.noPermission'));
        }

        return $this->transaction(function () use ($id, $info, $entId) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.operation.fail'));
            }

            if (! $this->contractPrice($info->cid)) {
                throw $this->exception('合同订单信息更新失败');
            }

            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CONTRACT_NOTICE, 5, $entId, $info->id));
            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::BILL_NOTICE, 5, $entId, $info->id));

            app(BillService::class)->delete(['link_id' => $id, 'link_cate' => 'client']);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return $res;
        });
    }

    /**
     * 根据时间获取指定合同订单ID.
     * @throws \ReflectionException
     */
    public function getCidByDate(int $entId, string $date): array
    {
        return $this->dao->search(['entid' => $entId, 'date' => $date])->select(['cid'])->groupBy('cid')->pluck('cid')->toArray();
    }

    /**
     * 付款详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(array $where, array $field = ['*'], array $with = []): array
    {
        $info = $this->dao->get($where, $field, $with)?->toArray();
        if (! $info) {
            throw $this->exception('付款记录不存在');
        }
        $info['recall'] = $this->revoked($info['apply_id']);
        return $info;
    }

    /**
     * 合同订单统计
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getContractStatistics(int $cid, int $entId): array
    {
        $where         = ['entid' => $entId, 'cid' => $cid];
        $contractPrice = '0.00';

        // 已付
        $paymentPrice = $this->dao->getSum(array_merge($where, ['types' => [0, 1]]));
        // 支出
        $expensePrice = $this->dao->getSum(array_merge($where, ['types' => 2]));
        // 回款
        $returnPrice = $this->dao->getSum(array_merge($where, ['types' => 0]));

        $contract = app(OrderService::class)->get($cid, ['id', 'contract_price', 'surplus']);
        if ($contract) {
            $contractPrice = $contract->contract_price;
        }

        $unpaidPrice = bcsub((string) $contractPrice, (string) $returnPrice, 2);
        if (bccomp($unpaidPrice, '0', 2) < 0) {
            $unpaidPrice = '0.00';
        }

        return [
            'payment_price'  => sprintf('%.2f', $paymentPrice),
            'unpaid_price'   => sprintf('%.2f', $unpaidPrice),
            'expense_price'  => sprintf('%.2f', $expensePrice),
            'contract_price' => sprintf('%.2f', $contractPrice),
        ];
    }

    /**
     * 客户统计
     * @throws \ReflectionException
     */
    public function getCustomerStatistics(int $eid, int $entId): array
    {
        $where = ['entid' => $entId, 'eid' => $eid];
        // 已付
        $paymentPrice = $this->dao->getSum(array_merge($where, ['types' => [0, 1]]));
        // 支出
        $expensePrice = $this->dao->getSum(array_merge($where, ['types' => 2]));
        // 审核中收入
        $auditIncomePricePrice = $this->dao->getSum(array_merge($where, ['types' => [0, 1]]), 0);
        // 审核中支出
        $auditExpensePricePrice = $this->dao->getSum(array_merge($where, ['types' => 2]), 0);

        return [
            'payment_price'       => sprintf('%.2f', $paymentPrice),
            'expense_price'       => sprintf('%.2f', $expensePrice),
            'audit_income_price'  => sprintf('%.2f', $auditIncomePricePrice),
            'audit_expense_price' => sprintf('%.2f', $auditExpensePricePrice),
        ];
    }

    /**
     * 业绩趋势
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getTrendStatistics(string $time, array $userIds, array $categoryIds): array
    {
        if (! $time) {
            $time = date('Y/m/d', datetime_timestamp('-1 month')) . '-' . date('Y/m/d');
        }
        $time = explode('-', $time);
        if (count($time) != 2) {
            throw $this->exception('参数错误');
        }
        $xAxis    = $incomeData = $expendData = [];
        $dayCount = (datetime_timestamp($time[1]) - datetime_timestamp($time[0])) / 86400 + 1;
        if ($dayCount > 730) {
            [$format, $step, $dateFormat] = ['Y', '+1 year', '%Y'];
        } elseif ($dayCount <= 31) {
            [$format, $step, $dateFormat] = ['Y-m-d', '+1 day', '%Y-%m-%d'];
        } else {
            [$format, $step, $dateFormat] = ['Y-m', '+1 month', '%Y-%m'];
        }

        $timestamp = datetime_timestamp($time[0]);
        while ($timestamp <= datetime_timestamp($time[1])) {
            $xAxis[]   = date($format, $timestamp);
            $timestamp = strtotime($step, $timestamp);
        }

        $categoryIds = app(OrderService::class)->getStatisticsCategoryIds($categoryIds);
        $income      = $this->getTrendByTypes(['types' => [0, 1], 'status' => 1, 'uid' => $userIds, 'contract_category' => $categoryIds], $dateFormat);
        $expend      = $this->getTrendByTypes(['types' => 2, 'status' => 1, 'uid' => $userIds, 'contract_category' => $categoryIds], $dateFormat);
        foreach ($xAxis as $item) {
            $incomeData[] = isset($income[$item]) ? floatval($income[$item]) : 0;
            $expendData[] = isset($expend[$item]) ? floatval($expend[$item]) : 0;
        }
        $series[] = ['name' => '流入', 'data' => $incomeData];
        $series[] = ['name' => '流出', 'data' => $expendData];
        return compact('xAxis', 'series');
    }

    /**
     * 部门业绩分析统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFrameAnalysis(string $time, array $userIds, array $categoryIds = []): array
    {
        $frameService = app(FrameService::class);

        // 根据用户获取主部门数据
        $list = $frameService->getFrameByUserIds($userIds);
        if (! $list) {
            return [];
        }
        $where = ['entid' => 1, 'types' => [0, 1], 'date' => $time];
        if ($categoryIds) {
            $where['contract_category'] = $categoryIds;
        }
        return collect($list)->map(function ($item) use ($frameService, $where) {
            if (isset($item['uid']) && $item['uid']) {
                $frameUid = $item['uid'];
                unset($item['uid']);
            } else {
                $frameUid = $frameService->scopeUser((int) $item['id'], withMaster: true);
            }
            $price = $this->dao->getJoinSum(array_merge($where, ['uid' => $frameUid]));
            if ($price) {
                $item['price'] = sprintf('%.2f', $price);
                return $item;
            }
            return [];
        })->filter()->values()->all();
    }

    /**
     * 自动审核.
     * @return true
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function autoApprove(int $id, mixed $bill = null): bool
    {
        if (is_null($bill)) {
            $bill = $this->dao->get($id);
            if (! $bill) {
                throw $this->exception(__('common.operation.noExists'));
            }
        }

        $this->transaction(function () use ($bill) {
            $bill->status = 1;
            if (! $bill->save()) {
                throw $this->exception('账目审核失败');
            }

            if (! app(BillService::class)->saveOrUpdate(1, $bill->id, 'client', [
                'entid'     => 1,
                'uid'       => $bill->uid,
                'cate_id'   => $bill->bill_cate_id,
                'num'       => $bill->num,
                'edit_time' => $bill->date,
                'types'     => $bill->types == 2 ? 0 : 1,
                'mark'      => $bill->mark,
                'link_id'   => $bill->id,
                'link_cate' => 'client',
                'type_id'   => $bill->type_id,
                'pay_type'  => $bill->pay_type,
            ])) {
                throw $this->exception('账目记录更新失败');
            }

            $customerService = app(CustomerService::class);

            // calc customer status
            $customer = $customerService->get($bill->eid, ['id', 'customer_status']);
            if (! $customer) {
                throw $this->exception('客户数据获取异常');
            }

            if ($bill->types != 2) {
                $this->reloadCustomerStatus($customer->id);
            }

            // 生成续费提醒
            if ($bill->types == 1 && $bill->end_date && $bill->end_date != '0000-00-00') {
                Task::deliver(new BillRemindTask(1, (int) $bill->id, (int) $bill->uid));
            }

            if (! $this->contractPrice((int) $bill->cid)) {
                throw $this->exception('合同订单信息更新失败');
            }

            if ($bill->invoice_id && ! $this->invoicePrice($bill->invoice_id)) {
                throw $this->exception('发票信息更新失败');
            }
        });

        Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
        return true;
    }

    /**
     * 业绩统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function performanceStatistics(string $searchTime, array $userIds, string $ratioTime, array $categoryIds = []): array
    {
        $categoryIds = app(OrderService::class)->getStatisticsCategoryIds($categoryIds);
        return [
            'income'     => $this->getRingRatioIncome($searchTime, $userIds, $ratioTime, [0, 1], $categoryIds),
            'renew'      => $this->getRingRatioIncome($searchTime, $userIds, $ratioTime, 1, $categoryIds),
            'frame_rank' => $this->getFrameAnalysis($searchTime, $userIds, $categoryIds),
        ];
    }

    /**
     * 首页业绩统计
     * @return string[]
     * @throws \ReflectionException
     */
    public function getIncome(array $where, string $time = 'month'): array
    {
        return [
            'income'           => (string) $this->dao->getSum(array_merge($where, ['date' => $time])),
            'today_income'     => (string) $this->dao->getSum(array_merge($where, ['date' => 'today'])),
            'yesterday_income' => (string) $this->dao->getSum(array_merge($where, ['date' => 'yesterday'])),
        ];
    }

    /**
     * 审批通过操作.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function approveSuccess(int $applyId): void
    {
        $info = $this->dao->get(['apply_id' => $applyId]);
        if (! $info) {
            throw $this->exception('账目记录获取异常');
        }

        $this->transaction(function () use ($info) {
            if ($info->status != 1) {
                $info->status = 1;
                $info->save();
            }
            $this->reloadCustomerAndContractStatus($info);
        });
    }

    /**
     * 通过撤回.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function revoke(int $applyId): void
    {
        $bill = $this->dao->get(['apply_id' => $applyId], ['id', 'eid', 'cid']);
        if (! $bill) {
            return;
        }
        $this->transaction(function () use ($bill) {
            $this->dao->update($bill->id, ['status' => -1]);
            app(BillService::class)->delete(['link_id' => $bill->id]);
            $this->reloadCustomerAndContractStatus($bill);
        });
    }

    /**
     * 对外接口保存账目记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveOpenBill(array $data, string $type, int $uid): mixed
    {
        $rule = app(OpenapiRuleService::class)->get(['crud_id' => 0, 'url' => 'open/bill/' . $type], ['id', 'post_prams'])->toArray();
        if (! $rule) {
            throw $this->exception('无效的接口数据');
        }

        $form       = [];
        $postParams = json_decode($rule['post_prams'], true);
        $key        = match ($type) {
            'payment' => 'contract_refund_switch',
            'renewal' => 'contract_renew_switch',
            'expend'  => 'contract_disburse_switch',
            default   => '',
        };
        [$approveId, $content] = app(ApproveService::class)->getApproveConfig($key);

        $requestParams = array_column($postParams, 'name', 'symbol');
        if (isset($content['children'])) {
            foreach ($content['children'] as $value) {
                if (isset($requestParams[$value['symbol']])) {
                    $form[$value['field']] = $data[$requestParams[$value['symbol']]];
                }
            }
        }
        return app(ApproveApplyService::class)->saveForm($form, [], (int) $approveId, 0, $uid);
    }

    /**
     * 获取客户账单统计.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getBillCensus(int $year = 0, int $types = 0)
    {
        $year      = $year ?: now()->year;
        $startDate = Carbon::make($year . '-01-01');
        $data      = [];
        do {
            $searchTime = (clone $startDate)->startOfMonth()->format('Y/m/d') . '-' . (clone $startDate)->endOfMonth()->format('Y/m/d');
            $where      = ['date' => $searchTime, 'entid' => 1, 'types' => [0, 1]];
            if ($types) {
                $bills = $this->getFrameRankList($where);
                $arr   = [];
                foreach ($bills as $bill) {
                    $arr[$bill['id']] = $bill;
                }
                $data[] = $arr;
            } else {
                $bills = $this->dao->getRankList($where, 0, 0);
                $arr   = [];
                foreach ($bills as $bill) {
                    $arr[$bill['uid']] = $bill;
                }
                $data[] = $arr;
            }
            $startDate->addMonth();
        } while ($startDate->lt(Carbon::make($year . '-12-31')));
        return $data;
    }

    /**
     * 调整合同订单金额.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function contractPrice(int $cid, mixed $contract = null, bool $customerStatusReload = false): bool
    {
        $contractService = app(OrderService::class);
        if (! $contract) {
            $contract = $contractService->get($cid);
        }
        if (! $contract) {
            return false;
        }
        $contract->received = max($this->dao->getSum(['cid' => $cid, 'types' => -1, 'status' => 1]), 0);
        $paymentAmount      = max($this->dao->getSum(['cid' => $cid, 'types' => 0, 'status' => 1]), 0);
        $contract->surplus  = max(bcsub((string) $contract->contract_price, (string) $paymentAmount, 2), 0);
        $contract->save();

        if (! $customerStatusReload) {
            return true;
        }

        // reload customer status by contract
        $this->reloadCustomerStatus($contract->eid);
        return true;
    }

    /**
     * 重载客户状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function reloadCustomerStatus(int $eid): bool
    {
        $customerService = app(CustomerService::class);
        $customer        = $customerService->get($eid, ['id', 'customer_status']);
        if (! $customer) {
            return true;
        }

        // 跳过流失客户
        if ($customer->customer_status == 2) {
            return true;
        }
        return $this->transaction(function () use ($eid, $customer) {
            $tradedNum                 = $this->dao->count(['eid' => $eid, 'types' => [0, 1], 'status' => 1]);
            $customer->customer_status = $tradedNum > 0 ? 1 : 0;
            $customer->save();

            // TODO:写入动态记录
            return true;
        });
    }

    /**
     * 更新客户状态/合同订单金额.
     * @param mixed $bill
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function reloadCustomerAndContractStatus($bill): void
    {
        // reload contract price
        if ($bill->cid > 0) {
            $this->contractPrice($bill->cid, customerStatusReload: $bill->eid <= 0);
        }
        // reload customer status
        if ($bill->eid > 0) {
            $this->reloadCustomerStatus($bill->eid);
        }
    }

    /**
     * 调整发票付款金额.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function invoicePrice(int $invoiceId, int $entId = 1): bool
    {
        if (! $invoiceId) {
            return true;
        }

        $invoice = app(InvoiceService::class)->get(['id' => $invoiceId, 'entid' => $entId ?: 1], ['id', 'price']);
        if (! $invoice) {
            throw $this->exception('发票信息获取异常');
        }
        $price = $this->dao->getSum(['invoice_id' => $invoiceId]);
        if (bccomp((string) $price, $invoice->price, 2) != 0) {
            $invoice->price = $price;
            return $invoice->save();
        }
        return true;
    }

    /**
     * 获取.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getTrendByTypes(array $where, string $dateFormat): array
    {
        $field = [DB::raw("DATE_FORMAT(`date`, '{$dateFormat}') AS `days`"), DB::raw('SUM(`num`) AS `num`')];
        return array_column($this->dao->getJoinSearch($where, group: ['days'])->select($field)->get()->toArray(), 'num', 'days');
    }

    /**
     * 是否在撤销中.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function revoked(array|int $applyId)
    {
        $apply = app(ApproveApplyService::class)->select(['id' => $applyId], ['id', 'status', 'apply_id'], [
            'recall' => fn ($q) => $q->select(['id as recall_id', 'apply_id']),
        ]);
        $map = collect($apply)->mapWithKeys(function ($apply) {
            $hasRecall = ! empty($apply->recall);
            return [$apply->id => $hasRecall ? 1 : 0];
        })->all();
        return is_array($applyId) ? $map : $map[$applyId];
    }

    private function getMonth($startDate, $endDate)
    {
        $startDate   = Carbon::parse($startDate, 'Asia/Shanghai'); // 开始时间
        $endDate     = Carbon::parse($endDate, 'Asia/Shanghai');   // 结束时间
        $months      = [];
        $currentDate = $startDate->copy(); // 复制开始日期，避免修改原实例
        // 循环遍历时间段，逐月添加
        while ($currentDate->lte($endDate)) {
            // 记录当前月份（格式：年-月，如 2023-03）
            $months[] = $currentDate->format('Y-m');
            // 跳到下一个月的第一天（避免月份天数差异导致的问题）
            $currentDate->addMonthNoOverflow()->firstOfMonth();
        }
        return $months;
    }
}
