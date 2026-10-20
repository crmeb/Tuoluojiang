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

namespace App\Http\Service\Ai;

use App\Constants\Ai\AiConversationEnum;
use App\Http\Model\Ai\AiApplication;
use Tuoluojiang\Open\services\CoreBusinessService;
use Illuminate\Support\Facades\DB;

/**
 * AI SQL 数据源服务：让模型生成只读查询，并把受限结果作为会话上下文。
 *
 * 调用链：AiToolCallService/AiConversationService → 本服务 → CoreBusinessService/DB；工具名 query_business_data。
 */
class AiSqlQueryService
{
    public const TOOL_NAME = 'query_business_data';

    /**
     * 返回注入模型的 SQL 查询工具定义（OpenAI function 格式）。
     *
     * @return array{name: string, description: string, inputSchema: array<string, mixed>} 工具元数据
     */
    public function definition(): array
    {
        return [
            'name'        => self::TOOL_NAME,
            'description' => '查询已授权的业务数据表。必须调用本工具（query_business_data），禁止编造 mysql_query/sql_query 等其它名称。参数 sql 必须是一条只读 SELECT/WITH 查询，且只能使用授权表。',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'sql' => [
                        'type'        => 'string',
                        'description' => '一条只读 SELECT 语句，表名必须在授权范围内',
                    ],
                ],
                'required' => ['sql'],
            ],
        ];
    }

    /**
     * 判断应用是否启用了 SQL 数据源（仅只读查询）.
     *
     * 需同时满足：source_type 含 SOURCE_SQL，且配置了非空表白名单.
     *
     * @param AiApplication $app 应用模型
     */
    public function isEnabled(AiApplication $app): bool
    {
        return $this->enabled($app);
    }

    /**
     * 生成 SQL 数据源说明片段，供系统提示词注入。
     *
     * @param AiApplication $app 应用模型
     * @param string $question 用户问题
     *
     * @return string 上下文 Markdown，未启用或无配置时为空
     */
    public function context(AiApplication $app, string $question): string
    {
        if (! $this->enabled($app)) {
            return '';
        }

        $prompt = trim((string) ($app->content ?: $app->tooltip_text));
        $tables = $this->allowedTables($app);
        if ($prompt === '' && ! $tables) {
            return '';
        }

        return "# SQL数据源说明\n"
            . "本数据源仅支持只读 SELECT/WITH 查询，禁止增删改与 DDL。\n"
            . "当编排计划启用 SQL 时，通过工具 query_business_data 提交 sql 参数；不要输出 FunctionCall 文本或其它工具名。\n"
            . ($prompt !== '' ? $prompt . "\n" : '')
            . ($tables ? '允许查询的数据表：' . implode(', ', $tables) . "\n" : '')
            . "\n用户问题：" . $question;
    }

    /**
     * 执行模型通过工具提交的只读 SQL，不再额外请求一次模型生成语句。
     *
     * @param AiApplication $app 应用模型
     * @param string $sql 模型提交的 SQL
     * @param null|int $maxRows 预览行数上限；null 时读 config('ai.plugins.sql.max_rows')
     *
     * @return array<string, mixed> 查询结果或 error 结构
     */
    public function run(AiApplication $app, string $sql, ?int $maxRows = null): array
    {
        if (! $this->enabled($app)) {
            return ['error' => true, 'message' => '当前应用未启用SQL数据源'];
        }

        $allowedTables = $this->allowedTables($app);
        if (! $allowedTables) {
            return ['error' => true, 'message' => '未配置允许查询的数据表'];
        }

        $sql = self::toolSql($sql);
        if ($sql === '') {
            return ['error' => true, 'message' => '缺少SQL查询语句'];
        }

        $sql = app(CoreBusinessService::class)->normalizeTablePrefix($sql);
        if (! app(CoreBusinessService::class)->validateSelectSql($sql)) {
            return ['error' => true, 'message' => 'SQL安全校验失败，仅允许SELECT查询', 'sql' => $sql];
        }
        if (! $this->usesAllowedTables($sql, $allowedTables)) {
            return ['error' => true, 'message' => 'SQL包含未授权的数据表', 'sql' => $sql];
        }

        $limit   = max(1, $maxRows ?? (int) config('ai.plugins.sql.max_rows', 10));
        $rows    = DB::select($this->limitSql($sql, $limit));
        $arrange = trim((string) ($app->data_arrange_text ?? ''));
        $result  = [
            'type' => 'sql',
            'sql'  => $sql,
            'rows' => $rows,
        ];
        if ($arrange !== '') {
            $result['arrange'] = $arrange;
        }

        return $result;
    }

    /**
     * 规范化工具参数中的 SQL：只接受整段以 SELECT/WITH 开头的语句，不从正文截取.
     *
     * @param string $content 工具 sql 参数原文
     *
     * @return string 可执行 SQL；散文夹带 SELECT 时返回空串
     */
    public static function toolSql(string $content): string
    {
        $content = trim(str_replace(['```sql', '```'], '', $content));
        if ($content === '') {
            return '';
        }
        // 工具参数必须整段是 SQL，禁止「请统计一下 SELECT …」这类散文截取.
        if (! preg_match('/^(WITH|SELECT)\b/is', $content)) {
            return '';
        }

        return rtrim($content, " \t\n\r\0\x0B;");
    }

    /**
     * 将 SQL 包装为子查询并限制预览行数。
     *
     * @param string $sql 已校验的 SELECT
     * @param int $maxRows 行数上限
     */
    private function limitSql(string $sql, int $maxRows): string
    {
        $sql = rtrim(trim($sql), ';');

        // 只取少量预览数据作为上下文，避免模型一次带出过多业务数据.
        return 'SELECT * FROM (' . $sql . ') AS ai_sql_preview LIMIT ' . max(1, $maxRows);
    }

    /** 读取应用授权表列表并补全表前缀。 */
    private function allowedTables(AiApplication $app): array
    {
        $tables = (array) ($app->tables ?? []);
        if ($tables === []) {
            // ponytail: 仅解析旧版 content 中的静态 CREATE TABLE，动态表名仍需显式配置 tables。
            preg_match_all(
                '/\bCREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([A-Za-z0-9_]{1,64})`?/i',
                (string) ($app->content ?? ''),
                $matches
            );
            $tables = $matches[1] ?? [];
        }
        $prefix = (string) DB::getTablePrefix();

        return array_values(array_unique(array_filter(array_map(function ($table) use ($prefix) {
            if (is_array($table)) {
                $table = $table['name'] ?? $table['table'] ?? '';
            }
            $table = trim((string) $table, "` \t\n\r\0\x0B");
            if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $table)) {
                return '';
            }
            $table = preg_replace('/^(?:' . preg_quote($prefix, '/') . '|eb_)/i', '', $table);

            return $prefix . ltrim((string) $table, '_');
        }, $tables))));
    }

    /** 判断应用 source_type 是否包含 SQL 且已配置表白名单. */
    private function enabled(AiApplication $app): bool
    {
        if (! AiConversationEnum::hasSource($app->sourceTypeMask(), AiConversationEnum::SOURCE_SQL)) {
            return false;
        }

        return $this->allowedTables($app) !== [];
    }

    /** 校验 SQL 中 FROM/JOIN 引用的表均在授权白名单内。 */
    private function usesAllowedTables(string $sql, array $allowedTables): bool
    {
        preg_match_all('/\b(?:FROM|JOIN)\s+`?([A-Za-z0-9_]+)`?/i', $sql, $matches);
        $tables = array_values(array_unique($matches[1] ?? []));

        return $tables !== [] && ! array_diff($tables, $allowedTables);
    }
}
