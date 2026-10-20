<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 处理过度转义的数据.
 */
class FixOverEscapedData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fix:over-escaped
                            {--db= : 数据库名称（默认使用配置中的默认数据库）}
                            {--table= : 表名称（必填）}
                            {--field= : 字段名称（必填）}
                            {--dry-run : 测试模式，不实际更新数据}';

    protected $description = '修复数据库中过度转义的数据，支持手动指定数据库、表和字段';

    public function handle()
    {
        $database = $this->option('db') ?: config('database.default');
        $table    = $this->option('table');
        $field    = $this->option('field');
        $dryRun   = $this->option('dry-run');

        if (empty($table) || empty($field)) {
            $this->error('请指定表名（--table）和字段名（--field）！');
            return 1;
        }

        $this->warn("⚠️ 即将修复数据库 [{$database}] 表 [{$table}] 中的字段 [{$field}]");
        $this->warn('⚠️ 请确保已备份数据！执行过程不可逆！');
        $confirm = $this->confirm('是否继续？', true);
        if (! $confirm) {
            $this->info('已取消操作');
            return 0;
        }

        // 构建查询时添加 orderBy（按主键 id 排序，解决 chunk 报错）
        $query = DB::connection($database)->table($table)
            ->whereNotNull($field)
            ->where(function ($q) use ($field) {
                $q->where($field, 'like', '%&amp;%')
                    ->orWhere($field, 'like', '%\\\%')
                    ->orWhere($field, 'like', '%&quot;%');
            })
            ->orderBy('id'); // 关键修复：添加排序，按主键 id 升序

        $total = $query->count();

        if ($total === 0) {
            $this->info('未找到需要修复的记录.');
            return 0;
        }
        $this->info("共找到 {$total} 条可能需要修复的记录，开始处理...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // chunk 方法现在会按 orderBy 排序分批处理
        $query->chunk(100, function ($records) use ($field, $dryRun, $bar, $table) {
            foreach ($records as $record) {
                $id       = $record->id;
                $original = $record->{$field};
                $fixed    = $this->restoreEscapedData($original);

                if ($fixed !== $original) {
                    if (! $dryRun) {
                        DB::table($table)
                            ->where('id', $id)
                            ->update([$field => $fixed]);
                    }
                    $this->line("\nID: {$id} 已修复（原始值: " . substr($original, 0, 50) . '...）');
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info("\n处理完成！" . ($dryRun ? '（测试模式，未实际更新）' : ''));
        return 0;
    }

    protected function restoreEscapedData(string $data): string
    {
        $prev    = $data;
        $changed = true;

        // 循环执行反转义，直到数据不再变化（无冗余转义）
        while ($changed) {
            // 步骤1：HTML实体反转义（处理 &amp; → &、&quot; → " 等）
            $current = htmlspecialchars_decode($prev, ENT_QUOTES);

            // 步骤2：JSON反转义（处理 \\ → \、\" → " 等）
            $current = json_decode('"' . $current . '"', true) ?? $current;

            // 检查是否还有变化
            if ($current === $prev) {
                $changed = false;
            } else {
                $prev = $current;
            }
        }
        return str_replace('""', '', $prev);
    }
}
