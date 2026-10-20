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

namespace App\Http\Dao\Notice;

use App\Http\Model\Message\MessageNotice;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\utils\MessageType;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业消息
 * Class MessageNoticeDao.
 */
class MessageNoticeDao extends BaseDao
{
    use BatchSearchTrait;
    use ListSearchTrait;

    /**
     * 待处理的消息类型.
     * @var array|string[]
     */
    public array $pendingType = [
        MessageType::BUSINESS_APPROVAL_TYPE,
        MessageType::ASSESS_SELF_TYPE,
        MessageType::ASSESS_PUBLISH_TYPE,
    ];

    /**
     * 设置模型.
     * @return mixed|string
     */
    public function setModel()
    {
        return MessageNotice::class;
    }

    /**
     * 设置搜索条件.
     * @param array|string[] $field
     * @param null $sort
     * @return Builder
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setWhere(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        $entid    = $where['entid'];
        $uid      = $where['uid'];
        $to_uid   = $where['to_uid'];
        $title    = $where['title'] ?? '';
        $isRead   = $where['is_read'] ?? '';
        $isHandle = $where['is_handle'] ?? '';
        unset($where['entid'], $where['uid'], $where['is_read'], $where['to_uid'], $where['title'], $where['is_handle']);
        return $this->search($where)->select($field)->when(
            $entid,
            function ($query) use ($entid) {
                $query->where(function ($q) use ($entid) {
                    $q->where('entid', $entid)->orWhere('entid', 0);
                });
            },
            fn ($q) => $q->where('entid', 0)
        )
            ->when($title !== '', fn ($query) => $query->where(fn ($qq) => $qq->where('title', 'like', '%' . $title . '%')
                ->orWhere('message', 'like', '%' . $title . '%')))
            ->when($isRead !== '', fn ($query) => $query->where('is_read', $isRead))
            ->when(
                isset($where['template_type']) && $where['template_type'] !== '',
                function ($query) use ($where) {
                    if (is_array($where['template_type'])) {
                        $query->whereIn('template_type', $where['template_type']);
                    } else {
                        $query->where('template_type', $where['template_type']);
                    }
                }
            )
            ->when($isHandle !== '', function ($query) use ($isHandle) {
                $query->where('is_handle', $isHandle)->whereIn('template_type', $this->pendingType);
            })
            ->when($uid && $to_uid, function ($query) use ($uid, $to_uid) {
                $query->where(function ($q) use ($uid, $to_uid) {
                    $q->where('to_uid', $uid)->orWhere('to_uid', $to_uid);
                });
            })->when($uid && ! $to_uid, function ($query) use ($uid) {
                if (strlen((string) $uid) == 32) {
                    $query->where('to_uid', $uid);
                }
            })->when($limit && ! $page, function ($query) use ($limit) {
                $query->limit($limit);
            })
            ->when($page && $limit, fn ($query) => $query->forPage($page, $limit))
            ->when($sort, function ($query) use ($sort) {
                if (is_array($sort)) {
                    foreach ($sort as $k => $v) {
                        if (is_numeric($k)) {
                            $query->orderByDesc($v);
                        } else {
                            $query->orderBy($k, $v);
                        }
                    }
                } else {
                    $query->orderByDesc($sort);
                }
            })->with($with);
    }

    public function getPageList($where, array $field = [], $page = 0, $limit = 0)
    {
        return $this->search($where)->select($field)
            ->when($page && $limit, fn ($query) => $query->forPage($page, $limit))
            ->get();
    }

    /**
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getMessageGroupCount(int $entid, int $toUid)
    {
        $uuid = uid_to_uuid($toUid);
        return $this->getModel()->where('entid', $entid)->where('is_read', 0)
            ->where(function ($query) use ($uuid, $toUid) {
                $query->where('to_uid', $uuid)->orWhere('to_uid', $toUid);
            })
            ->groupBy('cate_id')->select(['cate_id', DB::raw('count(*) as count')])->get()->toArray();
    }
}
