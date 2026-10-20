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

namespace App\Task\export;

use App\Http\Service\ImportExport\RecordService;
use Tuoluojiang\Open\services\export\BaseExport;
use Tuoluojiang\Open\services\export\SpoutHandler;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 导出任务
 */
class ExportTask extends Task
{
    private SpoutHandler $handler;

    /**
     * ExportTask constructor.
     */
    public function __construct(protected BaseExport $exportData, protected int $recordId)
    {
        $this->handler = app(SpoutHandler::class);
        $fileDir       = public_path('exports');
        if (! is_dir($fileDir)) {
            mkdir($fileDir, 0755, true);
        }
        $storageDir = storage_path('exports');
        if (! is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $filePath = $this->handler->export($this->exportData->format, $this->exportData->fileName, $this->exportData->headings, $this->exportData->setDataCallback());
            app(RecordService::class)->update(['id' => $this->recordId], ['file_path' => $filePath, 'name' => $this->exportData->fileName . '.' . $this->exportData->format, 'status' => 1]);
            logger()->info("数据导出完成，文件路径：{$filePath}，内存峰值：" . round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');
        } catch (\Throwable $e) {
            app(RecordService::class)->update(['id' => $this->recordId], ['fail_msg' => $e->getMessage(), 'status' => 2]);
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
