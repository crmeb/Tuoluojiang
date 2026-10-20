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

namespace App\Http\Service\Ai\Guest;

use App\Constants\Ai\AiConversationEnum;
use App\Http\Dao\Ai\AiApplicationDao;
use App\Http\Model\Ai\AiConversation;
use App\Http\Model\Ai\AiConversationMessage;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * 外部 AI 访客会话统计.
 *
 * 仅统计 app_type=EXTERNAL 且 session_type=GUEST 的会话与消息，供管理端 JSON 接口与 HTML 看板使用.
 */
class AiGuestStatsService extends BaseService
{
    public function __construct(private AiApplicationDao $appDao)
    {
    }

    /**
     * 访客会话统计概览（含日趋势）.
     *
     * @param int $appId 外部 AI 应用 ID
     * @param int $days  统计天数，自动限制在 1~90
     *
     * @return array<string, mixed> 含 total_conversations、unique_guests、消息/Token 指标及 daily 数组
     */
    public function overview(int $appId, int $days = 7): array
    {
        $this->assertExternalApp($appId);
        $days      = max(1, min(90, $days));
        $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $conversationQuery = AiConversation::query()
            ->where('chat_application_id', $appId)
            ->where('session_type', AiConversationEnum::SESSION_TYPE_GUEST);

        $totalConversations = (int) (clone $conversationQuery)->count();
        $uniqueGuests       = (int) (clone $conversationQuery)
            ->where('guest_token', '<>', '')
            ->distinct()
            ->count('guest_token');

        $todayConversations = (int) (clone $conversationQuery)
            ->whereDate('created_at', date('Y-m-d'))
            ->count();

        $messageStats = $this->messageAggregate($appId, $startDate);

        return [
            'app_id'              => $appId,
            'days'                => $days,
            'total_conversations' => $totalConversations,
            'unique_guests'       => $uniqueGuests,
            'today_conversations' => $todayConversations,
            'total_messages'      => (int) ($messageStats['total_messages'] ?? 0),
            'today_messages'      => (int) ($messageStats['today_messages'] ?? 0),
            'total_tokens'        => (int) ($messageStats['total_tokens'] ?? 0),
            'period_messages'     => (int) ($messageStats['period_messages'] ?? 0),
            'period_tokens'       => (int) ($messageStats['period_tokens'] ?? 0),
            'daily'               => $this->dailyTrend($appId, $days),
        ];
    }

    /**
     * 按日汇总访客会话与消息指标.
     *
     * @param int $appId 外部 AI 应用 ID
     * @param int $days  统计天数，自动限制在 1~90
     *
     * @return array<int, array<string, mixed>> 按日期升序，含 date/conversations/unique_guests/messages/tokens
     */
    public function dailyTrend(int $appId, int $days = 7): array
    {
        $this->assertExternalApp($appId);
        $days      = max(1, min(90, $days));
        $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $conversationRows = AiConversation::query()
            ->selectRaw('DATE(created_at) as stat_date, COUNT(*) as conversations, COUNT(DISTINCT guest_token) as unique_guests')
            ->where('chat_application_id', $appId)
            ->where('session_type', AiConversationEnum::SESSION_TYPE_GUEST)
            ->whereDate('created_at', '>=', $startDate)
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get()
            ->keyBy('stat_date');

        $messageRows = $this->messageDailyRows($appId, $startDate);

        $result = [];
        for ($i = $days - 1; $i >= 0; --$i) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $conv = $conversationRows->get($date);
            $msg  = $messageRows[$date] ?? ['messages' => 0, 'tokens' => 0];

            $result[] = [
                'date'          => $date,
                'conversations' => (int) ($conv->conversations ?? 0),
                'unique_guests' => (int) ($conv->unique_guests ?? 0),
                'messages'      => (int) ($msg['messages'] ?? 0),
                'tokens'        => (int) ($msg['tokens'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * 校验应用存在且为外部应用.
     *
     * @throws \Throwable 应用不存在或非外部应用
     */
    private function assertExternalApp(int $appId): void
    {
        $app = $this->appDao->get($appId);
        if (! $app) {
            throw $this->exception('AI应用不存在');
        }
        if ((int) $app->app_type !== AiConversationEnum::APP_TYPE_EXTERNAL) {
            throw $this->exception('仅外部应用支持访客统计');
        }
    }

    /**
     * 聚合访客消息总量、区间量与今日量.
     *
     * @return array<string, int>
     */
    private function messageAggregate(int $appId, string $startDate): array
    {
        $historyTable = (new AiConversation())->getTable();
        $recordTable  = (new AiConversationMessage())->getTable();

        $base = DB::table($recordTable . ' as r')
            ->join($historyTable . ' as h', 'h.id', '=', 'r.chat_history_id')
            ->where('h.chat_application_id', $appId)
            ->where('h.session_type', AiConversationEnum::SESSION_TYPE_GUEST)
            ->where('r.problem_text', '<>', '');

        $total = (clone $base)->count();
        $tokens = (int) (clone $base)->sum('r.tokens');

        $period = (clone $base)->whereDate('r.created_at', '>=', $startDate);
        $periodMessages = (int) (clone $period)->count();
        $periodTokens   = (int) (clone $period)->sum('r.tokens');

        $today = (clone $base)->whereDate('r.created_at', date('Y-m-d'));

        return [
            'total_messages'  => $total,
            'total_tokens'    => $tokens,
            'period_messages' => $periodMessages,
            'period_tokens'   => $periodTokens,
            'today_messages'  => (int) $today->count(),
        ];
    }

    /**
     * 按日统计访客消息数与 Token.
     *
     * @return array<string, array{messages: int, tokens: int}>
     */
    private function messageDailyRows(int $appId, string $startDate): array
    {
        $historyTable = (new AiConversation())->getTable();
        $recordTable  = (new AiConversationMessage())->getTable();

        $rows = DB::table($recordTable . ' as r')
            ->join($historyTable . ' as h', 'h.id', '=', 'r.chat_history_id')
            ->selectRaw('DATE(r.created_at) as stat_date, COUNT(*) as messages, COALESCE(SUM(r.tokens), 0) as tokens')
            ->where('h.chat_application_id', $appId)
            ->where('h.session_type', AiConversationEnum::SESSION_TYPE_GUEST)
            ->where('r.problem_text', '<>', '')
            ->whereDate('r.created_at', '>=', $startDate)
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->stat_date] = [
                'messages' => (int) $row->messages,
                'tokens'   => (int) $row->tokens,
            ];
        }

        return $map;
    }
}
