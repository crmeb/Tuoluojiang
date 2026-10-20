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

namespace App\Http\Service\Notepad;

use App\Http\Contract\Notepad\NotepadInterface;
use App\Http\Dao\User\UserMemorialDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 笔记列表
 * Class NotepadService.
 */
class NotepadService extends BaseService implements NotepadInterface
{
    public function __construct(UserMemorialDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string $sort
     */
    public function getList(array $where, array $field = ['id', 'uid', 'pid', 'title', 'created_at', 'updated_at'], $sort = 'updated_at', array $with = []): array
    {
        // pid 为空/0 时查全部笔记（PC 端左侧树需全量挂载），避免 0 被当作根目录过滤
        if (array_key_exists('pid', $where) && ! $where['pid']) {
            unset($where['pid']);
        }

        // 列表不查 content：正文按需走详情接口，避免全量列表携带大文本
        // 左侧树要一次挂载全部笔记，这里显式解除全局 limitMax 上限（getPageValue 第二参 isRelieve=false），
        // 否则前端传的 limit 会被压回 100，超过 100 篇的笔记静默不显示
        [$page, $limit] = $this->getPageValue(true, false);
        $list  = $this->dao->getList($where, $field, $page, $limit, $sort, $with + [
            'user' => function ($query) {
                $query->select(['id', 'name']);
            },
            'cate' => function ($query) {
                $query->select(['id', 'name']);
            },
        ]);
        $count = $this->dao->count($where);

        return $this->listData($list, $count);
    }

    /**
     * 笔记详情（切换笔记时按需加载正文）.
     * @throws BindingResolutionException
     */
    public function memorialInfo(int $id, string $uid): array
    {
        // uid 必须查出，否则归属校验恒失败；updated_at 供前端面包屑下方展示最后修改时间
        $info = toArray($this->dao->get($id, ['id', 'uid', 'pid', 'title', 'content', 'updated_at']));
        if (! $info || $info['uid'] != $uid) {
            throw $this->exception('笔记不存在');
        }
        return $info;
    }

    /**
     * 保存数据.
     * @param mixed $data
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveData($data): bool
    {
        $services = app()->get(NotepadCateService::class);

        if (! $data['pid']) {
            $data['pid'] = (int) $services->value(['uid' => $data['uid'], 'pid' => 0, 'types' => 0], 'id');
        }

        if ($services->value(['id' => $data['pid']], 'uid') != $data['uid'] || $data['pid'] == 0) {
            throw $this->exception('请选择正确的分类');
        }
        $time               = date('Y-m-d H:i:s');
        $data['created_at'] = $time;
        $data['updated_at'] = $time;
        $this->dao->create($data);

        return true;
    }

    /**
     * 通过ID修改.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateById(int $id, array $data): bool
    {
        /** @var NotepadCateService $services */
        $services = app()->get(NotepadCateService::class);
        if ($services->value(['id' => $data['pid']], 'uid') != $data['uid'] && $data['pid'] != 0) {
            throw $this->exception('请选择正确的分类');
        }

        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('分类信息不存在');
        }

        if ($info->title !== $data['title'] || $info->content !== $data['content']) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        $this->dao->update($id, $data);
        return true;
    }

    /**
     * 获取记录详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfoById(int $id): array
    {
        return toArray($this->dao->get($id, ['*'], ['cate']));
    }

    /**
     * 分组数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupList(array $where): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getGroupList($where, ['id', 'pid', 'title', 'content', 'created_at', 'updated_at'], $page, $limit);
        $count          = $this->dao->getGroupCount($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取备忘录详情.
     */
    public function getInfo(array $where): array
    {
        $info = $this->get($where, ['*'], ['parent' => fn ($q) => $q->select(['id', 'pid', 'name'])]);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        return $info->toArray();
    }

    /**
     * 移动端列表.
     * @param array|string[] $field
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getListForApp(array $where, array $field = ['*'], array|string $sort = 'updated_at', array $with = []): array
    {
        app()->get(NotepadCateService::class)->defaultCategory($where['uid']);
        if (! $where['pid']) {
            if ($where['title']) {
                unset($where['pid']);
            } else {
                $where['pid'] = (int) app()->get(NotepadCateService::class)->value(['uid' => $where['uid'], 'types' => 0], 'id');
            }
        }
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        foreach ($list as &$item) {
            $item['content'] = mb_substr(strip_tags($item['content']), 0, 60);
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 修改备忘录.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data): bool
    {
        if ($data['pid'] && app()->get(NotepadCateService::class)->value(['id' => $data['pid']], 'uid') != $data['uid']) {
            throw $this->exception('请选择正确的文件夹');
        }
        $info = $this->dao->get($id, ['title', 'content', 'updated_at']) ?: throw $this->exception('修改的内容不存在');
        // 乐观锁：客户端带回了读取时的时间戳且与服务端不一致，说明期间已被其他端修改，拒绝覆盖
        // 兼容不带该字段的旧客户端（移动端）：仅在客户端提供时才校验
        $clientUpdatedAt = (string) ($data['updated_at'] ?? '');
        unset($data['updated_at']);
        if ($clientUpdatedAt !== '' && (string) $info->updated_at !== $clientUpdatedAt) {
            throw $this->exception('笔记已在其他端被修改，请刷新后重试');
        }
        if ($info->title !== $data['title'] || $info->content !== $data['content']) {
            $data['updated_at'] = now()->tz(config('app.timezone'))->toDateTimeString();
        }
        $this->dao->update($id, $data);
        return true;
    }

    /**
     * 分组数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function groupList(array $where, bool $cutContent = true): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getGroupList($where, ['id', 'pid', 'title', 'content', 'created_at', 'updated_at'], $page, $limit, cutContent: $cutContent);
        $count          = $this->dao->getGroupCount($where);
        return $this->listData($list, $count);
    }

    /**
     * 删除数据.
     * @param int $id
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        return $this->dao->delete($id, $key);
    }
}
