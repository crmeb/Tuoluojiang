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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 为旧客户订单、商机数据补齐系统编号.
 */
class GenerateCustomerSystemNumbers extends Command
{
    /**
     * 命令签名.
     *
     * @var string
     */
    protected $signature = 'customer:generate-system-numbers
                            {--type=all : 处理类型：all、contract、odds}
                            {--chunk=500 : 每批处理数量}
                            {--dry-run : 仅统计待处理数据，不实际更新}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '为没有订单编号、商机编号的旧客户数据生成系统编号';

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        $type = (string) $this->option('type');
        $chunk = max(1, (int) $this->option('chunk'));
        $isDryRun = (bool) $this->option('dry-run');

        if (! in_array($type, ['all', 'contract', 'odds'], true)) {
            $this->error('type 只能是 all、contract、odds');
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('【Dry Run 模式】此操作不会实际更新数据库');
        }

        $total = 0;
        if (in_array($type, ['all', 'contract'], true)) {
            $total += $this->fillMissingNumbers('contract', 'contract_no', 'DD', '订单', $chunk, $isDryRun);
        }

        if (in_array($type, ['all', 'odds'], true)) {
            $total += $this->fillMissingNumbers('customer_odds', 'odds_no', 'SJ', '商机', $chunk, $isDryRun);
        }

        $this->newLine();
        $this->info(($isDryRun ? '待补齐' : '已补齐') . "数据共 {$total} 条");

        return self::SUCCESS;
    }

    /**
     * 分批补齐指定表的系统编号.
     */
    private function fillMissingNumbers(string $table, string $field, string $prefix, string $label, int $chunk, bool $isDryRun): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $field)) {
            $this->warn("{$label}跳过：{$table}.{$field} 不存在");
            return 0;
        }

        $query = $this->missingNumberQuery($table, $field);
        $count = (clone $query)->count();

        if ($isDryRun) {
            $this->info("{$label}待补齐：{$count} 条");
            return $count;
        }

        if ($count === 0) {
            $this->info("{$label}没有需要补齐的数据");
            return 0;
        }

        $updated = 0;
        $query->select(['id'])->chunkById($chunk, function ($records) use ($table, $field, $prefix, &$updated) {
            foreach ($records as $record) {
                $affected = $this->missingNumberQuery($table, $field)
                    ->where('id', $record->id)
                    ->update([
                        $field => $this->makeUniqueNo($table, $field, $prefix),
                    ]);

                $updated += $affected;
            }
        });

        $this->info("{$label}已补齐：{$updated} 条");
        return $updated;
    }

    /**
     * 缺失系统编号的数据查询.
     */
    private function missingNumberQuery(string $table, string $field): Builder
    {
        return DB::table($table)->where(function (Builder $query) use ($field) {
            $query->whereNull($field)->orWhere($field, '');
        });
    }

    /**
     * 生成并校验唯一系统编号.
     */
    private function makeUniqueNo(string $table, string $field, string $prefix): string
    {
        do {
            $microtime = microtime(true);
            $timePart = date('mdHis', (int) $microtime) . sprintf('%02d', (int) (fmod($microtime, 1) * 100));
            $randomPart = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $number = $prefix . $timePart . $randomPart;
        } while (DB::table($table)->where($field, $number)->exists());

        return $number;
    }
}
