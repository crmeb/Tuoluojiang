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

namespace App\Http\Service\Work;

use App\Http\Dao\Work\WorkMessageDao;
use App\Jobs\Customer\CustomerFollowAIAuthSaveJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 会话存档消息
 * Class WorkMessageService.
 */
class WorkMessageService extends BaseService
{
    public function __construct(WorkMessageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取群聊消息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getWorkMessages(WorkConfig $workConfig, int $seq = 0, int $limit = 100)
    {
        try {
            $response = app()->get(Work::class)->session()->getDecryptChatData(['seq' => $seq, 'limit' => $limit]);
            if ($response['code'] !== 100) {
                Log::error('获取群聊消息失败：' . $response['message']);
                return;
            }
        } catch (\Throwable $e) {
            Log::error('获取群聊消息失败报错日志：' . $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
            return;
        }

        $messageList = $response['data'] ?? [];

        $now          = date('Y-m-d H:i:s');
        $room         = $indexList = $datas = [];
        $groupService = app()->get(WorkGroupChatService::class);
        foreach ($messageList as $message) {
            if ($message['decrypted_chat_msg']['action'] === 'switch') {
                continue;
            }
            if ($this->dao->value(['msg_id' => $message['msgid']], 'id')) {
                continue;
            }
            $roomId = 0;
            if (! empty($message['roomid'])) {
                if (isset($room[$message['roomid']])) {
                    $roomId = $room[$message['roomid']];
                } else {
                    $roomId                   = $groupService->value(['chat_id' => $message['roomid']], 'id');
                    $room[$message['roomid']] = $roomId;
                }
            }
            $datas[] = [
                'corp_id'    => $workConfig->getCorpId(),
                'seq'        => $message['seq'],
                'msg_id'     => $message['msgid'],
                'action'     => $message['decrypted_chat_msg']['action'],
                'from'       => $message['decrypted_chat_msg']['from'],
                'tolist'     => json_encode($message['decrypted_chat_msg']['tolist'] ?? []),
                'msg_type'   => $message['decrypted_chat_msg']['msgtype'],
                'content'    => json_encode($message['decrypted_chat_msg'][$message['decrypted_chat_msg']['msgtype']] ?? $message['decrypted_chat_msg']['info'] ?? $message['decrypted_chat_msg']['doc'] ?? []),
                'msg_time'   => date('Y-m-d H:i:s', intval($message['decrypted_chat_msg']['msgtime'] / 1000)),
                'wx_room_id' => $message['decrypted_chat_msg']['roomid'] ?? '',
                'room_id'    => $roomId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $indexDatas = $this->buildIndexData($message['decrypted_chat_msg'], $workConfig->getCorpId());
            if ($indexDatas) {
                $indexList = array_merge($indexList, $indexDatas);
            }
        }

        if ($messageList) {
            $seqs   = array_column($messageList, 'seq');
            $endSeq = end($seqs);

            if ($endSeq != $seq) {
                $seqInfo = app()->get(WorkMessageSeqService::class)->get(['corp_id' => $workConfig->getCorpId()]);
                if ($seqInfo) {
                    $seqInfo->seq = $endSeq;
                    $seqInfo->save();
                } else {
                    app()->get(WorkMessageSeqService::class)->create([
                        'corp_id' => $workConfig->getCorpId(),
                        'seq'     => $endSeq,
                    ]);
                }
            }
        }
        // 插入聊天主数据
        if ($datas) {
            $this->dao->insert($datas);
        }
        // 插入索引相关的数据
        if ($indexList) {
            $indexService = app()->get(WorkMessageIndexService::class);

            $save = $uniqueKeys = [];

            $generateUniqueKey = function ($corpId, $indexId, $indexType) {
                return "{$corpId}_{$indexId}_{$indexType}";
            };
            foreach ($indexList as $indexData) {
                $fromKey = $generateUniqueKey($indexData['corp_id'], $indexData['index_id'], $indexData['index_type']);
                if (isset($uniqueKeys[$fromKey])) {
                    ++$uniqueKeys[$fromKey];
                } else {
                    $uniqueKeys[$fromKey] = 1;
                }
            }
            $saveIndexs = [];
            foreach ($indexList as $indexData) {
                $fromKey = $generateUniqueKey($indexData['corp_id'], $indexData['index_id'], $indexData['index_type']);
                if (isset($uniqueKeys[$fromKey]) && $uniqueKeys[$fromKey] == 1) {
                    $saveIndexs[] = $indexData;
                }
            }
            foreach ($saveIndexs as $indexData) {
                if (! $indexService->count([
                    'corp_id'    => $indexData['corp_id'],
                    'index_id'   => $indexData['index_id'],
                    'index_type' => $indexData['index_type'],
                ])) {
                    $save[] = $indexData;
                }
            }
            if ($save) {
                app()->get(WorkMessageIndexService::class)->insert($save);
            }
        }

        $this->followAIAuthSave();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function followAIAuthSave()
    {
        $service = app()->get(WorkMessageIndexService::class);
        $list    = $service->getTodayIndexData();
        foreach ($list as $item) {
            if (! isset($item['client'])) {
                continue;
            }
            CustomerFollowAIAuthSaveJob::dispatch($item['index_id'], $item['client']['external_userid'], $item['client']['userid']);
        }
    }

    /**
     * 解析聊天信息，把数据拆分成索引数据.
     * @throws BindingResolutionException
     */
    public function buildIndexData(array $decrypted, string $corpId): array
    {
        $indexData = [];
        $now       = date('Y-m-d H:i:s');

        $indexType = $this->getIndexType($decrypted['from']);
        $indexId   = $this->getIndexId($decrypted['from'], $indexType);
        if ($indexId) {
            // 4.1 发送方索引（from）
            $indexData[] = [
                'corp_id'    => $corpId,
                'index_id'   => $indexId, // 实际业务中可能需转换为本地用户ID
                'index_type' => $indexType, // 0=员工，1=客户
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        unset($indexType);
        // 4.2 接收方索引（tolist中的每个成员）
        foreach ($decrypted['tolist'] ?? [] as $receiver) {
            $indexType = $this->getIndexType($receiver);
            $indexId   = $this->getIndexId($receiver, $indexType);
            if (! $indexId) {
                continue;
            }
            $indexData[] = [
                'corp_id'    => $corpId,
                'index_id'   => $indexId,
                'index_type' => $indexType,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 4.3 群聊索引（若有roomid）
        if (! empty($decrypted['roomid'])) {
            $indexId = $this->getIndexId($decrypted['roomid'], 2);
            if ($indexId) {
                $indexData[] = [
                    'corp_id'    => $corpId,
                    'index_id'   => $indexId,
                    'index_type' => 2, // 2=群聊
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $indexData;
    }

    /**
     * 获取索引ID.
     * @throws BindingResolutionException
     */
    public function getIndexId(string $wxId, int $indexType): ?int
    {
        switch ($indexType) {
            case 0:
                return app()->get(WorkMemberService::class)->value(['userid' => $wxId], 'id');
            case 1:
                return app()->get(WorkClientService::class)->value(['external_userid' => $wxId], 'id');
            case 2:
                return app()->get(WorkGroupChatService::class)->value(['chat_id' => $wxId], 'id');
        }
    }

    /**
     * 获取索引类型.
     */
    public function getIndexType(string $wxId): int
    {
        return strpos($wxId, 'wmu') === 0 ? 0 : 1;
    }
}
