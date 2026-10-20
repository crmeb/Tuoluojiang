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

namespace Tuoluojiang\Open\services\wechat\config;

use Tuoluojiang\Open\services\wechat\contract\ConfigHandlerInterface;
use Tuoluojiang\Open\services\wechat\contract\WorkAppConfigHandlerInterface;
use Tuoluojiang\Open\services\wechat\DefaultConfig;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

/**
 * 企业微信配置
 * Class WorkConfig.
 */
class WorkConfig implements ConfigHandlerInterface
{
    // 应用
    public const TYPE_APP = 'app';

    // 客户联系
    public const TYPE_USER = 'user';

    // 通讯录同步
    public const TYPE_ADDRESS = 'address';

    // 客服
    public const TYPE_KEFU = 'kefu';

    // 审批
    public const TYPE_APPROVE = 'approve';

    // 会议室
    public const TYPE_MEETING = 'meeting';

    // 自建应用
    public const TYPE_USER_APP = 'build';

    // 会话存档
    public const TYPE_SESSION = 'session';

    public string $corpId;

    public string $token;

    public string $aesKey;

    public array $appConfig;

    protected string $responseType = 'array';

    protected HttpCommonConfig $httpConfig;

    protected bool $init = false;

    protected string $handler;

    /**
     * WorkConfig constructor.
     */
    public function __construct()
    {
        $this->httpConfig = app()->get(HttpCommonConfig::class);
        $this->init();
    }

    /**
     * 获取全部值
     */
    #[ArrayShape([
        'corp_id'       => 'string',
        'token'         => 'string',
        'aes_key'       => 'string',
        'response_type' => 'string',
        'log'           => 'array',
        'http'          => 'bool[]',
    ])]
    #[Pure]
    public function all(): array
    {
        $this->init();
        return [
            'corp_id'       => $this->corpId,
            'token'         => $this->token,
            'aes_key'       => $this->aesKey,
            'response_type' => $this->responseType,
            'http'          => $this->httpConfig->all(),
        ];
    }

    /**
     * 获取应用配置.
     */
    public function getAppConfig(string $type): array
    {
        if (! isset($this->appConfig[$type])) {
            /** @var WorkAppConfigHandlerInterface $make */
            $make = app()->get($this->handler);
            if (! $this->getCorpId()) {
                $this->init();
            }
            $this->appConfig[$type] = $make->getAppConfig($this->getCorpId(), $type);
        }
        return $this->appConfig[$type];
    }

    /**
     * 设置.
     * @return $this
     */
    public function setHandler(string $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * 获取corpId.
     * @return mixed
     */
    public function getCorpId()
    {
        return $this->corpId = $this->httpConfig->getConfig(DefaultConfig::WORK_CORP_ID, '');
    }

    /**
     * @email 136327134@qq.com
     * @date 2023/9/18
     */
    protected function init()
    {
        $this->corpId = $this->httpConfig->getConfig(DefaultConfig::WORK_CORP_ID, '');
        $this->token  = $this->httpConfig->getConfig('work.token', '');
        $this->aesKey = $this->httpConfig->getConfig('work.key', '');
    }
}
