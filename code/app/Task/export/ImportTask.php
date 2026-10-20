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
use Tuoluojiang\Open\services\export\BaseImport;
use Tuoluojiang\Open\services\export\SpoutHandler;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 数据导入任务
 */
class ImportTask extends Task
{
    private SpoutHandler $handler;

    public function __construct(protected BaseImport $importData, protected int $recordId = 0)
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

    public function handle()
    {
        try {
            $beforeInsertCallback = method_exists($this->importData, 'beforeInsertCallback') ? $this->importData->beforeInsertCallback() : null;
            $afterInsertCallback  = method_exists($this->importData, 'afterInsertCallback') ? $this->importData->afterInsertCallback() : null;
            $stats                = $this->handler->import(
                $this->importData->filePath,
                $this->importData->setTable(),
                $this->importData->processCallback(),
                beforeInsertCallback: $beforeInsertCallback,
                afterInsertCallback: $afterInsertCallback
            );
            app(RecordService::class)->update(['id' => $this->recordId], ['success_count' => $stats['success'], 'fail_count' => $stats['fail'], 'status' => 1]);
            logger()->info("数据导入完成，文件路径：{$this->importData->filePath}，内存峰值：" . round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');
            if (str_starts_with($this->importData->filePath, 'storage')) {
                unlink($this->importData->filePath);
            }
        } catch (\Throwable $e) {
            app(RecordService::class)->update(['id' => $this->recordId], ['fail_msg' => $e->getMessage(), 'status' => 2]);
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
            if (str_starts_with($this->importData->filePath, 'storage')) {
                unlink($this->importData->filePath);
            }
        }
    }
}
