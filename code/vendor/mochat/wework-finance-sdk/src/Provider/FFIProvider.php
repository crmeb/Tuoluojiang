<?php

declare(strict_types=1);
/**
 * This file is part of MoChat.
 * @link     https://mo.chat
 * @document https://mochat.wiki
 * @contact  group@mo.chat
 * @license  https://github.com/mochat-cloud/mochat/blob/master/LICENSE
 */
namespace MoChat\WeWorkFinanceSDK\Provider;

use FFI;
use MoChat\WeWorkFinanceSDK\Contract\ProviderInterface;
use MoChat\WeWorkFinanceSDK\Exception\FinanceSDKException;
use MoChat\WeWorkFinanceSDK\Exception\InvalidArgumentException;

class FFIProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var FFI
     */
    private $ffi;

    /**
     * @var string 指针
     */
    private $financeSdk;

    /**
     * @var string C语言头
     */
    private $cHeader = 'WeWorkFinanceSdk_C.h';

    /**
     * @var string C语言库
     */
    private $cLib = 'libWeWorkFinanceSdk_C.so';

    public function __destruct()
    {
        // 释放sdk
        $this->financeSdk instanceof FFI && $this->ffi->DestroySdk($this->financeSdk);
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config): ProviderInterface
    {
        $this->config = array_merge($this->config, $config);
        $this->setFinanceSDK();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     * @throws FinanceSDKException ...
     */
    public function getChatData(int $seq, int $limit, int $timeout = 0): string
    {
        // 初始化buffer
        $chatDatas = $this->ffi->NewSlice();
        // 拉取内容
        $res = $this->ffi->GetChatData($this->financeSdk, $seq, $limit, $this->config['proxy'], $this->config['passwd'], $this->config['timeout'], $chatDatas);
        if ($res !== 0) {
            throw new FinanceSDKException(sprintf('GetChatData err res:%d', $res));
        }

        $resStr = FFI::string($chatDatas->buf);
        // 释放buffer
        $this->ffi->FreeSlice($chatDatas);
        $chatDatas->len = 0;
        return $resStr;
    }

    /**
     * {@inheritdoc}
     * @throws FinanceSDKException ...
     */
    public function decryptData(string $randomKey, string $encryptStr): string
    {
        // 初始化buffer
        $msg = $this->ffi->NewSlice();
        $res = $this->ffi->DecryptData($randomKey, $encryptStr, $msg);
        if ($res !== 0) {
            throw new FinanceSDKException(sprintf('RsaDecryptChatData err res:%d', $res));
        }
        $resStr = FFI::string($msg->buf);
        // 释放buffer
        $this->ffi->FreeSlice($msg);
        $msg->len = 0;
        return $resStr;
    }

    /**
     * {@inheritdoc}
     * @throws FinanceSDKException
     */
    public function getMediaData(string $sdkFileId, string $ext): \SplFileInfo
    {
        $path = sys_get_temp_dir() . '/' . md5($sdkFileId);
        $ext && $path .= '.' . $ext;
        try {
            $this->downloadMediaData($sdkFileId, $path);
        } catch (\WxworkFinanceSdkExcption $e) {
            throw new FinanceSDKException('获取文件失败' . $e->getMessage(), $e->getCode());
        }
        return new \SplFileInfo($path);
    }

    /**
     * 下载媒体资源.
     * @param string $sdkFileId file id
     * @param string $path 文件路径
     * @throws FinanceSDKException
     */
    protected function downloadMediaData(string $sdkFileId, string $path): void
    {
        $indexBuf = '';

        while (true) {
            // 初始化buffer MediaData_t*
            $media = $this->ffi->NewMediaData();

            // 拉取内容
            $res = $this->ffi->GetMediaData($this->financeSdk, $indexBuf, $sdkFileId, $this->config['proxy'], $this->config['passwd'], $this->config['timeout'], $media);
            if ($res !== 0) {
                $this->ffi->FreeMediaData($media);
                throw new FinanceSDKException(sprintf('GetMediaData err res:%d\n', $res));
            }

            // buffer写入文件
            $handle = fopen($path, 'ab+');
            if (! $handle) {
                throw new \RuntimeException(sprintf('打开文件失败:%s', $path));
            }

            fwrite($handle, FFI::string($media->data, $media->data_len), $media->data_len);
            fclose($handle);

            // 完成下载
            if ($media->is_finish === 1) {
                $this->ffi->FreeMediaData($media);
                break;
            }

            // 重置文件指针
            $indexBuf = FFI::string($media->outindexbuf);
            $this->ffi->FreeMediaData($media);
        }
    }

    /**
     * 获取php-ext-sdk.
     * @param array $config ...
     * @throws FinanceSDKException ...
     * @throws InvalidArgumentException ...
     */
    protected function setFinanceSDK(array $config = []): void
    {
        if (! extension_loaded('ffi')) {
            throw new FinanceSDKException('缺少ext-ffi扩展');
        }

        $this->config = array_merge($this->config, $config);
        if (! isset($this->config['corpid'])) {
            throw new InvalidArgumentException('缺少配置:corpid');
        }
        if (! isset($this->config['secret'])) {
            throw new InvalidArgumentException('缺少配置:secret');
        }

        isset($this->config['proxy']) || $this->config['proxy']     = '';
        isset($this->config['passwd']) || $this->config['passwd']   = '';
        isset($this->config['timeout']) || $this->config['timeout'] = '';

        // C包路径
        $includePath   = dirname(__DIR__, 2) . '/include/';
        $this->cHeader = $includePath . $this->cHeader;
        $this->cLib    = $includePath . $this->cLib;

        // 引入ffi
        $this->ffi = FFI::cdef(file_get_contents($this->cHeader), $this->cLib);

        // WeWorkFinanceSdk_t* sdk
        $this->financeSdk = $this->ffi->NewSdk();

        // 初始化
        $res = $this->ffi->Init($this->financeSdk, $this->config['corpid'], $this->config['secret']);
        if ($res !== 0) {
            throw new FinanceSDKException('ffi:Init() 初始化错误');
        }
    }
}
