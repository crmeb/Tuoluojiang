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

namespace App\Http\Controller\AdminApi\Work;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\CheckRuleCompany;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Work\WorkMemberService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 企业微信授权.
 */
#[Prefix('ent/work')]
class WorkAuthController extends AuthController
{
    public function __construct(WorkMemberService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->middleware([AuthAdmin::class, AuthEnterprise::class, CheckRuleCompany::class])->except(['getCorpConfig']);
    }

    /**
     * 获取企业微信配置.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('corp/config', '获取企业微信配置')]
    public function getCorpConfig(WorkConfig $config)
    {
        return $this->success([
            'corpid'         => $config->getCorpId(),
            'client_switch'  => sys_config('wechat_work_client_switch'),
            'forced_build'   => sys_config('wechat_work_forced_build', 0),
            'uid'            => auth('admin')->id(),
            'agentid'        => $config->getAppConfig(WorkConfig::TYPE_USER_APP)['agent_id'] ?? '',
            'isBindingAdmin' => (bool) app()->get(AdminService::class)->value(auth('admin')->id(), 'work_member_id'),
        ]);
    }

    /**
     * 绑定管理员.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('binding/admin', '绑定管理员')]
    public function bindingAdmin()
    {
        $code    = $this->request->post('code');
        $replace = $this->request->post('replace', 0);
        if (! $code) {
            return $this->fail('缺少CODE');
        }

        $this->service->bindingAdmin($code, auth('admin')->id(), (int) $replace);

        return $this->success('绑定成功');
    }

    /**
     * 解绑管理员.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('unbind/admin', '解绑管理员')]
    public function unbindAdmin()
    {
        $this->service->unbindAdmin(auth('admin')->id());

        return $this->success('解绑成功');
    }
}
