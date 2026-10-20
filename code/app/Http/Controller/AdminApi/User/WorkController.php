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

namespace App\Http\Controller\AdminApi\User;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\PaymentService;
use App\Http\Service\Notice\NoticeService;
use App\Http\Service\Report\ReportService;
use App\Http\Service\Todo\TodoItemService;
use App\Http\Service\User\UserPendingService;
use App\Http\Service\User\UserQuickService;
use Tuoluojiang\Open\traits\SearchTrait;
use Tuoluojiang\Open\utils\Regex;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 工作台
 * Class WorkController.
 */
#[Prefix('ent/user/work')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkController extends AuthController
{
    use SearchTrait;

    /**
     * 获取某月计划填写列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('daily', '获取工作台某月计划列表')]
    public function daily(ReportService $services)
    {
        $time = $this->request->get('time');
        if (! $time) {
            $timeDate = now()->toArray();
            $time     = $timeDate['year'] . '-' . $timeDate['month'];
        }
        if (! preg_match(Regex::MONTH_TIME_RULE, $time)) {
            return $this->fail('时间段格式错误');
        }
        [$year, $month] = explode('-', $time);
        $month          = str_pad($month, 2, '0', STR_PAD_LEFT);
        $time           = $year . '-' . $month;
        return $this->success($services->getMonthDailyList($this->uuid, 1, $time));
    }

    /**
     * 待办列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('pending', '获取工作台待办列表')]
    public function pending(UserPendingService $services)
    {
        $where = $this->request->getMore([
            ['status', ''],
        ]);
        $where['uid'] = $this->uuid;
        return $this->success($services->getPendingList($where));
    }

    /**
     * 工作台数量.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('count', '获取工作台待办数量')]
    public function indexCount(
        TodoItemService $todoItemService,
        ApproveApplyService $approveApplyServices,
        NoticeService $noticeServices,
    ): mixed {
        $entId         = 1;
        $userId        = auth('admin')->id();
        $scheduleCount = $todoItemService->countPendingByDate($userId);
        $approveCount  = $approveApplyServices->getApproveCount($userId);
        $applyCount    = $approveApplyServices->count(['user_id' => $userId, 'status' => 0]);
        $noticeCount   = $noticeServices->getNotReadCount(['status' => 1, 'entid' => $entId, 'push_time' => now()->toDateTimeString()], $userId);
        return $this->success(compact('scheduleCount', 'applyCount', 'approveCount', 'noticeCount'));
    }

    /**
     * 获取工作台快捷入口.
     */
    #[Get('menus', '获取工作台快捷入口')]
    public function getFastEntry(UserQuickService $service): mixed
    {
        return $this->success($service->getFastEntry(auth('admin')->user()));
    }

    /**
     * 保存工作台快捷入口.
     * @throws BindingResolutionException
     */
    #[Post('menus', '保存工作台快捷入口')]
    public function setFastEntry(UserQuickService $service): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        if ($service->setFastEntry($this->uuid, $data, 1)) {
            return $this->success('修改成功');
        }
        return $this->fail('修改失败');
    }

    /**
     * 业绩统计类型.
     * @throws BindingResolutionException
     */
    #[Get('statistics_type', '获取业绩统计类型接口')]
    public function statisticsType(UserQuickService $service): mixed
    {
        return $this->success($service->getStatisticsType($this->uuid));
    }

    /**
     * 更新统计管理.
     * @throws BindingResolutionException
     */
    #[Post('statistics_type', '修改业绩统计类型接口')]
    public function updateStatisticsType(UserQuickService $service): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        $res = $service->setStatisticsType($this->uuid, $data);
        return $res ? $this->success('common.update.succ') : $this->fail('common.update.fail');
    }

    /**
     * 业绩统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('statistics/{types}', '工作台业绩统计')]
    public function statistics(
        $types,
        CustomerService $customerService,
        UserQuickService $quickService,
        PaymentService $paymentService,
        FollowUpService $followService,
        OrderService $orderService,
    ): mixed {
        if ($types) {
            $uid = auth('admin')->id();
        } else {
            $this->request->merge(['scope_frame' => 'all']);
            $this->withScopeFrame();
            [$uid] = $this->request->getMore([
                ['uid', ''],
            ], true);
        }

        $data           = [];
        $statisticsType = $quickService->getSelectType($this->uuid);
        foreach ($statisticsType as $item) {
            $data[] = [
                'title' => $quickService::STATISTICS_TYPE[$item],
                'value' => match ($item) {
                    'client'            => $customerService->count(['uid' => $uid]),
                    'month_client'      => $customerService->count(['time' => 'month', 'uid' => $uid]),
                    'today_client'      => $customerService->count(['time' => 'today', 'uid' => $uid]),
                    'today_contract'    => $orderService->count(['time' => 'today', 'uid' => $uid]),
                    'incomplete_follow' => $customerService->getFollowExpire(['uid' => $uid])->count(),
                    'today_follow'      => $followService->setTimeField('time')->count(['time' => 'today', 'user_id' => $uid]),
                    'today_income'      => (string) $paymentService->sum(['date' => 'today', 'uid' => $uid, 'status' => 1, 'types' => -1], 'num'),
                    'income'            => (string) $paymentService->sum(['date' => 'month', 'uid' => $uid, 'status' => 1, 'types' => -1], 'num'),
                    'yesterday_income'  => (string) $paymentService->sum(['date' => 'yesterday', 'uid' => $uid, 'status' => 1, 'types' => -1], 'num'),
                    default             => 0
                },
            ];
        }
        return $this->success($data);
    }
}
