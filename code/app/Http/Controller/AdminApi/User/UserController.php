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

namespace App\Http\Controller\AdminApi\User;

use App\Constants\CacheEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\user\UserRequest;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Company\CompanyApplyService;
use App\Http\Service\System\MenusService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\User\UserCardPerfectService;
use App\Http\Service\User\UserResumeService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 个人办公-用户管理
 * Class UserController.
 */
#[Prefix('ent/user')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class UserController extends AuthController
{
    public function __construct(AdminService $services)
    {
        parent::__construct();
        $this->service = $services;
        $this->middleware('auth:admin', ['except' => ['login', 'register']]);
    }

    /**
     * 获取个人用户信息.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('userInfo', '获取当前用户信息')]
    public function userInfo()
    {
        $info = $this->service->getInfo(auth('admin')->id(), ['id', 'uid', 'password', 'phone', 'name', 'avatar', 'work_member_id'], [
            'info' => fn ($query) => $query->select(['email', 'uid']),
        ]);
        if ($info['info']) {
            $info['email'] = $info['info']['email'];
        }
        $info['mcpKey'] = $this->service->getMcpKey((int) auth('admin')->id());
        return $this->success($info);
    }

    /**
     * 获取个人菜单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('menus', '获取当前用户菜单')]
    public function menus(MenusService $menusService, ModulePermissionService $modulePermissionService)
    {
        $authUser = auth('admin')->user();
        $adminId  = (int) auth('admin')->id();
        $userId   = $authUser->is_admin ? 'all' : $adminId;
        $cacheKey = 'user:menus:' . $this->entId . ':' . $userId . ':' . $adminId;
        $data     = Cache::tags([CacheEnum::TAG_ROLE])->remember($cacheKey, (int) sys_config('system_cache_ttl', 3600), function () use ($menusService, $modulePermissionService, $userId, $adminId) {
            $menu = $menusService->getUserMenus($userId);
            if (! $menu) {
                return [];
            }
            $roles       = $menusService->getUserButtons($userId);
            $permissions = $modulePermissionService->getUserAllPermissions($adminId);
            return compact('menu', 'roles', 'permissions');
        });

        if (! $data || empty($data['menu'])) {
            return $this->fail('暂无权限访问,请联系管理员分配权限!');
        }

        return $this->success($data);
    }

    /**
     * 修改当前用户信息.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     */
    #[Put('userInfo', '修改当前用户信息')]
    public function update(UserRequest $request)
    {
        [$avatar, $realName, $email, $phone, $password, $passwordConfirm, $verificationCode] = $this->request->postMore([
            ['avatar', ''],
            ['name', ''],
            ['email', ''],
            ['phone', ''],
            ['password', ''],
            ['password_confirm', ''],
            ['verification_code', ''],
        ], true);
        $userData = $data = [];
        if ($email) {
            $request->scene('update_email')->check();
            $userData['email'] = $email;
        }
        if ($phone && $verificationCode) {
            $request->scene('update_phone')->check();
            $data['phone'] = $phone;

            if ($this->service->exists(['phone' => $phone])) {
                return $this->fail('手机号已注册,请更换没有注册的手机号');
            }
        }
        if ($password && $passwordConfirm) {
            $request->scene('update_password')->check();
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
            $data['is_init']  = 0;
        }
        if ($realName) {
            $data['name'] = $realName;
        }
        if ($avatar) {
            $data['avatar'] = $avatar;
        }
        if ($userData) {
            app()->get(AdminInfoService::class)->update(auth('admin')->id(), $userData);
        }
        $data && app()->get(AdminService::class)->update(auth('admin')->id(), $data) && Cache::tags([CacheEnum::TAG_FRAME])->flush();
        return $this->success('common.update.succ', $data);
    }

    /**
     * 更新当前用户 MCP Key.
     *
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('mcp_key', '更新当前用户McpKey')]
    public function updateMcpKey()
    {
        return $this->success(['mcpKey' => $this->service->refreshMcpKey((int) auth('admin')->id())]);
    }

    /**
     * 验证密码规范.
     * @return mixed
     * @throws ValidationException
     */
    #[Post('checkpwd', '验证密码规范')]
    public function checkPwd(UserRequest $request)
    {
        $request->scene('update_password')->check();
        return $this->success('验证成功', tips: 0);
    }

    /**
     * 处理企业邀请.
     * @param mixed $id
     * @return mixed
     */
    public function apply(CompanyApplyService $services, UserCardPerfectService $perfectServices, $id)
    {
        $status = $this->request->get('status', 0);
        $res    = $services->setApply((int) $id, (int) $status, $this->uuid);
        if ($res) {
            if ($perfectServices->exists(['uid' => $this->uuid, 'status' => 0])) {
                return $this->success('处理成功', ['perfect' => 1]);
            }
            return $this->success('处理成功', ['perfect' => 0]);
        }
        return $this->success('处理失败');
    }

    /**
     * 我的简历.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('resume', '获取个人简历')]
    public function resume(UserResumeService $services): mixed
    {
        return $this->success($services->getInfo($this->uuid));
    }

    /**
     * 保存我的简历.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('resume_save', '保存个人简历')]
    public function resumeSave(UserResumeService $services): mixed
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['photo', ''],
            ['position', ''],
            ['card_id', ''],
            ['birthday', ''],
            ['age', ''],
            ['education', ''],
            ['education_image', ''],
            ['phone', ''],
            ['sex', 0],
            ['nation', ''],
            ['acad', ''],
            ['acad_image', ''],
            ['politic', ''],
            ['native', ''],
            ['address', ''],
            ['marriage', 0],
            ['work_years', ''],
            ['spare_name', ''],
            ['spare_tel', ''],
            ['email', ''],
            ['work_time', ''],
            ['trial_time', ''],
            ['formal_time', ''],
            ['treaty_time', ''],
            ['is_part', 0],
            ['work_years', 0],
            ['social_num', ''],
            ['fund_num', ''],
            ['bank_num', ''],
            ['bank_name', ''],
            ['graduate_name', ''],
            ['graduate_date', ''],
            ['card_front', ''],
            ['card_both', ''],
        ]);
        $services->saveInfo($data);
        return $this->success('保存成功');
    }
}
