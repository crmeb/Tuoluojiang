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

namespace App\Http\Service\User;

use App\Constants\CacheEnum;
use App\Constants\CustomerNoticeEnum;
use App\Constants\CommonEnum;
use App\Http\Dao\User\UserScheduleDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\RemindService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Other\TaskService;
use App\Task\message\MessageSendTask;
use App\Task\message\StatusChangeTask;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\options\TaskOptions;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 提醒事项
 * Class UserScheduleService.
 */
class UserScheduleService extends BaseService
{
    use ResourceServiceTrait;

    protected $TYPE_ASSESS = ['name' => '绩效考核', 'key' => 'assess', 'color' => '#1890FF'];

    /**
     * @var string[]
     */
    protected $PERSONAL = ['name' => '个人提醒', 'key' => 'personal', 'color' => '#1890FF'];

    /**
     * @var string[]
     */
    protected $CLIENT_TRACK = ['name' => '客户跟进', 'key' => 'client_track', 'color' => '#19BE6B'];

    /**
     * @var string[]
     */
    protected $CLIENT_RENEW = ['name' => '付款提醒', 'key' => 'client_renew', 'color' => '#FB7A4D'];

    /**
     * @var string[]
     */
    protected $REPORT_RENEW = ['name' => '汇报待办', 'key' => 'report_renew', 'color' => '#ED4014'];

    /**
     * @var string[]
     */
    protected array $CLIENT_RETURN = ['name' => '订单回款', 'key' => 'client_return', 'color' => '#A277FF'];

    /**
     * @var array|string[]
     */
    protected array $config = [
        'assess'        => '绩效考核',
        'personal'      => '个人提醒',
        'client_track'  => '客户跟进',
        'client_renew'  => '付款提醒',
        'client_return' => '订单回款',
        'report_renew'  => '汇报待办',
    ];

    protected $endTime;

    protected $entId = 0;

    protected $taskClass = 'user.schedule';

    protected $remindTime;

    /**
     * 日程
     * UserScheduleServices constructor.
     */
    public function __construct(UserScheduleDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取企业分组缓存ID.
     *
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function getEntListCache()
    {
        return Cache::tags([CacheEnum::TAG_SCHEDULE])->remember('schedule_ent_list', (int) sys_config('system_cache_ttl', 3600), fn () => $this->dao->getEntList());
    }

    /**
     * 获取当前企业待办条数.
     *
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function getEntCountCache(array $where)
    {
        return Cache::tags([CacheEnum::TAG_SCHEDULE])->remember(
            md5('schedule_ent_count_' . json_encode($where)),
            (int) sys_config('system_cache_ttl', 3600),
            fn () => $this->dao->count($where)
        );
    }

    /**
     * 设置结束时间.
     *
     * @return $this
     */
    public function setEndTime(string $endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * 设置任务类.
     *
     * @return $this
     */
    public function setTaskClass(string $class)
    {
        $this->taskClass = $class;

        return $this;
    }

    /**
     * 设置提醒时间.
     *
     * @return $this
     */
    public function setRemindTime(string $dateTime)
    {
        $this->remindTime = $dateTime;

        return $this;
    }

    /**
     * @return array
     */
    public function getScheduleTypes()
    {
        return [
            $this->PERSONAL,
            $this->CLIENT_TRACK,
            $this->CLIENT_RENEW,
            $this->CLIENT_RETURN,
            $this->REPORT_RENEW,
        ];
    }

    /**
     * 列表.
     *
     * @param array|string[] $field
     * @param string $sort
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        $period = $where['period'];
        $types  = is_array($where['types']) ? $where['types'] : [$where['types']];
        if (in_array('client_renew', $types)) {
            $types[] = 'client_return';
        }
        $start = $where['time'];
        $end   = $where['end'];
        unset($where['types'], $where['end'], $where['time'], $where['period']);
        $field = ['id', 'types', 'content', 'mark', 'remind', 'repeat', 'period', 'rate', 'remind_time', 'remind_day', 'days', 'end_time', 'link_id', 'created_at'];
        if ($period >= 0) {
            $detailKey = md5($where['uid'] . $period . json_encode($types) . $start . $end . 'schedule');
            $listKey   = md5($where['uid'] . 'schedule');
            if (Cache::tags([CacheEnum::TAG_SCHEDULE])->has($listKey)) {
                if (Cache::tags([CacheEnum::TAG_SCHEDULE])->has($detailKey)) {
                    $data = json_decode(Cache::tags([CacheEnum::TAG_SCHEDULE])->get($detailKey), true);
                } else {
                    $list = json_decode(Cache::tags([CacheEnum::TAG_SCHEDULE])->get($listKey), true);
                    $data = $this->checkPeriod($period, $detailKey, $list, $types, $start, $end);
                }
            } else {
                $list = $this->listHandler($this->dao->getList($where, $field));
                Cache::tags([CacheEnum::TAG_SCHEDULE])->put($listKey, json_encode($list));
                $data = $this->checkPeriod($period, $detailKey, $list, $types, $start, $end);
            }
        } else {
            [$page, $limit] = $this->getPageValue();
            $list           = $this->listHandler($this->dao->getList($where, $field, $page, $limit));
            $count          = $this->dao->getCount($where);
            $data           = $this->listData($list, $count);
        }

        return $data;
    }

    /**
     * 保存日程.
     *
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $data = $this->checkData($data);
        if ($this->dao->exists($data)) {
            throw $this->exception('已存在相同内容，请勿重复添加');
        }
        $data['entid'] = 1;
        $res           = $this->transaction(function () use ($data) {
            return $this->dao->create($data);
            //            if ($data['remind']) {
            //                // 添加定时任务
            //                $this->openTask($data, $res1->id);
            //            }
        });
        return $res && Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
    }

    /**
     * 修改支付方式获取表单信息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id): array
    {
        $info = toArray($this->dao->get($id));
        if (! $info) {
            throw $this->exception('查询的日程不存在');
        }

        $clientService   = app()->get(CustomerService::class);
        $contractService = app()->get(OrderService::class);
        $followService   = app()->get(FollowUpService::class);
        $remindService   = app()->get(RemindService::class);
        switch ($info['types']) {
            case 'client_track':
                $info['title'] = $clientService->value($followService->value($info['link_id'], 'eid'), 'name');
                break;
            case 'client_renew':
                $info['title'] = $contractService->value($remindService->value($info['link_id'], 'cid'), 'title');
                break;
        }
        return $info;
    }

    /**
     * 修改日程.
     *
     * @param int $id
     *
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $data = $this->checkData($data);
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('未找到待修改的记录');
        }

        $res = $this->transaction(function () use ($id, $data, $info) {
            $res1 = $this->dao->update($id, $data);
            app()->get(TaskService::class)->delete($info->uniqued, 'uniqued');
            //            if ($data['remind']) {
            //                // 添加定时任务
            //                $this->openTask($data, $id);
            //            }
            return $res1;
        });
        return $res && Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
    }

    /**
     * 删除日程.
     *
     * @param mixed $id
     * @param mixed $uid
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function scheduleDelete($id, $uid)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('未找到待删除的记录');
        }
        if (! in_array($info['types'], ['personal', 'report_renew'])) {
            throw $this->exception('非个人提醒无法执行删除操作');
        }
        if ($info['uid'] != $uid) {
            throw $this->exception('非本人提醒无法执行删除操作!');
        }

        $res = $this->transaction(function () use ($id, $info) {
            $res1 = $this->dao->delete($id, 'id');
            if ($info['remind']) {
                app()->get(TaskService::class)->delete($info->uniqued, 'uniqued');
            }
            if ($res1) {
                Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CUSTOMER_SCHEDULE_NOTICE, CommonEnum::STATUS_DELETE, 1, $info->id));
            }
            return $res1;
        });
        return $res && Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
    }

    /**
     * 处理数据.
     *
     * @param mixed $data
     * @return mixed
     */
    public function checkData($data)
    {
        if (! array_key_exists($data['types'], $this->config)) {
            throw $this->exception('无效的业务类型');
        }
        switch ($data['types']) {
            case 'personal':
                $data['entid'] = 0;
                break;
            case 'report_renew':
                break;
            default:
                $data['uid'] = '';
        }
        switch ($data['repeat']) {
            case 0:// 每天
            case 1:// 每周
            case 2:// 每月
            case 3:// 每年
                $data['period'] = $data['repeat'];
                $data['rate']   = 1;
                unset($data['days']);
                break;
            case 4:// 自定义
                if ($data['period'] === '') {
                    throw $this->exception('请选择重复周期');
                }
                if (! $data['rate']) {
                    throw $this->exception('请选择重复频率');
                }
                if (! $data['end_time']) {
                    throw $this->exception('请选择结束时间');
                }
                switch ($data['period']) {
                    case 0:// 天重复
                    case 3:// 年重复
                        unset($data['days']);
                        break;
                    case 1:// 周重复
                    case 2:// 月重复
                        if (! $data['days']) {
                            throw $this->exception('请选择提醒时间');
                        }
                        $data['days'] = json_encode($data['days']);
                        break;
                    default:
                        throw $this->exception('无效的重复周期');
                }
                break;
            default:// 不重复
                $data['period'] = -1;
                $data['rate']   = 0;
                if ($data['remind'] && $data['repeat'] == -1) {
                    $data['end_time'] = $data['remind'];
                }
                unset($data['days']);
        }
        if ($data['remind']) {
            $data['remind_day']  = Carbon::parse($data['remind'])->timezone(config('app.timezone'))->toDateString();
            $data['remind_time'] = Carbon::parse($data['remind'])->timezone(config('app.timezone'))->toTimeString();
            $data['remind']      = 1;
            $data['uniqued']     = md5(json_encode($data) . time());
        }

        return $data;
    }

    /**
     * 开启定时任务
     *
     * @param false $record
     * @param mixed $param
     *
     * @return bool
     * @throws BindingResolutionException
     */
    public function openTask(array $data, $param, $record = false)
    {
        if (isset($data['remind_day'], $data['remind_time'])) {
            $this->setRemindTime($data['remind_day'] . ' ' . $data['remind_time']);
        }
        if (! $this->remindTime) {
            throw $this->exception(__('common.empty.attrs'));
        }
        if ($record) {
            if ($this->dao->exists($data)) {
                throw $this->exception('已存在相同内容，请勿重复添加');
            }

            $timeString                                 = Carbon::parse($this->remindTime, config('app.timezone'))->toDateTimeString();
            [$data['remind_day'], $data['remind_time']] = explode(' ', $timeString);
            if ($data['remind'] && $data['repeat'] == -1) {
                $data['end_time'] = $data['remind_day'] . ' ' . $data['remind_time'];
            }
            if ($data['repeat'] == 4) {
                switch ($data['period']) {
                    case 1:
                        $data['days'] = json_encode([\Illuminate\Support\Carbon::make($data['remind_day'])->dayOfWeekIso]);
                        break;
                    case 2:
                        $data['days'] = json_encode([\Illuminate\Support\Carbon::make($data['remind_day'])->format('d')]);
                        break;
                }
            }
            $this->dao->create($data);
        }
        Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
        return true;
    }

    /**
     * 添加定时任务
     *
     * @param string $param
     *
     * @return bool
     * @throws BindingResolutionException
     */
    public function addTask(string $name, string $uniqued, string $period, int $rate, int $persist = 1, int $day = 0, $param = '')
    {
        $optione            = new TaskOptions($name);
        $optione->persist   = $persist;
        $optione->className = $this->taskClass;
        $optione->entid     = $this->entId;
        // 设置任务参数
        $optione->uniqued  = $uniqued;
        $optione->rate     = $rate;
        $optione->end_time = $this->endTime;
        // 设置生成时间
        $remind = Carbon::parse($this->remindTime)->timezone(config('app.timezone'));
        // 设置执行周期
        switch ($period) {
            case 0:// 天
                $optione->period         = 'day';
                $optione->intervalHour   = (int) $remind->format('H');
                $optione->intervalMinute = (int) $remind->format('i');
                $optione->intervalSecond = (int) $remind->format('s');
                break;
            case 1:// 周
                $optione->period         = 'week';
                $optione->intervalWeek   = $day ? (int) $day : (int) $remind->format('w');
                $optione->intervalHour   = (int) $remind->format('H');
                $optione->intervalMinute = (int) $remind->format('i');
                $optione->intervalSecond = (int) $remind->format('s');
                break;
            case 2:// 月
                $optione->period         = 'month';
                $optione->intervalDay    = $day ? (int) $day : (int) $remind->format('d');
                $optione->intervalHour   = (int) $remind->format('H');
                $optione->intervalMinute = (int) $remind->format('i');
                $optione->intervalSecond = (int) $remind->format('s');
                break;
            case 3:// 年
                $optione->period         = 'year';
                $optione->intervalMonth  = (int) $remind->format('m');
                $optione->intervalDay    = (int) $remind->format('d');
                $optione->intervalHour   = (int) $remind->format('H');
                $optione->intervalMinute = (int) $remind->format('i');
                $optione->intervalSecond = (int) $remind->format('s');
                break;
            default:
                $optione->period       = 'once';
                $optione->persist      = 0;
                $optione->intervalTime = (int) $remind->timestamp;
        }
        $optione->setParameter($param);
        return app()->get(TaskService::class)->addTask($optione);
    }

    /**
     * 处理周期数据.
     *
     * @param mixed $period
     * @param mixed $detailKey
     * @param mixed $list
     * @param mixed $types
     * @param mixed $start
     * @param mixed $end
     * @return array
     * @throws BindingResolutionException
     */
    public function checkPeriod($period, $detailKey, $list, $types, $start, $end)
    {
        $data = match ($period) {
            0       => $this->getDaySchedule($list, $types, $start, $end),
            1       => $this->getWeekSchedule($list, $types, $start, $end),
            2       => $this->getMonthSchedule($list, $types, $start, $end),
            default => [],
        };
        $data && Cache::tags([CacheEnum::TAG_SCHEDULE])->put($detailKey, json_encode($data), 300);
        return $data;
    }

    /**
     * 月提醒数量.
     *
     * @param mixed $where
     * @return array|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getMonthScheduleCount($where)
    {
        $start = Carbon::parse($where['time'])->timezone(config('app.timezone'));
        if (Carbon::parse($where['end'])->timezone(config('app.timezone'))->timestamp > now()->timestamp) {
            $end = now()->timezone(config('app.timezone'));
        } else {
            $end = Carbon::parse($where['end'])->timezone(config('app.timezone'));
        }
        $timeZone = CarbonPeriod::create($start->toDateString(), $end->toDateString())->toArray();
        $types    = is_array($where['types']) ? $where['types'] : [$where['types']];
        unset($where['time'], $where['end'], $where['types'], $where['period']);
        $listKey = md5(json_encode($where) . 'schedule');
        if (Cache::tags([CacheEnum::TAG_SCHEDULE])->has($listKey)) {
            $list = json_decode(Cache::tags([CacheEnum::TAG_SCHEDULE])->get($listKey), true);
        } else {
            $list = $this->dao->getList($where, ['id', 'types', 'content', 'mark', 'remind', 'repeat', 'period', 'rate', 'remind_time', 'remind_day', 'days', 'end_time', 'link_id', 'created_at']);
            Cache::tags([CacheEnum::TAG_SCHEDULE])->put($listKey, json_encode($list));
        }
        $data          = [];
        $recordService = app()->get(UserScheduleRecordService::class);
        foreach ($timeZone as $day) {
            $day  = $day->toDateString();
            $item = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    if ($this->haveSchedule($value, $day) && in_array($value['types'], $types)) {
                        $item[] = $value;
                    }
                }
            }
            $data[] = [
                'date'   => $day,
                'count'  => count($item),
                'finish' => empty($item) ? 0 : $recordService->count(['schedultid' => array_column($item, 'id'), 'status' => 1, 'remind_day' => $day]),
            ];
        }

        return $data;
    }

    /**
     * 执行定时任务
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function timer(array $where, int $page, int $limit)
    {
        $entid = $where['entid'];
        $list  = Cache::tags([CacheEnum::TAG_SCHEDULE])->remember(
            md5('schedule_list_' . json_encode($where) . '_' . $page . '_' . $limit),
            (int) sys_config('system_cache_ttl', 3600),
            fn () => $this->dao->setDefaultSort('id')->selectModel($where)->forPage($page, $limit)->get()->toArray()
        );

        $now = now();

        foreach ($list as $item) {
            $time    = $item['remind_day'] . ' ' . $item['remind_time'];
            $timeNow = now()->setTimeFromTimeString($time);
            switch ($item['repeat']) {
                case 0:// 每天
                    if ($now->toTimeString() === $timeNow->toTimeString()) {
                        $this->sendMessage($entid, $item);
                    }
                    break;
                case -1:// 不重复
                    // 不重复的执行一次就不要在提醒了
                    if ($item['period'] == -1 && $item['repeat'] == -1 && $item['is_remind']) {
                        break;
                    }
                    // 过期修改状态
                    if ($time < $now->toDateTimeString()) {
                        $this->dao->update($item['id'], ['is_remind' => 1]);

                        Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
                    }
                    if ($now->toDateTimeString() === $time) {
                        $this->sendMessage($entid, $item);
                    }
                    break;
                case 1:// 每周
                    if ($timeNow->dayOfWeek === $now->dayOfWeek && $timeNow->toTimeString() === $now->toTimeString()) {
                        $this->sendMessage($entid, $item);
                    }
                    break;
                case 2:// 每月
                    if ($timeNow->day === $now->day && $timeNow->toTimeString() === $now->toTimeString()) {
                        $this->sendMessage($entid, $item);
                    }
                    break;
                case 3:// 每年
                    if ($timeNow->month === $now->month && $timeNow->day === $now->day && $timeNow->toTimeString() === $now->toTimeString()) {
                        $this->sendMessage($entid, $item);
                    }
                    break;
                case 4:// 自定义
                    // 结束的不再提醒
                    if ($item['end_time'] != '0000-00-00 00:00:00' && $item['end_time'] < $now->toDateTimeString()) {
                        break;
                    }
                    $timeNowPeriod = now()->setTimeFromTimeString($time);
                    // 提醒日期比当前日期大，没到提醒时间
                    if ($timeNowPeriod->timestamp > $now->timestamp) {
                        break;
                    }

                    switch ($item['period']) {
                        case 0:
                            $diffNum = $now->diffInDays($timeNowPeriod);
                            // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                            if (($diffNum % $item['rate']) == 0) {
                                // 按照天进行提醒
                                $dayTime = $timeNowPeriod->addDays($diffNum);
                                if ($dayTime->day == $now->day && $dayTime->toTimeString() == $now->toTimeString()) {
                                    $this->sendMessage($entid, $item);
                                }
                            }
                            break;
                        case 1:
                            $diffNum = $now->diffInWeeks($timeNowPeriod);
                            // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                            if (($diffNum % $item['rate']) == 0) {
                                $dayTime   = $timeNowPeriod->addWeeks($diffNum);
                                $dayOfWeek = $dayTime->dayOfWeek;
                                if ($dayOfWeek == 0) {
                                    $dayOfWeek = 7;
                                }
                                // 在周几内提醒,并且本周当前的时间等于设置的时间
                                if (in_array($dayOfWeek, $item['days']) && $dayTime->toTimeString() == $now->toTimeString()) {
                                    $this->sendMessage($entid, $item);
                                }
                            }
                            break;
                        case 2:
                            $diffNum = $now->diffInMonths($timeNowPeriod);
                            // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                            if (($diffNum % $item['rate']) == 0) {
                                $dayTime = $timeNowPeriod->addMonths($diffNum);
                                $day     = $dayTime->day;
                                if ($day > 10) {
                                    $day = '0' . $day;
                                }
                                // 每月几号进行提醒
                                if (in_array($day, $item['days']) && $dayTime->toTimeString() == $now->toTimeString()) {
                                    $this->sendMessage($entid, $item);
                                }
                            }
                            break;
                        case 3:
                            $diffNum = $now->diffInYears($timeNowPeriod);
                            // 当前时间和提醒时间相差的天数取余频率等于0证明达到提醒
                            if (($diffNum % $item['rate']) == 0) {
                                $dayTime = $timeNowPeriod->addYears($item['rate']);
                                $day     = $dayTime->day;
                                if ($day > 10) {
                                    $day = '0' . $day;
                                }
                                // 每月几号进行提醒
                                if (in_array($day, $item['days']) && $dayTime->toTimeString() == $now->toTimeString()) {
                                    $this->sendMessage($entid, $item);
                                }
                            }
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * 发送消息.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendMessage(int $entId, array $info)
    {
        $this->dao->update($info['id'], ['is_remind' => 1, 'last_time' => date('Y-m-d H:i:s')]);
        $userInfo = app()->get(AdminService::class)->get(['uid' => $info['uid']], ['id', 'phone', 'name'])?->toArray();
        if (! $userInfo) {
            return;
        }

        if ($info['end_time'] != '0000-00-00 00:00:00') {
            Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
        }

        switch ($info['types']) {
            case 'personal':// 个人提醒
                $task = new MessageSendTask(
                    entid: $entId,
                    i: $entId,
                    type: MessageType::DEALT_PRESON_WORK_TYPE,
                    toUid: ['to_uid' => $userInfo['id'], 'phone' => $userInfo['phone'] ?? ''],
                    params: [
                        '待办内容' => $info['content'],
                        '备注'     => $info['mark'],
                        '创建人'   => $userInfo['name'] ?? '',
                        '创建时间' => $info['created_at'],
                    ],
                    linkId: $info['id'],
                    linkStatus: 0,
                );
                Task::deliver($task);
                break;
            case 'client_track':// 客户跟进
                $followInfo = app()->get(FollowUpService::class)->get($info['link_id'], ['id', 'eid'], ['client'])?->toArray();
                $task       = new MessageSendTask(
                    entid: $entId,
                    i: $entId,
                    type: MessageType::DEALT_CLIENT_WORK_TYPE,
                    toUid: ['to_uid' => $userInfo['id'], 'phone' => $userInfo['phone'] ?? ''],
                    params: [
                        '待办内容' => $info['content'],
                        '备注'     => $info['mark'],
                        '创建人'   => $userInfo['name'] ?? '',
                        '创建时间' => $info['created_at'],
                        '客户名称' => $followInfo['client']['name'] ?? '',
                    ],
                    other: ['id' => $info['link_id']],
                    linkId: $followInfo['eid'],
                    linkStatus: 0
                );
                Task::deliver($task);
                break;
            case 'client_renew':// 续费提醒
            case 'client_return':// 回款
                $clinetRemind = toArray(app()->get(RemindService::class)->get($info['link_id'], ['id', 'eid', 'cid'], ['client', 'treaty']));
                $task         = new MessageSendTask(
                    entid: $entId,
                    i: $entId,
                    type: MessageType::DEALT_MONEY_WORK_TYPE,
                    toUid: ['to_uid' => $userInfo['id'], 'phone' => $userInfo['phone'] ?? ''],
                    params: [
                        '待办内容'         => $info['content'],
                        '待办类型'         => $info['types'] == 'client_renew' ? '续费' : '回款',
                        '备注'             => $info['mark'],
                        '创建人'           => $userInfo['name'] ?? '',
                        '创建时间'         => $info['created_at'],
                        '客户名称'         => $clinetRemind['client']['name'] ?? '',
                        '关联订单名称' => $clinetRemind['treaty']['title'] ?? '',
                        '订单金额'     => $clinetRemind['treaty']['price'] ?? '',
                        '订单开始时间' => $clinetRemind['treaty']['start_date'] ?? '',
                        '订单结束时间' => $clinetRemind['treaty']['end_date'] ?? '',
                    ],
                    other: ['id' => $info['link_id']],
                    linkId: $clinetRemind['cid'],
                    linkStatus: 0
                );
                Task::deliver($task);
                break;
        }
    }

    /**
     * 获取当天已完成.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTodayCompleteRecord(string $uid, int $entId): array
    {
        $data = ['finish' => ''];
        if (Cache::tags([CacheEnum::TAG_OTHER])->has(md5('generate_today_report:' . $uid . $entId))) {
            return $data;
        }

        $list = $this->dao->selectModel(['entid' => 1, 'uid' => $uid, 'completed' => true], ['content'])->get()->toArray();
        if ($list) {
            $data['finish'] = implode("\n", array_column($list, 'content'));
        }
        return $data;
    }

    /**
     * 获取某个配置.
     *
     * @return mixed
     */
    protected function getKeyValue(string $key)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return null;
    }

    /**
     * 日重复提醒.
     *
     * @param string $end
     * @param mixed $list
     * @param mixed $types
     * @param mixed $start
     *
     * @return array
     * @throws BindingResolutionException
     */
    protected function getDaySchedule($list, $types, $start, $end = '')
    {
        $recordService = app()->get(UserScheduleRecordService::class);
        $dayTime       = Carbon::parse($start)->timezone(config('app.timezone'));
        $timeZone      = [$dayTime->toDateString()];
        $data          = [];
        foreach ($timeZone as $day) {
            $item = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    if ($this->haveSchedule($value, $day) && in_array($value['types'], $types)) {
                        $value['hour']   = Carbon::parse($day . ' ' . $value['remind_time'])->timezone(config('app.timezone'))->hour;
                        $value['finish'] = (bool) $recordService->value(['schedultid' => $value['id'], 'remind_day' => $day], 'status') ? 1 : 0;
                        $item[]          = $value;
                    }
                }
            }
            $data[] = [
                'date' => $day,
                'list' => $item,
            ];
        }

        return $data;
    }

    /**
     * 周重复提醒.
     *
     * @param string $end
     * @param mixed $list
     * @param mixed $types
     * @param mixed $start
     *
     * @return array
     * @throws BindingResolutionException
     */
    protected function getWeekSchedule($list, $types, $start, $end = '')
    {
        $recordService = app()->get(UserScheduleRecordService::class);
        $dayTime       = Carbon::parse($start)->timezone(config('app.timezone'));
        $timeZone      = CarbonPeriod::create($dayTime->startOfWeek()->toDateString(), $dayTime->endOfWeek()->toDateString())->toArray();
        $data          = [];
        foreach ($timeZone as $day) {
            $item = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    if ($this->haveSchedule($value, $day->toDateString()) && in_array($value['types'], $types)) {
                        $value['hour']   = Carbon::parse($day->toDateString() . ' ' . $value['remind_time'])->timezone(config('app.timezone'))->hour;
                        $value['finish'] = (bool) $recordService->value(['schedultid' => $value['id'], 'remind_day' => $day->toDateString()], 'status') ? 1 : 0;
                        $item[]          = $value;
                    }
                }
            }
            $data[] = [
                'date' => $day->toDateString(),
                'week' => $day->dayOfWeekIso,
                'list' => $item,
            ];
        }

        return $data;
    }

    /**
     * 月重复提醒.
     *
     * @param mixed $list
     * @param mixed $types
     * @param mixed $start
     * @param mixed $end
     * @return array
     * @throws BindingResolutionException
     */
    protected function getMonthSchedule($list, $types, $start, $end)
    {
        $recordService = app()->get(UserScheduleRecordService::class);
        $start         = Carbon::parse($start)->timezone(config('app.timezone'));
        $end           = Carbon::parse($end)->timezone(config('app.timezone'));
        $timeZone      = CarbonPeriod::create($start->toDateString(), $end->toDateString())->toArray();
        $data          = [];
        foreach ($timeZone as $day) {
            $day  = $day->toDateString();
            $item = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    if ($this->haveSchedule($value, $day) && in_array($value['types'], $types)) {
                        $value['finish'] = (bool) $recordService->value(['schedultid' => $value['id'], 'remind_day' => $day], 'status') ? 1 : 0;
                        $item[]          = $value;
                    }
                }
            }
            $data[$day] = $item;
        }

        return $data;
    }

    /**
     * 指定日期是否有日程提醒.
     *
     * @param mixed $schedule
     * @param mixed $day
     * @return bool
     */
    protected function haveSchedule($schedule, $day)
    {
        if ($schedule['remind_day'] == $day) {
            return true;
        }
        $isHava     = false;
        $endTime    = Carbon::parse($schedule['end_time'])->timezone(config('app.timezone'));
        $remindTime = Carbon::parse($schedule['remind_day'])->timezone(config('app.timezone'));
        $dayTime    = Carbon::parse($day)->timezone(config('app.timezone'));
        if ($remindTime->timestamp > $dayTime->timestamp) {
            return $isHava;
        }
        switch ($schedule['repeat']) {
            case 0:// 每天
                $isHava = true;
                break;
            case 1:// 每周
                if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                    if ($remindTime->dayOfWeek == $dayTime->dayOfWeek) {
                        $isHava = true;
                    }
                }
                break;
            case 2:// 每月
                if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                    if ($remindTime->day == $dayTime->day) {
                        $isHava = true;
                    }
                }
                break;
            case 3:// 每年
                if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                    if ($remindTime->day == $dayTime->day && $remindTime->month == $dayTime->month) {
                        $isHava = true;
                    }
                }
                break;
            case 4:// 自定义
                switch ($schedule['period']) {
                    case 0:// 天重复
                        if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                            if (bcmod(bcsub((string) $dayTime->timestamp, (string) $remindTime->timestamp), bcmul((string) $schedule['rate'], '86400')) == 0) {
                                $isHava = true;
                            }
                        }
                        break;
                    case 1:// 周重复
                        if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                            if (bcmod(bcsub($dayTime->format('w'), $remindTime->format('w')), (string) $schedule['rate']) == 0) {
                                foreach ($schedule['days'] as $v) {
                                    if ($dayTime->dayOfWeekIso == $v) {
                                        $isHava = true;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 2:// 月重复
                        if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                            if (bcmod(bcsub($dayTime->format('m'), $remindTime->format('m')), (string) $schedule['rate']) == 0) {
                                foreach ($schedule['days'] as $v) {
                                    if ($dayTime->format('d') == $v) {
                                        $isHava = true;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 3:// 年重复
                        if ($endTime->format('y') < 0 || $dayTime->timestamp < $endTime->timestamp) {
                            if (bcmod(bcsub($dayTime->format('y'), $remindTime->format('y')), (string) $schedule['rate']) == 0 && $remindTime->day == $dayTime->day && $remindTime->month == $dayTime->month) {
                                $isHava = true;
                            }
                        }
                        break;
                    default:
                        throw $this->exception('无效的重复周期');
                }
                break;
        }

        return $isHava;
    }

    /**
     * 处理列表数据.
     * @param mixed $list
     * @return array|mixed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function listHandler($list = [])
    {
        if (! $list) {
            return [];
        }
        $clientService   = app()->get(CustomerService::class);
        $contractService = app()->get(OrderService::class);
        $followService   = app()->get(FollowUpService::class);
        $remindService   = app()->get(RemindService::class);
        foreach ($list as &$val) {
            $val['color'] = $this->{strtoupper($val['types'])}['color'];
            $val['title'] = match ($val['types']) {
                'client_track' => $clientService->value($followService->value($val['link_id'], 'eid'), 'name'),
                'client_renew', 'client_return' => $contractService->value($remindService->value($val['link_id'], 'cid'), 'title'),
                default => '',
            };
        }
        return $list;
    }
}
