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
 * Class PushOptions.
 */
class PushOptions extends OptionsBase
{
    /**
     * @var string
     */
    public $requestId;

    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var array
     */
    public $audience = [];

    /**
     * @var array
     */
    public $pushMessage = [];

    /**
     * @var array
     */
    public $pushChannel = [];

    /**
     * PushOptions constructor.
     */
    public function __construct(string $requestId = '', array $settings = [], array $audience = [], array $pushMessage = [])
    {
        $this->requestId   = $requestId;
        $this->settings    = $settings;
        $this->audience    = $audience;
        $this->pushMessage = $pushMessage;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $publicData = get_object_vars($this);
        $data       = [];
        foreach ($publicData as $key => $value) {
            if ($value) {
                $data[Str::snake($key)] = $value;
            }
        }
        return $data;
    }

    /**
     * @return $this
     */
    public function setPushMessage(PushMessageOptions $options)
    {
        $this->pushMessage = $options->toArray();
        return $this;
    }

    public function setPushChannel(AndroidOptions $androidOptions, IosOptions $iosOptions)
    {
        return [
            'ios'     => $iosOptions->toArray(),
            'android' => $androidOptions->toArray(),
        ];
    }

    /**
     * @param mixed ...$clientId
     * @return $this
     */
    public function setAudience(...$clientId)
    {
        $this->audience = ['cid' => $clientId];
        return $this;
    }
}
