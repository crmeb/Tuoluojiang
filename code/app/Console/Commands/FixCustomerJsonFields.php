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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * 规整客户与线索历史 JSON 字段.
 */
class FixCustomerJsonFields extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fix:customer-json-fields
                            {--db= : 数据库连接名称（默认使用配置中的默认连接）}
                            {--dry-run : 测试模式，不实际更新数据}
                            {--force : 强制重新处理所有记录（包括已处理的）}
                            {--reset : 重置所有处理状态，重新开始}
                            {--max-attempts=5 : 最大处理尝试次数}
                            {--batch-size=500 : 批处理大小}';

    /**
     * @var string
     */
    protected $description = '将 customer/customer_clue 的 area_cascade、customer_label 规整为标准 JSON，空值写入 NULL';

    /**
     * 需要处理的表字段.
     */
    private const FIELD_MAP = [
        'customer'      => ['area_cascade', 'customer_label'],
        'customer_clue' => ['area_cascade', 'customer_label'],
    ];

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        $database = $this->option('db') ?: config('database.default');
        $success  = true;

        foreach (self::FIELD_MAP as $table => $fields) {
            if (! Schema::connection($database)->hasTable($table)) {
                $this->warn("数据表 [{$table}] 不存在，已跳过");
                continue;
            }

            foreach ($fields as $field) {
                if (! Schema::connection($database)->hasColumn($table, $field)) {
                    $this->warn("字段 [{$table}.{$field}] 不存在，已跳过");
                    continue;
                }

                $this->info("开始处理 [{$table}.{$field}]");
                $params = [
                    '--db'           => $database,
                    '--table'        => $table,
                    '--field'        => $field,
                    '--dry-run'      => (bool) $this->option('dry-run'),
                    '--force'        => (bool) $this->option('force'),
                    '--reset'        => (bool) $this->option('reset'),
                    '--max-attempts' => (int) $this->option('max-attempts'),
                    '--batch-size'   => (int) $this->option('batch-size'),
                ];

                $exitCode = Artisan::call('fix:to-json', $params);
                $output = trim(Artisan::output());
                if ($output !== '') {
                    $this->line($output);
                }

                if ($exitCode !== self::SUCCESS) {
                    $success = false;
                    $this->warn("字段 [{$table}.{$field}] 处理失败，退出码: {$exitCode}");
                }
            }
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
