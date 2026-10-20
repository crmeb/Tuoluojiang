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

namespace App\Http\Service\Attendance;

use App\Constants\AttendanceGroupEnum;
use App\Constants\CacheEnum;
use App\Constants\ModuleEnum;
use App\Http\Contract\Attendance\AttendanceGroupInterface;
use App\Http\Dao\Attendance\AttendanceGroupDao;
use App\Http\Dao\Attendance\AttendanceGroupMemberDao;
use App\Http\Dao\Attendance\AttendanceGroupShiftDao;
use App\Http\Dao\Attendance\AttendanceWhitelistDao;
use App\Http\Dao\Attendance\AttendanceWifiDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤组记录
 * Class AttendanceGroupService.
 */
class AttendanceGroupService extends BaseService implements AttendanceGroupInterface
{
    use ResourceServiceTrait;

    protected AttendanceGroupMemberDao $memberDao;

    protected AttendanceWhitelistDao $whitelistDao;

    protected AttendanceGroupShiftDao $shiftDao;

    protected AttendanceWifiDao $wifiDao;

    public function __construct(AttendanceGroupDao $dao, AttendanceGroupMemberDao $memberDao, AttendanceGroupShiftDao $shiftDao, AttendanceWhitelistDao $whitelistDao, AttendanceWifiDao $wifiDao)
    {
        $this->dao          = $dao;
        $this->memberDao    = $memberDao;
        $this->shiftDao     = $shiftDao;
        $this->whitelistDao = $whitelistDao;
        $this->wifiDao      = $wifiDao;
    }

    /**
     * 列表.
     * @param string $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'created_at', array $with = ['shifts', 'admin', 'user']): array
    {
        [$page, $limit] = $this->getPageValue();
        $super          = $this->whitelistDao->column(['type' => AttendanceGroupEnum::WHITELIST_ADMIN], 'uid') ?: [];
        if (! in_array(auth('admin')->id(), $super)) {
            $where['auth_uid'] = auth('admin')->id();
        }
        $list = $this->dao->setDefaultSort(sort_mode($sort))->select($where, $field, $with, $page, $limit)->each(function (&$item) use ($super) {
            if ($item->type) {
                $frameIds      = $this->memberDao->column(['group_id' => $item->id, 'type' => AttendanceGroupEnum::FRAME], 'member');
                $item->members = app()->get(FrameService::class)->select(['ids' => $frameIds], ['id', 'name']);
            } else {
                $item->load('members');
            }
            $admins = $item->admin ? array_column($item->admin->toArray(), 'id') : [];
            $users  = $item->user ? [$item->user->id] : [];
            unset($item->admin, $item->user);
            $item['admins'] = array_unique(array_merge($admins, $users));
            $item['super']  = $super;
        })?->toArray();
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 设置白名单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setWhitelist(array $list): bool
    {
        $res = $this->transaction(function () use ($list) {
            $this->setWhiteByType($list['members'], AttendanceGroupEnum::WHITELIST_MEMBER);
            $this->setWhiteByType($list['admins'], AttendanceGroupEnum::WHITELIST_ADMIN);

            app()->get(AttendanceArrangeService::class)->clearFutureArrangeByMembers($list['members'], true);
            app()->get(AttendanceStatisticsService::class)->clearWhitelist($list['members']);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_ATTENDANCE])->flush();
    }

    /**
     * 获取白名单.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getWhitelist(): array
    {
        return [
            'members' => $this->whitelistDao->select(['type' => AttendanceGroupEnum::WHITELIST_MEMBER], ['uid', 'uid as id'], ['card']),
            'admins'  => $this->whitelistDao->select(['type' => AttendanceGroupEnum::WHITELIST_ADMIN], ['uid', 'uid as id'], ['card']),
        ];
    }

    public function updateShift(int $id, mixed $shiftIds)
    {
        $this->handleShift($id, array_unique($shiftIds));
    }

    /**
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getShiftIds(int $id): array
    {
        return $this->shiftDao->column(['group_id' => $id], 'shift_id');
    }

    /**
     * 保存基本信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveGroup(array $data): mixed
    {
        $this->checkGroup((int) $data['type'], $data['name'], $data['members'], 0, $data['other_filters']);
        $res = $this->transaction(function () use ($data) {
            $res = $this->dao->create(['name' => $data['name'], 'uid' => auth('admin')->id(), 'type' => $data['type']]);
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }

            if ($data['other_filters']) {
                $this->otherFilters($data, $res->id);
            }

            $this->handleMember(array_unique($data['members']), $data['type'] == 0 ? AttendanceGroupEnum::MEMBER : AttendanceGroupEnum::FRAME, $res->id);
            [$filterGroups, $userIds] = $this->filterGroupMember((int) $data['type'], $data['members'], $res->id);
            $this->handleMember(array_unique(array_merge($data['filters'], $userIds)), AttendanceGroupEnum::FILTER, $res->id);
            $this->handleMember(array_unique($data['admins']), AttendanceGroupEnum::ADMIN, $res->id);

            foreach (array_unique($data['shifts']) as $shift) {
                $this->shiftDao->create(['group_id' => $res->id, 'shift_id' => $shift]);
            }

            return $res;
        });
        $this->refreshTodayStatistics();
        return $res;
    }

    /**
     * 核对考勤组.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkGroup(int $type = 0, string $name = '', array $members = [], int $id = 0, array $filterMember = []): void
    {
        if ($id && ! $this->dao->exists($id)) {
            throw $this->exception('操作失败，记录不存在');
        }

        if ($id > 0) {
            $uid = auth('admin')->id();
            if (! $this->isWhiteListAdmin($uid) && ! in_array($id, $this->column(['auth_uid' => $uid], 'id'))) {
                throw $this->exception('您没有权限操作');
            }
        }

        if ($name) {
            $where = ['name_like' => $name];
            if ($id) {
                $where['not_id'] = $id;
            }

            $this->dao->exists($where) && throw $this->exception('考勤组名称重复');
        }

        // 考勤重复检测
        if ($members) {
            $type ? $this->checkFrameRepeat($members, $id, $filterMember) : $this->checkMemberRepeat($members, $id, $filterMember);
        }
    }

    /**
     * 修改基本信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateStepOne(int $id, array $data): mixed
    {
        $this->checkGroup((int) $data['type'], $data['name'], $data['members'], $id, $data['other_filters']);
        $info = $this->dao->get(['id' => $id], ['*']);
        if (! $info) {
            throw $this->exception('操作失败，记录不存在');
        }

        $res = $this->transaction(function () use ($id, $data, $info) {
            $res = $this->dao->update($id, ['name' => $data['name'], 'type' => $data['type']]);
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }

            if ($info->type != $data['type']) {
                app()->get(AttendanceArrangeService::class)->clearFutureArrangeByGroupId($id, true);
                $this->memberDao->forceDelete(['group_id' => $id, 'type' => $info->type == 0 ? AttendanceGroupEnum::MEMBER : AttendanceGroupEnum::FRAME]);
            }
            $this->handleShift($id, array_unique($data['shifts']));
            if ($data['other_filters']) {
                $this->otherFilters($data, $id);
            }
            $this->handleMember(array_unique($data['members']), $data['type'] == 0 ? AttendanceGroupEnum::MEMBER : AttendanceGroupEnum::FRAME, $id);
            $this->handleMember(array_unique($data['filters']), AttendanceGroupEnum::FILTER, $id);
            $this->handleMember(array_unique($data['admins']), AttendanceGroupEnum::ADMIN, $id);

            return $res;
        });
        $this->refreshTodayStatistics();
        return $res;
    }

    /**
     * 修改考勤地点.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateStepTwo(int $id, array $data): int
    {
        $this->checkGroup(id: $id);
        $wifiData = $data['wifi_data'] ?: [];
        unset($data['wifi_data']);
        $this->wifiDao->delete(['group_id' => $id]);
        return $this->transaction(function () use ($id, $data, $wifiData) {
            foreach ($wifiData as $item) {
                $item['group_id'] = $id;
                $item['entid']    = $this->entId(false);
                $this->wifiDao->create($item);
            }
            return $this->dao->update($id, $data);
        });
    }

    /**
     * 修改考勤规则.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateStepThree(int $id, array $data): int
    {
        $this->checkGroup(id: $id);
        foreach ($data['repair_type'] as $item) {
            if (! in_array($item, AttendanceGroupEnum::CARD_REPLACEMENT_TYPE)) {
                throw $this->exception('请选择正确的补卡类型');
            }
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 修改考勤周期
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateStepFour(int $id, array $data): int
    {
        $this->checkGroup(id: $id);
        return $this->dao->update($id, $data);
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(int $id): array
    {
        $info = $this->dao->get(['id' => $id], ['*'], ['shifts', 'admins', 'filters', 'wifi']);
        if (! $info) {
            throw $this->exception('操作失败，记录不存在');
        }

        // 考勤部门
        if ($info->type) {
            $frameIds        = $this->memberDao->column(['group_id' => $id, 'type' => AttendanceGroupEnum::FRAME], 'member');
            $info['members'] = app()->get(FrameService::class)->select(['ids' => $frameIds], ['id', 'name']);
        } else {
            $info->load(['members']);
        }

        return toArray($info);
    }

    /**
     * 获取已考勤人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function filterGroupMember(int $type, array $members, int $excludeGroupId = 0): array
    {
        $userIds = $currenMemberIds = [];

        // 根据考勤部门获取考勤人员
        if ($type) {
            $currenMemberIds = $this->getMemberIdsByFrameIds(0, $members, false);
        } else {
            $currenMemberIds = $members;
        }

        [$filterGroups, $allMemberIds] = $this->getMemberIdsWithGroups($excludeGroupId);
        // 正常考勤人员
        $allMemberIds && $currenMemberIds && $userIds = array_intersect($currenMemberIds, $allMemberIds);
        return [$filterGroups, array_unique($userIds)];
    }

    /**
     * 考勤组重复人员检测.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function memberRepeatCheck(int $type, array $members, int $filterId): array
    {
        [$filterGroups, $allMemberIds] = $this->getMemberIdsWithGroups($filterId);

        // 考勤部门
        if ($type > 0) {
            $allMemberIds = array_diff($allMemberIds, $this->getMemberIdsByFrameIds(0, $members, false));
        }

        $intersect = array_intersect($members, $allMemberIds);
        if (empty($intersect)) {
            return [];
        }

        $with = [
            'job'    => fn ($query) => $query->select(['id', 'name']),
            'frames' => fn ($query) => $query->orderBy('frame_assist.is_admin', 'desc')->orderByDesc('frame_assist.is_mastart')->select(['frame.id', 'frame.name', 'frame_assist.is_mastart']),
        ];

        $groups = [];
        $list   = app()->get(AdminService::class)->select(['id' => $intersect, 'status' => 1], ['id', 'avatar', 'name', 'job', 'phone'], with: $with)->toArray();
        foreach ($list as &$item) {
            $groupId = $filterGroups[$item['id']];
            if (! isset($groups[$groupId])) {
                $item['group'] = $groups[$groupId] = $this->dao->get($groupId, ['id', 'name'])?->toArray();
            } else {
                $item['group'] = $groups[$groupId];
            }
        }

        return $list;
    }

    /**
     * 删除考勤组.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteGroup(int $id): bool
    {
        $this->checkGroup(id: $id);
        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }
            $this->memberDao->delete(['group_id' => $id]);
            $this->shiftDao->delete(['group_id' => $id]);
            app()->get(RosterCycleService::class)->deleteByGroupId($id);
            app()->get(AttendanceArrangeService::class)->clearFutureArrangeByGroupId($id);

            return true;
        });
    }

    /**
     * 检测班次是否使用.
     * @throws BindingResolutionException
     */
    public function checkShiftExist(int $shiftId): bool
    {
        return $this->shiftDao->exists(['shift_id' => $shiftId]);
    }

    /**
     * 获取未参与考核人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUnAttendMember(): array
    {
        [$filterGroups, $allMemberIds] = $this->getMemberIdsWithGroups();
        $adminService                  = app()->get(AdminService::class);
        $diffIds                       = array_diff(
            $adminService->column(['status' => 1], 'id'),
            array_unique(array_merge($this->getWhiteListMemberIds(), $allMemberIds))
        );
        $diffIds = array_intersect($diffIds, app(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id'));
        if (! count($diffIds)) {
            return [];
        }
        $with = [
            'job'    => fn ($query) => $query->select(['id', 'name']),
            'card'   => fn ($query) => $query->select(['work_time', 'id']),
            'frames' => fn ($query) => $query->orderBy('frame_assist.is_admin', 'desc')->orderByDesc('frame_assist.is_mastart')->select(['frame.id', 'frame.name', 'frame_assist.is_mastart']),
        ];
        return $adminService->getList(['id' => $diffIds, 'status' => 1], ['id', 'uid', 'avatar', 'name', 'job', 'phone'], with: $with);
    }

    /**
     * 考勤组人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupMember(int $id, string $name = '', bool $withTrashed = false): array
    {
        if ($withTrashed) {
            $this->dao->setTrashed();
            $this->memberDao->setTrashed();
        }

        if (! $this->dao->exists(['id' => $id])) {
            throw $this->exception('操作失败，考勤组记录不存在');
        }
        return app()->get(AdminService::class)->select(['id' => $this->getMemberIdsById($id), 'name_like' => $name], ['id', 'name'])->toArray();
    }

    /**
     * 根据成员获取.
     * @return null|BaseModel|BuildsQueries|mixed|Model|object
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupByUid(int $uid, array $field = ['*'], array $with = []): mixed
    {
        // 按人员获取
        $info = $this->dao->get(['member' => $uid], $field, $with);

        // 按部门获取
        if (! $info) {
            $frameIds = app()->get(FrameService::class)->getFrameIdsByUserId($uid);
            foreach (array_reverse($frameIds) as $frameId) {
                $info = $this->dao->get(['frame' => $frameId], $field, $with);
                if ($info) {
                    break;
                }
            }
        }
        return $info;
    }

    /**
     * 获取员工考勤组.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMemberClockGroup(int $uid, int $id = 0, bool $admins = false, bool $filter = false): array
    {
        $with = [];
        if ($admins) {
            $with['admins'] = fn ($q) => $q->select(['admin.id', 'admin.name', 'admin.job', 'admin.avatar'])->with(['job' => fn ($q) => $q->select(['id', 'name'])]);
        }
        $with['wifi'] = fn ($q) => $q->select(['group_id', 'attendance_wifi.name', 'attendance_wifi.mac']);
        $field        = ['id', 'name', 'address', 'lat', 'lng', 'effective_range', 'location_name', 'repair_allowed', 'repair_type',
            'is_limit_time', 'limit_time', 'is_limit_number', 'limit_number', 'is_photo', 'is_map', 'is_wifi', 'is_external', 'is_external_note', 'is_external_photo', ];

        if ($id) {
            $info = $this->dao->get($id, $field, $with);
        } else {
            $info = $this->getGroupByUid($uid, $field, $with);
        }

        if (! $info) {
            return [];
        }

        // 无需考勤
        if ($filter && ! in_array($uid, $this->getMemberIdsById((int) $info?->id))) {
            return [];
        }

        return toArray($info);
    }

    /**
     * 是否为白名单.
     * @throws BindingResolutionException
     */
    public function isWhitelist(int $uid, int $groupId = 0): bool
    {
        if ($groupId && $this->memberDao->exists(['group_id' => $groupId, 'type' => AttendanceGroupEnum::FILTER, 'member' => $uid])) {
            return true;
        }

        return $this->whitelistDao->exists(['uid' => $uid, 'type' => AttendanceGroupEnum::WHITELIST_MEMBER]);
    }

    /**
     * 获取考勤组人员.
     * @param bool $filter 过滤无需考勤
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMemberIdsById(int $id, bool $filter = true): array
    {
        $info = $this->dao->setTrashed()->get(['id' => $id], ['id', 'type']);
        if (! $info) {
            return [];
        }

        // 考勤部门
        if ($info->type) {
            $members = $this->getMemberIdsByFrameIds($id, $this->getMembersById($id, AttendanceGroupEnum::FRAME), false);
        } else {
            $members = $this->memberDao->column(['group_id' => $id, 'type' => AttendanceGroupEnum::MEMBER], 'member');
        }

        $filter && $members = $this->filterMember($id, $members);
        return $members;
    }

    /**
     * 过滤无需考勤人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function filterMember(int $id, array $members): array
    {
        if (! $id) {
            return $members;
        }

        $filters = array_merge(
            $this->getWhiteListMemberIds(),
            $this->memberDao->column(['group_id' => $id, 'type' => AttendanceGroupEnum::FILTER], 'member')
        );

        if ($filters) {
            return array_diff($members, $filters);
        }

        return $members;
    }

    /**
     * 根据管理员获取考勤成员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMemberByAdminUid(int $uid, bool $filter = false): array
    {
        $groupMemberIds = [];
        $rolesMemberIds = app()->get(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::ATTENDANCE);
        $ids            = $this->memberDao->column(['member' => $uid, 'type' => AttendanceGroupEnum::ADMIN], 'group_id');
        foreach ($ids as $id) {
            $groupMemberIds = array_merge($groupMemberIds, $this->getMemberIdsById((int) $id, $filter));
        }
        return array_unique(array_merge($rolesMemberIds, $groupMemberIds));
    }

    /**
     * 获取团队人员数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamMember(int|string $uuid, int $entId = 1, bool $filter = true, bool $withMe = true): array
    {
        $uid = is_string($uuid) ? uuid_to_uid($uuid) : $uuid;

        // 超级管理员
        if ($this->isWhiteListAdmin($uid)) {
            $member = app()->get(AdminService::class)->column(['status' => 1], 'id');
        } else {
            $member = $this->getMemberByAdminUid($uid, $filter);
        }

        $withMe && $member[] = $uid;
        return array_unique($member);
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelectList(array $where = [], array $field = ['id', 'name']): array
    {
        return $this->dao->getList($where, $field, 0, 0, 'id');
    }

    /**
     * 用户是否为白名单管理员.
     * @throws BindingResolutionException
     */
    public function isWhiteListAdmin(int $uid): bool
    {
        return $this->whitelistDao->exists(['uid' => $uid, 'type' => AttendanceGroupEnum::WHITELIST_ADMIN]);
    }

    /**
     * 获取考勤组管理员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMembersById(int $id, int $type): array
    {
        return $this->memberDao->column(['group_id' => $id, 'type' => $type], 'member');
    }

    /**
     * 获取超级管理员ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getWhiteListAdminIds(): array
    {
        $res = Cache::tags([CacheEnum::TAG_ATTENDANCE])->remember(md5('white_list_admin_ids' . $this->entId(false)), (int) sys_config('system_cache_ttl', 3600), function () {
            $uids = app()->get(AdminService::class)->column(['status' => 1], 'id');
            return json_encode($this->whitelistDao->column(['type' => AttendanceGroupEnum::WHITELIST_ADMIN, 'uid' => $uids], 'uid'), JSON_UNESCAPED_UNICODE);
        });
        return json_decode($res, true);
    }

    /**
     * 获取白名单人员ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getWhiteListMemberIds(): array
    {
        $res = Cache::tags([CacheEnum::TAG_ATTENDANCE])->remember(md5('white_list_member_ids'), (int) sys_config('system_cache_ttl', 3600), function () {
            $uids = app()->get(AdminService::class)->column(['status' => 1], 'id');
            return json_encode($this->whitelistDao->column(['type' => AttendanceGroupEnum::WHITELIST_MEMBER, 'uid' => $uids], 'uid'));
        });
        return json_decode($res, true);
    }

    /**
     * 获取考勤组人员数据.
     * @param bool $filter 过滤无需考勤
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMemberUsersById(int $id, bool $filter = true, bool $trashed = false): array
    {
        if ($trashed) {
            $this->dao->setTrashed();
            $this->memberDao->setTrashed();
        }
        $info = $this->dao->get(['id' => $id], ['id', 'type']);
        if (! $info) {
            return [];
        }

        $adminService = app()->get(AdminService::class);
        // 考勤部门
        if ($info->type) {
            $members = $this->getMemberIdsByFrameIds($id, $this->getMembersById($id, AttendanceGroupEnum::FRAME), false);
        } else {
            $members = $this->getMembersById($id, AttendanceGroupEnum::MEMBER);
        }
        $filter && $members = $this->filterMember($id, $members);
        return $adminService->select(['id' => $members], ['id', 'name', 'avatar'])->toArray();
    }

    /**
     * 考勤部门重复检测.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkFrameRepeat(array $members, int $id = 0, array $filterMember = []): void
    {
        $allFrameIds  = [];
        $frameService = app()->get(FrameService::class);

        $entId = 1;
        foreach ($this->dao->select(['type' => 1], ['id', 'type']) as $item) {
            $frameIds = $this->getMembersById((int) $item->id, AttendanceGroupEnum::FRAME);
            foreach ($frameIds as $frameId) {
                $allFrameIds = array_merge($allFrameIds, [$frameId], $frameService->getFrameTotalIds($frameId, $entId));
            }
        }

        if ($id) {
            $groupFrameIds = [];
            if (! $this->dao->exists(['id' => $id])) {
                throw $this->exception('考勤组异常');
            }

            $entId        = 1;
            $frameService = app()->get(FrameService::class);
            $frameIds     = $this->getMembersById($id, AttendanceGroupEnum::FRAME);
            foreach ($frameIds as $frameId) {
                $groupFrameIds = array_merge($groupFrameIds, [$frameId], $frameService->getFrameTotalIds($frameId, $entId));
            }

            $allFrameIds = array_diff($allFrameIds, $groupFrameIds);
        }

        $memberFrameIds = [];
        foreach ($members as $member) {
            $memberFrameIds = array_merge($memberFrameIds, [$member], $frameService->getFrameTotalIds($member, $entId));
        }

        if (! empty(array_intersect(array_unique($memberFrameIds), array_unique($allFrameIds)))) {
            throw $this->exception('考勤部门重复');
        }
    }

    /**
     * 考勤人员重复检测.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function checkMemberRepeat(array $members, int $id = 0, array $filterMember = []): void
    {
        [$filterGroups, $allMemberIds] = $this->getMemberIdsWithGroups();
        $allMemberIds                  = array_unique($allMemberIds);

        if ($id) {
            $allMemberIds = array_diff($allMemberIds, $this->getMemberIdsById($id));
        }

        if ($filterMember) {
            $allMemberIds = array_diff($allMemberIds, $filterMember);
        }

        if (! empty(array_intersect($members, $allMemberIds))) {
            throw $this->exception('考勤人员重复');
        }
    }

    /**
     * 参加考勤的部门/人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupMembersByType(int $type, int $filterId = 0): array
    {
        $filterIds = [];

        $frameService = app()->get(FrameService::class);

        // 所有考勤人员
        [$filterGroups, $allMemberIds] = $this->getMemberIdsWithGroups();
        $groupMemberIds                = array_unique($allMemberIds);

        if ($filterId) {
            if ($type == AttendanceGroupEnum::FRAME) {
                foreach ($this->getMembersById($filterId, AttendanceGroupEnum::FRAME) as $frameId) {
                    $filterIds = array_merge($filterIds, [$frameId], $frameService->getFrameTotalIds($frameId, 1));
                }
            } else {
                $filterIds = $this->getMemberIdsById($filterId);
            }

            $groupMemberIds = array_diff($groupMemberIds, array_unique($filterIds));
        }

        if (empty($groupMemberIds)) {
            return [];
        }

        $with = [
            'job'    => fn ($query) => $query->select(['id', 'name']),
            'frames' => fn ($query) => $query->orderBy('frame_assist.is_admin', 'desc')->orderByDesc('frame_assist.is_mastart')->select(['frame.id', 'frame.name', 'frame_assist.is_mastart']),
        ];
        $list = app()->get(AdminService::class)->select(['id' => $groupMemberIds, 'status' => 1], ['id', 'avatar', 'name', 'job', 'phone'], with: $with)->toArray();
        foreach ($list as $key => $item) {
            $list[$key]['group'] = $this->getGroupByUid($item['id'], ['id', 'name']);
        }
        return $list;
    }

    /**
     * 获取所有实际参与考勤人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getAttendanceMemberIds(): array
    {
        [, $memberIds] = $this->getMemberIdsWithGroups();
        return array_values(array_unique($memberIds));
    }

    private function refreshTodayStatistics(): void
    {
        try {
            app()->get(AttendanceStatisticsService::class)->generateMemberStatistics(now(config('app.timezone'))->toDateString());
        } catch (\Throwable $e) {
            Log::error('考勤组成员变更后补当天统计失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * 获取考勤组.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupByUidAndGroupId(int $uid, int $groupId = 0, array $field = ['id', 'name']): mixed
    {
        if ($groupId) {
            $group = $this->dao->get($groupId, $field);
            if ($group) {
                return $group;
            }
        }
        return $this->getGroupByUid($uid, $field);
    }

    /**
     * 设置白名单人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function setWhiteByType(array $list, int $type): void
    {
        $whiteList    = array_column($this->whitelistDao->column(['type' => $type], ['uid']), 'uid');
        $delAids      = array_diff($whiteList, $list);
        $list         = array_diff($list, $whiteList);
        $adminService = app()->get(AdminService::class);

        $delAids && $this->whitelistDao->delete(['uid' => $delAids]);
        foreach ($list as $item) {
            if (! $adminService->exists(['id' => $item])) {
                continue;
            }
            $this->whitelistDao->create(['uid' => $item, 'type' => $type]);
        }
    }

    /**
     * 处理考勤成员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function handleMember(array $members, int $type, int $groupId = 0, int $entId = 1): void
    {
        $oldMembers = $this->memberDao->column(['group_id' => $groupId, 'type' => $type, 'entid' => $entId], 'member', 'id');
        $delIds     = array_diff($oldMembers, $members);
        $this->transaction(function () use ($members, $type, $groupId, $entId, $delIds) {
            foreach ($members as $member) {
                $this->memberDao->firstOrCreate([
                    'group_id' => $groupId, 'type' => $type, 'entid' => $entId, 'member' => $member,
                ], [
                    'group_id' => $groupId, 'type' => $type, 'entid' => $entId, 'member' => $member,
                ]);
            }
            $this->memberDao->delete(['id' => array_keys($delIds)]);
            return true;
        });
        if ($delIds) {
            $ids = match ($type) {
                AttendanceGroupEnum::MEMBER => array_keys($delIds),
                AttendanceGroupEnum::FRAME  => $this->getMemberIdsByFrameIds($groupId, array_keys($delIds)),
                AttendanceGroupEnum::FILTER => $members,
                default                     => [],
            };
            app()->get(AttendanceArrangeService::class)->clearFutureArrangeByMembers($ids);
        }
    }

    /**
     * 处理考勤班次
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function handleShift(int $groupId, array $shifts): void
    {
        $data      = [];
        $shiftList = $this->shiftDao->column(['group_id' => $groupId], 'shift_id', 'id');
        foreach ($shiftList as $key => $item) {
            $data[$groupId . '_' . $item] = $key;
        }

        foreach ($shifts as $shift) {
            if (isset($data[$groupId . '_' . $shift])) {
                unset($data[$groupId . '_' . $shift]);
                continue;
            }

            $whereData = ['group_id' => $groupId, 'shift_id' => $shift];
            $this->shiftDao->firstOrCreate($whereData, $whereData);
        }
        $data && $this->shiftDao->forceDelete(['id' => array_values($data)]);
    }

    /**
     * 调整考勤组排除成员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function otherFilters(array $data, int $excludeGroupId = 0): void
    {
        [$filterGroups, $userIds] = $this->filterGroupMember((int) $data['type'], $data['members'], $excludeGroupId);
        app()->get(AttendanceArrangeService::class)->clearFutureArrangeByMembers($data['other_filters']);
        foreach ($data['other_filters'] as $filter) {
            if (isset($filterGroups[$filter])) {
                $whereData = ['group_id' => $filterGroups[$filter], 'member' => $filter, 'type' => AttendanceGroupEnum::FILTER];
                $this->memberDao->updateOrCreate($whereData, $whereData);
            }
        }
    }

    /**
     * 是否为无需考勤人员.
     * @throws BindingResolutionException
     */
    private function isFilterMember(int $groupId, int $uid): bool
    {
        return $this->memberDao->exists(['group_id' => $groupId, 'type' => AttendanceGroupEnum::FILTER, 'member' => $uid]);
    }

    /**
     * 获取所有考勤组人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getMemberIdsWithGroups(int $excludeGroupId = 0): array
    {
        $groups        = $memberIds = [];

        $list = $this->dao->select($excludeGroupId ? ['not_id' => $excludeGroupId] : [], ['id', 'type']);

        foreach ($list as $item) {
            if ($item->type) {
                $currentMemberIds = $this->getMemberIdsByFrameIds($item->id, $this->getMembersById($item->id, AttendanceGroupEnum::FRAME));
            } else {
                $currentMemberIds = $this->getMemberIdsById($item->id);
            }
            // 员工对应部门
            foreach ($currentMemberIds as $memberId) {
                $groups[$memberId] = $item->id;
            }
            $memberIds = array_merge($memberIds, $currentMemberIds);
        }

        $excludeGroupId && $memberIds = array_diff($memberIds, $this->getMemberIdsById($excludeGroupId));
        return [$groups, array_unique($excludeGroupId ? $this->filterMember($excludeGroupId, $memberIds) : $memberIds)];
    }

    /**
     * 获取指定部门考勤人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getMemberIdsByFrameIds(int $id, array $frameIds, bool $filter = true): array
    {
        if (! $frameIds) {
            return [];
        }

        $members = array_unique(app()->get(FrameService::class)->scopeUser($frameIds, withMaster: true));
        return $filter ? $this->filterMember($id, $members) : $members;
    }
}
