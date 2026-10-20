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
use App\Http\Requests\enterprise\storage\StorageRequest;
use App\Http\Service\Storage\StorageService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 物资
 * Class StorageController.
 */
#[Prefix('ent/storage')]
#[Resource('list', false, except: ['create', 'show', 'edit', 'update'], names: [
    'index'   => '物资列表',
    'store'   => '物资入库',
    'destroy' => '删除物资接口',
], parameters: ['list' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class StorageController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(StorageService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 修改物资分类.
     * @return int
     * @throws BindingResolutionException
     */
    #[Post('list/cate', name: '物资分类修改')]
    public function updateCate()
    {
        [$ids,$cateId] = $this->request->postMore([
            ['ids', []],
            ['cate_id', 0],
        ], true);
        $this->service->updateCate((array) $ids, (int) $cateId);
        return $this->success('修改成功');
    }

    /**
     * 添加.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function store()
    {
        $data = $this->request()->postMore($this->getRequestFields());
        $res  = $this->service->resourceSave($data);
        if ($res) {
            if (is_object($res)) {
                return $this->success(__('common.operation.succ'), ['id' => $res->id]);
            }

            return $this->success(__('common.operation.succ'), is_array($res) ? $res : []);
        }
        return $this->fail(__('common.operation.fail'));
    }
    protected function getRequestClassName(): string
    {
        return StorageRequest::class;
    }

    protected function getRequestFields(): array
    {
        return [
            ['id', 0],
            ['cid', 0],
            ['name', ''],
            ['specs', ''],
            ['factory', ''],
            ['mark', ''],
            ['price', ''],
            ['types', 0],
            ['units', ''],
            ['number', 0],
            ['remark', ''],
            ['entid', 1],
        ];
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['cid', ''],
            ['types', 0],
            ['stock', ''],
            ['status', ''],
            ['time', ''],
            ['frame_id', ''],
            ['card_id', '', 'user_id'],
            ['receive', ''], // 是否为领用
            ['distinct', ''], // 是否去重
            ['entid', 1],
        ];
    }
}
