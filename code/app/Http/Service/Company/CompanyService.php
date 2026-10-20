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

namespace App\Http\Service\Company;

use App\Constants\CacheEnum;
use App\Constants\System\ConfigEnum;
use App\Http\Contract\Company\CompanyInterface;
use App\Http\Dao\Company\CompanyDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Cloud\CloudFileService;
use App\Http\Service\Config\SystemConfigService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\System\RolesService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\services\synchro\Company;
use Tuoluojiang\Open\services\WeatherService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CompanyService extends BaseService implements CompanyInterface
{
    use ResourceServiceTrait;

    // 企业状态 0=禁用,1=正常,2=待缴费,3=已过期
    public const STATUS = [0, 1, 2, 3];

    public function __construct(CompanyDao $dao)
    {
        $this->dao = $dao;
    }

    public function companyList(array $where, array $field, $sort, array $with): array
    {
        return [];
    }

    public function createCompanyForm(): array
    {
        return [];
    }

    public function createCompanySave(array $data): array
    {
        return [];
    }

    public function updateCompanyForm(int $id): array
    {
        return [];
    }

    public function updateCompanySave($id, array $data): array
    {
        return [];
    }

    /**
     * 列表.
     *
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['id', 'business_license', 'enterprise_name', 'enterprise_name_en', 'lead', 'logo', 'phone', 'status', 'verify', 'address', 'disable_remark', 'created_at'], $sort = null, array $with = []): array
    {
        return $this->dao->select($where, $field)->toArray();
    }

    /**
     * 新建表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('企业入驻申请', $this->createFormRule(collect($other)), '/ent/enterprise');
    }

    /**
     * 保存.
     *
     * @return mixed|void
     */
    public function resourceSave(array $data)
    {
        if ($this->dao->count(['verifys' => [1], 'name' => $data['enterprise_name']])) {
            throw $this->exception('该企业名称已被占用');
        }
        if ($this->dao->count(['uid' => $this->uuId(false)]) >= 5) {
            throw $this->exception('账号可申请企业数量已达上限');
        }
        //        $res                        = app()->get(BaiduTranslateService::class)->query($data['enterprise_name'], 'en')->all();
        //        $data['enterprise_name_en'] = $res['trans_result'][0]['dst'] ?? '';
        $data['enterprise_name_en'] = '';
        $data['verify']             = 0;
        $data['status']             = 0;
        $data['uid']                = $this->uuId(false);

        return $this->transaction(function () use ($data) {
            $data['uniqued'] = app()->get(Company::class)->setConfig()->create($data)['uniqued'];

            return $this->dao->create($data)->toArray();
        });
    }

    /**
     * 修改表单.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankInfo = $this->dao->get($id);
        if (! $rankInfo) {
            throw $this->exception('修改的申请不存在');
        }

        return $this->createElementForm('修改企业入驻信息', $this->createFormRule(collect($rankInfo->toArray())), '/ent/enterprise/' . $id, 'put');
    }

    /**
     * 修改企业信息.
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateEnt($id, $data)
    {
        $res = $this->transaction(function () use ($id, $data) {
            $res = $this->dao->update($id, $data);
            if ($data['enterprise_name']) {
                app()->get(FrameService::class)->update(['pid' => 0], ['name' => $data['enterprise_name']]);
            }
            return $res;
        });
        $res && Cache::tags([CacheEnum::TAG_FRAME])->flush();
        return $res;
    }

    /**
     * 修改.
     *
     * @param int $id
     *
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $info = $this->dao->get($id);
        if (empty($info)) {
            throw $this->exception('未找到可修改的申请记录');
        }
        if ($info['verify'] == 1) {
            throw $this->exception('审核已通过，无法修改相关信息');
        }
        if ($this->dao->exists(['verifys' => [1], 'name' => $data['enterprise_name']])) {
            throw $this->exception('该企业名称已被占用');
        }
        //        $res                        = app()->get(BaiduTranslateService::class)->query($data['enterprise_name'], 'en')->all();
        //        $data['enterprise_name_en'] = $res['trans_result'][0]['dst'] ?? '';
        $data['enterprise_name_en'] = '';
        $data['verify']             = 0;
        $data['status']             = 0;
        $data['uid']                = $this->uuId(false);

        return $this->transaction(function () use ($id, $data, $info) {
            $data['remark']  = '';
            $data['verify']  = 0;
            $data['uniqued'] = $info['uniqued'];
            app()->get(Company::class)->setConfig()->create($data);

            return $this->dao->update($id, $data);
        });
    }

    /**
     * 删除.
     *
     * @param mixed $id
     * @return int|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $info = $this->dao->get($id);
        if (empty($info)) {
            throw $this->exception('未找到可删除的申请记录');
        }
        if ($info['verify'] == 1) {
            throw $this->exception('审核已通过，无法删除相关信息');
        }

        return $this->transaction(function () use ($info) {
            app()->get(Company::class)->setConfig()->delete(['uniqued' => $info['uniqued']]);

            return true;
        });
    }

    /**
     * 企业安装初始化.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public function install()
    {
        sys_config(ConfigEnum::SITE_URL['key']);
        return $this->transaction(function () {
            $envData  = get_env(['MANAGE_ACCOUNT', 'MANAGE_PASSWORD']);
            $phone    = $envData['MANAGE_ACCOUNT'] ?: '13888888888';
            $password = $envData['MANAGE_PASSWORD'] ?: password_hash('888888', PASSWORD_BCRYPT);
            $res      = app()->get(AdminService::class)->create([
                'avatar'   => link_file('/static/admin_face/face' . rand(1, 6) . '.png'),
                'phone'    => $phone,
                'name'     => '企业创始人',
                'password' => $password,
            ]);
            app()->get(SystemConfigService::class)->update(['key' => ConfigEnum::SITE_URL['key']], ['value' => request()->root()]);
            // 更新网站地址
            return $res;
        });
    }

    /**
     * 获取企业信息.
     *
     * @param string[] $field
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getEntInfo(int $entId, $field = ['*'])
    {
        $fieldKey = is_array($field) ? implode(',', $field) : (string) $field;
        return Cache::tags([CacheEnum::TAG_FRAME])->remember(
            md5('ent_info_' . $entId . '_' . $fieldKey),
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($entId, $field) {
                $entInfo = $this->dao->get($entId, $field);
                return $entInfo ? $entInfo->makeHidden(['delete'])->toArray() : [];
            }
        );
    }

    /**
     * 获取企业信息和用户相关信息.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getEntAndUserInfo(int $entId)
    {
        if (! $entId) {
            throw $this->exception('企业ID不能为空');
        }
        $entInfo = $this->dao->get(['id' => $entId, 'status' => 1], ['*'], [
            'user' => function ($query) {
                $query->select(['uid', 'name']);
            }, 'frames' => function ($query) {
                $query->select(['id', 'entid']);
            },
        ]);
        if (! $entInfo) {
            throw $this->exception('企业不存在');
        }
        $entInfo = $entInfo->makeHidden(['delete', 'uniqued'])->toArray();
        if (isset($entInfo['frames'])) {
            $entInfo['frames'] = count($entInfo['frames']);
        } else {
            $entInfo['frames'] = 0;
        }
        $entInfo['enterprises'] = app()->get(AdminService::class)->count(['status' => 1]);
        return $entInfo;
    }

    /**
     * 强制删除企业.
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function forceDelete(int $id)
    {
        $verify = $this->dao->value($id, 'verify');
        if ($verify == 1) {
            throw $this->exception('已审核通过不能被强制删除');
        }

        return $this->dao->forceDelete($id);
    }

    /**
     * 永久删除企业.
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function deleteEnt(int $ent)
    {
        $entInfo = $this->dao->get($ent);
        if (! $entInfo) {
            throw $this->exception('删除的企业不存在');
        }

        return $this->transaction(function () use ($entInfo, $ent) {
            Event::dispatch('enterprise.DeleteSuccess', [$ent, $entInfo->toArray()]);

            return $entInfo->forceDelete();
        });
    }

    /**
     * 企业后置事件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function afterVerify(int $adminId, int $entId = 1)
    {
        try {
            $entInfo = $this->dao->get($entId)?->toArray();
            if (! $entInfo) {
                throw $this->exception('企业不存在');
            }

            $this->transaction(function () use ($entInfo, $adminId, $entId) {
                $roleService    = app()->get(RolesService::class);
                [$rules, $apis] = $roleService->getSuperRoleAll(false);
                $roleService->addSysRole([
                    'role_name' => $entInfo['enterprise_name'],
                    'entid'     => $entId,
                    'status'    => 1,
                    'rules'     => $rules,
                    'apis'      => $apis,
                    'type'      => 'enterprise',
                    'uniqued'   => $entInfo['uniqued'],
                ]);
                // 创建企业初始架构
                $frameId = app()->get(FrameService::class)->entFrameInit($entId, $entInfo['enterprise_name']);
                // 创建文档文件夹
                app()->get(CloudFileService::class)->create(['entid' => $entId, 'name' => $entInfo['enterprise_name']]);
                // 用户加入部门
                app()->get(FrameAssistService::class)->setUserFrame($frameId, $adminId, $frameId, true, $adminId, [$frameId]);
                return true;
            });
        } catch (\Throwable $e) {
            Log::error('企业后置事件执行失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    /**
     * 企业审核状态
     *
     * @param mixed $where
     * @param mixed $field
     * @return null|BaseModel|BuildsQueries|mixed|Model|object
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function verifyStatus($where, $field)
    {
        return $this->dao->select($where, $field);
    }

    /**
     * 统计数量.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getQuantity(string $type, int $entId): array
    {
        $num = match ($type) {
            'inviter_review' => app()->get(CompanyApplyService::class)->count(['entid' => $entId, 'status' => 1, 'verify' => 0], []),
        };

        return ['num' => $num];
    }

    /**
     * 用户权限.
     * @throws BindingResolutionException
     */
    public function getUserAuth(int $entId, string $uuid): array
    {
        $data = ['manage_auth' => 0];
        if (app()->get(FrameService::class)->hasManageScopeAuth($entId, $uuid)) {
            $data['manage_auth'] = 1;
        }
        return $data;
    }

    /**
     * 获取企业地址信息.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCompanyAddress(int $entId = 1): string
    {
        if (! $entId) {
            throw $this->exception('企业ID不能为空');
        }
        $entInfo = $this->dao->get(['id' => $entId, 'status' => 1], ['province', 'city', 'area', 'address']);
        if (! $entInfo) {
            throw $this->exception('企业不存在');
        }

        return $entInfo?->province . $entInfo?->city . $entInfo?->area . $entInfo?->address;
    }

    /**
     * 获取企业信息.
     *
     * @param mixed $field
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCompanyInfo($field = 'logo', int $entId = 1): array|string
    {
        if (! $entId) {
            throw $this->exception('企业ID不能为空');
        }
        if (is_array($field)) {
            return toArray($this->dao->get(['id' => $entId, 'status' => 1], $field));
        }
        return $this->dao->value(['id' => $entId, 'status' => 1], $field);
    }

    /**
     * 服务配置.
     * @throws \ReflectionException
     */
    public function getServerConfig(): array
    {
        return Cache::remember('server_config', 60, function () {
            $ent = toArray($this->dao->get(['id' => 1], ['enterprise_name', 'short_name', 'logo']));
            return array_merge($ent, ['address' => sys_config('site_url', config('app.url')), 'version' => getVersion('version_num') ?? '1.6.0']);
        });
    }

    /**
     * 获取当前IP的天气信息.
     */
    public function getCurrentWeather(): array
    {
        $request = request();
        $ips     = array_merge(
            explode(',', (string) $request->header('X-Forwarded-For')),
            [(string) $request->header('X-Real-IP'), (string) $request->server('HTTP_X_REAL_IP'), (string) $request->ip()]
        );

        $ip = '127.0.0.1';
        foreach ($ips as $item) {
            $item = trim($item);
            if (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $item;
                break;
            }
        }

        return app(WeatherService::class)->getWeatherByIp($ip)->toArray();
    }

    /**
     * 获取表单数据.
     */
    protected function createFormRule(Collection $collection): array
    {
        return [
            Form::input('enterprise_name', '企业名称', $collection->get('enterprise_name', ''))->required()->maxlength(20)->showWordLimit(true),
            Form::input('lead', '法人代表', $collection->get('lead', ''))->required()->maxlength(20)->showWordLimit(true),
            Form::input('address', '公司地址', $collection->get('address', ''))->required()->maxlength(64)->showWordLimit(true),
            Form::input('phone', '手机号码', $collection->get('phone', app()->get(AdminService::class)->value(auth('admin')->id(), 'phone')))->required()->readonly(true),
            Form::uploadImage('business_license', '营业执照', '/api/ent/upload', $collection->get('business_license', ''))->headers([
                'Authorization' => request()->header('Authorization'),
            ])->uploadType('image')->data(['types' => 'image'])->required(),
        ];
    }
}
