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

namespace Tuoluojiang\Open\services\synchro;

/**
 * Class HelpCenter.
 */
class HelpCenter extends TokenService
{
    protected string $cacheTokenPrefix = '';

    protected string $salt = '';

    protected string $serviceName = '';

    protected string $aggregateSearchApi = '/api/v2/help_center/aggregate_search';

    protected string $sidebarSearchApi = '/api/v2/help_center/sidebar_search';

    /**
     * 结果页搜索.
     */
    public function aggregateSearch(array $data): mixed
    {
        return $this->httpRequest($this->aggregateSearchApi, $data);
    }

    /**
     * 侧边栏搜索.
     */
    public function sidebarSearch(array $data): mixed
    {
        return $this->httpRequest($this->sidebarSearchApi, $data);
    }
}
