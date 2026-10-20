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

namespace App\Http\Service\Knowledge;

use App\Constants\KnowledgeEnum;
use App\Http\Dao\Knowledge\KnowledgeEvalCaseDao;
use App\Http\Dao\Knowledge\KnowledgeEvalRunDao;
use App\Http\Dao\Knowledge\KnowledgeQuestionLogDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\ServicesException;
use Illuminate\Support\Facades\Log;

/**
 * 知识库离线评测：指标计算、跑批与用例导入.
 *
 * 指标定义（scoreRun 纯函数，不访问数据库）：
 * - Recall@k：黄金 chunk 任一出现在命中 Top k 的 case 占比.
 * - MRR：首个黄金 chunk 排名的倒数平均；Top max(k) 未命中计 0.
 * - miss_rate：Top1..max(k) 皆无黄金的占比（k∈{3,5}，故 max(k)=5）.
 *
 * 跑批走 KnowledgeRagService::retrieveHits（改写 → 粗排 → 精排 → topK），
 * --baseline / featureOverride 会临时改写 knowledge.features 从而改变管线.
 * 已确认但 golden_chunk_ids 为空的用例跳过，计入 metrics.skipped_empty_golden.
 *
 * 验证命令：php artisan test --filter=KnowledgeEval
 */
class KnowledgeEvalService extends BaseService
{
    private const RECALL_KS = [3, 5];

    private KnowledgeEvalCaseDao $caseDao;

    private KnowledgeRagService $ragService;

    /**
     * 注入评测 Dao 与 RAG 检索服务.
     *
     * @param KnowledgeEvalRunDao $dao 评测运行 Dao
     * @param KnowledgeEvalCaseDao $caseDao 评测用例 Dao
     * @param KnowledgeRagService $ragService RAG 检索（与线上同一管线）
     */
    public function __construct(
        KnowledgeEvalRunDao $dao,
        KnowledgeEvalCaseDao $caseDao,
        KnowledgeRagService $ragService
    ) {
        $this->dao        = $dao;
        $this->caseDao    = $caseDao;
        $this->ragService = $ragService;
    }

    /**
     * 根据检索命中计算 Recall@k、MRR、miss_rate.
     *
     * @param array<int, array{id?:int, golden_chunk_ids?:array<int,int>, hits?:array<int, mixed>}> $caseResults
     *
     * @return array{recall_at_3: float, recall_at_5: float, mrr: float, miss_rate: float, details: array<int, array<string, mixed>>}
     */
    public function scoreRun(array $caseResults): array
    {
        $total = count($caseResults);
        if ($total === 0) {
            return [
                'recall_at_3' => 0.0,
                'recall_at_5' => 0.0,
                'mrr'         => 0.0,
                'miss_rate'   => 0.0,
                'details'     => [],
            ];
        }

        $maxK    = max(self::RECALL_KS);
        $hitAtK  = [3 => 0, 5 => 0];
        $mrrSum  = 0.0;
        $miss    = 0;
        $details = [];

        foreach ($caseResults as $case) {
            $id     = (int) ($case['id'] ?? 0);
            $golden = array_values(array_filter(array_map('intval', (array) ($case['golden_chunk_ids'] ?? []))));
            $hitIds = $this->extractHitIds((array) ($case['hits'] ?? []));
            $rank   = $this->firstGoldenRank($hitIds, $golden, $maxK);

            $at3 = $rank > 0 && $rank <= 3;
            $at5 = $rank > 0 && $rank <= 5;
            if ($at3) {
                ++$hitAtK[3];
            }
            if ($at5) {
                ++$hitAtK[5];
            }
            if ($rank === 0) {
                ++$miss;
            }
            $mrrSum += $rank > 0 ? 1 / $rank : 0.0;

            $details[] = [
                'id'       => $id,
                'rank'     => $rank,
                'hit_at_3' => $at3,
                'hit_at_5' => $at5,
            ];
        }

        return [
            'recall_at_3' => $hitAtK[3] / $total,
            'recall_at_5' => $hitAtK[5] / $total,
            'mrr'         => $mrrSum / $total,
            'miss_rate'   => $miss / $total,
            'details'     => $details,
        ];
    }

    /**
     * 对已确认用例走 RAG 检索管线并落库 run，返回 run id.
     *
     * @param null|array<string, mixed> $featureOverride 临时覆盖 knowledge.features（如基线全关 rewrite/rerank）
     *
     * @throws \Throwable
     */
    public function runOffline(string $name, ?array $featureOverride = null): int
    {
        $original = (array) config('knowledge.features', []);
        $snapshot = $this->snapshotFlags($featureOverride);
        $run      = $this->dao->create([
            'name'          => $name,
            'note'          => '',
            'feature_flags' => $snapshot,
            'metrics'       => [],
            'status'        => KnowledgeEnum::EVAL_RUN_PENDING,
            'error_msg'     => '',
            'created_by'    => 0,
        ]);
        $runId = (int) $run->id;

        try {
            $this->dao->update($runId, ['status' => KnowledgeEnum::EVAL_RUN_RUNNING]);
            if ($featureOverride !== null) {
                config(['knowledge.features' => array_merge($original, $featureOverride)]);
            }

            $cases = $this->caseDao->search(['status' => KnowledgeEnum::EVAL_CASE_CONFIRMED])->get()->toArray();
            if ($cases === []) {
                $this->dao->update($runId, [
                    'status'    => KnowledgeEnum::EVAL_RUN_FAILED,
                    'error_msg' => '没有已确认的评测用例',
                ]);

                return $runId;
            }

            $topK        = max(self::RECALL_KS);
            $skipped     = 0;
            $caseResults = [];
            foreach ($cases as $case) {
                $caseId = (int) ($case['id'] ?? 0);
                $golden = array_values(array_filter(array_map('intval', (array) ($case['golden_chunk_ids'] ?? []))));
                if ($golden === []) {
                    ++$skipped;
                    Log::warning('knowledge.eval.skip_empty_golden', ['id' => $caseId]);
                    continue;
                }

                $kbIds     = array_values(array_filter(array_map('intval', (array) ($case['kb_ids'] ?? []))));
                $spaceType = (int) ($case['space_type'] ?? KnowledgeEnum::SPACE_INTERNAL);
                $hits      = $this->ragService->retrieveHits(
                    (string) ($case['question'] ?? ''),
                    $kbIds,
                    $spaceType,
                    $topK
                );
                $caseResults[] = [
                    'id'               => $caseId,
                    'golden_chunk_ids' => $golden,
                    'hits'             => $hits,
                ];
            }

            if ($caseResults === []) {
                $this->dao->update($runId, [
                    'status'    => KnowledgeEnum::EVAL_RUN_FAILED,
                    'error_msg' => "没有可评测的用例（已跳过 {$skipped} 条黄金切片为空的已确认用例）",
                    'note'      => $this->skippedNote($skipped),
                    'metrics'   => ['skipped_empty_golden' => $skipped],
                ]);

                return $runId;
            }

            $metrics                         = $this->scoreRun($caseResults);
            $metrics['skipped_empty_golden'] = $skipped;

            $this->dao->update($runId, [
                'status'        => KnowledgeEnum::EVAL_RUN_DONE,
                'metrics'       => $metrics,
                'feature_flags' => $this->snapshotFlags(),
                'note'          => $this->skippedNote($skipped),
            ]);
        } catch (\Throwable $e) {
            $this->dao->update($runId, [
                'status'    => KnowledgeEnum::EVAL_RUN_FAILED,
                'error_msg' => mb_substr($e->getMessage(), 0, 500),
            ]);

            throw $e;
        } finally {
            config(['knowledge.features' => $original]);
        }

        return $runId;
    }

    /**
     * 从 JSON 文件导入评测用例；缺省为草稿，source=manual.
     *
     * 支持根数组或 `{"cases":[...]}`；limit 为截取条数，null 表示全部.
     * 同一文件内按问句 trim+小写的 sha256 去重，避免重复导入.
     *
     * @throws ServicesException
     */
    public function importFromJson(string $file, ?int $limit = null): int
    {
        if (! is_readable($file)) {
            throw $this->exception('评测用例文件不可读：' . $file);
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (! is_array($decoded)) {
            throw $this->exception('评测用例 JSON 格式无效');
        }

        $rows = isset($decoded['cases']) && is_array($decoded['cases']) ? $decoded['cases'] : $decoded;
        if ($limit !== null && $limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $count = 0;
        $seen  = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $question = trim((string) ($row['question'] ?? ''));
            if ($question === '') {
                continue;
            }
            $hash = $this->questionHash($question);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $this->caseDao->create($this->normalizeImportedCase($row, $question, KnowledgeEnum::EVAL_SOURCE_MANUAL));
            ++$count;
        }

        return $count;
    }

    /**
     * 从问答日志生成草稿用例（黄金切片为空，待人工填写）.
     */
    public function importFromLog(int $limit = 100): int
    {
        $limit = max(1, $limit);
        $logs  = app(KnowledgeQuestionLogDao::class)
            ->search([])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->toArray();

        $count = 0;
        foreach ($logs as $log) {
            $question = trim((string) ($log['question'] ?? ''));
            if ($question === '') {
                continue;
            }
            $kbId = (int) ($log['kb_id'] ?? 0);
            $this->caseDao->create($this->normalizeImportedCase([
                'question'         => $question,
                'golden_chunk_ids' => [],
                'answer_points'    => [],
                'kb_ids'           => $kbId > 0 ? [$kbId] : [],
                'space_type'       => KnowledgeEnum::SPACE_INTERNAL,
                'tags'             => [],
                'status'           => KnowledgeEnum::EVAL_CASE_DRAFT,
            ], $question, KnowledgeEnum::EVAL_SOURCE_QUESTION_LOG));
            ++$count;
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeImportedCase(array $row, string $question, string $defaultSource): array
    {
        $status = (int) ($row['status'] ?? KnowledgeEnum::EVAL_CASE_DRAFT);
        if (! in_array($status, [KnowledgeEnum::EVAL_CASE_DRAFT, KnowledgeEnum::EVAL_CASE_CONFIRMED, KnowledgeEnum::EVAL_CASE_DISABLED], true)) {
            $status = KnowledgeEnum::EVAL_CASE_DRAFT;
        }

        $points = $row['answer_points'] ?? [];
        if (is_string($points)) {
            $points = $points === '' ? [] : [$points];
        }

        return [
            'question'         => $question,
            'golden_chunk_ids' => array_values(array_map('intval', (array) ($row['golden_chunk_ids'] ?? []))),
            'answer_points'    => array_values((array) $points),
            'kb_ids'           => array_values(array_filter(array_map('intval', (array) ($row['kb_ids'] ?? [])))),
            'space_type'       => (int) ($row['space_type'] ?? KnowledgeEnum::SPACE_INTERNAL) ?: KnowledgeEnum::SPACE_INTERNAL,
            'tags'             => array_values((array) ($row['tags'] ?? [])),
            'status'           => $status,
            'source'           => (string) ($row['source'] ?? $defaultSource) ?: $defaultSource,
        ];
    }

    /**
     * 黄金切片为空时写入 note，避免静默抬高 miss_rate.
     */
    private function skippedNote(int $skipped): string
    {
        if ($skipped <= 0) {
            return '';
        }

        return "跳过 {$skipped} 条黄金切片为空的已确认用例，未计入 miss_rate";
    }

    /**
     * 问句归一化哈希，用于 JSON 导入去重.
     */
    private function questionHash(string $question): string
    {
        return hash('sha256', mb_strtolower(trim($question)));
    }

    /**
     * @param null|array<string, mixed> $featureOverride
     *
     * @return array<string, mixed>
     */
    private function snapshotFlags(?array $featureOverride = null): array
    {
        $features = (array) config('knowledge.features', []);
        if ($featureOverride !== null) {
            $features = array_merge($features, $featureOverride);
        }

        return [
            'features' => $features,
            'retrieve' => (array) config('knowledge.retrieve', []),
        ];
    }

    /**
     * @param array<int, mixed> $hits
     *
     * @return array<int, int>
     */
    private function extractHitIds(array $hits): array
    {
        $ids = [];
        foreach ($hits as $hit) {
            if (is_array($hit)) {
                $ids[] = (int) ($hit['id'] ?? 0);
                continue;
            }
            $ids[] = (int) $hit;
        }

        return $ids;
    }

    /**
     * 首个黄金切片在 Top maxK 中的 1-based 排名；未命中返回 0.
     *
     * @param array<int, int> $hitIds
     * @param array<int, int> $golden
     */
    private function firstGoldenRank(array $hitIds, array $golden, int $maxK): int
    {
        if ($golden === []) {
            return 0;
        }

        $goldenMap = array_fill_keys($golden, true);
        foreach (array_slice($hitIds, 0, $maxK) as $index => $hitId) {
            if ($hitId > 0 && isset($goldenMap[$hitId])) {
                return $index + 1;
            }
        }

        return 0;
    }
}
