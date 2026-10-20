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

namespace App\Http\Service\Package;

use App\Http\Contract\Package\HelpCenterInterface;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\synchro\HelpCenter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 帮助中心.
 */
class HelpCenterService extends BaseService implements HelpCenterInterface
{
    /**
     * 结果页搜索.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function aggregateSearch(array $where): mixed
    {
        return app()->get(HelpCenter::class)->aggregateSearch($where);
    }

    /**
     * 侧边栏搜索.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function sidebarSearch(array $where): mixed
    {
        return app()->get(HelpCenter::class)->sidebarSearch($where);
    }
}
