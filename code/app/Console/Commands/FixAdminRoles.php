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

use App\Constants\CacheEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 修复 admin 表 roles 字段中的无效角色ID.
 */
class FixAdminRoles extends Command
{
    /**
     * 命令签名.
     *
     * @var string
     */
    protected $signature = 'fix:admin-roles
                            {--id=* : 指定需要修复的用户ID，可传多个}
                            {--dry-run : 测试模式，不实际更新数据}
                            {--batch-size=500 : 批处理大小}
                            {--remove-missing : 同时移除 enterprise_role 表中不存在的角色ID}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '修复 admin.roles 字段，移除 0、空值、非法值和重复角色ID';

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        $isDryRun      = (bool) $this->option('dry-run');
        $batchSize     = max(10, min(1000, (int) $this->option('batch-size')));
        $removeMissing = (bool) $this->option('remove-missing');
        $ids           = array_values(array_filter(array_map('intval', (array) $this->option('id'))));

        if ($isDryRun) {
            $this->warn('【Dry Run 模式】此操作不会实际更新数据库');
        }
        if ($removeMissing) {
            $this->warn('已开启 --remove-missing，将移除 enterprise_role 表中不存在的角色ID');
        }
        if (! Schema::hasTable('admin') || ! Schema::hasColumn('admin', 'roles')) {
            $this->warn('admin 表或 roles 字段不存在，已跳过');
            return self::SUCCESS;
        }
        if ($removeMissing && ! Schema::hasTable('enterprise_role')) {
            $this->warn('enterprise_role 表不存在，已跳过不存在角色ID校验');
            $removeMissing = false;
        }
        $validRoleIds = $removeMissing ? DB::table('enterprise_role')->pluck('id')->map(fn ($id) => (int) $id)->all() : [];

        $query = DB::table('admin')
            ->select(['id', 'roles'])
            ->whereNotNull('roles')
            ->where('roles', '!=', '')
            ->orderBy('id');

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $total = (clone $query)->count();
        $this->info("共 {$total} 条用户角色记录待检查");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $processed = 0;
        $updated   = 0;

        $query->chunkById($batchSize, function ($records) use (&$processed, &$updated, $isDryRun, $removeMissing, $validRoleIds) {
            foreach ($records as $record) {
                ++$processed;

                $fixed = $this->formatRoles($record->roles, $removeMissing ? $validRoleIds : null);
                if ($fixed === $record->roles) {
                    continue;
                }

                ++$updated;
                $this->line("用户ID {$record->id}: {$record->roles} -> {$fixed}");

                if (! $isDryRun) {
                    DB::table('admin')->where('id', $record->id)->update(['roles' => $fixed]);
                }
            }

            $this->info("已检查 {$processed} 条记录...");
        });

        if (! $isDryRun) {
            Cache::tags([CacheEnum::TAG_ROLE])->flush();
        }

        $this->newLine();
        $this->info('========== 修复完成 ==========');
        $this->info("已检查: {$processed}");
        $this->info("需更新: {$updated}");
        if ($isDryRun) {
            $this->warn('这是 Dry Run 模式，实际数据未更新');
            $this->info('移除 --dry-run 选项来执行实际更新');
        }

        return self::SUCCESS;
    }

    /**
     * 格式化 roles 字段.
     */
    private function formatRoles(string $value, ?array $validRoleIds = null): string
    {
        $roles = $this->decodeRoles($value);
        $roles = array_values(array_reduce($roles, function (array $carry, $roleId) use ($validRoleIds) {
            $roleId = $this->parseRoleId($roleId);
            if (! $roleId || in_array($roleId, $carry, true)) {
                return $carry;
            }
            if ($validRoleIds !== null && ! in_array($roleId, $validRoleIds, true)) {
                return $carry;
            }
            $carry[] = $roleId;
            return $carry;
        }, []));

        return $roles ? json_encode($roles) : '';
    }

    /**
     * 解码 roles 字段，兼容普通 JSON 与双重 JSON 编码.
     */
    private function decodeRoles(string $value): array
    {
        $decoded = json_decode($value, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 解析角色ID，仅接受正整数或纯数字字符串.
     *
     * @param mixed $roleId
     */
    private function parseRoleId($roleId): int
    {
        if (is_int($roleId)) {
            return $roleId > 0 ? $roleId : 0;
        }

        if (is_string($roleId) && ctype_digit(trim($roleId))) {
            return (int) $roleId;
        }

        return 0;
    }
}
