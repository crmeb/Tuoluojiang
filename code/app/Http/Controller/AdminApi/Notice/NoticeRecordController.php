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

namespace App\Http\Controller\AdminApi\Notice;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\Notice\NoticeSubscribeService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 企业消息
 * Class NoticeRecordController.
 */
#[Prefix('ent/company/message')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class NoticeRecordController extends AuthController
{
    public function __construct(NoticeRecordService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 消息列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('/', '企业消息列表')]
    public function index()
    {
        $entId = $this->entId ?: 0;
        return $this->success($this->service->getMessageList(
            $this->uuid,
            $entId,
            $this->request->get('cate_id', ''),
            $this->request->get('title', ''),
            $this->request->get('is_read', '')
        ));
    }

    /**
     * 获取消息详情并标记已读.
     * @param int $id 消息ID
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('detail/{id}', '获取消息详情并标记已读')]
    public function detail(int $id)
    {
        $userId = auth('admin')->id();
        return $this->success($this->service->getInfoAndMarkRead($id, $userId));
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('list', '企业消息全部列表')]
    public function list()
    {
        $entId = $this->entId ?: 0;
        return $this->success($this->service->getMessageList(
            '',
            $entId,
            $this->request->get('cate_id', ''),
            $this->request->get('title', ''),
            $this->request->get('is_read', '')
        ));
    }

    /**
     * 修改消息状态
     * @param mixed $id
     * @param mixed $isRead
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update($id, $isRead)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $messageInfo = $this->service->get($id);
        if (! $messageInfo) {
            return $this->fail('消息不存在');
        }
        $messageInfo->is_read = $isRead;
        if ($messageInfo->save()) {
            return $this->success('common.update.succ');
        }
        return $this->fail('common.update.fail');
    }

    /**
     * 修改处理状态
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('update/{id}/{isHandle}', '企业消息处理状态')]
    public function updateHandle($id, $isHandle)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $messageInfo = $this->service->get($id);
        if (! $messageInfo) {
            return $this->fail('消息不存在');
        }
        $messageInfo->is_handle = $isHandle;
        if ($messageInfo->save()) {
            return $this->success('common.update.succ');
        }
        return $this->fail('common.update.fail');
    }

    /**
     * 批量修改.
     * @return mixed
     */
    #[Put('batch/{isRead}', '企业消息批量已读')]
    public function batchUpdate($isRead)
    {
        $where = $this->request->postMore([
            ['ids', []],
            ['cate_id', 0],
        ]);
        $this->service->updateRead($where, $isRead, auth('admin')->id());
        return $this->success('修改成功', tips: 0);
    }

    /**
     * 批量删除.
     * @return mixed
     */
    #[Delete('batch', '企业消息批量删除')]
    public function batchDelete()
    {
        $ids = $this->request->post('ids', []);
        if (! $ids) {
            return $this->fail('缺少参数');
        }
        if ($this->service->delete(['ids' => $ids])) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }

    /**
     * 用户订阅列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('subscribe', '企业消息订阅列表')]
    public function subscribe()
    {
        $where = $this->request->getMore([
            ['cate_id', 0],
            ['title', ''],
            ['entid', 1],
        ]);

        return $this->success($this->service->getListForUser($this->uuid, $this->entId, $where));
    }

    /**
     * 用户订阅/取消订阅消息.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Put('subscribe/{id}', '企业消息订阅/取消订阅')]
    public function toSubscribe(NoticeSubscribeService $services, $id)
    {
        $status = $this->request->post('status', 0);
        $services->saveSubscribe((int) $id, (int) $status, auth('admin')->id());
        return $this->success('操作成功');
    }
}
