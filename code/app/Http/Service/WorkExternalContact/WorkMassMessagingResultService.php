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

use App\Constants\CacheEnum;
use App\Http\Dao\WorkExternalContact\WorkMassMessagingResultDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\Work;
use EasyWeChat\Kernel\Exceptions\BadResponseException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 群发消息发送结果.
 */
class WorkMassMessagingResultService extends BaseService
{
    private Work $work;

    private WorkMassMessagingTaskService $messagingTask;

    public function __construct(WorkMassMessagingResultDao $dao)
    {
        $this->dao           = $dao;
        $this->work          = app(Work::class);
        $this->messagingTask = app(WorkMassMessagingTaskService::class);
    }

    public function syncMessagingResult()
    {
        $this->syncMomentTask();
        $this->syncGroupMsgTask();
    }

    /**
     * 同步朋友圈发送结果.
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws BadResponseException
     * @throws InvalidArgumentException
     * @throws DecodingExceptionInterface
     */
    public function syncMomentResult()
    {
        $cacheData  = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('syncMomentResult'));
        $sourceData = $cacheData ? collect($cacheData) : collect(app(WorkMassMessagingTaskService::class)->select(['status' => 2, 'types' => 2, 'time' => 'lately7'])?->toArray() ?? []);
        $cursors    = collect();
        $sourceData->each(function ($item) use ($cursors) {
            $item['cursor'] = $item['cursor'] ?? '';
            $result         = $this->work->getMomentSendResult($item['moment_id'], $item['userid'], $item['cursor']);
            $item['cursor'] = $result['next_cursor'] ?? '';
            foreach ($result['customer_list'] ?? [] as $value) {
                $this->dao->updateOrCreate([
                    'task_id'         => $item['id'],
                    'userid'          => $item['userid'],
                    'external_userid' => $value['external_userid'],
                ], [
                    'task_id'         => $item['id'],
                    'uid'             => $item['uid'],
                    'userid'          => $item['userid'],
                    'external_userid' => $value['external_userid'],
                ]);
            }
            $dataResult = $this->work->getMomentComments($item['moment_id'], $item['userid']);
            $commentId  = collect($dataResult['customer_list'] ?? [])->pluck('external_userid')->unique()->values()->all();
            $this->dao->update([
                'task_id'         => $item['id'],
                'external_userid' => $commentId,
            ], ['is_comment' => 1]);
            $likeId = collect($dataResult['like_list'] ?? [])->pluck('external_userid')->unique()->values()->all();
            $this->dao->update([
                'task_id'         => $item['id'],
                'external_userid' => $likeId,
            ], ['is_like' => 1]);
            $cursors->push($item);
        });
        if ($cursors->filter(fn ($item) => $item['cursor'])->isNotEmpty()) {
            Cache::tags([CacheEnum::TAG_OTHER])->put(md5('syncMomentResult'), $cursors->filter(fn ($item) => $item['cursor']), (int) sys_config('system_cache_ttl', 3600));
        } else {
            Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('syncMomentResult'));
        }
    }

    /**
     * 统计群发结果.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function censusResult(): void
    {
        $service  = app()->get(WorkMassMessagingService::class);
        $normalId = $service->column(['status' => [0, 3]], 'id');
        $taskId   = app()->get(WorkMassMessagingTaskService::class)->column(['status' => 2, 'types' => [0, 1]], 'id');
        $taskId   = array_diff($taskId, $normalId);
        $result   = $this->dao->select(['task_id' => $taskId], with: ['messaging'])?->toArray();
        $mass     = collect($result)->pluck('messaging.id', 'task_id')->all();
        collect($result ?? [])->groupBy('task_id')->map(function ($item) {
            return collect($item)->groupBy('status')->map(function ($group) {
                return $group->count();
            })->all();
        })->each(function ($item, $key) use ($mass, $service) {
            $save = [
                'be_sent'  => ($item[0] ?? 0) + ($item[1] ?? 0) + ($item[2] ?? 0) + ($item[3] ?? 0),
                'is_send'  => ($item[1] ?? 0) + ($item[2] ?? 0) + ($item[3] ?? 0),
                'is_sent'  => $item[1] ?? 0,
                'not_sent' => $item[0] ?? 0,
            ];
            if ($save['is_send'] == $save['be_sent']) {
                $save['status'] = 3;
            }
            $service->update($mass[$key], $save);
        });
    }

    /**
     * 获取群发结果.
     * @param mixed $where
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getResult($where): array
    {
        [$page, $limit]   = $this->getPageValue();
        $where['task_id'] = app()->get(WorkMassMessagingTaskService::class)->column(['mass_id' => $where['mass_id']], 'id') ?: [];
        unset($where['mass_id']);
        $list  = $this->dao->select($where, with: ['chat_group', 'customer', 'admin'], page: $page, limit: $limit);
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取朋友圈任务创建结果.
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws BadResponseException
     * @throws DecodingExceptionInterface
     */
    private function syncMomentTask()
    {
        $cacheData  = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('syncMomentTask'));
        $sourceData = $cacheData && $cacheData->isNotEmpty() ? $cacheData : collect($this->messagingTask->select(['status' => 0, 'types' => 2, 'time' => 'lately3']) ?? [])->unique('moment_id');
        $cursors    = collect();
        $sourceData->each(function ($task) use ($cursors) {
            $taskResult   = $this->work->getMomentTask($task->jobid);
            $task->cursor = $task->cursor ?? '';
            if ($taskResult) {
                $this->messagingTask->update(['jobid' => $task->jobid], ['moment_id' => $taskResult['result']['moment_id']]);
                if ($taskResult['status'] == 3) {
                    $userid       = [];
                    $result       = $this->work->getMomentTaskInfo($task->moment_id, $task->cursor);
                    $task->cursor = $result['next_cursor'] ?? '';
                    $userid       = array_merge($userid, array_column($result['task_list'] ?? [], 'userid'));
                    foreach ($result['task_list'] as $value) {
                        $this->messagingTask->update(['userid' => $value['userid']], ['status' => $value['publish_status'] ? 2 : 0]);
                    }
                    if (! in_array($task->userid, $userid)) {
                        $this->messagingTask->delete(['userid' => $task->userid]);
                    }
                }
            }
            $cursors->push($task);
        });
        if ($cursors->filter(fn ($item) => $item['cursor'])->isNotEmpty()) {
            Cache::tags([CacheEnum::TAG_OTHER])->put(md5('syncMomentTask'), $cursors->filter(fn ($item) => $item['cursor']), (int) sys_config('system_cache_ttl', 3600));
        } else {
            Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('syncMomentTask'));
        }
    }

    /**
     * 获取客户群群发任务创建结果.
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function syncGroupMsgTask()
    {
        $cacheData  = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('syncGroupMsgTask'));
        $sourceData = $cacheData && $cacheData->isNotEmpty() ? $cacheData : collect($this->messagingTask->select(['status' => 0, 'types' => [0, 1], 'time' => 'lately3']) ?? []);
        $cursors    = collect();
        $sourceData->each(function ($item) use ($cursors) {
            try {
                $taskResult = $this->work->getGroupmsgTask($item->msgid);
                foreach ($taskResult['task_list'] ?? [] as $value) {
                    $item->status                                  = $value['status'];
                    isset($value['send_time']) && $item->send_time = date('Y-m-d H:i:s', $value['send_time']);
                    $item->save();
                }
            } catch (\Exception $e) {
                Log::error('同步发送任务异常：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'func' => 'singleImport', 'record' => $item]);
            }
            $item->sumCount     = $item->sumCount ?? 0;
            $item->successCount = $item->successCount ?? 0;
            $item->notCount     = $item->notCount ?? 0;
            $item->failCount    = $item->failCount ?? 0;
            $item->cursor       = $item->cursor ?? '';
            try {
                $result       = $this->work->getGroupmsgSendResult($item->msgid, $item->userid, 100, $item->cursor);
                $item->cursor = $result['next_cursor'] ?? '';
                foreach ($result['send_list'] as $value) {
                    $where = [
                        'userid'  => $value['userid'],
                        'task_id' => $item->id,
                    ];
                    isset($value['external_userid']) && $where['external_userid'] = $value['external_userid'];
                    isset($value['chat_id']) && $where['chat_id']                 = $value['chat_id'];
                    $this->dao->updateOrCreate($where, [
                        'task_id'         => $item->id,
                        'uid'             => $item->uid,
                        'msgid'           => $item->msgid,
                        'userid'          => $value['userid'],
                        'chat_id'         => $value['chat_id'] ?? '',
                        'external_userid' => $value['external_userid'] ?? '',
                        'status'          => $value['status'],
                        'send_time'       => isset($value['send_time']) ? date('Y-m-d H:i:s', $value['send_time']) : null,
                    ]);
                    switch ($value['status']) {
                        case 0:
                            $item->notCount++;
                            break;
                        case 1:
                            $item->successCount++;
                            break;
                        default:
                            $item->failCount++;
                    }
                }
                $item->sumCount = $item->sumCount + count($result['send_list']);
            } catch (\Exception $e) {
                Log::error('同步发送结果异常：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            }
            $item->sum_count      = $item->sumCount;
            $item->success_count  = $item->successCount;
            $item->not_send_count = $item->notCount;
            $item->fail_count     = $item->failCount;
            $cursors->push($item);
            unset($item->sumCount, $item->successCount, $item->notCount, $item->failCount, $item->cursor);
            $item->save();
        });
        if ($cursors->filter(fn ($item) => $item['cursor'])->isNotEmpty()) {
            Cache::tags([CacheEnum::TAG_OTHER])->put(md5('syncGroupMsgTask'), $cursors->filter(fn ($item) => $item['cursor']), (int) sys_config('system_cache_ttl', 3600));
        } else {
            Cache::tags([CacheEnum::TAG_OTHER])->forget(md5('syncGroupMsgTask'));
        }
    }
}
