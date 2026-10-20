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

namespace App\Http\Service\Notice;

use App\Http\Dao\Notice\NoticeVisitDao;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 通知阅读记录
 * Class NoticeVisitService.
 */
class NoticeVisitService extends BaseService
{
    /**
     * NoticeVisitService constructor.
     */
    public function __construct(NoticeVisitDao $dao)
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
        $userId = app()->get(FrameService::class)->uuidToUid($uuid, $entid);
        $save   = ['user_id' => $userId, 'notice_id' => $notice_id];
        return $this->dao->firstOrCreate($save, $save);
    }
}
