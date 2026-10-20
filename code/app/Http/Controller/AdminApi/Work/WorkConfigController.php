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

namespace App\Http\Controller\AdminApi\Work;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Config\SystemConfigService;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 企业微信配置.
 */
#[Prefix('ent/work')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkConfigController extends AuthController
{
    public function __construct(SystemConfigService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取配置.
     */
    #[Get('config', '获取企业微信配置')]
    public function index()
    {
        return $this->success($this->service->getWorkConfig());
    }

    /**
     * 保存配置.
     */
    #[Post('config/save', '保存企业微信配置')]
    public function save()
    {
        $config = [];
        foreach (SystemConfigService::WORK_CONFIG as $value) {
            $data = $this->request->post($value, '');
            if ($data !== '') {
                $config[$value] = $data;
            }
        }

        if ($config) {
            $this->service->saveWorkConfig($config);
        }

        return $this->success('保存成功');
    }

    /**
     * 获取RSA密钥.
     */
    #[Get('config/rsa', '获取企业微信会话存档配置')]
    public function getRsaKeys()
    {
        $ssl = openssl_pkey_new([
            'digest_alg'       => 'sha256', // 可以用openssl_get_md_methods() 查看支持的加密方法
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        # # 私钥
        openssl_pkey_export($ssl, $rsaPrivateKey);

        # # 公钥
        $rsaPublicKey = openssl_pkey_get_details($ssl)['key'];

        return $this->success([
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key'  => $rsaPublicKey,
        ]);
    }
}
