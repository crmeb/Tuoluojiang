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

use App\Constants\ModuleEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Customer\PaymentRequest;
use App\Http\Service\Customer\PaymentService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 付款款记录
 * Class PaymentController.
 */
#[Prefix('ent/client/bill')]
#[Resource('/', false, except: ['create', 'show', 'edit', 'store', 'update'], names: [
    'index'   => '获取付款记录列表',
    'destroy' => '删除付款记录',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PaymentController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use SearchTrait;

    public function __construct(PaymentService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取列表.
     * @return mixed
     */
    #[Get('list', '付款记录列表')]
    public function list()
    {
        $where = $this->request->getMore([
            ['eid', ''],
            ['cid', ''],
            ['cate_id', ''],
            ['time', ''],
            ['status', ''],
            ['field_key', ''],
            ['name', ''],
            ['entid', 1],
            ['date', ''],
            ['status', ''],
        ]);
        $data = $this->service->getBillList($where);
        return $this->success($data);
    }

    /**
     * 付款记录财务审核.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setStatus($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->request->postMore([
            ['status', 0],
            ['eid', 0],
            ['cid', 0],
            ['cate_id', 0],
            ['num', 0],
            ['types', ''],
            ['mark', ''],
            ['date', ''],
            ['uid', $this->uuid],
            ['type_id', 0],
            ['attach_ids', []],
            ['end_date', ''],
            ['fail_msg', ''],
            ['bill_cate_id', 0],
            ['entid', 1],
        ]);
        $res = $this->service->resourceStatusUpdate((int) $id, $data);
        return $res ? $this->success('common.operation.succ') : $this->fail('common.operation.fail');
    }

    /**
     * 修改备注.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setMark($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        [$mark] = $this->request->postMore([
            ['mark', ''],
        ], true);
        $this->service->setMark($id, $mark);
        return $this->success('common.operation.succ');
    }

    /**
     * 获取累计付款金额/审核中金额.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('price_statistics/{eid}', '累计付款/审核中金额')]
    public function getPriceStatistics($eid): mixed
    {
        if (! $eid) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->service->priceStatistics($this->entId, (int) $eid);
        return $this->success($data);
    }

    /**
     * 撤回.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function withdraw($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $res = $this->service->withdraw((int) $id, $this->entId);
        return $res ? $this->success('common.operation.succ') : $this->fail('common.operation.fail');
    }

    /**
     * 待开票付款列表.
     */
    #[Get('un_invoiced_list', '待开票付款列表')]
    public function getUnInvoicedList(): mixed
    {
        [$eid, $invoiceId] = $this->request->getMore([
            ['eid', ''],
            ['invoice_id', ''],
        ], true);
        if (! $eid) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->service->unInvoiceList(['eid' => $eid, 'invoice_id' => $invoiceId, 'entid' => $this->entId]);
        return $this->success($data, tips: 0);
    }

    /**
     * 财务编辑.
     * @param mixed $id
     * @throws BindingResolutionException
     */
    public function financeUpdate($id): mixed
    {
        if (! $id) {
            return $this->fail($this->message['update']['empty']);
        }
        $data = $this->request()->postMore($this->getRequestFields());
        if ($this->service->resourceUpdate($id, $data, true)) {
            return $this->success($this->message['update']['success']);
        }
        return $this->fail($this->message['update']['fail']);
    }

    /**
     * 财务删除.
     * @param mixed $id
     */
    public function financeDelete($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $res = $this->service->financeDelete((int) $id, $this->entId);
        return $res ? $this->success('common.operation.succ') : $this->fail('common.operation.fail');
    }

    /**
     * 展示数据.
     * @return mixed
     */
    public function index()
    {
        $this->withScopeFrame('salesman_id', module: ModuleEnum::CUSTOMER);
        $where = $this->request->getMore($this->getSearchField());
        $with  = ['renew', 'card', 'treaty', 'client', 'contract', 'invoice', 'attachs' => fn ($q) => $q->select(['id', 'att_dir as src', 'relation_id', 'real_name'])];
        return $this->success($this->service->getList($where, with: $with));
    }

    /**
     * 获取详情.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $with = ['renew', 'card', 'treaty', 'client', 'attachs' => fn ($q) => $q->select(['id', 'att_dir as src', 'relation_id', 'real_name'])];
        return $this->success($this->service->getInfo(['id' => (int) $id, 'entid' => $this->entId], with: $with));
    }

    /**
     * 获取合同订单统计
     * @param mixed $cid
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('contract_statistics/{cid}', '合同订单统计')]
    public function contractStatistics($cid): mixed
    {
        if (! $cid) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->service->getContractStatistics((int) $cid, $this->entId);
        return $this->success($data);
    }

    /**
     * 获取客户统计
     * @throws \ReflectionException
     */
    #[Get('customer_statistics/{eid}', '客户统计')]
    public function customerStatistics($eid): mixed
    {
        if (! $eid) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->service->getCustomerStatistics((int) $eid, $this->entId);
        return $this->success($data);
    }

    /**
     * 获取续费到期
     * @param mixed $cid
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function renew_census($cid): mixed
    {
        if (! $cid) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->getRenewCensus(['cid' => (int) $cid, 'entid' => $this->entId, 'types' => 1]));
    }

    /**
     * 搜索字段.
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['eid', ''],
            ['cid', ''],
            ['cate_id', ''],
            ['time', '', 'time_data'],
            ['status', ''],
            ['time_field', 'date'],
            ['name', '', 'name_like'],
            ['entid', 1],
            ['type_id', 0],
            ['time', ''],
            ['date', ''],
            ['no_withdraw', ''],
            ['created_at', ''],
            ['invoice_id', ''],
            ['salesman_id', '', 'uid'],
            ['types', ''],
            ['sort', ['date', 'id']],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return PaymentRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['eid', 0],
            ['cid', 0],
            ['cate_id', 0],
            ['num', 0],
            ['types', ''],
            ['mark', ''],
            ['date', ''],
            ['uid', $this->uuid],
            ['type_id', 0],
            ['attach_ids', []],
            ['end_date', ''],
            ['bill_cate_id', 0],
            ['remind_id', 0],
        ];
    }
}
