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

namespace App\Providers;

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Model\Warehouse\WarehouseCheck;
use App\Http\Model\Warehouse\WarehouseConvert;
use App\Http\Model\Warehouse\WarehouseInbound;
use App\Http\Model\Warehouse\WarehouseOutbound;
use App\Http\Model\Warehouse\WarehouseTransfer;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Ai\AiStreamService;
use App\Http\Service\Config\CompanyConfigService;
use App\Http\Service\Config\SystemConfigService;
use App\Http\Service\System\SystemGroupService;
use App\Http\Service\Work\WorkConfigService;
use App\Listeners\wechat\WorkListener;
use Tuoluojiang\Open\services\ApiResponseService;
use Tuoluojiang\Open\services\ConfigService;
use Tuoluojiang\Open\services\EntUserService;
use Tuoluojiang\Open\services\GroupDataService;
use Tuoluojiang\Open\services\wechat\config\HttpCommonConfig;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // 绑定response
        $this->app->singleton('json', ApiResponseService::class);

        $stores = $this->app->config->get('cache.default', 'file');
        $timeout = $this->app->config->get('cache.stores.' . $stores . '.timeout', 3600);
        // 绑定组合数据
        $this->app->singleton('group_config', function ($app) use ($timeout) {
            return new GroupDataService(SystemGroupService::class, $app->cache, $timeout);
        });
        // 绑定系统config
        $this->app->singleton('config_crmeb', function ($app) use ($timeout) {
            return new ConfigService(SystemConfigService::class, $timeout);
        });
        // 绑定系统config
        $this->app->singleton('ent_config_crmeb', function ($app) use ($timeout) {
            return new ConfigService(CompanyConfigService::class, $app->cache, $timeout);
        });
        // 绑定企业用户
        $this->app->singleton('enterprise_user', function ($app) use ($timeout) {
            return new EntUserService(AdminService::class, $app->cache, $timeout);
        });

        $this->app->singleton('admin_user', function ($app) use ($timeout) {
            return new EntUserService(AdminService::class, $app->cache, $timeout);
        });

        $this->app->bind(HttpCommonConfig::class, function () {
            return (new HttpCommonConfig())->setServe(WorkConfigService::class);
        });
        // 企业微信
        $this->app->singleton(Work::class, function () {
            return (new Work())->setPushMessageHandler(WorkListener::class)
                ->setConfigHandler(WorkConfigService::class);
        });

        $this->app->singleton(WorkConfig::class, function () {
            return (new WorkConfig($this->app->make(HttpCommonConfig::class)))->setHandler(WorkConfigService::class);
        });

        // AI 流式服务持有请求态过滤器，须为单例以便 RequestStateCleaner 统一重置
        $this->app->singleton(AiStreamService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Relation::morphMap([
            WarehouseEnum::SOURCE_INBOUND  => WarehouseInbound::class,
            WarehouseEnum::SOURCE_OUTBOUND => WarehouseOutbound::class,
            WarehouseEnum::SOURCE_CHECK    => WarehouseCheck::class,
            WarehouseEnum::SOURCE_TRANSFER => WarehouseTransfer::class,
            WarehouseEnum::SOURCE_CONVERT  => WarehouseConvert::class,
        ]);
    }
}
