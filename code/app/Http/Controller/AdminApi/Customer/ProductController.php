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

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Config\FormService;
use App\Http\Service\Customer\ProductService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 产品分类.
 */
#[Prefix('ent/client/products')]
#[Resource('/', false, except: ['index', 'show'], names: [
    'create'  => '获取添加产品接口',
    'store'   => '添加产品保存接口',
    'edit'    => '获取修改产品分类接口',
    'update'  => '修改产品保存接口',
    'destroy' => '删除产品接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ProductController extends AuthController
{
    public function __construct(ProductService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 列表数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('list', '产品列表')]
    public function index(): mixed
    {
        $where                 = $this->request->postMore($this->service->searchField());
        $where['product_type'] = $where['types'] == ViewSearchEnum::VIEW_PRODUCT ? '' : $where['types'];
        $where['types']        = ViewSearchEnum::VIEW_PRODUCT;
        return $this->success($this->service->getListByType($where));
    }

    /**
     * 添加产品表单接口.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function create(FormService $service): mixed
    {
        return $this->success($service->getFormDataWithType(CustomEnum::PRODUCT));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function store(FormService $service): mixed
    {
        $data                          = $this->request->postMore($service->getRequestFields(CustomEnum::PRODUCT));
        [$spec_type,$attr, $attrValue] = $this->request->postMore([
            ['spec_type', 0],
            ['attr', []],
            ['attrValue', []],
        ], true);
        $id = $this->service->saveProduct($data, auth('admin')->id(), $spec_type, $attr, $attrValue);
        return $this->success('common.insert.succ', ['id' => $id]);
    }

    /**
     * 获取产品数据.
     * @param mixed $id
     * @return mixed
     */
    public function edit($id)
    {
        if (! $id) {
            return $this->fail('缺少必要参数：产品ID');
        }
        return $this->success($this->service->getEditInfo((int) $id));
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '产品详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail($this->message['update']['empty']);
        }

        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update(FormService $service, $id)
    {
        $data                          = $this->request->postMore($service->getRequestFields(CustomEnum::PRODUCT));
        [$spec_type,$attr, $attrValue] = $this->request->postMore([
            ['spec_type', 0],
            ['attr', []],
            ['attrValue', []],
        ], true);
        if ($this->service->saveProduct($data, auth('admin')->id(), $spec_type, $attr, $attrValue, (int) $id)) {
            return $this->success('common.update.succ');
        }
        return $this->fail('common.update.fail');
    }

    /**
     * 删除数据.
     * @param mixed $id
     * @return mixed
     */
    public function destroy($id)
    {
        if (! $id) {
            return $this->fail('缺少必要参数：产品ID');
        }
        $this->service->delete($id);
        return $this->success('common.delete.succ');
    }

    /**
     * 获取产品属性.
     * @return mixed
     */
    #[Get('attrs', '获取产品属性列表')]
    public function getAttr()
    {
        $where = $this->request->getMore([
            ['pid', ''],
            ['name', '', 'name_like'],
            ['attr', '', 'attr_like'],
            ['types', ''],
            ['filter_stock', 0],
            ['warehouse_id', 0],
            ['sku_id', ''],
        ]);
        $data = $this->service->getAttrTree($where);
        return $this->success($data);
    }

    #[Get('real_attrs', '获取仓储产品属性列表')]
    public function getRealAttr()
    {
        $where = $this->request->getMore([
            ['pid', ''],
            ['name', '', 'name_like'],
            ['attr', '', 'attr_like'],
        ]);
        return $this->success($this->service->getRealProductAttrTree($where));
    }
}
