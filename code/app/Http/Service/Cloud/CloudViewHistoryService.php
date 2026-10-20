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

namespace App\Http\Service\Cloud;

use App\Http\Dao\Cloud\CloudViewHistoryDao;
use App\Http\Service\BaseService;

/**
 * 文件浏览历史.
 * @mixin CloudViewHistoryDao
 */
class CloudViewHistoryService extends BaseService
{
    public function __construct(CloudViewHistoryDao $dao)
    {
        $this->dao = $dao;
    }
}
