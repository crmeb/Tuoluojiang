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
use App\Constants\ScheduleEnum;
use App\Http\Contract\Customer\CustomerRemindInterface;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Dao\Customer\RemindDao;
use App\Http\Dao\Schedule\ScheduleRemindDao;
use App\Http\Service\Other\TaskService;
use App\Http\Service\System\SystemGroupDataService;
use App\Task\message\StatusChangeTask;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 提醒.
 */
class RemindService extends BaseService implements CustomerRemindInterface
{
    use ResourceServiceTrait;

    protected $tags;

    public function __construct(RemindDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param string $sort
     * @param array|string[] $with
     */
    public function getList(array $where, array $field = ['id', 'eid', 'cid', 'cate_id', 'user_id', 'bill_id', 'num', 'mark', 'time', 'rate', 'period', 'types', 'status'], $sort = 'id', array $with = ['card', 'client', 'contract', 'renew']): array
    {
        if (isset($where['cid']) && !$where['cid']){
            unset($where['cid']);
        }
        if (isset($where['eid']) && !$where['eid']){
            unset($where['eid']);
        }
        return parent::getList($where, $field, $sort, $with);
    }

    /**
     * 保存.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if (isset($data['user_id'], $data['entid'])) {
            $uuid  = uid_to_uuid($data['user_id']);
            $entId = $data['entid'];
        } else {
            $uuid            = (string) $this->uuId(false);
            $entId           = 1;
            $data['entid']   = $entId;
            $data['user_id'] = uuid_to_uid($uuid, $entId);
        }

        return $this->transaction(function () use ($data, $entId) {
            if (! $data['time']) {
                throw $this->exception('common.empty.attrs');
            }
            $data['uniqued'] = md5(json_encode($data) . time());
            $res1            = $this->dao->create($data);
            $save            = [
                'title'       => $data['mark'],
                'content'     => $data['mark'],
                'remind'      => 1,
                'remind_time' => $data['time'],
                'all_day'     => 1,
                'cid'         => $data['types'] ? ScheduleEnum::TYPE_CLIENT_RENEW : ScheduleEnum::TYPE_CLIENT_RETURN,
                'period'      => 0,
                'rate'        => 1,
                'days'        => [],
                'start_time'  => Carbon::parse($data['time'], config('app.timezone'))->startOfDay()->toDateTimeString(),
                'end_time'    => Carbon::parse($data['time'], config('app.timezone'))->endOfDay()->toDateTimeString(),
                'fail_time'   => $data['types'] ? null : Carbon::parse($data['time'], config('app.timezone'))->endOfDay()->toDateTimeString(),
                'link_id'     => $data['cid'],
                'uniqued'     => $data['uniqued'],
                'member'      => [$data['user_id']],
            ];
            $res2 = app(ScheduleInterface::class)->saveSchedule($data['user_id'], $entId, $save);
            return $res1 && $res2 ? $res1 : [];
        });
    }

    /**
     * 修改.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('common.operation.noExists');
        }
        if (! $data['time']) {
            throw $this->exception('common.empty.attrs');
        }
        $data['uniqued'] = md5(json_encode($data) . time());
        $uuid            = (string) $this->uuId(false);
        $entId           = 1;
        $schedule        = app(ScheduleInterface::class);
        return $this->transaction(function () use ($data, $info, $id, $schedule, $entId) {
            $res1 = $this->dao->update($id, $data);
            $res2 = true;
            if ($info->uniqued && $info->uniqued != $data['uniqued']) {
                $schedule->deleteRemind($info->user_id, $info->uniqued);
                $save = [
                    'title'       => $data['mark'],
                    'content'     => $data['mark'],
                    'remind'      => 1,
                    'remind_time' => $data['time'],
                    'all_day'     => 1,
                    'cid'         => $data['types'] ? ScheduleEnum::TYPE_CLIENT_RENEW : ScheduleEnum::TYPE_CLIENT_RETURN,
                    'period'      => 0,
                    'rate'        => 1,
                    'days'        => [],
                    'start_time'  => Carbon::parse($data['time'], config('app.timezone'))->startOfDay()->toDateTimeString(),
                    'end_time'    => Carbon::parse($data['time'], config('app.timezone'))->endOfDay()->toDateTimeString(),
                    'fail_time'   => $data['types'] ? null : Carbon::parse($data['time'], config('app.timezone'))->endOfDay()->toDateTimeString(),
                    'link_id'     => $info->cid,
                    'uniqued'     => $data['uniqued'],
                    'member'      => [$info->user_id],
                ];
                $res2 = $schedule->saveSchedule($info->user_id, $entId, $save);
            }
            return $res1 && $res2;
        });
    }

    /**
     * 修改备注信息.
     * @param mixed $id
     * @param mixed $mark
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setMark($id, $mark)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        return $this->transaction(function () use ($info, $mark) {
            if ($info->uniqued) {
                app(TaskService::class)->update(['uniqued' => $info->uniqued], ['name' => $mark]);
            }
            $info->mark = $mark;
            return $info->save();
        });
    }

    /**
     * 删除.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null): bool
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('common.operation.noExists');
        }
        if ($info->uniqued) {
            $userId = uuid_to_uid((string) $this->uuId(false), 1);
            /** @var ScheduleInterface $schedule */
            $schedule = app(ScheduleInterface::class);
            $schedule->deleteRemind($userId, $info->uniqued);
        }
        if ($info->cid && $info->types && ! $this->dao->exists(['cid' => $info->cid])) {
            app(OrderService::class)->update($info->cid, ['renew' => 0]);
        }
        Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
        $res = $this->dao->delete($id);
        if ($res) {
            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CUSTOMER_REMIND_NOTICE, CommonEnum::STATUS_DELETE, 1, $info->cid));
        }
        return (bool) $res;
    }

    /**
     * 提醒详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(array $where, array $field = ['*'], array $with = []): array
    {
        $info = $this->dao->get($where, $field, $with);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }
        return $info->toArray();
    }

    /**
     * 日程删除后.
     * @param mixed $uniqued
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function delScheduleAfter($uniqued): void
    {
        $this->dao->delete(['uniqued' => $uniqued]);
    }

    /**
     * 更新提醒周期
     */
    public function updatePeriod(int $status, string $uniqued, array $nextPeriod = []): bool
    {
        $info = $this->get(['uniqued' => $uniqued], ['id', 'cid', 'types']);
        if (! $info) {
            Log::error('日程状态同步付款提醒记录', ['schedule' => $uniqued]);
            return true;
        }

        if ($status < 1) {
            $res = $info->update(['this_period' => null, 'next_period' => null]);
        } else {
            $update = ['this_period' => $nextPeriod[0]];
            if ($status == 1) {
                $update = array_merge($update, ['next_period' => $nextPeriod[1]]);
            }

            $res = $info->update($info->types == 0 ? $update : array_merge($update, ['next_period' => $nextPeriod[1]]));
        }
        return $res;
    }

    /**
     * 获取未完成提醒合同订单ID.
     * @param mixed $userId
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getIncompleteRemindCidByUserId($userId): array
    {
        $remindWhere = ['user_id' => $userId, 'next_period_it' => now(config('app.timezone'))->startOfDay()->toDateTimeString()];
        return $this->dao->column($remindWhere, 'cid');
    }

    /**
     * 续费提醒.
     * @throws BindingResolutionException
     */
    public function remind(int $entId, int $id, int $userId)
    {
        $billInfo = app(PaymentService::class)->get($id);
        if (! $billInfo) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $contractTitle = app(OrderService::class)->value($billInfo->cid, 'title');

        $cateTitle  = '';
        $sourceData = app(SystemGroupDataService::class)->get(['id' => $billInfo->cate_id]);
        if ($sourceData) {
            $cateTitle = $sourceData?->value['title'] ?? '';
        }

        $this->resourceSave([
            'eid'     => $billInfo->eid,
            'cid'     => $billInfo->cid,
            'num'     => $billInfo->num,
            'mark'    => "{$contractTitle}{$cateTitle}该续费啦！",
            'types'   => 1,
            'cate_id' => $billInfo->cate_id,
            'bill_id' => $id,
            'entid'   => $entId,
            'user_id' => $userId,
            'time'    => Carbon::parse($billInfo->end_date, config('app.timezone'))->toDateString() . ' 09:00:00',
        ]);
    }

    /**
     * 更新提醒状态
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateStatus(int $id, int $status): bool
    {
        $info = $this->dao->get($id, ['id', 'status', 'uniqued']);
        if (! $info) {
            throw $this->exception('付款提醒信息获取失败');
        }
        if ($info->status != 0) {
            throw $this->exception('付款提醒关联数据异常, 请刷新后重试');
        }
        return $this->transaction(function () use ($info, $status) {
            $info->status       = $status;
            $res                = $info->save();
            $scheduleRemindInfo = app(ScheduleRemindDao::class)->get(['uniqued' => $info->uniqued], ['id', 'sid', 'uid', 'entid'], ['schedule']);
            if (! $scheduleRemindInfo || ! $scheduleRemindInfo->schedule) {
                throw $this->exception('待办数据获取失败');
            }
            $res2 = app(ScheduleInterface::class)->updateStatus(
                $scheduleRemindInfo->sid,
                (int) $scheduleRemindInfo->uid,
                $scheduleRemindInfo->entid,
                $status == 2 ? 3 : 2,
                [$scheduleRemindInfo->schedule->start_time, $scheduleRemindInfo->schedule->end_time]
            );

            return $res && $res2;
        });
    }

    /**
     * 获取待处理提醒合同订单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUrgentRenewalCid(bool $isExpire = false): array
    {
        $where = ['entid' => 1, 'status' => 0];
        if ($isExpire) {
            $where['time_lt'] = date('Y-m-d 00:00:00');
        } else {
            $where['time'] = 'future30';
        }
        return array_unique($this->dao->setTimeField('time')->column($where, 'cid'));
    }
}
