<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Cloud;

use App\Http\Dao\Cloud\CloudFileDao;
use App\Http\Service\Admin\AdminService;
use App\Task\folder\SpaceDestroyTask;
use App\Http\Service\BaseService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use League\Flysystem\WhitespacePathNormalizer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 云盘空间服务.
 */
class CloudSpaceService extends BaseService
{
    public function __construct(CloudFileDao $dao)
    {
        $this->dao = $dao;
    }

    public function spaceList($where)
    {
        $search = $this->dao->getSpaceSearch($where);
        return $search->select('folder.*')->orderBy('folder_share.created_at', 'ASC')->orderBy('folder.created_at', 'ASC')->get()?->toArray();
    }

    /**
     * 创建云盘空间.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function createSpace(int $uid, string $name, array $rule)
    {
        /** @var WhitespacePathNormalizer $pathNormalizer */
        $pathNormalizer = app()->get(WhitespacePathNormalizer::class);
        $uuid           = app()->get(AdminService::class)->value($uid, 'uid');
        $space          = app()->get(CloudFileService::class)->saveFileInfo([
            'name'    => $pathNormalizer->normalizePath($name),
            'pid'     => 0,
            'user_id' => $uid,
            'uid'     => $uuid,
        ], 1);
        if (! $rule) {
            $rule[] = [
                'create'   => 1,
                'delete'   => 1,
                'download' => 1,
                'read'     => 1,
                'update'   => 1,
                'uid'      => $uuid,
                'value'    => $uid,
            ];
        } else {
            $rule = app()->get(CloudShareService::class)->mergeSpaceRules($id, $rule);
        }
        $rule && app()->get(CloudShareService::class)->spaceShare($space, $rule);
    }

    /**
     * 删除云盘空间.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteSpace(int $id, int $uid)
    {
        $userId = $this->dao->value(['type' => 1, 'id' => $id], 'user_id');
        if (! $userId) {
            throw $this->exception('未找到指定的空间');
        }
        if ($userId !== $uid) {
            throw $this->exception('只能删除自己创建的空间');
        }
        $this->dao->delete(['all_id' => $id]) && Task::deliver(new SpaceDestroyTask($id));
    }

    /**
     * 更新空间.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function updateSpace(int $id, mixed $name, mixed $rule, int $uid)
    {
        $space = $this->dao->get($id);
        if (! $space) {
            throw $this->exception('空间不存在');
        }
        if ($space->type !== 1) {
            throw $this->exception('只能修改空间名称');
        }
        if ($space->user_id != $uid) {
            throw $this->exception('只能修改自己的空间');
        }
        $pathNormalizer = app()->get(WhitespacePathNormalizer::class);
        $space->name    = $pathNormalizer->normalizePath($name);
        if (! $rule) {
            $rule[] = [
                'create'   => 1,
                'delete'   => 1,
                'download' => 1,
                'read'     => 1,
                'update'   => 1,
                'uid'      => app()->get(AdminService::class)->value($uid, 'uid'),
                'value'    => $uid,
            ];
        }
        $rule && app()->get(CloudShareService::class)->spaceShare($space, $rule);
    }

    /**
     * 获取文件夹列表.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dirList(int $uid)
    {
        $where['path'] = app()->get(CloudAuthService::class)->column(['user_id' => $uid, 'create' => 1], 'folder_id');
        $where['type'] = 1;
        $list          = $this->dao->select($where, ['pid', 'id', 'name'])?->toArray();
        return get_tree_children($list);
    }

    /**
     * 转移空间.
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function transfer(int $id, int $uid, string $toUid)
    {
        if (! $id && ! $this->dao->exists(['type' => 1, 'id' => $id, 'user_id' => $uid])) {
            throw $this->exception('没有权限转让');
        }
        $toUser = app()->get(AdminService::class)->get(['uid' => $toUid])?->toArray();
        if ($toUser['status'] != 1) {
            throw $this->exception('请选择有效的员工');
        }
        return $this->dao->update($id, ['uid' => $toUser['uid'], 'user_id' => $toUser['id']]);
    }
}
