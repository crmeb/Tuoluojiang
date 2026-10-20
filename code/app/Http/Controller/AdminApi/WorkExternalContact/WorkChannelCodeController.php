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

namespace App\Http\Controller\AdminApi\WorkExternalContact;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\WorkExternalContact\ChannelCodeRequest;
use App\Http\Service\Work\WorkGroupChatService;
use App\Http\Service\WorkExternalContact\WorkChannelCodeLogService;
use App\Http\Service\WorkExternalContact\WorkChannelCodeService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 企微渠道活码.
 */
#[Prefix('ent/work/channel_code')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取企微渠道活码列表',
    'store'   => '保存企微渠道活码',
    'edit'    => '获取企微渠道活码详情',
    'update'  => '修改企微渠道活码',
    'destroy' => '删除企微渠道活码',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkChannelCodeController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(WorkChannelCodeService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 列表搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['group_id', ''],
            ['invite_group', ''],
            ['status', ''],
            ['time', ''],
        ];
    }

    /**
     * 保存.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function store()
    {
        $data = $this->request()->postMore($this->saveFields());
        return $this->service->resourceSave($data) ? $this->success('保存成功') : $this->fail('保存失败');
    }

    /**
     * 修改.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update($id)
    {
        $data = $this->request()->postMore($this->saveFields());
        return $this->service->resourceUpdate((int) $id, $data) ? $this->success('修改成功') : $this->fail('修改失败');
    }

    /**
     * 重新生成企微二维码.
     * @return mixed
     */
    #[Post('refresh/{id}', '重新生成企微渠道二维码')]
    public function refreshQrCode($id)
    {
        return $this->service->refreshQrCode((int) $id) ? $this->success('操作成功') : $this->fail('操作失败');
    }

    /**
     * 快捷启用/关闭渠道活码.
     * @return mixed
     */
    #[Put('status/{id}/{status}', '快捷启停企微渠道活码')]
    public function status($id, $status)
    {
        return $this->service->updateStatus((int) $id, (int) $status) ? $this->success('操作成功') : $this->fail('操作失败');
    }

    /**
     * 批量编辑活码分组.
     * @return mixed
     */
    #[Put('batch/group', '批量编辑活码分组')]
    public function batchGroup()
    {
        [$ids, $groupId] = $this->request->postMore([
            ['id', []],
            ['group_id', 0],
        ], true);
        $this->service->batchUpdateGroup((array) $ids, (int) $groupId);
        return $this->success('修改成功');
    }

    /**
     * 渠道触达日志.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('logs/{id}', '获取企微渠道活码日志')]
    public function logs($id, WorkChannelCodeLogService $logService)
    {
        $where = $this->request->getMore([
            ['channel_id', (int) $id],
            ['send_status', ''],
            ['group_status', ''],
            ['external_userid', '', 'external_userid'],
        ]);
        [$page, $limit] = $logService->getPageValue();
        $list           = $logService->getLogList($where, $page, $limit);
        $count          = $logService->count($where);
        return $this->success($logService->listData($list, $count));
    }

    /**
     * 渠道活码数据统计.
     * @return mixed
     */
    #[Get('statistics/{id}', '获取企微渠道活码数据统计')]
    public function statistics($id, WorkChannelCodeLogService $logService)
    {
        return $this->success($logService->getStatistics((int) $id, (string) $this->request->get('time', '')));
    }

    /**
     * 获取可选客户群.
     * @return mixed
     */
    #[Post('group_chat', '获取企微客户群列表')]
    public function groupChat(WorkGroupChatService $service)
    {
        $where = $this->request->postMore([
            ['status', ''],
            ['name', '', 'name_like'],
        ]);
        return $this->success($service->getSelect($where));
    }

    private function saveFields(): array
    {
        return [
            ['name', ''],
            ['group_id', 0],
            ['userids', []],
            ['tag_ids', []],
            ['welcome', []],
            ['welcome_text', ''],
            ['welcome_messages', []],
            ['invite_group', 1],
            ['code_id', 0],
            ['skip_verify', 1],
            ['verify_type', ''],
            ['status', 1],
            ['remark', ''],
        ];
    }

    protected function getRequestClassName(): string
    {
        return ChannelCodeRequest::class;
    }

    protected function getRequestFields(): array
    {
        return $this->saveFields();
    }
}
