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

namespace App\Http\Service\Program;

use App\Constants\ProgramEnum\DynamicEnum;
use App\Constants\ProgramEnum\ProgramStatusEnum;
use App\Http\Dao\Program\ProgramDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目
 * Class ProgramService.
 */
class ProgramService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(ProgramDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'created_at', array $with = ['admins']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $taskService    = app()->get(ProgramTaskService::class);
        foreach ($list as &$item) {
            $item['operate']         = in_array($where['admin_uid'], [$item['uid'], $item['creator_uid']]);
            $item['task_statistics'] = [
                'total'      => $taskService->count(['program_id' => $item['id']]),
                'incomplete' => $taskService->count(['program_id' => $item['id'], 'status' => [0, 1]]),
            ];
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 保存项目.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveProgram(array $data): mixed
    {
        $this->checkCustomerContact($data);
        return $this->transaction(function () use ($data) {
            $members = array_filter(array_unique(array_merge([$data['uid']], $data['members'])));
            unset($data['members']);
            $creatorUid = uuid_to_uid($this->uuId(false));
            $res        = $this->dao->create(array_merge($data, [
                'ident'       => sprintf('P%04d', $this->dao->setTrashed()->count() + 1),
                'creator_uid' => $creatorUid,
            ]));
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }

            if ($members) {
                app()->get(ProgramMemberService::class)->handleMember($members, $res->id);
            }

            // program dynamic
            app()->get(ProgramDynamicService::class)->addLog(DynamicEnum::PROGRAM, DynamicEnum::CREATED_ACTION, [
                'uid'         => uuid_to_uid($this->uuId(false)),
                'relation_id' => $res->id,
                'title'       => '创建了项目 </b>' . $data['name'] . '</b>',
            ]);
            return $res;
        });
    }

    /**
     * 修改项目.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateProgram(array $data, int $id): mixed
    {
        $info = toArray($this->dao->get(['id' => $id]));
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $uid = uuid_to_uid($this->uuId(false));
        if ($uid != $info['uid'] && $uid != $info['creator_uid']) {
            throw $this->exception('您暂无权限操作！');
        }

        $this->checkCustomerContact($data);
        return $this->transaction(function () use ($data, $id, $info, $uid) {
            $memberService = app()->get(ProgramMemberService::class);

            $members       = array_filter(array_unique(array_merge([$data['uid']], $data['members'])));
            $removeMembers = array_diff($memberService->column(['program_id' => $id], 'uid'), $members);
            $removeMembers && app()->get(ProgramTaskService::class)->removeMemberByProgramId($id, $removeMembers);

            $data['eid'] < 1 && $data['cid'] = 0;
            $describe                        = $this->getUpdateDescribe($id, $info, $data);
            unset($data['members']);
            $res = $this->dao->update($id, $data);
            if (! $res) {
                throw $this->exception(__('common.update.fail'));
            }

            $memberService->handleMember($members, $id);

            // program dynamic
            $describe && app()->get(ProgramDynamicService::class)->addLog(DynamicEnum::PROGRAM, DynamicEnum::UPDATE_ACTION, [
                'uid'         => $uid,
                'relation_id' => $id,
                'title'       => '修改了 <b>项目基本信息</b>',
                'describe'    => $describe,
            ]);
            return $res;
        });
    }

    /**
     * 核对关联数据.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function checkCustomerContact(array $data): void
    {
        if ($data['cid'] && ! app()->get(OrderService::class)->exists(['id' => $data['cid'], 'eid' => $data['eid']])) {
            throw $this->exception('请重新选择关联订单');
        }
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(int $id): array
    {
        $program = toArray($this->dao->get($id, ['*'], ['admins', 'members', 'contract', 'customer']));
        if (! $program) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $program['operate'] = in_array(uuid_to_uid($this->uuId(false)), [$program['uid'], $program['creator_uid']]);
        return $program;
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteProgram(int $id): mixed
    {
        $info = toArray($this->dao->get(['id' => $id]));
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $uid = uuid_to_uid($this->uuId(false));
        if ($info['uid'] != $uid && $info['creator_uid'] != $uid) {
            throw $this->exception('删除失败，您暂无权限删除！');
        }

        return $this->transaction(function () use ($id, $info) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }

            // program dynamic
            app()->get(ProgramDynamicService::class)->addLog(DynamicEnum::PROGRAM, DynamicEnum::DELETE_ACTION, [
                'uid'         => uuid_to_uid($this->uuId(false)),
                'relation_id' => $id,
                'title'       => '删除了项目 </b>' . $info['name'] . '</b>',
            ]);

            // destroy task
            $taskService = app()->get(ProgramTaskService::class);
            $taskService->batchDel($taskService->column(['program_id' => $id], 'id'), true);

            // destroy member
            app()->get(ProgramMemberService::class)->delete(['program_id' => $id]);

            // destroy version
            app()->get(ProgramVersionService::class)->delete(['program_id' => $id]);
            return true;
        });
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelect(array $where): array
    {
        $field = ['id as value', 'name as label', 'ident', 'start_date', 'end_date', 'status'];
        return $this->dao->getList($where, $field, 0, 0, 'created_at');
    }

    /**
     * 项目成员.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getMemberList(array $where): array
    {
        $uid = app()->get(ProgramMemberService::class)->column($where, 'uid');
        if (! $uid) {
            return [];
        }
        return app()->get(AdminService::class)->select(['status' => 1, 'id' => $uid], ['id', 'name', 'avatar'])->toArray();
    }

    /**
     * 获取更新内容.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getUpdateDescribe(int $id, array $before, array $after): array
    {
        $describe = [];
        if ($before['name'] != $after['name']) {
            $describe[] = [
                'title' => DynamicEnum::getProgramFieldText('name') . '：',
                'value' => '由【' . $before['name'] . '】修改为【' . $after['name'] . '】',
            ];
        }

        if ($before['uid'] != $after['uid']) {
            $userService = app()->get(AdminService::class);
            $describe[]  = [
                'title' => DynamicEnum::getProgramFieldText('uid') . '：',
                'value' => '由【' . $userService->value((int) $before['uid'], 'name') . '】修改为【' . $userService->value((int) $after['uid'], 'name') . '】',
            ];
        }

        if ($before['eid'] != $after['eid']) {
            $customerService = app()->get(CustomerService::class);
            $describe[]      = [
                'title' => DynamicEnum::getProgramFieldText('eid') . '：',
                'value' => '由【' . ($before['eid'] ? $customerService->value((int) $before['eid'], 'customer_name') : '空') . '】修改为【' . ($after['eid'] ? $customerService->value((int) $after['eid'], 'customer_name') : '空') . '】',
            ];
        }

        if ($before['cid'] != $after['cid']) {
            $contractService = app()->get(OrderService::class);
            $describe[]      = [
                'title' => DynamicEnum::getProgramFieldText('cid') . '：',
                'value' => '由【' . ($before['cid'] ? $contractService->value((int) $before['cid'], 'contract_name') : '空') . '】修改为【' . ($after['cid'] ? $contractService->value((int) $after['cid'], 'contract_name') : '空') . '】',
            ];
        }

        $members = app()->get(ProgramMemberService::class)->column(['program_id' => $id], 'uid');
        if (array_diff($members, $after['members']) != array_diff($after['members'], $members)) {
            $userService  = app()->get(AdminService::class);
            $beforeMember = implode('、', $userService->column(['id' => $members], 'name')) ?: '空';
            $afterMember  = implode('、', $userService->column(['id' => $after['members']], 'name')) ?: '空';
            $describe[]   = [
                'title' => DynamicEnum::getProgramFieldText('members') . '：',
                'value' => '由【' . $beforeMember . '】修改为【' . $afterMember . '】',
            ];
        }

        if ($before['start_date'] != $after['start_date']) {
            $describe[] = [
                'title' => DynamicEnum::getProgramFieldText('start_date') . '：',
                'value' => '由【' . ($before['start_date'] ?: '空') . '】修改为【' . ($after['start_date'] ?: '空') . '】',
            ];
        }

        if ($before['end_date'] != $after['end_date']) {
            $describe[] = [
                'title' => DynamicEnum::getProgramFieldText('end_date') . '：',
                'value' => '由【' . ($before['end_date'] ?: '空') . '】修改为【' . ($after['end_date'] ?: '空') . '】',
            ];
        }

        if ($before['status'] != $after['status']) {
            $describe[] = [
                'title' => DynamicEnum::getProgramFieldText('status') . '：',
                'value' => '由【' . ProgramStatusEnum::getStatusText($before['status']) . '】修改为【' . ProgramStatusEnum::getStatusText((int) $after['status']) . '】',
            ];
        }

        if ($before['describe'] != $after['describe']) {
            $describe[] = [
                'title' => DynamicEnum::getProgramFieldText('describe') . '：',
                'value' => '由【' . ($before['describe'] ?: '空') . '】修改为【' . ($after['describe'] ?: '空') . '】',
            ];
        }

        return $describe;
    }
}
