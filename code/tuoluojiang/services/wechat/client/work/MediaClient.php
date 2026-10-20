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

namespace Tuoluojiang\Open\services\wechat\client\work;

use Tuoluojiang\Open\services\wechat\client\BaseClient;
use EasyWeChat\Kernel\HttpClient\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 附件管理
 * Class MediaClient.
 * @email 136327134@qq.com
 * @date 2023/9/15
 */
class MediaClient extends BaseClient
{
    /**
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function upload(string $type, string $path, array $form = []): Response
    {
        $files = [
            'media' => $path,
        ];

        return $this->httpUpload('cgi-bin/media/upload', $files, $form, compact('type'));
    }

    /**
     * 生成异步上传任务
     * @throws TransportExceptionInterface
     */
    public function uploadByUrl(string $mediaType, string $filename, string $url, string $md5, array $form = []): Response
    {
        $data = [
            'scene'    => 1,
            'type'     => $mediaType,
            'filename' => $filename,
            'url'      => $url,
            'md5'      => $md5,
        ];
        return $this->api->postJson('cgi-bin/media/upload_by_url', $data, $form);
    }

    /**
     * 查询异步任务结果.
     * @throws TransportExceptionInterface
     */
    public function uploadResult(string $jobId): Response
    {
        $data = [
            'jobid' => $jobId,
        ];
        return $this->api->postJson('cgi-bin/media/get_upload_by_url_result', $data);
    }

    /**
     * 上传附件资源.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function uploadAttachment(string $path, string $mediaType, string $attachmentType): Response
    {
        $query = [
            'media_type'      => $mediaType,
            'attachment_type' => $attachmentType,
        ];

        return $this->httpUpload('cgi-bin/media/upload_attachment', ['media' => $path], $query);
    }

    /**
     * 获取临时素材.
     * @email 136327134@qq.com
     * @date 2023/9/15
     * @throws TransportExceptionInterface
     */
    public function get(string $mediaId): Response
    {
        return $this->getResources($mediaId, 'cgi-bin/media/get');
    }
}
