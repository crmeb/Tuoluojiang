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

namespace App\Http\Controller\AdminApi;

use App\Http\Middleware\AuthAdmin;
use App\Http\Requests\common\AccountLoginRequest;
use App\Http\Requests\user\UserLoginRequest;
use App\Http\Service\Admin\AdminService;
use App\Constants\UserEnum;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 基础用户.
 */
#[Prefix('ent/user')]
class LoginController extends AuthController
{
    private const CLIENT_WEB = 'web';

    /**
     * 需登录路由.
     * @var array|string[]
     */
    protected array $guarded = [
        'logout',
    ];

    public function __construct(AdminService $service)
    {
        parent::__construct();
        $this->middleware([AuthAdmin::class])->only($this->guarded);
        $this->service = $service;
    }

    /**
     * 获取企业微信配置.
     * @return mixed
     */
    #[Get('corp/config', '获取企业微信配置')]
    public function getCorpConfig(WorkConfig $config)
    {
        return $this->success([
            'corpid'  => $config->getCorpId(),
            'agentid' => $config->getAppConfig(WorkConfig::TYPE_USER_APP)['agent_id'] ?? '',
        ]);
    }

    /**
     * 用户注册.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     * @throws \ReflectionException
     */
    #[Post('register', '用户注册')]
    public function register(UserLoginRequest $request): mixed
    {
        $request->scene('registerUser')->check();
        [$phone, $password] = $request->postMore([
            ['phone', ''],
            ['password', ''],
        ], true);
        $this->service->register($phone, $password);
        return $this->success('注册成功!', $this->service->login($phone, $password));
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info', '用户信息', 'auth.admin')]
    public function info()
    {
        return $this->success($this->service->loginInfo());
    }

    /**
     * 用户账号密码登录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     * @throws \ReflectionException
     */
    #[Post('login', '用户账号密码登录')]
    public function login(AccountLoginRequest $request): mixed
    {
        $request->scene('login')->check();
        [$account, $password] = $request->postMore([
            ['account', ''],
            ['password', ''],
        ], true);
        $this->service->checkUserAuth($account);
        return $this->success($this->service->login($account, $password, self::CLIENT_WEB, isSingle: UserEnum::SINGLE_LOGIN));
    }

    /**
     * 企业微信扫码登录.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('work_auth_login', '企业微信扫码登录')]
    public function workAuthLogin()
    {
        $code = $this->request->post('code', '');
        if (! $code) {
            return $this->fail('缺少CODE');
        }

        return $this->success($this->service->workAuthLogin($code, client: self::CLIENT_WEB, isSingle: UserEnum::SINGLE_LOGIN));
    }

    /**
     * 用户短信验证码登录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     * @throws \ReflectionException
     */
    #[Post('phone_login', '用户短信验证码登录')]
    public function phone_login(UserLoginRequest $request): mixed
    {
        $request->scene('phoneLogin')->check();
        [$phone] = $request->postMore([
            ['phone', ''],
        ], true);
        $this->service->checkUserAuth($phone);
        if (! $this->service->exists(['phone' => $phone])) {
            $this->service->register($phone, md5(microtime() . $phone));
        }
        return $this->success($this->service->phoneLogin($phone, self::CLIENT_WEB, isSingle: UserEnum::SINGLE_LOGIN));
    }

    /**
     * 刷新登录凭证.
     */
    #[Post('token/refresh', '刷新登录凭证')]
    public function refreshToken(): mixed
    {
        $refreshToken = (string) $this->request->post('refresh_token', '');
        if (! $refreshToken) {
            return $this->fail('缺少刷新TOKEN');
        }

        return $this->success($this->service->refreshToken($refreshToken), tips: 0);
    }

    /**
     * 获取扫码登录二维码参数.
     */
    #[Get('scan_key', '获取扫码登录二维码参数')]
    public function getScanCode(): mixed
    {
        return $this->success($this->service->scanCodeKey());
    }

    #[Post('scan_status', '获取扫码状态')]
    public function scanKeyStatus()
    {
        $key = $this->request->post('key', '');
        return $this->success($this->service->keyStatus($key));
    }

    /**
     * 用户修改密码
     * @throws BindingResolutionException|ValidationException
     */
    #[Put('save_pwd', '用户修改密码', 'auth.admin')]
    public function password(UserLoginRequest $request): mixed
    {
        $request->scene('updatePassword')->check();

        [$phone, $password] = $request->postMore([
            ['phone', ''],
            ['password', ''],
        ], true);

        $this->service->password(auth('admin')->id(), $phone, $password);

        return $this->success('common.update.succ');
    }

    /**
     * 用户退出登录.
     */
    #[Get('logout', '用户退出登录')]
    public function logout(): mixed
    {
        $this->service->logout();
        return $this->success('退出成功', tips: 0);
    }

    /**
     * 修改密码
     * @return mixed
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    #[Put('common/savePassword', '修改密码', 'auth.admin')]
    #[Put('savePassword', '修改密码')]
    public function savePassword(UserLoginRequest $request)
    {
        $request->scene('updatePassword')->check();
        [$phone, $password] = $request->postMore([
            ['phone', ''],
            ['password', ''],
        ], true);
        $this->service->password(auth('admin')->id(), (string) $phone, $password);

        return $this->success('common.update.succ');
    }
}
