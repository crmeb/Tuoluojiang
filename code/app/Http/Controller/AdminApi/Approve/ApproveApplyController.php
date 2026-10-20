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

namespace App\Http\Controller\AdminApi\Approve;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\bill\BillCategoryRequest;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveFormService;
use App\Http\Service\Approve\ApproveProcessService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 申请记录.
 */
#[Prefix('ent/approve/apply')]
#[Resource('/', false, except: ['show', 'store', 'create'], names: [
    'index'   => '获取审批申请列表',
    'edit'    => '获取审批申请接口',
    'update'  => '修改审批申请接口',
    'destroy' => '删除审批申请接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ApproveApplyController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(ApproveApplyService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 修改获取详情.
     * @param mixed $id
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function edit($id)
    {
        $where = $this->request->getMore([
            ['types', ''],
        ]);
        if (! $id) {
            return $this->fail($this->message['edit']['empty']);
        }
        $data = $this->service->resourceEdit((int) $id, $where);
        return $this->success(is_array($data) ? $data : $data->toArray());
    }

    /**
     * 流程审批.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Post('verify/{id}/{status}', '处理审批申请')]
    public function verify($id, $status)
    {
        $content = (string) $this->request->input('content', '');
        $this->service->verify((int) $id, auth('admin')->id(), (int) $status, $content);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 获取审批申请表单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('form/{id}', '获取审批申请表单')]
    public function applyForm(ApproveFormService $services, $id)
    {
        $data = $this->request->getMore([
            ['customer_id', 0],
            ['bill_id', []],
            ['invoice_id', 0],
            ['contract_id', 0],
        ]);
        return $this->success($services->getApplyForm((int) $id, auth('admin')->id(), $data, $this->origin));
    }

    /**
     * 获取审批申请流程.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('form/{id}', '获取审批人员列表')]
    public function verifyForm(ApproveFormService $formServices, ApproveProcessService $services, $id)
    {
        $uniqueds = $formServices->getUniques((int) $id);
        if (! $uniqueds) {
            return $this->fail(__('common.empty.attrs'));
        }
        foreach ($uniqueds as $unique) {
            $fields[] = [trim($unique['value'], '\"'), ''];
        }
        $data = $this->request->postMore($fields);
        return $this->success($services->verifyForm($data, $id, auth('admin')->id()));
    }

    /**
     * 保存审批申请.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('save/{id}', '保存审批申请')]
    public function save(ApproveFormService $formService, $id)
    {
        if (! $id) {
            return $this->fail(__('common.empty.attr', ['attr' => 'id']));
        }
        [$form, $process, $approveId, $apply_id, $linkId] = $this->request->postMore([
            ['formInfo', []],
            ['processInfo', []],
            ['approve_id', $id],
            ['apply_id', 0],
            ['link_id', 0],
        ], true);
        foreach ($formService->getUniques((int) $id) as $v) {
            if (! isset($form[$v['value']])) {
                return $this->fail(__('common.empty.attr', ['attr' => $v['label']]));
            }
        }
        $this->service->saveForm($form, $process, (int) $approveId, (int) $apply_id, auth('admin')->id(), linkId: (int) $linkId);
        return $this->success(__('common.insert.succ'));
    }

    /**
     * 撤销申请.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('revoke/{id}', '撤销申请')]
    public function revoke($id)
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        $this->service->revokeApply($id, auth('admin')->id(), $this->request->post('info', ''));
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 获取筛选导出数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('export', '导出审批记录')]
    public function export()
    {
        $where = $this->request->getMore([
            ['number', ''],
            ['types', 0],
            ['approve_id', ''],
            ['status', ''],
            ['time', 'last month'],
            ['frame_id', ''],
            ['name', ''],
            ['verify_status', ''],
            ['entid', 1],
        ]);
        if (! $where['time']) {
            $where['time'] = 'last month';
        }
        if (! $where['approve_id']) {
            return $this->fail('请选择审批类型');
        }
        return $this->success($this->service->getListForExport($where));
    }

    /**
     * 审批催办.
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('urge/{id}', '审批催办')]
    public function urge($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $this->service->urge((int) $id, auth('admin')->id());
        return $this->success('操作成功');
    }

    /**
     * 审批加签.
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Post('sign/{id}', '审批加签')]
    public function sign($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $data = $this->request->postMore([
            ['user', []],
            ['types', 0],
            ['examine_mode', ''],
            ['info', ''],
        ]);
        $this->service->addSign((int) $id, auth('admin')->id(), $data);
        return $this->success('操作成功');
    }

    /**
     * 审批加签.
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Post('transfer/{id}', '审批转审')]
    public function transfer($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $data = $this->request->postMore([
            ['user', []],
            ['info', ''],
        ]);
        $this->service->addTransfer((int) $id, auth('admin')->id(), $data);
        return $this->success('操作成功');
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['icon', ''],
            ['color', ''],
            ['info', ''],
            ['entid', 1],
            ['uuid', $this->uuid],
        ];
    }

    protected function getRequestClassName(): string
    {
        return BillCategoryRequest::class;
    }

    /**
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['number', ''],
            ['types', 0],
            ['approve_id', ''],
            ['status', ''],
            ['time', ''],
            ['frame_id', ''],
            ['name', ''],
            ['verify_status', ''],
            ['examine', 1],
            ['entid', 1],
        ];
    }
}
