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

namespace App\Http\Controller\AdminApi\Attendance;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\attendance\AttendanceGroupRequest;
use App\Http\Service\Attendance\AttendanceGroupService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 考勤组
 * Class AttendanceGroupController.
 */
#[Prefix('ent/attendance/group')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '考勤组列表',
    'store'   => '考勤组保存',
    'update'  => '考勤组修改',
    'destroy' => '考勤组删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttendanceGroupController extends AuthController
{
    /**
     * AttendanceGroupController constructor.
     * @throws \Throwable
     */
    public function __construct(AttendanceGroupService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取列表.
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
        ]);

        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ValidationException
     * @throws \ReflectionException
     */
    public function store(AttendanceGroupRequest $request): mixed
    {
        $request->scene('step_one')->check();
        $data = $request->postMore($this->getRequestStepOneFields());
        $res  = $this->service->saveGroup($data);
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws ValidationException
     */
    public function update($id, AttendanceGroupRequest $request): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $step = 'step_' . $this->request->post('step', 'one');
        $request->scene($step)->check();
        $func = Str::studly($step);
        $data = $request->postMore($this->{"getRequest{$func}Fields"}());
        $this->service->{'update' . $func}((int) $id, $data);

        return $this->success('保存成功', tips: 0);
    }

    /**
     * 删除.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $this->service->deleteGroup((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '考勤组详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 获取白名单.
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('white', '获取白名单')]
    public function getWhiteList(): mixed
    {
        return $this->success($this->service->getWhitelist());
    }

    /**
     * 设置白名单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('white', '设置白名单')]
    public function setWhiteList(): mixed
    {
        $data = $this->request->postMore([
            ['members', []],
            ['admins', []],
        ]);
        $this->service->setWhitelist($data);
        return $this->success('common.update.succ');
    }

    /**
     * 重复检测.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('repeat_check', '考勤组人员重复检测')]
    public function repeatCheck(): mixed
    {
        [$id, $type, $members] = $this->request->postMore([
            ['id', 0],
            ['type', 0],
            ['members', []],
        ], true);
        return $this->success($this->service->memberRepeatCheck((int) $type, (array) $members, (int) $id));
    }

    /**
     * 获取未参与考勤人员.
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('unattended_member', '获取未参与考勤人员')]
    public function unAttendMember()
    {
        return $this->success($this->service->getUnAttendMember());
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('select', '考勤组下拉列表')]
    public function select(): mixed
    {
        return $this->success($this->service->getSelectList());
    }

    /**
     * 获取参加考勤人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('member', '获取参加考勤人员')]
    public function attendanceMember(): mixed
    {
        [$type, $filterId] = $this->request->getMore([
            ['type', ''],
            ['filter_id', ''],
        ], true);

        $data = $this->service->getGroupMembersByType((int) $type, (int) $filterId);
        return $this->success($data);
    }

    /**
     * 提取字段.
     */
    protected function getRequestStepOneFields(): array
    {
        return [
            ['name', ''],
            ['shifts', []],
            ['type', 0],
            ['members', []],
            ['admins', []],
            ['filters', []],
            ['other_filters', []],
        ];
    }

    /**
     * 提取字段.
     */
    protected function getRequestStepTwoFields(): array
    {
        return [
            ['address', ''],
            ['lat', ''],
            ['lng', ''],
            ['effective_range', 0],
            ['location_name', ''],
            ['is_map', 1],
            ['is_wifi', 0],
            ['wifi_data', []],
        ];
    }

    /**
     * 提取字段.
     */
    protected function getRequestStepThreeFields(): array
    {
        return [
            ['repair_allowed', 0],
            ['repair_type', []],
            ['is_limit_time', 0],
            ['limit_time', 0],
            ['is_limit_number', 0],
            ['limit_number', 0],
            ['is_photo', 0],
            ['is_external', 0],
            ['is_external_note', 0],
            ['is_external_photo', 0],
        ];
    }

    /**
     * 提取字段.
     */
    protected function getRequestStepFourFields(): array
    {
        return [
            ['data', []],
        ];
    }
}
