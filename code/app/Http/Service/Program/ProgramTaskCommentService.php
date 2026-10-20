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

namespace App\Http\Service\Program;

use App\Http\Dao\Program\ProgramTaskCommentDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目任务评论
 * Class ProgramTaskCommentService.
 */
class ProgramTaskCommentService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(ProgramTaskCommentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = ['member', 'reply_member']): array
    {
        $list = $this->dao->getList($where, $field, 0, 0, $sort, $with);
        return [
            'list'        => get_tree_children($list),
            'total_count' => $this->dao->count(['task_id' => $where['task_id']]),
        ];
    }

    /**
     * 保存评论.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveComment(array $data): mixed
    {
        if (! $data['task_id']) {
            throw $this->exception('common.empty.attrs');
        }

        if (! app()->get(ProgramTaskService::class)->exists(['id' => $data['task_id']])) {
            throw $this->exception('任务数据异常');
        }

        if ($data['pid']) {
            $parent = $this->dao->get(['id' => $data['pid'], 'task_id' => $data['task_id']]);
            if (! $parent) {
                throw $this->exception('请重新选择上级评价');
            }
        }
        $data['uid'] = uuid_to_uid($this->uuId(false));

        return $this->transaction(function () use ($data) {
            $res = $this->dao->create($data);
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }
            return $res;
        });
    }

    /**
     * 修改评论.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateComment(array $data, int $id): mixed
    {
        $comment = $this->dao->get($id);
        if (! $comment) {
            throw $this->exception('common.operation.noExists');
        }

        if ($comment->uid != uuid_to_uid($this->uuId(false))) {
            throw $this->exception(__('common.operation.noPermission'));
        }
        return $this->transaction(function () use ($id, $data) {
            $res = $this->dao->update(['id' => $id], ['describe' => $data['describe']]);
            if (! $res) {
                throw $this->exception(__('common.update.fail'));
            }
            return $res;
        });
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteComment(int $id): mixed
    {
        $comment = $this->dao->get($id);
        if (! $comment) {
            throw $this->exception('common.operation.noExists');
        }

        if ($comment->uid != uuid_to_uid($this->uuId(false))) {
            throw $this->exception(__('common.operation.noPermission'));
        }

        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if ($this->dao->exists(['pid' => $id])) {
                $this->dao->delete(['pid' => $id]);
            }
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }
            return true;
        });
    }
}
