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

namespace App\Http\Service\WorkExternalContact;

use App\Http\Dao\WorkExternalContact\WorkChannelCodeGroupDao;
use App\Http\Model\WorkExternalContact\WorkChannelCode;
use App\Http\Model\WorkExternalContact\WorkChannelCodeGroup;
use App\Http\Service\Work\WorkGroupChatService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WorkChannelCodeGroupService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(WorkChannelCodeGroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $query = $this->dao->getModel()
            ->when($where['name_like'] ?? '', fn (Builder $query, $name) => $this->searchByName($query, (string) $name))
            ->when(($where['status'] ?? '') !== '', fn ($query) => $query->where('status', (int) $where['status']));

        $count = (clone $query)->count();
        $list = (clone $query)
            ->orderByDesc('id')
            ->when($page && $limit, fn ($query) => $query->forPage($page, $limit))
            ->get()
            ->toArray();

        return $this->listData($this->formatConfigList($list), $count);
    }

    private function searchByName(Builder $query, string $name): Builder
    {
        $chatIds = app()->get(WorkGroupChatService::class)->column(['name_like' => $name], 'chat_id');

        return $query->where(function (Builder $query) use ($name, $chatIds) {
            $query->where('group_name', 'like', '%' . $name . '%')
                ->when($chatIds, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query->chatId($chatIds)));
        });
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    public function resourceSave(array $data)
    {
        $group = $this->normalizeGroup($data);
        if (! $group['chat_ids']) {
            throw $this->exception('请至少选择一个可加入的群聊');
        }

        $config = $this->transaction(function () use ($group) {
            $config = $this->dao->create($this->formatConfigData($group));
            return $config;
        });
        $this->syncWorkJoinWay($config);
        return ['id' => (int) $config->id, 'code_id' => (int) $config->id];
    }

    /**
     * @param mixed $id
     */
    public function resourceUpdate($id, array $data)
    {
        $this->assertConfigExists((int) $id);
        if ((int) ($data['status'] ?? 1) === 0 && WorkChannelCode::query()->where('code_id', (int) $id)->exists()) {
            throw $this->exception('邀请加群配置已被渠道活码关联，不能停用');
        }
        $group = $this->normalizeGroup($data);
        if (! $group['chat_ids']) {
            throw $this->exception('请至少选择一个可加入的群聊');
        }
        $res = $this->transaction(fn () => $this->dao->update((int) $id, $this->formatConfigData($group)));
        $this->syncWorkJoinWay((int) $id);
        return $res;
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $data = $this->dao->get($id)?->toArray();
        if (! $data) {
            throw $this->exception('邀请加群配置不存在');
        }
        return $this->formatConfigList([$data])[0] ?? [];
    }

    /**
     * @param mixed $id
     * @return mixed
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $this->assertConfigExists((int) $id);
        if (WorkChannelCode::query()->where('code_id', (int) $id)->exists()) {
            throw $this->exception('邀请加群配置已被渠道活码关联，不能删除');
        }
        $this->deleteWorkJoinWay((int) $id);
        return $this->dao->delete((int) $id);
    }

    public function syncWorkJoinWay(WorkChannelCodeGroup|int $group): void
    {
        $group = is_int($group) ? $this->dao->get($group) : $group;
        if (! $group) {
            throw $this->exception('邀请加群配置不存在');
        }
        if ((int) $group->status !== 1) {
            $this->deleteWorkJoinWay($group);
            return;
        }

        $state = (string) $group->state;
        if (! $this->isValidGroupState($state)) {
            $state = $this->generateUniqueGroupState((int) $group->id);
        }

        $chatIds  = $this->normalizeChatIds($group->chat_ids ?: []);
        $roomName = $group->room_base_name ?: $group->group_name ?: '客户群';
        $remark   = $group->join_way_remark ?: $group->group_name;
        $work     = app()->get(Work::class);
        $response = $group->join_way_config_id
            ? $work->updateGroupJoinWay($group->join_way_config_id, $roomName, $chatIds, $state, (int) $group->auto_create_room, (int) $group->room_base_id, $remark, (int) ($group->scene ?: 2))
            : $work->createGroupJoinWay($roomName, $chatIds, $state, (int) $group->auto_create_room, (int) $group->room_base_id, $remark, (int) ($group->scene ?: 2));

        $result = $this->normalizeWechatResponse($response);
        if (($result['errcode'] ?? 0) !== 0) {
            throw $this->exception($result['errmsg'] ?? '创建客户群进群方式失败');
        }

        $configId = (string) ($result['config_id'] ?? $group->join_way_config_id ?? '');
        if (! $configId) {
            throw $this->exception('客户群进群方式配置ID不能为空');
        }
        if (! $this->responseQrCode($result)) {
            $joinWay = $this->normalizeWechatResponse($work->getGroupJoinWay($configId));
            if (($joinWay['errcode'] ?? 0) !== 0) {
                throw $this->exception($joinWay['errmsg'] ?? '获取客户群进群方式失败');
            }
            $result = array_merge($result, $joinWay);
        }
        $qrCode = $this->responseQrCode($result) ?: (string) ($group->qr_code ?? '');
        if (! $qrCode) {
            throw $this->exception('客户群进群二维码不能为空');
        }

        $this->dao->update((int) $group->id, [
            'join_way_config_id' => $configId,
            'qr_code'            => $qrCode,
            'state'              => $state,
        ]);
    }

    public function deleteWorkJoinWay(WorkChannelCodeGroup|int $group): void
    {
        $group = is_int($group) ? $this->dao->get($group) : $group;
        if (! $group || ! $group->join_way_config_id) {
            return;
        }

        $result = $this->normalizeWechatResponse(app()->get(Work::class)->deleteGroupJoinWay((string) $group->join_way_config_id));
        if (($result['errcode'] ?? 0) !== 0) {
            throw $this->exception($result['errmsg'] ?? '删除客户群进群方式失败');
        }

        $this->dao->update((int) $group->id, [
            'join_way_config_id' => '',
            'qr_code'            => '',
            'state'              => null,
        ]);
    }

    public function configForChannel(int $id): array
    {
        $config = $this->dao->get(['id' => $id, 'status' => 1]);
        if (! $config) {
            throw $this->exception('邀请加群配置不存在');
        }

        $data = $config->toArray();
        unset($data['id'], $data['join_way_config_id'], $data['qr_code'], $data['state'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
        return [$data];
    }

    public function idsByChatId(string $chatId): array
    {
        return $this->dao->getModel()->whereJsonContains('chat_ids', $chatId)->pluck('id')->all();
    }

    public function idByState(string $state): int
    {
        return (int) ($this->dao->get(['state' => $state], ['id'])?->id ?? 0);
    }

    public function stateExists(string $state, int $ignoreId = 0): bool
    {
        return $this->dao->getModel()
            ->where('state', $state)
            ->when($ignoreId > 0, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists();
    }

    private function assertConfigExists(int $id): void
    {
        if (! $this->dao->get($id, ['id'])) {
            throw $this->exception('邀请加群配置不存在');
        }
    }

    private function formatConfigData(array $group): array
    {
        return [
            'chat_ids'         => $group['chat_ids'],
            'group_name'       => (string) ($group['group_name'] ?? ''),
            'room_base_name'   => (string) ($group['room_base_name'] ?? ''),
            'room_base_id'     => (int) ($group['room_base_id'] ?? 1),
            'auto_create_room' => (int) ($group['auto_create_room'] ?? 1),
            'max_member_num'   => (int) ($group['max_member_num'] ?? 200),
            'sort'             => (int) ($group['sort'] ?? 0),
            'status'           => (int) ($group['status'] ?? 1),
            'scene'            => (int) ($group['scene'] ?? 2),
            'join_way_remark'  => (string) ($group['join_way_remark'] ?? ''),
        ];
    }

    private function generateUniqueGroupState(int $ignoreId = 0): string
    {
        do {
            $state  = WorkChannelCodeService::groupState();
            $exists = $this->stateExists($state, $ignoreId);
        } while ($exists);

        return $state;
    }

    private function isValidGroupState(string $state): bool
    {
        return str_starts_with($state, 'ccg-') && mb_strlen($state) <= 30;
    }

    private function normalizeWechatResponse($response): array
    {
        if (is_array($response)) {
            return $response;
        }
        if (is_object($response) && method_exists($response, 'toArray')) {
            return $response->toArray();
        }
        return [];
    }

    private function responseQrCode(array $response): string
    {
        return (string) ($response['qr_code'] ?? $response['join_way']['qr_code'] ?? '');
    }

    private function normalizeGroup(array $data): array
    {
        $chatIds      = $this->normalizeChatIds($data['chat_ids'] ?? []);
        $groupName    = (string) ($data['group_name'] ?? $data['room_base_name'] ?? $data['room_name'] ?? '');
        $roomBaseName = (string) ($data['room_base_name'] ?? $data['room_name'] ?? ($groupName ?: '客户群'));
        $roomBaseId   = (int) ($data['room_base_id'] ?? $data['room_base_start'] ?? $data['group_serial'] ?? 1);

        return [
            'chat_ids'         => $chatIds,
            'group_name'       => $groupName,
            'room_base_name'   => $roomBaseName,
            'room_base_id'     => max(1, $roomBaseId),
            'auto_create_room' => $this->normalizeBooleanInt($data['auto_create_room'] ?? 1),
            'max_member_num'   => min(200, max(1, (int) ($data['max_member_num'] ?? 200))),
            'sort'             => (int) ($data['sort'] ?? 0),
            'status'           => (int) ($data['status'] ?? 1),
            'scene'            => (int) ($data['scene'] ?? 2),
            'join_way_remark'  => (string) ($data['join_way_remark'] ?? ''),
        ];
    }

    private function normalizeBooleanInt($value): int
    {
        return in_array($value, [1, '1', true, 'true', 'on'], true) ? 1 : 0;
    }

    private function normalizeChatIds($chatIds): array
    {
        $ids = collect((array) $chatIds)
            ->map(fn ($chatId) => trim((string) $chatId))
            ->filter()
            ->values();
        if ($ids->duplicates()->isNotEmpty()) {
            throw $this->exception('邀请加入的群聊不能重复');
        }
        if ($ids->count() > 5) {
            throw $this->exception('邀请加入的群聊最多选择五个');
        }
        return $ids->all();
    }

    private function formatConfigList(array $list): array
    {
        $chatIds = [];
        foreach ($list as $item) {
            $chatIds = array_merge($chatIds, $this->normalizeChatIds($item['chat_ids'] ?? []));
        }
        $chatMap = $chatIds ? collect(app()->get(WorkGroupChatService::class)->select(
            ['chat_id' => array_values(array_unique($chatIds))],
            ['chat_id', 'name', 'owner', 'notice', 'member_num', 'group_create_time', 'retreat_group_num'],
            ['admin']
        )?->toArray() ?? [])->keyBy('chat_id')->all() : [];

        foreach ($list as &$item) {
            unset($item['code_id']);
            $item['name']            = (string) ($item['group_name'] ?? '');
            $item['room_name']       = (string) ($item['room_base_name'] ?? '');
            $item['room_base_start'] = (int) ($item['room_base_id'] ?? 1);
            $item['group_serial']    = (int) ($item['room_base_id'] ?? 1);
            $item['remark']          = (string) ($item['join_way_remark'] ?? '');
            $item['chat_ids']        = $this->normalizeChatIds($item['chat_ids'] ?? []);
            $item['group_chat']      = collect($item['chat_ids'])
                ->map(fn ($chatId) => $chatMap[$chatId] ?? ['chat_id' => $chatId, 'name' => $chatId])
                ->values()
                ->all();
        }
        unset($item);
        return array_values($list);
    }
}
