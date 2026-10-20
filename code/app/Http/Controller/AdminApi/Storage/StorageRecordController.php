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

namespace App\Http\Controller\AdminApi\Storage;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\storage\StorageRecordRequest;
use App\Http\Service\Storage\StorageRecordService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 物资记录
 * Class StorageController.
 */
#[Prefix('ent/storage/record')]
#[Resource('/', false, except: ['create', 'show', 'edit', 'update'], names: [
    'index'   => '物资记录列表',
    'store'   => '保存物资记录接口',
    'destroy' => '删除物资记录接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class StorageRecordController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(StorageRecordService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取记录关联人员/部门列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('user', '获取物资记录关联人员/部门列表')]
    public function user()
    {
        [$status, $storageId, $types] = $this->request->getMore([
            ['status', ''],
            ['storage_id', ''],
            ['types', ''],
        ], true);
        return $this->success($this->service->getRecordUsers($status, $storageId));
    }

    /**
     * 获取记录关联人员/部门列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('users', '获取历史记录关联人员/部门列表')]
    public function historyUser()
    {
        $where = $this->request->getMore([
            ['types', ''],
            ['storage_type', ''],
            ['storage_id', ''],
            ['entid', 1],
        ]);
        return $this->success($this->service->getRecordHistoryUsers($where));
    }

    /**
     * 获取物资记录统计
     */
    #[Get('census', '物资记录统计')]
    public function census()
    {
        $where = $this->request->getMore([
            ['time', ''],
        ]);
        return $this->success($this->service->getRecordCensus($where));
    }

    /**
     * 获取维修记录详情.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('repair/{id}', '获取维修记录详情')]
    public function repair($id)
    {
        if (! $id) {
            return $this->fail('缺少物资记录ID');
        }
        return $this->success($this->service->getRepair($id));
    }

    /**
     * 添加.
     * @throws BindingResolutionException
     */
    public function store(): mixed
    {
        $data = $this->request()->postMore($this->getRequestFields());
        $res  = $this->service->resourceSave($data);
        $msg  = $this->message['store']['success'];
        if (in_array($data['types'], [1, 2, 3, 4, 5])) {
            $msg = match ((int) $data['types']) {
                2       => '物资归还成功',
                3       => '编号为' . $res->number . '物资提交维修成功',
                4       => '编号为' . $res->number . '物资提交报废成功',
                5       => '编号为' . $res->number . '物资维修处理成功',
                default => '物资领用成功'
            };
        }

        return $this->success($msg, is_object($res) ? ['id' => $res->id] : []);
    }

    protected function getRequestClassName(): string
    {
        return StorageRecordRequest::class;
    }

    protected function getRequestFields(): array
    {
        return [
            ['types', 0],
            ['user_type', 0],
            ['user_id', 0],
            ['storage', []],
            ['other', ''],
            ['price', 0],
            ['mark', ''],
        ];
    }

    protected function getSearchField(): array
    {
        return [
            ['name', ''],
            ['frame_id', ''],
            ['card_id', ''],
            ['storage_id', ''],
            ['storage_type', ''], // 物资类型
            ['time', ''],
            ['types', ''], // 记录类型
            ['cid', ''],
            ['status', ''],
            ['entid', 1],
        ];
    }
}
