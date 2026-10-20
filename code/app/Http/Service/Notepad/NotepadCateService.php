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

namespace App\Http\Service\Notepad;

use App\Http\Dao\User\UserMemorialCategoryDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 笔记文件夹
 * Class UserMemorialCategoryServices.
 */
class NotepadCateService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(UserMemorialCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     *
     * @param array|string[] $field
     * @param null $sort
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = ['id' => 'asc'], array $with = []): array
    {
        $service = app()->get(NotepadService::class);

        if (! $this->dao->exists(['uid' => $where['uid'], 'types' => 0])) {
            $this->dao->create(['uid' => $where['uid'], 'name' => '我的文件夹', 'types' => 0]);
        }
        $where['types'] = 1;
        $total          = $service->count(['uid' => $where['uid']]);
        $list           = toArray($list = $this->dao->getList($where, $field, 0, 0, $sort, $with + [
            'cate' => function ($query) {
                $query->select(['pid', 'id', 'name']);
            },
        ]));
        $tree = get_tree_children($list);
        return compact('total', 'tree');
    }

    /**
     * 获取列表移动端.
     *
     * @param array|string[] $field
     * @param null $sort
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getListV1(array $where, array $field = ['*'], $sort = ['id' => 'asc'], array $with = []): array
    {
        /** @var NotepadService $service */
        $service = app()->get(NotepadService::class);
        $total   = $service->count(['uid' => $where['uid']]);
        $list    = toArray($this->dao->getList($where, $field, 0, 0, $sort, $with + [
            'cate' => function ($query) {
                $query->select(['pid', 'id', 'name']);
            },
        ]));
        $tree   = $list;
        $pid    = isset($where['pid']) ? $where['pid'] : 0;
        $parent = [];
        if ($pid) {
            $parent = ($parent = $this->dao->get(['id' => $where['pid']], ['pid', 'name'])) ? $parent->toArray() : [];
        }
        return compact('total', 'tree', 'pid', 'parent');
    }

    /**
     * 获取备忘录文件夹tree型数据.
     *
     * @param mixed $id
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getBillCateTree($id = 0)
    {
        $where['uid'] = $this->uuId(false);
        if ($id) {
            $where['not_id'] = array_merge([(int) $id], $this->dao->column(['uid' => $where['uid'], 'path' => $id], 'id'));
        }
        $list = ($list = $this->dao->getList($where, ['name as label', 'id as value', 'pid'], 0, 0, 'id')) ? $list->toArray() : [];

        return get_tree_children($list, 'children', 'value');
    }

    /**
     * 获取修改备忘录文件夹表单.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankCateInfo = $this->dao->get($id);
        if (! $rankCateInfo) {
            throw $this->exception('修改的备忘录文件夹不存在');
        }

        return $this->createElementForm('修改备忘录文件夹', $this->getBillCateFormRule(collect($rankCateInfo->toArray())), '/ent/user/memorial_cate/' . $id, 'PUT');
    }

    /**
     * 获取创建备忘录文件夹表单.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceCreate(array $other = []): array
    {
        if (isset($other['pid'])) {
            if ($other['pid']) {
                $path   = $this->normalizePath($this->dao->value(['id' => $other['pid']], 'path'));
                $path[] = (int) $other['pid'];
            } else {
                $path[] = (int) $this->dao->value(['uid' => $other['uid'], 'types' => 0], 'id');
            }
        } else {
            $path = [];
        }

        return $this->createElementForm('添加备忘录文件夹', $this->getBillCateFormRule(collect([]), $path), '/ent/user/memorial_cate');
    }

    /**
     * 保存数据.
     *
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $data = $this->getData($data);
        if ($this->dao->exists(['pid' => $data['pid'], 'name' => $data['name'], 'uid' => $data['uid']])) {
            throw $this->exception('文件夹已存在，请勿重复添加');
        }

        return $this->dao->create($data);
    }

    /**
     * 修改数据.
     *
     * @param int $id
     *
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $data = $this->getData($data);
        if (! $this->dao->value(['id' => $id], 'types') && (isset($data['pid']) && $data['pid'])) {
            throw $this->exception('该文件夹不能修改前置文件夹');
        }

        $this->dao->update($id, $data);

        return true;
    }

    /**
     * 删除备忘录文件夹.
     *
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (! $this->dao->value(['id' => $id], 'types')) {
            throw $this->exception('该文件夹无法删除');
        }
        $service = app()->get(NotepadService::class);
        if ($service->exists(['pid' => $id, 'uid' => $this->uuId(false)]) || $this->dao->exists(['pid' => $id])) {
            throw $this->exception('请先删除关联备忘录或文件夹,再尝试删除');
        }

        return $this->dao->delete($id, $key);
    }

    /**
     * 删除备忘录文件夹.
     *
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDeleteV1($id, ?string $key = null)
    {
        $service = app()->get(NotepadService::class);
        if ($service->exists(['pid' => $id, 'uid' => $this->uuId(false)]) || $this->dao->exists(['pid' => $id])) {
            throw $this->exception('请先删除关联备忘录或文件夹,再尝试删除');
        }

        return $this->dao->delete($id, $key);
    }

    /**
     * 获取文件夹列表.
     * @param array|string[] $field
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCateList(array $where, array $field = ['id', 'name', 'pid', 'path', 'types', 'created_at', 'updated_at'], ?array $sort = ['created_at' => 'desc'], array $with = []): array
    {
        $where['pid'] = (int) $where['pid'];
        if (! $where['pid']) {
            if ($where['name_like']) {
                unset($where['pid']);
            } else {
                $where['pid'] = (int) $this->value(['uid' => $where['uid'], 'types' => 0], 'id');
            }
        }
        $count          = $this->dao->getUniCount($where);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->setWithListCount(false)->getUniList($where, $field, $page, $limit, $sort, $with)->toArray();
        if (! $list && ! $this->dao->exists(['uid' => $where['uid'], 'types' => 0])) {
            $this->dao->create(['uid' => $where['uid'], 'name' => '我的文件夹', 'types' => 0]);
            $list = $this->dao->setWithListCount(false)->getUniList($where, $field, $page, $limit, $sort, $with)->toArray();
        }

        $parent = null;
        if (isset($where['pid']) && $where['pid'] > 0) {
            $parent = $this->dao->get(
                ['uid' => $where['uid'], 'id' => $where['pid'], 'types' => 1],
                ['id', 'pid', 'name', 'path'],
                ['parent' => fn ($q) => $q->where(['uid' => $where['uid'], 'types' => 1])->select(['id', 'pid', 'name', 'path']),
                ]
            );
        }

        return compact('count', 'list', 'parent');
    }

    /**
     * 数据处理.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getData(array $data): array
    {
        $path        = $this->normalizePath($data['path'] ?? []);
        $data['pid'] = (int) $data['pid'];
        if ($path) {
            $parent      = $this->checkParent($data['uid'], intval($path[count($path) - 1]));
            $data['pid'] = $parent->id;
            $data['path'] = $path;
        } else {
            $parent = $this->checkParent($data['uid'], $data['pid']);
            if ($parent->types) {
                $path = $parent->path;
                if (is_array($path)) {
                    $path[] = $data['pid'];
                }
            }
            $data['pid']  = $parent->id;
            $data['path'] = $path;
        }

        if (count($this->getBusinessPath($path, $data['uid'])) > 2) {
            throw $this->exception('文件夹最多可添加三级');
        }
        return $data;
    }

    /**
     * 获取父级文件夹.
     * @return null|int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function checkParent(string $uuid, int $pid = 0): mixed
    {
        $cate = null;
        if ($pid) {
            $cate = $this->dao->get(['uid' => $uuid, 'id' => $pid]);
        }
        if (! $cate) {
            $cate = $this->getTopCateIdByUid($uuid);
        }
        if (! $cate) {
            throw $this->exception('父级文件夹获取异常');
        }
        return $cate;
    }

    /**
     * 文件夹可移动列表.
     */
    public function movable(int $id, string $uuid): array
    {
        $info = $this->dao->get(['uid' => $uuid, 'id' => $id]);
        if (! $info) {
            throw $this->exception('文件夹获取异常');
        }

        $childPathCount = 0;
        $pathCount      = count($info->path);
        $child          = $this->column(['uid' => $uuid, 'path' => $id], ['id', 'path']);
        foreach ($child as $item) {
            $count = count($item['path']);
            if ($childPathCount < $count) {
                $childPathCount = $count;
            }
        }

        $field = ['name as label', 'id as value', 'pid', 'path'];
        $where = ['uid' => $uuid, 'types' => 1, 'not_id' => [$id, $info->pid]];
        $list  = ($list = $this->dao->getList($where, $field, 0, 0, 'id')) ? $list->toArray() : [];
        foreach ($list as $key => $item) {
            $itemPathCount = count($item['path']);
            if (($pathCount >= 2 && $childPathCount >= 1 && $itemPathCount >= 2)
                || ($pathCount >= 1 && $childPathCount >= 1 && $itemPathCount >= 1)
                || (! $pathCount && ($childPathCount >= 2 || ($childPathCount >= 1 && $itemPathCount >= 1) || $itemPathCount >= 2))
                || ($childPathCount >= 2 && $itemPathCount >= 1)
                || (! $childPathCount && $itemPathCount >= 2)
            ) {
                unset($list[$key]);
            }
        }
        return get_tree_children($list, 'children', 'value');
    }

    /**
     * 文件夹移动.
     * @throws BindingResolutionException
     */
    public function move(int $id, int $pid, string $uuid): mixed
    {
        $info = $this->get(['id' => $id, 'uid' => $uuid], ['id', 'pid']);
        if (! $info) {
            throw $this->exception('文件夹获取异常');
        }

        if ($info->pid === $pid) {
            return true;
        }

        $parent = $this->get(['id' => $pid, 'uid' => $uuid], ['id', 'pid', 'path']);
        if (! $parent) {
            throw $this->exception('父级文件夹获取异常');
        }

        $pathCount = count($parent->path);
        if ($pathCount >= 2) {
            throw $this->exception('父文件夹层级不能超过两级');
        }

        $childPathCount = 0;
        $child          = $this->column(['uid' => $uuid, 'path' => $id], ['id', 'path']);
        foreach ($child as $item) {
            $count = count($item['path']);
            if ($childPathCount < $count) {
                $childPathCount = $count;
            }
        }

        if ($pathCount >= 1 && $childPathCount >= 2) {
            throw $this->exception('文件夹层级不能超过两级');
        }
        $list = $this->column(['uid' => $uuid, 'path' => $id], 'id');
        if (in_array($pid, $list)) {
            throw $this->exception('不能移动至下级文件夹');
        }

        return $this->transaction(function () use ($id, $childPathCount, $parent) {
            $path = array_merge($parent->path, [$parent->id]);
            $this->dao->update(['id' => $id], ['pid' => $parent->id, 'path' => array_merge($parent->path, [$parent->id])]);
            if ($childPathCount) {
                $this->dao->update(['pid' => $id], ['path' => array_merge($path, [$id])]);
            }
            return true;
        });
    }

    /**
     * 获取顶级文件夹.
     * @return BaseModel|bool|Model
     * @throws BindingResolutionException
     */
    public function getTopCateIdByUid(string $uid)
    {
        $where = ['uid' => $uid, 'types' => 0];
        return $this->dao->firstOrCreate($where, array_merge($where, ['name' => '我的文件夹']));
    }

    /**
     * 默认创建一个文件夹.
     * @param mixed $uid
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function defaultCategory($uid)
    {
        if (! $this->dao->exists(['uid' => $uid, 'types' => 0, 'pid' => 0])) {
            $defaultId = $this->dao->getIncId(['uid' => $uid, 'name' => '我的文件夹', 'types' => 0]);
        } else {
            $defaultId = $this->dao->value(['uid' => $uid, 'types' => 0, 'pid' => 0], 'id');
        }
        $this->dao->update(['pid' => 0, 'uid' => $uid, 'types' => 1], ['pid' => $defaultId]);
    }

    /**
     * 获取备忘录文件夹表单规则.
     *
     * @param mixed $path
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getBillCateFormRule(Collection $collection, $path = [])
    {
        $path = $this->getParentPath(
            $path ?: (array) ($collection->get('path', []) ?? []),
            (int) $collection->get('pid', 0),
            $collection->get('uid') ?: $this->uuId(false)
        );

        return [
            Form::cascader('path', '前置文件夹')
                ->options($this->getBillCateTree($collection->get('id')))->value($path)->appendRule('value', $path)
                ->props(['props' => ['checkStrictly' => true]])->placeholder('请选择前置文件夹')
                ->clearable(true),
            Form::input('name', '文件夹名称', $collection->get('name'))->required()->maxlength(15)->showWordLimit(true)->placeholder('请输入文件夹名称'),
        ];
    }

    /**
     * 获取表单前置文件夹回显路径.
     */
    protected function getParentPath($path, int $pid = 0, string $uid = ''): array
    {
        $path = $this->normalizePath($path);
        if ($pid && ! in_array($pid, $path, true)) {
            $path[] = $pid;
        }

        $topId = $uid ? (int) $this->getTopCateIdByUid($uid)->id : 0;
        if ($topId && ! in_array($topId, $path, true)) {
            array_unshift($path, $topId);
        }

        return $path;
    }

    /**
     * 标准化文件夹路径.
     */
    protected function normalizePath($path): array
    {
        if (is_string($path)) {
            $path = explode('/', $path);
        }
        if (! is_array($path)) {
            $path = [$path];
        }

        return array_values(array_filter(array_map('intval', $path)));
    }

    /**
     * 去掉默认根文件夹后的业务路径.
     */
    protected function getBusinessPath(array $path, string $uid): array
    {
        $topId = (int) $this->getTopCateIdByUid($uid)->id;
        if ($topId) {
            $path = array_values(array_filter($path, fn ($id) => (int) $id !== $topId));
        }

        return $path;
    }
}
