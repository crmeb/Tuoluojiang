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

namespace App\Http\Service\Ai\Agent\Middleware;

use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiConversation;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\Agent\AgentConfig;
use App\Http\Service\Ai\AiStreamService;
use App\Http\Service\Ai\AiToolCallService;

/**
 * Agent 流式前置中间件共享上下文（T-3）.
 */
final class AgentStreamContext
{
    public int $conversationId;

    public string $question;

    public bool $saveUserMessage;

    public int $historyBeforeId;

    public ?string $guestToken;

    public bool $isGuest = false;

    public ?AiConversation $conversation = null;

    public ?AiApplication $app = null;

    public ?AiModel $model = null;

    public ?AgentConfig $agentConfig = null;

    public bool $quotaAcquired = false;

    public ?AiStreamService $streamService = null;

    public ?AiToolCallService $toolService = null;

    /** @var array<string, mixed> */
    public array $bag = [];

    public function __construct(
        int $conversationId,
        string $question,
        bool $saveUserMessage = true,
        int $historyBeforeId = 0,
        ?string $guestToken = null
    ) {
        $this->conversationId  = $conversationId;
        $this->question        = $question;
        $this->saveUserMessage = $saveUserMessage;
        $this->historyBeforeId = $historyBeforeId;
        $this->guestToken      = $guestToken;
    }
}
