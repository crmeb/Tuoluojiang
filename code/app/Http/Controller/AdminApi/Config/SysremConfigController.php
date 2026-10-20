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

namespace App\Http\Controller\AdminApi\Config;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\system\SystemConfigRequest;
use App\Http\Service\Config\CompanyConfigService;
use App\Http\Service\Config\SystemConfigService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\services\UploadService;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 系统配置控制器.
 */
#[Prefix('ent/config')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class SysremConfigController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(SystemConfigService $services)
    {
        parent::__construct();
        $this->service = $services;
        $this->setShowField('is_show');
    }

    /**
     * 获取工作台配置表单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('work_bench', '获取企业工作台配置')]
    public function getWorkBenchFrom(CompanyConfigService $services)
    {
        return $this->success($services->getConfigFrom($this->entId, '工作台设置', 'work_bench', '/ent/config/work_bench'));
    }

    /**
     * 保存工作台配置表单.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('work_bench', '保存企业工作台配置')]
    public function saveWorkBenchFrom(CompanyConfigService $services)
    {
        $config = $services->select(['category' => 'work_bench', 'entid' => $this->entId], ['key', 'key_name', 'value', 'type', 'input_type']);
        foreach ($config as $value) {
            $keys[] = [
                $value['key'], $value['value'],
            ];
        }
        $data = $this->request->postMore($keys);
        $services->saveConfig($this->entId, 'work_bench', $data);
        return $this->success('保存配置成功');
    }

    /**
     * 获取上传配置.
     */
    public function uploadConfig(UploadService $service): mixed
    {
        return $this->success($service->uploadConfig());
    }

    /**
     * 修改配置获取表单.
     * @return mixed
     * @throws FormBuilderException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('data/updateConfig', '修改配置获取表单')]
    public function updateConfig()
    {
        $category = $this->request->get('category', '');
        return $this->success($this->service->getConfigForm($category));
    }

    /**
     * 修改配置.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Put('data/all/{cate_id}', '修改系统配置')]
    public function updateAll($category)
    {
        $keys = $this->service->column(['category' => $category], 'key');
        $data = $this->request->postMore($keys);
        if (!$data) {
            return $this->fail('保存失败');
        }
        $this->service->updateAllConfig($data);
        return $this->success('保存成功');
    }

    /**
     * 设置Request类名.
     */
    protected function getRequestClassName(): string
    {
        return SystemConfigRequest::class;
    }

    /**
     * 设置搜索字段.
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['key_name', ''],
            ['key', ''],
            ['time', ''],
            ['cate_id', 0],
        ];
    }

    /**
     * 设置提交字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['key', ''],
            ['key_name', ''],
            ['type', ''],
            ['input_type', ''],
            ['path', []],
            ['cate_id', 0],
            ['parameter', ''],
            ['upload_type', 0],
            ['required', ''],
            ['width', 0],
            ['high', 0],
            ['value', ''],
            ['desc', ''],
            ['sort', 1],
            ['is_show', 0],
        ];
    }

    /**
     * 获取防火墙配置.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('data/firewall', '获取防火墙配置')]
    public function firewallConfig()
    {
        return $this->success($this->service->getFirewallConfig());
    }

    /**
     * 保存防火墙配置.
     * @return mixed
     */
    #[Put('data/firewall', '保存防火墙配置')]
    public function saveFirewallConfig()
    {
        $firewallSwitch = $this->request->post('firewall_switch', 0);
        $firewallContent = $this->request->post('firewall_content', []);

        $info = $this->service->get(['key' => 'firewall_switch']);
        if ($info) {
            $info->value = $firewallSwitch;
            $info->save();
        } else {
            $data = $this->service->setConfigData('firewall_switch', '防火墙开关', (int)$firewallSwitch, 'firewall');
            $this->service->create($data);
        }

        if ($firewallContent) {
            $isProbablyValidRegex = function ($pattern) {
                try {
                    preg_match($pattern, '');
                    return true;
                } catch (\Exception) {
                    return false;
                }
            };
            foreach ($firewallContent as $value) {
                if (!$isProbablyValidRegex($value)) {
                    return $this->fail('防火墙配置格式错误,必须为正则表达式');
                }
            }
        }
        $info = $this->service->get(['key' => 'firewall_content']);
        if ($info) {
            $info->value = $firewallContent;
            $info->save();
        } else {
            $data = $this->service->setConfigData('firewall_content', '防火墙设置', $firewallContent, 'firewall');
            $this->service->create($data);
        }

        return $this->success('保存成功');
    }
}
