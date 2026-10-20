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

namespace App\Http\Service\Customer;

use App\Constants\CacheEnum;
use App\Http\Dao\Customer\LabelRelationDao;
use App\Http\Dao\Customer\LabelDao;
use App\Jobs\Customer\ReplaceCustomerLabelJob;
use App\Jobs\Work\WorkPlatformToWorkLabelJob;
use App\Jobs\Work\WorkWorkToPlatformLabelJob;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 标签.
 * @mixin LabelDao
 */
class LabelService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    private array $lastWorkTagResponse = [];

    public function __construct(LabelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function idByCacheValue(array $ids)
    {
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5(json_encode($ids)), (int) sys_config('system_cache_ttl', 3600), fn () => $this->dao->idByValue($ids));
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param string $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'name', 'sort', 'pid', 'group_type', 'work_group_id', 'work_tag_id'], $sort = 'sort', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        // 修复：缓存键包含所有影响查询结果的参数，避免不同排序/字段请求相互影响
        $cacheKey = md5(json_encode($where) . json_encode($field) . json_encode($sort) . json_encode($with) . $page . $limit);
        $data     = Cache::tags([CacheEnum::TAG_DICT])->remember($cacheKey, 360, function () use ($where, $field, $page, $limit, $sort, $with) {
            $list = $this->dao->getList($where, $field, $page, $limit, $sort, $with + [
                'children' => function ($query) use ($sort) {
                    // 修复：children 关联使用与父级相同的排序字段和方向
                    $query->when($sort, function ($q) use ($sort) {
                        if (is_array($sort)) {
                            foreach ($sort as $k => $v) {
                                if (is_numeric($k)) {
                                    $q->orderByDesc($v);
                                } else {
                                    $v && $q->orderBy($k, $v);
                                }
                            }
                        } else {
                            $q->orderByDesc($sort);
                        }
                    })->select(['id', 'pid', 'name', 'sort', 'group_type', 'work_group_id', 'work_tag_id']);
                },
            ]);
            $count = $this->dao->count($where);
            return json_encode($this->listData($list, $count), JSON_UNESCAPED_UNICODE);
        });
        return json_decode($data, true);
    }

    /**
     * 获取标签选项.
     * @param array $where
     * @param array $field
     * @param $sort
     * @param array $with
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getLabelOptions(array $where, array $field = ['id', 'name', 'sort', 'pid'], $sort = 'sort', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        // 修复：缓存键包含所有影响查询结果的参数，避免不同排序/字段请求相互影响
        $cacheKey = md5(json_encode($where) . json_encode($field) . json_encode($sort) . json_encode($with) . $page . $limit);
        $data = Cache::tags([CacheEnum::TAG_DICT])->remember($cacheKey, 360, function () use ($where, $field, $page, $limit, $sort, $with) {
            return collect($this->dao->getList($where, $field, $page, $limit, $sort, $with + [
                'children' => function ($query) use ($sort) {
                    // 修复：children 关联使用与父级相同的排序字段和方向
                    $query->when($sort, function ($q) use ($sort) {
                        if (is_array($sort)) {
                            foreach ($sort as $k => $v) {
                                if (is_numeric($k)) {
                                    $q->orderByDesc($v);
                                } else {
                                    $v && $q->orderBy($k, $v);
                                }
                            }
                        } else {
                            $q->orderByDesc($sort);
                        }
                    })->select(['id', 'pid', 'name', 'sort']);
                },
            ]))->filter(fn ($item) => $item['children'])->values()->toJson();
        });
        return json_decode($data, true);
    }

    /**
     * 保存数据.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceSave(array $data)
    {
        if (($data['group_type'] ?? '') === '') {
            $data['group_type'] = 0;
        }
        if (! $data['pid']) {
            $data['pid'] = 0;
            if ($this->dao->exists(['name' => $data['name'], 'entid' => $data['entid'], 'pid' => 0])) {
                throw $this->exception('已存在相同数据，请勿重复添加');
            }
        } else {
            if ($this->dao->exists(['name' => $data['name'], 'entid' => $data['entid'], 'not_pid' => 0])) {
                throw $this->exception('已存在相同数据，请勿重复添加');
            }
        }
        $res = $this->dao->create($data);

        if ($data['pid']) {
            $groupName = $this->dao->value($data['pid'], 'name');
            WorkPlatformToWorkLabelJob::dispatch((int) $data['pid'], $groupName);
        }

        $res && Cache::tags([CacheEnum::TAG_DICT])->flush();
        return $res;
    }

    /**
     * 修改数据.
     * @param int $id
     * @return true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        if (($data['group_type'] ?? '') === '') {
            unset($data['group_type']);
        }
        if (! $data['pid']) {
            $data['pid'] = 0;
        }
        $this->dao->update($id, $data) && Cache::tags([CacheEnum::TAG_DICT])->flush();

        if (sys_config('wechat_work_client_switch')) {
            $labelInfo = $this->dao->get($id);
            if ($labelInfo && $labelInfo->is_work && $labelInfo->work_tag_id) {
                $response = app(Work::class)->updateCorpTag($labelInfo->work_tag_id, $labelInfo->name, $labelInfo->sort);
                if (! $this->isWorkResponseSuccess($response)) {
                    Log::warning('平台更新客户标签：企业微信更新失败', [
                        'id'       => $id,
                        'tag_id'   => $labelInfo->work_tag_id,
                        'name'     => $labelInfo->name,
                        'errcode'  => $response['errcode'] ?? null,
                        'errmsg'   => $response['errmsg'] ?? null,
                        'response' => $response,
                    ]);
                    throw $this->exception($this->formatWorkTagErrorMessage($response, '更新'));
                }
            }
        }

        return true;
    }

    /**
     * 删除客户标签.
     * @param mixed $id
     * @param mixed $labelId 需要替换的标签id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDeleteLabel($id, int $labelId)
    {
        if ($this->dao->exists(['pid' => $id])) {
            throw $this->exception('请先删除标签组内的标签');
        }
        $traceId   = uniqid('work_label_delete_', true);
        $labelInfo = $this->dao->get($id);
        $childId   = $this->dao->column(['pid' => $id]) ?? [];
        $deleteWorkLabels = [];
        $deleteWorkGroups = [];
        if ($labelInfo) {
            if ($labelInfo->pid) {
                $parentInfo = $this->dao->get((int) $labelInfo->pid, ['id', 'name', 'work_group_id']);
                $deleteWorkLabels[] = [
                    'id'                   => (int) $labelInfo->id,
                    'pid'                  => (int) $labelInfo->pid,
                    'name'                 => (string) $labelInfo->name,
                    'work_tag_id'          => (string) $labelInfo->work_tag_id,
                    'parent_name'          => (string) ($parentInfo?->name ?: ''),
                    'parent_work_group_id' => (string) ($parentInfo?->work_group_id ?: ''),
                ];
            } else {
                $deleteWorkGroups[] = [
                    'id'            => (int) $labelInfo->id,
                    'name'          => (string) $labelInfo->name,
                    'work_group_id' => (string) $labelInfo->work_group_id,
                ];
            }
        }

        $workDeleteRes = true;
        if ($deleteWorkLabels) {
            $workDeleteRes = $this->deleteWorkTagsForDeletedLabels($deleteWorkLabels, $traceId, 'resource_delete_label');
        }
        if ($deleteWorkGroups) {
            $workDeleteRes = $this->deleteWorkGroupsForDeletedGroups($deleteWorkGroups, $traceId, 'resource_delete_group') && $workDeleteRes;
        }
        if (! $workDeleteRes) {
            Log::warning('平台删除客户标签：企业微信删除失败，已中断平台删除', [
                'trace_id' => $traceId,
                'id'       => $id,
                'labels'   => $deleteWorkLabels,
                'groups'   => $deleteWorkGroups,
            ]);
            throw $this->exception($this->formatWorkTagErrorMessage($this->lastWorkTagResponse, '删除'));
        }

        $res       = $this->transaction(function () use ($id, $childId) {
            if ($childId) {
                $this->dao->delete(['id' => $childId]);
            }
            return $this->dao->delete($id);
        });
        if ($labelId) {
            ReplaceCustomerLabelJob::dispatch($labelInfo ? $labelInfo->toArray() : [], $labelId);
        }
        return $res && Cache::tags([CacheEnum::TAG_DICT])->flush();
    }

    /**
     * 根据名称顺序获取id.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getIdsByName(array $name): array
    {
        $ids  = [];
        $list = $this->dao->column(['name_eq' => $name], 'id', 'name');
        foreach ($name as $tmpValue) {
            if (isset($list[$tmpValue])) {
                $ids[] = $list[$tmpValue];
            }
        }
        return $ids;
    }

    /**
     * 同步标签到企业微信.
     * @return true
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function authWorkClientLabel()
    {
        $traceId = uniqid('work_label_sync_', true);
        $startedAt = microtime(true);
        $page = (int) request()->input('page', 0);
        $limit = (int) request()->input('limit', 0);
        $totalGroupCount = $this->dao->count(['pid' => 0]);
        Log::warning('客户标签双向同步开始', [
            'trace_id'          => $traceId,
            'request_page'      => $page,
            'request_limit'     => $limit,
            'total_group_count' => $totalGroupCount,
        ]);

        $this->authWorkLabel([], [], $traceId);
        $workLabelRes = app(Work::class)->getCorpTags();
        $this->resetInvalidWorkLabelMappings($workLabelRes, $traceId);
        $data = $this->dao->getList(['pid' => 0], ['id', 'name']);
        Log::warning('客户标签双向同步：平台标签组队列准备完成', [
            'trace_id'         => $traceId,
            'request_page'     => $page,
            'request_limit'    => $limit,
            'total_group_count' => $totalGroupCount,
            'group_count'       => count($data),
            'groups'            => collect($data)->map(fn ($item) => [
                'id'   => $item['id'] ?? 0,
                'name' => $item['name'] ?? '',
            ])->values()->all(),
        ]);
        $duplicateGroups = collect($data)
            ->groupBy(fn ($item) => (string) ($item['name'] ?? ''))
            ->filter(fn ($items, $name) => $name !== '' && $items->count() > 1)
            ->map(fn ($items, $name) => [
                'name'   => $name,
                'count'  => $items->count(),
                'groups' => $items->map(fn ($item) => [
                    'id'   => $item['id'] ?? 0,
                    'name' => $item['name'] ?? '',
                ])->values()->all(),
            ])->values()->all();
        if ($duplicateGroups) {
            Log::warning('客户标签双向同步：平台存在重名标签组，企业微信同名创建可能返回40071', [
                'trace_id'         => $traceId,
                'duplicate_groups' => $duplicateGroups,
            ]);
        }
        if (count($data) < $totalGroupCount) {
            Log::warning('客户标签双向同步：平台标签组派发数量小于本地总数，可能受请求分页参数影响', [
                'trace_id'          => $traceId,
                'request_page'      => $page,
                'request_limit'     => $limit,
                'total_group_count' => $totalGroupCount,
                'dispatch_count'    => count($data),
            ]);
        }
        $platformJobInterval = 2;
        $workJobDelay        = count($data) * $platformJobInterval + 90;
        if ($data) {
            foreach ($data as $index => $item) {
                $delaySeconds = $index * $platformJobInterval;
                WorkPlatformToWorkLabelJob::dispatch($item['id'], $item['name'], $traceId)
                    ->delay(now()->addSeconds($delaySeconds));
            }
        }
        WorkWorkToPlatformLabelJob::dispatch($traceId)->delay(now()->addSeconds($workJobDelay));
        Cache::tags([CacheEnum::TAG_DICT])->flush();
        Log::warning('客户标签双向同步已派发队列', [
            'trace_id'          => $traceId,
            'platform_jobs'     => count($data),
            'platform_job_interval' => $platformJobInterval,
            'work_job_delay'    => $workJobDelay,
            'total_group_count' => $totalGroupCount,
            'request_page'      => $page,
            'request_limit'     => $limit,
            'cost_ms'           => (int) ((microtime(true) - $startedAt) * 1000),
        ]);
        return true;
    }

    /**
     * 以平台标签为准，清理企业微信已不存在的本地映射，后续队列会重新创建。
     */
    private function resetInvalidWorkLabelMappings(array $workLabelRes, string $traceId): void
    {
        if (($workLabelRes['errcode'] ?? null) !== 0) {
            Log::warning('客户标签双向同步：跳过失效企微映射重置，企业微信接口返回异常', [
                'trace_id' => $traceId,
                'errcode'  => $workLabelRes['errcode'] ?? null,
                'errmsg'   => $workLabelRes['errmsg'] ?? null,
                'response' => $workLabelRes,
            ]);
            return;
        }

        $tagGroups      = $workLabelRes['tag_group'] ?? [];
        $remoteGroupIds = collect($tagGroups)->pluck('group_id')->filter()->values()->all();
        $remoteTagIds   = collect($tagGroups)->flatMap(fn ($group) => collect($group['tag'] ?? [])->pluck('id'))->filter()->values()->all();

        $invalidGroups  = $this->dao->getInvalidWorkGroups($remoteGroupIds);
        $invalidGroupIds = $invalidGroups->pluck('id')->values()->all();

        $groupChildLabels = collect();
        if ($invalidGroupIds) {
            $groupChildLabels = $this->dao->getWorkChildrenByPids($invalidGroupIds);
        }

        $invalidTags = $this->dao->getInvalidWorkTags($remoteGroupIds, $remoteTagIds, $invalidGroupIds);

        $invalidTagIds = $invalidTags->pluck('id')->values()->all();

        if ($invalidGroupIds || $invalidTagIds) {
            $this->transaction(function () use ($invalidGroupIds, $invalidTagIds) {
                if ($invalidGroupIds) {
                    $this->dao->updateByIds($invalidGroupIds, [
                        'is_work'       => 0,
                        'work_group_id' => '',
                    ]);
                    $groupChildIds = $this->dao->getLabelsByPids($invalidGroupIds, ['id'])->pluck('id')->all();
                    $this->dao->updateByIds($groupChildIds, [
                        'is_work'     => 0,
                        'work_tag_id' => '',
                    ]);
                }

                if ($invalidTagIds) {
                    $this->dao->updateByIds($invalidTagIds, [
                        'is_work'     => 0,
                        'work_tag_id' => '',
                    ]);
                }
            });
        }

        Log::warning('客户标签双向同步：失效企微映射重置完成', [
            'trace_id'           => $traceId,
            'remote_group_count' => count($remoteGroupIds),
            'remote_tag_count'   => count($remoteTagIds),
            'reset_group_count'  => count($invalidGroupIds),
            'reset_tag_count'    => $groupChildLabels->count() + count($invalidTagIds),
            'reset_groups'       => $invalidGroups->map(fn ($item) => [
                'id'            => $item->id,
                'name'          => $item->name,
                'work_group_id' => $item->work_group_id,
            ])->values()->all(),
            'reset_tags' => $groupChildLabels->map(fn ($item) => [
                'id'          => $item->id,
                'pid'         => $item->pid,
                'name'        => $item->name,
                'work_tag_id' => $item->work_tag_id,
                'reason'      => 'group_missing',
            ])->merge($invalidTags->map(fn ($item) => [
                'id'                   => $item->id,
                'pid'                  => $item->pid,
                'name'                 => $item->name,
                'work_tag_id'          => $item->work_tag_id,
                'parent_name'          => $item->parent?->name,
                'parent_work_group_id' => $item->parent?->work_group_id,
                'reason'               => 'tag_missing',
            ]))->values()->all(),
        ]);
    }

    /**
     * 查找同名且已同步到企业微信的父级标签组，平台重名组共用一个企微标签组。
     */
    private function getSameNameWorkGroup(int $pid, string $groupName): array
    {
        if ($groupName === '') {
            return [];
        }

        return $this->dao->getSameNameSyncedWorkGroup($pid, $groupName) ?: [];
    }

    /**
     * 拉取企微标签组；没有 group_id 时按组名兜底查找。
     */
    private function getWorkTagGroupForSync(string $workGroupId, string $groupName, string $traceId, string $reason): array
    {
        $res       = app(Work::class)->getCorpTags([], $workGroupId ? [$workGroupId] : []);
        $tagGroups = $res['tag_group'] ?? [];
        $tagGroup  = collect($tagGroups)->first(function ($item) use ($workGroupId, $groupName) {
            if ($workGroupId) {
                return ($item['group_id'] ?? '') === $workGroupId;
            }
            return ($item['group_name'] ?? '') === $groupName;
        }) ?: [];

        Log::warning('客户标签同步到企业微信：拉取企微标签组用于共享映射', [
            'trace_id'      => $traceId,
            'reason'        => $reason,
            'group_name'    => $groupName,
            'work_group_id' => $workGroupId,
            'errcode'       => $res['errcode'] ?? null,
            'errmsg'        => $res['errmsg'] ?? null,
            'found'         => ! empty($tagGroup),
            'remote_group_id' => $tagGroup['group_id'] ?? '',
            'remote_tag_count' => count($tagGroup['tag'] ?? []),
        ]);

        return $tagGroup;
    }

    /**
     * 用企微标签组返回值更新本地父级和子标签映射。
     */
    private function updateLocalWorkLabelMappings(int $pid, string $groupName, array $localLabels, array $tagGroup, string $traceId, string $reason): array
    {
        $workGroupId = (string) ($tagGroup['group_id'] ?? '');
        if ($workGroupId === '') {
            return [
                'matched_count'    => 0,
                'unmatched_list'   => $localLabels,
                'unmatched_labels' => collect($localLabels)->map(fn ($item) => [
                    'id'   => $item['id'] ?? 0,
                    'name' => $item['name'] ?? '',
                ])->values()->all(),
            ];
        }

        $this->dao->update($pid, ['work_group_id' => $workGroupId, 'is_work' => 1]);
        $remoteTagsByName = collect($tagGroup['tag'] ?? [])
            ->filter(fn ($item) => ($item['id'] ?? '') !== '' && ($item['name'] ?? '') !== '')
            ->keyBy(fn ($item) => (string) $item['name']);

        $matchedLabelIds = [];
        foreach ($localLabels as $value) {
            $labelName = (string) ($value['name'] ?? '');
            $remoteTag = $remoteTagsByName->get($labelName);
            if (! $remoteTag) {
                continue;
            }
            $labelId = (int) ($value['id'] ?? 0);
            $this->dao->update($labelId, ['work_tag_id' => $remoteTag['id'], 'is_work' => 1]);
            $matchedLabelIds[] = $labelId;
        }

        $matchedLabelIds = array_values(array_unique($matchedLabelIds));
        $unmatchedList   = collect($localLabels)
            ->reject(fn ($item) => in_array((int) ($item['id'] ?? 0), $matchedLabelIds, true))
            ->values()
            ->all();
        $unmatchedLabels = collect($unmatchedList)->map(fn ($item) => [
            'id'   => $item['id'] ?? 0,
            'name' => $item['name'] ?? '',
        ])->values()->all();

        Log::warning('客户标签同步到企业微信：本地映射更新完成', [
            'trace_id'           => $traceId,
            'reason'             => $reason,
            'pid'                => $pid,
            'group_name'         => $groupName,
            'work_group_id'      => $workGroupId,
            'response_tag_count' => count($tagGroup['tag'] ?? []),
            'matched_count'      => count($matchedLabelIds),
            'unmatched_labels'   => $unmatchedLabels,
        ]);

        return [
            'matched_count'    => count($matchedLabelIds),
            'unmatched_list'   => $unmatchedList,
            'unmatched_labels' => $unmatchedLabels,
        ];
    }

    /**
     * 同步平台标签到企业微信客户.
     * @return bool
     */
    public function addCorpClientLabel(int $pid, string $groupName, ?string $traceId = null, ?array &$syncResult = null)
    {
        try {
            $traceId = $traceId ?: uniqid('work_label_sync_', true);
            $syncResult = [
                'success'   => false,
                'retryable' => false,
                'errcode'   => null,
                'errmsg'    => null,
            ];
            $groupInfo = $this->dao->get($pid, ['id', 'name', 'sort', 'work_group_id', 'is_work']);
            $list = $this->dao->getList(where: ['is_work' => 0, 'pid' => $pid], field: ['name', 'id']);
            Log::warning('客户标签同步到企业微信：开始处理标签组', [
                'trace_id'       => $traceId,
                'pid'            => $pid,
                'group_name'     => $groupName,
                'group_info'     => $groupInfo ? $groupInfo->toArray() : null,
                'pending_count'  => count($list),
                'pending_labels' => collect($list)->map(fn ($item) => [
                    'id'   => $item['id'] ?? 0,
                    'name' => $item['name'] ?? '',
                ])->values()->all(),
            ]);
            $workGroupId       = $groupInfo?->work_group_id ?: '';
            if ($workGroupId) {
                $updateRes = app(Work::class)->updateCorpTag($workGroupId, $groupName, (int) ($groupInfo?->sort ?: 1));
                if (! $this->isWorkResponseSuccess($updateRes)) {
                    $syncResult = [
                        'success'   => false,
                        'retryable' => (int) ($updateRes['errcode'] ?? 0) === 6000 || $updateRes === [],
                        'errcode'   => $updateRes['errcode'] ?? null,
                        'errmsg'    => $updateRes['errmsg'] ?? null,
                    ];
                    Log::warning('客户标签同步到企业微信：标签组更新失败', [
                        'trace_id'      => $traceId,
                        'pid'           => $pid,
                        'group_name'    => $groupName,
                        'work_group_id' => $workGroupId,
                        'response'      => $updateRes,
                    ]);
                    return false;
                }
            }
            if (! $list) {
                Log::warning('客户标签同步到企业微信：无待同步标签', [
                    'trace_id'   => $traceId,
                    'pid'        => $pid,
                    'group_name' => $groupName,
                ]);
                $syncResult['success'] = true;
                return true;
            }
            $data = [];
            foreach ($list as $item) {
                $data[] = ['name' => $item['name']];
            }

            $sharedGroup       = [];
            $remoteMappingInfo = null;
            if (! $workGroupId) {
                $sharedGroup = $this->getSameNameWorkGroup($pid, $groupName);
                if ($sharedGroup) {
                    $workGroupId = (string) ($sharedGroup['work_group_id'] ?? '');
                    Log::warning('客户标签同步到企业微信：复用平台同名标签组的企微group_id', [
                        'trace_id'            => $traceId,
                        'pid'                 => $pid,
                        'group_name'          => $groupName,
                        'shared_platform_group' => $sharedGroup,
                        'work_group_id'       => $workGroupId,
                    ]);

                    $remoteTagGroup = $this->getWorkTagGroupForSync($workGroupId, $groupName, $traceId, 'same_name_group_precheck');
                    $remoteMappingInfo = $this->updateLocalWorkLabelMappings($pid, $groupName, $list, $remoteTagGroup, $traceId, 'same_name_group_precheck');
                    $list = $remoteMappingInfo['unmatched_list'];
                    $data = collect($list)->map(fn ($item) => ['name' => $item['name']])->values()->all();
                    if (! $data) {
                        $syncResult['success'] = true;
                        return true;
                    }
                }
            }

            $res = app(Work::class)->addCorpTag($groupName, $data, $workGroupId);
            $errcode = $res['errcode'] ?? null;
            $errmsg  = $res['errmsg'] ?? null;
            $syncResult = [
                'success'   => ($errcode ?? 0) === 0 && ! empty($res['tag_group']['group_id']),
                'retryable' => (int) $errcode === 6000 || $res === [],
                'errcode'   => $errcode,
                'errmsg'    => $errmsg,
            ];
            Log::warning('客户标签同步到企业微信：接口返回', [
                'trace_id'   => $traceId,
                'pid'        => $pid,
                'group_name' => $groupName,
                'work_group_id' => $workGroupId,
                'shared_platform_group' => $sharedGroup,
                'errcode'    => $errcode,
                'errmsg'     => $errmsg,
                'response'   => $res,
            ]);
            if (($errcode ?? 0) !== 0 || empty($res['tag_group']['group_id'])) {
                Log::warning('客户标签同步到企业微信：接口未返回有效标签组', [
                    'trace_id'       => $traceId,
                    'pid'            => $pid,
                    'group_name'     => $groupName,
                    'work_group_id'  => $workGroupId,
                    'pending_labels' => $data,
                    'response'       => $res,
                ]);
                if ((int) $errcode === 40071) {
                    $remoteTagGroup = $this->getWorkTagGroupForSync($workGroupId, $groupName, $traceId, '40071_existing_name');
                    if ($remoteTagGroup) {
                        $remoteMappingInfo = $this->updateLocalWorkLabelMappings($pid, $groupName, $list, $remoteTagGroup, $traceId, '40071_existing_name');
                        if (($remoteMappingInfo['matched_count'] ?? 0) > 0 && empty($remoteMappingInfo['unmatched_list'])) {
                            $syncResult = [
                                'success'   => true,
                                'retryable' => false,
                                'errcode'   => $errcode,
                                'errmsg'    => $errmsg,
                            ];
                            return true;
                        }
                    }
                    $sameNameGroups = $this->dao->getSameNameGroups($groupName);
                    Log::warning('客户标签同步到企业微信：企微存在同名标签组或标签，需人工确认平台重名标签组处理方式', [
                        'trace_id'       => $traceId,
                        'pid'            => $pid,
                        'group_name'     => $groupName,
                        'work_group_id'  => $workGroupId,
                        'pending_labels' => $data,
                        'same_name_platform_groups' => $sameNameGroups,
                        'response'       => $res,
                    ]);
                }
                return false;
            }

            $this->updateLocalWorkLabelMappings($pid, $groupName, $list, $res['tag_group'], $traceId, $sharedGroup ? 'same_name_group_append' : 'append_or_create');

            $syncResult['success'] = true;
            return true;
        } catch (\Throwable $e) {
            $syncResult = [
                'success'   => false,
                'retryable' => true,
                'errcode'   => null,
                'errmsg'    => $e->getMessage(),
            ];
            Log::error('客户标签同步到企业微信失败：' . $e->getMessage(), [
                'trace_id'   => $traceId ?? '',
                'pid'        => $pid,
                'group_name' => $groupName,
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);
            return false;
        }
    }

    /**
     * 客户标签同步到平台.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function authWorkLabel(array $tagIds = [], array $group = [], ?string $traceId = null)
    {
        $traceId = $traceId ?: uniqid('work_label_sync_', true);
        Log::warning('企业微信标签同步到平台：开始拉取', [
            'trace_id'  => $traceId,
            'tag_ids'   => $tagIds,
            'group_ids' => $group,
        ]);
        $res       = app(Work::class)->getCorpTags($tagIds, $group);
        $tagGroup  = $res['tag_group'] ?? [];
        $cateData  = [];
        $labelData = [];
        $groupIds  = [];
        $syncStats = [
            'group_update_count'   => 0,
            'group_create_count'   => 0,
            'label_update_count'   => 0,
            'label_create_count'   => 0,
            'missing_parent_groups' => [],
        ];

        Log::warning('企业微信标签同步到平台：接口返回', [
            'trace_id'    => $traceId,
            'errcode'     => $res['errcode'] ?? null,
            'errmsg'      => $res['errmsg'] ?? null,
            'group_count' => count($tagGroup),
            'groups'      => collect($tagGroup)->map(fn ($item) => [
                'group_id'   => $item['group_id'] ?? '',
                'group_name' => $item['group_name'] ?? '',
                'tag_count'  => count($item['tag'] ?? []),
            ])->values()->all(),
        ]);
        if (($res['errcode'] ?? 0) !== 0) {
            Log::warning('企业微信标签同步到平台：接口返回非成功状态', [
                'trace_id' => $traceId,
                'response' => $res,
            ]);
        }

        $this->transaction(function () use ($tagGroup, &$cateData, &$labelData, &$groupIds, &$syncStats) {
            foreach ($tagGroup as $item) {
                $groupIdsByWorkId = $this->dao->getSyncedGroupIdsByWorkGroupId($item['group_id']);
                if ($groupIdsByWorkId) {
                    $this->dao->updateByIds($groupIdsByWorkId, [
                        'name'          => $item['group_name'],
                        'work_group_id' => $item['group_id'],
                        'sort'          => $item['order'],
                    ]);
                    $syncStats['group_update_count'] += count($groupIdsByWorkId);
                } else {
                    $cateData[] = [
                        'name'          => $item['group_name'],
                        'sort'          => $item['order'],
                        'created_at'    => date('Y-m-d H:i:s', $item['create_time']),
                        'work_group_id' => $item['group_id'],
                        'is_work'       => 1,
                    ];
                    ++$syncStats['group_create_count'];
                }
                $groupIds[] = $item['group_id'];
                foreach ($item['tag'] ?? [] as $tag) {
                    $labelIds = $this->dao->getIdsByWorkTagId($tag['id']);
                    if ($labelIds) {
                        $this->dao->updateByIds($labelIds, [
                            'name'        => $tag['name'],
                            'sort'        => $tag['order'] ?? 0,
                            'work_tag_id' => $tag['id'],
                            'is_work'     => 1,
                        ]);
                        $syncStats['label_update_count'] += count($labelIds);
                    } else {
                        $labelData[$item['group_id']][] = [
                            'name'        => $tag['name'],
                            'is_work'     => 1,
                            'created_at'  => date('Y-m-d H:i:s', $tag['create_time']),
                            'work_tag_id' => $tag['id'],
                        ];
                        ++$syncStats['label_create_count'];
                    }
                }
            }
            if ($cateData) {
                $this->dao->insert($cateData);
            }
            $cateIds = $this->dao->column([
                'work_group_id' => $groupIds,
                'is_work'       => 1,
            ], 'id', 'work_group_id');
            if ($labelData) {
                $saveData = [];
                foreach ($labelData as $groupId => $labels) {
                    $cateId = $cateIds[$groupId] ?? 0;
                    if (! $cateId) {
                        $syncStats['missing_parent_groups'][] = $groupId;
                        continue;
                    }
                    foreach ($labels as $label) {
                        $label['pid'] = $cateId;
                        $saveData[]   = $label;
                    }
                }
                if ($saveData) {
                    $this->dao->insert($saveData);
                }
            }
        });

        if ($syncStats['missing_parent_groups']) {
            Log::warning('企业微信标签同步到平台：存在未找到本地父级的标签组', [
                'trace_id'              => $traceId,
                'missing_parent_groups' => $syncStats['missing_parent_groups'],
            ]);
        }
        Log::warning('企业微信标签同步到平台：写入完成', [
            'trace_id' => $traceId,
            'stats'    => $syncStats,
        ]);

        return true;
    }

    /**
     * 企业微信创建客户标签事件.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function createUserLabel(string $corpId, string $strId, string $type)
    {
        $traceId = uniqid('work_label_callback_', true);
        Log::warning('企业微信创建客户标签回调：开始同步到平台', [
            'trace_id' => $traceId,
            'corp_id'  => $corpId,
            'id'       => $strId,
            'type'     => $type,
        ]);
        return $this->authWorkLabel($type === 'tag' ? [$strId] : [], $type === 'tag_group' ? [$strId] : [], $traceId);
    }

    /**
     * 企业微信更新客户标签事件.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateUserLabel(string $corpId, string $strId, string $type)
    {
        $traceId = uniqid('work_label_callback_', true);
        Log::warning('企业微信更新客户标签回调：开始同步到平台', [
            'trace_id' => $traceId,
            'corp_id'  => $corpId,
            'id'       => $strId,
            'type'     => $type,
        ]);
        return $this->authWorkLabel($type === 'tag' ? [$strId] : [], $type === 'tag_group' ? [$strId] : [], $traceId);
    }

    /**
     * 删除客户标签事件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteUserLabel(string $corpId, string $strId, string $type)
    {
        $traceId = uniqid('work_label_callback_', true);
        Log::warning('企业微信删除客户标签回调：开始删除平台标签', [
            'trace_id' => $traceId,
            'corp_id'  => $corpId,
            'id'       => $strId,
            'type'     => $type,
        ]);

        $deleteLabelCount    = 0;
        $deleteGroupCount    = 0;
        $deleteRelationCount = 0;
        $deleteGroups        = [];
        $deleteLabels        = [];

        if ($type === 'tag') {
            $labels = $this->dao->getLabelsByWorkTagId($strId);
            $labelIds = $labels->pluck('id')->values()->all();
            $deleteLabels = $labels->map(fn ($item) => [
                'id'          => $item->id,
                'pid'         => $item->pid,
                'name'        => $item->name,
                'work_tag_id' => $item->work_tag_id,
            ])->values()->all();
            $deleteLabelCount = count($labelIds);
            if ($labelIds) {
                $clientLabelsDao      = app(LabelRelationDao::class);
                $deleteRelationCount = $clientLabelsDao->countByLabelIds($labelIds);
                $this->transaction(function () use ($labelIds, $clientLabelsDao) {
                    $clientLabelsDao->deleteByLabelIds($labelIds);
                    $this->dao->deleteByIds($labelIds);
                });
            }
        } elseif ($type === 'tag_group') {
            $groups = $this->dao->getGroupsByWorkGroupId($strId);
            $groupIds = $groups->pluck('id')->values()->all();
            $deleteGroups = $groups->map(fn ($item) => [
                'id'            => $item->id,
                'name'          => $item->name,
                'work_group_id' => $item->work_group_id,
            ])->values()->all();
            $deleteGroupCount = count($groupIds);
            if ($groupIds) {
                $labels = $this->dao->getLabelsByPids($groupIds);
                $labelIds = $labels->pluck('id')->values()->all();
                $deleteLabels = $labels->map(fn ($item) => [
                    'id'          => $item->id,
                    'pid'         => $item->pid,
                    'name'        => $item->name,
                    'work_tag_id' => $item->work_tag_id,
                ])->values()->all();
                $deleteLabelCount = count($labelIds);
                $clientLabelsDao      = app(LabelRelationDao::class);
                $deleteRelationCount = $clientLabelsDao->countByLabelIds($labelIds);

                $this->transaction(function () use ($groupIds, $labelIds, $clientLabelsDao) {
                    if ($labelIds) {
                        $clientLabelsDao->deleteByLabelIds($labelIds);
                        $this->dao->deleteByIds($labelIds);
                    }
                    $this->dao->deleteByIds($groupIds);
                });
            }
        }

        if ($deleteGroupCount || $deleteLabelCount || $deleteRelationCount) {
            Cache::tags([CacheEnum::TAG_DICT])->flush();
        }

        Log::warning('企业微信删除客户标签回调：平台标签删除完成', [
            'trace_id'              => $traceId,
            'corp_id'               => $corpId,
            'id'                    => $strId,
            'type'                  => $type,
            'delete_group_count'    => $deleteGroupCount,
            'delete_label_count'    => $deleteLabelCount,
            'delete_relation_count' => $deleteRelationCount,
            'delete_groups'         => $deleteGroups,
            'delete_labels'         => $deleteLabels,
        ]);
    }

    private function deleteWorkTagsForDeletedLabels(array $labels, string $traceId, string $source): bool
    {
        if (! sys_config('wechat_work_client_switch')) {
            return true;
        }

        $tagIds = collect($labels)
            ->pluck('work_tag_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $tagIds) {
            Log::warning('平台删除客户标签：跳过删除企业微信标签，缺少work_tag_id', [
                'trace_id' => $traceId,
                'source'   => $source,
                'labels'   => $labels,
            ]);
            return true;
        }

        $response = app(Work::class)->deleteCorpTag($tagIds);
        Log::warning('平台删除客户标签：删除企业微信标签完成', [
            'trace_id' => $traceId,
            'source'   => $source,
            'tag_ids'  => $tagIds,
            'labels'   => $labels,
            'errcode'  => $response['errcode'] ?? null,
            'errmsg'   => $response['errmsg'] ?? null,
            'response' => $response,
        ]);

        $this->lastWorkTagResponse = $response;
        return $this->isWorkResponseSuccess($response);
    }

    private function isWorkResponseSuccess(array $response): bool
    {
        return isset($response['errcode']) && (int) $response['errcode'] === 0;
    }

    private function formatWorkTagErrorMessage(array $response, string $action): string
    {
        $errcode = (int) ($response['errcode'] ?? 0);
        $errmsg  = (string) ($response['errmsg'] ?? '企业微信接口未返回错误信息');

        if ($errcode === 81011) {
            return "企业微信客户标签{$action}失败：当前应用无权限访问或修改该标签，可能该标签由其他应用创建/管理。请到企业微信后台使用对应应用处理，或确认当前应用具备该标签的管理权限。错误码：{$errcode}";
        }

        return "企业微信客户标签{$action}失败：{$errmsg}（错误码：{$errcode}）";
    }

    private function deleteWorkGroupsForDeletedGroups(array $groups, string $traceId, string $source): bool
    {
        if (! sys_config('wechat_work_client_switch')) {
            return true;
        }

        $groupIds = collect($groups)
            ->pluck('work_group_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $groupIds) {
            Log::warning('平台删除客户标签组：跳过删除企业微信标签组，缺少work_group_id', [
                'trace_id' => $traceId,
                'source'   => $source,
                'groups'   => $groups,
            ]);
            return true;
        }

        $response = app(Work::class)->deleteCorpTag([], $groupIds);
        Log::warning('平台删除客户标签组：删除企业微信标签组完成', [
            'trace_id'  => $traceId,
            'source'    => $source,
            'group_ids' => $groupIds,
            'groups'    => $groups,
            'errcode'   => $response['errcode'] ?? null,
            'errmsg'    => $response['errmsg'] ?? null,
            'response'  => $response,
        ]);

        $this->lastWorkTagResponse = $response;
        return $this->isWorkResponseSuccess($response);
    }

    /**
     * 同步企业微信标签顺序(重排事件).
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function syncTagOrder(): bool
    {
        try {
            $res       = app(Work::class)->getCorpTags();
            $tagGroups = $res['tag_group'] ?? [];

            foreach ($tagGroups as $group) {
                // 更新标签组的排序
                if (isset($group['order'])) {
                    $this->dao->update(['work_group_id' => $group['group_id']], ['sort' => $group['order']]);
                }

                // 更新标签的排序
                foreach ($group['tag'] ?? [] as $tag) {
                    if (isset($tag['order'])) {
                        $this->dao->update(['work_tag_id' => $tag['id']], ['sort' => $tag['order']]);
                    }
                }
            }

            Cache::tags([CacheEnum::TAG_DICT])->flush();
            return true;
        } catch (\Throwable $e) {
            Log::error('同步企业微信标签顺序失败：' . $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
            return false;
        }
    }

    /**
     * 保存标签.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveLabels(array $group, array $label): bool
    {
        $traceId      = uniqid('work_label_save_', true);
        $groupCollect = collect($group ?: []);
        $groupId      = (int) $groupCollect->get('id', 0);
        $deleteLabels = [];
        if ($groupId) {
            $oldLabels = $this->dao->getLabelsByPids([$groupId]);
            $newTagIds = collect($label ?: [])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $deleteLabels = $oldLabels
                ->filter(fn ($item) => ! in_array((int) $item->id, $newTagIds, true))
                ->map(fn ($item) => [
                    'id'          => (int) $item->id,
                    'pid'         => (int) $item->pid,
                    'name'        => (string) $item->name,
                    'work_tag_id' => (string) $item->work_tag_id,
                ])->values()->all();
        }

        $workDeleteRes = $deleteLabels ? $this->deleteWorkTagsForDeletedLabels($deleteLabels, $traceId, 'save_labels_remove_child') : true;
        if (! $workDeleteRes) {
            Log::warning('保存客户标签：企业微信删除旧标签失败，已中断平台保存', [
                'trace_id'      => $traceId,
                'group_id'      => $groupId,
                'delete_labels' => $deleteLabels,
            ]);
            return false;
        }

        $groupName = (string) $groupCollect->get('name', '');
        $res = $this->transaction(function () use ($groupCollect, &$groupId, $label, $groupName) {
            $groupData = ['name' => $groupName];
            if (! $groupId || $groupCollect->has('group_type')) {
                $groupData['group_type'] = (int) $groupCollect->get('group_type', 0);
            }
            if ($groupId) {
                $this->dao->update(['id' => $groupId], $groupData);
            } else {
                $tagGroup = $this->dao->create($groupData);
                $groupId  = $tagGroup->id;
            }
            $tagCollect = collect($label ?: []);
            $tagIds     = [];
            $tagCount   = $tagCollect->count();
            $tagCollect->each(function ($tagItem, $index) use ($groupId, &$tagIds, $tagCount) {
                $tagItemCollect = collect($tagItem);
                $tagId          = $tagItemCollect->get('id', 0);
                $sort           = $tagCount - 1 - $index;
                $tagData        = [
                    'name' => $tagItemCollect->get('name', ''),
                    'pid'  => $groupId,
                    'sort' => $sort,
                ];
                if ($tagId) {
                    $this->dao->update(['id' => $tagId], $tagData);
                    $tagIds[] = $tagId;
                } else {
                    $newTag   = $this->dao->create($tagData);
                    $tagIds[] = $newTag->id;
                }
            });
            if ($tagCollect->isNotEmpty()) {
                $this->dao->delete(['not_id' => $tagIds, 'pid' => $groupId]);
            } else {
                $this->dao->delete(['pid' => $groupId]);
            }
            return true;
        });
        if ($res) {
            Cache::tags([CacheEnum::TAG_DICT])->flush();
            WorkPlatformToWorkLabelJob::dispatch($groupId, $groupName, $traceId);
        }
        return $res;
    }

    /**
     * 排序标签组.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function sortLabels(array $groupId): void
    {
        if ($this->dao->exists(['pid' => 0, 'sort' => 0])) {
            $group = $this->dao->setDefaultSort('sort')->column(['pid' => 0], 'id');
            $total = count($group);
            $cases = [];
            foreach ($group as $index => $id) {
                $sort    = $total - $index;
                $cases[] = "WHEN {$id} THEN {$sort}";
            }
            $caseSql = implode(' ', $cases);
            if (! empty($cases)) {
                DB::table($this->dao->getTable())->update([
                    'sort' => DB::raw("CASE id {$caseSql} END"),
                ]);
            }
        }
        $sorts       = $this->dao->column(['id' => $groupId], 'sort');
        $reverseSort = collect($sorts)->sortByDesc(fn ($v) => $v)->values()->all();
        foreach ($groupId as $index => $id) {
            $this->dao->update(['id' => $id], ['sort' => $reverseSort[$index]]);
        }
        Cache::tags([CacheEnum::TAG_DICT])->flush();
    }

    /**
     * 获取父子级拼接的标签.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getWithParent(array $labelId, bool $isArray = true): array|string
    {
        if (! $labelId) {
            return $isArray ? [] : '';
        }
        $result = collect(app(LabelService::class)->select(['id' => $labelId], ['id', 'pid', 'name'], with: ['parent'])?->toArray() ?? [])
            ->map(function ($item) {
                if ($item['parent']) {
                    $item['name'] = $item['parent']['name'] . '·' . $item['name'];
                }
                unset($item['parent']);
                return $item;
            });
        return $isArray ? $result->all() : $result->implode('name', '、');
    }
}
