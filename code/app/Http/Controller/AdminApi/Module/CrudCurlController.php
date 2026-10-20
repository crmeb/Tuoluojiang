<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Controller\AdminApi\Module;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\LogEnterprise;
use App\Http\Requests\ApiRequest;
use App\Http\Requests\Crud\SystemCrudCurlRequest;
use App\Http\Service\Crud\SystemCrudCurlService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 接口管理.
 */
#[Prefix('ent/crud')]
#[Middleware([AuthAdmin::class, AuthEnterprise::class, LogEnterprise::class])]
#[Resource(
    resource: 'curl',
    apiResource: false,
    except: ['show', 'create'],
    names: [
        'index'   => '获取接口数据列表接口',
        'store'   => '保存接口数据接口',
        'edit'    => '编辑接口数据接口',
        'update'  => '修改接口数据接口',
        'destroy' => '删除接口数据接口',
    ],
    parameters: ['curl' => 'id'],
    shallow: true
)]
class CrudCurlController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(SystemCrudCurlService $service)
    {
        $this->service = $service;
    }

    /**
     * 列表查询.
     * @return mixed
     */
    public function index()
    {
        $where = app()->get(ApiRequest::class)->getMore($this->getSearchField());
        $order = $where['order'] ?? '';
        unset($where['order']);

        if (! $order) {
            $order = ['id' => 'desc'];
        }

        return $this->success($this->service->getList(
            where: $where,
            field: ['title', 'id', 'url', 'is_pre', 'method', 'created_at'],
            sort: $order
        ));
    }

    /**
     * 获取编辑数据.
     * @param mixed $id
     * @return mixed
     */
    public function edit($id)
    {
        if (! $id) {
            return $this->fail($this->message['edit']['empty']);
        }
        $curl = $this->service->get($id);
        if (! $curl) {
            return $this->fail('没有查询到数据');
        }

        return $this->success($curl->toArray());
    }

    /**
     * 测试请求并返回请求数据.
     * @return mixed
     */
    #[Post('test_send', '测试请求并返回请求数据')]
    public function testRequest(ApiRequest $request)
    {
        $data = $request->postMore([
            ['url', ''],
            ['method', ''],
            ['headers', []],
            ['data', []],
        ]);

        if (! $data['url']) {
            return $this->fail('缺少请求地址');
        }

        $formData = [];
        foreach ($data['data'] as $item) {
            if ($item['value']) {
                $formData[$item['name']] = $item['value'];
            }
        }

        $headers = [];
        foreach ($data['headers'] as $item) {
            $headers[] = $item['name'] . ':' . $item['value'];
        }

        $res = $this->service->request($data['url'], $data['method'], $formData, $headers);

        $dataFlatten = $this->service->flattenArrayWithDots($res->toArray());

        return $this->success([
            'res'         => $res->toArray(),
            'key'         => array_keys($dataFlatten),
            'dataFlatten' => $dataFlatten,
        ]);
    }

    /**
     * 发送请求
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('send/{id}', '发送请求')]
    public function send($id)
    {
        if (! $id) {
            return $this->fail('缺少参数');
        }

        $resData     = $this->service->send((int) $id);
        $res         = $resData['response'];
        $dataFlatten = $this->service->flattenArrayWithDots($res);

        return $this->success([
            'res'         => $res,
            'key'         => array_keys($dataFlatten),
            'dataFlatten' => $dataFlatten,
        ]);
    }

    /**
     * 数据验证
     */
    protected function getRequestClassName(): string
    {
        return SystemCrudCurlRequest::class;
    }

    /**
     * 获取搜索字段.
     * @return array[]
     */
    protected function getSearchField(): array
    {
        return [
            ['title', ''],
            ['order', ''],
            ['method', ''],
        ];
    }

    /**
     * 获取请求字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['title', ''],
            ['url', ''],
            ['is_pre', 0],
            ['pre_url', ''],
            ['pre_method', ''],
            ['pre_headers', []],
            ['pre_data', []],
            ['pre_cache_time', 0],
            ['method', ''],
            ['headers', []],
            ['data', []],
        ];
    }
}
