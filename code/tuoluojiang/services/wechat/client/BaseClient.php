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

namespace Tuoluojiang\Open\services\wechat\client;

use EasyWeChat\Kernel\Form\File;
use EasyWeChat\Kernel\Form\Form;
use EasyWeChat\Kernel\HttpClient\AccessTokenAwareClient;
use EasyWeChat\Kernel\HttpClient\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 基础接口请求
 * Class BaseClient.
 * @email 136327134@qq.com
 * @date 2023/9/18
 */
abstract class BaseClient
{
    /**
     * UserClient constructor.
     */
    public function __construct(protected AccessTokenAwareClient $api) {}

    /**
     * 上传文件.
     * @return Response
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function httpUpload(string $url, array $files = [], array $form = [], array $query = [])
    {
        $form['media'] = File::fromPath($files['media']);
        $options       = Form::create($form)->toArray();
        return $this->api->request(
            'POST',
            $url . '?' . http_build_query($query),
            $options
        );
    }

    /**
     * @return Response
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    protected function getResources(string $mediaId, string $uri)
    {
        return $this->api->request('GET', $uri, [
            'query' => [
                'media_id' => $mediaId,
            ],
        ]);
    }
}
