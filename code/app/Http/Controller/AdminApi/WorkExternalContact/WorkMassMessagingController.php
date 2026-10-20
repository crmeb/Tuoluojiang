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

namespace App\Http\Controller\AdminApi\WorkExternalContact;

use App\Constants\System\ViewSearchEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\WorkExternalContact\MassMessagingRequest;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Work\WorkClientFollowService;
use App\Http\Service\Work\WorkGroupChatService;
use App\Http\Service\WorkExternalContact\WorkMassMessagingResultService;
use App\Http\Service\WorkExternalContact\WorkMassMessagingService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 企微消息群发.
 */
#[Prefix('ent/work/mass_messaging')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取群发列表',
    'store'   => '保存群发信息',
    'edit'    => '群发信息详情',
    'update'  => '修改群发信息',
    'destroy' => '删除群发信息',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkMassMessagingController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(WorkMassMessagingService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 修改群发状态.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[Get('status/{id}', '修改群发状态')]
    public function updateStatus($id)
    {
        [$status] = $this->request->postMore([
            ['status', 0],
        ], true);
        $this->service->updateStatus((int) $id, (int) $status);
        return $this->success('修改成功');
    }

    /**
     * 提醒用户群发.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('remind/{id}', '提醒用户群发')]
    public function remind($id)
    {
        $this->service->remind((int) $id);
        return $this->success('操作成功');
    }

    /**
     * 待发送客户数.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('customer_count', '待发送客户数')]
    public function sendCount(CustomerService $customerService, WorkClientFollowService $clientFollowService)
    {
        $data = $this->request->postMore([
            ['search', []],
            ['send_uid', []],
        ]);
        $search = collect($data['search'])->filter(function ($item) {
            if (! Arr::has($item, ['field', 'value'])) {
                return false;
            }
            return filled($item['field']) && filled($item['value']);
        })->pluck('value', 'field')->all();
        if ((int) $this->request->post('is_all', 1) == 2) {
            $count = $clientFollowService->getUserClientCount($search);
        } else {
            $search = $search + ['types' => ViewSearchEnum::VIEW_CUSTOMER, 'involved' => $data['send_uid']];
            $count  = collect(
                $customerService->listSearch($search)->get()?->toArray()
            )->pluck('external_userid')->filter()->unique()->values()->count();
        }
        return $this->success(compact('count'));
    }

    /**
     * 获取群列表.
     * @return mixed
     */
    #[Post('group_chat', '获取企微群列表')]
    public function group_chat(WorkGroupChatService $service)
    {
        $where = $this->request->postMore([
            ['status', 0],
            ['admin_id', []],
            ['name', '', 'name_like'],
            ['corp_id', sys_config('wechat_work_corpid')],
        ]);
        return $this->success($service->getSelect($where));
    }

    /**
     * 群发结果.
     * @return mixed
     */
    #[Get('result/{id}', '群发结果')]
    public function result(WorkMassMessagingResultService $service, $id)
    {
        if (! $id) {
            return $this->fail('参数错误');
        }
        $where['mass_id'] = $id;
        $result           = $service->getResult($where);
        return $this->success($result);
    }

    /**
     * 测试同步群列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('test', '获取企微群列表')]
    public function test(WorkGroupChatService $service)
    {
        $service->syncWorkGroupChat();
        return $this->success();
    }

    protected function getRequestClassName(): string
    {
        return MassMessagingRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['content', '', 'content_like'],
            ['send_time', '', 'time'],
            ['status', ''],
            ['types', 0],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['send_uid', []],
            ['is_all', 1],
            ['search', []],
            ['send_group', []],
            ['is_modify', 0],
            ['temp', []],
            ['temp_id', 0],
            ['is_timed', 0],
            ['send_time', ''],
            ['types', 0],
            ['uid', auth('admin')->id()],
        ];
    }
}
