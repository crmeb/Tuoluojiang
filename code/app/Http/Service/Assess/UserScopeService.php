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

namespace App\Http\Service\Assess;

use App\Http\Dao\Frame\FrameUserScopeDao;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 用户管理范围
 * Class UserScopeService.
 */
class UserScopeService extends BaseService
{
    /**
     * UserScopeService constructor.
     */
    public function __construct(FrameUserScopeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存管理范围.
     * @param array $frames
     * @param array $users
     * @param mixed $rules
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function saveUserScope($uid, $entid, $frames = [], $users = [], $rules = []): void
    {
        $this->dao->delete(['uid' => $uid, 'entid' => $entid, 'types' => 0]);
        if ($frames) {
            foreach ($frames as $val) {
                $this->dao->create([
                    'uid'     => $uid,
                    'entid'   => $entid,
                    'link_id' => $val,
                    'types'   => 0,
                ]);
            }
        }
        $this->dao->delete(['uid' => $uid, 'entid' => $entid, 'types' => 1]);
        if ($users) {
            foreach ($users as $value) {
                $this->dao->create([
                    'uid'     => $uid,
                    'entid'   => $entid,
                    'link_id' => $value,
                    'types'   => 1,
                ]);
            }
        }
    }

    public function getUserScope($card_id)
    {
        return $this->dao->select(['uid' => $card_id], ['*'], ['frames', 'cards']);
    }

    /**
     * 用户管理范围部门ID.
     * @param mixed $uuid
     * @param mixed $entId
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserScopeFrame($uuid, $entId)
    {
        $cardId = app()->get(FrameService::class)->uuidToCardid($uuid, $entId);
        return $this->dao->column(['uid' => $cardId], 'link_id');
    }
}
