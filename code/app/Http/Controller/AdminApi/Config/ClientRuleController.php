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

namespace App\Http\Controller\AdminApi\Config;

use App\Constants\System\CategoryEnum;
use App\Constants\System\ConfigEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Config\SystemConfigService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 客户规则控制器.
 */
#[Prefix('ent/config/client_rule')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ClientRuleController extends AuthController
{
    private array $baseKeys = [
        CategoryEnum::CUSTOMER_FOLLOW_CONFIG['key'],
        CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
        CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
        CategoryEnum::CLUE_POOL_CONFIG['key'],
        CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
        CategoryEnum::CUSTOMER_OUTBOUND_CONFIG['key'],
        //        CategoryEnum::ODDS_FOLLOW_CONFIG['key'],
    ];

    public function __construct(SystemConfigService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取配置分类列表.
     */
    #[Get('cate', '获取客户规则分类列表')]
    public function cateList(): mixed
    {
        $cates = [];
        foreach (CategoryEnum::values() as $key => $value) {
            if (in_array(strtolower($key), $this->baseKeys)) {
                $cates[] = $value->getValue();
            }
        }
        return $this->success($cates);
    }

    /**
     * 获取配置数据.
     * @param mixed $category
     * @throws BindingResolutionException
     */
    #[Get('{cate_id}', '获取客户规则配置')]
    public function getConfig($category): mixed
    {
        if (! $category) {
            return $this->fail('common.empty.attrs');
        }
        $keys = [];
        foreach (ConfigEnum::values() as $value) {
            if ($value->getValue()['category'] == $category) {
                $keys[] = $value->getValue()['key'];
            }
        }
        return $this->success(sys_more($keys));
    }

    /**
     * 获取客户审批配置数据.
     * @param int $form
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('approve/{form?}', '获取客户审批规则')]
    public function getApproveConfig($form = 1): mixed
    {
        $keys = [
            ConfigEnum::CONTRACT_REFUND_SWITCH['key'],
            ConfigEnum::CONTRACT_RENEW_SWITCH['key'],
            ConfigEnum::CONTRACT_DISBURSE_SWITCH['key'],
            ConfigEnum::INVOICING_SWITCH['key'],
            ConfigEnum::VOID_INVOICE_SWITCH['key'],
            ConfigEnum::CONTRACT_SIGN_SWITCH['key'],
        ];
        if ($form) {
            return $this->success($this->service->getApproveConfig($keys));
        }
        return $this->success($this->service->getApproveConfigs($keys));
    }

    /**
     * 保存客户审批配置数据.
     * @throws BindingResolutionException
     */
    #[Put('approve', '保存客户审批规则')]
    public function setApproveConfig(): mixed
    {
        $data = $this->request->postMore([
            ConfigEnum::CONTRACT_REFUND_SWITCH['key'],
            ConfigEnum::CONTRACT_RENEW_SWITCH['key'],
            ConfigEnum::CONTRACT_DISBURSE_SWITCH['key'],
            ConfigEnum::INVOICING_SWITCH['key'],
            ConfigEnum::VOID_INVOICE_SWITCH['key'],
            ConfigEnum::CONTRACT_SIGN_SWITCH['key'],
        ]);
        $this->service->updateAllConfig($data);
        return $this->success('保存成功');
    }

    /**
     * 设置规则数据.
     * @param mixed $category
     * @throws BindingResolutionException
     */
    #[Put('{cate_id}', '保存客户规则配置')]
    public function setConfig($category): mixed
    {
        if (! $category) {
            return $this->fail('common.empty.attrs');
        }
        $keys = [];
        foreach (ConfigEnum::values() as $value) {
            if ($value->getValue()['category'] == $category) {
                $keys[] = $value->getValue()['key'];
            }
        }
        $this->service->updateAllConfig($this->request->postMore($keys));
        return $this->success('保存成功');
    }
}
