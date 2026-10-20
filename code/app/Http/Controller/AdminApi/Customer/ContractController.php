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
use App\Http\Requests\Customer\ContractRequest;
use App\Http\Service\Customer\ContractService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

#[Prefix('ent/client/contract_doc')]
#[Resource('/', false, except: ['create', 'show'], names: [
    'index'   => '合同签约列表',
    'store'   => '新增合同签约',
    'edit'    => '合同签约修改表单',
    'update'  => '修改合同签约',
    'destroy' => '删除合同签约',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log', 'module.switch'])]
class ContractController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(ContractService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 签约审批流程.
     * @return mixed
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('process', '签约审批流程')]
    public function process()
    {
        $data = $this->request->postMore([
            ['eid', ''],
            ['cid', []],
            ['doc_name', ''],
            ['sign_type', ''],
            ['term_type', ''],
            ['date_count', ''],
            ['start_date', ''],
            ['end_date', ''],
        ]);
        $signFile = $this->request->post('sign_file', []);
        $fileId   = $this->request->post('file_id', '');
        return $this->success($this->service->process($data, auth('admin')->id(), $signFile, $fileId));
    }

    /**
     * 获取任务信息.
     * @return mixed
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    #[Get('task/{id}', '获取合同转换任务结果')]
    public function taskInfo($taskId)
    {
        return $this->success($this->service->uploadResult($taskId));
    }

    /**
     * 获取合同签约人.
     * @return mixed
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    #[Get('signatory/{id}', '获取合同签约人')]
    public function signatory($id)
    {
        return $this->success($this->service->getSignatory((int) $id));
    }

    /**
     * 撤销合同签约.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('cancel/{id}', '撤销合同签约')]
    public function cancel($id)
    {
        $this->service->cancel((int) $id);
        return $this->success('操作成功');
    }

    /**
     * 关联签约订单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('link_order/{id}', '关联签约订单')]
    public function linkOrder($id)
    {
        $this->service->linkOrder((int) $id, (array) $this->request->post('cid', []));
        return $this->success('操作成功');
    }

    /**
     * 获取关联签约订单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('orders/{id}', '获取关联签约订单')]
    public function orders($id)
    {
        return $this->success($this->service->orders((int) $id, auth('admin')->id()));
    }

    /**
     * 线下签约文件上传.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('sign/{id}', '线下签约文件上传')]
    public function saveSignFile($id)
    {
        $this->service->saveSign((int) $id, (int) $this->request->post('file_id', ''), auth('admin')->id());
        return $this->success('操作成功');
    }

    protected function getRequestClassName(): string
    {
        return ContractRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['time', '', 'sign_time'],
            ['fail_status', ''],
            ['eid', ''],
            ['status', ''],
            ['view_search', ''],
            ['link_type', ''],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['eid', ''],
            ['cid', []],
            ['link_type', 2],
            ['doc_name', ''],
            ['sign_type', ''],
            ['term_type', ''],
            ['date_count', ''],
            ['start_date', ''],
            ['end_date', ''],
            ['sign_file', ''],
            ['file_id', ''],
            ['mark', ''],
            ['signatory', []],
            ['processInfo', []],
            ['productInfo', []],
        ];
    }
}
