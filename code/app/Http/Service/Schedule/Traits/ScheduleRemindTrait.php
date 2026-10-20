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

namespace App\Http\Service\Schedule\Traits;

use App\Constants\CacheEnum;
use App\Constants\CommonEnum;
use App\Constants\NoticeEnum;
use App\Constants\ScheduleEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\RemindService;
use App\Http\Service\Notice\NoticeRecordService;
use App\Task\message\StatusChangeTask;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 日程提醒、定时与通知相关方法.
 */
trait ScheduleRemindTrait
{
    public function getEntListCache(): mixed
    {
        return Cache::tags([CacheEnum::TAG_SCHEDULE])->remember('schedule_ent_list', $this->cacheTtl, fn () => $this->remindDao->getEntList());
    }

    public function getEntCountCache(array $where): mixed
    {
        return Cache::tags([CacheEnum::TAG_SCHEDULE])->remember(
            md5('schedule_ent_count_' . json_encode($where)),
            $this->cacheTtl,
            fn () => $this->remindDao->count($where)
        );
    }

    public function scheduleTimer(array $where, int $page, int $limit): void
    {
        $list = Cache::tags([CacheEnum::TAG_SCHEDULE])->remember(
            md5('schedule_list_' . json_encode($where) . '_' . $page . '_' . $limit),
            $this->cacheTtl,
            fn () => $this->remindDao->setDefaultSort('id')->selectModel($where, with: ['schedule'])->forPage($page, $limit)->get()?->toArray(),
        );
        $now = now();
        foreach ($list as $item) {
            $time = $item['remind_day'] . ' ' . $item['remind_time'];
            // 结束的不再提醒
            if ($item['end_time'] != null && datetime_timestamp($item['end_time']) < $now->timestamp) {
                break;
            }
            $timeNowPeriod = now()->setTimeFromTimeString($time);
            // 提醒日期比当前日期大，没到提醒时间
            if ($timeNowPeriod->timestamp > $now->timestamp) {
                break;
            }
            $item['days'] = $item['days'] ? (is_string($item['days']) ? json_decode($item['days'], true) : $item['days']) : [];
            switch ($item['period']) {
                case ScheduleEnum::REPEAT_NOT:
                case ScheduleEnum::REPEAT_DAY:
                    $diffNum = $now->diffInDays($timeNowPeriod);
                    // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                    if (($diffNum % $item['rate']) == 0) {
                        // 按照天进行提醒
                        $dayTime = $timeNowPeriod->addDays($diffNum);
                        if ($dayTime->day == $now->day && $dayTime->timestamp == $now->timestamp) {
                            $this->sendMessage(1, $item['schedule'], $item['uid']);
                        }
                    }
                    break;
                case ScheduleEnum::REPEAT_WEEK:
                    $diffNum = $now->diffInWeeks($timeNowPeriod);
                    // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                    if (($diffNum % $item['rate']) == 0) {
                        $dayTime   = $timeNowPeriod->addWeeks($diffNum);
                        $dayOfWeek = $dayTime->dayOfWeekIso;
                        // 在周几内提醒,并且本周当前的时间等于设置的时间
                        if (in_array($dayOfWeek, $item['days']) && $dayTime->timestamp == $now->timestamp) {
                            $this->sendMessage(1, $item['schedule'], $item['uid']);
                        }
                    }
                    break;
                case ScheduleEnum::REPEAT_MONTH:
                    $diffNum = $now->diffInMonths($timeNowPeriod);
                    // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                    if (($diffNum % $item['rate']) == 0) {
                        $dayTime = $timeNowPeriod->addMonths($diffNum);
                        $day     = $dayTime->day;
                        if ($day > 10) {
                            $day = '0' . $day;
                        }
                        // 每月几号进行提醒
                        if (in_array($day, $item['days']) && $dayTime->timestamp == $now->timestamp) {
                            $this->sendMessage(1, $item['schedule'], $item['uid']);
                        }
                    }
                    break;
                case ScheduleEnum::REPEAT_YEAR:
                    $diffNum = $now->diffInYears($timeNowPeriod);
                    // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                    if (($diffNum % $item['rate']) == 0) {
                        $dayTime = $timeNowPeriod->addYears($item['rate']);
                        $day     = $dayTime->day;
                        if ($day > 10) {
                            $day = '0' . $day;
                        }
                        // 每月几号进行提醒
                        if (in_array($day, $item['days']) && $dayTime->timestamp == $now->timestamp) {
                            $this->sendMessage(1, $item['schedule'], $item['uid']);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * 发送消息.
     *
     * @param mixed $uid
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function sendMessage(int $entid, array $info, $uid = 0)
    {
        if ($uid) {
            $this->remindDao->update(['sid' => $info['id'], 'uid' => $uid], ['last_time' => date('Y-m-d H:i:s'), 'is_remind' => DB::raw('is_remind + 1')]);
            $userInfo = app()->get(AdminService::class)->get($info['uid']);
            if ($info['end_time']) {
                $this->clearScheduleCache((int) $uid);
            }
            switch ((int) $info['cid']) {
                case 1:// 个人提醒
                    event(new SystemMessageEvent(
                        type: NoticeEnum::DEALT_PRESON_WORK_TYPE,
                        params: [
                            '待办内容' => $info['title'],
                            '备注'     => $info['title'],
                            '创建人'   => $userInfo['name'] ?? '',
                            '创建时间' => $info['created_at'],
                        ],
                        receiverIds: (int) $uid,
                        other: [
                            'id'         => $info['id'],
                            'start_time' => $info['start_time'],
                            'end_time'   => $info['end_time'],
                        ],
                        linkId: $info['id'],
                        linkStatus: 0,
                    ));
                    break;
                case 2:// 客户跟进
                    $followInfo = app()->get(FollowUpService::class)->get(['eid' => $info['link_id']], ['id', 'eid'], ['client'])?->toArray();
                    event(new SystemMessageEvent(
                        type: NoticeEnum::DEALT_CLIENT_WORK_TYPE,
                        params: [
                            '待办内容' => $info['title'],
                            '备注'     => $info['content'],
                            '创建人'   => $userInfo['name'] ?? '',
                            '创建时间' => $info['created_at'],
                            '客户名称' => $followInfo['client']['name'] ?? '',
                        ],
                        receiverIds: (int) $uid,
                        other: ['id' => $info['link_id']],
                        linkId: $followInfo['eid'],
                        linkStatus: 0
                    ));
                    break;
                case 3:// 续费提醒
                case 4:// 回款
                    $clientRemind = app()->get(RemindService::class)->get(['cid' => $info['link_id']], ['id', 'eid', 'cid'], ['client', 'treaty'])?->toArray();
                    event(new SystemMessageEvent(
                        type: NoticeEnum::DEALT_MONEY_WORK_TYPE,
                        params: [
                            '待办内容'         => $info['title'],
                            '待办类型'         => $info['cid'] == ScheduleEnum::TYPE_CLIENT_RENEW ? '续费' : '回款',
                            '备注'             => $info['content'],
                            '创建人'           => $userInfo['name'] ?? '',
                            '创建时间'         => $info['created_at'],
                            '客户名称'         => $clientRemind['client']['name'] ?? '',
                            '关联订单名称' => $clientRemind['treaty']['title'] ?? '',
                            '订单金额'     => $clientRemind['treaty']['price'] ?? '',
                            '订单开始时间' => $clientRemind['treaty']['start_date'] ?? '',
                            '订单结束时间' => $clientRemind['treaty']['end_date'] ?? '',
                        ],
                        receiverIds: (int) $uid,
                        other: ['id' => $info['link_id']],
                        linkId: $clientRemind['cid'],
                        linkStatus: 0
                    ));
                    break;
                default:
                    event(new SystemMessageEvent(
                        type: NoticeEnum::DEALT_PRESON_WORK_TYPE,
                        params: [
                            '待办内容' => $info['title'],
                            '备注'     => $info['title'],
                            '创建人'   => $userInfo['name'] ?? '',
                            '创建时间' => $info['created_at'],
                        ],
                        receiverIds: (int) $uid,
                        other: ['id' => $info['id']],
                        linkId: $info['id'],
                        linkStatus: 0,
                    ));
            }
        }
    }

    /**
     * 通过提醒删除日程.
     * @param mixed $unique
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteRemind(int $uid, string $unique): bool
    {
        $remindInfo = toArray($this->remindDao->get(['uid' => $uid, 'uniqued' => $unique], ['id', 'sid']));
        if (! $remindInfo) {
            return true;
        }
        $res = $this->transaction(function () use ($remindInfo) {
            $res1 = $this->remindDao->delete($remindInfo['id']);
            $res2 = $this->dao->delete($remindInfo['sid']);
            return $res1 && $res2;
        });
        $res && $this->dispatchTodoSync((int) $remindInfo['sid'], 'delete');
        return $res;
    }

    /**
     * 处理数据.
     */
    protected function getRemindInfo(array $data): array
    {
        if (isset($data['remind_time']) && $data['remind_time']) {
            [$remind['remind_day'], $remind['remind_time']] = $this->getRemindTime($data['remind_time']);
        } else {
            [$remind['remind_day'], $remind['remind_time']] = $this->getRemindTime($data['start_time'], $data['remind']);
        }
        $remind['period']   = $data['period'];
        $remind['end_time'] = $data['fail_time'];
        $remind['uniqued']  = isset($data['uniqued']) && $data['uniqued'] ? $data['uniqued'] : md5(json_encode($data) . time());
        switch ((int) $data['period']) {
            case ScheduleEnum::REPEAT_DAY:
            case ScheduleEnum::REPEAT_YEAR:
                if (! $data['rate']) {
                    throw $this->exception('请选择重复频率');
                }
                $remind['rate'] = $data['rate'];
                $remind['days'] = json_encode($data['days']);
                break;
            case ScheduleEnum::REPEAT_WEEK:
            case ScheduleEnum::REPEAT_MONTH:
                if (! $data['days']) {
                    throw $this->exception('请选择提醒时间');
                }
                $remind['days'] = $data['days'] ?: null;
                $remind['rate'] = $data['rate'];
                break;
            default:
                $remind['end_time'] = $data['end_time'];
                $remind['rate']     = $data['rate'];
                $remind['days']     = null;
        }
        return $remind;
    }

    protected function getRemindTime($startTime, $remind = 0): array
    {
        return match ((int) $remind) {
            1 => [
                Carbon::parse($startTime, $this->timeZone)->subMinutes(5)->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subMinutes(5)->toTimeString(),
            ],
            2 => [
                Carbon::parse($startTime, $this->timeZone)->subMinutes(15)->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subMinutes(15)->toTimeString(),
            ],
            3 => [
                Carbon::parse($startTime, $this->timeZone)->subMinutes(30)->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subMinutes(30)->toTimeString(),
            ],
            4 => [
                Carbon::parse($startTime, $this->timeZone)->subHour()->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subHour()->toTimeString(),
            ],
            5 => [
                Carbon::parse($startTime, $this->timeZone)->subHours(2)->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subHours(2)->toTimeString(),
            ],
            6 => [
                Carbon::parse($startTime, $this->timeZone)->subDay()->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subDay()->toTimeString(),
            ],
            7 => [
                Carbon::parse($startTime, $this->timeZone)->subDays(2)->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subDays(2)->toTimeString(),
            ],
            8 => [
                Carbon::parse($startTime, $this->timeZone)->subWeek()->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->subWeek()->toTimeString(),
            ],
            default => [
                Carbon::parse($startTime, $this->timeZone)->toDateString(),
                Carbon::parse($startTime, $this->timeZone)->toTimeString(),
            ],
        };
    }

    protected function getRemindText($startTime, $remindTime): array
    {
        $remind = Carbon::parse($remindTime, $this->timeZone)->timestamp;
        return match ($remind) {
            Carbon::parse($startTime, $this->timeZone)->subMinutes(5)->timestamp => [
                'ident' => 1,
                'text'  => '提前5分钟',
            ],
            Carbon::parse($startTime, $this->timeZone)->subMinutes(15)->timestamp => [
                'ident' => 2,
                'text'  => '提前15分钟',
            ],
            Carbon::parse($startTime, $this->timeZone)->subMinutes(30)->timestamp => [
                'ident' => 3,
                'text'  => '提前30分钟',
            ],
            Carbon::parse($startTime, $this->timeZone)->subHour()->timestamp => [
                'ident' => 4,
                'text'  => '提前1小时',
            ],
            Carbon::parse($startTime, $this->timeZone)->subHours(2)->timestamp => [
                'ident' => 5,
                'text'  => '提前2小时',
            ],
            Carbon::parse($startTime, $this->timeZone)->subDay()->timestamp => [
                'ident' => 6,
                'text'  => '提前1天',
            ],
            Carbon::parse($startTime, $this->timeZone)->subDays(2)->timestamp => [
                'ident' => 7,
                'text'  => '提前2天',
            ],
            Carbon::parse($startTime, $this->timeZone)->subWeek()->timestamp => [
                'ident' => 8,
                'text'  => '提前1周',
            ],
            default => [
                'ident' => 0,
                'text'  => '任务开始时',
            ]
        };
    }

    /**
     * 更新消息状态
     * @throws BindingResolutionException
     */
    protected function changeNoticeStatus(int $entId, array $info, string $startTime = '', string $endTime = '', bool $withAfter = false)
    {
        [$linkId, $type] = match ((int) $info['cid']) {
            2 => [$info['link_id'], NoticeEnum::DEALT_CLIENT_WORK_TYPE],
            3, 4 => [$info['link_id'], NoticeEnum::DEALT_MONEY_WORK_TYPE],
            default => [$info['id'], NoticeEnum::DEALT_PRESON_WORK_TYPE],
        };

        $where = ['entid' => $entId, 'template_type' => $type, 'link_status' => 0, 'link_id' => $linkId];
        app()->get(NoticeRecordService::class)->select($where, ['id', 'other'])->each(function ($item) use ($startTime, $endTime, $withAfter) {
            $other = is_string($item->other) ? json_decode($item->other, true) : $item->other;

            if (! isset($other['start_time']) && ! isset($other['end_time'])) {
                $item->link_status = CommonEnum::STATUS_DELETE;
                $item->save();
            } else {
                $recordStartTime = $other['start_time'] ?? '';
                $recordEndTime   = $other['end_time'] ?? '';
                if (($withAfter && $recordStartTime >= $startTime && $recordEndTime >= $endTime) || ($recordStartTime == $startTime && $recordEndTime == $endTime)) {
                    $item->link_status = CommonEnum::STATUS_DELETE;
                    $item->save();
                }
            }
        });
    }
}
