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

namespace Tuoluojiang\Open\services\uniPush\options;

use Tuoluojiang\Open\services\uniPush\helper\Str;
use Tuoluojiang\Open\services\uniPush\OptionsBase;

/**
 * Class IosOptions.
 */
class IosOptions extends OptionsBase
{
    public $title;

    public $body;

    public $payload;

    public $autoBadge;

    /**
     * @return array
     */
    public function toArray()
    {
        $publicData = get_object_vars($this);
        $data       = [];
        foreach ($publicData as $key => $value) {
            $data[Str::snake($key)] = $value;
        }

        return [
            'type' => 'notify',
            'aps'  => [
                'alert' => $data,
                'sound' => 'sound',
            ],
            'payload'    => json_encode($this->payload),
            'auto_badge' => $this->autoBadge,
        ];
    }
}
