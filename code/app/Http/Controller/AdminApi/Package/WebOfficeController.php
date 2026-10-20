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

namespace App\Http\Controller\AdminApi\Package;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\File\FileAuthService;
use App\Http\Service\File\FilePermissionsService;
use App\Http\Service\File\FileService;
use App\Http\Service\File\FolderHistoryService;
use App\Task\message\CloudFileReadRemind;
use Tuoluojiang\Open\exceptions\ServicesException;
use Tuoluojiang\Open\interfaces\OptionsInterface;
use Tuoluojiang\Open\services\synchro\Company;
use Tuoluojiang\Open\services\wps\options\OfficeCommonOptions;
use Tuoluojiang\Open\services\wps\options\OfficeFileInfoOptions;
use Tuoluojiang\Open\services\wps\options\OfficeFileNewOptions;
use Tuoluojiang\Open\services\wps\WebOffice as WebOfficeService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WebOfficeController extends AuthController
{
    /**
     * 新建文件.
     * @return JsonResponse|mixed
     */
    public function newFile(WebOfficeService $office, OfficeFileNewOptions $options): mixed
    {
        [$file, $name] = $this->request->postMore([
            ['file', 'file'],
            ['name', ''],
        ], true);

        $param = $this->request->getMore([
            ['_w_type', 's'],
            ['_w_path', ''],
            ['_w_folder', ''],
            ['_w_dir', ''],
        ]);

        try {
            if ($param['_w_folder']) {
                $folder               = app()->get(FileService::class)->upload($file, $this->uId, 0, 0);
                $options->redirectUrl = $office->editOffic($folder->folder_sn, $param['_w_type'], $param);
            } else {
                $fileInfo             = $this->service->upload($file, $name, null, $param['_w_path'], $this->uId, (int) $this->userInfo['entid'], $param['_w_dir'] ?? null);
                $options->redirectUrl = $office->editOffic($fileInfo['file_id'], $param['_w_type'], $param);
            }
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }

        $options->userId = str_replace('-', '', $this->uuid);

        return $this->successf($options);
    }

    /**
     * 获取文件数据.
     * @return JsonResponse|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function infoFile(OfficeFileInfoOptions $options, FilePermissionsService $services): mixed
    {
        $permission = $this->request->get('_w_permission');
        $isFolder   = $this->request->get('_w_folder');
        $isTemplate = $this->request->get('_w_template');
        if ($isTemplate) {
            // 模板文件预览
            $id   = $this->fileId;
            $file = app()->get(Company::class)->setConfig()->templateInfoById(['id' => $id, 'view' => 0]);
            if (empty($file)) {
                throw new ServicesException('文件不存在!');
            }
            $fileExt               = $file['ext'] ?: pathinfo($file['url'], PATHINFO_EXTENSION);
            $name                  = $file['real_name'];
            $options->id           = $id;
            $options->version      = (int) $file['version'];
            $options->size         = (int) $file['size'];
            $options->creator      = '1';
            $options->createTime   = datetime_timestamp($file['created_at']);
            $options->download_url = $file['url'];
            $options->name         = $name . '.' . $fileExt;
            $options->modifier     = '1';
            $options->modifyTime   = time();

            $options->user['id']         = $this->uId;
            $options->user['name']       = $this->userInfo['account'] ?: $this->userInfo['real_name'];
            $options->user['permission'] = 'read';
            $options->user['avatar_url'] = $this->userInfo['avatar'];

            $options->watermark['type']  = 1;
            $options->watermark['value'] = '《陀螺匠》企业管理文档模板';

            $options->userAcl['rename']  = 0;    // 重命名权限，1为打开该权限，0为关闭该权限，默认为0
            $options->userAcl['history'] = 0;   // 历史版本权限，1为打开该权限，0为关闭该权限,默认为1
            $options->userAcl['copy']    = 0;      // 复制
            $options->userAcl['export']  = 0;    // 导出PDF
            $options->userAcl['print']   = 0;     // 打印
        } else {
            if ($isFolder) {
                $folder = app()->get(FileService::class)->get(['file_sn' => $this->fileId, 'type' => 0]);
                if (! $folder) {
                    return $this->error('文件不存在');
                }
                if ($folder->uid === $this->uId) {
                    $permission = 'write';
                } else {
                    if (! $folder->entid) {
                        $auth = app()->get(FileAuthService::class)->checkAuth($folder->id, $this->uId);
                    } else {
                        $auth  = app()->get(FileAuthService::class)->checkEntAuth($this->uId, explode('/', trim($folder->path, '/'))[0], 'read');
                        $auth2 = app()->get(FileAuthService::class)->checkEntPathAuth($this->uId, $folder, 'read');
                        if ($auth2) {
                            $auth = $auth2;
                        }
                    }
                    if (! $auth) {
                        return $this->error('您没有权限查看文件');
                    }
                    $permission = $auth->update ? 'write' : 'read';
                }

                $extension             = pathinfo($folder->file_name, PATHINFO_EXTENSION);
                $name                  = $folder->name;
                $options->id           = $folder->file_sn;
                $options->version      = (int) $folder->version;
                $options->size         = (int) $folder->size;
                $options->creator      = $folder->uid;
                $options->createTime   = datetime_timestamp($folder->created_at);
                $options->download_url = get_download_url($this->fileId, ['folder' => 1]);
                $options->user['id']   = $folder->uid;

                // 文件阅读提醒
                Task::deliver(new CloudFileReadRemind($this->entid, $this->uId, $folder->toArray()));
            } else {
                $info = $this->service->get(['file_id' => $this->fileId, 'is_master' => 1], ['uid', 'real_name', 'entid', 'version', 'uid', 'edit_uid', 'name', 'file_id', 'created_at', 'updated_at', 'size']);
                if (! $info) {
                    return $this->error('文件信息未查询到');
                }
                // 如果是自己的文档而且没有查看文件方式默认进行浏览
                if ($info->uid == $this->uId && ! $permission) {
                    $permission = 'read';
                }
                // 如果还没有浏览方式,查看当前用户的文件访问权限
                if (! $permission) {
                    $filePermissions = $services->get(['entid' => $this->entid, 'uid' => $this->uId, 'file_id' => $this->fileId], ['type']);
                    if (! $filePermissions) {
                        return $this->error('您没有权限查看文件');
                    }
                    $permission = $filePermissions->type;
                }

                $extension             = pathinfo($info->name, PATHINFO_EXTENSION);
                $name                  = pathinfo($info->real_name, PATHINFO_FILENAME);
                $options->id           = $info->file_id;
                $options->version      = (int) $info->version;
                $options->size         = (int) $info->size;
                $options->creator      = $info->uid;
                $options->createTime   = datetime_timestamp($info->created_at);
                $options->download_url = get_download_url($this->fileId);
                $options->user['id']   = $info->uid;
            }
            $options->name               = $name . '.' . $extension;
            $options->modifier           = $this->uId;
            $options->previewPages       = 10;
            $options->modifyTime         = time();
            $options->user['name']       = $this->userInfo['account'] ?: $this->userInfo['real_name'];
            $options->user['permission'] = $permission;
            $options->user['avatar_url'] = $this->userInfo['avatar'];
            $options->watermark['type']  = 1;
            $options->watermark['value'] = $this->userInfo['account'];
        }
        $options->watermark['fillstyle']  = 'rgba(192,192,192,0.6)';
        $options->watermark['font']       = 'bold 20px Serif';
        $options->watermark['rotate']     = -0.7853982;
        $options->watermark['horizontal'] = 50;
        $options->watermark['vertical']   = 100;
        return $this->successf($options);
    }

    /**
     * 当前协作用户信息.
     * @return JsonResponse|mixed
     */
    public function infoUser(OfficeCommonOptions $options, AdminService $services): mixed
    {
        [$ids] = $this->request->postMore([
            ['ids', []],
        ], true);

        $options->users = $services->column(['uid' => $ids], ['uid as id', 'account as name', 'avatar as avatar_url']);

        return $this->successf($options);
    }

    /**
     * 通知此文件目前有哪些人正在协作.
     * @return JsonResponse|mixed
     */
    public function online(): mixed
    {
        [$ids] = $this->request->postMore([
            ['ids', []],
        ], true);

        return $this->officeSuccess();
    }

    /**
     * 上传文件新版本.
     * @return JsonResponse|mixed
     * @throws BindingResolutionException
     */
    public function save(OfficeCommonOptions $options): mixed
    {
        $param = $this->request->getMore([
            ['_w_type', 's'],
            ['_w_path', ''],
            ['_w_dir', ''],
            ['_w_folder', ''],
        ]);

        [$file] = $this->request->postMore([
            ['file', 'file'],
        ], true);

        if ($param['_w_folder']) {
            $make   = app()->get(FileService::class);
            $folder = $make->get(['file_sn' => $this->fileId, 'type' => 0]);
            if (! $folder) {
                return $this->error('文件不存在');
            }
            if ($folder->entid) {
                $auth  = app()->get(FileAuthService::class)->checkEntAuth($this->uId, explode('/', trim($folder->path, '/'))[0], 'read');
                $auth2 = app()->get(FileAuthService::class)->checkEntPathAuth($this->uId, $folder, FileAuthService::UPDATE_AUTH);
                if ($auth2) {
                    $auth = $auth2;
                }
            } else {
                $auth = app()->get(FileAuthService::class)->checkEntAuth($this->uId, $folder->id, FileAuthService::UPDATE_AUTH);
            }
            if ($this->uId != $folder->uid && ! $auth) {
                return $this->error('没有权限编辑');
            }
            $make->updateVersion($this->uId, $folder, $file);

            $options->file = [
                'id'           => $folder->file_sn,
                'name'         => $folder->name,
                'version'      => (int) $folder->version,
                'size'         => (int) $folder->file_size,
                'download_url' => get_download_url($folder->file_sn, ['folder' => 1]),
            ];
        } else {
            $fileInfo = $this->service->get(['file_id' => $this->fileId, 'is_master' => 1], ['name', 'real_name', 'cate_id', 'image', 'is_template']);

            try {
                $fileInfo = $this->service->setOther([
                    'cate_id'     => $fileInfo->cate_id,
                    'image'       => $fileInfo->image,
                    'real_name'   => $fileInfo->real_name,
                    'is_template' => $fileInfo->is_template,
                ])->upload($file, $fileInfo->name, $this->fileId, $param['_w_path'], $this->uId, (int) $this->userInfo['entid'], $param['_w_dir'] ?? null);
            } catch (\Throwable $e) {
                return $this->error($e->getMessage());
            }

            $options->file = [
                'id'           => $fileInfo['file_id'],
                'name'         => $fileInfo['name'],
                'version'      => (int) $fileInfo['version'],
                'size'         => (int) $fileInfo['size'],
                'download_url' => get_download_url($this->fileId, ['version' => $fileInfo['version']]),
            ];
        }

        return $this->successf($options);
    }

    /**
     * 获取特定版本的文件信息.
     * @param mixed $version
     * @return JsonResponse|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function version(OfficeCommonOptions $options, $version): mixed
    {
        $isFolder = $this->request->get('_w_folder');

        if ($isFolder) {
            $folder = app()->get(FileService::class)->get(['file_sn' => $this->fileId, 'type' => 0]);
            if (! $folder) {
                return $this->error('文件不存在');
            }
            $history = app()->get(FolderHistoryService::class)->get(['folder_id' => $folder->id, 'version' => $version]);
            if (! $history) {
                return $this->error('记录不存在');
            }

            $options->file = [
                'id'           => $folder->file_sn,
                'name'         => $folder->name,
                'version'      => (int) $version,
                'size'         => (int) $folder->file_size,
                'create_time'  => datetime_timestamp($folder->created_at),
                'creator'      => $folder->uid,
                'modify_time'  => datetime_timestamp($history->created_at),
                'modifier'     => $history->uid,
                'download_url' => get_download_url($this->fileId, ['version' => $version, 'folder' => 1]),
            ];
        } else {
            $fileInfo = $this->service->get(['file_id' => $this->fileId, 'uid' => $this->uId, 'version' => $version], ['file_id', 'uid', 'edit_uid', 'name', 'version', 'size', 'created_at', 'updated_at']);

            if (! $fileInfo) {
                return $this->error('文件尚未查到');
            }

            $options->file = [
                'id'           => $fileInfo->file_id,
                'name'         => $fileInfo->name,
                'version'      => (int) $version,
                'size'         => (int) $fileInfo->size,
                'create_time'  => datetime_timestamp($fileInfo->created_at),
                'creator'      => $fileInfo->uid,
                'modify_time'  => datetime_timestamp($fileInfo->updated_at),
                'modifier'     => $fileInfo->edit_uid,
                'download_url' => get_download_url($fileInfo->file_id, ['version' => $version]),
            ];
        }

        return $this->successf($options);
    }

    /**
     * 修改名称.
     * @return JsonResponse|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function rename(): mixed
    {
        [$name] = $this->request->postMore([
            ['name', ''],
        ], true);
        $isFolder = $this->request->get('_w_folder');

        if ($isFolder) {
            $folder = app()->get(FileService::class)->get(['file_sn' => $this->fileId, 'type' => 0]);
            if (! $folder) {
                return $this->error('文件不存在');
            }
            if ($folder->entid) {
                $auth  = app()->get(FileAuthService::class)->checkEntAuth($this->uId, explode('/', trim($folder->path, '/'))[0], 'read');
                $auth2 = app()->get(FileAuthService::class)->checkEntPathAuth($this->uId, $folder, FileAuthService::UPDATE_AUTH);
                if ($auth2) {
                    $auth = $auth2;
                }
            } else {
                $auth = app()->get(FileAuthService::class)->checkEntAuth($this->uId, $folder->id, FileAuthService::UPDATE_AUTH);
            }
            if ($this->uId != $folder->uid && ! $auth) {
                return $this->error('没有权限编辑');
            }
            $folder->update(['name' => Util::normalizePath($name)]);
        } else {
            $this->service->update(['file_id' => $this->fileId], ['real_name' => $name]);
        }

        return $this->officeSuccess();
    }

    /**
     * 获取历史版本.
     * @return JsonResponse|mixed
     */
    public function history(OfficeCommonOptions $options): mixed
    {
        [$id, $offset, $count] = $this->request->postMore([
            ['id', ''],
            ['offset', 1],
            ['count', 10],
        ], true);
        $isFolder = $this->request->get('_w_folder');

        if ($isFolder) {
            $histories = app()->get(FolderHistoryService::class)->folderHistory($this->fileId, (int) $offset, (int) $count);
        } else {
            $histories = $this->service->getFileHistories($this->fileId, (int) $offset, (int) $count);
        }
        $options->histories = $histories;
        return $this->successf($options);
    }

    /**
     * 回调事件.
     * @return JsonResponse|mixed
     */
    public function onnotify(): mixed
    {
        [$cmd, $body] = $this->request->postMore([
            ['cmd', ''],
            ['body', []],
        ], true);

        $this->service->officeEvent($cmd, $body);

        return $this->officeSuccess();
    }

    /**
     * 正确返回.
     * @return JsonResponse|mixed
     */
    protected function successf(OptionsInterface $options): mixed
    {
        return Response::json($options->toArray());
    }

    /**
     * office成功返回.
     * @return JsonResponse|mixed
     */
    protected function officeSuccess(): mixed
    {
        $options = new OfficeCommonOptions();

        $options->common['code'] = 200;

        return $this->successf($options);
    }

    /**
     * 失败返回.
     */
    protected function error(?string $message = null, ?string $details = null): mixed
    {
        $data = [
            'code'    => 40007,
            'message' => 'CustomMsg',
            'details' => $message,
            'hint'    => $details,
        ];
        return Response::json($data);
    }
}
