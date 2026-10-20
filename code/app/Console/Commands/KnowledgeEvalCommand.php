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

use App\Constants\KnowledgeEnum;
use App\Http\Dao\Knowledge\KnowledgeEvalRunDao;
use App\Http\Service\Knowledge\KnowledgeEvalService;
use Illuminate\Console\Command;

/**
 * 对已确认评测用例跑离线检索指标.
 *
 * 用法：php artisan knowledge:eval {--name=} {--baseline}
 */
class KnowledgeEvalCommand extends Command
{
    protected $signature = 'knowledge:eval
                            {--name= : 运行名称}
                            {--baseline : 关闭增强开关作为基线对照}';

    protected $description = '对已确认评测用例跑离线检索（Recall@k / MRR / miss_rate）';

    /**
     * 执行离线评测.
     */
    public function handle(KnowledgeEvalService $evalService, KnowledgeEvalRunDao $runDao): int
    {
        $baseline = (bool) $this->option('baseline');
        $name     = trim((string) $this->option('name'));
        if ($name === '') {
            $name = $baseline ? 'baseline' : 'eval-' . date('YmdHis');
        }

        $override = $baseline ? [
            'query_rewrite'  => false,
            'rerank'         => false,
            'semantic_chunk' => false,
            'fact_check'     => false,
        ] : null;

        $this->info($baseline ? "开始基线评测：{$name}" : "开始评测：{$name}");

        try {
            $runId = $evalService->runOffline($name, $override);
        } catch (\Throwable $e) {
            $this->error('评测失败：' . $e->getMessage());

            return self::FAILURE;
        }

        $run = $runDao->get($runId);
        if (! $run) {
            $this->error("评测记录不存在：{$runId}");

            return self::FAILURE;
        }

        if ((int) $run->status === KnowledgeEnum::EVAL_RUN_FAILED) {
            $this->error('评测失败：' . (string) $run->error_msg);

            return self::FAILURE;
        }

        $metrics = (array) ($run->metrics ?? []);
        $this->info("run_id={$runId}");
        $this->table(
            ['recall@3', 'recall@5', 'mrr', 'miss_rate'],
            [[
                $this->formatMetric($metrics['recall_at_3'] ?? 0),
                $this->formatMetric($metrics['recall_at_5'] ?? 0),
                $this->formatMetric($metrics['mrr'] ?? 0),
                $this->formatMetric($metrics['miss_rate'] ?? 0),
            ]]
        );

        return self::SUCCESS;
    }

    /**
     * @param mixed $value
     */
    private function formatMetric($value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
