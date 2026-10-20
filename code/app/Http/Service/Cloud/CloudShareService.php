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

namespace App\Http\Service\Cloud;

use App\Http\Dao\Cloud\CloudShareDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *  文件分享.
 */
class CloudShareService extends BaseService
{
    public function __construct(CloudShareDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 合并空间已有成员权限，避免编辑空间时只提交新增成员导致原成员被清空.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function mergeSpaceRules(int $folderId, array $rule): array
    {
        $incoming = collect($rule)
            ->filter(fn ($item) => ! empty($item['uid']))
            ->keyBy('uid');

        $exists = $this->dao->select(['folder_id' => $folderId], with: ['auth'])?->toArray() ?? [];
        foreach ($exists as $item) {
            $uid = $item['to_uid'] ?? '';
            if (! $uid || $incoming->has($uid)) {
                continue;
            }

            $auth = $item['auth'] ?? [];
            $incoming->put($uid, [
                'uid'      => $uid,
                'value'    => $item['user_id'] ?? 0,
                'create'   => $auth['create'] ?? 0,
                'read'     => $auth['read'] ?? 1,
                'update'   => $auth['update'] ?? 0,
                'download' => $auth['download'] ?? 0,
                'delete'   => $auth['delete'] ?? 0,
            ]);
        }

        return $incoming->values()->all();
    }

    /**
     * 分享列表.
     * @param mixed $where
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function shareUserLst($where)
    {
        $list  = $this->dao->select($where, with: ['auth', 'user'])?->toArray();
        $count = count($list);
        return $this->listData($list, $count);
    }

    /**
     * 云盘分享.
     * @param null|mixed $name
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function spaceShare(mixed $folder, array $rule, $name = null)
    {
        $validator = ['download' => '下载', 'update' => '更新', 'create' => '创建', 'delete' => '删除'];
        foreach ($rule as $k => $item) {
            if (! $item['uid']) {
                throw $this->exception('请选择员工');
            }

            foreach ($item as $key => $val) {
                if (in_array($key, array_keys($validator)) && ! in_array($val, [0, 1])) {
                    throw $this->exception($validator[$key] . '权限有误');
                }
            }
            unset($rule[$k]['read']);
        }
        $this->transaction(function () use ($rule, $name, $folder) {
            if (! is_null($name)) {
                $folder->name = $name;
                $folder->saveOrFail();
            }
            $this->createShare($folder, $rule);
        });
    }

    /**
     * 删除分享.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function rmShare(int $folderId)
    {
        $this->dao->search(['folder_id' => $folderId])->delete();
        app()->get(CloudAuthService::class)->delete(['folder_id' => $folderId]);
    }

    /**
     * 获取目录权限.
     * @return array|mixed[]
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRules(int $fileId, int $uid, int $spaceId)
    {
        $authService = app()->get(CloudAuthService::class);
        $fileService = app()->get(CloudFileService::class);
        $fileService->checkAuth($spaceId, $uid, $fileId);
        $list     = $this->dao->select(['folder_id' => $spaceId], with: ['user'])?->toArray();
        $path     = $fileService->value($fileId, 'path');
        $path     = array_map('intval', explode('/', trim($path, '/')));
        $adminUid = $fileService->value(['id' => $path[0] ?? 0], 'user_id');
        $data     = $this->parentAuth($fileId, $fileService, $authService);
        $auths    = [];
        foreach ($data as $item) {
            $item['is_admin']        = intval($adminUid == $item['user_id']);
            $auths[$item['user_id']] = $item;
        }
        foreach ($list as $k => $item) {
            $list[$k]['auth'] = $auths[$item['user_id']] ?? null;
        }
        return $list;
    }

    /**
     * 设置目录权限.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setRules(int $fileId, int $uid, int $spaceId, array $rule = [])
    {
        app()->get(CloudFileService::class)->checkAuth($spaceId, $uid, $fileId);
        $admin   = app(AdminService::class)->column(['status' => 1, 'uid' => array_column($rule, 'uid')], 'id', 'uid');
        return $this->transaction(function () use ($fileId, $rule, $admin) {
            $now = now()->toDateTimeString();
            $this->dao->delete(['folder_id' => $fileId]);
            $auths = collect($rule)->filter(fn ($item)=>isset($item['uid']) && array_key_exists($item['uid'], $admin))
                ->map(function ($item) use ($fileId, $now, $admin) {
                    return [
                        'folder_id'  => $fileId,
                        'uid'        => $item['uid'],
                        'user_id'    => $admin[$item['uid']],
                        'create'     => $item['create'] ?? 0,
                        'read'       => $item['read'] ?? 0,
                        'update'     => $item['update'] ?? 0,
                        'download'   => $item['download'] ?? 0,
                        'delete'     => $item['delete'] ?? 0,
                        'created_at' => $now,
                    ];
                })->toArray();
            return $auths && app(CloudAuthService::class)->insert($auths);
        });
    }

    /**
     * 创建分享.
     * @param mixed $folder
     * @param mixed $rule
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function createShare($folder, $rule)
    {
        $this->transaction(function () use ($folder, $rule) {
            $shares = [];
            $make   = app()->get(CloudAuthService::class);
            $admin  = app()->get(AdminService::class);
            $now    = now()->toDateTimeString();
            $this->rmShare($folder->id);
            $this->search(['folder_id' => $folder->id], true)->delete();
            foreach ($rule as $item) {
                $auth = $make->create([
                    'user_id'    => $item['value'] ?: $admin->value(['uid' => $item['uid']], 'id'),
                    'uid'        => $item['uid'],
                    'folder_id'  => $folder->id,
                    'create'     => $item['create'] ?? 0,
                    'read'       => $item['read'] ?? 1,
                    'update'     => $item['update'] ?? 0,
                    'download'   => $item['download'] ?? 0,
                    'delete'     => $item['delete'] ?? 0,
                    'created_at' => $now,
                ]);
                $shares[] = [
                    'auth_id'    => $auth->id,
                    'user_id'    => $item['value'] ?: $admin->value(['uid' => $item['uid']], 'id'),
                    'to_uid'     => $item['uid'],
                    'entid'      => $folder->entid,
                    'folder_id'  => $folder->id,
                    'created_at' => $now,
                ];
            }
            $folder->is_share = 1;
            $folder->saveOrFail();
            $this->dao->insert($shares);
        });
    }

    /**
     * 获取父级权限.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function parentAuth(?int $fileId, CloudFileService $fileService, CloudAuthService $authService)
    {
        if (! $fileId) {
            return [];
        }

        $data = $authService->select(['folder_id' => $fileId])?->toArray();
        if (! $data) {
            return $this->parentAuth((int) $fileService->value($fileId, 'pid'), $fileService, $authService);
        }
        return $data;
    }
}
