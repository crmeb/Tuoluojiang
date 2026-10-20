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

namespace App\Http\Service\Report;

use App\Http\Dao\Report\UserDailyReplyDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 汇报评论.
 */
class ReportReplyService extends BaseService
{
    public function __construct(UserDailyReplyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 删除回复.
     * @return null|bool|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteReply(int $id, int $dailyId, string $uid, int $entid)
    {
        $replyInfo = $this->dao->get(['id' => $id, 'daily_id' => $dailyId, 'uid' => $uid]);
        if (! $replyInfo) {
            throw $this->exception('删除失败');
        }
        return $this->dao->delete(['id' => $id]);
    }
}
