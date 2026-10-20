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

namespace App\Http\Middleware;

use App\Jobs\System\EnterpriseLogJob;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;

/**
 * Class LogEnterprise.
 */
class LogEnterprise implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    protected array $filter = [
        'api/ent/enterprise/log',
        'api/ent/system/log',
    ];

    /**
     * @return mixed|void
     */
    public function before(Request $request)
    {
        try {
            $route = $request->route();
            $rule  = $route?->uri() ?? $request->path();
            if (in_array($rule, $this->filter, true) || ! str_starts_with($rule, 'api/')) {
                return;
            }

            $admin = auth('admin')->user();
            if (! $admin) {
                return;
            }

            EnterpriseLogJob::dispatch([
                'method'     => $request->method(),
                'uid'        => $admin->uid,
                'entid'      => 1,
                'user_name'  => $admin->name,
                'path'       => $rule,
                'event_name' => $route?->getName() ?: '未知',
                'last_ip'    => $request->server('HTTP_X_REAL_IP') ?: $request->ip(),
                'type'       => 'system',
                'terminal'   => get_os(),
            ])->onQueue(env('REDIS_QUEUE', 'CRMEB_OA'));
        } catch (\Throwable $e) {
        }
    }

    public function after($response)
    {
        // TODO: Implement after() method.
    }
}
