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

namespace App\Http\Middleware;

use App\Http\Service\Crud\SystemCrudDataShareService;
use App\Http\Service\System\RolesService;
use Tuoluojiang\Open\exceptions\AuthException;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class AuthCrud extends BaseMiddleware implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    private array $actions = [
        'GET'      => 1, // read
        'POST'     => 2, // create
        'PUT'      => 3, // update
        'DELETE'   => 4, // delete
        'TRANSFER' => 5, // transfer
        'SHARE'    => 6, // share
    ];

    public function before(Request $request)
    {
        $crudName = $request->route('name', '');
        if (! $crudName) {
            throw new AuthException('缺少必要的实体参数');
        }
        $method = $request->method();
        if (! $request->hasMacro('uuId')) {
            throw new AuthException('您没有权限操作', 410005);
        }

        $path = str_replace(['api/ent/', 'api/uni/'], '', $request->path());

        if (strtolower($method) === 'post') {
            switch ($path) {
                case 'crud/module/' . $crudName . '/list':
                    $method = 'GET';
                    break;
                case 'crud/module/' . $crudName . '/transfer':
                    $method = 'TRANSFER';
                    break;
                case 'crud/module/' . $crudName . '/share':
                    $method = 'SHARE';
                    break;
            }
        }
        if (preg_match('/crud\/module\/' . $crudName . '\/update_field\/[0-9]+/', $path)) {
            $method = 'PUT';
        }

        $userId = uuid_to_uid($request->uuId());
        if (in_array($method, array_keys($this->actions))) {
            try {
                $request->merge([
                    'system_user_id' => app()->get(RolesService::class)->getDataUids($userId, $crudName, $this->actions[$method]),
                ]);
            } catch (\Throwable $e) {
                switch ($this->actions[$method]) {
                    case 3:
                        $id = $request->route()->parameter('id');
                        if (! $id) {
                            throw new AuthException('缺少必要的参数');
                        }
                        if (! app()->get(SystemCrudDataShareService::class)->value(['crud_id' => $request->crudInfo->id, 'data_id' => $id, 'user_id' => $userId], 'is_update')) {
                            throw new AuthException('暂无权限在' . $request->crudInfo->table_name . '中修改数据');
                        }
                        break;
                    case 4:
                        $id = $request->route()->parameter('id');
                        if (! $id) {
                            throw new AuthException('缺少必要的参数');
                        }
                        if (! app()->get(SystemCrudDataShareService::class)->value(['crud_id' => $request->crudInfo->id, 'data_id' => $id, 'user_id' => $userId], 'is_delete')) {
                            throw new AuthException('暂无权限在' . $request->crudInfo->table_name . '中删除数据');
                        }
                        break;
                    default:
                        throw $e;
                }
            }
        }
    }

    public function after($response)
    {
        // TODO: Implement after() method.
    }
}
