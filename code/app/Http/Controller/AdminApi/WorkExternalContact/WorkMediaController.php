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

namespace App\Http\Controller\AdminApi\WorkExternalContact;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\WorkExternalContact\WorkMediaService;
use Tuoluojiang\Open\exceptions\UploadException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('ent/work/media')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkMediaController extends AuthController
{
    public function __construct(WorkMediaService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 分片上传临时素材.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('upload', '分片上传临时素材')]
    public function upload()
    {
        [$file, $md5, $chunkIndex, $chunkTotal] = $this->request->postMore([
            ['file', 'file'],
            ['md5', ''],        // 文件md5
            ['chunk_index', 0], // 分片索引
            ['chunk_total', 0], // 总分片数
        ], true);
        $res = $this->service->mediaUpload($file, auth('admin')->id(), ['chunk_index' => (int) $chunkIndex, 'chunk_total' => (int) $chunkTotal, 'md5' => $md5]);
        return $this->success($res === true ? 'ok' : '上传成功', $res);
    }

    /**
     * 保存前端上传素材.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('save', '保存前端上传素材')]
    public function save()
    {
        [$file] = $this->request->postMore([
            ['file', []], // 文件内容(含地址url、名称name、大小size、类型type)
        ], true);
        $res = $this->service->mediaSave($file, auth('admin')->id());
        return $this->success($res);
    }

    /**
     * 通过URL上传素材.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('upload-by-url', '通过URL上传素材')]
    public function uploadByUrl()
    {
        [$url] = $this->request->postMore([
            ['url', ''], // 远程文件URL
        ], true);
        if (empty($url)) {
            return $this->fail('URL不能为空');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->fail('URL格式不正确');
        }

        try {
            $res = $this->service->mediaUploadByUrl($url, auth('admin')->id());
            return $this->success($res);
        } catch (UploadException $e) {
            return $this->fail($e->getMessage());
        }
    }
}
