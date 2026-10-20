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

class Disk
{
    /**
     * 容量转换.
     * @return string
     */
    public function getSizeName($size)
    {
        if ($size >= 1073741824) {
            // 转成GB
            $size = round($size / 1073741824 * 100) / 100 . 'G';
        } elseif ($size >= 1048576) {
            // 转成MB
            $size = round($size / 1048576 * 100) / 100 . 'M';
        } elseif ($size >= 1024) {
            // 转成KB
            $size = round($size / 1024 * 100) / 100 . 'K';
        } else {
            // 不转换直接输出
            $size = $size . 'B';
        }

        return $size;
    }
}
