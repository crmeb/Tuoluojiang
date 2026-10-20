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

namespace App\Http\Service\News;

use App\Http\Dao\News\NewsVisitDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 阅读记录.
 */
class NewsVisitService extends BaseService
{
    public function __construct(NewsVisitDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存访问记录.
     * @param mixed $entid
     * @return bool
     * @throws BindingResolutionException
     */
    public function saveVisit($notice_id, $uuid, $entid)
    {
        $save = ['user_id' => uuid_to_uid($uuid, $entid), 'notice_id' => $notice_id];
        return $this->dao->firstOrCreate($save, $save);
    }
}
