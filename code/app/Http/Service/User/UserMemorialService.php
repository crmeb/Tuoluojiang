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

namespace App\Http\Service\User;

use App\Http\Dao\User\UserMemorialDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 笔记列表
 * Class UserMemorialServices.
 */
class UserMemorialService extends BaseService
{
    use ResourceServiceTrait;

    /**
     * UserMemorialServices constructor.
     */
    public function __construct(UserMemorialDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     *
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = 'updated_at', array $with = []): array
    {
        if ($where['pid'] < 1) {
            /** @var UserMemorialCategoryService $cateService */
            $cateService  = app()->get(UserMemorialCategoryService::class);
            $cate         = $cateService->getTopCateIdByUid($where['uid']);
            $where['pid'] = $cate->id;
        }

        return parent::getList($where, $field, $sort, $with + [
            'user' => function ($query) {
                $query->select(['id', 'name']);
            },
            'cate' => function ($query) {
                $query->select(['id', 'name']);
            },
        ]);
    }

    /**
     * 修改备忘录.
     *
     * @param int $id
     *
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        /** @var UserMemorialCategoryService $services */
        $services = app()->get(UserMemorialCategoryService::class);
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
     * 修改备忘录详情.
     *
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit($id)
    {
        return ($info = $this->dao->get($id, ['*'], ['cate'])) ? $info->toArray() : [];
    }

    /**
     * 保存备忘录.
     *
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        /** @var UserMemorialCategoryService $services */
        $services = app()->get(UserMemorialCategoryService::class);

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
     * @throws \ReflectionException
     */
    public function getMemorialList(array $where, array $field = ['*'], array|string $sort = 'updated_at', array $with = []): array
    {
        if (! $where['pid']) {
            if ($where['title']) {
                unset($where['pid']);
            } else {
                /** @var UserMemorialCategoryService $cateService */
                $cateService  = app()->get(UserMemorialCategoryService::class);
                $where['pid'] = (int) $cateService->value(['uid' => $where['uid'], 'types' => 0], 'id');
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
}
