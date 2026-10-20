<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Attendance;

use App\Http\Contract\Attendance\CalendarConfigInterface;
use App\Http\Dao\Attendance\CalendarConfigDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 日历配置
 * Class CalendarConfigService.
 */
class CalendarConfigService extends BaseService implements CalendarConfigInterface
{
    public const CACHE_KEY = 'rest_day';

    public function __construct(CalendarConfigDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 时间处理.
     * @return string[]
     */
    public function parseTime(string $time): array
    {
        $year      = $time;
        $month     = '01';
        $lastMonth = '12';
        if (str_contains($time, '-')) {
            [$year, $month] = explode('-', $time);
            $lastMonth      = $month;
        }

        return [$year, $month, $lastMonth];
    }

    /**
     * 获取休息时间数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRestList(string $time): array
    {
        return Cache::tags([self::CACHE_KEY])->remember(md5(self::CACHE_KEY . $time), (int) sys_config('system_cache_ttl', 3600), function () use ($time) {
            $tz                         = config('app.timezone');
            [$year, $month, $lastMonth] = $this->parseTime($time);
            return $this->getRestDay(
                Carbon::createFromDate($year, $month, '01', $tz)->startOfDay(),
                Carbon::createFromDate($year, $lastMonth, '01', $tz)->lastOfMonth(),
                $this->getCalendar(['year' => $year])
            );
        });
    }

    /**
     * 更新休息日.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateCalendar(string $date, array $data, string $tz): mixed
    {
        $dateObj = Carbon::parse($date, $tz);
        if ($dateObj->format('Ym') < now($tz)->format('Ym')) {
            throw $this->exception('今日及以前的日期禁止调整');
        }

        $format = 'Y-m-d';
        // 检查日期格式
        foreach ($data as $item) {
            $dayObj = Carbon::parse($item['day'], $tz);
            if (! ($dayObj && $dayObj->format($format) === $item['day'])) {
                throw $this->exception('时间格式错误');
            }
        }

        return $this->transaction(function () use ($tz, $dateObj, $data) {
            $calendar = $this->getCalendar(['year' => $dateObj->format('Y'), 'month' => $dateObj->format('m')]);
            foreach ($data as $item) {
                if (isset($calendar[$item['day']]) && $calendar[$item['day']]['is_rest'] == $item['is_rest']) {
                    unset($calendar[$item['day']]);
                    continue;
                }

                // 处理无效数据
                $isRest = in_array(Carbon::parse($item['day'], $tz)->weekday(), [0, 6]);
                if (($item['is_rest'] && $isRest) || (! $item['is_rest'] && ! $isRest)) {
                    $this->dao->delete(['day' => $item['day']]);
                    continue;
                }

                $this->dao->create(['day' => $item['day'], 'is_rest' => $item['is_rest']]);
            }

            $delIds = array_column($calendar, 'id');
            $delIds && $this->dao->delete(['id' => $delIds]);
            Cache::tags([self::CACHE_KEY])->flush();
            return true;
        });
    }

    /**
     * 获取日历配置.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCalendar(array $where, bool $isPeriod = false): array
    {
        $calendar = [];
        if ($isPeriod) {
            $list = $this->dao->setTimeField('day')->column($where, ['id', 'day', 'is_rest']);
        } else {
            $list = $this->dao->column($where, ['id', 'day', 'is_rest']);
        }
        foreach ($list as $item) {
            $calendar[$item['day']] = $item;
        }

        return $calendar;
    }

    /**
     * 当日是否休息.
     */
    public function dayIsRest(string $date = ''): bool
    {
        $tz   = config('app.timezone');
        $date = $date ?: now($tz)->toDateString();
        $info = $this->get(['day' => $date], ['id', 'day', 'is_rest']);
        if ($info) {
            return (bool) $info->is_rest;
        }

        if (in_array(Carbon::parse($date, $tz)->dayOfWeek, [0, 6])) {
            return true;
        }
        return false;
    }

    /**
     * 获取休息时间数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRestListByPeriod(Carbon $startObj, Carbon $endObj, bool $isMap = true): array
    {
        $key = md5(self::CACHE_KEY . $startObj->toDateTimeString() . ' _' . $endObj->toDateTimeString() . '_' . $isMap);
        return Cache::tags([self::CACHE_KEY])->remember($key, (int) sys_config('system_cache_ttl', 3600), function () use ($startObj, $endObj, $isMap) {
            $list = $this->getRestDay(
                $startObj,
                $endObj,
                $this->getCalendar(['time' => $startObj->format('Y/m/d') . ' 00:00:00-' . $endObj->format('Y/m/d') . ' 23:59:59'], true)
            );
            if (! $isMap) {
                return $list;
            }

            $restData = [];
            foreach ($list as $rest) {
                $restData[$rest] = 1;
            }

            return $restData;
        });
    }

    /**
     * 获取休息时间数据.
     */
    private function getRestDay(Carbon $startDateObj, Carbon $endDateObj, array $calendar): array
    {
        $restDay = [];
        while (true) {
            $merge = true;
            if ($startDateObj->gt($endDateObj)) {
                break;
            }

            $day = $startDateObj->toDateString();
            if (in_array($startDateObj->weekday(), [0, 6])) {
                if (isset($calendar[$day]) && ! $calendar[$day]['is_rest']) {
                    $merge = false;
                }
            } else {
                if (! isset($calendar[$day])) {
                    $merge = false;
                }
            }
            $merge && $restDay[] = $day;
            $startDateObj->addDay();
        }
        return $restDay;
    }
}
