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

namespace App\Http\Service\Attendance;

use App\Constants\AttendanceClockEnum;
use App\Http\Contract\Attendance\AttendanceShiftInterface;
use App\Http\Dao\Attendance\AttendanceShiftDao;
use App\Http\Dao\Attendance\AttendanceShiftRuleDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤班次记录
 * Class AttendanceShiftService.
 */
class AttendanceShiftService extends BaseService implements AttendanceShiftInterface
{
    use ResourceServiceTrait;

    protected AttendanceShiftRuleDao $ruleDao;

    public function __construct(AttendanceShiftDao $dao, AttendanceShiftRuleDao $ruleDao)
    {
        $this->dao     = $dao;
        $this->ruleDao = $ruleDao;
    }

    /**
     * 列表.
     * @param string[] $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'name', 'uid', 'color', 'created_at', 'updated_at'], $sort = ['sort', 'created_at'], array $with = ['card', 'times']): array
    {
        $where['id_gt'] = 1;
        if ($where['group_id'] > 0) {
            unset($where['id_gt']);
        }
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 班次下拉.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelectList(array $where = [], array $field = ['id', 'name', 'color'], array $sort = ['sort', 'created_at']): array
    {
        return $this->dao->getList($where, $field, 0, 0, $sort);
    }

    /**
     * 保存班次
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveShift(array $data, int $uid): mixed
    {
        $this->checkShift(name: $data['name']);
        $rules = $this->getRules($data);
        return $this->transaction(function () use ($data, $rules, $uid) {
            $res = $this->dao->create([
                'uid'              => $uid,
                'name'             => $data['name'],
                'number'           => $data['number'],
                'rest_time'        => $data['rest_time'],
                'rest_start'       => $data['rest_start'],
                'rest_end'         => $data['rest_end'],
                'overtime'         => $data['overtime'],
                'work_time'        => $data['work_time'],
                'color'            => $data['color'],
                'rest_start_after' => $data['rest_start_after'],
                'rest_end_after'   => $data['rest_end_after'],
            ]);
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }
            foreach ($rules as $rule) {
                $rule['shift_id'] = $res->id;
                $this->ruleDao->create($rule);
            }

            return $res;
        });
    }

    /**
     * 更新班次
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateShift(int $id, array $data): mixed
    {
        $this->checkShift($id, name: $data['name']);
        $rules = $this->getRules($data);
        return $this->transaction(function () use ($id, $data, $rules) {
            $this->dao->update(
                $id,
                [
                    'name'             => $data['name'],
                    'number'           => $data['number'],
                    'rest_time'        => $data['rest_time'],
                    'rest_start'       => $data['rest_start'],
                    'rest_end'         => $data['rest_end'],
                    'overtime'         => $data['overtime'],
                    'work_time'        => $data['work_time'],
                    'color'            => $data['color'],
                    'rest_start_after' => $data['rest_start_after'],
                    'rest_end_after'   => $data['rest_end_after'],
                ]
            );

            $this->ruleDao->delete(['shift_id' => $id]);
            foreach ($rules as $rule) {
                $rule['shift_id'] = $id;
                $this->ruleDao->create($rule);
            }
        });
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteShift(int $id): bool
    {
        $this->checkShift($id, 'delete');
        if (app()->get(AttendanceGroupService::class)->checkShiftExist($id)
            || app()->get(RosterCycleService::class)->checkShiftExist($id)) {
            throw $this->exception('当前班次已被使用, 不能删除');
        }

        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id) && $this->ruleDao->delete(['shift_id' => $id]);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }
            app()->get(AttendanceArrangeService::class)->clearFutureArrangeByShiftId($id);
            return true;
        });
    }

    /**
     * 获取详情.
     */
    public function getInfo(int $id): array
    {
        $info            = $this->dao->get($id, ['*'], ['rules'])?->toArray();
        $info['number1'] = $info['rules'][0] ?? [];
        $info['number2'] = $info['rules'][1] ?? [];
        unset($info['rules']);
        return $info;
    }

    /**
     * 获取规则.
     */
    public function getRules(array $shiftData): array
    {
        $rules   = [];
        $tz      = config('app.timezone');
        $rules[] = $this->getRule($shiftData['number1'] ?? [], $tz);
        if ($shiftData['number'] == 2) {
            $rules[] = $this->getRule($shiftData['number2'] ?? [], $tz, 2);
            // 判断班次时间重叠
            if (Carbon::parse(date("Y-m-d {$rules[1]['work_hours']}:00"), $tz)->subSeconds($rules[1]['early_card'])
                ->lte(Carbon::parse(date("Y-m-d {$rules[0]['off_hours']}:00"), $tz)->addSeconds($rules[0]['delay_card']))) {
                throw $this->exception('连续班次最早上班卡与最晚下班卡时间不能重叠');
            }
        }
        $rules[0]['first_day_after'] = 0;

        if (! count($rules)) {
            throw $this->exception('请设置班次规则');
        }
        return $rules;
    }

    /**
     * 单个班次规则.
     * @param mixed $tz
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRule(array $data, $tz, int $number = 1): array
    {
        $rule['number']          = $number;
        $rule['late']            = $data['late'] ?? 0;
        $rule['first_day_after'] = $data['first_day_after'] ?? 0;
        $rule['work_hours']      = $data['work_hours'] ?? '';
        $rule['extreme_late']    = $data['extreme_late'] ?? 0;

        if ($rule['extreme_late'] <= $rule['late']) {
            throw $this->exception('严重迟到值要大于迟到');
        }
        $rule['late_lack_card'] = $data['late_lack_card'] ?? 0;
        if ($rule['late_lack_card'] <= $rule['extreme_late']) {
            throw $this->exception('晚到缺卡值要大于严重迟到');
        }
        $rule['early_card']      = $data['early_card'] ?? 0;
        $rule['off_hours']       = $data['off_hours'] ?? '';
        $rule['early_leave']     = $data['early_leave'] ?? 0;
        $rule['early_lack_card'] = $data['early_lack_card'] ?? 0;
        if ($rule['early_lack_card'] <= $rule['early_leave']) {
            throw $this->exception('半天缺卡的值要大于早退');
        }
        $rule['second_day_after'] = $data['second_day_after'] ?? 0;

        $date = Carbon::now($tz)->toDateString();

        $statisticsService = app()->get(AttendanceStatisticsService::class);
        $workHours         = $statisticsService->getWorkObj($rule, $date, $tz);
        $offWorkHours      = $statisticsService->getOffWorkObj($rule, $date, $tz);
        if ($workHours->gte($offWorkHours)) {
            throw $this->exception('下班时间要晚于上班时间');
        }

        $rule['delay_card'] = $data['delay_card'] ?? 0;
        if ($workHours->subSeconds($data['early_card'])->gte($offWorkHours->addSeconds($data['delay_card']))) {
            throw $this->exception('提前打卡时间要早于延后打卡时间');
        }
        $rule['free_clock'] = $data['free_clock'] ?? 0;
        if (isset($data['id']) && $data['id']) {
            $rule['id'] = $data['id'];
        }
        return $rule;
    }

    /**
     * 打卡班次
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getArrangeShiftById(int $id, string $date = ''): array
    {
        if ($date) {
            $shift = app()->get(AttendanceStatisticsService::class)->value(['shift_id' => $id, 'date' => $date], 'shift_data');
            if ($shift) {
                return $shift;
            }
        }
        $field     = ['id', 'name', 'number', 'rest_time', 'rest_start', 'rest_start_after', 'rest_end', 'rest_end_after', 'overtime', 'work_time', 'color'];
        $ruleField = ['id', 'shift_id', 'number', 'first_day_after', 'second_day_after', 'work_hours', 'late', 'extreme_late',
            'late_lack_card', 'early_card', 'off_hours', 'early_leave', 'early_lack_card', 'delay_card', 'free_clock'];
        $info          = $this->dao->get($id, $field, ['rules' => fn ($q) => $q->select($ruleField)])?->toArray();
        $info['rules'] = collect($info['rules'] ?? [])->map(function ($item) use ($date) {
            $item['work_date'] = $item['first_day_after'] ? Carbon::make($date)->addDay()->toDateString() : Carbon::make($date)->toDateString();
            $item['off_date']  = $item['second_day_after'] ? Carbon::make($date)->addDay()->toDateString() : Carbon::make($date)->toDateString();
            return $item;
        })->all();
        if (! $info) {
            throw $this->exception('操作失败，记录不存在');
        }
        return $info;
    }

    /**
     * 打卡时间班次检测.
     */
    public function afterCheck(Carbon $dateObj, array $rule, int $num): Carbon
    {
        if ($rule[['first', 'second'][$num] . '_day_after']) {
            $dateObj->addDay();
        }
        return $dateObj;
    }

    /**
     * 获取应出勤工时.
     */
    public function getRequiredAttendanceHours(array $shift, string $date): float
    {
        $seconds = 0;

        if (empty($shift) || $shift['id'] < 2) {
            return $seconds;
        }

        $tz = config('app.timezone');
        for ($i = 0; $i < $shift['number']; ++$i) {
            $rule = $shift['rules'][$i];
            $seconds += $this->afterCheck(Carbon::parse($date . ' ' . $rule['work_hours'] . ':00', $tz), $rule, 0)
                ->diffInSeconds($this->afterCheck(Carbon::parse($date . ' ' . $rule['off_hours'] . ':00', $tz), $rule, 1), false);
        }

        if ($shift['number'] == 1) {
            $seconds -= $this->getRestTimeSeconds($shift, $date, $tz);
        }
        return floor(bcdiv((string) max($seconds, 0), '3600', 4) * pow(10, 1)) / pow(10, 1);
    }

    /**
     * 获取出勤工时.
     * @param mixed $statistics
     */
    public function getActualWorkHours($statistics, string $date, string $tz = ''): float
    {
        $seconds = 0;

        $tz = $tz ?: config('app.timezone');

        // 无需打卡工时
        if ($statistics->shift_id < 2) {
            if (! empty($statistics->one_shift_time) && ! empty($statistics->two_shift_time)) {
                $seconds += Carbon::parse(substr($statistics->one_shift_time, 0, -3), $tz)
                    ->diffInSeconds(Carbon::parse(substr($statistics->two_shift_time, 0, -3), $tz), false);
            }
        } else {
            $shift  = $statistics->shift_data;
            $shifts = AttendanceClockEnum::SHIFT_CLASS;
            for ($i = 0; $i < $shift['number']; ++$i) {
                $number = $i * 2;
                if ($statistics->{$shifts[$number] . '_shift_location_status'} == AttendanceClockEnum::OFFICE_ABNORMAL
                    || $statistics->{$shifts[$number + 1] . '_shift_location_status'} == AttendanceClockEnum::OFFICE_ABNORMAL
                    || $statistics->{$shifts[$number] . '_shift_status'} == AttendanceClockEnum::LACK_CARD
                    || $statistics->{$shifts[$number + 1] . '_shift_status'} == AttendanceClockEnum::LACK_CARD
                    || empty($statistics->{$shifts[$number] . '_shift_time'}) || empty($statistics->{$shifts[$number + 1] . '_shift_time'})
                ) {
                    continue;
                }

                $seconds += Carbon::parse($statistics->{$shifts[$number] . '_shift_time'}, $tz)
                    ->diffInSeconds(Carbon::parse($statistics->{$shifts[$number + 1] . '_shift_time'}, $tz), false);
            }

            $shift['number'] == 1 && $seconds > 0 && $seconds -= $this->getRestTimeSeconds($shift, $date, $tz);
        }

        return floor(bcdiv((string) max($seconds, 0), '3600', 4) * pow(10, 1)) / pow(10, 1);
    }

    /**
     * 获取异常时间.
     */
    public function getNormalMinutes(string $day, array $shift, int $status, int $number, string $shiftTime, string $tz): int
    {
        if (empty($shiftTime)) {
            return 0;
        }

        $isWork  = in_array($number, [0, 2]);
        $date    = Carbon::parse($day, $tz)->toDateString();
        $rule    = $shift['rules'][$number > 1 ? 1 : 0];
        $dateObj = Carbon::parse($date . ' ' . $rule[$isWork ? 'work_hours' : 'off_hours'] . ':00', $tz);
        if ($rule[['first', 'second'][$isWork ? 0 : 1] . '_day_after']) {
            $dateObj->addDay();
        }

        $seconds = $dateObj->diffInSeconds(Carbon::parse(substr($shiftTime, 0, -3), $tz));
        return $seconds > match ($status) {
            AttendanceClockEnum::LATE         => $rule['late'],
            AttendanceClockEnum::EXTREME_LATE => $rule['extreme_late'],
            AttendanceClockEnum::LEAVE_EARLY  => $rule['early_leave'],
        } ? intval($seconds / 60) : 0;
    }

    /**
     * 核对班次
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function checkShift(int $id = 0, string $act = '', string $name = ''): void
    {
        if ($act == 'delete') {
            if (in_array($id, [1, 2])) {
                throw $this->exception('默认班次不能删除');
            }
        }

        if ($id && ! $this->dao->exists(['id' => $id])) {
            throw $this->exception('操作失败，记录不存在');
        }

        if ($name) {
            $where = ['name_like' => $name];
            if ($id) {
                $where['not_id'] = $id;
            }

            $this->dao->exists($where) && throw $this->exception('班次名称重复');
        }
    }

    /**
     * 获取休息时间.
     */
    private function getRestTimeSeconds(array $shiftData, string $dateString, string $tz): int
    {
        $seconds = 0;
        if ($shiftData['number'] > 1 || ! $shiftData['rest_time']) {
            return $seconds;
        }

        $startObj = Carbon::parse($dateString . ' ' . $shiftData['rest_start'] . ':00', $tz);
        $endObj   = Carbon::parse($dateString . ' ' . $shiftData['rest_end'] . ':00', $tz);
        return ($shiftData['rest_start_after'] ? $startObj->addDay() : $startObj)
            ->diffInSeconds($shiftData['rest_end_after'] ? $endObj->addDay() : $endObj, false);
    }
}
