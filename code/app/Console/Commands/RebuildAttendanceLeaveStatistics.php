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

namespace App\Console\Commands;

use App\Constants\ApproveEnum;
use App\Http\Service\Attendance\AttendanceStatisticsService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 重建考勤请假统计明细.
 */
class RebuildAttendanceLeaveStatistics extends Command
{
    /**
     * 命令签名.
     *
     * @var string
     */
    protected $signature = 'attendance:rebuild-leave-statistics
                            {--month= : 重建月份，格式 YYYY-MM}
                            {--start= : 开始日期，格式 YYYY-MM-DD}
                            {--end= : 结束日期，格式 YYYY-MM-DD}
                            {--uid=* : 指定用户ID，可重复传入}
                            {--chunk=200 : 每批处理数量}
                            {--dry-run : 仅统计待处理申请，不实际重建}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '按月份或日期范围重建 attendance_statistics_leave 请假统计明细';

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        try {
            [$startObj, $endObj] = $this->resolveDateRange();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $chunk              = max(1, (int) $this->option('chunk'));
        $uids               = array_filter(array_map('intval', (array) $this->option('uid')));
        $isDryRun           = (bool) $this->option('dry-run');
        $query              = $this->buildQuery($startObj, $endObj, $uids);
        $total              = (clone $query)->count();

        $this->info('重建范围：' . $startObj->toDateString() . ' 至 ' . $endObj->toDateString());
        if ($uids) {
            $this->info('指定用户：' . implode(',', $uids));
        }

        if ($isDryRun) {
            $this->warn('【Dry Run 模式】不会实际重建请假统计明细');
            $this->info("待处理请假申请记录：{$total} 条");
            return self::SUCCESS;
        }

        if ($total === 0) {
            $this->info('没有需要重建的请假申请记录');
            return self::SUCCESS;
        }

        $processed = 0;
        $skipped   = 0;
        $service   = app()->get(AttendanceStatisticsService::class);

        $this->buildQuery($startObj, $endObj, $uids)
            ->select([
                'attendance_apply_record.id',
                'attendance_apply_record.uid',
                'attendance_apply_record.start_time',
                'attendance_apply_record.end_time',
                'attendance_apply_record.others',
            ])
            ->orderBy('attendance_apply_record.id')
            ->chunkById($chunk, function ($records) use ($service, &$processed, &$skipped) {
                foreach ($records as $record) {
                    $others        = json_decode((string) $record->others, true) ?: [];
                    $holidayTypeId = (int) ($others['holiday_type_id'] ?? 0);
                    if (! $holidayTypeId || ! $record->start_time || ! $record->end_time) {
                        ++$skipped;
                        continue;
                    }

                    $service->calcLeaveDurationByTime(
                        (int) $record->uid,
                        (int) $record->id,
                        $holidayTypeId,
                        (string) $record->start_time,
                        (string) $record->end_time
                    );
                    ++$processed;
                }
            }, 'attendance_apply_record.id', 'id');

        $this->info("已重建请假申请记录：{$processed} 条");
        if ($skipped) {
            $this->warn("跳过无假期类型或无时间的记录：{$skipped} 条");
        }

        return self::SUCCESS;
    }

    /**
     * 解析重建日期范围.
     */
    private function resolveDateRange(): array
    {
        $tz    = config('app.timezone');
        $month = (string) $this->option('month');
        $start = (string) $this->option('start');
        $end   = (string) $this->option('end');

        if ($month !== '') {
            if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new \InvalidArgumentException('month 格式必须是 YYYY-MM');
            }
            $monthObj = Carbon::parse($month . '-01', $tz);
            return [$monthObj->copy()->startOfMonth(), $monthObj->copy()->endOfMonth()];
        }

        if ($start === '' && $end === '') {
            $now = Carbon::now($tz);
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }

        if ($start === '' || $end === '') {
            throw new \InvalidArgumentException('start 和 end 必须同时传入');
        }

        $startObj = Carbon::parse($start, $tz)->startOfDay();
        $endObj   = Carbon::parse($end, $tz)->endOfDay();
        if ($startObj->gt($endObj)) {
            throw new \InvalidArgumentException('start 不能晚于 end');
        }

        return [$startObj, $endObj];
    }

    /**
     * 构建请假申请查询.
     */
    private function buildQuery(Carbon $startObj, Carbon $endObj, array $uids): Builder
    {
        return DB::table('attendance_apply_record')
            ->join('approve_apply', 'approve_apply.id', '=', 'attendance_apply_record.apply_id')
            ->where('attendance_apply_record.apply_type', ApproveEnum::PERSONNEL_HOLIDAY)
            ->where('approve_apply.status', 1)
            ->where('attendance_apply_record.start_time', '<=', $endObj->toDateTimeString())
            ->where('attendance_apply_record.end_time', '>=', $startObj->toDateTimeString())
            ->when($uids, fn (Builder $query) => $query->whereIn('attendance_apply_record.uid', $uids));
    }
}
