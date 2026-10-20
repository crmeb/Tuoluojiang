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

namespace App\Http\Controller\AdminApi\Schedule;

use App\Constants\ScheduleEnum;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\CheckRuleCompany;
use App\Http\Requests\user\ScheduleRequest;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 待办日程.
 */
#[Prefix('ent/schedule')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ScheduleController extends AuthController
{
    public function __construct(ScheduleInterface $schedule)
    {
        parent::__construct();
        $this->service = $schedule;
        $this->middleware([AuthAdmin::class, AuthEnterprise::class, CheckRuleCompany::class]);
    }

    /**
     * 日程类型列表.
     * @return mixed
     */
    #[Get('types', '获取日程类型列表')]
    public function typeList()
    {
        return $this->success($this->service->typeList(auth('admin')->id(), ['id', 'name', 'color', 'info', 'is_public']));
    }

    /**
     * 新建日程类型表单.
     * @return mixed
     */
    #[Get('type/create', '新建日程类型表单')]
    public function createType()
    {
        return $this->success($this->service->typeCreateForm());
    }

    /**
     * 新建日程类型.
     * @return mixed
     */
    #[Post('type/save', '新建日程类型')]
    public function saveType()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['color', ''],
            ['info', ''],
        ]);
        $res = $this->service->saveType(auth('admin')->id(), $data);
        return $res ? $this->success('添加成功') : $this->fail('添加失败');
    }

    /**
     * 修改日程类型表单.
     * @return mixed
     */
    #[Get('type/edit/{id}', '修改日程类型表单')]
    public function editType($id)
    {
        if (! $id) {
            return $this->fail('缺少日程类型ID');
        }
        return $this->success($this->service->typeEditForm((int) $id, auth('admin')->id()));
    }

    /**
     * 修改日程类型.
     * @return mixed
     */
    #[Put('type/update/{id}', '修改日程类型')]
    public function updateType($id)
    {
        if (! $id) {
            return $this->fail('缺少日程类型ID');
        }
        $data = $this->request->postMore([
            ['name', ''],
            ['color', ''],
            ['info', ''],
        ]);
        $res = $this->service->updateType((int) $id, auth('admin')->id(), $data);
        return $res ? $this->success('修改成功') : $this->fail('修改失败');
    }

    /**
     * 删除日程类型.
     * @return mixed
     */
    #[Delete('type/delete/{id}', '删除日程类型')]
    public function deleteType($id)
    {
        if (! $id) {
            return $this->fail('缺少日程类型ID');
        }
        $res = $this->service->deleteType((int) $id, auth('admin')->id());
        return $res ? $this->success('删除成功') : $this->fail('删除失败');
    }

    /**
     * 获取日程列表.
     * @return mixed
     */
    #[Post('index', '获取日程列表')]
    public function index()
    {
        [$start, $end, $cid, $period] = $this->request->postMore([
            ['start_time', ''],
            ['end_time', ''],
            ['cid', []],
            ['period', 1],
        ], true);
        return $this->success($this->service->scheduleList(auth('admin')->id(), $this->entId, $start, $end, (array) $cid, (int) $period));
    }

    /**
     * 新建日程保存.
     */
    #[Post('store', '新建日程保存')]
    public function store(ScheduleRequest $request): mixed
    {
        $request->scene('create')->check();
        $data = $this->request->postMore([
            ['title', ''],
            ['member', []], // 参与人
            ['content', ''],
            ['cid', 0],
            ['color', ''],
            ['remind', 0],
            ['remind_time', ''],
            ['repeat', 0],
            ['period', 0],
            ['rate', 0],
            ['days', []],
            ['all_day', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['fail_time', null],
        ]);
        $this->service->saveSchedule(auth('admin')->id(), 1, $data);
        return $this->success('添加成功');
    }

    /**
     * 快捷创建日程（标题必填，可选参与人/截止时间/描述/提醒）.
     */
    #[Post('quick', '快捷创建日程')]
    public function quick(ScheduleRequest $request): mixed
    {
        $request->scene('quick')->check();
        $data = $this->request->postMore([
            ['title', ''],
            ['member', []],
            ['content', ''],
            ['end_time', ''],
            ['remind', 0],
        ]);
        $id = $this->service->quickSaveSchedule(auth('admin')->id(), $this->entId, $data);

        return $this->success('添加成功', ['id' => $id]);
    }

    /**
     * 修改日程内容.
     * @return mixed
     * @throws ValidationException
     */
    #[Put('update/{id}', '修改日程内容')]
    public function update(ScheduleRequest $request, $id)
    {
        if (! $id) {
            return $this->fail('缺少日程ID');
        }
        $request->scene('update')->check();
        $data = $this->request->postMore([
            ['title', ''],
            ['member', []], // 参与人
            ['content', ''],
            ['cid', 0],
            ['color', ''],
            ['remind', 0],
            ['remind_time', ''],
            ['repeat', 0],
            ['period', 0],
            ['rate', 0],
            ['days', []],
            ['all_day', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['fail_time', null],
            ['type', ScheduleEnum::CHANGE_ALL],
            ['start', ''],
            ['end', ''],
        ]);
        $this->service->updateSchedule($this->entId, (int) $id, $data, auth('admin')->id());
        return $this->success('修改成功');
    }

    /**
     * 修改日程状态
     */
    #[Put('status/{id}', '修改日程状态')]
    public function status(ScheduleRequest $request, $id)
    {
        if (! $id) {
            return $this->fail('缺少日程ID');
        }
        $request->scene('status')->check();
        [$status, $start_time, $end_time] = $this->request->postMore([
            ['status', 0],
            ['start', 0],
            ['end', 0],
        ], true);
        $res = $this->service->updateStatus((int) $id, auth('admin')->id(), $this->entId, (int) $status, [$start_time, $end_time]);
        return $res ? $this->success('操作成功') : $this->fail('操作失败');
    }

    /**
     * 日程详情.
     */
    #[Get('info/{id}', '获取日程信息')]
    public function info($id)
    {
        if (! $id) {
            return $this->fail('缺少日程ID');
        }
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
        ]);
        $field = ['id', 'uid', 'cid', 'color', 'title', 'content', 'all_day', 'start_time', 'end_time', 'period', 'days', 'rate', 'remind as is_remind', 'link_id', 'fail_time'];
        $info  = $this->service->scheduleInfo((int) $id, auth('admin')->id(), $field, $where);
        return $this->success($info);
    }

    /**
     * 删除日程.
     * @throws ValidationException
     */
    #[Delete('delete/{id}', '删除日程')]
    public function delete(ScheduleRequest $request, $id): mixed
    {
        if (! $id) {
            return $this->fail('缺少日程ID');
        }
        $request->scene('delete')->check();
        $data = $this->request->postMore([
            ['type', ScheduleEnum::CHANGE_ALL],
            ['start', ''],
            ['end', ''],
        ]);
        $this->service->deleteSchedule(auth('admin')->id(), $this->entId, (int) $id, $data);
        return $this->success('删除成功');
    }

    /**
     * 获取日程数量.
     * @return mixed
     */
    #[Post('count', '获取日程数量')]
    public function count()
    {
        [$start, $end, $cid, $period] = $this->request->postMore([
            ['start_time', ''],
            ['end_time', ''],
            ['cid', []],
            ['period', 1],
        ], true);
        $data = $this->service->scheduleCount(auth('admin')->id(), $this->entId, $start, $end, $cid, (int) $period);
        return $this->success($data);
    }

    /**
     * 评价列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('replys', '日程评价列表')]
    public function replays()
    {
        $where = $this->request->getMore([
            ['schedule_id', 0, 'pid'],
            ['time', '', 'time_zone'],
        ]);
        $data = $this->service->replays($where);
        return $this->success($data);
    }

    /**
     * 保存评价.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('reply/save', '保存日程评价')]
    public function save_reply()
    {
        $data = $this->request->postMore([
            ['schedule_id', 0, 'pid'],
            ['reply_id', 0],
            ['content', ''],
            ['start', '', 'start_time'],
            ['end', '', 'end_time'],
            ['to_uid', ''],
            ['files', []],
        ]);
        $this->service->saveReply($this->uuid, $this->entId, $data);
        return $this->success('保存成功');
    }

    /**
     * 删除评价.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Delete('reply/del/{id}', '删除日程评价')]
    public function del_reply($id)
    {
        $this->service->delReply((int) $id, $this->uuid, $this->entId);
        return $this->success('删除成功');
    }
}
