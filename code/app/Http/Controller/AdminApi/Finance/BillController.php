<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Controller\AdminApi\Finance;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\bill\BillRequest;
use App\Http\Service\Finance\BillLogService;
use App\Http\Service\Finance\BillService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 财务流水记录
 * Class BillController.
 */
#[Prefix('ent/bill')]
#[Resource('/', false, except: ['show', 'index'], names: [
    'create'  => '获取财务流水创建接口',
    'store'   => '保存财务流水接口',
    'edit'    => '获取财务流水信息接口',
    'update'  => '修改财务流水接口',
    'show'    => '修改财务流水状态接口',
    'destroy' => '删除财务流水接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class BillController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(BillService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 财务流水列表
     * @return mixed
     * @throws BindingResolutionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('/list', '获取财务流水列表接口')]
    public function index()
    {
        $where = $this->request->postMore($this->getSearchField());
        return $this->success($this->service->getList($where));
    }

    /**
     * 资金统计图(总).
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('chart', '财务流水统计图')]
    public function billTrend()
    {
        [$type, $time, $cateId] = $this->request->postMore([
            ['type', ''],
            ['time', ''],
            ['cate_id', []],
        ], true);
        $cateId = $this->service->handleCateId($cateId);
        return $this->success($this->service->getTrend($time, 1, 1, true, $cateId, $type));
    }

    /**
     * 占比分析.
     *
     * @throws BindingResolutionException
     */
    #[Post('rank_analysis', '财务流水占比分析')]
    public function rankAnalysis(): mixed
    {
        [$time, $cateId, $types, $cateIds] = $this->request->postMore([
            ['time', ''],
            ['cate_id', 0],
            ['types', 1],
            ['cate_ids', []],
        ], true);
        $cateIds = $this->service->handleCateId($cateIds);
        return $this->success($this->service->getRankAnalysis($time, (int)$cateId, (int)$types, $cateIds));
    }

    /**
     * 收支记账记录.
     * @throws BindingResolutionException
     */
    #[Get('record/{id}', '财务流水记录')]
    public function logs(BillLogService $service, $id): mixed
    {
        if (!$id) {
            return $this->fail('common.empty.attrs');
        }
        $where = ['bill_list_id' => (int)$id, 'entid' => $this->entId];
        return $this->success($service->getList($where));
    }

    /**
     * 资金统计图(部分).
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('chart_part', '财务流水统计数据')]
    public function billChart()
    {
        [$time, $income, $expend] = $this->request->postMore([
            ['time', ''],
            ['income', ''],
            ['expend', ''],
        ], true);

        return $this->success($this->service->getTrend($time, $income, $expend));
    }

    /**
     * 批量导入资金记录.
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('import', '导入资金记录')]
    public function saveBill()
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        $this->service->saveBill($data, auth('admin')->id());

        return $this->success('导入成功');
    }

    /**
     * 搜索字段.
     *
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['types', ''],
            ['cate_id', []],
            ['time', ''],
            ['type_id', ''],
            ['entid', 1],
            ['name', '', 'name_like'],
            ['sort', 'created_at'],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return BillRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['cate_id', 0],
            ['types', 0],
            ['edit_time', ''],
            ['mark', ''],
            ['num', 0],
            ['type_id', 0],
            ['file_id', []],
            ['entid', 1],
            ['uid', $this->uuid],
        ];
    }
}
