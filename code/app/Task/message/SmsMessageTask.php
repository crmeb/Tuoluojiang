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

namespace App\Task\message;

use Tuoluojiang\Open\services\SmsService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 新版短信消息发送
 * Class SmsMessageSendJob.
 */
class SmsMessageTask extends Task
{
    public function __construct(protected $phone, protected $entId, protected $templateCode, protected $message, protected $var) {}

    public function handle()
    {
        try {
            $smsMake = app()->get(SmsService::class);
            $result  = $smsMake->send($this->phone, $this->templateCode, $this->var);
            if ($result && isset($result['status']) && $result['status'] != 200) {
                Log::error('短信发送' . $result['msg'], $result);
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
