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

namespace App\Task\todo;

use App\Constants\TodoEnum;
use App\Http\Service\Todo\TodoItemService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

class TodoItemSyncTask extends Task
{
    /**
     * @param array<int, string> $types
     */
    public function __construct(protected array $types = []) {}

    public function handle(): void
    {
        $types = $this->types ?: TodoEnum::ALL_TYPES;
        $types = array_values(array_intersect(TodoEnum::ALL_TYPES, array_unique($types)));
        if (! $types) {
            return;
        }

        try {
            app()->get(TodoItemService::class)->syncByTypesForAllUsers($types);
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), [
                'types' => $types,
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTrace(),
            ]);
        }
    }
}
