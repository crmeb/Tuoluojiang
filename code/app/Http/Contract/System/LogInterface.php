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

namespace App\Http\Contract\System;

/**
 * 日志.
 */
interface LogInterface
{
    /**
     * 获取列表(分页).
     */
    public function getLogPageList(array $where, int $page, int $limit, array $field, array|string $sort, array $with): array;

    /**
     * 新增日志记录.
     */
    public function createLog(string $userId, int $entId, string $userName, string $type): bool;

    /**
     * 新增已整理的日志记录.
     */
    public function createLogFromData(array $data): bool;

    /**
     * 清理过期日志.
     */
    public function deleteExpiredLogs(int $months = 12): int;
}
