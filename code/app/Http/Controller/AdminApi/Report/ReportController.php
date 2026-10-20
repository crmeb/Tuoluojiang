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

namespace App\Http\Controller\AdminApi\Report;

use App\Constants\ModuleEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\user\EnterpriseUserDailyReplyRequest;
use App\Http\Requests\enterprise\user\EnterpriseUserDailyRequest;
use App\Http\Service\Report\ReportReplyService;
use App\Http\Service\Report\ReportService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 日报
 * Class DailyController.
 */
#[Prefix('ent/daily')]
#[Resource('/', false, except: ['create', 'show'], names: [
    'index'   => '获取日报列表接口',
    'store'   => '保存日报接口',
    'edit'    => '获取查看日报接口',
    'update'  => '修改日报接口',
    'destroy' => '删除当天日报接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ReportController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use SearchTrait;

    public function __construct(ReportService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    public function index()
    {
        $this->withScopeFrame('user_id', module: ModuleEnum::REPORT);
        $where = $this->request->getMore($this->getSearchField());
        return $this->success($this->service->getList($where));
    }

    /**
     * 获取下级日报人员.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('users', '获取下级汇报人员')]
    public function users()
    {
        [$viewer] = $this->request->getMore([
            ['viewer', 'user'],
        ], true);
        $users = $this->service->getDailyUser(auth('admin')->id(), $viewer);

        return $this->success($users);
    }

    /**
     * 获取修改数据.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function edit($id)
    {
        if (! $id) {
            return $this->fail($this->message['edit']['empty']);
        }

        return $this->success($this->service->resourceEdit((int) $id, ['entid' => $this->entId]));
    }

    /**
     * 回复评论.
     * @return mixed
     */
    #[Post('reply', '日报回复')]
    public function reply(EnterpriseUserDailyReplyRequest $request, ReportReplyService $services)
    {
        $data = $request->postMore([
            ['content', ''],
            ['daily_id', 0],
            ['pid', 0],
            ['uid', $this->uuid],
        ]);
        if ($services->create($data)) {
            return $this->success('回复成功');
        }
        return $this->fail('回复失败');
    }

    /**
     * 删除汇报回复.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Delete('reply/{id}/{daily_id}', '删除汇报回复')]
    public function deleteReply(ReportReplyService $services, $id, $dailyId): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        if ($services->deleteReply((int) $id, (int) $dailyId, $this->uuid, $this->entId)) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }

    /**
     * 汇报提交统计
     */
    #[Get('submit_statistics', '汇报提交统计')]
    public function submitStatistics()
    {
        $userIds               = [];
        [$types, $time, $type] = $this->request->getMore([
            ['types', ''],
            ['time', ''],
            ['type', 0],
        ], true);

        // 部门汇报
        if ($type < 1) {
            $this->withScopeFrame(module: ModuleEnum::REPORT);
            [$userIds] = $this->request->getMore([
                ['uid', []],
            ], true);
        }

        return $this->success($this->service->submitStatistics($this->uuid, (int) $type, (int) $types, $time, (array) $userIds));
    }

    /**
     * 汇报统计
     */
    #[Get('statistics', '汇报统计')]
    public function statistics()
    {
        $this->withScopeFrame(module: ModuleEnum::REPORT);
        [$types, $time, $userIds] = $this->request->getMore([
            ['types', ''],
            ['time', ''],
            ['uid', []],
        ], true);
        return $this->success($this->service->statistics(['types' => (int) $types, 'time' => $time, 'user_ids' => (array) $userIds]));
    }

    /**
     * 提交统计列表.
     */
    #[Get('submit_list', '汇报提交列表')]
    public function submitList(): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::REPORT);
        [$types, $time, $userIds] = $this->request->getMore([
            ['types', ''],
            ['time', ''],
            ['uid', []],
        ], true);
        return $this->success($this->service->getSubmitList(['types' => (int) $types, 'time' => $time, 'user_ids' => (array) $userIds]));
    }

    /**
     * 未提交汇报人员列表.
     */
    #[Get('no_submit_list', '未提交汇报人员列表')]
    public function noSubmitUserList(): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::REPORT);
        [$types, $time, $userIds] = $this->request->getMore([
            ['types', ''],
            ['time', ''],
            ['uid', []],
        ], true);
        return $this->success($this->service->getNoSubmitUserList(['types' => (int) $types, 'time' => $time, 'user_ids' => (array) $userIds]));
    }

    /**
     * 日报表单回显数据.
     * @param mixed $type
     * @throws BindingResolutionException
     */
    #[Get('schedule_record/{type}', '日报待办回显')]
    public function scheduleRecord($type = 0): mixed
    {
        return $this->success($this->service->dailyScheduleRecord($this->uuid, (int) $type));
    }

    /**
     * 默认汇报人.
     * @throws BindingResolutionException
     */
    #[Get('report_member', '默认汇报人')]
    public function reportMember(): mixed
    {
        return $this->success($this->service->getReportMemberPC(auth('admin')->id()), tips: 0);
    }

    /**
     * 汇报人查看汇报列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('report_list', '汇报人查看汇报列表')]
    public function reportDaily(): mixed
    {
        return $this->success($this->service->getReportDailyList($this->request->getMore($this->getSearchField())));
    }

    /**
     * 导出.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('export', '汇报统计导出')]
    public function export(): mixed
    {
        $this->withScopeFrame('user_id', module: ModuleEnum::REPORT);
        $where = $this->request->getMore([
            ['types', ''],
            ['time', ''],
            ['user_id', []],
            ['viewer', 'user'],
            ['name', '', 'name_like'],
        ]);

        return $this->success($this->service->export($where));
    }

    /**
     * 获取保存和修改字段.
     *
     * @return array|string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['finish', []],
            ['plan', []],
            ['mark', ''],
            ['status', 0],
            ['types', 1],
            ['uid', $this->uuid],
            ['entid', 1],
            ['attach_ids', []],
            ['members', []],
        ];
    }

    /**
     * 字段验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserDailyRequest::class;
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['types', 0],
            ['time', ''],
            ['user_id', ''],
            ['viewer', 'user'],
            ['name', '', 'finish_like'],
        ];
    }
}
