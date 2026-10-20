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

namespace App\Console\Commands;

use App\Http\Service\Knowledge\KnowledgeEvalService;
use Illuminate\Console\Command;

/**
 * 从 JSON 或问答日志导入评测用例草稿.
 *
 * 用法：php artisan knowledge:eval-import {file?} {--from-log} {--limit=}
 */
class KnowledgeEvalImportCommand extends Command
{
    protected $signature = 'knowledge:eval-import
                            {file? : JSON 文件路径}
                            {--from-log : 从 knowledge_question_log 生成草稿用例}
                            {--limit=100 : 导入条数上限}';

    protected $description = '从 JSON 或问答日志导入知识库评测用例（默认草稿）';

    /**
     * 执行导入.
     */
    public function handle(KnowledgeEvalService $evalService): int
    {
        $fromLog = (bool) $this->option('from-log');
        $limit   = max(1, (int) $this->option('limit'));
        $file    = (string) $this->argument('file');

        if (! $fromLog && $file === '') {
            $this->error('请提供 JSON 文件路径，或使用 --from-log 从问答日志导入');

            return self::FAILURE;
        }

        try {
            $count = $fromLog
                ? $evalService->importFromLog($limit)
                : $evalService->importFromJson($file, $limit);
        } catch (\Throwable $e) {
            $this->error('导入失败：' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info($fromLog ? "已从问答日志导入 {$count} 条草稿用例" : "已从 JSON 导入 {$count} 条用例");

        return self::SUCCESS;
    }
}
