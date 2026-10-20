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

namespace Tuoluojiang\Open\swoole\laravels;

use Hhxsv5\LaravelS\Swoole\DynamicResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE/流式响应：跳过 gzip 与输出缓冲，write() 后立即下发.
 */
class StreamedDynamicResponse extends DynamicResponse
{
    public function send($gzip = false): void
    {
        if ($this->laravelResponse instanceof StreamedResponse) {
            $this->prepareStreamHeaders();
        }

        $this->sendStatusCode();
        $this->sendHeaders();
        $this->sendCookies();
        $this->sendTrailers();

        if ($this->laravelResponse instanceof StreamedResponse) {
            $this->sendStreamContent();

            return;
        }

        if ($gzip) {
            $this->gzip();
        }
        parent::sendContent();
    }

    private function prepareStreamHeaders(): void
    {
        $headers = $this->laravelResponse->headers;
        $headers->set('Content-Encoding', 'identity');
        $headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $headers->set('Connection', 'keep-alive');
        $headers->set('X-Accel-Buffering', 'no');
        $headers->remove('Content-Length');
    }

    private function sendStreamContent(): void
    {
        $this->laravelResponse->sendContent();
        if (method_exists($this->swooleResponse, 'isWritable') && ! $this->swooleResponse->isWritable()) {
            return;
        }
        $this->swooleResponse->end();
    }
}
