<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\ImportExport;

use App\Http\Model\System\Log;
use Tuoluojiang\Open\services\export\BaseExport;

class LogsExport extends BaseExport
{
    public function setHeadings(): array
    {
        return [
            'ID',
            '管理员姓名',
            '链接',
            '访问方式',
            '行为',
            '类型',
            '访问终端',
            '访问ip',
            '创建时间',
        ];
    }

    public function setDataCallback(): callable
    {
        return function () {
            $lazyCollection = Log::query()->select('id', 'uid', 'user_name', 'path', 'method', 'event_name', 'type', 'terminal', 'last_ip', 'created_at')
                ->whereDate('created_at', '>=', '2025-06-05 00:00:00')->cursor();
            foreach ($lazyCollection as $row) {
                // 生成器：逐行返回数据，无内存堆积
                yield [
                    $row['id'],
                    trim($row['user_name'] ?? ''),
                    trim($row['path'] ?? ''),
                    trim($row['method'] ?? ''),
                    trim($row['event_name'] ?? ''),
                    trim($row['type'] ?? ''),
                    trim($row['last_ip'] ?? ''),
                    $row['created_at']->toDateTimeString(),
                ];
            }
            unset($lazyCollection, $row);
        };
    }
}
