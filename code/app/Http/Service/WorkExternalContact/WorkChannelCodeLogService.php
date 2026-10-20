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

namespace App\Http\Service\WorkExternalContact;

use App\Http\Dao\WorkExternalContact\WorkChannelCodeLogDao;
use App\Http\Service\BaseService;
use Illuminate\Support\Carbon;

class WorkChannelCodeLogService extends BaseService
{
    public function __construct(WorkChannelCodeLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getLogList(array $where, int $page = 0, int $limit = 0): array
    {
        return $this->dao->getList($where, ['*'], $page, $limit, ['id' => 'desc'], ['group']);
    }

    public function getStatistics(int $channelId, string $time = ''): array
    {
        [$startAt, $endAt] = $this->parseDateRange($time);

        $addCustomerCount = $this->dao->countDistinctCustomersByTime($channelId, 'created_at', $startAt, $endAt, []);
        $lossCustomerCount = $this->dao->countDistinctCustomersByTime($channelId, 'lost_at', $startAt, $endAt, ['customer_status' => 2]);
        $joinCustomerCount = $this->dao->countDistinctCustomersByTime($channelId, 'joined_at', $startAt, $endAt, ['group_status' => [2, 3]]);
        $quitGroupCustomerCount = $this->dao->countDistinctCustomersByTime($channelId, 'left_at', $startAt, $endAt, ['group_status' => 3]);

        $xAxis = $this->dateAxis($startAt, $endAt);

        return [
            'add_customer_count'        => $addCustomerCount,
            'loss_customer_count'       => $lossCustomerCount,
            'join_customer_count'       => $joinCustomerCount,
            'quit_group_customer_count' => $quitGroupCustomerCount,
            'customer_service'          => [
                'xAxis'  => array_values($xAxis),
                'series' => [
                    [
                        'name' => '新增客户数',
                        'data' => $this->seriesData($xAxis, $this->dao->dailyDistinctCustomersByTime($channelId, 'created_at', $startAt, $endAt, [])),
                    ],
                    [
                        'name' => '流失客户数',
                        'data' => $this->seriesData($xAxis, $this->dao->dailyDistinctCustomersByTime($channelId, 'lost_at', $startAt, $endAt, ['customer_status' => 2])),
                    ],
                ],
            ],
            'group_chat'                => [
                'xAxis'  => array_values($xAxis),
                'series' => [
                    [
                        'name' => '加群客户数',
                        'data' => $this->seriesData($xAxis, $this->dao->dailyDistinctCustomersByTime($channelId, 'joined_at', $startAt, $endAt, ['group_status' => [2, 3]])),
                    ],
                    [
                        'name' => '退群客户数',
                        'data' => $this->seriesData($xAxis, $this->dao->dailyDistinctCustomersByTime($channelId, 'left_at', $startAt, $endAt, ['group_status' => 3])),
                    ],
                ],
            ],
        ];
    }

    public function markCustomerLost(string $corpId, string $userid, string $externalUserid): bool
    {
        return $this->dao->markCustomerLost($corpId, $userid, $externalUserid);
    }

    private function parseDateRange(string $time): array
    {
        if ($time === '') {
            $endAt = Carbon::today()->endOfDay();
            return [$endAt->copy()->subDays(29)->startOfDay(), $endAt];
        }

        $parts = preg_split('/\s+-\s+/', str_replace('/', '-', trim($time)));
        if (! is_array($parts) || count($parts) !== 2) {
            throw $this->exception('时间范围格式错误');
        }

        $startAt = Carbon::parse($parts[0])->startOfDay();
        $endAt = Carbon::parse($parts[1])->endOfDay();
        if ($startAt->gt($endAt)) {
            [$startAt, $endAt] = [$endAt->startOfDay(), $startAt->endOfDay()];
        }

        return [$startAt, $endAt];
    }

    private function dateAxis(Carbon $startAt, Carbon $endAt): array
    {
        $axis = [];
        $date = $startAt->copy()->startOfDay();
        $end = $endAt->copy()->startOfDay();
        while ($date->lte($end)) {
            $axis[$date->toDateString()] = $date->format('m-d');
            $date->addDay();
        }

        return $axis;
    }

    private function seriesData(array $xAxis, array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(string) ($row['days'] ?? '')] = (int) ($row['count'] ?? 0);
        }

        return array_map(fn (string $date) => $map[$date] ?? 0, array_keys($xAxis));
    }
}
