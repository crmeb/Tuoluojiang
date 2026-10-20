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

namespace Tuoluojiang\Open\interfaces;

/**
 * 配置基础类
 * Interface ConfigInterface.
 */
interface ConfigInterface
{
    /**
     * 获取单个配置.
     * @return mixed
     */
    public function getConfig(string $name);

    /**
     * 获取多个配置.
     */
    public function getConfigs(array $name): array;

    /**
     * 获取配置分页的配置.
     * @return mixed
     */
    public function getConfigLimit(string $name, int $limit = 0, int $entid = 0, int $page = 0);
}
