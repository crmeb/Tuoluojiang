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

namespace App\Http\Service\Schedule\Traits;

use App\Constants\AttachEnum;
use App\Http\Service\Attach\AttachService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 日程评价回复相关方法.
 */
trait ScheduleReplyTrait
{
    /**
     * 日程回复列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function replays(array $where): array
    {
        $field       = ['id', 'uid', 'pid', 'reply_id', 'to_uid', 'content', 'created_at'];
        $mainReplies = collect($this->replyDao->select(
            $where + ['reply_id' => 0],
            $field,
            with: [
                'from_user' => fn ($q) => $q->select(['id', 'name', 'avatar']),
                'files'     => fn ($query) => $query->select(['id', 'att_dir as src', 'att_size as size', 'relation_id', 'real_name as name']),
            ]
        ));
        if ($mainReplies->isEmpty()) {
            return [];
        }
        $mainReplyIds  = $mainReplies->pluck('id')->all();
        $allSubReplies = collect($this->replyDao->select(
            $where + ['reply_id' => $mainReplyIds],
            $field,
            with: [
                'from_user' => fn ($q) => $q->select(['id', 'name', 'avatar']),
                'to_user'   => fn ($q) => $q->select(['id', 'name', 'avatar']),
                'files'     => fn ($query) => $query->select(['id', 'att_dir as src', 'att_size as size', 'relation_id', 'real_name as name']),
            ]
        ));
        $mainReplies = $mainReplies->map(function ($mainReply) {
            // 将 files 转为集合，处理 src 链接
            $mainReply['files'] = collect($mainReply['files'] ?? [])->map(function ($file) {
                $file['src'] = $file['src'] ? link_file($file['src']) : '';
                return $file;
            })->all(); // 转回数组保持原结构
            return $mainReply;
        });
        $allSubReplies = $allSubReplies->map(function ($subReply) {
            $subReply['files'] = collect($subReply['files'] ?? [])->map(function ($file) {
                $file['src'] = $file['src'] ? link_file($file['src']) : '';
                return $file;
            })->all();
            return $subReply;
        });
        $subRepliesGrouped = $allSubReplies->groupBy('reply_id')->map->all();
        // 为主回复分配子回复
        return $mainReplies->map(function ($mainReply) use ($subRepliesGrouped) {
            $mainReply['children'] = $subRepliesGrouped->get($mainReply['id'], []);
            return $mainReply;
        })->all();
    }

    /**
     * 保存评价.
     * @throws BindingResolutionException
     */
    public function saveReply(string $uuid, int $entId, array $data)
    {
        $data['uid'] = uuid_to_uid($uuid, $entId);
        $fileId      = $data['files'];
        unset($data['files']);
        $saved = $this->replyDao->create($data);
        if ($saved && $fileId) {
            app()->get(AttachService::class)->saveRelation($fileId, $uuid, $saved->id, AttachEnum::RELATION_TYPE_SCHEDULE_REPLY);
        }
        return $saved;
    }

    /**
     * 删除日程评价.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function delReply(int $id, string $uuid, int $entId)
    {
        $uid = uuid_to_uid($uuid, $entId);
        if (! $this->replyDao->exists(['id' => $id, 'uid' => $uid])) {
            throw $this->exception('未找到可删除评价');
        }
        return $this->transaction(function () use ($id) {
            $this->replyDao->delete($id);
            $this->replyDao->delete(['reply_id' => $id]);
            app()->get(AttachService::class)->delRelationFiles($id, AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_SCHEDULE_REPLY]);
            return true;
        });
    }
}
