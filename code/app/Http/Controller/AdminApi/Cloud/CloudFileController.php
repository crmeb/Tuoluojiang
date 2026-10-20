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

namespace App\Http\Controller\AdminApi\Cloud;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Cloud\CloudFileService;
use App\Http\Service\Cloud\CloudShareService;
use Illuminate\Contracts\Container\BindingResolutionException;
use OSS\Core\OssException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 云盘文件.
 */
#[Prefix('ent/cloud/file/{fid}')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
final class CloudFileController extends AuthController
{
    public function __construct(CloudFileService $service, protected int $spaceId = 0, protected int $fileId = 0)
    {
        parent::__construct();
        $this->service = $service;
        $this->spaceId = $spaceId ?: (int) $this->request->route('fid');
        $this->fileId  = $fileId ?: (int) $this->request->route('id');
    }

    /**
     * 文件列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('list', '文件列表')]
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['sort_type', ''],
            ['sort_by', ''],
            ['file_type', ''],
            ['keyword', ''],
            ['pid', ''],
            ['id', ''],
        ]);
        return $this->success($this->service->fileList($this->spaceId, auth('admin')->id(), $where));
    }

    /**
     * 创建文件.
     * @throws BindingResolutionException
     * @throws OssException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('create', '创建文件')]
    public function create(): mixed
    {
        [$type, $name, $id] = $this->request->postMore([
            ['type', ''],
            ['name', ''],
            ['pid', 0, 'id'],
        ], true);
        if (! $name) {
            return $this->fail('请输入文件名称');
        }
        $this->service->createEmptyFile($type, $name, auth('admin')->id(), (int) ($id ?: $this->spaceId));
        return $this->success('创建成功');
    }

    /**
     * 创建文件夹.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('folder', '创建文件夹')]
    public function folder(): mixed
    {
        [$name, $id] = $this->request->postMore([
            ['name', ''],
            ['pid', 0, 'id'],
        ], true);
        if (! $name) {
            return $this->fail('请输入文件名称');
        }
        $this->service->createFolder($name, auth('admin')->id(), (int) ($id ?: $this->spaceId));
        return $this->success('创建成功');
    }

    /**
     * 更新文件.
     * @return mixed
     * @throws BindingResolutionException
     * @throws OssException
     * @throws Exception
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('update/{id}', '更新文件')]
    public function update()
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        [$file, $isDoc] = $this->request->postMore([
            ['content', 'content'],
            ['is_file', 0],
        ], true);
        $this->service->updateFile($this->spaceId, auth('admin')->id(), $this->fileId, $file, (bool) $isDoc);
        return $this->success('保存成功');
    }

    /**
     * 删除文件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Delete('delete/{id}', '删除文件')]
    public function delete(): mixed
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $this->service->destroyFile($this->fileId, auth('admin')->id(), $this->spaceId);
        return $this->success('删除成功');
    }

    /**
     * 批量删除文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    #[Delete('batch_delete', '批量删除文件')]
    public function batch_delete(): mixed
    {
        $ids = $this->request->post('id', []);
        if (! $ids) {
            return $this->fail('缺少必要参数');
        }
        $this->service->batchDestroyFile((array) $ids, auth('admin')->id(), $this->spaceId);
        return $this->success('删除成功');
    }

    /**
     * 文件详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '文件详情')]
    public function info(): mixed
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $info = $this->service->fileInfo($this->fileId, auth('admin')->id(), $this->spaceId);
        return $this->success($info);
    }

    /**
     * 上传文件.
     * @throws BindingResolutionException
     * @throws OssException
     * @throws Exception
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('upload', '上传文件')]
    public function upload(): mixed
    {
        [$file, $pid, $md5, $chunkIndex, $chunkTotal] = $this->request->postMore([
            ['file', 'file'],
            ['pid', 0],
            ['md5', ''],        // 文件md5
            ['chunk_index', 0], // 分片索引
            ['chunk_total', 0], // 总分片数
        ], true);

        $res = $this->service->uploadFile((int) ($pid ?: $this->spaceId), auth('admin')->id(), $file, ['chunk_index' => (int) $chunkIndex, 'chunk_total' => (int) $chunkTotal, 'md5' => $md5]);
        return $this->success($res === true ? 'ok' : '上传成功', $res === true ? [] : $res, tips: 0);
    }

    /**
     * 保存云存储文件
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('save', '保存云存储文件')]
    public function save(): mixed
    {
        $file = $this->request->postMore([
            ['name', ''],
            ['size', 0],
            ['type', ''],
            ['url', 0],
        ]);
        $res = $this->service->saveCloudFile($this->spaceId, auth('admin')->id(), $file);
        return $this->success($res === true ? 'ok' : '上传成功', $res === true ? [] : $res, tips: 0);
    }

    /**
     * 移动文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    #[Post('move/{id}', '移动文件')]
    public function move(): mixed
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $toId = $this->request->post('to_id', '');
        $this->service->moveFile($this->fileId, auth('admin')->id(), $this->spaceId, (int) $toId);
        return $this->success('移动成功');
    }

    /**
     * 批量移动文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    #[Post('batch_move', '批量移动文件')]
    public function batch_move(): mixed
    {
        $ids = $this->request->post('id', []);
        if (! $ids) {
            return $this->fail('缺少必要参数');
        }
        $toId = $this->request->post('to_id', '');
        $this->service->batchMoveFile((array) $ids, auth('admin')->id(), $this->spaceId, (int) $toId);
        return $this->success('移动成功');
    }

    /**
     * 复制文件.
     * @return mixed
     * @throws BindingResolutionException
     * @throws OssException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('copy/{id}', '复制文件')]
    public function copy()
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $toId = $this->request->post('to_id', '');
        $this->service->copyFile($this->fileId, auth('admin')->id(), $this->spaceId, (int) $toId);
        return $this->success('复制成功');
    }

    /**
     * 重命名文件.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('rename/{id}', '重命名文件')]
    public function rename()
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $name = $this->request->post('name', '');
        $this->service->renameFile($this->fileId, auth('admin')->id(), $this->spaceId, $name);
        return $this->success('操作成功');
    }

    /**
     * 获取文件权限.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('rules/{id}', '获取文件权限')]
    public function rules(CloudShareService $service)
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $list = $service->getRules($this->fileId, auth('admin')->id(), $this->spaceId);
        return $this->success($list);
    }

    /**
     * 设置文件权限.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('rules/{id}', '设置文件权限')]
    public function setRules(CloudShareService $service)
    {
        if (! $this->fileId) {
            return $this->fail('缺少必要参数');
        }
        $rule = $this->request->post('rule', []);
        $service->setRules($this->fileId, auth('admin')->id(), $this->spaceId, $rule);
        return $this->success('操作成功');
    }

    /**
     * 模板下载.
     * @throws BindingResolutionException
     * @throws OssException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('temp_download', '模板下载')]
    public function tempDownload(): mixed
    {
        [$pid, $tempId] = $this->request->postMore([
            ['id', 0],
            ['temp_id', 0],
        ], true);
        if (! $pid) {
            return $this->fail('请选择目录');
        }
        if (! $tempId) {
            return $this->fail('请选择模板');
        }
        $this->service->templateDownload($this->spaceId, auth('admin')->id(), (int) $tempId, (int) $pid);
        return $this->success('操作成功');
    }

    /**
     * 分片上传.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('chunk_upload', '分片上传')]
    public function chunkUpload(): mixed
    {
        [$pid, $md5, $file, $chunkIndex, $chunkTotal] = $this->request->postMore([
            ['pid', 0],
            ['md5', ''],        // 文件md5
            ['file', 'file'],
            ['chunk_index', 0], // 分片索引
            ['chunk_total', 0], // 总分片数
        ], true);
        $uid = auth('admin')->id();
        $res = $this->service->chunkUpload((int) ($pid ?: $this->spaceId), $uid, (int) $chunkIndex, (int) $chunkTotal, $md5, $file);
        return $this->success($res ? 'success' : 'waiting');
    }
}
