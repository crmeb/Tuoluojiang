<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Cloud;

use App\Constants\CacheEnum;
use App\Constants\CloudEnum;
use App\Http\Dao\Cloud\CloudFileDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Other\UploadService;
use App\Task\message\CloudFileCreateRemind;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\ServicesException;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\HttpService;
use Tuoluojiang\Open\services\phpoffice\PptService;
use Tuoluojiang\Open\services\phpoffice\SheetService;
use Tuoluojiang\Open\services\phpoffice\WordService;
use Tuoluojiang\Open\services\wps\WebOffice;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\WhitespacePathNormalizer;
use OSS\Core\OssException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webpatser\Uuid\Uuid;

/**
 * 云盘文件服务.
 * @mixin CloudFileDao
 */
class CloudFileService extends BaseService
{
    public function __construct(CloudFileDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 检查用户是否有权限访问特定文件.
     * @return bool|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkAuth(int $spaceId, int $uid, int $fileId = 0, string $auth = '', array $data = [], bool $isDel = false)
    {
        if ($isDel) {
            $dao = $this->dao->setTrashed();
        } else {
            $dao = $this->dao;
        }
        if (! $spaceId) {
            throw $this->exception('企业空间不存在');
        }
        $spaceUid = $dao->value($spaceId, 'user_id');
        if (! $spaceUid) {
            throw $this->exception('企业空间不存在');
        }
        if ($spaceUid == $uid) {
            return true;
        }
        if ($dao->value($fileId, 'user_id') == $uid) {
            return true;
        }
        if ($auth && ! app(CloudAuthService::class)->checkFilePermission($data['id'] ?? $spaceId, $uid, $auth)) {
            throw $this->exception('没有权限操作');
        }
    }

    /**
     * 文件列表.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function fileList(int $spaceId, int $uid, array $where)
    {
        if ($where['pid']) {
            $this->checkPath((int) $where['pid'], $spaceId, 1);
        } else {
            $where['pid'] = $spaceId;
            $this->checkAuth($spaceId, $uid, auth: CloudEnum::READ_AUTH, data: ['id' => $where['pid']]);
        }
        $sort = [
            $where['sort_type'] => $where['sort_by'],
        ];
        unset($where['sort_type'], $where['sort_by'], $where['is_del']);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->setDefaultSort($sort)->select($where, ['id', 'user_id', 'type', 'name', 'path', 'file_name', 'file_ext', 'file_sn', 'file_size', 'file_type', 'upload_type', 'updated_at'], ['share', 'user'], $page, $limit)?->toArray();
        foreach ($list as &$item) {
            $path             = array_map('intval', explode('/', trim($item['path'], '/')));
            $item['is_admin'] = intval($this->dao->value($path[0] ?? 0, 'user_id') == $uid);
            $item['path']     = implode('/', $this->dao->column(['id' => $path], 'name'));
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 回收站文件列表.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteList(int $uid, array $where)
    {
        $where['path'] = app()->get(CloudAuthService::class)->column(['user_id' => $uid, 'read' => 1], 'folder_id');
        $sort          = [
            $where['sort_type'] => $where['sort_by'],
        ];
        unset($where['sort_type'], $where['sort_by'], $where['is_del']);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->setOnlyTrashed()->setDefaultSort($sort)->setTimeField('deleted_at')->select(
            $where,
            ['id', 'user_id', 'type', 'name', 'path', 'file_name', 'file_ext', 'file_size', 'file_type', 'del_uid', 'deleted_at'],
            ['share', 'user', 'del_user'],
            $page,
            $limit
        )?->toArray();
        foreach ($list as &$item) {
            $path               = array_map('intval', explode('/', trim($item['path'], '/')));
            $item['path']       = implode('/', $this->dao->setTrashed()->column(['id' => $path], 'name'));
            $item['master_uid'] = $this->dao->value($path[0], 'user_id');
        }
        $count = $this->dao->setOnlyTrashed()->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 创建空文件.
     * @param mixed $type
     * @param mixed $name
     * @param mixed $uid
     * @param mixed $spaceId
     * @return bool
     * @throws BindingResolutionException
     * @throws OssException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function createEmptyFile($type, $name, $uid, $spaceId)
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $spaceId]);
        $type = match ($type) {
            'word'  => 'docx',
            'ppt'   => 'ppt',
            'excel' => 'xlsx',
            default => throw $this->exception('不支持的文件类型'),
        };

        $time = now()->toArray();
        $path = $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        $dir  = '/uploads/cloud/' . $path;
        Storage::makeDirectory('uploads/cloud/' . $path);
        /** @var WhitespacePathNormalizer $pathNormalizer */
        $pathNormalizer = app()->get(WhitespacePathNormalizer::class);
        $name           = $pathNormalizer->normalizePath($name);
        $fileName       = Str::random(40) . '.' . $type;
        $pathFile       = public_path($dir . '/' . $fileName);
        if (in_array($type, WebOffice::WPS_OFFICE_SHEET_TYPE)) {
            SheetService::instance()->setPath($dir)->setFileName($fileName)->save();
        } elseif (in_array($type, WebOffice::WPS_OFFICE_PPT_TYPE)) {
            PptService::instance()->setPath($dir)->setFileName($fileName)->save();
        } elseif (in_array($type, WebOffice::WPS_OFFICE_WORD_TYPE)) {
            WordService::instance()->setPath($dir)->setFileName($fileName)->save();
        }
        $uploadType = (int) sys_config('upload_type', 1);
        if (1 > $uploadType) {
            $file = fopen($pathFile, 'w+');
            try {
                $upload = UploadService::init($uploadType);
                $upload->to('cloud/' . $path . '/')->validate()->stream($file, $fileName);
                $fileInfo = $upload->getUploadInfo();
            } finally {
                fclose($file);
            }
            unset($file);
        } else {
            $fileInfo = [
                'type' => File::mimeType($pathFile),
                'size' => File::size($pathFile),
                'dir'  => $dir . '/' . $fileName,
                'name' => $fileName,
            ];
        }
        $fileInfo['file_ext']    = $type;
        $fileInfo['upload_type'] = $uploadType;
        $fileInfo['real_name']   = $fileInfo['name'];
        $fileInfo['name']        = $name;
        $res                     = $this->saveFileInfo([
            'pid'         => $spaceId,
            'file_type'   => $fileInfo['type'],
            'file_ext'    => $fileInfo['file_ext'],
            'file_size'   => $fileInfo['size'],
            'file_url'    => $fileInfo['dir'],
            'is_temp'     => $fileInfo['is_temp'] ?? 0,
            'file_name'   => $fileInfo['real_name'],
            'name'        => $fileInfo['name'],
            'upload_type' => $fileInfo['upload_type'],
            'user_id'     => $uid,
            'uid'         => app()->get(AdminService::class)->value($uid, 'uid'),
            'entid'       => 1,
        ]);
        // 创建文件成功消息提醒
        return $res && Task::deliver(new CloudFileCreateRemind(1, uid_to_uuid($uid), $res->toArray(), $spaceId));
    }

    /**
     * 删除文件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function destroyFile(int $fileId, int $uid, int $spaceId): void
    {
        if (! $this->dao->exists($fileId)) {
            app()->get(CloudViewHistoryService::class)->delete(['folder_id' => $fileId]);
            throw $this->exception('资源不存在');
        }
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::DELETE_AUTH, data: ['id' => $fileId]);
        $file = $this->dao->get($fileId)?->toArray();
        $this->checkPath($fileId, $spaceId, $file['type']);
        $this->transaction(function () use ($fileId, $uid) {
            $this->dao->update(['all_id' => $fileId], ['del_uid' => $uid]);
            $this->dao->delete(['all_id' => $fileId]);
            app()->get(CloudViewHistoryService::class)->delete(['folder_id' => $fileId]);
            return true;
        });
    }

    /**
     * 批量删除文件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchDestroyFile(array $fileId, int $uid, int $spaceId)
    {
        foreach ($fileId as $id) {
            $this->destroyFile((int) $id, $uid, $spaceId);
        }
    }

    /**
     * 获取文件信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function fileInfo(int $fileId, int $uid, int $spaceId): array
    {
        if (! $this->dao->exists($fileId)) {
            app()->get(CloudViewHistoryService::class)->delete(['folder_id' => $fileId]);
            throw $this->exception('资源不存在');
        }
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::READ_AUTH, data: ['id' => $fileId]);
        $info = $this->dao->get(
            $fileId,
            ['id', 'user_id', 'type', 'name', 'path', 'file_name', 'file_ext', 'file_url', 'file_sn', 'file_size', 'file_type', 'upload_type', 'created_at', 'updated_at'],
            ['user']
        )?->toArray();
        if (! $info) {
            throw $this->exception('文件不存在');
        }
        if (! $info['type']) {
            app()->get(CloudViewHistoryService::class)->updateOrCreate([
                'folder_id' => $fileId,
                'user_id'   => $uid,
            ], [
                'folder_id'  => $fileId,
                'user_id'    => $uid,
                'file_name'  => $info['file_name'],
                'file_url'   => $info['file_url'],
                'updated_at' => now()->toDateTimeString(),
            ]);
        }
        if (! sys_config('wps_type')) {
            if (! sys_config('wps_appid') || ! sys_config('wps_appkey')) {
                throw $this->exception('未完善wps配置无法预览，请完善配置！');
            }
            $extension = pathinfo($info['file_name'], PATHINFO_EXTENSION);
            if (! $extension) {
                $extension = pathinfo($info['file_url'], PATHINFO_EXTENSION);
            }
            $WebOfficeService = app()->get(WebOffice::class);
            $officeType       = $WebOfficeService->getOffiesType(strtolower($extension));
            if (! $officeType) {
                throw $this->exception('该文件类型不支持预览');
            }
            $info['file_url'] = $WebOfficeService->viewOffic($info['file_sn'], $officeType, ['tokentype' => 1, 'folder' => 1]);
        }
        $info['auth'] = $info['user_id'] == $uid ? ['user_id' => $uid, 'read' => 1, 'update' => 1, 'download' => 1, 'delete' => 1, 'folder_id' => $fileId] : app()->get(CloudAuthService::class)->getFolderAuth($uid, $fileId);
        $path         = array_map('intval', explode('/', trim($info['path'], '/')));
        $info['path'] = implode('/', $this->dao->setTrashed()->column(['id' => $path], 'name'));
        return $info;
    }

    /**
     * 上传文件.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws OssException
     * @throws Exception
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \ReflectionException
     */
    public function uploadFile(int $spaceId, int $uid, mixed $file, array $option = [])
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $spaceId]);
        $key = 'upload.key.' . app('request')->ip();
        if (Cache::tags([CacheEnum::TAG_OTHER])->has($key)) {
            Cache::tags([CacheEnum::TAG_OTHER])->increment($key, 1);
        } else {
            Cache::tags([CacheEnum::TAG_OTHER])->add($key, 1, now()->endOfDay()->timestamp - now()->timestamp);
        }

        // $fileInfo = $this->formatConversion();
        $time   = now()->toArray();
        $dir    = 'cloud/' . $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        $upload = UploadService::init();
        $res    = $upload->to($dir)->validate()->move($file, false, $option);
        if (is_bool($res)) {
            $res === false && throw new UploadException($upload->getError());
            return true;
        }
        $fileInfo                = $upload->getUploadInfo();
        $fileInfo['file_ext']    = pathinfo($fileInfo['dir'], PATHINFO_EXTENSION);
        $fileInfo['name']        = pathinfo($fileInfo['real_name'], PATHINFO_FILENAME);
        $fileInfo['upload_type'] = (int) sys_config('upload_type', 1);

        $res = $this->saveFileInfo([
            'pid'         => $spaceId,
            'file_type'   => $fileInfo['type'],
            'file_ext'    => $fileInfo['file_ext'],
            'file_size'   => $fileInfo['size'],
            'file_url'    => $fileInfo['dir'],
            'is_temp'     => $fileInfo['is_temp'] ?? 0,
            'file_name'   => $fileInfo['real_name'],
            'name'        => $fileInfo['name'],
            'upload_type' => $fileInfo['upload_type'],
            'user_id'     => $uid,
            'uid'         => app()->get(AdminService::class)->value($uid, 'uid'),
            'entid'       => 1,
        ]);

        if (! $res) {
            throw $this->exception('上传失败');
        }
        return ['src' => $fileInfo['dir'], 'url' => $fileInfo['dir'], 'id' => $res->id, 'size' => $fileInfo['size'], 'name' => $fileInfo['real_name']];
    }

    /**
     * 保存云存储上传的文件.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveCloudFile(int $spaceId, int $uid, array $fileInfo)
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $spaceId]);
        $key = 'upload.key.' . app('request')->ip();
        if (Cache::tags([CacheEnum::TAG_OTHER])->has($key)) {
            Cache::tags([CacheEnum::TAG_OTHER])->increment($key);
        } else {
            Cache::tags([CacheEnum::TAG_OTHER])->add($key, 1, now()->endOfDay()->timestamp - now()->timestamp);
        }
        $pathInfo = pathinfo($fileInfo['url']);
        if (empty($pathInfo['extension'])) {
            throw $this->exception('无效的文件路径');
        }
        $res = $this->saveFileInfo([
            'pid'         => $spaceId,
            'file_type'   => $fileInfo['type'],
            'file_ext'    => $pathInfo['extension'],
            'file_size'   => $fileInfo['size'],
            'file_url'    => $fileInfo['url'],
            'is_temp'     => 0,
            'file_name'   => $fileInfo['name'],
            'name'        => pathinfo($fileInfo['name'])['filename'],
            'upload_type' => sys_config('upload_type'),
            'user_id'     => $uid,
            'uid'         => app()->get(AdminService::class)->value($uid, 'uid'),
            'entid'       => 1,
        ]);
        if (! $res) {
            throw $this->exception('上传失败');
        }
        return ['src' => $fileInfo['url'], 'url' => $fileInfo['url'], 'id' => $res->id, 'size' => $fileInfo['size'], 'name' => $fileInfo['name']];
    }

    /**
     * 更新文件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws OssException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \ReflectionException
     */
    public function updateFile(int $spaceId, int $uid, int $fileId, mixed $file, bool $isDoc = false): void
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::UPDATE_AUTH, data: ['id' => $fileId]);
        $folder = $this->dao->get($fileId, ['file_name', 'file_url', 'pid', 'upload_type'])?->toArray();
        $key    = 'upload.key.' . app('request')->ip();
        if (Cache::tags([CacheEnum::TAG_OTHER])->get($key) > 500) {
            throw new ServicesException('您今日上传文件的次数已达上限');
        }
        if (Cache::tags([CacheEnum::TAG_OTHER])->has($key)) {
            Cache::tags([CacheEnum::TAG_OTHER])->increment($key, 1);
        } else {
            Cache::tags([CacheEnum::TAG_OTHER])->add($key, 1, now()->endOfDay()->timestamp - now()->timestamp);
        }
        $time     = now()->toArray();
        $dir      = 'cloud/' . $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        $upload   = UploadService::init();
        $ext      = pathinfo($folder['file_name'], PATHINFO_EXTENSION);
        $fileSize = 0;
        if ($isDoc) {
            $name     = pathinfo($folder['file_name'], PATHINFO_FILENAME) . '.' . $ext;
            $pathFile = public_path($name);

            if ($ext == 'doc' || $ext == 'docx') {
                // 创建一个新的Word文档
                $phpWord = new PhpWord();
                // 添加一个新的空白页
                $section = $phpWord->addSection();
                $file    = str_replace('<br>', '<br/>', $file);
                if (str_contains($file, 'table')) {
                    $file = str_replace('auto', '100%', $file);
                }
                // 将HTML内容添加到文档中
                Html::addHtml($section, $file);
                $writer = IOFactory::createWriter($phpWord);
                $writer->save($pathFile);
            } else {
                file_put_contents($pathFile, $file);
            }

            $fileSize = filesize($pathFile);
            $res      = $upload->to($dir)->validate()->stream(file_get_contents($pathFile), md5(micro_time()) . '.' . $ext);
            if ($res === false) {
                unlink($pathFile);
                throw new UploadException($upload->getError());
            }
            unlink($pathFile);
        } else {
            $res = $upload->to($dir)->validate()->move($file);
            if ($res === false) {
                throw new UploadException($upload->getError());
            }
        }
        $fileInfo                = $upload->getUploadInfo();
        $fileInfo['file_ext']    = pathinfo($fileInfo['dir'], PATHINFO_EXTENSION);
        $fileInfo['name']        = pathinfo($fileInfo['real_name'], PATHINFO_FILENAME);
        $fileInfo['upload_type'] = (int) sys_config('upload_type', 1);
        $this->transaction(function () use ($fileInfo, $fileId, $folder, $upload, $fileSize) {
            $upload = UploadService::init($folder['upload_type']);
            $fileInfo['dir'] != $folder['file_url'] && $upload->delete($folder['file_url']);
            $this->dao->update($fileId, [
                'file_ext'    => $fileInfo['file_ext'],
                'file_size'   => $fileSize ?: $fileInfo['size'],
                'file_url'    => $fileInfo['dir'],
                'upload_type' => $fileInfo['upload_type'],
            ]);
            return true;
        });
    }

    /**
     * 最近浏览.
     * @param mixed $uid
     * @param mixed $where
     * @return array
     * @throws BindingResolutionException
     */
    public function latelyFileList($uid, $where)
    {
        $where['user_id'] = $uid;
        [$page, $limit]   = $this->getPageValue();
        $search           = $this->dao->getViewHistorySearch($where);
        $count            = $search->count();
        $list             = $search->with(['user'])->forPage($page, $limit)->get($this->dao->getAliasA() . '.*');
        foreach ($list as &$item) {
            $path         = array_map('intval', explode('/', trim($item['path'], '/')));
            $item['pid']  = $path[0];
            $item['path'] = implode('/', $this->dao->column(['id' => $path], 'name'));
        }
        return $this->listData($list, $count);
    }

    /**
     * 移动文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function moveFile(int $fileId, int $uid, int $spaceId, int $toId)
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $toId]);
        $this->checkPath($fileId, $spaceId);
        $toPath = $this->dao->getPath($toId) . $toId . '/';
        if ($fileId === $toId || $this->dao->hasDeleted($toPath)) {
            throw $this->exception('无效路径');
        }
        $ids = explode('/', trim($toPath, '/'));
        if (in_array($fileId, $ids)) {
            throw $this->exception('无效路径');
        }
        $folder = $this->dao->get($fileId);
        $this->transaction(function () use ($toId, $toPath, $fileId, $folder) {
            if ($folder->type) {
                $path    = $folder->path . $fileId . '/';
                $_toPath = $toPath . $fileId . '/';
                $this->dao->update([['path', 'LIKE', "{$path}%"]], ['path' => DB::raw("REPLACE(CONCAT('A', `path`),'A{$path}','{$_toPath}')")]);
            }
            $folder->pid  = $toId;
            $folder->path = $toPath;
            $res          = $folder->saveOrFail();
            $res && $this->saveUpdateTime($fileId);
        });
    }

    /**
     * 复制文件.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws OssException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function copyFile(int $fileId, int $uid, int $spaceId, int $toId)
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $toId]);
        $this->checkPath($fileId, $spaceId, 0);
        $toPath = $this->dao->getPath($toId) . $toId . '/';
        $file   = $this->dao->get($fileId)?->toArray();
        if (! $file || ! $file['file_url']) {
            throw $this->exception('资源不存在');
        }
        if ($file['type']) {
            throw $this->exception('仅支持复制文件');
        }
        $uploadType = (int) sys_config('upload_type', 1);
        if ($file['upload_type'] <= 1) {
            $f = file_get_contents(str_contains($file['file_url'], 'http') ? $file['file_url'] : public_path($file['file_url']));
        } else {
            $f = file_get_contents($file['file_url']);
        }
        $time     = now()->toArray();
        $dir      = $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        $fileName = Str::random(40) . '.' . $file['file_ext'];
        $upload   = UploadService::init($uploadType);
        if (! $upload->to('cloud/' . $dir . '/')->validate()->stream($f, $fileName)) {
            throw new UploadException($upload->getError());
        }
        $fileInfo = $upload->getUploadInfo();
        return $this->saveFileInfo([
            'pid'         => $toId,
            'path'        => $toPath,
            'file_type'   => $fileInfo['type'],
            'file_ext'    => $file['file_ext'],
            'file_size'   => $fileInfo['size'],
            'file_url'    => $fileInfo['dir'],
            'is_temp'     => $file['is_temp'] ?? 0,
            'file_name'   => $fileInfo['real_name'],
            'name'        => $file['name'] . '[副本]',
            'upload_type' => $uploadType,
            'user_id'     => $uid,
            'uid'         => app()->get(AdminService::class)->value($uid, 'uid'),
            'entid'       => 1,
        ]);
    }

    /**
     * 重命名文件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function renameFile(int $fileId, int $uid, int $spaceId, string $name): void
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $fileId]);
        $this->checkPath($spaceId, $spaceId, null);
        /** @var WhitespacePathNormalizer $pathNormalizer */
        $pathNormalizer = app()->get(WhitespacePathNormalizer::class);
        $name           = $pathNormalizer->normalizePath($name);
        $this->dao->update($fileId, ['name' => $name]);
    }

    /**
     * 批量移动文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function batchMoveFile(array $fileId, int $uid, int $spaceId, int $toId)
    {
        foreach ($fileId as $id) {
            $this->moveFile((int) $id, $uid, $spaceId, $toId);
        }
    }

    /**
     * 彻底删除文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteFile(int $id, int $uid)
    {
        $info = $this->dao->setTrashed()->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('无效路径');
        }
        $path = explode('/', trim($info['path'], '/'));
        $this->checkAuth((int) $path[0], $uid, isDel: true);
        $this->transaction(function () use ($id, $info) {
            if ($info['file_url']) {
                UploadService::init($info['upload_type'])->delete($info['file_url']);
            }
            $this->dao->setOnlyTrashed()->get($id)?->forceDelete();
        });
    }

    /**
     * 批量彻底删除文件.
     */
    public function batchDeleteFile(array $ids, int $uid): int
    {
        $error = $success = 0;
        foreach ($ids as $id) {
            try {
                $this->deleteFile((int) $id, $uid);
                ++$success;
            } catch (\Exception) {
                ++$error;
            }
        }
        $error === count($ids) && throw $this->exception('删除失败，无权限操作');
        return $success;
    }

    /**
     * 恢复删除文件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function recoveryFile(int $id, int $uid)
    {
        $path = $this->dao->setTrashed()->value($id, 'path');
        $path = explode('/', trim($path, '/'));
        if (! $path) {
            throw $this->exception('无效路径');
        }
        $this->checkAuth((int) $path[0], $uid, isDel: true);
        $path[] = $id;
        foreach (array_unique($path) as $item) {
            $this->dao->setOnlyTrashed()->get($item)?->restore();
        }
    }

    /**
     * 批量恢复删除文件.
     */
    public function batchRecoveryFile(array $ids, int $uid)
    {
        foreach ($ids as $id) {
            try {
                $this->recoveryFile((int) $id, $uid);
            } catch (\Exception) {
            }
        }
    }

    /**
     * 创建文件夹.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function createFolder(string $name, int $uid, int $spaceId)
    {
        /** @var WhitespacePathNormalizer $pathNormalizer */
        $pathNormalizer = app()->get(WhitespacePathNormalizer::class);
        return $this->saveFileInfo([
            'name'    => $pathNormalizer->normalizePath($name),
            'pid'     => $spaceId,
            'user_id' => $uid,
            'uid'     => app()->get(AdminService::class)->value($uid, 'uid'),
        ], 1);
    }

    /**
     * 模板下载.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws OssException
     * @throws \ReflectionException
     */
    public function templateDownload(int $spaceId, int $uid, int $tempId, int $pid): mixed
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $pid]);
        $url                           = $this->getTempUrl($tempId);
        [$httpCode, $content, $header] = app()->get(HttpService::class)->requests($url);
        if (! $httpCode) {
            throw $this->exception(__('request was aborted'));
        }

        $realName = $this->getFileName($header);
        $extname  = pathinfo($realName, PATHINFO_EXTENSION);

        $time       = now()->toArray();
        $uploadType = (int) sys_config('upload_type', 1);
        $name       = substr(md5($realName), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $extname;
        $upload     = UploadService::init($uploadType ?? 1);
        if (! $upload->to('cloud/' . $time['year'] . '/' . $time['month'] . '/' . $time['day'])->validate()->stream($content, $name)) {
            throw new UploadException($upload->getError());
        }
        $headers    = get_headers($url, true);
        $uploadInfo = $upload->getUploadInfo();
        return $this->saveFileInfo([
            'pid'         => $pid,
            'file_type'   => getMimetype($realName),
            'file_ext'    => $extname,
            'file_size'   => $uploadInfo['size'] ?: $headers['Content-Length'],
            'file_url'    => $uploadInfo['dir'],
            'is_temp'     => 0,
            'file_name'   => $realName,
            'name'        => basename($realName, '.' . $extname),
            'upload_type' => $uploadType,
            'user_id'     => $uid,
            'uid'         => app()->get(AdminService::class)->value($uid, 'uid'),
            'entid'       => 1,
        ]);
    }

    /**
     * 保存文件信息.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function saveFileInfo(array $data, int $type = 0)
    {
        $data['path'] = $data['pid'] ? $this->dao->value($data['pid'], 'path') . $data['pid'] . '/' : '/';
        if ($this->dao->hasDeleted($data['path'])) {
            throw $this->exception('无效路径');
        }
        if (! $data['name']) {
            throw $this->exception('请输入名称');
        }
        $data['name'] = str_replace(['/', '\\'], '&', $data['name']);
        if ($data['pid'] && $this->dao->hasDeleted($data['path'])) {
            throw $this->exception('无效路径');
        }
        $data['type']    = $type;
        $data['file_sn'] = str_replace('-', '', (string) Uuid::generate(4));
        $res             = $this->dao->create($data);
        $this->saveUpdateTime($res->id);
        return $res;
    }

    /**
     * 处理离职人员权限.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function leaveUserTransfer(int $uid, int $toUid): void
    {
        // 转移企业空间
        if ($this->dao->exists(['user_id' => $uid])) {
            $this->dao->update(['user_id' => $uid], ['user_id' => $toUid, 'uid' => uid_to_uuid($toUid)]);
        }
        $authService  = app()->get(CloudAuthService::class);
        $shareService = app()->get(CloudShareService::class);
        // 将离职人员从企业空间/目录权限中移除
        if ($authService->exists(['user_id' => $uid])) {
            $authService->delete(['user_id' => $uid]);
        }
        // 将离职人员从共享权限中移除
        if ($shareService->exists(['user_id' => $uid])) {
            $shareService->delete(['user_id' => $uid]);
        }
    }

    /**
     * 获取文件含被删除的.
     * @param mixed $where
     * @param mixed $field
     * @return BaseModel
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getWithTrashed($where, $field = ['*'])
    {
        return $this->dao->getWithTrashed($where, $field);
    }

    /**
     * 彻底删除.
     * @return null|bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function forceDelete(int $fileId)
    {
        return $this->dao->setTrashed()->get($fileId)?->forceDelete();
    }

    /**
     * 上传文件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function chunkUpload(int $spaceId, int $uid, int $chunkIndex, int $chunkTotal, string $md5, mixed $file): bool
    {
        $this->checkAuth($spaceId, $uid, auth: CloudEnum::CREATE_AUTH, data: ['id' => $spaceId]);

        $path = DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cloud' . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');

        $fullPath = public_path($path);
        is_dir($fullPath) || mkdir($fullPath, 0777, true) || throw $this->exception('创建文件失败');

        $fileHandle = app('request')->file($file);
        if (! $fileHandle) {
            throw $this->exception('上传的文件不存在');
        }

        $filename = $fileHandle->getClientOriginalName();
        $filepath = $fullPath . DIRECTORY_SEPARATOR . $filename;
        if (! move_uploaded_file($fileHandle->getRealPath(), $filepath . '__' . $chunkIndex)) {
            throw $this->exception('上传分片文件失败');
        }

        if ($chunkIndex === $chunkTotal - 1) {
            $data = '';
            for ($i = 0; $i < $chunkTotal; ++$i) {
                $data .= file_get_contents($filepath . '__' . $i);
            }
            if (! file_put_contents($filepath, $data)) {
                throw $this->exception('上传文件失败');
            }
            for ($i = 0; $i < $chunkTotal; ++$i) {
                @unlink($filepath . '__' . $i);
            }

            if ($md5 && md5_file($filepath) !== $md5) {
                throw $this->exception('校验文件失败');
            }
        } else {
            return false;
        }

        $fileInfo['file_ext']    = pathinfo($filepath, PATHINFO_EXTENSION);
        $fileInfo['name']        = pathinfo($filepath, PATHINFO_FILENAME);
        $fileInfo['upload_type'] = (int) sys_config('upload_type', 1);

        return (bool) $this->saveFileInfo([
            'pid'         => $spaceId,
            'file_type'   => (new \finfo(FILEINFO_MIME_TYPE))->file($filepath),
            'file_ext'    => $fileInfo['file_ext'],
            'file_size'   => filesize($filepath),
            'file_url'    => $path . DIRECTORY_SEPARATOR . $filename,
            'is_temp'     => $fileInfo['is_temp'] ?? 0,
            'file_name'   => $filename,
            'name'        => $fileInfo['name'],
            'upload_type' => $fileInfo['upload_type'],
            'user_id'     => $uid,
            'uid'         => app()->get(AdminService::class)->value($uid, 'uid'),
            'entid'       => 1,
        ]);
    }

    /**
     * 修改更新时间.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function saveUpdateTime(int $id)
    {
        $model = $this->dao->get($id, ['id', 'pid', 'path']);
        if ($model->pid > 0) {
            $ids = array_filter(explode('/', $model->path));
            $ids && $this->update(['id' => $ids, 'type' => 1], ['updated_at' => now()->toDateTimeString()]);
        }
    }

    /**
     * 文件格式转换.
     * @return array
     * @throws OssException
     * @throws Exception
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function formatConversion(string $file = 'file')
    {
        $fileHandle = app('request')->file($file);
        if (! $fileHandle->getClientOriginalExtension() && ! $fileHandle->extension()) {
            throw $this->exception('获取文件格式失败，请上传标准文件格式');
        }
        $type     = $fileHandle->getClientOriginalExtension() ?: $fileHandle->extension();
        $name     = pathinfo($fileHandle->getClientOriginalName(), PATHINFO_FILENAME);
        $savePath = public_path(pathinfo($fileHandle->getClientOriginalName(), PATHINFO_FILENAME));
        if (in_array($type, WebOffice::WPS_OFFICE_SHEET_TYPE) && $type != 'xlsx') {
            $savePath .= '.xlsx';
            $name .= '.xlsx';
            // 读取xlsm文件
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileHandle->getRealPath());
            // 写入xlsx文件
            $writer = new Xlsx($spreadsheet);
            $writer->save($savePath);
        } elseif (in_array($type, WebOffice::WPS_OFFICE_WORD_TYPE) && $type != 'doc' && $type != 'docx' && $type != 'txt' && $type != 'md') {
            $savePath .= '.docx';
            $name .= '.docx';
            $newWord = IOFactory::load($fileHandle->getRealPath());
            // 创建一个新的Word2007文档
            $writer = IOFactory::createWriter($newWord);
            // 保存文档为.docx格式
            $writer->save($savePath);
        } elseif ($type == 'doc') {
            return [];
        //            throw $this->exception('请上传标准的docx格式文件');
        } else {
            return [];
        }
        $upload = UploadService::init();
        $time   = now()->toArray();
        $dir    = 'cloud/' . $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        $res    = $upload->to($dir)->validate()->stream(file_get_contents($savePath), $name);
        if ($res === false) {
            unlink($savePath);
            throw new UploadException($upload->getError());
        }
        unlink($savePath);
        $fileInfo                = $upload->getUploadInfo();
        $fileInfo['file_ext']    = pathinfo($fileInfo['dir'], PATHINFO_EXTENSION);
        $fileInfo['name']        = pathinfo($fileInfo['real_name'], PATHINFO_FILENAME);
        $fileInfo['upload_type'] = (int) sys_config('upload_type', 1);
        return $fileInfo;
    }

    /**
     * 验证路径.
     * @return true|void
     * @throws BindingResolutionException
     */
    private function checkPath(int $id, int $spaceId, mixed $type = null)
    {
        if ($id === $spaceId) {
            return true;
        }
        $where = ['path' => $spaceId, 'id' => $id];
        if (! is_null($type)) {
            $where['type'] = $type;
        }
        if (! $this->dao->exists($where)) {
            throw $this->exception($type ? '文件夹不存在' : '资源不存在');
        }
    }

    /**
     * 获取模板地址
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getTempUrl(int $tempId): string
    {
        $host                          = env('API_HOST', 'https://manage.tuoluojiang.com');
        [$httpCode, $content, $header] = app()->get(HttpService::class)->requests($host . '/api/know/temp/download/' . $tempId);
        if (! $httpCode) {
            throw $this->exception(__('request was aborted'));
        }

        $content = json_decode($content, true);
        if (! isset($content['data']['url'])) {
            throw $this->exception('平台错误：发生异常，请稍后重试');
        }
        return $content['data']['url'];
    }

    /**
     * 从header中获取文件名.
     */
    private function getFileName(string $header): string
    {
        $name = '';
        if (strpos($header, 'filename')) {
            foreach (explode("\r\n", $header) as $loop) {
                $flag = strpos($loop, 'filename=');
                if ($flag !== false) {
                    $name = substr($loop, $flag + 9);
                    break;
                }
            }
        }
        return $name;
    }
}
