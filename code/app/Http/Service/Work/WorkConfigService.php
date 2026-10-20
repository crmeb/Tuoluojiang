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

namespace App\Http\Service\Work;

use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\contract\ServeConfigInterface;
use Tuoluojiang\Open\services\wechat\contract\WorkAppConfigHandlerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业微信配置.
 */
class WorkConfigService extends BaseService implements WorkAppConfigHandlerInterface, ServeConfigInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAppConfig(string $corpId, string $type): array
    {
        $config = [];
        switch ($type) {
            case WorkConfig::TYPE_USER:
                $config = [
                    'secret' => sys_config('wechat_work_user_secret'),
                ];
                break;
            case WorkConfig::TYPE_ADDRESS:
                $config = [
                    'secret' => sys_config('wechat_work_address_secret'),
                ];
                break;
            case WorkConfig::TYPE_USER_APP:
                $config = [
                    'agent_id' => sys_config('wechat_work_build_agent_id'),
                    'secret'   => sys_config('wechat_work_build_secret'),
                ];
                break;
            case WorkConfig::TYPE_SESSION:
                $config = [
                    'secret'             => sys_config('wechat_work_session_secret'),
                    'public_key_version' => sys_config('wechat_work_session_public_key_version'),
                    'public_key'         => sys_config('wechat_work_session_public_key'),
                    'private_key'        => sys_config('wechat_work_session_private_key'),
                ];
                break;
        }
        return $config;
    }

    /**
     * @param null|mixed $default
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getConfig(string $key, $default = null)
    {
        return sys_config($key, $default);
    }
}
