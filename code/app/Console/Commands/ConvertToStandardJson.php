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

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * 处理非标准JSON数据.
 */
class ConvertToStandardJson extends Command
{
    /**
     * 命令签名.
     *
     * @var string
     */
    protected $signature = 'fix:to-json
                            {--db= : 数据库名称（默认使用配置中的默认数据库）}
                            {--table= : 表名称（必填）}
                            {--field= : 需要转换的字段名（必填）}
                            {--dry-run : 测试模式，不实际更新数据}
                            {--max-attempts=5 : 最大处理尝试次数}
                            {--batch-size=500 : 批处理大小}
                            {--force : 强制重新处理所有记录（包括已处理的）}
                            {--reset : 重置所有处理状态，重新开始}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '将数据库中指定字段转换为标准JSON格式（强制转换JSON数组内数字为字符串）';

    /**
     * 更新这些历史业务表时需要显式保留创建时间，避免线上字段误带 ON UPDATE 时被刷新.
     */
    private const PRESERVE_CREATED_AT_TABLES = ['customer'];

    /**
     * 执行命令.
     *
     * @return int
     */
    public function handle()
    {
        // 获取用户输入的参数
        $database    = $this->option('db') ?: config('database.default');
        $table       = $this->option('table');
        $field       = $this->option('field');
        $dryRun      = $this->option('dry-run');
        $maxAttempts = (int) $this->option('max-attempts');
        $batchSize   = (int) $this->option('batch-size');
        $force       = $this->option('force');
        $reset       = $this->option('reset');

        // 限制批处理大小范围
        $batchSize   = max(10, min(1000, $batchSize));
        $maxAttempts = max(1, min(10, $maxAttempts));

        // 验证必填参数
        if (empty($table) || empty($field)) {
            $this->error('请指定表名（--table）和字段名（--field）！');
            return 1;
        }

        // 安全提示
        $this->warn("⚠️ 即将处理数据库 [{$database}] 表 [{$table}] 的字段 [{$field}]");
        $this->warn('⚠️ 操作前请务必备份数据！建议先使用 --dry-run 测试！');

        // 创建处理状态跟踪表
        $this->createProcessingTable($database);

        // 重置处理状态（如果需要）
        if ($reset) {
            DB::connection($database)->table('json_conversion_status')
                ->where('table_name', $table)
                ->delete();
            $this->info("已重置表 [{$table}] 的所有处理状态");
        }

        // 多轮处理主循环
        $round       = 1;
        $totalFixed  = 0;
        $totalErrors = 0;

        do {
            $this->info("\n===== 第 {$round} 轮处理开始 =====");

            // 构建查询：默认只获取字段值可疑的记录，避免百万级表全量刷状态
            $query = $this->buildQuery($database, $table, $field, $force);

            $this->info($force ? '本轮将按批次处理所有非 NULL 记录' : '本轮将按批次处理可疑记录');

            // 初始化进度条（不预先 count，避免正式处理前额外扫一遍大表）
            $bar = $this->output->createProgressBar();
            $bar->start();

            // 本轮统计变量
            $roundProcessed = 0;
            $roundFixed  = 0;
            $roundErrors = 0;

            // 分批处理记录：使用 id 游标，避免记录更新为 NULL 后查询结果收缩导致 offset 跳过数据
            $lastId = 0;
            do {
                $records     = (clone $query)->where("{$table}.id", '>', $lastId)->take($batchSize)->get();
                $recordCount = $records->count();

                if ($recordCount > 0) {
                    foreach ($records as $record) {
                        $id             = (int) $record->id;
                        $lastId         = $id;
                        ++$roundProcessed;
                        $originalValue  = $record->{$field};
                        $convertedValue = null;
                        $error          = null;

                        try {
                            // 核心转换逻辑（强制处理所有值）
                            $convertedValue = $this->convertToStandardJson($originalValue);
                        } catch (\Exception $e) {
                            $error = $e->getMessage();
                            ++$roundErrors;
                        }

                        // 处理转换结果
                        if ($error) {
                            $this->line("\nID: {$id} 转换失败: {$error}（原始值: " . substr((string) $originalValue, 0, 50) . '...)');
                            if (! $dryRun) {
                                $this->updateProcessingStatus($database, $table, $id, 'error', $error);
                            }
                        } elseif ($convertedValue !== $originalValue) {
                            ++$roundFixed;
                            $this->line("\nID: {$id} 已转换（原始值: " . substr((string) $originalValue, 0, 50) . '...）');

                            // 非测试模式则更新数据库
                            if (! $dryRun) {
                                $this->updateRecordField($database, $table, $field, $id, $convertedValue);
                            }
                            if (! $dryRun) {
                                $this->updateProcessingStatus($database, $table, $id, 'processed', '转换成功');
                            }
                        } else {
                            // 已经是标准格式且无需转换
                            if (! $dryRun) {
                                $this->updateProcessingStatus($database, $table, $id, 'valid', '已是目标格式');
                            }
                        }

                        $bar->advance();
                    }
                }
            } while ($recordCount === $batchSize);

            $bar->finish();

            if ($roundProcessed === 0) {
                $this->info("\n没有需要处理的记录，提前结束");
                break;
            }

            // 累计统计
            $totalFixed += $roundFixed;
            $totalErrors += $roundErrors;

            $this->info("\n第 {$round} 轮处理完成:");
            $this->info("  本轮处理记录: {$roundProcessed}");
            $this->info("  本轮转换成功: {$roundFixed}");
            $this->info("  本轮转换失败: {$roundErrors}");

            ++$round;
        } while ($round <= $maxAttempts && $roundFixed > 0);

        // 输出总体统计结果
        $this->info("\n\n===== 全部处理完成 =====");
        $this->info('总处理轮次: ' . ($round - 1));
        $this->info("累计转换成功: {$totalFixed}");
        $this->info("累计转换失败: {$totalErrors}");

        if ($dryRun) {
            $this->info('（测试模式，未实际更新数据）');
        }

        $this->info("处理状态记录已保存到 {$database}.json_conversion_status 表");

        return 0;
    }

    /**
     * 创建处理状态跟踪表.
     */
    protected function createProcessingTable(string $database): void
    {
        $schema = DB::connection($database)->getSchemaBuilder();

        if (! $schema->hasTable('json_conversion_status')) {
            $schema->create('json_conversion_status', function ($table) {
                $table->string('table_name', 100);
                $table->bigInteger('record_id');
                $table->string('status', 20)->comment('valid:有效, processed:已处理, error:错误');
                $table->text('message')->nullable();
                $table->integer('attempts')->default(1);
                $table->timestamp('last_processed_at')->useCurrent();

                $table->primary(['table_name', 'record_id']);
                $table->index('status');
            });

            $this->info('已创建处理状态表 json_conversion_status');
        }
    }

    /**
     * 更新处理状态
     */
    protected function updateProcessingStatus(
        string $database,
        string $table,
        int $recordId,
        string $status,
        string $message = ''
    ): void {
        $connection = DB::connection($database);
        $exists     = $connection->table('json_conversion_status')
            ->where('table_name', $table)
            ->where('record_id', $recordId)
            ->exists();

        if ($exists) {
            $connection->table('json_conversion_status')
                ->where('table_name', $table)
                ->where('record_id', $recordId)
                ->update([
                    'status'            => $status,
                    'message'           => $message,
                    'attempts'          => DB::raw('attempts + 1'),
                    'last_processed_at' => now(),
                ]);
        } else {
            $connection->table('json_conversion_status')
                ->insert([
                    'table_name'        => $table,
                    'record_id'         => $recordId,
                    'status'            => $status,
                    'message'           => $message,
                    'attempts'          => 1,
                    'last_processed_at' => now(),
                ]);
        }
    }

    /**
     * 构建查询：默认只查询需要转换的可疑记录，--force 才全量重刷.
     */
    protected function buildQuery(string $database, string $table, string $field, bool $force): Builder
    {
        $connection = DB::connection($database);
        $query      = $connection->table($table)
            ->whereNotNull("{$table}.{$field}")
            ->select("{$table}.*")
            ->orderBy("{$table}.id");

        if ($force) {
            return $query;
        }

        $query->leftJoin(
            'json_conversion_status',
            function ($join) use ($table, $connection) {
                $join->on('json_conversion_status.table_name', '=', $connection->raw("'{$table}'"))
                    ->on('json_conversion_status.record_id', '=', "{$table}.id");
            }
        )->where(function ($q) {
            $q->whereNull('json_conversion_status.status')
                ->orWhere('json_conversion_status.status', 'error');
        })->where(function ($q) use ($field, $connection, $table) {
            $qualifiedField = $this->qualifiedColumn($connection, $table, $field);
            $jsonType       = $this->jsonTypeExpression($qualifiedField);

            $q->whereRaw("LOWER(TRIM({$qualifiedField})) IN ('', 'null', 'nil', 'n/a', '[]', '{}', '\"\"', '\"null\"', '''null''', '[\"\"]', '[null]', '[\"null\"]')")
                ->orWhereRaw("TRIM({$qualifiedField}) = CHAR(39,39)")
                ->orWhereRaw("JSON_VALID({$qualifiedField}) = 0")
                ->orWhereRaw("{$jsonType} IS NOT NULL AND {$jsonType} <> 'ARRAY'")
                ->orWhereRaw("{$jsonType} = 'ARRAY' AND JSON_SEARCH({$qualifiedField}, 'one', '') IS NOT NULL")
                ->orWhereRaw("{$jsonType} = 'ARRAY' AND JSON_SEARCH(LOWER({$qualifiedField}), 'one', 'null') IS NOT NULL")
                ->orWhereRaw("{$jsonType} = 'ARRAY' AND JSON_CONTAINS({$qualifiedField}, CAST('[null]' AS JSON))")
                ->orWhereRaw("{$jsonType} = 'ARRAY' AND {$qualifiedField} REGEXP '\\\\[[^\\\"]*[0-9]'");
        });

        return $query;
    }

    protected function updateRecordField(string $database, string $table, string $field, int $id, ?string $convertedValue): void
    {
        DB::connection($database)->table($table)
            ->where('id', $id)
            ->update($this->buildUpdateValues($table, $field, $convertedValue));
    }

    protected function buildUpdateValues(string $table, string $field, ?string $convertedValue): array
    {
        $values = [$field => $convertedValue];

        if (in_array($table, self::PRESERVE_CREATED_AT_TABLES, true)) {
            $values['created_at'] = new Expression('created_at');
        }

        return $values;
    }

    protected function qualifiedColumn($connection, string $table, string $field): string
    {
        return '`' . str_replace('`', '``', $connection->getTablePrefix() . $table) . '`.`' . str_replace('`', '``', $field) . '`';
    }

    protected function jsonTypeExpression(string $qualifiedField): string
    {
        return "CASE WHEN JSON_VALID({$qualifiedField}) = 1 THEN JSON_TYPE({$qualifiedField}) ELSE NULL END";
    }

    /**
     * 转换为标准JSON格式（强制处理所有JSON数组）.
     * @param mixed $value
     */
    protected function convertToStandardJson($value): ?string
    {
        // 1. 确保值是字符串
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = trim($value);

        // 2. 处理空值/null值（直接返回null，不包装数组）
        if ($this->isNullLikeValue($value)) {
            return null;
        }

        // 3. 处理斜杠分隔的字符串（/1/2/3/、1/2/3 等）
        if (str_contains($value, '/') && ! preg_match('/[\{\[\}]/', $value)) {
            $parts = explode('/', trim($value, '/'));
            $parts = array_filter($parts, function ($part) {
                return trim($part) !== '';
            });
            $parts = array_map(function ($part) {
                return (string) trim($part);
            }, $parts);
            $jsonData = $parts;
        }
        // 4. 强制处理所有JSON数组（核心逻辑）
        elseif (preg_match('/^\[.*\]$/', $value)) {
            // 先解析JSON
            $jsonData = json_decode($value, true);

            // 确保解析结果是数组
            if (! is_array($jsonData)) {
                $jsonData = [(string) trim($value, '[] ')];
            }

            // 强制将所有元素转为字符串，并清理旧数据中的空值/null字符串
            $jsonData = $this->normalizeJsonArray($jsonData);
        }
        // 5. 处理其他格式
        else {
            // 剥离外层引号
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = substr($value, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                    $value = trim($value);
                }
            }

            // 反转义
            $unescaped = $this->unescapeValue($value);

            // 尝试解析为JSON
            $jsonData  = json_decode($unescaped, true);
            $jsonError = json_last_error();

            // 多策略处理非标准格式
            if ($jsonError !== JSON_ERROR_NONE) {
                $strategies = [
                    function ($str) { // 单引号转双引号
                        return str_replace("'", '"', $str);
                    },
                    function ($str) { // 无引号键名加引号
                        return preg_replace('/([{,]\s*)(\w+)(\s*:)/', '$1"$2"$3', $str);
                    },
                    function ($str) { // 无引号数组元素加引号
                        return preg_replace_callback('/([,\[])\s*([^",\]\[]+?)(?=\s*[,\]])/', function ($match) {
                            $val = trim($match[2]);
                            $normalized = in_array(strtolower($val), ['true', 'false']) ? $val : '"' . addslashes($val) . '"';
                            return $match[1] . $normalized;
                        }, $str);
                    },
                    function ($str) { // 逗号分隔转数组
                        if (str_contains($str, ',') && ! preg_match('/[\{\[\}]/', $str)) {
                            $parts = array_map(function ($part) {
                                return '"' . addslashes(trim($part, " \t\n\r\0\x0B\"'")) . '"';
                            }, explode(',', $str));
                            return '[' . implode(',', $parts) . ']';
                        }
                        return $str;
                    },
                ];

                foreach ($strategies as $strategy) {
                    $modified = $strategy($unescaped);
                    if ($modified !== $unescaped) {
                        $jsonData = json_decode($modified, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $unescaped = $modified;
                            break;
                        }
                    }
                }

                // 最终兜底：包装为字符串数组
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $jsonData = [(string) trim($unescaped, " \t\n\r\0\x0B\"'")];
                }
            }

            // 确保数组元素都是字符串，并清理旧数据中的空值/null字符串
            if (is_array($jsonData)) {
                $jsonData = $this->normalizeJsonArray($jsonData);
            } else {
                if ($this->isNullLikeScalar($jsonData)) {
                    return null;
                }
                $jsonData = [(string) $jsonData];
            }
        }

        // 确保最终是数组
        if (! is_array($jsonData)) {
            $jsonData = [(string) $jsonData];
        }

        if ($jsonData === []) {
            return null;
        }

        try {
            return json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \Exception('转换后仍非有效JSON: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * 判断旧数据中的空值/null字符串.
     * @param mixed $value
     */
    protected function isNullLikeScalar($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_scalar($value)) {
            return false;
        }

        $value = trim((string) $value);

        return $value === '' || in_array(strtolower($value), ['null', 'nil', 'n/a'], true);
    }

    /**
     * 判断原始字符串是否应当写入数据库 NULL.
     */
    protected function isNullLikeValue(string $value): bool
    {
        $value = trim($value);

        if ($this->isNullLikeScalar($value) || in_array($value, ['[]', '{}', '""', "''", '[""]'], true)) {
            return true;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return $this->isNullLikeScalar(substr($value, 1, -1));
            }
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if ($this->isNullLikeScalar($decoded)) {
            return true;
        }

        if (! is_array($decoded)) {
            return false;
        }

        foreach ($decoded as $item) {
            if (! $this->isNullLikeScalar($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 将 JSON 数组规范为字符串数组，并过滤旧数据中的空值/null字符串.
     */
    protected function normalizeJsonArray(array $items): array
    {
        $jsonData = [];

        foreach ($items as $item) {
            if ($this->isNullLikeScalar($item)) {
                continue;
            }

            if (is_bool($item)) {
                $jsonData[] = $item;
                continue;
            }

            $jsonData[] = (string) $item;
        }

        return $jsonData;
    }

    /**
     * 反转义可能的过度转义字符.
     */
    protected function unescapeValue(string $value): string
    {
        $prev          = $value;
        $changed       = true;
        $maxIterations = 5;
        $iteration     = 0;

        while ($changed && $iteration < $maxIterations) {
            $current     = htmlspecialchars_decode($prev, ENT_QUOTES);
            $jsonDecoded = json_decode('"' . $current . '"', true);
            $current     = $jsonDecoded ?? $current;

            if ($current === $prev) {
                $changed = false;
            } else {
                $prev = $current;
            }

            ++$iteration;
        }

        return $prev;
    }
}
