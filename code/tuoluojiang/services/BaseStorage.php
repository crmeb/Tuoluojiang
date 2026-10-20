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

namespace Tuoluojiang\Open\services;

use Tuoluojiang\Open\traits\ErrorTrait;

/**
 * Class BaseStorage.
 */
abstract class BaseStorage
{
    use ErrorTrait;

    /**
     * 驱动名称.
     */
    protected ?string $name;

    /**
     * 驱动配置文件名.
     */
    protected ?string $configFile;

    /**
     * BaseStorage constructor.
     * @param null|string $name 驱动名
     * @param array $config 其他配置
     * @param null|string $configFile 驱动配置名
     */
    public function __construct(?string $name = null, array $config = [], ?string $configFile = null)
    {
        $this->name       = $name;
        $this->configFile = $configFile;
        $this->initialize($config);
    }

    /**
     * 初始化.
     * @return mixed
     */
    abstract protected function initialize(array $config);
}
