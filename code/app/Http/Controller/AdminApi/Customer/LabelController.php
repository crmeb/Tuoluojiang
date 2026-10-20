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

namespace App\Http\Controller\AdminApi\Customer;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Customer\LabelRequest;
use App\Http\Service\Customer\LabelService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 客户标签
 * Class LabelController.
 */
#[Prefix('ent/client/labels')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '获取客户标签列表接口',
    'store'   => '保存客户标签接口',
    'update'  => '修改客户标签接口',
    'destroy' => '删除客户标签接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class LabelController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(LabelService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 保存客户标签.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('save_labels', '保存客户标签')]
    public function saveLabels()
    {
        $data = $this->request->postMore([
            ['group', []],
            ['label', []],
        ]);
        $this->service->saveLabels($data['group'], $data['label']);
        return $this->success($this->message['update']['success']);
    }

    /**
     * 标签组排序.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('sort_labels', '标签组排序')]
    public function sortLabels()
    {
        [$group] = $this->request->postMore([
            ['label', []],
        ], true);
        $this->service->sortLabels($group);
        return $this->success('修改成功');
    }

    /**
     * 互相同步标签.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('auth_work_client_label', '互相同步标签')]
    public function authWorkClientLabel()
    {
        $this->service->authWorkClientLabel();

        return $this->success('已加入队列，正在同步中');
    }

    /**
     * 删除.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function destroy($id)
    {
        $labelId = $this->request->post('label_id', 0);
        if (! $id) {
            return $this->fail($this->message['destroy']['empty']);
        }
        if ($this->service->resourceDeleteLabel($id, (int) $labelId)) {
            return $this->success('操作成功');
        }
        return $this->fail('操作失败');
    }

    /**
     * 搜索字段.
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['pid', 0],
            ['group_type', ''],
        ];
    }

    protected function getRequestClassName(): string
    {
        return LabelRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['pid', 0],
            ['sort', 0],
            ['entid', 1],
            ['group_type', ''],
        ];
    }
}
