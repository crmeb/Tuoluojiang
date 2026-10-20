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

use MoChat\WeWorkFinanceSDK\Contract\ProviderInterface;
use MoChat\WeWorkFinanceSDK\Exception\InvalidArgumentException;

/**
 * Class WxFinanceSDK.
 * @method array getConfig()  获取微信配置
 * @method string getChatData(int $seq, int $limit)  获取会话记录数据(加密)
 * @method string decryptData(string $randomKey, string $encryptStr)  解密数据
 * @method \SplFileInfo getMediaData(string $sdkFileId, string $ext)  获取媒体资源
 * @method array getDecryptChatData(int $seq, int $limit)  获取会话记录数据(解密)
 */
class WxFinanceSDK
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $wxConfig;

    public function __construct(array $config = [], array $wxConfig = [])
    {
        $default = [
            'default'   => 'php-ext',
            'providers' => [
                'php-ext' => [
                    'driver' => \MoChat\WeWorkFinanceSDK\Provider\PHPExtProvider::class,
                ],
                'php-ffi' => [
                    'driver' => \MoChat\WeWorkFinanceSDK\Provider\FFIProvider::class,
                ],
            ],
        ];

        // hyperf框架
        if (interface_exists(\Hyperf\Contract\ConfigInterface::class)) {
            $default = config('wx_finance_sdk', $default);
        }

        $this->config = empty($config) ? $default : array_merge($default, $config);
        $this->wxConfig = $wxConfig;
    }

    public function __call($name, $arguments)
    {
        $provider = $this->provider($this->config['default']);

        if (method_exists($provider, $name)) {
            return call_user_func_array([$provider, $name], $arguments);
        }

        throw new InvalidArgumentException('WxFinanceSDK::Method not defined. method:' . $name);
    }

    public static function init(array $wxConfig = [], array $driverConfig = []): self
    {
        return new self($driverConfig, $wxConfig);
    }

    /**
     * @param $providerName ...
     * @throws InvalidArgumentException ...
     * @return ProviderInterface ...
     */
    public function provider($providerName): ProviderInterface
    {
        if (! $this->config['providers'] || ! $this->config['providers'][$providerName]) {
            throw new InvalidArgumentException("file configurations are missing {$providerName} options");
        }
        return (new $this->config['providers'][$providerName]['driver']())->setConfig($this->wxConfig);
    }
}
