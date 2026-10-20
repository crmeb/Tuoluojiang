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

namespace App\Http\Service\Crud;

use App\Constants\CacheEnum;
use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudOperatorEnum;
use App\Http\Dao\Crud\SystemCrudApproveProcessDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Approve\ApproveAbnormalService;
use App\Http\Service\Frame\FrameService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SystemCrudApproveProcessService extends BaseService
{
    protected $cacheTtl;

    public function __construct(SystemCrudApproveProcessDao $dao)
    {
        $this->dao      = $dao;
        $this->cacheTtl = (int) sys_config('system_cache_ttl', 3600);
    }

    /**
     * @param mixed $data
     * @param mixed $approve_id
     * @return bool
     * @throws BindingResolutionException
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
     * @param mixed $userId
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function verifyForm($data, $approveId, $userId): array
    {
        $applyProcess = toArray($this->dao->get(['approve_id' => $approveId, 'types' => 0], ['uniqued', 'user_list']));
        if (! $applyProcess) {
            throw $this->exception('无效的审批流程');
        }
        if (array_column($applyProcess['user_list'], 'value') && ! in_array($userId, array_column($applyProcess['user_list'], 'value'))) {
            throw $this->exception('申请人不可申请该流程');
        }
        $process = $this->dao->setDefaultSort(['groups' => 'asc'])->select(['approve_id' => $approveId])?->toArray() ?: [];
        $data    = $this->getNodeList((int) $approveId, $process, $data, $userId);
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
            $context  = $abnormal->loadContext((int) $approveId, true);
            $list     = $abnormal->preview($list, $context['abnormal'], $context['handler']);
        }
        return $list;
    }

    /**
     * 获取流程节点数据.
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
                    $nextUnique = $this->verifyCondition($child, $data, $approveId);
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
     * @return null|BaseModel|BuildsQueries|false|mixed|Model|object
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function verifyCondition($condition, $data, $approveId)
    {
        $res = true;
        foreach ($condition['condition_list'] as $value) {
            if ($value['field']) {
                $val = $data[$value['field']] ?? '';
            } else {
                $val = '';
            }
            switch ($value['type']) {
                case CrudFormEnum::FORM_RADIO:
                case CrudFormEnum::FORM_CASCADER_RADIO:// 级联单选
                    $res = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_EQ     => $val === $value['option'],
                        CrudOperatorEnum::OPERATOR_NOT_EQ => $val != $value['option'],
                        CrudOperatorEnum::OPERATOR_IN     => in_array($val, collect($value['option'])->map(function ($item) {
                            return '/' . collect(explode('/', $item))->filter()->implode('/') . '/';
                        })->all()),
                        CrudOperatorEnum::OPERATOR_NOT_IN => ! in_array($val, collect($value['option'])->map(function ($item) {
                            return '/' . collect(explode('/', $item))->filter()->implode('/') . '/';
                        })->all()),
                        CrudOperatorEnum::OPERATOR_IS_EMPTY  => empty($val),
                        CrudOperatorEnum::OPERATOR_NOT_EMPTY => ! empty($val),
                    };
                    break;
                case CrudFormEnum::FORM_INPUT_SELECT:// 部门/人员
                    $res = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_EQ        => $val === $value['option'],
                        CrudOperatorEnum::OPERATOR_NOT_EQ    => $val != $value['option'],
                        CrudOperatorEnum::OPERATOR_IN        => in_array($val, $value['option']),
                        CrudOperatorEnum::OPERATOR_NOT_IN    => ! in_array($val, $value['option']),
                        CrudOperatorEnum::OPERATOR_IS_EMPTY  => empty($val),
                        CrudOperatorEnum::OPERATOR_NOT_EMPTY => ! empty($val),
                    };
                    break;
                case CrudFormEnum::FORM_INPUT:
                case CrudFormEnum::FORM_TEXTAREA:
                    $res = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_IN        => str_contains($value['option'], $val),
                        CrudOperatorEnum::OPERATOR_NOT_IN    => ! str_contains($value['option'], $val),
                        CrudOperatorEnum::OPERATOR_EQ        => $val == $value['option'],
                        CrudOperatorEnum::OPERATOR_NOT_EQ    => $val != $value['option'],
                        CrudOperatorEnum::OPERATOR_IS_EMPTY  => empty($val),
                        CrudOperatorEnum::OPERATOR_NOT_EMPTY => ! empty($val),
                    };
                    break;
                case CrudFormEnum::FORM_SWITCH:
                    $res = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_EQ        => $val == $value['option'],
                        CrudOperatorEnum::OPERATOR_IS_EMPTY  => empty($val),
                        CrudOperatorEnum::OPERATOR_NOT_EMPTY => ! empty($val),
                    };
                    break;
                case CrudFormEnum::FORM_INPUT_NUMBER:
                case CrudFormEnum::FORM_INPUT_FLOAT:
                case CrudFormEnum::FORM_INPUT_PERCENTAGE:
                    $res = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_EQ    => $val == $value['option'],
                        CrudOperatorEnum::OPERATOR_GT    => $val > $value['option'],
                        CrudOperatorEnum::OPERATOR_LT    => $val < $value['option'],
                        CrudOperatorEnum::OPERATOR_GT_EQ => $val >= $value['option'],
                        CrudOperatorEnum::OPERATOR_LT_EQ => $val <= $value['option'],
                        CrudOperatorEnum::OPERATOR_BT    => ($val >= $value['min']) && ($val <= $value['max']),
                    };
                    break;
                case CrudFormEnum::FORM_DATE_PICKER:
                case CrudFormEnum::FORM_DATE_TIME_PICKER:
                    $time = datetime_timestamp($val);
                    $res  = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_EQ       => $time == datetime_timestamp($value['option']),
                        CrudOperatorEnum::OPERATOR_GT       => $time > datetime_timestamp($value['option']),
                        CrudOperatorEnum::OPERATOR_LT       => $time < datetime_timestamp($value['option']),
                        CrudOperatorEnum::OPERATOR_BT       => ($time >= datetime_timestamp($value['option'])) && ($time <= datetime_timestamp($value['option'])),
                        CrudOperatorEnum::OPERATOR_N_DAY    => $value['option'] ? $time < datetime_timestamp('-' . $value['option'] . 'day') : $val == now()->toDateString(),
                        CrudOperatorEnum::OPERATOR_LAST_DAY => $value['option'] ? $time >= datetime_timestamp('-' . $value['option'] . 'day') && $time <= datetime_timestamp('+' . $value['option'] . 'day') : $val == now()->toDateString(),
                        CrudOperatorEnum::OPERATOR_NEXT_DAY => $value['option'] ? $time <= datetime_timestamp('+' . $value['option'] . 'day') && $time >= time() : $val == now()->toDateString(),
                        CrudOperatorEnum::OPERATOR_TO_DAY   => $val == now()->toDateString(),
                        CrudOperatorEnum::OPERATOR_WEEK     => Carbon::make($val)->week == now()->week && Carbon::make($val)->year == now()->year,
                        CrudOperatorEnum::OPERATOR_MONTH    => Carbon::make($val)->month == now()->month && Carbon::make($val)->year == now()->year,
                        CrudOperatorEnum::OPERATOR_QUARTER  => Carbon::make($val)->quarter == now()->quarter && Carbon::make($val)->year == now()->year,
                        CrudOperatorEnum::OPERATOR_YEAR     => Carbon::make($val)->year == now()->year,
                    };
                    break;
                case CrudFormEnum::FORM_CASCADER:// 级联复选
                case CrudFormEnum::FORM_TAG:// 标签选择
                case CrudFormEnum::FORM_CHECKBOX:// 复选
                case CrudFormEnum::FORM_CASCADER_ADDRESS:// 地址选择
                    $res = match ($value['value']) {
                        CrudOperatorEnum::OPERATOR_EQ        => ! array_diff($val, $value['option']),// 等于
                        CrudOperatorEnum::OPERATOR_NOT_EQ    => array_diff($val, $value['option']),// 不等于
                        CrudOperatorEnum::OPERATOR_IN        => array_intersect($val, $value['option']),// 包含
                        CrudOperatorEnum::OPERATOR_NOT_IN    => ! array_intersect($val, $value['option']),// 不包含
                        CrudOperatorEnum::OPERATOR_IS_EMPTY  => empty($val),// 为空
                        CrudOperatorEnum::OPERATOR_NOT_EMPTY => ! empty($val),// 不为空
                    };
                    break;
            }
            if (! $res) {
                return $res;
            }
        }
        return $this->dao->get(['approve_id' => $approveId, 'parent' => $condition['uniqued'], 'groups' => $condition['groups']])?->toArray() ?: [];
    }

    /**
     * 格式化节点配置.
     * @param mixed $userId
     * @param mixed $data
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function formatUserData($data, $userId)
    {
        $save                = $users = [];
        $save['types']       = $data['types'];
        $save['title']       = $data['name'];
        $save['uniqued']     = $data['uniqued'];
        $save['select_mode'] = $data['select_mode'];
        if ($data['types'] == 1) {
            $save['examine_mode'] = $data['examine_mode'];
            $save['pass_ratio']   = $data['pass_ratio'] ?? 0;
            $save['settype']      = $data['settype'];
            $save['no_hander']    = $data['no_hander'];
            switch ($data['settype']) {
                case 1:// 指定成员
                    if ($data['user_list'] && $card = array_column($data['user_list'], 'card')) {
                        $users = app()->get(AdminService::class)->select(['uid' => array_column($card, 'uid'), 'status' => 1], ['id', 'uid', 'name', 'avatar'])?->toArray();
                    } else {
                        $users = app()->get(AdminService::class)->select(['uid' => array_column($data['user_list'], 'uid'), 'status' => 1], ['id', 'uid', 'name', 'avatar'])?->toArray();
                    }
                    break;
                case 2:// 指定部门主管
                    $ups = app()->get(FrameService::class)->getLevelSuperUser($userId, 0, true);
                    if (! $data['director_order']) {
                        $ups = array_reverse($ups);
                    }
                    foreach ($ups as $k => $v) {
                        if ($data['director_level'] == $k + 1) {
                            if ($data['no_hander'] == 1 && ! $v && isset($ups[$k + 2])) {
                                $users[] = $ups[$k + 2];
                            } else {
                                $users[] = $v;
                            }
                        }
                    }
                    break;
                case 7:// 连续多部门
                    if ($data['director_order']) {
                        $users = app()->get(FrameService::class)->getLevelSuperUser($userId, $data['director_level'], true);
                    } else {
                        $ups           = app()->get(FrameService::class)->getLevelSuperUser($userId, 0, true);
                        $ups           = array_reverse($ups, false);
                        $ups && $users = array_slice($ups, 0, $data['director_level']);
                    }
                    break;
                case 5:// 申请人自己
                    $arr   = app()->get(AdminService::class)->get($userId, ['id', 'uid', 'name', 'avatar'])?->toArray();
                    $users = [
                        [
                            'id'      => $arr['id'],
                            'card_id' => $arr['id'],
                            'card'    => $arr,
                        ],
                    ];
                    break;
                case 4:// 申请人自选
                    $save['uniqued'] = $data['uniqued'];
                    $users           = [];
                    $save['options'] = $data['select_range'] > 1 ? $data['user_list'] : [];
                    break;
            }
        } else {
            $save['self_select'] = $data['self_select'];
            if ($data['dep_head']) {
                $ups = app()->get(FrameService::class)->getLevelSuperUser($userId, 0, true);
                if ($ups) {
                    foreach ($ups as $k => $v) {
                        if (in_array($k + 1, $data['dep_head'])) {
                            $users[] = $v;
                        }
                    }
                }
            }
            if ($data['user_list']) {
                if ($card = array_column($data['user_list'], 'card')) {
                    $user_list = app()->get(AdminService::class)->select(['uid' => array_column($card, 'uid'), 'status' => 1], ['id', 'uid', 'name', 'avatar'])?->toArray();
                } else {
                    $user_list = app()->get(AdminService::class)->select(['uid' => array_column($data['user_list'], 'uid'), 'status' => 1], ['id', 'uid', 'name', 'avatar'])?->toArray();
                }
                $users = array_merge($users, $user_list);
            }
            $users = assoc_unique($users, 'id');
        }
        $userIds       = app()->get(AdminService::class)->column(['status' => 1], 'id');
        $save['users'] = array_values(array_filter($users, function ($v) use ($userIds) {
            return is_array($v) && isset($v['id']) && in_array($v['id'], $userIds);
        }));
        return $save;
    }

    /**
     * 获取下一步条件列表.
     * @param mixed $approveId
     * @param mixed $unique
     * @return array|Collection
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNodeCondition(int $approveId, string $unique)
    {
        return Cache::tags([CacheEnum::TAG_APPROVE])->remember(md5($approveId . 'NodeCondition' . $unique), $this->cacheTtl, function () use ($approveId, $unique) {
            $sub = $this->dao->setDefaultSort(['priority' => 'asc'])->select(['approve_id' => $approveId, 'parent' => $unique, 'types' => 3])?->toArray() ?: [];
            if (! $sub) {
                $sub = $this->dao->value(['approve_id' => $approveId, 'parent' => $unique, 'types' => 4], 'uniqued');
                return $sub ? $this->dao->setDefaultSort(['priority' => 'asc'])->select(['approve_id' => $approveId, 'parent' => $sub, 'types' => 3])->toArray() : [];
            }
            return $sub;
        });
    }

    /**
     * 处理流程配置.
     * @param mixed $userId
     * @param mixed $data
     * @return mixed
     */
    public function checkProcessConfig($data, int $userId, string $type = 'processConfig'): array
    {
        if (! isset($data[$type]) || ! $data[$type]) {
            return [];
        }
        return $this->getSerializeData($data[$type], $userId);
    }

    /**
     * 初始化流程配置数据.
     * @param int|mixed $group
     * @param mixed $userId
     * @param mixed $data
     */
    public function getSerializeData($data, int $userId, int $level = 0, string $onlyValue = '', int $is_initial = 1, int $group = 0): array
    {
        $info[] = $this->getInfo($data, $userId, $onlyValue, $level, $is_initial, $group);
        if ($data['childNode']) {
            ++$level;
            $info = array_merge($info, $this->getSerializeData($data['childNode'], $userId, $level, $data['onlyValue'], 0, $group));
        }
        if (isset($data['conditionNodes']) && $data['conditionNodes']) {
            foreach ($data['conditionNodes'] as $v) {
                ++$group;
                ++$level;
                $info[] = $this->getInfo($v, $userId, $data['onlyValue'], $level, 0, $group);
                if ($v['childNode']) {
                    $info = array_merge($info, $this->getSerializeData($v['childNode'], $userId, $level, $v['onlyValue'], 0, $group));
                }
            }
        }
        return $info;
    }

    /**
     * 组合流程数据.
     * @param mixed $userId
     * @param mixed $data
     */
    public function getInfo($data, int $userId, string $parent = '', int $level = 0, int $is_initial = 0, int $group = 0): array
    {
        return [
            // 节点名称（申请人、审核人、抄送人）
            'name' => $data['nodeName'],
            // 节点类型：0、申请人；1、审核人；2、抄送人；3、条件；4、路由；
            'types'   => $data['type'],
            'uniqued' => $data['onlyValue'], // 节点唯一值
            // 审核人类型：1、指定成员；2、指定部门主管；7、连续多部门；5、申请人自己；4、申请人自选；(0、无此条件)
            'settype' => isset($data['settype']) && $data['settype'] ? $data['settype'] : 0,
            // 指定层级顺序：0、从上至下；1、从下至上；(-1、无此条件)
            'director_order' => $data['directorOrder'] ?? -1,
            // 指定主管层级/指定终点层级：1-10；(0、无此条件)
            'director_level' => isset($data['directorLevel']) && $data['directorLevel'] ? $data['directorLevel'] : 0,
            // 当前部门无负责人时：1、上级部门负责人审批；2、为空时跳过；(0、无此条件)
            'no_hander' => isset($data['noHanderAction']) && $data['noHanderAction'] ? $data['noHanderAction'] : 0,
            // 可选范围：1、不限范围；2、指定成员；(0、无此条件)
            'select_range' => isset($data['selectRange']) && $data['selectRange'] ? $data['selectRange'] : 0,
            // 指定的成员列表
            'user_list' => isset($data['nodeUserList']) && $data['nodeUserList'] ? $data['nodeUserList'] : [],
            // 选人方式：1、单选；2、多选；(0、无此条件)
            'select_mode' => isset($data['selectMode']) && $data['selectMode'] ? $data['selectMode'] : 0,
            // 多人审批方式：1、或签；2、会签；3、依次审批；(0、无此条件)
            'examine_mode' => isset($data['examineMode']) && $data['examineMode'] ? $data['examineMode'] : 0,
            // 通过比例(%)：仅在 examine_mode=1（或签）时有效，0=关闭（原逻辑）
            'pass_ratio' => isset($data['passRatio']) ? (int) $data['passRatio'] : ($data['pass_ratio'] ?? 0),
            // 条件优先级
            'priority'       => isset($data['priorityLevel']) && $data['priorityLevel'] ? $data['priorityLevel'] : 0,
            'parent'         => $parent, // 父级唯一值
            'level'          => $level,
            'info'           => $is_initial > 0 ? $data : [],
            'is_initial'     => $is_initial,
            'is_child'       => $data['childNode'] ? 1 : 0,
            'is_condition'   => isset($data['conditionNodes']) && $data['conditionNodes'] ? 1 : 0,
            'user_id'        => $userId,
            'groups'         => $group,
            'condition_list' => $data['conditionList'] ?? [],
            // 指定部门负责人
            'dep_head' => $data['departmentHead'] ?? [],
            // 是否允许自选抄送人
            'self_select' => $data['ccSelfSelectFlag'] ?? 0,
        ];
    }

    /**
     * 获取上级分组节点人员.
     * @param mixed $userId
     * @param mixed $approveId
     * @param mixed $nextUniqued
     * @param mixed $uniqueds
     * @param mixed $data
     * @param mixed $userNodes
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getTopNodes($approveId, $nextUniqued, $uniqueds, $data, $userId, $userNodes)
    {
        $topUniqued = $this->dao->value(['parent' => $uniqueds, 'groups' => $nextUniqued['groups'] - 1], 'uniqued');
        if ($topUniqued && ($topNode = $this->dao->get(['approve_id' => $approveId, 'uniqued' => $topUniqued])) && ! empty($topNode)) {
            $userNodes[] = $this->formatUserData($topNode->toArray(), $userId);
            if ($this->dao->exists(['parent' => $uniqueds])) {
                $userNodes = $this->getSubNode($approveId, $topUniqued, $userId, $userNodes, $data);
            }
            if ($this->dao->exists(['parent' => $uniqueds, 'groups' => $topNode['groups'] - 1])) {
                return $this->getTopNodes($approveId, $topNode, $uniqueds, $data, $userNodes, $userId);
            }
        }
        return $userNodes;
    }

    /**
     * 获取人员节点下级.
     * @param mixed $approveId
     * @param mixed $uniqued
     * @param mixed $userId
     * @param mixed $nodes
     * @param mixed $data
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function getSubNode($approveId, $uniqued, $userId, $nodes = [], $data = [])
    {
        $info = $this->dao->get(['approve_id' => $approveId, 'parent' => $uniqued, 'types' => [1, 2]])?->toArray();
        if ($info) {
            if (in_array($info['types'], [1, 2])) {
                $nodes[] = $this->formatUserData($info, $userId);
                return $this->getNodeList($approveId, $info['uniqued'], $data, $userId, $nodes);
            }
        }
        return $nodes;
    }
}
