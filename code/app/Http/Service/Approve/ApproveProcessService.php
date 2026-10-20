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

namespace App\Http\Service\Approve;

use App\Http\Dao\Approve\ApproveProcessDao;
use App\Http\Dao\Approve\ApproveUserDao;
use App\Http\Model\Admin\Admin;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 审核流程树.
 * @mixin ApproveProcessDao
 */
class ApproveProcessService extends BaseService
{
    protected $cacheTtl;

    public function __construct(ApproveProcessDao $dao, protected ApproveUserDao $userDao)
    {
        $this->dao      = $dao;
        $this->cacheTtl = (int) sys_config('system_cache_ttl', 3600);
    }

    /**
     * @param mixed $data
     * @param mixed $approve_id
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveMore($data, $approve_id)
    {
        foreach ($data as $val) {
            if ($this->dao->exists(['approve_id' => $approve_id, 'uniqued' => $val['uniqued']])) {
                $this->dao->update(['approve_id' => $approve_id, 'uniqued' => $val['uniqued']], $val);
            } else {
                $val['approve_id'] = $approve_id;
                $this->dao->create($val);
            }
        }
        return true;
    }

    /**
     * 验证表单数据、获取处理人员列表.
     * @param mixed $data
     * @param mixed $approveId
     * @param mixed $uid
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function verifyForm($data, $approveId, $uid): array
    {
        $approveService = app()->get(ApproveService::class);
        $approve        = $approveService->get(['id' => $approveId], ['id', 'name', 'status', 'examine']);
        // 审批配置不存在或未开启审批流时，业务无需走流程
        if (! $approve || ! $approve->examine) {
            return [];
        }
        if (! $approve->status) {
            throw $this->exception('审批流程「' . $approve->name . '」已停用，请前往 人事->办公审批->审批设置 中启用后重试');
        }
        if (! in_array($approveId, $approveService->getUsableApprove(auth('admin')->id()))) {
            throw $this->exception('您不在审批流程「' . $approve->name . '」的发起人范围内，或该流程未配置发起节点，请联系管理员检查审批设置');
        }
        $process = $this->dao->setDefaultSort(['groups' => 'asc'])->select(['approve_id' => $approveId])?->toArray() ?: [];
        $rules   = app()->get(ApproveRuleService::class)->get(['approve_id' => $approveId], ['*'], ['card'])?->toArray();
        $data    = $this->getNodeList((int) $approveId, $process, $data, $uid);
        if ($data) {
            foreach ($data as $key => $item) {
                if ($item['types'] == 3) {
                    unset($data[$key]);
                }
            }
            $list = array_values($data);
        } else {
            $list = [];
        }
        if ($list) {
            $abnormal = app()->get(ApproveAbnormalService::class);
            $context  = $abnormal->loadContext((int) $approveId);
            $list     = $abnormal->preview($list, $context['abnormal'], $context['handler']);
        }
        return compact('list', 'rules');
    }

    /**
     * 获取流程节点数据(新).
     * @param mixed $approveId
     * @param mixed $unique
     * @param mixed $data
     * @param mixed $userNodes
     * @param mixed $uniques
     * @param mixed $userId
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNodeList(int $approveId, array $process, array $data, int $userId, string $unique = '', array $routes = [], array $userNodes = [], array $uniques = [], bool $end = false)
    {
        if (! $unique) {
            $children = array_filter($process, function ($item) {
                return $item['types'] == 0;
            });
            $uniques = array_column($children, 'uniqued');
            return $children ? $this->getNodeList($approveId, $process, $data, $userId, $uniques[0], $routes, $userNodes, $uniques) : $userNodes;
        }
        $children = array_filter($process, function ($item) use ($unique, $uniques) {
            return $item['parent'] == $unique && ! in_array($item['uniqued'], $uniques);
        });
        if (! $children) {
            if (! $routes) {
                return $userNodes;
            }
            $key = array_search($unique, $routes);
            if ($end && $unique == $routes[0]) {
                return $userNodes;
            }
            if ($key) {
                return $this->getNodeList($approveId, $process, $data, $userId, $routes[$key - 1], $routes, $userNodes, $uniques, ! ($key - 1));
            }
            return $this->getNodeList($approveId, $process, $data, $userId, end($routes), $routes, $userNodes, $uniques, true);
        }
        if ($condition = array_filter($children, function ($item) {
            return $item['types'] == 3;
        })) {
            $children             = $condition;
            $children && $uniques = array_merge($uniques, array_column($children, 'uniqued'));
        } elseif ($haveRoute = array_filter($children, function ($item) {
            return $item['types'] == 4;
        })) {
            $children = $haveRoute;
        }

        foreach ($children as $child) {
            ! in_array($child['uniqued'], $uniques) && $uniques[] = $child['uniqued'];
            switch ($child['types']) {
                case 4:
                    if (in_array($child['uniqued'], $routes)) {
                        if (($key = array_search($unique, $routes)) !== false) {
                            return $this->getNodeList($approveId, $process, $data, $userId, $routes[$key - 1], $routes, $userNodes, $uniques);
                        }
                        return $userNodes;
                    }
                    $routes[] = $child['uniqued'];
                    return $this->getNodeList($approveId, $process, $data, $userId, $child['uniqued'], $routes, $userNodes, $uniques);
                case 3:
                    $nextUnique = $this->verifyCondition($child, $data, $approveId, $userId);
                    if (is_bool($nextUnique)) {
                        break;
                    }
                    if ($nextUnique) {
                        if (in_array($nextUnique['types'], [3, 4])) {
                            $routes[] = $nextUnique['uniqued'];
                            return $this->getNodeList($approveId, $process, $data, $userId, $nextUnique['uniqued'], $routes, $userNodes, $uniques);
                        }
                        if (! in_array($nextUnique['uniqued'], array_column($userNodes, 'uniqued'))) {
                            $userNodes[] = $this->formatUserData($nextUnique, $userId);
                            return $this->getNodeList($approveId, $process, $data, $userId, $nextUnique['uniqued'], $routes, $userNodes, $uniques);
                        }
                    } else {
                        return $this->getNodeList($approveId, $process, $data, $userId, end($routes), $routes, $userNodes, $uniques);
                    }
                    break;
                default:
                    if (! in_array($child['uniqued'], array_column($userNodes, 'uniqued'))) {
                        $userNodes[] = $this->formatUserData($child, $userId);
                    }
                    return $this->getNodeList($approveId, $process, $data, $userId, $child['uniqued'], $routes, $userNodes, $uniques);
            }
        }
        return $userNodes;
    }

    /**
     * 通过条件验证获取下一步节点.
     * @param mixed $condition
     * @param mixed $data
     * @param mixed $approveId
     * @param mixed $userId
     * @return array|false
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function verifyCondition($condition, $data, $approveId, $userId = 0)
    {
        foreach ($condition['condition_list'] as $value) {
            if ($value['field']) {
                $val = isset($data[$value['field']]['holiday_id']) ? $data[$value['field']]['holiday_id']['value'] : $data[$value['field']];
            } else {
                $val = '';
            }
            switch ($value['type']) {
                case 'inputNumber':
                case 'moneyFrom':
                    $res = match ($value['value']) {
                        '3'     => $val >= $value['option'],
                        '2'     => $val <= $value['option'],
                        '1'     => $val == $value['option'],
                        default => $val < $value['option']
                    };
                    if (! $res) {
                        return false;
                    }
                    break;
                case 'select':
                    if ($value['option']) {
                        if (! in_array($val, $value['option'])) {
                            return false;
                        }
                    } else {
                        if ($value['option'] != [$val]) {
                            return false;
                        }
                    }
                    break;
                case 'radio':
                    if ($value['option'] != $val) {
                        return false;
                    }
                    break;
                case 'checkbox':
                    if ($value['value']) {
                        if (is_array($val)) {
                            if (! array_intersect($val, $value['option'])) {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    } else {
                        if (is_array($val)) {
                            if (array_diff($val, $value['option'])) {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    }
                    break;
                case 'departmentTree':
                    if (! $val && $value['category'] > 1) {
                        return false;
                    }
                    switch ($value['category']) {
                        case 3:
                            $cardIds = array_column($value['options']['depList'], 'id');
                            $val     = is_array($val) ? array_column($val, 'id') : [];
                            if (isset($value['options']['value']) && $value['options']['value']) {
                                if (! array_diff($val, $cardIds)) {
                                    return false;
                                }
                            } else {
                                if (array_diff($val, $cardIds)) {
                                    return false;
                                }
                            }
                            break;
                        case 2:
                            $cardIds = array_column($value['options']['userList'], 'card_id');
                            $val     = is_array($val) ? array_column($val, 'id') : [];
                            if (isset($value['options']['value']) && $value['options']['value']) {
                                if (! array_diff($val, $cardIds)) {
                                    return false;
                                }
                            } else {
                                if (array_diff($val, $cardIds)) {
                                    return false;
                                }
                            }
                            break;
                        default:// 申请人
                            $cardIds = array_column($value['options']['userList'], 'id');
                            if (! in_array($userId, $cardIds)) {
                                return false;
                            }
                    }
                    break;
                case 'timeFrom':
                    $val = $val['duration'] ?? 0;
                    switch ($value['value']) {
                        case 4:
                            if ($val < $value['min'] || $val > $value['max']) {
                                return false;
                            }
                            break;
                        case 3:
                            if ($val < $value['option']) {
                                return false;
                            }
                            break;
                        case 2:
                            if ($val > $value['option']) {
                                return false;
                            }
                            break;
                        case 1:
                            if ($val != $value['option']) {
                                return false;
                            }
                            break;
                        default:
                            if ($val >= $value['option']) {
                                return false;
                            }
                    }
                    break;
            }
        }
        return $this->dao->get(['approve_id' => $approveId, 'parent' => $condition['uniqued'], 'groups' => $condition['groups']])?->toArray() ?: [];
    }

    /**
     * 格式化审批节点数据.
     * @param array $data 原始数据
     * @param int $userId 用户ID
     * @return array 格式化后的数据
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function formatUserData(array $data, int $userId): array
    {
        // 初始化返回数据
        $save = [
            'types'       => $data['types'] ?? 0,
            'title'       => $data['name'] ?? '',
            'uniqued'     => $data['uniqued'] ?? '',
            'level'       => $data['level'] ?? 0,
            'select_mode' => $data['select_mode'] ?? 0,
            'users'       => [],
        ];
        $users         = [];
        $adminService  = app()->get(AdminService::class);
        $frameService  = app()->get(FrameService::class);
        $defaultFields = ['id', 'uid', 'name', 'avatar'];

        // 审批节点
        if ($data['types'] == 1) {
            $save['examine_mode'] = $data['examine_mode'] ?? 0;
            $save['pass_ratio']   = $data['pass_ratio'] ?? 0;
            $save['settype']      = $data['settype'] ?? 0;
            $save['no_hander']    = $data['no_hander'] ?? 0;
            switch ($data['settype']) {
                case 1: // 指定成员
                    $users = $this->getUsersByUserList($data, $adminService, $defaultFields);
                    break;
                case 2: // 指定部门主管
                    $ups = $frameService->getLevelSuperUser($userId, 0, true);
                    if (! ($data['director_order'] ?? false)) {
                        $ups = array_reverse($ups);
                    }

                    foreach ($ups as $k => $v) {
                        if (($data['director_level'] ?? 0) == $k + 1) {
                            if (($data['no_hander'] ?? 0) == 1 && ! $v && isset($ups[$k + 2])) {
                                $users[] = $ups[$k + 2];
                            } else {
                                $users[] = $v;
                            }
                        }
                    }
                    break;
                case 7: // 连续多级审批
                    if ($data['director_order'] ?? false) {
                        $users = $frameService->getLevelSuperUser($userId, $data['director_level'] ?? 1, true);
                    } else {
                        $ups           = $frameService->getLevelSuperUser($userId, 0, true);
                        $ups           = array_reverse($ups, false);
                        $ups && $users = array_slice($ups, 0, $data['director_level'] ?? 1);
                    }
                    break;
                case 5: // 申请人自己
                    $arr = $adminService->get($userId, $defaultFields)?->toArray();
                    if ($arr) {
                        $users = [
                            [
                                'id'      => $arr['id'],
                                'card_id' => $arr['id'],
                                'card'    => $arr,
                            ],
                        ];
                    }
                    break;
                case 4: // 申请人自选
                    $save['uniqued'] = $data['uniqued'] ?? '';
                    $save['options'] = isset($data['select_range']) && $data['select_range'] > 1 ? ($data['user_list'] ?? []) : [];
                    break;
            }
        } else { // 抄送节点
            $save['self_select'] = $data['self_select'] ?? 0;
            // 处理部门负责人
            if (! empty($data['dep_head'])) {
                $ups = $frameService->getLevelSuperUser($userId, 0, true);
                if ($ups) {
                    foreach ($ups as $k => $v) {
                        if (in_array($k + 1, $data['dep_head'])) {
                            $users[] = $v;
                        }
                    }
                }
            }
            // 处理用户列表
            if (! empty($data['user_list'])) {
                $user_list = $this->getUsersByUserList($data, $adminService, $defaultFields);
                $users     = array_merge($users, $user_list);
            }
            // 去重
            $users = assoc_unique($users, 'id');
        }
        $save['users'] = $users;
        return $save;
    }

    /**
     * 回填流程配置中的人员名片（含离职/停用），避免前端显示 undefined.
     *
     * @param mixed $processConfig
     * @return array<string, mixed>
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function enrichProcessConfigInfo(mixed $processConfig): array
    {
        if (is_string($processConfig) && $processConfig !== '') {
            $decoded = json_decode($processConfig, true);
            $processConfig = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($processConfig) || $processConfig === []) {
            return is_array($processConfig) ? $processConfig : [];
        }

        return $this->fillProcessNodeUsers($processConfig);
    }

    /**
     * 读取审批配置展示用的人员档案（不限制在职，含软删）.
     *
     * @return array{id: int, uid: string, name: string, avatar: string, status: int}|null
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getProcessUserProfile(int $adminId): ?array
    {
        if ($adminId <= 0) {
            return null;
        }
        $admin = Admin::withTrashed()
            ->where('id', $adminId)
            ->first(['id', 'uid', 'name', 'avatar', 'status']);
        if (! $admin) {
            return null;
        }
        $row = $admin->toArray();

        return [
            'id'     => (int) $row['id'],
            'uid'    => (string) ($row['uid'] ?? ''),
            'name'   => (string) ($row['name'] ?: ('已离职用户#' . $row['id'])),
            'avatar' => (string) ($row['avatar'] ?? ''),
            'status' => (int) ($row['status'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function fillProcessNodeUsers(array $node): array
    {
        if (isset($node['nodeUserList']) && is_array($node['nodeUserList'])) {
            if (array_key_exists('userList', $node['nodeUserList']) || array_key_exists('depList', $node['nodeUserList'])) {
                $node['nodeUserList']['userList'] = $this->hydrateNodeUserList($node['nodeUserList']['userList'] ?? []);
            } else {
                $node['nodeUserList'] = $this->hydrateNodeUserList($node['nodeUserList']);
            }
        }
        if (! empty($node['childNode']) && is_array($node['childNode'])) {
            $node['childNode'] = $this->fillProcessNodeUsers($node['childNode']);
        }
        if (! empty($node['conditionNodes']) && is_array($node['conditionNodes'])) {
            foreach ($node['conditionNodes'] as $index => $condition) {
                if (is_array($condition)) {
                    $node['conditionNodes'][$index] = $this->fillProcessNodeUsers($condition);
                }
            }
        }

        return $node;
    }

    /**
     * @param mixed $users
     * @return array<int, array<string, mixed>>
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function hydrateNodeUserList(mixed $users): array
    {
        if (! is_array($users) || $users === []) {
            return [];
        }
        $ids  = [];
        $uids = [];
        foreach ($users as $user) {
            if (! is_array($user)) {
                continue;
            }
            $id = (int) ($user['value'] ?? 0);
            if ($id <= 0 && isset($user['id']) && is_numeric($user['id'])) {
                $id = (int) $user['id'];
            }
            if ($id > 0) {
                $ids[] = $id;
            }
            $uid = (string) ($user['uid'] ?? ($user['card']['uid'] ?? ''));
            if ($uid !== '') {
                $uids[] = $uid;
            }
        }
        $profiles = $this->loadProcessUserProfiles($ids, $uids);
        $result   = [];
        foreach ($users as $user) {
            if (! is_array($user)) {
                continue;
            }
            $id  = (int) ($user['value'] ?? 0);
            if ($id <= 0 && isset($user['id']) && is_numeric($user['id'])) {
                $id = (int) $user['id'];
            }
            $uid     = (string) ($user['uid'] ?? ($user['card']['uid'] ?? ''));
            $profile = $profiles['by_id'][$id] ?? ($uid !== '' ? ($profiles['by_uid'][$uid] ?? null) : null);
            if ($profile) {
                $card = is_array($user['card'] ?? null) ? $user['card'] : [];
                $card = array_merge($card, [
                    'id'     => $profile['id'],
                    'uid'    => $profile['uid'],
                    'name'   => $profile['name'],
                    'avatar' => $profile['avatar'],
                    'status' => $profile['status'],
                ]);
                $user['card']   = $card;
                $user['id']     = $user['id'] ?? $profile['id'];
                $user['value']  = $user['value'] ?? $profile['id'];
                $user['uid']    = $user['uid'] ?? $profile['uid'];
                $user['name']   = $user['name'] ?? $profile['name'];
                $user['avatar'] = $user['avatar'] ?? $profile['avatar'];
                $user['label']  = $user['label'] ?? $profile['name'];
            } else {
                $fallback = '未知人员';
                if ($id > 0) {
                    $fallback = '已离职用户#' . $id;
                }
                $user['name'] = $user['name'] ?? $fallback;
                if (! isset($user['card']) || ! is_array($user['card'])) {
                    $user['card'] = [];
                }
                $user['card']['name'] = $user['card']['name'] ?? $fallback;
            }
            $result[] = $user;
        }

        return $result;
    }

    /**
     * @param array<int, int> $ids
     * @param array<int, string> $uids
     * @return array{by_id: array<int, array<string, int|string>>, by_uid: array<string, array<string, int|string>>}
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function loadProcessUserProfiles(array $ids, array $uids): array
    {
        $byId  = [];
        $byUid = [];
        $ids   = array_values(array_unique(array_filter($ids)));
        $uids  = array_values(array_unique(array_filter($uids)));
        if ($ids === [] && $uids === []) {
            return ['by_id' => $byId, 'by_uid' => $byUid];
        }
        $query = Admin::withTrashed()->select(['id', 'uid', 'name', 'avatar', 'status']);
        if ($ids !== [] && $uids !== []) {
            $query->where(function ($q) use ($ids, $uids) {
                $q->whereIn('id', $ids)->orWhereIn('uid', $uids);
            });
        } elseif ($ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereIn('uid', $uids);
        }
        foreach ($query->get() as $admin) {
            $profile = [
                'id'     => (int) $admin->id,
                'uid'    => (string) $admin->uid,
                'name'   => (string) ($admin->name ?: ('已离职用户#' . $admin->id)),
                'avatar' => (string) ($admin->avatar ?? ''),
                'status' => (int) $admin->status,
            ];
            $byId[$profile['id']]   = $profile;
            $byUid[$profile['uid']] = $profile;
        }

        return ['by_id' => $byId, 'by_uid' => $byUid];
    }

    /**
     * 根据用户列表获取用户数据.
     * @param array $data 原始数据
     * @param AdminService $adminService 实例
     * @param array $fields 查询字段
     * @return array 用户数据
     */
    private function getUsersByUserList(array $data, AdminService $adminService, array $fields = ['id', 'uid', 'name', 'avatar']): array
    {
        if (empty($data['user_list'])) {
            return [];
        }
        $card = array_column($data['user_list'], 'card');
        if ($card) {
            $uidColumn = array_column($card, 'uid');
        } else {
            $uidColumn = array_column($data['user_list'], 'uid');
        }
        if (empty($uidColumn)) {
            return [];
        }
        $users = $adminService->select(['uid' => $uidColumn, 'status' => 1], $fields)?->toArray() ?: [];
        if ($users) {
            // 排序处理
            $sortOrder = array_column(array_map(function ($item) use ($card, $uidColumn) {
                return [
                    'name'  => $item['name'],
                    'order' => array_search($item['uid'], $card ? array_column($card, 'uid') : $uidColumn),
                ];
            }, $users), 'order');

            array_multisort($sortOrder, SORT_ASC, $users);
        }
        return $users;
    }
}
