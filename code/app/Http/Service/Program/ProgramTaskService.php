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

namespace App\Http\Service\Program;

use App\Constants\ModuleEnum;
use App\Constants\ProgramEnum\DynamicEnum;
use App\Constants\ProgramEnum\TaskPriorityEnum;
use App\Constants\ProgramEnum\TaskStatusEnum;
use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use App\Http\Dao\Program\ProgramTaskDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目任务
 * Class ProgramTaskService.
 */
class ProgramTaskService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(ProgramTaskDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param null|mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->setDefaultSort(sort_mode($sort))->select($where, $field, $with)->each(function (&$item) {
            $item['operate'] = true;
        })?->toArray();
        $list  = get_tree_children($list);
        $count = count($list);
        $list  = array_slice($list, ($page - 1) * $limit, $limit);
        return $this->listData($list, $count);
    }

    /**
     * 保存任务
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveTask(array $data, int $uid): mixed
    {
        $data['pid']   = 0;
        $data['level'] = 1;
        $data['ident'] = $this->generateIdent();
        $members       = $data['members'];
        unset($data['members']);
        if ($data['path']) {
            $data['pid'] = (int) last($data['path']);
        }
        if ($data['plan_start'] && $data['plan_end'] && Carbon::parse($data['plan_start'])->startOfDay()->gt(Carbon::parse($data['plan_end']))) {
            throw $this->exception('开始时间不能大于结束时间');
        }
        if ($data['pid'] > 0) {
            $parent = $this->dao->get(['program_id' => $data['program_id'], 'id' => $data['pid']]);
            if (! $parent) {
                throw $this->exception('请重新选择父级任务');
            }
            if ($parent->level >= 4) {
                throw $this->exception('最多支持4级任务');
            }
            if ($data['program_id'] != $parent->program_id) {
                throw $this->exception('当前任务存在父级任务，无法修改关联项目');
            }
            $data['level']  = $parent->level + 1;
            $data['top_id'] = $parent->top_id;
        }
        if (! app()->get(ProgramService::class)->exists($data['program_id'])) {
            throw $this->exception('请重新选择关联项目');
        }
        if ($data['version_id'] && ! app()->get(ProgramVersionService::class)->exists(['id' => $data['version_id'], 'program_id' => $data['program_id']])) {
            throw $this->exception('请重新选择关联版本');
        }
        $programMember = app()->get(ProgramMemberService::class)->column(['program_id' => $data['program_id']], 'uid');
        if ($data['uid'] && ! in_array($data['uid'], $programMember)) {
            throw $this->exception('请重新选择负责人');
        }
        if ($members && array_diff($members, $programMember)) {
            throw $this->exception('请重新选择协作者');
        }
        $data['sort'] = $this->dao->max(['pid' => $data['pid']], 'sort') + 1;
        return $this->transaction(function () use ($data, $members, $uid) {
            $res = $this->dao->create(array_merge($data, ['creator_uid' => $uid]));
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }
            if ($members) {
                app()->get(ProgramTaskMemberService::class)->handleMember($members, $res->id);
            }
            $this->syncTaskTodo((int) $res->id, 'update', [(int) $res->uid, ...$members]);
            // program dynamic
            app()->get(ProgramDynamicService::class)->addLog(DynamicEnum::TASK, DynamicEnum::CREATED_ACTION, [
                'uid'         => $uid,
                'relation_id' => $res->id,
                'title'       => '新建了 <b>任务</b>',
            ]);
            return $res;
        });
    }

    /**
     * 保存下级任务
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveSubordinateTask(array $data, int $uid): mixed
    {
        $data['level'] = 1;
        $data['ident'] = $this->generateIdent();
        $parent        = $this->dao->get(['id' => $data['pid']]);
        if (! $parent) {
            throw $this->exception('请选择父级任务');
        }
        if ($parent->level >= 4) {
            throw $this->exception('最多支持4级任务');
        }
        $planDate           = now()->toDateString();
        $data['status']     = 0;
        $data['priority']   = 0;
        $data['level']      = $parent->level + 1;
        $data['top_id']     = $parent->level == 1 ? $parent->id : $parent->top_id;
        $data['path']       = array_merge($parent->path, [$parent->id]);
        $data['uid']        = $parent->uid;
        $data['program_id'] = $parent->program_id;
        $data['version_id'] = $parent->version_id;
        $data['plan_start'] = $planDate;
        $data['plan_end']   = $planDate;
        $data['sort']       = $this->dao->max(['pid' => $data['pid']], 'sort') + 1;
        return $this->transaction(function () use ($data, $uid) {
            $res = $this->dao->create(array_merge($data, ['creator_uid' => $uid]));
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }
            $this->syncTaskTodo((int) $res->id, 'update', [(int) $res->uid]);
            // program dynamic
            app()->get(ProgramDynamicService::class)->addLog(DynamicEnum::TASK, DynamicEnum::CREATED_ACTION, [
                'uid'         => $uid,
                'relation_id' => $res->id,
                'title'       => '新建了 <b>任务</b>',
            ]);
            return $res;
        });
    }

    /**
     * 修改任务
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateTask(array $data, string $field, int $id): mixed
    {
        if (! isset($data[$field]) && $field != 'plan_date') {
            throw $this->exception('修改的数据不存在');
        }

        $task = toArray($this->dao->get($id));
        if (! $task) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $uid     = auth('admin')->id();
        $members = app()->get(ProgramMemberService::class)->getMemberIdsByUid($uid);
        if (! in_array($uid, array_filter(array_merge($members, [$task['creator_uid'], $task['uid']])))) {
            throw $this->exception('您没有权限修改该任务');
        }

        return $this->transaction(function () use ($id, $field, $task, $data) {
            $value  = '';
            $update = $programMember = [];
            if ($field != 'plan_date') {
                $value = $data[$field];
                if ($field == 'version_id' && $value && ! app()->get(ProgramVersionService::class)->exists(['id' => $value, 'program_id' => $task['program_id']])) {
                    throw $this->exception('请重新选择关联版本');
                }

                $programMember = app()->get(ProgramMemberService::class)->column(['program_id' => $field == 'program_id' ? (int) $value : $task['program_id']], 'uid');
                if ($field == 'members' && array_diff($value, $programMember)) {
                    throw $this->exception('请重新选择协作者');
                }

                if ($field == 'uid' && $value && ! in_array($value, $programMember)) {
                    throw $this->exception('请重新选择负责人');
                }

                $before = $field == 'members' ? app()->get(ProgramTaskMemberService::class)->column(['task_id' => $id], 'uid') : $task[$field];
            }
            switch ($field) {
                case 'program_id':
                    if ($task[$field] != $value) {
                        if ($task['pid'] > 0) {
                            throw $this->exception('当前任务存在父级任务，无法修改关联项目');
                        }

                        if ($task['uid'] != 0 && ! in_array($task['uid'], $programMember)) {
                            $update['uid'] = 0;
                            $this->combinationDynamic($task, 'uid', $task['uid'], 0);
                        }

                        $update['version_id'] = 0;
                        $this->combinationDynamic($task, 'version_id', $task['version_id'], 0);
                        $this->updateMembersById($task, $programMember);
                        $this->updateByPid(array_merge($task, $update), $programMember);
                        $update[$field] = $value;
                    }
                    break;
                case 'pid':
                    $update = ['pid' => 0, 'level' => 1, 'path' => [], 'top_id' => 0];
                    if ($value > 0) {
                        $parent = $this->dao->get($value);
                        if (! $parent) {
                            throw $this->exception('请重新选择父级任务');
                        }

                        if ($parent->id == $id || in_array($id, $parent->path) || $task['program_id'] != $parent->program_id) {
                            throw $this->exception('请重新选择父级任务');
                        }

                        if ($parent->level >= 4) {
                            throw $this->exception('最多支持4级任务');
                        }

                        $update = [
                            'level'  => $parent->level + 1,
                            'pid'    => $parent->id,
                            'path'   => array_merge($parent->path, [$parent->id]),
                            'top_id' => $parent->level == 1 ? $parent->id : $parent->top_id,
                        ];

                        $this->updateMembersById($task, $programMember);
                        $this->updateByPid(array_merge($task, $update), $programMember);
                    }
                    break;
                case 'members':
                    $this->updateMembersById($task, $value, false);
                    break;
                case 'plan_date':
                    $tz = config('app.timezone');
                    if ($data['plan_start'] && $data['plan_end']) {
                        if (Carbon::parse($data['plan_start'], $tz)->startOfDay()->gt(Carbon::parse($data['plan_end'], $tz))) {
                            throw $this->exception('请重新选择计划时间, 开始时间需要小于结束时间');
                        }

                        $update = ['plan_start' => $data['plan_start'], 'plan_end' => $data['plan_end']];
                    } else {
                        $update = ['plan_start' => null, 'plan_end' => null];
                    }
                    if ($data['plan_start'] != $task['plan_start']) {
                        $this->combinationDynamic($task, 'plan_start', $task['plan_start'], $data['plan_start']);
                    }

                    if ($data['plan_end'] != $task['plan_end']) {
                        $this->combinationDynamic($task, 'plan_end', $task['plan_end'], $data['plan_end']);
                    }
                    break;
                case 'plan_start':
                    $tz     = config('app.timezone');
                    $update = [$field => $value];
                    if ($value && $task['plan_end'] && Carbon::parse($value, $tz)->gt($task['plan_end'])) {
                        $update['plan_end'] = null;
                        $this->combinationDynamic($task, 'plan_end', $task['plan_end'], null);
                    }
                    $before = $task['plan_start'];
                    break;
                case 'plan_end':
                    $tz     = config('app.timezone');
                    $update = [$field => $value];
                    if ($value && $task['plan_start'] && Carbon::parse($task['plan_start'], $tz)->gt($value)) {
                        $update['plan_start'] = null;
                        $this->combinationDynamic($task, 'plan_start', $task['plan_start'], null);
                    }
                    $before = $task['plan_end'];
                    break;
                default:
                    $update = [$field => $value];
                    break;
            }

            if ($update) {
                $res = $this->dao->update($id, $update);
                if (! $res) {
                    throw $this->exception(__('common.update.fail'));
                }
                if ($field === 'status') {
                    $this->finishTaskTodoWhenClosed((int) $id, (int) $value);
                } elseif ($field === 'uid') {
                    $this->syncTaskTodo((int) $id, 'update', [
                        (int) ($task['uid'] ?? 0),
                        (int) $value,
                        ...app()->get(ProgramTaskMemberService::class)->column(['task_id' => $id], 'uid'),
                    ]);
                }
                // task dynamic
                $field != 'plan_date' && $this->combinationDynamic($task, $field, $before, $value);
            }
            return true;
        });
    }

    /**
     * 生成操作动态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function combinationDynamic(array $task, string $field, mixed $before, mixed $after): void
    {
        if (($field == 'members' && array_diff($before, $after) == array_diff($after, $before)) || $before == $after) {
            return;
        }
        app()->get(ProgramDynamicService::class)->addLog(DynamicEnum::TASK, DynamicEnum::UPDATE_ACTION, [
            'relation_id' => $task['id'],
            'uid'         => uuid_to_uid($this->uuId(false)),
            'title'       => $this->getDynamicTitle($field, $before, $after),
            'describe'    => $this->getUpdateDescribe($field, $before, $after),
        ]);
    }

    /**
     * 获取动态标题.
     */
    public function getDynamicTitle(string $field, mixed $before, mixed $after): string
    {
        $method = 'dynamic' . Str::studly($field);
        return method_exists($this, $method) ? $this->{$method}(DynamicEnum::getTaskFieldText($field), $before, $after) : '';
    }

    /**
     * 获取修改描述.
     * @return array|array[]
     */
    public function getUpdateDescribe(string $field, mixed $before, mixed $after): array
    {
        return match ($field) {
            'name' => [
                ['title' => '修改前：', 'value' => $before],
                ['title' => '修改后：', 'value' => $after],
            ],
            'describe' => [
                ['title' => '修改前：', 'value' => $before],
                ['title' => '修改后：', 'value' => $after ? stripslashes(htmlspecialchars_decode($after)) : ''],
            ],
            default => []
        };
    }

    /**
     * 关联管理员动态
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dynamicUid(string $name, mixed $before, mixed $after): string
    {
        $userService = app()->get(AdminService::class);
        return '将 <b>' . $name . '</b> 从 <b>' . ($userService->value((int) $before, 'name') ?: '空')
               . '</b> 修改为 <b>' . ($userService->value((int) $after, 'name') ?: '空') . '</b>';
    }

    /**
     * 关联名称动态
     */
    public function dynamicName(string $name, mixed $before, mixed $after): string
    {
        return '修改了 <b>' . $name . '</b>';
    }

    /**
     * 关联描述动态
     */
    public function dynamicDescribe(string $name, mixed $before, mixed $after): string
    {
        return '修改了 <b>' . $name . '</b>';
    }

    /**
     * 关联状态动态
     */
    public function dynamicStatus(string $name, mixed $before, mixed $after): string
    {
        return '将 <b>' . $name . '</b> 从 <b>' . TaskStatusEnum::getStatusText((int) $before) . '<b> 修改为 <b>'
               . TaskStatusEnum::getStatusText((int) $after) . '<b>';
    }

    /**
     * 关联协作者动态
     * @param null|mixed $before
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dynamicMembers(string $name, mixed $before, mixed $after): string
    {
        $userService = app()->get(AdminService::class);
        $beforeNames = implode('、', $userService->column(['id' => $before], 'name')) ?: '空';
        $afterNames  = implode('、', $userService->column(['id' => $after], 'name')) ?: '空';
        return '将 <b>' . $name . '</b> 从 <b>' . $beforeNames . '</b> 修改为 <b>' . $afterNames . '</b>';
    }

    /**
     * 关联计划开始动态
     */
    public function dynamicPlanStart(string $name, mixed $before, mixed $after): string
    {
        return '将 <b>' . $name . '</b> 从 <b>' . ($before ?: '空') . '</b> 修改为 <b>' . ($after ?: '空') . '</b>';
    }

    /**
     * 关联计划结束动态
     */
    public function dynamicPlanEnd(string $name, mixed $before, mixed $after): string
    {
        return '将 <b>' . $name . '</b> 从 <b>' . ($before ?: '空') . '</b> 修改为 <b>' . ($after ?: '空') . '</b>';
    }

    /**
     * 关联父级任务动态
     * @throws BindingResolutionException
     */
    public function dynamicPid(string $name, mixed $before, mixed $after): string
    {
        return '将 <b>' . $name . '</b> 从 <b>' . ($this->dao->value((int) $before, 'name') ?: '空')
               . '</b> 修改为 <b>' . ($this->dao->value((int) $after, 'name') ?: '空') . '</b>';
    }

    /**
     * 关联项目动态
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dynamicProgramId(string $name, mixed $before, mixed $after): string
    {
        $service = app()->get(ProgramService::class);
        return '将 <b>' . $name . '</b> 从 <b>' . ($service->value((int) $before, 'name') ?: '空') . '</b> 修改为 <b>'
               . ($service->value((int) $after, 'name') ?: '空') . '</b>';
    }

    /**
     * 关联版本动态
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dynamicVersionId(string $name, mixed $before, mixed $after): string
    {
        $service = app()->get(ProgramVersionService::class);
        return '将 <b>' . $name . '</b> 从 <b>' . ($service->value((int) $before, 'name') ?: '空') . '</b> 修改为 <b>'
               . ($service->value((int) $after, 'name') ?: '空') . '</b>';
    }

    /**
     * 关联优先级动态
     */
    public function dynamicPriority(string $name, mixed $before, mixed $after): string
    {
        return '将 <b>' . $name . '</b> 从 <b>' . TaskPriorityEnum::getPriorityText((int) $before)
               . '<b> 修改为 <b>' . TaskPriorityEnum::getPriorityText((int) $after) . '<b>';
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(int $id): array
    {
        $program = toArray($this->dao->get($id, ['*'], ['admins', 'members', 'program', 'version', 'creator', 'parent']));
        if (! $program) {
            throw $this->exception(__('common.operation.noExists'));
        }
        $program['operate'] = true;

        return $program;
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteTask(int $id): void
    {
        $this->batchDel([$id]);
    }

    /**
     * 生成编号.
     */
    public function generateIdent(int $len = 7): string
    {
        do {
            $ident = strtolower(Str::random($len));
        } while ($this->exists(['ident' => $ident]));
        return $ident;
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelectList(array $where, int $uid): array
    {
        $field      = ['id as value', 'name as label', 'level', 'pid'];
        $uids       = app()->get(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::PROGRAM);
        $programIds = app()->get(ProgramService::class)->column(['uid' => $uids, 'admin_uid' => $uid, 'types' => ''], 'id', 'id');
        if (! $where['program_id']) {
            $where['program_id'] = $programIds;
        } else {
            if (! in_array($where['program_id'], $programIds)) {
                $where['program_id'] = 0;
            }
        }
        $list = $this->dao->getList($where, $field, 0, 0, 'id', [], function ($list) {
            foreach ($list as $item) {
                if ($item->level == 4) {
                    $item['disabled'] = true;
                }
            }
        });
        return get_tree_children($list, 'children', 'value');
    }

    /**
     * 批量更新.
     * @return true|void
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function batchUpdate(array $batchData, int $uid)
    {
        $data = array_filter(array_unique($batchData['data']));
        unset($batchData['data']);
        $list = $this->dao->column(['id' => $data, 'auth_uid' => $uid], ['id']);
        if (empty($list)) {
            return true;
        }
        $tz = config('app.timezone');
        if ($batchData['start_date'] && $batchData['end_date'] && Carbon::parse($batchData['start_date'], $tz)->startOfDay()->gt($batchData['end_date'])) {
            throw $this->exception('开始时间不能大于结束时间');
        }
        if (($batchData['pid'] > 0 || $batchData['version_id'] > 0 || $batchData['uid'] > 0) && $batchData['program_id'] < 1) {
            throw $this->exception('请选择关联项目');
        }
        return $this->transaction(function () use ($data, $batchData) {
            foreach ($batchData as $key => $item) {
                $method = 'update' . Str::studly($key);
                if (method_exists($this, $method) && $item !== '') {
                    $this->{$method}($data, $item, $batchData);
                }
            }
        });
    }

    /**
     * 批量删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchDel(array $data, bool $forceDel = false): void
    {
        if (empty($data)) {
            return;
        }

        $this->transaction(function () use ($data, $forceDel) {
            $uid            = uuid_to_uid($this->uuId(false));
            $dynamicService = app()->get(ProgramDynamicService::class);
            foreach ($data as $id) {
                $info = toArray($this->dao->get(['id' => $id]));
                if (! $info) {
                    continue;
                }
                $program = app()->get(ProgramService::class)->setTrashed()->get($info['program_id']);
                if (! $program) {
                    continue;
                }

                if (! $forceDel && ! in_array($uid, array_filter([$program['uid'], $program['creator_uid'], $info['creator_uid']]))) {
                    throw $this->exception('删除失败，您暂无权限删除！');
                }

                $res = $this->dao->delete($id);
                if (! $res) {
                    throw $this->exception(__('common.delete.fail'));
                }

                // task dynamic
                $dynamicService->addLog(DynamicEnum::TASK, DynamicEnum::DELETE_ACTION, [
                    'uid'         => $uid,
                    'relation_id' => $id,
                    'title'       => '删除了任务 </b>' . $info['name'] . '</b>',
                ]);

                $delIds   = [$id];
                $children = $this->dao->column(['path' => $id], 'name', 'id');
                if ($children) {
                    $delIds = array_merge(array_keys($children), $delIds);
                    foreach ($children as $name) {
                        $dynamicService->addLog(DynamicEnum::TASK, DynamicEnum::DELETE_ACTION, [
                            'uid'         => $uid,
                            'relation_id' => $id,
                            'title'       => '删除了任务 </b>' . $name . '</b>',
                        ]);
                    }
                }

                // destroy children
                $this->dao->delete(['path' => $id]);

                // destroy task comment
                app()->get(ProgramTaskCommentService::class)->delete(['task_id' => $delIds]);

                // destroy task member
                app()->get(ProgramTaskMemberService::class)->delete(['task_id' => $delIds]);
                foreach ($delIds as $delId) {
                    $this->syncTaskTodo((int) $delId, 'delete');
                }
            }
        });
    }

    /**
     * 项目成员变更移除人员.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function removeMemberByProgramId(int $programId, array $removeMembers): void
    {
        $memberService = app()->get(ProgramTaskMemberService::class);
        $list          = $this->dao->select(['program_id' => $programId])->toArray();
        foreach ($list as $task) {
            $members    = $memberService->column(['task_id' => $task['id']], 'uid');
            $delMembers = array_intersect($members, $removeMembers);
            if (! $delMembers) {
                continue;
            }

            $memberService->delete(['task_id' => $task['id'], 'uid' => $delMembers]);
            $this->syncTaskTodo((int) $task['id'], 'update', [(int) ($task['uid'] ?? 0), ...$members]);
            $this->combinationDynamic($task, 'members', $members, array_diff($members, $removeMembers));
        }
    }

    /**
     * 排序.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sort(int $currentId, int $targetId): void
    {
        if ($currentId == $targetId) {
            return;
        }

        $this->transaction(function () use ($currentId, $targetId) {
            $currTask = $this->dao->get(['id' => $currentId]);
            $tagTask  = $this->dao->get(['id' => $targetId]);
            if ($currTask->pid != $tagTask->pid) {
                throw $this->exception('仅支持兄弟之间进行排序');
            }
            $temp           = $tagTask->sort;
            $tagTask->sort  = $currTask->sort;
            $currTask->sort = $temp;
            $currTask->save();
            $tagTask->save();
        });
    }

    /**
     * 获取分享详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getShareInfo(string $ident): array
    {
        $program = toArray($this->dao->get(['ident' => $ident], ['*'], ['admins', 'members', 'program', 'version', 'creator', 'parent']));
        if (! $program) {
            throw $this->exception(__('common.operation.noExists'));
        }
        $program['operate'] = true;

        return $program;
    }

    /**
     * pid 更新.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function updatePid(array|int $id, mixed $pid, array $otherData = []): void
    {
        $pid    = (int) $pid;
        $parent = $this->dao->get(['id' => $pid, 'program_id' => $otherData['program_id']]);
        if (! $parent) {
            throw $this->exception('父级任务不存在');
        }

        if ($parent->level >= 4) {
            throw $this->exception('最多支持4级任务');
        }

        $list = $this->select(['id' => $id, 'program_id' => $otherData['program_id'], 'pid_not' => $pid])->toArray();
        $this->transaction(function () use ($list, $pid, $parent) {
            $updateIds = [];
            foreach ($list as $task) {
                if ($parent->id == $task['id'] || in_array($task['id'], $parent->path)) {
                    continue;
                }

                $updateIds[] = $task['id'];

                // task dynamic
                $this->combinationDynamic($task, 'pid', $task['pid'], $pid);
            }

            if (! $updateIds) {
                return;
            }

            $update = [
                'pid'    => $pid,
                'level'  => $parent->level + 1,
                'path'   => array_merge($parent->path, [$pid]),
                'top_id' => $parent->level == 1 ? $parent->id : $parent->top_id,
            ];

            if ($update['level'] >= 4) {
                throw $this->exception('最多支持4级任务');
            }

            $programMember = app()->get(ProgramMemberService::class)->column(['program_id' => $parent->program_id], 'uid');
            foreach ($updateIds as $id) {
                $this->dao->update(['id' => $id], array_merge($update, [
                    'sort' => $this->dao->max(['pid' => $pid], 'sort') + 1,
                ]));
                $this->updateByPid(array_merge(['id' => $id], $update), $programMember);
            }
        });
    }

    /**
     * status 更新.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function updateStatus(array|int $id, mixed $status, array $otherData = []): void
    {
        $status = (int) $status;
        if (! in_array($status, [0, 1, 2, 3, 4, 5])) {
            throw $this->exception('状态不正确');
        }
        $list = $this->select(['id' => $id, 'status_not' => $status])->toArray();
        $this->transaction(function () use ($list, $status) {
            foreach ($list as $task) {
                // task dynamic
                $this->combinationDynamic($task, 'status', $task['status'], $status);
            }
            $this->dao->update(['id' => array_column($list, 'id')], ['status' => $status]);

            foreach ($list as $task) {
                $this->finishTaskTodoWhenClosed((int) $task['id'], $status);
            }
        });
    }

    protected function finishTaskTodoWhenClosed(int $taskId, int $status): void
    {
        if (! in_array($status, [
            TaskStatusEnum::RESOLVED,
            TaskStatusEnum::ACCEPTED,
            TaskStatusEnum::REJECTED,
            TaskStatusEnum::CLOSED,
        ], true)) {
            return;
        }

        $this->syncTaskTodo($taskId, 'complete');
    }

    /**
     * uid 更新.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function updateUid(array|int $id, mixed $uid, array $otherData = []): void
    {
        $uid = (int) $uid;
        if (! in_array($uid, app()->get(ProgramMemberService::class)->column(['program_id' => $otherData['program_id']], 'uid'))) {
            throw $this->exception('项目负责人不存在');
        }

        $list = $this->select(['id' => $id, 'program_id' => $otherData['program_id'], 'uid_not' => $uid])->toArray();
        $this->transaction(function () use ($list, $uid) {
            foreach ($list as $task) {
                // task dynamic
                $this->combinationDynamic($task, 'uid', $task['uid'], $uid);
            }
            $this->dao->update(['id' => array_column($list, 'id')], ['uid' => $uid]);
            foreach ($list as $task) {
                $this->syncTaskTodo((int) $task['id'], 'update', [(int) $task['uid'], $uid]);
            }
        });
    }

    /**
     * 更新项目ID.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function updateProgramId(array|int $id, mixed $programId, array $otherData = []): void
    {
        $programId = (int) $programId;
        $program   = app()->get(ProgramService::class)->get($programId);
        if (! $program) {
            throw $this->exception('项目不存在');
        }
        $parent = null;
        if ($otherData['pid'] > 0) {
            $parent = $this->dao->get(['id' => $otherData['pid'], 'program_id' => $programId]);
            if (! $parent) {
                throw $this->exception('父级任务不存在');
            }

            if ($parent->level >= 4) {
                throw $this->exception('最多支持4级任务');
            }
        }

        $list = $this->dao->column(['id' => $id, 'program_id_not' => $programId], 'id');
        if (! $list) {
            return;
        }

        $this->transaction(function () use ($list, $programId, $parent) {
            $programMember = app()->get(ProgramMemberService::class)->column(['program_id' => $programId], 'uid');
            foreach ($list as $id) {
                $task = toArray($this->dao->get($id));
                if ($task['program_id'] == $programId) {
                    continue;
                }
                $update = ['program_id' => $programId, 'version_id' => 0, 'pid' => 0, 'top_id' => 0, 'path' => [], 'level' => 1];
                if ($parent) {
                    $update['pid']    = $parent->id;
                    $update['top_id'] = $parent->level == 1 ? $parent->id : $parent->top_id;
                    $update['path']   = array_merge($parent->path, [$parent->id]);
                    $update['level']  = $parent->level + 1;
                    $update['uid']    = $parent->uid;
                } else {
                    $update['uid'] = $task['uid'] > 0 && ! in_array($task['uid'], $programMember) ? 0 : $task['uid'];
                }
                $this->combinationDynamic($task, 'program_id', $task['program_id'], $programId);
                if ($task['uid'] != $update['uid']) {
                    $this->combinationDynamic($task, 'uid', $task['uid'], $update['uid']);
                }

                if ($task['pid'] != $update['pid']) {
                    $update['sort'] = $this->dao->max(['pid' => $update['pid']], 'sort') + 1;
                    $this->combinationDynamic($task, 'pid', $task['pid'], $update['pid']);
                }

                $this->dao->update(['id' => $task['id']], $update);

                $this->updateMembersById($task, $programMember);

                $this->updateByPid(array_merge($task, $update), $programMember);
            }
        });
    }

    /**
     * version_id 更新.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function updateVersionId(array|int $id, mixed $versionId, array $otherData = []): void
    {
        if (! app()->get(ProgramVersionService::class)->exists(['program_id' => $otherData['program_id'], 'id' => $versionId])) {
            throw $this->exception('版本不存在');
        }

        $list = $this->dao->select(['id' => $id, 'program_id' => $otherData['program_id'], 'version_id_not' => $versionId])->toArray();
        $this->transaction(function () use ($list, $versionId) {
            foreach ($list as $task) {
                // task dynamic
                $this->combinationDynamic($task, 'version_id', $task['version_id'], $versionId);
            }
            $this->dao->update(['id' => array_column($list, 'id')], ['version_id' => $versionId]);
        });
    }

    /**
     * plan_start 更新.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function updateStartDate(array|int $id, mixed $planStart, array $otherData = []): void
    {
        $tz   = config('app.timezone');
        $date = Carbon::parse($planStart, $tz)->toDateString();
        $list = $this->select(['id' => $id, 'program_id' => $otherData['program_id'], 'plan_start_not_or_null' => $date])->toArray();
        $this->transaction(function () use ($list, $date, $tz) {
            foreach ($list as $task) {
                $update = ['plan_start' => $date];
                if ($task['plan_end'] && Carbon::parse($date, $tz)->gt($task['plan_end'])) {
                    $update['plan_end'] = null;
                    $this->combinationDynamic($task, 'plan_end', $task['plan_end'], null);
                }

                // task dynamic
                $this->combinationDynamic($task, 'plan_start', $task['plan_start'], $date);
                $this->dao->update(['id' => $task['id']], $update);
            }
        });
    }

    /**
     * plan_end 更新.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function updateEndDate(array|int $id, mixed $planEnd, array $otherData = []): void
    {
        $tz   = config('app.timezone');
        $date = Carbon::parse($planEnd, $tz)->toDateString();
        $list = $this->select(['id' => $id, 'program_id' => $otherData['program_id'], 'plan_end_not_or_null' => $date])->toArray();
        $this->transaction(function () use ($list, $date, $tz) {
            foreach ($list as $task) {
                $update = ['plan_end' => $date];
                if ($task['plan_start'] && Carbon::parse($task['plan_start'], $tz)->gt($date)) {
                    $update['plan_start'] = null;
                    $this->combinationDynamic($task, 'plan_start', $task['plan_start'], null);
                }

                // task dynamic
                $this->combinationDynamic($task, 'plan_end', $task['plan_end'], $date);
                $this->dao->update(['id' => $task['id']], $update);
            }
        });
    }

    /**
     * 更新成员数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function updateMembersById(array $task, array $member, bool $intersect = true): void
    {
        $memberService = app()->get(ProgramTaskMemberService::class);
        $taskMember    = $memberService->column(['task_id' => $task['id']], 'uid');
        if (array_diff($member, $taskMember) == array_diff($taskMember, $member)) {
            return;
        }

        if (! $intersect) {
            $updateMember = $member;
        } else {
            $updateMember = array_intersect($taskMember, $member);
        }

        $memberService->handleMember($updateMember, $task['id']);
        $this->syncTaskTodo((int) $task['id'], 'update', [(int) ($task['uid'] ?? 0), ...$taskMember, ...$updateMember]);
        $this->combinationDynamic($task, 'members', $taskMember, $updateMember);
    }

    /**
     * @param array<int, int> $userIds
     */
    private function syncTaskTodo(int $taskId, string $action = 'update', array $userIds = []): void
    {
        BusinessDataChangeEvent::dispatch(
            TodoEnum::TYPE_TASK,
            $taskId,
            $action,
            array_values(array_unique(array_filter(array_map('intval', $userIds))))
        );
    }

    /**
     * 更新数据跟随父级.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function updateByPid(array $parent, array $programMember): void
    {
        $list = $this->dao->select(['pid' => $parent['id']])->toArray();
        if (! $list) {
            return;
        }

        $programVersion = [];
        if ($parent['level'] >= 4) {
            throw $this->exception('最多支持4级任务');
        }

        $level = $parent['level'] + 1;
        $path  = array_merge($parent['path'], [$parent['id']]);
        $topId = $parent['level'] == 1 ? $parent['id'] : $parent['top_id'];
        foreach ($list as $task) {
            $update = ['top_id' => $topId, 'path' => $path, 'level' => $level];
            if ($task['program_id'] != $parent['program_id']) {
                $update['program_id'] = $parent['program_id'];
                $this->combinationDynamic($task, 'program_id', $task['program_id'], $update['program_id']);

                $programVersion = app()->get(ProgramVersionService::class)->column(['program_id' => $parent['program_id']], 'id');
                if ($task['version_id'] != 0 && ! in_array($task['version_id'], $programVersion)) {
                    $update['version_id'] = 0;
                    $this->combinationDynamic($task, 'version_id', $task['version_id'], $update['version_id']);
                }
            }

            if ($task['uid'] != 0 && ! in_array($task['uid'], $programMember)) {
                $update['uid'] = 0;
                $this->combinationDynamic($task, 'uid', $task['uid'], 0);
            }

            $this->dao->update(['id' => $task['id']], $update);
            $this->updateMembersById($task, $programMember);
            $this->updateByPid(array_merge($task, $update), $programMember);
        }
    }
}
