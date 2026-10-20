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

namespace Tuoluojiang\Open\services\wechat\config;

use Tuoluojiang\Open\services\wechat\contract\ConfigHandlerInterface;
use Tuoluojiang\Open\services\wechat\contract\ServeConfigInterface;
use Tuoluojiang\Open\services\wechat\DefaultConfig;

/**
 * Http请求配置
 * Class HttpCommonConfig.
 */
class HttpCommonConfig implements ConfigHandlerInterface
{
    /**
     * @var bool[]
     */
    protected array $config = [
        'verify'  => false,
        'timeout' => 5,
    ];

    protected string $serve;

    /**
     * @return $this
     */
    public function setServe(string $serve): static
    {
        $this->serve = $serve;
        return $this;
    }

    /**
     * 获取服务端实例.
     * @return ServeConfigInterface
     */
    public function getServe()
    {
        return app()->get($this->serve);
    }

    /**
     * 直接获取配置.
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        if ($value = DefaultConfig::value($key)) {
            return $value;
        }

        return $this->getServe()->getConfig(DefaultConfig::key($key), $default);
    }

    public function all(): array
    {
        return $this->config;
    }
}
