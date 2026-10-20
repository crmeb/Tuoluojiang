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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 修复 admin_info 表的 letter 字段.
 * - 汉字姓名：取姓氏拼音首字母（大写）
 * - 纯字母姓名：取首字母（大写）
 * - 非汉字非字母：返回 #
 */
class FixAdminInfoLetter extends Command
{
    /**
     * 命令签名.
     *
     * @var string
     */
    protected $signature = 'fix:admin-letter
                            {--dry-run : 测试模式，不实际更新数据}
                            {--batch-size=500 : 批处理大小}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '修复 admin_info 表的 letter 字段（姓名拼音首字母）';

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        if ($isDryRun) {
            $this->warn('【Dry Run 模式】此操作不会实际更新数据库');
        }

        $this->info('开始修复 admin_info 表的 letter 字段...');

        if (! Schema::hasTable('admin_info') || ! Schema::hasColumn('admin_info', 'letter')) {
            $this->warn('admin_info 表或 letter 字段不存在，已跳过');
            return self::SUCCESS;
        }

        $total = DB::table('admin_info')->count();
        $this->info("共 {$total} 条记录待处理");

        if ($total === 0) {
            $this->info('没有需要处理的记录');
            return self::SUCCESS;
        }

        $processed = 0;
        $updated = 0;
        $errors = 0;

        DB::table('admin_info')
            ->join('admin', 'admin.uid', '=', 'admin_info.uid')
            ->whereNotNull('admin.name')
            ->where('admin.name', '!=', '')
            ->orderBy('admin_info.id')
            ->select('admin_info.id', 'admin_info.letter', 'admin.name')
            ->chunk($batchSize, function ($records) use (&$processed, &$updated, &$errors, $isDryRun) {
                foreach ($records as $record) {
                    $processed++;
                    $correctLetter = $this->calculateLetter($record->name);

                    // 检测是否需要更新（非空且与当前值不同）
                    if ($record->letter !== $correctLetter) {
                        $changeDesc = "{$record->name}: '{$record->letter}' -> '{$correctLetter}'";
                        if ($isDryRun) {
                            $this->line("  [DRY] {$changeDesc}");
                        } else {
                            DB::table('admin_info')
                                ->where('id', $record->id)
                                ->update(['letter' => $correctLetter]);
                            $this->line("  [更新] {$changeDesc}");
                        }
                        $updated++;
                    }
                }

                $this->info("已处理 {$processed} 条记录...");
            });

        $this->newLine();
        $this->info('========== 修复完成 ==========');
        $this->info("总记录数: {$total}");
        $this->info("已处理: {$processed}");
        $this->info("已更新: {$updated}");

        if ($errors > 0) {
            $this->error("错误数: {$errors}");
        }

        if ($isDryRun) {
            $this->warn('这是 Dry Run 模式，实际数据未更新');
            $this->info('移除 --dry-run 选项来执行实际更新');
        }

        return self::SUCCESS;
    }

    /**
     * 计算姓名的字母索引.
     *
     * @param string $name 姓名
     * @return string 字母索引
     */
    private function calculateLetter(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '#';
        }

        // 检测是否包含汉字
        $hasChinese = preg_match('/[\x{4e00}-\x{9fa5}]/u', $name);

        if ($hasChinese) {
            // 汉字姓名：使用拼音库获取姓氏首字母
            $pinyin = \Overtrue\Pinyin\Pinyin::nameAbbr($name);
            $letter = $pinyin[0] ?? '';
            if ($letter !== '') {
                return strtoupper($letter);
            }
        }

        // 非汉字姓名：取首字符
        $firstChar = mb_substr($name, 0, 1, 'utf-8');

        // 检测首字符是否为字母（a-zA-Z）
        if (preg_match('/^[a-zA-Z]$/', $firstChar)) {
            return strtoupper($firstChar);
        }

        // 其他情况（符号等）返回 #
        return '#';
    }
}
