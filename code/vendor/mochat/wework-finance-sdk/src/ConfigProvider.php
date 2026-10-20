<?php

declare(strict_types=1);
/**
 * This file is part of MoChat.
 * @link     https://mo.chat
 * @document https://mochat.wiki
 * @contact  group@mo.chat
 * @license  https://github.com/mochat-cloud/mochat/blob/master/LICENSE
 */
namespace MoChat\WeWorkFinanceSDK;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'dependencies' => [
            ],
            'commands' => [
            ],
            'publish' => [
                [
                    'id'          => 'wx_finance_sdk',
                    'description' => '企业微信会话内容存档',
                    'source'      => __DIR__ . '/../publish/wx_finance_sdk.php',
                    'destination' => BASE_PATH . '/config/autoload/wx_finance_sdk.php',
                ],
            ],
        ];
    }
}
