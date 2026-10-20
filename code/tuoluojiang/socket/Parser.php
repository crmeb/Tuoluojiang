<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\socket;

use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\Parser as ParserHandle;

class Parser extends ParserHandle
{
    /**
     * Encode output payload for websocket push.
     * @param mixed $data
     * @return mixed
     */
    public function encode(string $event, $data)
    {
        $string = ['event' => $event, 'data' => $data];
        return json_encode($string);
    }

    /**
     * Input message on websocket connected.
     * Define and return event name and payload data here.
     * @param Frame $frame
     * @return array
     */
    public function decode($frame)
    {
        // 这里是解析客户端发来的数据，我们约定所有的传输都是json
        $json = $frame->data;
        $data = json_decode($json, true);
        if (! $data || ! isset($data['event'])) {
            return ['event' => 'error', 'data' => $frame->data];
        }
        return ['event' => $data['event'], 'data' => $data['data'] ?? ''];
    }
}
