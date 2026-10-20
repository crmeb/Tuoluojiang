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

namespace App\Http\Service\News;

use App\Constants\CommonEnum;
use App\Constants\NoticeEnum;
use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use App\Http\Dao\Notice\NoticeDao;
use App\Http\Service\Frame\FrameService;
use App\Task\todo\TodoNoticeSourceSyncTask;
use App\Task\message\NewsRemind;
use App\Task\message\StatusChangeTask;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class NewsService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(NoticeDao $dao, protected $page = 0, protected $limit = 0)
    {
        $this->dao = $dao;
    }

    public function setLimit($page, $limit)
    {
        $this->page  = $page;
        $this->limit = $limit;
        return $this;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $sort = ['is_top', 'id'];
        if ($this->page && $this->limit) {
            [$page, $limit] = [$this->page, $this->limit];
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        if ($where['status']) {
            $where['is_push'] = now()->toDateTimeString();
            $sort             = ['is_top', 'push_time'];
        }
        if ($where['is_new']) {
            unset($where['cate_id']);
            $page  = 0;
            $limit = 8;
        }
        unset($where['is_new']);
        if (isset($where['is_read']) && $where['is_read'] !== '') {
            if ($where['is_read']) {
                $where['is_visit'] = auth('admin')->id();
            } else {
                $where['not_visit'] = auth('admin')->id();
            }
            unset($where['is_read']);
        }
        $list = $this->dao->getList($where, ['id', 'title', 'cover', 'user_id', 'is_top', 'info', 'status', 'visit', 'push_time', 'created_at'], $page, $limit, $sort, [
            'card' => function ($query) {
                $query->select(['id', 'name']);
            },
            'isVisit' => function ($query) {
                $query->where('user_id', auth('admin')->id())->select(['notice_id', 'user_id']);
            },
        ]);
        $list = array_map(function ($item) {
            $item['is_read'] = (bool) $item['is_visit'];
            return $item;
        }, $list);
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取企业动态未读量及数据.
     * @param mixed $uuid
     * @param mixed $entid
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserNoticeCount($uuid, $entid)
    {
        $userId    = app()->get(FrameService::class)->uuidToUid($uuid, $entid);
        $noticeIds = app()->get(NewsVisitService::class)->dao->setDefaultSort('created_at')->column(['user_id' => $userId], 'notice_id');
        $count     = $this->dao->count(['status' => 1, 'entid' => $entid, 'push_time' => now()->toDateTimeString(), 'not_id' => $noticeIds]);
        $field     = ['id', 'title', 'cover', 'card_id', 'is_top', 'info', 'status', 'visit', 'push_time', 'created_at'];
        if ($count) {
            $where = ['status' => 1, 'entid' => $entid, 'push_time' => now()->toDateTimeString(), 'not_id' => $noticeIds];
            if ($count >= $this->limit) {
                $list = $this->dao->getList(
                    $where,
                    $field,
                    $this->page,
                    $this->limit,
                    'push_time'
                );
                if (! empty($list)) {
                    foreach ($list as &$item) {
                        $item['is_read'] = 0;
                    }
                }
            } else {
                $list1 = $this->dao->getList($where, $field, $this->page, $count, 'push_time');
                if (! empty($list1)) {
                    foreach ($list1 as &$item1) {
                        $item1['is_read'] = 0;
                    }
                }
                $list2 = $this->dao->getList(
                    ['status' => 1, 'entid' => $entid, 'push_time' => now()->toDateTimeString(), 'id' => $noticeIds],
                    $field,
                    $this->page,
                    (int) bcsub((string) $this->limit, (string) $count)
                );
                if (! empty($list2)) {
                    foreach ($list2 as &$item2) {
                        $item2['is_read'] = 1;
                    }
                }
                $list = array_merge($list1, $list2);
            }
        } else {
            $list = $this->dao->getList(
                ['status' => 1, 'entid' => $entid, 'push_time' => now()->toDateTimeString()],
                $field,
                $this->page,
                $this->limit,
                'push_time'
            );
            if (! empty($list)) {
                foreach ($list as &$item) {
                    $item['is_read'] = 1;
                }
            }
        }
        return compact('count', 'list');
    }

    /**
     * @return BaseModel|Model
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resourceSave(array $data)
    {
        if (! $data['push_type']) {
            $data['push_time'] = now()->toDateTimeString();
        }
        $data['user_id'] = auth('admin')->id();
        $res             = $this->dao->create($data);
        // 发送提醒消息
        Task::deliver(new NewsRemind(1, (int) $res->id));
        $this->scheduleTodoSync((int) $res->id, (string) $res->push_time);
        return $res;
    }

    /**
     * 修改获取信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('修改的记录不存在');
        }
        return $info->toArray();
    }

    /**
     * 删除企业动态
     * @param mixed $id
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        Task::deliver(new StatusChangeTask([NoticeEnum::COMPANY_NEWS], CommonEnum::STATUS_DELETE, 1, $id));
        foreach ((array) $id as $noticeId) {
            BusinessDataChangeEvent::dispatch(TodoEnum::TYPE_NOTICE, (int) $noticeId, 'delete');
        }
        return $this->dao->delete($id, $key);
    }

    /**
     * 修改显示状态
     * @param mixed $id
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resourceShowUpdate($id, array $data)
    {
        Task::deliver(new StatusChangeTask([NoticeEnum::COMPANY_NEWS], $data['status'] ? CommonEnum::STATUS_NOMAL : CommonEnum::STATUS_DELETE, 1, $id));
        return $this->showUpdate($id, $data);
    }

    /**
     * 获取详情.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo($id)
    {
        $where['id']     = $id;
        $where['entid']  = 1;
        $where['status'] = 1;
        $info            = $this->dao->get($where, ['title', 'visit', 'push_time as time', 'content']);
        if (! $info) {
            throw $this->exception('未找到相关通知内容');
        }
        $userId = app()->get(FrameService::class)->uuidToUid($this->uuId(false), $where['entid']);
        if (app()->get(NewsVisitService::class)->saveVisit($id, $this->uuId(false), $where['entid'])) {
            $this->dao->inc($where, 1, 'visit');
            ++$info->visit;
        }
        BusinessDataChangeEvent::dispatch(TodoEnum::TYPE_NOTICE, (int) $id, 'update', [(int) $userId]);
        return $info->toArray();
    }

    /**
     * @param mixed $id
     * @param mixed $data
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, $data)
    {
        if (! $data['push_type']) {
            $data['push_time'] = now()->toDateTimeString();
        }
        $res = $this->dao->update($id, $data);
        $this->scheduleTodoSync((int) $id, (string) $data['push_time']);
        return $res;
    }

    private function scheduleTodoSync(int $id, string $pushTime): void
    {
        $delay = max(0, datetime_timestamp($pushTime) - time());
        if ($delay < 1) {
            return;
        }

        $task = new TodoNoticeSourceSyncTask($id);
        $task->delay($delay);
        Task::deliver($task);
    }

    /**
     * 日期分组列表.
     * @param string[] $field
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getGroupList(array $where, $field = ['*'])
    {
        $sort           = 'id';
        [$page, $limit] = $this->getPageValue();
        if ($where['status']) {
            $where['is_push'] = now()->toDateTimeString();
            $sort             = ['is_top', 'push_time'];
        }
        $userId    = app()->get(FrameService::class)->uuidToUid($this->uuId(false), 1);
        $noticeIds = app()->get(NewsVisitService::class)->dao->setDefaultSort('created_at')->column(['user_id' => $userId], 'notice_id');
        $list      = $this->dao->getGroupList($where, $field, $page, $limit, $sort, [], $noticeIds);
        $count     = $this->dao->count($where);

        return $this->listData($list, $count);
    }

    /**
     * 获取未读企业公共.
     * @param mixed $where
     * @param mixed $userId
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNotReadCount($where, $userId)
    {
        $noticeIds       = app()->get(NewsVisitService::class)->dao->setDefaultSort('created_at')->column(['user_id' => $userId], 'notice_id');
        $where['not_id'] = $noticeIds;
        return $this->dao->count($where);
    }

    /**
     * 获取全部选项列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNoticeList(array $where): array
    {
        if ($this->page && $this->limit) {
            [$page, $limit] = [$this->page, $this->limit];
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $where['is_push'] = now()->toDateTimeString();
        if (isset($where['cate_id'])) {
            unset($where['cate_id']);
        }
        $count            = $this->dao->count($where);
        $where['user_id'] = app()->get(FrameService::class)->uuidToUid($this->uuId(false));
        $field            = [
            'enterprise_notice.id',
            'enterprise_notice.title',
            'enterprise_notice.cover',
            'enterprise_notice.card_id',
            'enterprise_notice.is_top',
            'enterprise_notice.info',
            'enterprise_notice.status',
            'enterprise_notice.visit',
            'enterprise_notice.push_time',
            'enterprise_notice.created_at',
        ];
        $list = $this->dao->noticeList($where, $field, $page, $limit, ['is_top', 'push_time'], [
            'card' => function ($query) {
                $query->select(['id', 'name']);
            },
        ]);
        return $this->listData($list, $count);
    }

    /**
     * 反转置顶.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function reversalTop(int $id): void
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        $info->is_top = $info->is_top ? 0 : 1;
        $info->save();
    }
}
