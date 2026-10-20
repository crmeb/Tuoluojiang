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

namespace Tuoluojiang\Open\traits;

use App\Constants\ModuleEnum;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\System\ModulePermissionService;

/**
 * 注册额外搜索条件.
 */
trait SearchTrait
{
    public array $uids = [];

    /**
     * 全部.
     */
    protected string $all = 'all';

    /**
     * 仅本人.
     */
    protected string $self = 'self';

    /**
     * 本部门(含无限下级).
     */
    protected string $department = 'dep';

    /**
     * 直属下级.
     */
    protected string $subordinate = 'sub';

    /**
     * 本人+直属下级.
     */
    protected string $team = 'team';

    public function withScopeFrame($key = 'uid', int $crudId = 0, int $crudRoleType = 1, bool $normal = true, string $scopeFrame = '', string $module = '')
    {
        $frameIds   = $scopeFrame ?: app('request')->input('scope_frame', 'all');
        $normal     = $normal && app('request')->input('scope_normal', '1');
        $searchUid  = app('request')->input($key, []);
        $userId     = auth('admin')->id();
        $isReport   = $module === ModuleEnum::REPORT;
        $normalUids = fn () => $isReport
            ? app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id')
            : app()->get(AdminService::class)->column(['status' => 1], 'id');
        $roleUids = $module && ModuleEnum::isValid($module) && ! auth('admin')->user()->is_admin
            ? app(ModulePermissionService::class)->getAccessibleUserIds($userId, $module, (bool) $normal)
            : ($normal ? $normalUids() : app()->get(AdminService::class)->column([], 'id'));
        $frameAssist = app()->get(FrameAssistService::class);
        switch ($frameIds) {
            case $this->self:
                $uid = [$userId];
                break;
            case $this->department:
                $info = $frameAssist->setTrashed(! $normal)->get(['user_id' => $userId, 'is_mastart' => 1], ['frame_id', 'is_admin', 'entid']);
                if ($info['is_admin']) {
                    $uid = app()->get(FrameService::class)->scopeUser((int) $info['frame_id'], $normal);
                } else {
                    $uid = $frameAssist->setTrashed(! $normal)->column(['frame_id' => $info['frame_id'], 'is_mastart' => 1, 'is_admin' => 0], 'user_id');
                }
                $uid = array_intersect($uid, $roleUids);
                break;
            case $this->subordinate:
                $uid = $frameAssist->getSubUid($userId, $normal);
                $uid = array_intersect($uid, $roleUids);
                break;
            case $this->team:
                $uid = array_merge($frameAssist->getSubUid($userId, $normal), [$userId]);
                $uid = array_intersect($uid, $roleUids);
                break;
            case $this->all:
                $uid = $roleUids;
                break;
            default:
                $frameId  = app()->get(FrameService::class)->scopeFrames((int) $frameIds);
                $frameUid = $frameAssist->setTrashed(! $normal)->column(['frame_id' => $frameId, 'is_mastart' => 1], 'user_id');
                $uid      = array_intersect($frameUid, $roleUids);
        }
        if ($normal) {
            $uid = array_intersect($uid, $normalUids());
        }
        if (in_array($userId, array_map('intval', $roleUids), true)) {
            $uid[] = $userId;
        }
        $uid = array_values(array_unique(array_filter(array_map('intval', $uid))));
        if ($searchUid) {
            $searchUid = array_intersect(is_array($searchUid) ? $searchUid : [$searchUid], $uid);
            app('request')->merge([
                $key => $searchUid,
            ]);
        } else {
            app('request')->merge([
                $key => $uid,
            ]);
        }
    }
}
