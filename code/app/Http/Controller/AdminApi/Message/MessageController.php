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

namespace App\Http\Controller\AdminApi\Message;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Message\MessageService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 消息控制器
 * Class MessageController.
 */
#[Prefix('ent/system/message')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class MessageController extends AuthController
{
    public function __construct(MessageService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 消息列表.
     * @return mixed
     */
    #[Get('list', '系统消息列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['cate_id', ''],
            ['title', ''],
            ['entid', 1],
        ]);

        return $this->success($this->service->getList(where: $where, with: ['messageTemplate']));
    }

    /**
     * 分类.
     * @return mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    #[Get('cate', '系统消息分类')]
    public function cate()
    {
        return $this->success($this->service->getMessageCateCount($this->entId, auth('admin')->id()));
    }

    /**
     * 获取系统消息.
     * @return mixed
     */
    #[Get('find/{id}', '获取系统消息')]
    public function find($id)
    {
        if (! $id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->info((int) $id));
    }

    /**
     * 系统消息修改.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Put('update/{id}', '系统消息修改')]
    public function update($id)
    {
        if (! $id) {
            return $this->fail('缺少参数');
        }
        $data = $this->request->postMore([
            ['remind_time', ''],
            ['status', ''],
            ['sms_status', ''],
            ['template_id', ''],
            ['work_webhook_url', ''],
            ['work_status', 0],
            ['ding_webhook_url', ''],
            ['ding_status', 0],
            ['other_webhook_url', ''],
            ['other_status', 0],
            ['wework_status', 0],
        ], true);
        $this->service->saveMessage((int) $id, $data);
        Cache::tags('message')->clear();
        return $this->success('修改成功');
    }

    /**
     * 批量修改消息渠道.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Put('batch_update', '批量修改消息渠道')]
    public function batch_update()
    {
        $data = $this->request->postMore([
            ['remind_time', ''],
            ['status', ''],
            ['sms_status', ''],
            ['template_id', ''],
            ['work_webhook_url', ''],
            ['work_status', 0],
            ['ding_webhook_url', ''],
            ['ding_status', 0],
            ['other_webhook_url', ''],
            ['other_status', 0],
            ['wework_status', 0],
        ], true);
        $this->service->batchSaveMessage((array) $this->request->post('id', []), $data);
        Cache::tags('message')->clear();
        return $this->success('修改成功');
    }

    /**
     * 修改消息状态
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('status/{id}/{type}', '修改消息状态')]
    public function status($id, $type)
    {
        if (! $id) {
            return $this->fail('缺少参数');
        }
        $status = $this->request->post('status', 0);
        $this->service->updateStatus((int) $id, (int) $type, (int) $status);
        return $this->success('修改成功');
    }

    /**
     * 批量修改状态
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Put('batch_status/{type}', '批量修改状态')]
    public function batch_status($type)
    {
        $id     = $this->request->post('id', []);
        $status = $this->request->post('status', 0);
        $this->service->batchUpdateStatus((array) $id, (int) $type, (int) $status);
        return $this->success('修改成功');
    }

    /**
     * 用户是否可取消订阅.
     * @return mixed
     */
    #[Put('subscribe/{id}', '用户是否可取消订阅')]
    public function user_sub($id)
    {
        $status = $this->request->post('status', 0);
        $this->service->update($id, ['user_sub' => $status]);
        return $this->success('修改成功');
    }

    /**
     * 同步消息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    #[Put('sync', '系统消息同步')]
    public function syncMessage()
    {
        $this->service->syncMessage($this->entId);
        return $this->success('ok');
    }
}
