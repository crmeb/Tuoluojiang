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

namespace App\Http\Dao\WorkExternalContact;

use App\Http\Model\WorkExternalContact\WorkChannelCodeLog;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class WorkChannelCodeLogDao extends BaseDao
{
    use ListSearchTrait;

    protected function setModel()
    {
        return WorkChannelCodeLog::class;
    }

    public function countDistinctCustomersByTime(int $channelId, string $timeField, Carbon $startAt, Carbon $endAt, array $where = []): int
    {
        return (int) $this->statisticsQuery($channelId, $timeField, $startAt, $endAt, $where)
            ->distinct('external_userid')
            ->count('external_userid');
    }

    public function dailyDistinctCustomersByTime(int $channelId, string $timeField, Carbon $startAt, Carbon $endAt, array $where = []): array
    {
        return $this->statisticsQuery($channelId, $timeField, $startAt, $endAt, $where)
            ->selectRaw("DATE_FORMAT(`{$timeField}`, '%Y-%m-%d') as days, COUNT(DISTINCT external_userid) as count")
            ->groupBy('days')
            ->orderBy('days')
            ->get()
            ->toArray();
    }

    public function markCustomerLost(string $corpId, string $userid, string $externalUserid): bool
    {
        if (! $corpId || ! $userid || ! $externalUserid) {
            return true;
        }

        $this->getModel()->where([
            'corp_id'          => $corpId,
            'userid'           => $userid,
            'external_userid'  => $externalUserid,
        ])->update([
            'customer_status' => 2,
            'lost_at'         => now()->toDateTimeString(),
            'updated_at'      => now()->toDateTimeString(),
        ]);

        return true;
    }

    private function statisticsQuery(int $channelId, string $timeField, Carbon $startAt, Carbon $endAt, array $where = []): Builder
    {
        return $this->applyExtraWhere(
            $this->getModel()
                ->where('channel_id', $channelId)
                ->where('external_userid', '<>', '')
                ->whereNotNull($timeField)
                ->whereBetween($timeField, [$startAt->toDateTimeString(), $endAt->toDateTimeString()]),
            $where
        );
    }

    private function applyExtraWhere(Builder $query, array $where): Builder
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
                continue;
            }
            $query->where($field, $value);
        }

        return $query;
    }
}
