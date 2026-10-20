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

use MoChat\WeWorkFinanceSDK\Contract\ProviderInterface;
use MoChat\WeWorkFinanceSDK\Exception\FinanceSDKException;
use MoChat\WeWorkFinanceSDK\Exception\InvalidArgumentException;

abstract class AbstractProvider implements ProviderInterface
{
    /**
     * 获取会话解密记录数据.
     * @param int $seq 起始位置
     * @param int $limit 限制条数
     * @param int $retry 重试次数
     * @return array ...
     * @throws InvalidArgumentException
     * @throws FinanceSDKException
     */
    public function getDecryptChatData(int $seq, int $limit, int $retry = 0): array
    {
        $config = $this->getConfig();
        if (!isset($config['private_keys'])) {
            throw new InvalidArgumentException('缺少配置:private_keys[{"version":"private_key"}]');
        }
        $privateKeys = $config['private_keys'];

        try {
            $chatData = json_decode($this->getChatData($seq, $limit), true)['chatdata'];
            $newChatData = [];
            $lastSeq = 0;
            foreach ($chatData as $i => $item) {
                $lastSeq = $item['seq'];
                if (!isset($privateKeys[$item['publickey_ver']])) {
                    continue;
                }

                $decryptRandKey = null;
                openssl_private_decrypt(
                    base64_decode($item['encrypt_random_key']),
                    $decryptRandKey,
                    $privateKeys[$item['publickey_ver']],
                    OPENSSL_PKCS1_PADDING
                );

                // TODO 无法解密，一般为秘钥不匹配
                // 临时补丁方案，需要改为支持多版本key
                if ($decryptRandKey === null) {
                    continue;
                }

                $newChatData[$i] = json_decode($this->decryptData($decryptRandKey, $item['encrypt_chat_msg']), true);
                $newChatData[$i]['seq'] = $item['seq'];
            }

            if (!empty($chatData) && empty($chatData) && $retry && $retry < 10) {
                return $this->getDecryptChatData($lastSeq, $limit, ++$retry);
            }

            return $newChatData;
        } catch (\Exception $e) {
            throw new FinanceSDKException($e->getMessage(), $e->getCode());
        }
    }
}
