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

namespace App\Jobs\Config;

use App\Http\Service\Admin\AdminService;
use Tuoluojiang\Open\services\SmsService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 用户绑定电子签角色.
 */
class SignStaffJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public function __construct() {}

    /**
     * @throws GuzzleException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public function handle()
    {
        $smsService = app(SmsService::class);
        $service    = app(AdminService::class);
        $roles      = $smsService->getOperatorRole();
        $role       = collect($roles['data'] ?? [])->first(fn ($role) => $role['label'] === '业务员')['value'] ?? '';
        if ($role) {
            LazyCollection::make($service->select(['status' => 1], with: ['info'], cursor: true))->each(function ($item) use ($smsService, $role) {
                try {
                    $result = $smsService->addSignOperator($item['name'], $item['phone'], $role, $item['info']['email'] ?? '');
                    if ($result) {
                        $item->e_sign   = 1;
                        $item->e_userid = $result['userid'];
                        $item->e_openid = $result['openid'];
                    } else {
                        $item->e_sign = 0;
                    }
                    $item->save();
                } catch (\Exception $e) {
                    Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
                }
            });
        }
    }
}
