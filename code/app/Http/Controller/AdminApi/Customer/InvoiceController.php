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
use App\Http\Requests\Customer\InvoiceRequest;
use App\Http\Service\Customer\InvoiceLogService;
use App\Http\Service\Customer\InvoiceService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Tuoluojiang\Open\traits\SearchTrait;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 发票
 * Class InvoiceController.
 */
#[Prefix('ent/client/invoice')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '获取合同订单发票列表',
    'store'   => '保存合同订单发票接口',
    'update'  => '修改合同订单发票接口',
    'destroy' => '删除合同订单发票接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log', 'module.switch'])]
class InvoiceController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use SearchTrait;

    public function __construct(InvoiceService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 展示数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('list', '获取合同订单发票列表')]
    public function list(): mixed
    {
        $where = $this->request->getMore([
            ['types', ''],
            ['status', [1, -1, 4, 5]],
            ['name', '', 'name_like'],
            ['date', '', 'real_date'],
            ['time', '', 'created_at'],
        ]);
        $field = ['id', 'eid', 'uid', 'cid', 'name', 'title', 'ident', 'bank', 'types', 'num', 'price', 'amount', 'account', 'address', 'tel', 'collect_name', 'collect_tel', 'collect_email', 'mail_address', 'invoice_type', 'invoice_address', 'bill_date', 'mark', 'remark', 'card_remark', 'finance_remark', 'collect_type', 'real_date', 'status', 'created_at', 'updated_at', 'link_id', 'revoke_id'];
        return $this->success($this->service->listForFinance($where, $field, with: ['card', 'treaty', 'customer' => fn ($q) => $q->select(['customer_name', 'id'])]));
    }

    /**
     * 展示数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function index(): mixed
    {
        $where = $this->request->getMore($this->getSearchField());
        if (! $where['time_field']) {
            $where['time_field'] = 'time';
        }
        $field = ['id', 'eid', 'uid', 'cid', 'name', 'title', 'ident', 'bank', 'types', 'num', 'price', 'amount', 'account', 'address', 'tel', 'collect_name', 'collect_tel', 'collect_email', 'mail_address', 'invoice_type', 'invoice_address', 'bill_date', 'mark', 'remark', 'card_remark', 'finance_remark', 'collect_type', 'real_date', 'status', 'created_at', 'updated_at', 'link_id', 'revoke_id'];
        $data  = $this->service->getList($where, $field, with: ['card', 'treaty', 'customer' => fn ($q) => $q->select(['customer_name', 'id'])], uid: auth('admin')->id());
        return $this->success($data);
    }

    /**
     * 获取关联付款单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('bill/{id}', '获取关联付款单')]
    public function billList($id)
    {
        return $this->success($this->service->getBillList($id));
    }

    /**
     * 付款记录财务审核.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Post('status/{id}', '审核合同订单发票')]
    public function setStatus($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->request->postMore([
            ['remark', ''],
            ['attach_ids', []],
            ['is_send', 0],
            ['invoice_type', ''],
            ['invoice_address', ''],
            ['invoice_mail', ''],
            ['collect_name', ''],
            ['collect_tel', ''],
        ]);
        $this->service->resourceStatusUpdate((int) $id, $data, (int) $this->request->post('status', 0));
        return $this->success('common.operation.succ');
    }

    /**
     * 修改备注.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Put('mark/{id}', '修改发票备注')]
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
     * 客户发票转移.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('shift', '客户发票转移')]
    public function shift()
    {
        [$ids, $toUid] = $this->request->postMore([
            ['data', []],
            ['to_uid', 0],
        ], true);
        if (! $ids) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->shift($ids, $toUid);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 冲红表单.
     * @throws BindingResolutionException
     */
    #[Get('invalid_form/{id}', '发票冲红表单')]
    public function invalidForm($id): array
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        return $this->service->invalidApplyForm((int) $id);
    }

    /**
     * 冲红申请.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('invalid_apply/{id}', '发票冲红申请')]
    public function invalidApply($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        [$invalid, $remark] = $this->request->postMore([
            ['invalid', 1],
            ['remark', ''],
        ], true);

        if (! in_array($invalid, [-1, 1])) {
            return $this->fail('参数错误');
        }
        $this->service->invalidApply((int) $id, (int) $invalid, $remark);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 冲红审核.
     * @param mixed $id
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Put('invalid_review/{id}', '发票冲红审核')]
    public function invalidReview($id)
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        [$invalid, $remark] = $this->request->postMore([
            ['invalid', 2],
            ['remark', ''],
        ], true);

        if (! in_array($invalid, [2, 3])) {
            return $this->fail('参数错误');
        }
        $this->service->invalidReview((int) $id, auth('admin')->id(), (int) $invalid, $remark);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 获取累计/审核中/付款金额.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('price_statistics', '获取累计/审核中/付款金额')]
    public function getPriceStatistics(): mixed
    {
        [$eid, $cid] = $this->request->getMore([
            ['eid', ''],
            ['cid', ''],
        ], true);
        if (! $eid && ! $cid) {
            return $this->fail('common.empty.attrs');
        }
        $data = $this->service->priceStatistics($this->entId, $eid, $cid);
        return $this->success($data);
    }

    /**
     * 开票撤回.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('withdraw/{id}', '开票撤回')]
    public function withdraw($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        [$remark] = $this->request->postMore([
            ['remark', ''],
        ], true);
        $res = $this->service->withdraw($id, $remark);
        return $res ? $this->success('common.operation.succ') : $this->fail('common.operation.fail');
    }

    /**
     * 获取详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '获取发票详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $with = ['card', 'treaty', 'client', 'attachs', 'category'];
        return $this->success($this->service->getInfo(['id' => (int) $id, 'entid' => $this->entId], with: $with));
    }

    /**
     * 获取在线开票uri.
     * @return mixed
     * @throws BindingResolutionException
     * @throws GuzzleException
     * @throws \ReflectionException
     */
    #[Get('uri/{id}', '获取在线开票uri')]
    public function getInvoiceUri($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->getInvoiceUri((int) $id));
    }

    /**
     * 在线开票前端回调.
     * @param mixed $id
     * @return JsonResponse
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('status/{id}', '在线开票前端回调')]
    public function callback($id)
    {
        [$num, $serial_number] = $this->request->postMore([
            ['invoice_num', ''],
            ['invoice_serial_number', ''],
        ], true);
        return $this->success('开票成功', ['res' => $this->service->editStatus($id, $num, $serial_number)]);
    }

    /**
     * 保存发票关联付款单.
     */
    #[Put('bill/{id}', '保存发票关联付款单')]
    public function saveInvoiceBill($id)
    {
        [$billId] = $this->request->postMore([
            ['bill_id', []],
        ], true);
        $this->service->saveInvoiceBill((int) $id, (array) $billId);
        return $this->success('操作成功');
    }

    /**
     * 开票操作记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('record/{id}', '开票操作记录')]
    public function record($invoiceId): mixed
    {
        $invoiceId = (int) $invoiceId;
        if (! $invoiceId) {
            return $this->fail('common.empty.attrs');
        }
        $where = [
            'invoice_id' => $invoiceId,
            'entid'      => $this->entId,
        ];
        return $this->success(app(InvoiceLogService::class)->getList($where));
    }

    /**
     * 搜索字段.
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            //            ['way', ''],
            ['types', ''],
            ['status', ''],
            ['invoiced', ''],
            ['eid', ''],
            //            ['cid', ''],
            ['name', '', 'name_like'],
            ['time', '', 'time_data'],
            ['from', ''],
            ['category_id', ''],
            ['entid', 1],
            ['uid', auth('admin')->id()],
            ['time_field', 'time'],
            ['view_search', 2],
            ['scope_frame', ''],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return InvoiceRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['eid', 0],
            ['cid', 0],
            ['name', ''],
            ['amount', 0],
            ['types', ''],
            ['title', ''],
            ['ident', ''],
            ['bank', ''],
            ['account', ''],
            ['address', ''],
            ['tel', ''],
            ['collect_name', ''],
            ['collect_tel', ''],
            ['collect_type', ''],
            ['collect_email', ''],
            ['bill_date', ''],
            ['mark', ''],
            ['category_id', 0],
            ['bill_id', []],
            ['mail_address', ''],
        ];
    }
}
