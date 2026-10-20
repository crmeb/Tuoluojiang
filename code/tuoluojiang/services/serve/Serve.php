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

namespace Tuoluojiang\Open\services\serve;

use Tuoluojiang\Open\services\BaseManager;
use Tuoluojiang\Open\services\AccessTokenServeService;
use Tuoluojiang\Open\services\serve\storage\Crmeb;
use Illuminate\Support\Facades\Config;

/**
 * Class Serve.
 * @mixin Crmeb
 */
class Serve extends BaseManager
{
    /**
     * 空间名.
     * @var string
     */
    protected $namespace = '\\Tuoluojiang\Open\\services\\serve\\storage\\';

    /**
     * 默认驱动.
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('serve.default', 'crmeb');
    }

    /**
     * 获取类的实例.
     * @return mixed|void
     */
    protected function invokeClass($class)
    {
        if (! class_exists($class)) {
            throw new \RuntimeException('class not exists: ' . $class);
        }
        $this->getConfigFile();

        if (! $this->config) {
            $this->config = Config::get($this->configFile . '.stores.' . $this->name, []);
        }

        $handleAccessToken = new AccessTokenServeService($this->config['account'] ?? '', $this->config['secret'] ?? '');
        $handle            = $this->make($class, [$this->name, $handleAccessToken, $this->configFile]);
        $this->config      = [];
        return $handle;
    }
}
