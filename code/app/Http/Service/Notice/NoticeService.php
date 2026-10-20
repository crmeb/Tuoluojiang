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

namespace App\Http\Service\Notice;

use App\Http\Dao\Notice\NoticeDao;
use App\Http\Service\Frame\FrameService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class NoticeService extends BaseService
{
    use ResourceServiceTrait;

    /**
     * NoticeService constructor.
     * @param mixed $page
     * @param mixed $limit
     */
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
     * @return array|mixed
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $sort = 'id';
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
        $list = $this->dao->getList($where, ['id', 'title', 'cover', 'card_id', 'is_top', 'info', 'status', 'visit', 'push_time', 'created_at'], $page, $limit, $sort, [
            'card' => function ($query) {
                $query->select(['id', 'name']);
            },
        ]);
        $userId    = app()->get(FrameService::class)->uuidToUid($this->uuId(false), 1);
        $noticeIds = app()->get(NoticeVisitService::class)->dao->setDefaultSort('created_at')->column(['user_id' => $userId], 'notice_id');
        if (! empty($list)) {
            foreach ($list as &$item) {
                if (in_array($item['id'], $noticeIds)) {
                    $item['is_read'] = 1;
                } else {
                    $item['is_read'] = 0;
                }
            }
        }
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
        $noticeIds = app()->get(NoticeVisitService::class)->dao->setDefaultSort('created_at')->column(['user_id' => $userId], 'notice_id');
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
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if (! $data['push_type']) {
            $data['push_time'] = now()->toDateTimeString();
        }
        $data['card_id'] = app()->get(FrameService::class)->uuidToCardid($this->uuId(false), 1);
        return $this->dao->create($data);
    }

    /**
     * 修改获取信息.
     * @throws BindingResolutionException
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
     * 获取详情.
     * @param mixed $id
     * @return BaseModel|BuildsQueries|mixed|Model|object
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
        if (app()->get(NoticeVisitService::class)->saveVisit($id, $this->uuId(false), $where['entid'])) {
            $this->dao->inc($where, 1, 'visit');
            ++$info->visit;
        }
        return $info->toArray();
    }

    /**
     * @param mixed $id
     * @param mixed $data
     * @return int
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, $data)
    {
        if (! $data['push_type']) {
            $data['push_time'] = now()->toDateTimeString();
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 日期分组列表.
     * @param string[] $field
     * @return array
     * @throws BindingResolutionException
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
        $noticeIds = app()->get(NoticeVisitService::class)->dao->setDefaultSort('created_at')->column(['user_id' => $userId], 'notice_id');
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
        $noticeIds       = app()->get(NoticeVisitService::class)->column(['user_id' => $userId], 'notice_id');
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
        $where['user_id'] = app()->get(FrameService::class)->uuidToUid($this->uuId(false), 1);
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
        $list = $this->dao->noticeList($where, $field, $page, $limit, ['push_time'], [
            'card' => function ($query) {
                $query->select(['id', 'name']);
            },
        ]);
        return $this->listData($list, $count);
    }
}
