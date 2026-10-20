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

use App\Constants\CloudEnum;
use App\Http\Dao\Cloud\CloudAuthDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *  文件权限服务.
 */
class CloudAuthService extends BaseService
{
    public function __construct(CloudAuthDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 权限校验.
     * @return true|void
     * @throws BindingResolutionException
     */
    public function checkPermission(string $auth, int $uid, int $spaceId = 0, array $data = [])
    {
        if (! $this->dao->exists(['user_id' => $uid, 'folder_id' => $spaceId, $auth => 1])) {
            throw $this->exception('没有权限操作');
        }
        $ids = [];
        if (isset($data['id']) && $data['id'] && $data['id'] != $spaceId) {
            $ids = [$data['id']];
        } elseif (isset($data['ids']) && count($data['ids'])) {
            $ids = $data['ids'];
        }
        if ($ids) {
            if ($this->getFolderAuth($uid, $ids, $auth)) {
                return true;
            }
            if ($auth == CloudEnum::DELETE_AUTH) {
                $validIds = $this->validIds($ids, $uid);
                if (count($validIds) != count($ids)) {
                    throw $this->exception('没有权限操作');
                }
                return true;
            }
            throw $this->exception('没有权限操作');
        }
    }

    /**
     * 获取文件夹权限.
     * @param mixed $uid
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getFolderAuth($uid, array|int $fileId, string $auth = ''): array|bool
    {
        $ids     = is_array($fileId) ? $fileId : [$fileId];
        $folders = app()->get(CloudFileService::class)->column(['id' => $ids], ['id', 'path']);
        if (count($folders) != count($ids)) {
            return false;
        }
        if ($auth) {
            foreach ($folders as $folder) {
                $flag = $this->checkEntPathAuth($uid, $folder, $auth);
                if ($flag === false) {
                    return false;
                }
            }
            return true;
        }
        $arr = [];
        foreach ($folders as $folder) {
            $flag = $this->checkEntPathAuth($uid, $folder);
            if ($flag) {
                $arr[] = $flag;
            }
        }
        return $arr ? (is_array($fileId) ? $arr : $arr[0]) : $arr;
    }

    /**
     * 校验文件权限.
     * @param int $folderId 文件ID
     * @param int $uid 用户ID
     * @param string $auth 权限类型(create,read,update,download,delete)
     * @param bool $recursive 是否递归查询父级权限
     * @return bool 权限检查结果
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkFilePermission(int $folderId, int $uid, string $auth = 'read', bool $recursive = true): bool
    {
        // 获取文件路径信息
        $fileService = app(CloudFileService::class);
        $fileInfo    = $fileService->get($folderId, ['id', 'pid', 'path']);
        // 文件不存在时默认返回false（无权限）
        if (! $fileInfo) {
            return false;
        }
        // 构建需要查询的所有ID集合
        $queryIds = collect([$folderId]);
        if ($recursive) {
            // 解析路径获取所有父级ID并合并
            $pathIds  = collect(explode('/', trim($fileInfo['path'], '/')))->filter(fn ($id) => is_numeric($id) && $id > 0)->values();
            $queryIds = $queryIds->merge($pathIds);
        }
        // 一次性查询所有相关的权限记录
        $permissionMap = collect($this->dao->select(['user_id' => $uid, 'folder_id' => $queryIds->all()], ['folder_id', 'create', 'read', 'update', 'download', 'delete'])?->toArray())->keyBy('folder_id');
        // 按照检查顺序构建ID序列
        $checkOrder = collect([$folderId]);
        if ($recursive) {
            $pathIds    = collect(explode('/', trim($fileInfo['path'], '/')))->filter(fn ($id) => is_numeric($id) && $id > 0)->reverse()->values();
            $checkOrder = $checkOrder->merge($pathIds);
        }
        // 使用Collection的first方法查找第一个有权限的记录
        $result = $checkOrder->first(fn ($checkId) => $permissionMap->has($checkId));
        // 返回权限检查结果：找到权限记录则返回对应权限值，否则返回false
        return $result !== null && $permissionMap->get($result)[$auth];
    }

    /**
     * 校验权限.
     * @param mixed $uid
     * @param mixed $folder
     * @param mixed $auth
     * @return null|bool|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function checkEntPathAuth($uid, $folder, $auth = '')
    {
        $ids   = explode('/', trim($folder['path'], '/'));
        $ids[] = $folder['id'];
        $ids   = array_reverse($ids);
        $data  = $this->dao->column(['user_id' => $uid, 'folder_id' => $ids], ['user_id', 'folder_id', 'create', 'read', 'update', 'download', 'delete']);
        $auths = [];
        foreach ($data as $item) {
            $auths[$item['folder_id']] = $item;
        }
        foreach ($ids as $id) {
            if (isset($auths[$id])) {
                if ($auth) {
                    return (bool) $auths[$id][$auth];
                }
                return $auths[$id];
            }
        }
        return null;
    }
}
