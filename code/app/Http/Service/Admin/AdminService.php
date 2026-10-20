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

namespace App\Http\Service\Admin;

use App\Constants\CacheEnum;
use App\Constants\CommonEnum;
use App\Constants\NoticeEnum;
use App\Constants\System\ConfigEnum;
use App\Constants\UserEnum;
use App\Events\SystemMessageEvent;
use App\Http\Contract\User\UserInterface;
use App\Http\Dao\Admin\AdminDao;
use App\Http\Model\Admin\Admin;
use App\Http\Service\Assess\AssessScoreService;
use App\Http\Service\Assess\UserAssessService;
use App\Http\Service\Cloud\CloudFileService;
use App\Http\Service\Company\CompanyService;
use App\Http\Service\Company\CompanyUserChangeService;
use App\Http\Service\Company\CompanyUserEducationService;
use App\Http\Service\Company\CompanyUserWorkService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameScopeService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\Position\PositionJobService;
use App\Http\Service\Storage\StorageRecordService;
use App\Http\Service\User\UserCardPerfectService;
use App\Http\Service\User\UserChangeService;
use App\Http\Service\User\UserResumeService;
use App\Http\Service\User\UserTokenService;
use App\Http\Service\Work\WorkMemberService;
use App\Task\frame\FrameCensusTask;
use App\Task\user\SaveHistoryTask;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\utils\Regex;
use FormBuilder\Exception\FormBuilderException;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 基础用户.
 * @method adminListSearch(array $where, array $field = ['*'], int $page = 1, int $limit = 15, $sort = null, array $with = [])
 * @method listSearch(array $where, int $page = 0, int $limit = 0, $sort = null, array $with = [])
 * @method info(int $id, array $field = ['*'])
 * @method getSortData(array $where, array $sortValues, array $field = ['*'])
 * @mixin AdminDao
 */
class AdminService extends BaseService implements UserInterface
{
    public function __construct(AdminDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo(int $uid, array $field = ['*'], array $with = []): array
    {
        return $this->dao->get($uid, $field, $with)?->toArray() ?: [];
    }

    /**
     * 获取或初始化用户 MCP 工具调用唯一值.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getMcpKey(int $uid): string
    {
        $mcpKey = (string) $this->dao->value($uid, 'mcp_key');
        if ($mcpKey !== '') {
            return $mcpKey;
        }

        return $this->refreshMcpKey($uid);
    }

    /**
     * 刷新用户 MCP 工具调用唯一值.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function refreshMcpKey(int $uid): string
    {
        $mcpKey = Admin::generateMcpKey();
        $this->dao->update($uid, ['mcp_key' => $mcpKey]);

        return $mcpKey;
    }

    /**
     * 获取用户信息.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function loginInfo(): array
    {
        $admin      = auth('admin')->user()->toArray();
        $enterprise = [];
        if ($admin) {
            $admin['frames'] = app()->get(FrameAssistService::class)->getUserFrames($admin['uid']);
            $admin['job']    = app()->get(PositionJobService::class)->get($admin['job'])?->toArray();
            $enterprise      = app()->get(CompanyService::class)->get(['id' => 1], ['title', 'enterprise_name', 'enterprise_name_en', 'id as entid', 'logo', 'uniqued'])?->toArray();
            if ($enterprise) {// 企业考核相关配置信息
                $enterprise['maxScore']        = app()->get(AssessScoreService::class)->max(['entid' => 1], 'max') ?: 0;
                $enterprise['culture']         = sys_config('enterprise_culture');
                $enterprise['compute_mode']    = (int) sys_config('assess_compute_mode', 1);
                $enterprise['customer_switch'] = sys_more([
                    ConfigEnum::LEAD_MODULE_SWITCH['key'],
                    ConfigEnum::OPPORTUNITY_MODULE_SWITCH['key'],
                    ConfigEnum::CONTRACT_MODULE_SWITCH['key'],
                    ConfigEnum::INVOICE_MODULE_SWITCH['key'],
                    ConfigEnum::ORDER_MODULE_SWITCH['key'],
                    ConfigEnum::LIAISON_MODULE_SWITCH['key'],
                    ConfigEnum::CUSTOMER_MODULE_SWITCH['key'],
                ]);
            }

            $admin['real_name'] = $admin['name'];
            if ($admin['work_member_id']) {
                $admin['work'] = app()->get(WorkMemberService::class)->get($admin['work_member_id'], ['name', 'avatar'])?->toArray();
            }
        }
        return ['userInfo' => $admin, 'enterprise' => $enterprise];
    }

    /**
     * 用户登陆.
     * @param mixed $origin
     * @param mixed $isSingle
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function login(string $account, string $password, string $client = '', string $mac = '', string $clientId = '', $origin = CommonEnum::ORIGIN_WEB, bool $isSingle = UserEnum::MULTIPORT_LOGIN): array
    {
        $key      = md5('login_error_count_' . $account);
        $flag     = preg_match(Regex::PHONE_NUMBER, $account);
        $userInfo = $this->dao->get([$flag ? 'phone' : 'account' => $account]);
        if (! $userInfo) {
            $userInfo = $this->dao->create([
                'name'     => substr($account, 0, 3) . '****' . substr($account, 7, 4),
                'phone'    => $flag ? $account : '',
                'account'  => $flag ? '' : $account,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'status'   => 1,
                'avatar'   => link_file('/static/admin_face/face' . rand(1, 6) . '.png'),
            ]);
        }
        if ($userInfo->status == UserEnum::USER_LOCKING || app()->get(AdminInfoService::class)->value($userInfo->id, 'type') == 4) {
            throw $this->exception('您的账号已被锁定,无法登录!');
        }
        if (Cache::tags([CacheEnum::TAG_OTHER])->has($key) && Cache::tags([CacheEnum::TAG_OTHER])->get($key) >= (int) sys_config('login_error_count', 5)) {
            throw $this->exception('账号或密码错误次数已达上限请稍后再试!');
        }
        if (! password_verify($password, $userInfo->password)) {
            $this->lockLogin($key);
            throw $this->exception('账号或密码不正确');
        }
        $token = auth('admin')->login($userInfo, true);
        $this->lockLogin($key, true);
        if (! $token) {
            throw $this->exception('token创建失败');
        }
        $userInfo->last_ip = app('request')->ip();
        // 记录unipush通道ID
        if ($clientId) {
            $userInfo->client_id  = $clientId;
            $userInfo->uni_online = 1;
        }
        ++$userInfo->login_count;
        $userInfo->save();
        return match ($origin) {
            CommonEnum::ORIGIN_WEB, CommonEnum::ORIGIN_UNI => app()->get(UserTokenService::class)
                ->issueLoginTokens($userInfo, $token, $client, $mac, app('request')->ip(), $this->resolveSingleLogin($isSingle)),
            default => [],
        };
    }

    /**
     * 用户手机号登陆.
     * @param mixed $origin
     * @param mixed $isSingle
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function phoneLogin(string $account, string $client = '', string $mac = '', string $clientId = '', $origin = CommonEnum::ORIGIN_WEB, bool $isSingle = UserEnum::MULTIPORT_LOGIN): array
    {
        $flag     = preg_match(Regex::PHONE_NUMBER, $account);
        $userInfo = $this->dao->get([$flag ? 'phone' : 'account' => $account]);
        if (! $userInfo) {
            $userInfo = $this->dao->create([
                'name'     => substr($account, 0, 3) . '****' . substr($account, 7, 4),
                'phone'    => $flag ? $account : '',
                'account'  => $flag ? '' : $account,
                'password' => password_hash('888888', PASSWORD_BCRYPT),
                'status'   => 1,
                'avatar'   => link_file('/static/admin_face/face' . rand(1, 6) . '.png'),
            ]);
        }
        if ($userInfo->status == UserEnum::USER_LOCKING || app()->get(AdminInfoService::class)->value($userInfo->id, 'type') == 4) {
            throw $this->exception('您的账号已被锁定,无法登录!');
        }
        $token = auth('admin')->login($userInfo, true);
        if (! $token) {
            throw $this->exception('token创建失败');
        }
        $userInfo->last_ip = app('request')->ip();
        // 记录unipush通道ID
        if ($clientId) {
            $userInfo->client_id  = $clientId;
            $userInfo->uni_online = 1;
        }
        ++$userInfo->login_count;
        $userInfo->save();
        $clientId && app(NoticeRecordService::class)->updatePushBadge($userInfo->id);
        return match ($origin) {
            CommonEnum::ORIGIN_WEB, CommonEnum::ORIGIN_UNI => app()->get(UserTokenService::class)
                ->issueLoginTokens($userInfo, $token, $client, $mac, app('request')->ip(), $this->resolveSingleLogin($isSingle)),
            default => [],
        };
    }

    /**
     * 企业微信扫码登录.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function workAuthLogin(string $code, string $origin = CommonEnum::ORIGIN_WEB, string $client = '', string $mac = '', bool $isSingle = UserEnum::MULTIPORT_LOGIN)
    {
        try {
            $userInfo = app()->get(Work::class)->auth()->getUserInfo($code);
        } catch (\Throwable $e) {
            throw $this->exception($e->getMessage());
        }

        if (empty($userInfo['userid'])) {
            throw $this->exception('当前扫码非企业内部成员，无法登录');
        }

        $id = app()->get(WorkMemberService::class)->value(['userid' => $userInfo['userid']], 'id');
        if (! $id) {
            throw $this->exception('没有查到成员信息');
        }

        $userInfo = $this->dao->get(['work_member_id' => $id]);
        if (! $userInfo) {
            throw $this->exception('当前成员没有绑定账号');
        }

        if ($userInfo->status == UserEnum::USER_LOCKING || app()->get(AdminInfoService::class)->value($userInfo->id, 'type') == 4) {
            throw $this->exception('您的账号已被锁定,无法登录!');
        }
        $token = auth('admin')->login($userInfo, true);
        if (! $token) {
            throw $this->exception('token创建失败');
        }
        $userInfo->last_ip = app('request')->ip();

        ++$userInfo->login_count;
        $userInfo->save();

        return match ($origin) {
            CommonEnum::ORIGIN_WEB, CommonEnum::ORIGIN_UNI => app()->get(UserTokenService::class)
                ->issueLoginTokens($userInfo, $token, $client, $mac, app('request')->ip(), $this->resolveSingleLogin($isSingle)),
            default => [],
        };
    }

    /**
     * 按系统配置控制单端登录；关闭后保留所有已登录端。
     */
    private function resolveSingleLogin(bool $isSingle): bool
    {
        if (! $isSingle) {
            return false;
        }

        return (int) sys_config(ConfigEnum::SINGLE_LOGIN_SWITCH['key'], ConfigEnum::SINGLE_LOGIN_SWITCH['value']) === 1;
    }

    /**
     * 用户注册.
     * @param mixed $phone
     * @param mixed $password
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function register($phone, $password): array
    {
        if ($this->dao->count(['phone' => $phone])) {
            throw $this->exception('手机号码已注册,请直接登录');
        }

        return $this->dao->create([
            'phone'    => $phone,
            'name'     => substr($phone, 0, 3) . '****' . substr($phone, 7, 4),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'avatar'   => link_file('/static/admin_face/face' . rand(1, 6) . '.png'),
        ])->toArray();
    }

    /**
     * 用户修改密码
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function password(?int $uid, string $phone, string $password): int
    {
        $info = $this->dao->get(['phone' => $phone])?->toArray();
        if (! $info) {
            throw $this->exception('您的手机号码尚未注册');
        }
        if ($uid && $uid != $info['id']) {
            throw $this->exception('无法修改其他用户密码');
        }
        return $this->dao->update($info['id'], ['password' => password_hash($password, PASSWORD_BCRYPT), 'is_init' => 0]);
    }

    /**
     * 系统修改用户密码
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updatePasswordFromUid(string $uid, string $password): int
    {
        if (! $uid) {
            throw $this->exception('用户ID不能为空');
        }
        return $this->dao->update(['uid' => $uid], ['password' => password_hash($password, PASSWORD_BCRYPT), 'is_init' => 0]);
    }

    /**
     * 退出登录.
     */
    public function logout(): void
    {
        app()->get(UserTokenService::class)->revokeByAccessToken(request()->bearerToken());
        auth('admin')->logout();
    }

    /**
     * 刷新登录凭证.
     */
    public function refreshToken(string $refreshToken): array
    {
        return app()->get(UserTokenService::class)->refreshAccessToken($refreshToken);
    }

    /**
     * 获取扫码登录参数.
     */
    public function scanCodeKey(): array
    {
        $key = password_hash(uniqid('scan'), PASSWORD_BCRYPT);
        Cache::tags([CacheEnum::TAG_OTHER])->add('scan.key.' . $key, 0, 180);
        $expire_time = sys_config('verify_expire_time', 3);
        $expire_time = Carbon::now()->addMinutes($expire_time)->toDateTimeString();
        return compact('key', 'expire_time');
    }

    /**
     * 扫码参数状态
     * @param mixed $key
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function keyStatus($key): array
    {
        if (($phone = $this->dao->value(['scan_key' => $key], 'phone')) && $key) {
            Cache::tags([CacheEnum::TAG_OTHER])->delete('scan.key.' . $key);
            $this->dao->update(['phone' => $phone], ['scan_key' => '']);
            return $this->phoneLogin($phone, 'web', isSingle: UserEnum::SINGLE_LOGIN);
        }
        if (Cache::tags([CacheEnum::TAG_OTHER])->has('scan.key.' . $key)) {
            if (Cache::tags([CacheEnum::TAG_OTHER])->get('scan.key.' . $key)) {
                return [
                    'status' => 1,
                    'msg'    => '已扫码',
                ];
            }
            return [
                'status' => 0,
                'msg'    => '未扫码',
            ];
        }
        return [
            'status' => -1,
            'msg'    => '参数已失效,请重新获取',
        ];
    }

    /**
     * 用户绑定扫码参数.
     */
    public function scanWithUser(string $key, int $uid): bool
    {
        if (Cache::tags([CacheEnum::TAG_OTHER])->has('scan.key.' . $key)) {
            Cache::tags([CacheEnum::TAG_OTHER])->put('scan.key.' . $key, $uid, 180);
            return true;
        }
        return false;
    }

    /**
     * 确认扫码登录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function scanLogin(int $uid, string $key, int|string $status = ''): void
    {
        $user = $this->dao->get($uid);
        if (! $user) {
            throw $this->exception('用户不存在');
        }
        if (Cache::tags([CacheEnum::TAG_OTHER])->has('scan.key.' . $key) && Cache::tags([CacheEnum::TAG_OTHER])->get('scan.key.' . $key) == $uid) {
            if ($status) {
                $user->scan_key = $key;
                $user->save();
            } else {
                Cache::tags([CacheEnum::TAG_OTHER])->delete('scan.key.' . $key);
            }
            Cache::tags([CacheEnum::TAG_OTHER])->delete(md5('scan_key' . $key));
        }
    }

    /**
     * 修改用户信息.
     * @param mixed $uid
     * @param mixed $params
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateInfo($uid, $params): bool
    {
        [$email, $phone, $password, $passwordConfirm, $sex, $status, $remark] = array_values($this->filterParams($params, ['email', 'phone', 'password', 'password_confirm', 'sex', 'status', 'remark']));
        $data                                                                 = [];
        if ($email) {
            $data['email'] = $email;
        }
        if ($phone) {
            $data['phone'] = $phone;
            if ($this->dao->exists(['phone' => $phone, 'not_uid' => $uid])) {
                throw $this->exception('手机号重复');
            }
        }
        if ($password && $passwordConfirm) {
            if ($password != $passwordConfirm) {
                throw $this->exception('两次输入的密码不一致');
            }
            $data['password'] = $password;
        }
        if ($sex !== '') {
            $data['sex'] = $sex;
        }
        if ($status !== '') {
            $data['status'] = $status;
        }
        if ($remark) {
            $data['remark'] = $remark;
        }
        if ($data) {
            $this->dao->update($uid, $data);
        }

        return true;
    }

    /**
     * 添加锁定登录.
     */
    public function lockLogin(string $key, bool $clear = false): void
    {
        if ($clear) {
            Cache::tags([CacheEnum::TAG_OTHER])->delete($key);
        } else {
            if (Cache::tags([CacheEnum::TAG_OTHER])->has($key)) {
                Cache::tags([CacheEnum::TAG_OTHER])->increment($key);
            } else {
                Cache::tags([CacheEnum::TAG_OTHER])->add($key, 1, (int) sys_config('login_time_out', 1) * 60 * 60);
            }
        }
    }

    /**
     * 用户名片列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function adminList(array $where, int $page = 0, int $limit = 0, array $sort = ['admin_info.work_time', 'admin_info.id']): array
    {
        if (is_array($where['types']) && count($where['types']) == 1 && $where['types'][0] == 4) {
            $sort = ['admin_info.quit_time', 'admin_info.created_at'];
        }
        switch ($where['types']) {
            case [1, 2, 3]:
                $where['work_time'] = $where['time'] ?? '';
                break;
            case [4]:
                $where['quit_time'] = $where['time'] ?? '';
                break;
        }
        unset($where['time']);
        if (! $page || ! $limit) {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->adminListSearch($where, ['*'], $page, $limit, sort: $sort, with: [
            'frames',
            'job' => function ($query) {
                $query->select(['id', 'name', 'describe']);
            }, ])->get()?->toArray();
        foreach ($list as &$value) {
            if ($value['frames']) {
                break;
            }
            $frameIds = app()->get(FrameAssistService::class)->dao->setTrashed()->column(['user_id' => $value['id']], ['frame_id', 'is_mastart', 'is_admin', 'deleted_at']) ?: [];
            $value    = $this->getValue($frameIds, $value);
        }
        $count = $this->dao->adminListSearch($where)->count();

        return $this->listData($list, $count);
    }

    /**
     * 用户名片详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function adminInfo(int $id): array
    {
        $userInfo = $this->dao->adminInfo($id, [
            'works' => function ($query) {
                $query->orderByDesc('id');
            },
            'educations' => function ($query) {
                $query->orderByDesc('id');
            },
            'job' => function ($query) {
                $query->select(['id', 'name', 'describe']);
            },
            'scope' => function ($query) {
                $query->select(['frame.id', 'name', 'enterprise_user_scope.uid']);
            },
            'frames',
            'manage_frames',
            'super' => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'uid']),
        ])?->toArray();
        if (! $userInfo) {
            throw $this->exception('企业用户信息不存在');
        }
        $userInfo['is_admin']     = 0;
        $userInfo['manage_frame'] = [];
        $userInfo['superior']     = $userInfo['super'] ?: [];
        if ($userInfo['manage_frames']) {
            $userInfo['manage_frame'] = array_column($userInfo['manage_frames'], 'id');
            $userInfo['is_admin']     = 1;
        }
        if (! $userInfo['frames']) {
            $frameIds = app()->get(FrameAssistService::class)->dao->setTrashed()->column(['user_id' => $id], ['frame_id', 'is_mastart', 'is_admin']) ?: [];
            $userInfo = $this->getValue($frameIds, $userInfo);
        }
        return $userInfo;
    }

    /**
     * 创建企业用户.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function createAdmin(array $data = [], int $type = 0, array $frameInfo = [], bool $import = false): bool
    {
        $preg = '/^(13[0-9]|14[01456879]|15[0-35-9]|16[2567]|17[0-8]|18[0-9]|19[0-35-9])\d{8}$/';
        if ($data['phone'] && ! preg_match($preg, $data['phone'])) {
            throw $this->exception('请检查手机号是否正确');
        }
        if ($this->dao->exists(['phone' => $data['phone']])) {
            throw $this->exception('企业存在相同记录，请勿重复操作');
        }
        $frameAssist = app()->get(FrameAssistService::class);
        if (! ($frameInfo['frame_id'] ?? [])) {
            $topFrameId                 = (int) app()->get(FrameService::class)->topFrameId();
            $frameInfo['frame_id']      = [$topFrameId];
            $frameInfo['main_id']       = (int) ($frameInfo['main_id'] ?? 0) ?: $topFrameId;
            $frameInfo['manage_frames'] = [];
            $frameInfo['is_admin']      = 0;
        }
        $frameInfo = $this->normalizeAdminFrameInfo($frameInfo, 'main_id');
        $res       = $this->transaction(function () use ($data, $frameInfo, $type, $import, $frameAssist) {
            $admin = $this->dao->create([
                'phone'    => $data['phone'],
                'name'     => $data['name'],
                'password' => password_hash('888888', PASSWORD_BCRYPT),
                'avatar'   => $data['photo'] ?: (link_file('/static/admin_face/face' . rand(1, 6) . '.png')),
                'roles'    => app()->get(FrameService::class)->column(['ids' => $frameInfo['frame_id'] ?? 0], 'role_id'),
                'job'      => $data['position'],
                'status'   => $type === 1 ? 1 : 0,
            ])->toArray();
            if (! $admin) {
                throw $this->exception('创建用户信息失败！');
            }
            $import && $data['type'] = match ($type) {
                1       => 1,
                2       => 4,
                default => 0
            };
            if ($data['name']) {
                $data['letter'] = get_name_letter(trim($data['name']));
            }
            if (isset($data['works']) && $data['works']) {
                $userWorkService = app()->get(CompanyUserWorkService::class);
                foreach ($data['works'] as $work) {
                    $work['card_id'] = $admin['id'];
                    $work['user_id'] = $admin['id'];
                    $userWorkService->create($work);
                }
            }
            if (isset($data['educations']) && $data['educations']) {
                $userEducationService = app()->get(CompanyUserEducationService::class);
                foreach ($data['educations'] as $education) {
                    $education['card_id'] = $admin['id'];
                    $education['user_id'] = $admin['id'];
                    $userEducationService->create($education);
                }
            }
            if ($data['type'] == 1 || $data['type'] == 2) {
                app()->get(CompanyUserChangeService::class)->create([
                    'card_id'      => $admin['id'],
                    'entid'        => 1,
                    'new_position' => $data['position'],
                    'new_frame'    => $frameInfo['main_id'] ?? 0,
                    'created_at'   => $data['work_time'] ? Carbon::make($data['work_time'])->toDateString() : now()->toDateString(),
                ]);
            }
            $frameAssist->setUserFrame($frameInfo['frame_id'], (int) $admin['id'], (int) ($frameInfo['main_id'] ?? 0), (bool) ($frameInfo['is_admin'] ?? false), (int) ($frameInfo['superior_uid'] ?? 0), $frameInfo['manage_frames'] ?: []);
            app()->get(FrameScopeService::class)->saveUserScope($admin['id'], 1, $frameInfo['frames'] ?? []);
            unset($data['name'], $data['phone'], $data['position'], $data['status'], $data['works'], $data['educations']);
            app()->get(AdminInfoService::class)->update($admin['id'], $data);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_FRAME])->flush();
    }

    /**
     * 修改员工档案信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateAdmin(int $id, string $type, array $data, array $frameInfo = []): int
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('未找到相关员工信息');
        }
        $save = $this->getUpdateData($type, $data);
        if (isset($save['phone']) && $this->dao->exists(['phone' => $save['phone'], 'not_id' => $id])) {
            throw $this->exception('手机号码已存在');
        }
        if (isset($save['phone']) && $info->status && $info->phone != $save['phone']) {
            throw $this->exception('手机号绑定的帐号已激活，无法修改手机号!');
        }
        $frameAssist = app()->get(FrameAssistService::class);
        if ($type == 'basic' && $frameInfo) {
            $frameInfo = $this->normalizeAdminFrameInfo($frameInfo, 'main_id');
        }
        return $this->transaction(function () use ($type, $save, $id, $info, $frameInfo, $frameAssist) {
            if ($type == 'basic') {
                $info->job  = $save['position'];
                $info->name = $save['name'];
                $frameInfo && $frameAssist->setUserFrame($frameInfo['frame_id'], $id, (int) $frameInfo['main_id'], (bool) $frameInfo['is_admin'], (int) $frameInfo['superior_uid'], (array) $frameInfo['manage_frames']);
                $frameInfo && app()->get(FrameScopeService::class)->saveUserScope($id, 1, $frameInfo['frame_id']);
                unset($save['name'], $save['phone'], $save['position']);
            }
            $info->save();
            // reload user role
            $frameInfo && $frameInfo['main_id'] && $frameAssist->addFrameRole($id, (int) $frameInfo['main_id']);
            return app()->get(AdminInfoService::class)->update($id, $save);
        });
    }

    /**
     * 获取修改员工部门信息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function editAdminFrame(int $id): array
    {
        $info = $this->dao->get($id, ['id', 'name', 'phone', 'avatar', 'job'], ['frames', 'manage_frames', 'super'])?->toArray();
        if (! $info) {
            throw $this->exception('修改的用户不存在');
        }
        $info['is_admin']        = 0;
        $info['superior_uid']    = 0;
        $info['superior_name']   = '';
        $info['superior_avatar'] = '';
        $info['manage_frame']    = [];
        if ($info['manage_frames']) {
            $info['is_admin']     = 1;
            $info['manage_frame'] = array_column($info['manage_frames'], 'id');
        }
        if ($info['super']) {
            $info['superior_uid']    = $info['super']['id'];
            $info['superior_name']   = $info['super']['name'];
            $info['superior_avatar'] = $info['super']['avatar'];
        }
        unset($info['super']);
        return $info;
    }

    /**
     * 修改保存员工部门信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveAdminFrame(int $id, array $data): bool
    {
        $data = $this->normalizeAdminFrameInfo($data, 'mastart_id');
        if ($this->dao->exists(['not_id' => $id, 'phone' => $data['phone']])) {
            throw $this->exception('该手机号已存在');
        }
        $admin = $this->dao->get($id);
        if (! $admin) {
            throw $this->exception('修改的用户不存在');
        }
        $res = $this->transaction(function () use ($id, $data, $admin) {
            // 组织架构关联用户
            $frameAssist = app()->get(FrameAssistService::class);
            $frameAssist->setUserFrame($data['frame_id'], $id, (int) $data['mastart_id'], (bool) $data['is_admin'], (int) $data['superior_uid'], $data['manage_frames']);
            $admin->name  = $data['name'];
            $admin->job   = $data['position'];
            $admin->phone = $data['phone'];
            $admin->save();
            app()->get(FrameScopeService::class)->saveUserScope($id, 1, $data['frame_id']);
            $data['mastart_id'] && $frameAssist->addFrameRole($id, (int) $data['mastart_id']);
            return true;
        });
        return $res && Cache::tags([CacheEnum::TAG_FRAME, CacheEnum::TAG_APPROVE, CacheEnum::TAG_ROLE, CacheEnum::TAG_ASSESS])->flush() && Task::deliver(new FrameCensusTask(1));
    }

    /**
     * 规范化并校验员工部门入参（唯一主部门 / 唯一负责部门）.
     *
     * @param array $frameInfo 部门相关入参
     * @param string $mainKey 主部门字段名（main_id|mastart_id）
     */
    protected function normalizeAdminFrameInfo(array $frameInfo, string $mainKey = 'main_id'): array
    {
        $frameIds = $frameInfo['frame_id'] ?? [];
        if (! is_array($frameIds)) {
            $frameIds = $frameIds ? [(int) $frameIds] : [];
        }
        $frameIds               = array_values(array_unique(array_filter(array_map('intval', $frameIds))));
        $frameInfo['frame_id']  = $frameIds;
        $frameInfo['is_admin']  = (int) ($frameInfo['is_admin'] ?? 0);

        $mainId = (int) ($frameInfo[$mainKey] ?? 0);
        if (! $frameIds) {
            throw $this->exception('必须选择一个部门');
        }
        if (! $mainId || ! in_array($mainId, $frameIds, true)) {
            throw $this->exception('必须选择一个主部门');
        }
        $frameInfo[$mainKey] = $mainId;

        $manageFrames = $frameInfo['manage_frames'] ?? [];
        if (! is_array($manageFrames)) {
            $manageFrames = $manageFrames !== '' && $manageFrames !== null ? [(int) $manageFrames] : [];
        }
        $manageFrames = array_values(array_unique(array_filter(array_map('intval', $manageFrames))));
        if ($frameInfo['is_admin']) {
            if (! $manageFrames) {
                throw $this->exception('必须选择一个负责部门');
            }
            if (count($manageFrames) > 1) {
                throw $this->exception('只能选择一个负责部门');
            }
            if (! in_array($manageFrames[0], $frameIds, true)) {
                throw $this->exception('负责部门必须是所属部门之一');
            }
        } else {
            $manageFrames = [];
        }
        $frameInfo['manage_frames'] = $manageFrames;

        return $frameInfo;
    }

    /**
     * 企业用户入职.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function adminEntry(int $id): bool
    {
        $assistServices = app()->get(FrameAssistService::class);
        $res            = $this->transaction(function () use ($id, $assistServices) {
            $adminInfo = app()->get(AdminInfoService::class)->get($id);
            if (! $adminInfo) {
                throw $this->exception('修改的用户名片不存在');
            }
            if ($adminInfo->type == 2) {
                throw $this->exception('该员工已入职');
            }
            $adminInfo->work_time = now()->toDateString();
            if (! $adminInfo->type || $adminInfo->type == 4) {
                $adminInfo->type = 2;
            }
            $res1 = $adminInfo->save();
            $res2 = $this->dao->update($id, ['status' => 1]);
            app()->get(UserChangeService::class)->create([
                'card_id'      => $id,
                'uid'          => $id,
                'types'        => 0,
                'date'         => now()->toDateString(),
                'new_position' => $this->dao->value($id, 'job') ?: '',
                'new_frame'    => $assistServices->dao->setTrashed()->value(['user_id' => $id, 'is_mastart' => 1], 'frame_id') ?: 0,
            ]);
            $assistServices->restore(['user_id' => $id]);
            return $res1 && $res2;
        });
        return $res && Task::deliver(new FrameCensusTask()) && Cache::tags([CacheEnum::TAG_FRAME])->flush();
    }

    /**
     * 获取转正表单.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws FormBuilderException
     * @throws NotFoundExceptionInterface
     */
    public function getFormalForm(int $id): array
    {
        if (! $this->dao->exists($id)) {
            throw $this->exception('修改的用户名片不存在');
        }
        return $this->createElementForm('办理转正', [
            Form::input('name', '人员姓名', $this->dao->value($id, 'name') ?: '')->readonly(true),
            Form::date('formal_time', '转正时间', app()->get(AdminInfoService::class)->value($id, 'formal_time') ?: now()->toDateString())
                ->validate([Form::validateStr()->required()->message('请选择转正时间')]),
            Form::textarea('mark', '转正备注', '')->placeholder('请输入转正备注信息')->maxlength(200)->showWordLimit(true),
        ], '/ent/company/card/be_formal/' . $id, 'put');
    }

    /**
     * 确认转正.
     * @param mixed $id
     * @param mixed $data
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function adminBeFormal($id, $data): bool
    {
        if (! $this->dao->exists($id)) {
            throw $this->exception('修改的用户名片不存在');
        }
        $info = app()->get(AdminInfoService::class)->get($id);
        if ($info->type === 1) {
            throw $this->exception('已转正，请勿重复操作');
        }
        $info->formal_time = $data['formal_time'];
        $info->type        = 1;
        $res               = $info->save();
        app()->get(UserChangeService::class)->create([
            'card_id'      => $id,
            'uid'          => $id,
            'types'        => 1,
            'date'         => $data['formal_time'],
            'old_position' => $this->dao->value($id, 'job') ?: 0,
            'old_frame'    => app()->get(FrameAssistService::class)->value(['user_id' => $id, 'is_mastart' => 1], 'frame_id') ?: 0,
            'mark'         => $data['mark'],
        ]);
        return $res;
    }

    /**
     * 离职.
     * @param mixed $id
     * @param mixed $data
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function adminQuit($id, $data): bool
    {
        $user = $this->dao->get($id)?->toArray();
        if (! $user) {
            throw $this->exception('未找到相关用户信息');
        }
        if ($user['is_admin']) {
            throw $this->exception('企业创始人不能离职！');
        }
        $infoService = app()->get(AdminInfoService::class);
        // 获取交接人信息
        if (! $this->dao->exists($data['user_id'])) {
            throw $this->exception('未找到交接人信息');
        }
        // 核对物资
        $recordUsers = app()->get(StorageRecordService::class)->getRecordUsers(1, '');
        if ($recordUsers && in_array($id, array_column($recordUsers, 'id'))) {
            throw $this->exception('当前员工物资未归还，不能离职！');
        }
        // 核对绩效
        if (app()->get(UserAssessService::class)->count(['entid' => 1, 'test_uid' => $id, 'not_status' => [0, 4]])) {
            throw $this->exception('当前员工绩效未结束，不能离职！');
        }
        $assistServices = app()->get(FrameAssistService::class);
        $res            = $this->transaction(function () use ($infoService, $id, $data, $user, $assistServices) {
            $res1 = $infoService->update($id, [
                'type'      => 4,
                'quit_time' => $data['quit_time'],
            ]);
            $res2 = $this->dao->update($id, ['status' => 2]);
            app()->get(UserChangeService::class)->create([
                'types'        => 3,
                'card_id'      => $id,
                'uid'          => $id,
                'date'         => $data['quit_time'],
                'old_position' => $user['job'],
                'old_frame'    => $assistServices->value(['user_id' => $id, 'is_mastart' => 1], 'frame_id') ?: 0,
                'info'         => $data['info'],
                'mark'         => $data['mark'],
                'user_id'      => $data['user_id'],
            ]);
            $assistServices->update(['user_id' => $id], ['is_admin' => 0]);
            // 保留 frame_assist 记录以便查询离职人员的部门归属历史
            // $assistServices->delete(['user_id' => $id]);
            // 处理云盘数据
            app()->get(CloudFileService::class)->leaveUserTransfer($user['id'], (int) $data['user_id']);
            return $res1 && $res2;
        });
        $services = app()->get(CustomerService::class);
        $ids      = $services->column(['uid' => $id], 'id') ?: [];
        if ($ids) {
            $services->shift($ids, $data['user_id'], 1, 1, force: true);
        }
        // 离职员工名下未完成审批按异常规则自动同意或转交
        try {
            app()->get(\App\Http\Service\Approve\ApproveApplyService::class)->handleUserQuitPendingApproves((int) $id);
        } catch (\Throwable $e) {
            Log::error('离职审批异常处理失败:' . $e->getMessage(), ['user_id' => $id, 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        return $res && Task::deliver(new FrameCensusTask());
    }

    /**
     * 获取修改表单内容.
     * @param mixed $type
     * @param mixed $data
     */
    public function getUpdateData($type, $data): array
    {
        $result = match ($type) {
            'basic' => [
                'name'     => $data['name'],
                'phone'    => $data['phone'],
                'position' => $data['position'],
                'letter'   => get_name_letter(trim($data['name'])),
            ],
            'staff' => [
                'is_part'            => $data['is_part'],
                'work_time'          => $data['work_time'],
                'trial_time'         => $data['trial_time'],
                'formal_time'        => $data['formal_time'],
                'treaty_time'        => $data['treaty_time'],
                'interview_date'     => $data['interview_date'],
                'interview_position' => $data['interview_position'],
                'quit_time'          => $data['quit_time'],
                'type'               => $data['type'],
            ],
            'user' => [
                'sex'        => $data['sex'],
                'birthday'   => $data['birthday'],
                'age'        => $data['age'],
                'nation'     => $data['nation'],
                'politic'    => $data['politic'],
                'work_years' => $data['work_years'],
                'native'     => $data['native'],
                'address'    => $data['address'],
                'marriage'   => $data['marriage'],
                'email'      => $data['email'],
                'card_id'    => $data['card_id'],
            ],
            'education' => [
                'education'     => $data['education'],
                'acad'          => $data['acad'],
                'graduate_date' => $data['graduate_date'],
                'graduate_name' => $data['graduate_name'],
            ],
            'bank' => [
                'bank_num'  => $data['bank_num'],
                'bank_name' => $data['bank_name'],
            ],
            'social' => [
                'social_num' => $data['social_num'],
                'fund_num'   => $data['fund_num'],
            ],
            'spare' => [
                'spare_name' => $data['spare_name'],
                'spare_tel'  => $data['spare_tel'],
            ],
            'card' => [
                'card_front'      => $data['card_front'],
                'card_both'       => $data['card_both'],
                'education_image' => $data['education_image'],
                'acad_image'      => $data['acad_image'],
            ],
            'all' => [
                'letter'             => $data['name'] ? get_name_letter(trim($data['name'])) : '',
                'work_time'          => $data['work_time'],
                'trial_time'         => $data['trial_time'],
                'formal_time'        => $data['formal_time'],
                'treaty_time'        => $data['treaty_time'],
                'interview_position' => $data['position'] ?? '',
                'card_id'            => $data['card_id'],
                'sex'                => $data['sex'],
                'birthday'           => $data['birthday'],
                'age'                => $data['age'],
                'nation'             => $data['nation'],
                'politic'            => $data['politic'],
                'work_years'         => $data['work_years'],
                'native'             => $data['native'],
                'address'            => $data['address'],
                'marriage'           => $data['marriage'],
                'email'              => $data['email'],
                'education'          => $data['education'],
                'acad'               => $data['acad'],
                'graduate_date'      => $data['graduate_date'],
                'graduate_name'      => $data['graduate_name'],
                'bank_num'           => ! empty($data['bank_num']) ? $data['bank_num'] : '',
                'bank_name'          => ! empty($data['bank_name']) ? $data['bank_name'] : '',
                'social_num'         => ! empty($data['social_num']) ? $data['social_num'] : '',
                'fund_num'           => ! empty($data['fund_num']) ? $data['fund_num'] : '',
                'spare_name'         => ! empty($data['spare_name']) ? $data['spare_name'] : '',
                'spare_tel'          => ! empty($data['spare_tel']) ? $data['spare_tel'] : '',
                'card_front'         => ! empty($data['card_front']) ? $data['card_front'] : '',
                'card_both'          => ! empty($data['card_both']) ? $data['card_both'] : '',
                'education_image'    => ! empty($data['education_image']) ? $data['education_image'] : '',
                'acad_image'         => ! empty($data['acad_image']) ? $data['acad_image'] : '',
            ],
            'perfect' => [
                'letter'             => $data['name'] ? get_name_letter(trim($data['name'])) : '',
                'interview_position' => $data['position'] ?? '',
                'card_id'            => $data['card_id'],
                'sex'                => $data['sex'],
                'birthday'           => $data['birthday'],
                'age'                => $data['age'],
                'nation'             => $data['nation'],
                'politic'            => $data['politic'],
                'work_years'         => $data['work_years'],
                'native'             => $data['native'],
                'address'            => $data['address'],
                'marriage'           => $data['marriage'],
                'email'              => $data['email'],
                'education'          => $data['education'],
                'acad'               => $data['acad'],
                'graduate_date'      => $data['graduate_date'],
                'graduate_name'      => $data['graduate_name'],
                'bank_num'           => ! empty($data['bank_num']) ? $data['bank_num'] : '',
                'bank_name'          => ! empty($data['bank_name']) ? $data['bank_name'] : '',
                'social_num'         => ! empty($data['social_num']) ? $data['social_num'] : '',
                'fund_num'           => ! empty($data['fund_num']) ? $data['fund_num'] : '',
                'spare_name'         => ! empty($data['spare_name']) ? $data['spare_name'] : '',
                'spare_tel'          => ! empty($data['spare_tel']) ? $data['spare_tel'] : '',
                'card_front'         => ! empty($data['card_front']) ? $data['card_front'] : '',
                'card_both'          => ! empty($data['card_both']) ? $data['card_both'] : '',
                'education_image'    => ! empty($data['education_image']) ? $data['education_image'] : '',
                'acad_image'         => ! empty($data['acad_image']) ? $data['acad_image'] : '',
            ],
            'photo' => [
                'photo' => $data['photo'],
            ],
            default => ['sort' => $data['sort'] ?? 0],
        };
        $result['photo'] = $data['photo'];

        return $result;
    }

    /**
     * 获取组织架构人员列表.
     * @param mixed $where
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getListOrderAdmin(array $where, array $sort, array $with): array
    {
        [$page, $limit] = $this->getPageValue();
        if ($where['frame_id']) {
            $where['frame_ids'] = app()->get(FrameService::class)->column(['path' => $where['frame_id'], 'not_id' => $where['frame_id']], 'id');
        }
        $list  = $this->dao->listSearch($where, $page, $limit, $sort, $with)->get()?->toArray();
        $count = $this->dao->listSearch($where)->count('admin.id');
        return $this->listData($list, $count);
    }

    /**
     * 验证账号状态.
     * @param mixed $phone
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkUserAuth($phone): void
    {
        $info = $this->dao->get(['phone' => $phone]);
        if ($info) {
            if ($info->status == UserEnum::USER_LOCKING) {
                throw $this->exception('该账号暂时无法登陆，请联系管理员激活');
            }
        } elseif (! sys_config('registration_open', 0)) {
            throw $this->exception('无效的账号信息!');
        }
    }

    /**
     * 从企业中删除用户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteAdmin(int $id): bool
    {
        $info = $this->dao->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('删除的用户名片不存在');
        }
        if ($info['status']) {
            throw $this->exception('已激活的用户不可删除');
        }
        if ($info['is_admin']) {
            throw $this->exception('不能删除企业的创始人');
        }
        $res = $this->transaction(function () use ($id) {
            app()->get(FrameAssistService::class)->delete(['user_id' => $id]);
            $this->dao->delete($id);
            app()->get(AdminInfoService::class)->delete($id);
            return true;
        });
        return $res && Task::deliver(new FrameCensusTask());
    }

    /**
     * 修改用户管理员.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateRole(array $userId, int $roleId): void
    {
        $this->dao->getModel()->whereIn('id', $userId)->get()->each(function ($item) use ($roleId) {
            $roles       = is_string($item->roles) ? json_decode($item->roles, true) : $item->roles;
            $roles[]     = $roleId;
            $item->roles = array_merge(array_unique($roles));
            $item->save();
        });
    }

    /**
     * TODO 邀请完善信息.
     * @return array|string[]
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function sendPerfect(int $uid, int $id): array
    {
        $info = $this->dao->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('未找到相关员工档案信息');
        }
        $uniqued    = md5('_perfect_' . $id . time());
        $cardPerfec = app()->get(UserCardPerfectService::class);
        $save       = [
            'entid'     => 1,
            'uniqued'   => $uniqued,
            'total'     => 1,
            'types'     => 1,
            'creator'   => $uid,
            'user_id'   => $id,
            'card_id'   => $id,
            'fail_time' => now()->addDays(8)->toDateTimeString(),
        ];
        $entInfo = app()->get(CompanyService::class)->get(1)?->toArray();
        event(new SystemMessageEvent(
            type: NoticeEnum::PERFECT_USER_INFO,
            params: [
                '企业名称' => $entInfo['enterprise_name'] ?? '',
            ],
            receiverIds: $id,
            other: ['entid' => 1, 'uid' => $id]
        ));
        $data = ['url' => '', 'message' => '邀请完善信息已发出，等待用户完善'];
        $cardPerfec->create($save);
        return $data;
    }

    /**
     * 发送/完善个人档案信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function agreePerfect(int $id, int $uid): bool|int
    {
        $info = app()->get(UserCardPerfectService::class)->get($id);
        if (! $info) {
            throw $this->exception('未找到相关邀请记录');
        }
        if ($info->user_id != $uid) {
            throw $this->exception('人员信息不符，禁止操作');
        }
        $resumeService = app()->get(UserResumeService::class);
        $uuid          = $this->dao->value($uid, 'uid');
        $resume        = $resumeService->get(['uid' => $uuid]);
        $count         = 0;
        foreach ($resume as $v) {
            if (! $v) {
                ++$count;
            }
        }
        if ($count > 5) {
            throw $this->exception('简历完善度过低，请先完善个人简历');
        }
        $info->status = 1;
        $this->updateAdmin($uid, 'perfect', $resume->setHidden(['id', 'uid', 'phone', 'position', 'created_at', 'updated_at'])->toArray());
        $info->save();
        Task::deliver(new SaveHistoryTask($uuid, $id));
        return true;
    }

    /**
     * 拒绝发送/完善个人信息.
     * @param mixed $id
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function refusePerfect($id)
    {
        $info = app()->get(UserCardPerfectService::class)->get($id);
        if (! $info) {
            throw $this->exception('未找到相关邀请记录');
        }
        $info->status = 2;
        return $info->save();
    }

    /**
     * 用户索引查询.
     * @param mixed $where
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getLangUser($where, array $with = [])
    {
        $letter = array_unique(app()->get(AdminInfoService::class)->setDefaultSort(DB::raw('convert(letter using gbk)'))->column(['type' => [1, 2, 3]], 'letter'));
        $result = $this->dao->joinSearch($where)->orderBy(DB::raw('convert(letter using gbk)'))->when(! empty($with), function ($query) use ($with) {
            $query->with($with);
        })->select(['*'])->get()->toArray();
        $list  = [];
        $count = 0;
        if (empty($letter) || empty($result)) {
            return compact('list', 'count');
        }
        $count = count($result);
        foreach ($letter as $value) {
            $data = [];
            foreach ($result as $val) {
                if ($val['letter'] == $value) {
                    $data[] = $val;
                }
            }
            $list[] = [
                'letter' => $value,
                'data'   => $data,
            ];
        }
        return compact('list', 'count');
    }

    /**
     * @return array|mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getValue(array $frameIds, mixed $value): mixed
    {
        $master = 0;
        foreach ($frameIds as $frameId) {
            if ($frameId['is_mastart'] == 1) {
                $master = $frameId['frame_id'];
            }
        }
        $frames = app()->get(FrameService::class)->select(['ids' => array_column($frameIds, 'frame_id')], ['id', 'name']);
        foreach ($frames as $frame) {
            if ($frame['id'] == $master) {
                $frame['is_mastart'] = 1;
            }
            $value['frames'][] = $frame;
        }
        return $value;
    }

    /**
     * 记录/失效token.
     * @param mixed $uid
     * @param mixed $token
     * @param mixed $client
     * @param mixed $mac
     * @param mixed $ip
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function rememberToken($uid, $token, $client, $mac, $ip): void
    {
        $tokenServices = app()->get(UserTokenService::class);
        $info          = $tokenServices->get(['uid' => $uid, 'client' => $client]);
        if ($info) {
            $oldTokens = array_unique(array_filter([$info->remember_token, $info->last_token]));
            foreach ($oldTokens as $oldToken) {
                try {
                    auth('admin')->setToken($oldToken)->invalidate(true);
                } catch (\Exception $e) {
                    Log::error('单点登录Token失效失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
                }
            }
            $info->last_token     = $info->remember_token;
            $info->remember_token = $token;
            $info->last_ip        = $ip;
            $info->mac            = $mac;
            $info->save();
        } else {
            $tokenServices->create([
                'uid'            => $uid,
                'client'         => $client,
                'last_ip'        => $ip,
                'mac'            => $mac,
                'last_token'     => $token,
                'remember_token' => $token,
            ]);
        }
    }
}
