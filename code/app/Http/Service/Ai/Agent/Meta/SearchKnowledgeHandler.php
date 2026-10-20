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

namespace App\Http\Service\Ai\Agent\Meta;

use App\Http\Model\Ai\AiApplication;
use App\Http\Service\Ai\AiToolCallService;
use App\Http\Service\Knowledge\KnowledgeRagService;

/**
 * search_knowledge 元工具：按需检索应用绑定知识库并累积 references.
 */
final class SearchKnowledgeHandler
{
    /**
     * @param array<string, mixed> $toolCall
     * @param array<string, mixed> $args
     * @param array{
     *     knowledgeToolAllowed: bool,
     *     knowledgeReferences: array<int, array<string, mixed>>,
     *     knowledgeImages: array<int, mixed>
     * } $state
     *
     * @return array{envelope: array<string, mixed>, state: array<string, mixed>}
     */
    public static function execute(AiApplication $app, array $toolCall, array $args, array $state): array
    {
        if (empty($state['knowledgeToolAllowed'])) {
            $result = [
                'error'      => true,
                'error_code' => 'TOOL_UNAVAILABLE',
                'message'    => '当前应用未绑定知识库或未开放检索',
            ];

            return [
                'envelope' => self::envelope($toolCall, $result),
                'state'    => $state,
            ];
        }

        $query = trim((string) ($args['query'] ?? ''));
        if ($query === '') {
            $result = [
                'error'      => true,
                'error_code' => 'INVALID_ARGUMENT',
                'message'    => 'query 不能为空',
            ];

            return [
                'envelope' => self::envelope($toolCall, $result),
                'state'    => $state,
            ];
        }

        $topK = isset($args['top_k']) ? (int) $args['top_k'] : null;
        if ($topK !== null && $topK <= 0) {
            $topK = null;
        }
        $budget = (int) config('ai.plugins.rag.token_budget', 2000);
        $hit    = app(KnowledgeRagService::class)->retrieveForApp(
            (int) $app->id,
            $query,
            $topK,
            $budget > 0 ? $budget : null
        );

        $references = (array) ($hit['references'] ?? []);
        $images     = (array) ($hit['images'] ?? []);
        $state['knowledgeReferences'] = array_values(array_merge(
            (array) ($state['knowledgeReferences'] ?? []),
            $references
        ));
        $state['knowledgeImages'] = array_values(array_merge(
            (array) ($state['knowledgeImages'] ?? []),
            $images
        ));

        $result = [
            'hit_count'  => count($references),
            'prompt'     => (string) ($hit['prompt'] ?? ''),
            'references' => array_map(static function (array $ref): array {
                return [
                    'title'   => (string) ($ref['title'] ?? $ref['doc_title'] ?? ''),
                    'score'   => $ref['score'] ?? null,
                    'snippet' => mb_substr((string) ($ref['content'] ?? $ref['text'] ?? ''), 0, 200),
                ];
            }, $references),
            'message' => $references === []
                ? '未命中相关知识库资料，可改写 query 重试或改用业务工具'
                : '已检索到资料，请优先依据 prompt/references 作答并标注引用',
        ];

        return [
            'envelope' => [
                'id'      => (string) ($toolCall['id'] ?? ''),
                'name'    => AiToolCallService::SEARCH_KNOWLEDGE_NAME,
                'result'  => $result,
                'content' => json_encode([
                    'hit_count'  => $result['hit_count'],
                    'prompt'     => $result['prompt'],
                    'message'    => $result['message'],
                    'references' => $result['references'],
                ], JSON_UNESCAPED_UNICODE) ?: '',
            ],
            'state' => $state,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'name'        => AiToolCallService::SEARCH_KNOWLEDGE_NAME,
            'description' => '按需检索当前应用绑定的知识库。回答产品说明、操作流程、制度政策等静态知识时先调用本工具；'
                . '实时业务数据、创建/修改/审批等操作不要用本工具，应 load_tools 后调用业务工具。',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'query' => [
                        'type'        => 'string',
                        'description' => '检索问句，尽量完整复述用户知识相关需求',
                    ],
                    'top_k' => [
                        'type'        => 'integer',
                        'description' => '最多返回片段条数，默认跟随应用配置',
                    ],
                ],
                'required'   => ['query'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $toolCall
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private static function envelope(array $toolCall, array $result): array
    {
        return [
            'id'      => (string) ($toolCall['id'] ?? ''),
            'name'    => AiToolCallService::SEARCH_KNOWLEDGE_NAME,
            'result'  => $result,
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE) ?: '',
        ];
    }
}
