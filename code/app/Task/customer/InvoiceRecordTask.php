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

namespace App\Task\customer;

use App\Http\Service\Customer\InvoiceLogService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 客户发票操作记录队列
 * Class InvoiceRecordTask.
 */
class InvoiceRecordTask extends Task
{
    public function __construct(protected int $entId, protected int $id, protected int $uid, protected int $type = 0, protected array $param = []) {}

    /**
     * 执行队列.
     */
    public function handle()
    {
        try {
            app()->get(InvoiceLogService::class)->saveRecord($this->entId, $this->id, $this->uid, $this->type, $this->param);
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
