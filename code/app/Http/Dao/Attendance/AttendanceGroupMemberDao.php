<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Dao\Attendance;

use App\Http\Model\Attendance\AttendanceGroupMember;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 考勤组人员Dao
 * Class AttendanceGroupMemberDao.
 */
class AttendanceGroupMemberDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 查询指定用户所在的考勤组及成员信息.
     * @param array $userIds 待查询的用户ID数组
     * @return array 整理后的关联数组
     */
    public function getUserAttendanceGroups(array $userIds, string $date): array
    {
        // 1. 直接关联：type为0/1/2（member是用户ID）
        $directMembers = $this->getModel()
            ->select('group_id', 'member as user_id', 'type', DB::raw("'user' as relation_type"))
            ->whereIn('member', $userIds)
            ->whereIn('type', [0, 1])
            ->get();
        // 2. 部门关联：type为3（member是部门ID，需通过FrameAssist关联用户）
        $deptMembers = $this->getModel()
            ->select(
                'group_id',
                'efa.user_id',
                'type',
                DB::raw("'dept' as relation_type")
            )
            ->join('frame_assist as efa', 'member', '=', 'efa.frame_id')
            ->whereIn('efa.user_id', $userIds)
            ->where('efa.is_mastart', 1)
            ->whereNull('efa.deleted_at')
            ->where('type', 3)
            ->get();
        // 3. 合并两种结果并去重（避免同一用户因多部门关联重复出现）
        $allMembers = $directMembers->concat($deptMembers)->unique(function ($item) {
            return $item->group_id . '-' . $item->user_id . '-' . $item->type;
        });
        // 3. 找出每个考勤组内需要排除的用户（存在type=1的user_id）
        $excludeUsersByGroup = $allMembers
            ->filter(function ($item) {
                return $item->type == 1; // 筛选出type=1的记录
            })
            ->groupBy('group_id') // 按考勤组分组
            ->map(function (Collection $group) {
                // 每个组内需要排除的user_id列表
                return $group->pluck('user_id')->unique()->all();
            });
        // 4. 过滤记录：排除每个组内的"需排除用户"
        $filteredMembers = $allMembers->filter(function ($item) use ($excludeUsersByGroup) {
            $groupId = $item->group_id;
            $userId  = $item->user_id;
            // 若该组存在需排除的用户，且当前用户在排除列表中，则过滤掉
            return ! isset($excludeUsersByGroup[$groupId]) || ! in_array($userId, $excludeUsersByGroup[$groupId]);
        });
        // 5. 按考勤组整理结果，排除空组
        $groupUsers = $filteredMembers->groupBy('group_id')
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'group_id' => $first->group_id,
                    'users'    => $group->map(function ($item) {
                        return [
                            'user_id'       => $item->user_id,
                            'type'          => $item->type,
                            'type_desc'     => AttendanceGroupMember::$typeMap[$item->type] ?? '未知类型',
                            'relation_type' => $item->relation_type,
                        ];
                    })->values()->all(),
                ];
            })
            ->filter(function ($group) {
                return ! empty($group['users']); // 排除无成员的组
            });
        $userIds = $groupUsers->flatMap(function ($group) {
            return collect($group['users'])->pluck('user_id');
        })->unique()->all();
        $shiftIds = collect(app()->get(AttendanceArrangeRecordDao::class)->column(['uid' => $userIds, 'date' => $date], 'shift_id', 'uid'));
        $result   = collect();
        $groupUsers->map(function ($group) use ($shiftIds, $result) {
            $group['users'] = collect($group['users'])->map(function ($user) use ($group, $shiftIds, $result) {
                $result->push([
                    'group_id' => $group['group_id'],
                    'user_id'  => $user['user_id'],
                    'shift_id' => $shiftIds->get($user['user_id']) ?? 2,
                ]);
                return $user;
            })->values()->all();
            return $group;
        })->values()->all();
        return $result->keyBy('user_id')->all();
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AttendanceGroupMember::class;
    }
}
