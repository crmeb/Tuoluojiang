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

namespace Tuoluojiang\Open\utils;

/**
 * 统计
 * Class Statistics.
 */
class Statistics
{
    /**
     * 百分比计算.
     * @param mixed $rateList
     * @param mixed $statisticKey
     * @param mixed $calcKey
     * @param mixed $ratioKey
     */
    public static function calcRatio($rateList, $statisticKey, $calcKey, $ratioKey, float $total = 0): array
    {
        if (count($rateList) === 0) {
            return [];
        }
        $sum        = $total ?: array_sum(array_column($rateList, $statisticKey));
        $ratioTotal = '0';
        $scores     = array_column($rateList, $statisticKey);
        array_multisort($scores, SORT_DESC, $rateList);
        foreach ($rateList as $key => &$item) {
            if (!$sum) {
                $item[$ratioKey] = 0;
                continue;
            }
            if ($key === count($rateList) - 1) {
                $item[$ratioKey] = bcsub('100', $ratioTotal, 2);
            } else {
                $ratio           = bcmul(bcdiv(strval($item[$statisticKey]), strval($sum), 4), '100', 2);
                $item[$ratioKey] = $ratio;
                $ratioTotal      = bcadd($ratioTotal, $ratio, 2);
            }
        }
        return $rateList;
    }

    /**
     * 环比计算.
     * @param mixed $first
     * @param mixed $second
     */
    public static function ringRatio($first, $second): int
    {
        $first  = (string)$first;
        $second = (string)$second;
        if ($first == $second) {
            return 0;
        }

        if ($second == 0) {
            $ratio = 100;
        } else {
            $ratio = (int)bcmul(bcdiv(bcsub($first, $second, 2), $second, 2), '100');
        }
        return $ratio;
    }
}
