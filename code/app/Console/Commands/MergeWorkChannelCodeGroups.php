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

use App\Http\Dao\WorkExternalContact\WorkChannelCodeDao;
use App\Http\Dao\WorkExternalContact\WorkChannelCodeGroupDao;
use App\Http\Model\WorkExternalContact\WorkChannelCode;
use App\Http\Model\WorkExternalContact\WorkChannelCodeGroup;
use App\Http\Service\WorkExternalContact\WorkChannelCodeService;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 合并历史渠道活码客户群配置.
 */
class MergeWorkChannelCodeGroups extends Command
{
    protected $signature = 'work:channel-code-groups:merge {--keep-channel-id : 仅合并数据，不删除 work_channel_code_group.channel_id 字段}';

    protected $description = '将 work_channel_code_group 按历史 channel_id 合并为单条配置，并写回 work_channel_code.code_id';

    public function __construct(
        private WorkChannelCodeDao $channelDao,
        private WorkChannelCodeGroupDao $groupDao
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasColumn('work_channel_code_group', 'channel_id')) {
            $this->info('work_channel_code_group.channel_id 已不存在，无需处理');
            return self::SUCCESS;
        }
        if (! Schema::hasColumn('work_channel_code', 'code_id')) {
            $this->error('work_channel_code.code_id 不存在，请先执行结构迁移');
            return self::FAILURE;
        }
        if (! Schema::hasColumn('work_channel_code_group', 'chat_ids')) {
            $this->error('work_channel_code_group.chat_ids 不存在，请先执行 chat_id 到 chat_ids 的结构迁移');
            return self::FAILURE;
        }

        $groups = $this->groupDao->getModel()
            ->where('channel_id', '>', 0)
            ->orderBy('channel_id')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        foreach ($groups->groupBy('channel_id') as $channelId => $items) {
            if (! $this->mergeChannel((int) $channelId, $items)) {
                $this->warn('已停止处理，channel_id 字段未删除，可修正后重跑本命令');
                return self::FAILURE;
            }
        }

        if (! (bool) $this->option('keep-channel-id')) {
            Schema::table('work_channel_code_group', function (Blueprint $table) {
                $table->dropColumn('channel_id');
            });
            $this->info('已删除 work_channel_code_group.channel_id 字段');
        }

        $this->info('渠道活码客户群历史数据处理完成');
        return self::SUCCESS;
    }

    private function mergeChannel(int $channelId, Collection $items): bool
    {
        /** @var WorkChannelCode $channel */
        $channel = $this->channelDao->get($channelId);
        if (! $channel) {
            $this->groupDao->getModel()->whereIn('id', $items->pluck('id')->all())->delete();
            $this->warn("渠道活码 {$channelId} 不存在，已删除其历史群配置");
            return true;
        }

        $target       = $items->first();
        $targetId     = (int) $target->id;
        $deleteIds    = $items->pluck('id')->filter(fn ($id) => (int) $id !== $targetId)->values()->all();
        $chatIds      = $this->mergeChatIds($items);
        $oldConfigIds = $items->pluck('join_way_config_id')->filter()->unique()->values()->all();
        if (count($chatIds) > 5) {
            $this->error("渠道活码 {$channelId} 合并后群聊超过 5 个，请先拆分配置");
            return false;
        }
        if ((int) $channel->invite_group === 1 && ! $chatIds) {
            $this->error("渠道活码 {$channelId} 未找到可合并的企微群 ID");
            return false;
        }

        DB::transaction(function () use ($channelId, $targetId, $target, $chatIds) {
            $target->chat_ids           = $chatIds;
            $target->group_name         = (string) $target->group_name;
            $target->room_base_name     = (string) ($target->room_base_name ?: $target->group_name);
            $target->room_base_id       = max(1, (int) $target->room_base_id);
            $target->auto_create_room   = (int) $target->auto_create_room;
            $target->max_member_num     = max(1, (int) $target->max_member_num);
            $target->sort               = 0;
            $target->status             = (int) $target->status;
            $target->scene              = (int) ($target->scene ?: 2);
            $target->join_way_remark    = (string) $target->join_way_remark;
            $target->join_way_config_id = '';
            $target->qr_code            = '';
            $target->save();
            $this->channelDao->update($channelId, ['code_id' => $targetId]);
        });

        if ((int) $channel->invite_group === 1 && (int) $channel->status === 1 && $chatIds) {
            try {
                app()->get(WorkChannelCodeService::class)->syncJoinWays($channelId);
            } catch (\Throwable $e) {
                $this->error("渠道活码 {$channelId} 同步企微进群方式失败：" . $e->getMessage());
                return false;
            }
        }

        $newConfigId = (string) ($this->groupDao->get($targetId, ['join_way_config_id'])?->join_way_config_id ?? '');
        foreach ($oldConfigIds as $configId) {
            if ((string) $configId === $newConfigId) {
                continue;
            }
            try {
                app()->get(Work::class)->deleteGroupJoinWay((string) $configId);
            } catch (\Throwable $e) {
                Log::warning('删除历史客户群进群方式失败:' . $e->getMessage(), ['config_id' => $configId]);
            }
        }

        DB::transaction(function () use ($targetId, $deleteIds) {
            if ($deleteIds) {
                $this->groupDao->getModel()->whereIn('id', $deleteIds)->delete();
            }
            $this->groupDao->update($targetId, ['channel_id' => 0]);
        });

        $this->line("渠道活码 {$channelId} 已合并到邀请加群配置 {$targetId}");
        return true;
    }

    private function mergeChatIds(Collection $groups): array
    {
        $chatIds = [];
        foreach ($groups as $group) {
            foreach ($this->chatIds($group) as $chatId) {
                $chatIds[$chatId] = $chatId;
            }
        }
        return array_values($chatIds);
    }

    private function chatIds(WorkChannelCodeGroup $group): array
    {
        $chatIds = $group->chat_ids ?: [];
        if (is_string($chatIds)) {
            $decoded = json_decode($chatIds, true);
            $chatIds = json_last_error() === JSON_ERROR_NONE ? $decoded : [$chatIds];
        }
        if (! $chatIds && Schema::hasColumn('work_channel_code_group', 'chat_id')) {
            $chatIds = [$group->getAttribute('chat_id')];
        }
        return collect((array) $chatIds)->map(fn ($chatId) => trim((string) $chatId))->filter()->values()->all();
    }
}
