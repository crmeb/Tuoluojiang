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

use App\Constants\Work\MediaEnum;
use App\Http\Dao\WorkExternalContact\WorkChannelCodeDao;
use App\Http\Model\Work\WorkClient;
use App\Http\Model\WorkExternalContact\WorkChannelCode;
use App\Http\Model\WorkExternalContact\WorkChannelCodeCate;
use App\Http\Model\WorkExternalContact\WorkChannelCodeGroup;
use App\Http\Model\WorkExternalContact\WorkChannelCodeLog;
use App\Http\Model\WorkExternalContact\WorkMedia;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\LabelService;
use App\Http\Service\Work\WorkClientService;
use App\Http\Service\Work\WorkGroupChatService;
use App\Http\Service\Work\WorkMemberService;
use App\Jobs\Work\WorkClientSetLabelJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企微渠道活码.
 */
class WorkChannelCodeService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    private const CHANNEL_STATE_PREFIX = 'channelCode-';

    private const GROUP_STATE_PREFIX = 'ccg-';

    private const CLIENT_NAME_VARIABLES = [
        '{客户名称}',
        '{客户姓名}',
        '{客户名}',
        '${客户名称}',
        '${客户姓名}',
        '${客户名}',
        '{{客户名称}}',
        '{{客户姓名}}',
        '{{客户名}}',
        '[客户名称]',
        '[客户姓名]',
        '[客户名]',
        '#客户名称#',
        '#客户姓名#',
        '#客户名#',
        '{client_name}',
        '{customer_name}',
        '{{client_name}}',
        '{{customer_name}}',
        '${client_name}',
        '${customer_name}',
    ];

    /**
     * WorkChannelCodeService constructor.
     */
    public function __construct(WorkChannelCodeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = ['id' => 'desc'], array $with = ['creator', 'groups', 'group', 'inviteGroupConfig']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        $list           = $this->appendListBaseInfo($list, false);
        return $this->listData($list, $count);
    }

    /**
     * 保存渠道活码.
     * @return mixed
     */
    public function resourceSave(array $data)
    {
        $data = $this->formatSaveData($data);

        /** @var WorkChannelCode $channel */
        $channel = $this->transaction(function () use ($data) {
            $fileIds = $this->collectWelcomeFileIds((array) ($data['welcome_messages'] ?? []));

            $channel = $this->dao->create($data);
            $this->syncWelcomeMediaLinks((int) $channel->id, $fileIds);
            return $channel;
        });

        if ((int) $channel->status === 1) {
            $this->syncContactWay($channel->fresh());
            if ((int) $channel->invite_group === 1) {
                $this->syncJoinWays((int) $channel->id);
            }
        }

        return true;
    }

    /**
     * 修改渠道活码.
     * @param mixed $id
     * @return mixed
     */
    public function resourceUpdate($id, array $data)
    {
        $data = $this->formatSaveData($data, (int) $id);
        /** @var WorkChannelCode $oldChannel */
        $oldChannel = $this->dao->get((int) $id);
        if (! $oldChannel) {
            throw $this->exception('渠道活码不存在');
        }
        if ((int) $data['status'] === 0) {
            $this->disableContactWay($oldChannel);
            $data['contact_config_id'] = '';
            $data['qr_code']           = '';
        }

        $this->transaction(function () use ($id, $data) {
            $fileIds = $this->collectWelcomeFileIds((array) ($data['welcome_messages'] ?? []));
            unset($data['uid'], $data['corp_id']);

            $this->dao->update((int) $id, $data);
            $this->syncWelcomeMediaLinks((int) $id, $fileIds);
        });

        /** @var WorkChannelCode $channel */
        $channel = $this->dao->get((int) $id);
        if ((int) $channel->status === 1) {
            $this->syncContactWay($channel);
            if ((int) $channel->invite_group === 1) {
                $this->syncJoinWays((int) $id);
            }
        }

        return true;
    }

    /**
     * 删除渠道活码，并同步删除企微联系我二维码和客户群进群方式.
     * @param mixed $id
     * @return mixed
     */
    public function resourceDelete($id, ?string $key = null)
    {
        /** @var WorkChannelCode $channel */
        $channel = $this->dao->get((int) $id, with: ['groups']);
        if (! $channel) {
            throw $this->exception('渠道活码不存在');
        }

        if ($channel->contact_config_id) {
            try {
                app()->get(Work::class)->deleteQrCode($channel->contact_config_id);
            } catch (\Throwable $e) {
                Log::warning('删除联系我二维码失败:' . $e->getMessage(), ['channel_id' => $channel->id]);
            }
        }

        return $this->transaction(function () use ($id) {
            app()->get(WorkMediaService::class)->deleteLink((int) $id, MediaEnum::LINK_TYPE_CHANNEL_CODE);
            return $this->dao->delete((int) $id);
        });
    }

    /**
     * 获取渠道活码创建页初始化数据.
     */
    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 获取渠道活码详情.
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $data = $this->dao->get($id, with: [
            'creator',
            'group',
            'inviteGroupConfig',
            'groups',
        ])?->toArray();
        if (! $data) {
            throw $this->exception('渠道活码不存在');
        }

        return $this->appendListBaseInfo([$data])[0] ?? $data;
    }

    /**
     * 批量修改活码分组.
     */
    public function batchUpdateGroup(array $ids, int $groupId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        if (! $ids) {
            throw $this->exception('请选择需要修改的渠道活码');
        }
        if ($groupId < 0) {
            throw $this->exception('活码分组格式错误');
        }
        if ($groupId && ! WorkChannelCodeCate::query()->whereKey($groupId)->exists()) {
            throw $this->exception('活码分组不存在');
        }
        if (! WorkChannelCode::query()->whereIn('id', $ids)->exists()) {
            throw $this->exception('渠道活码不存在');
        }

        return WorkChannelCode::query()->whereIn('id', $ids)->update(['group_id' => $groupId]);
    }

    /**
     * 重新生成企微联系我二维码和客户群进群二维码.
     */
    public function refreshQrCode(int $id): bool
    {
        $channel = $this->dao->get($id);
        if (! $channel) {
            throw $this->exception('渠道活码不存在');
        }
        if ((int) $channel->status !== 1) {
            throw $this->exception('请先开启渠道码');
        }
        $this->syncContactWay($channel);
        if ((int) $channel->invite_group === 1) {
            $this->syncJoinWays($id);
        }
        return true;
    }

    /**
     * 同步使用指定邀请加群配置的渠道活码.
     */
    public function syncInviteGroupConfig(int $configId): void
    {
        $channels = $this->dao->select(['code_id' => $configId, 'invite_group' => 1]);
        foreach ($channels as $channel) {
            if ((int) $channel->status === 1) {
                $this->syncJoinWays((int) $channel->id);
            }
        }
    }

    /**
     * 通过配置 ID 获取企业已配置的「联系我」方式.
     */
    public function getContactWayByConfigId(string $configId): array
    {
        $configId = trim($configId);
        if (! $configId) {
            throw $this->exception('联系我配置ID不能为空');
        }

        $result = $this->normalizeWechatResponse(app()->get(Work::class)->getContactWayByConfigId($configId));
        if (($result['errcode'] ?? 0) !== 0) {
            throw $this->exception($result['errmsg'] ?? '获取联系我方式失败');
        }

        return $result;
    }

    /**
     * 快捷启用/关闭渠道活码.
     */
    public function updateStatus(int $id, int $status): bool
    {
        if (! in_array($status, [0, 1], true)) {
            throw $this->exception('状态值错误');
        }
        /** @var WorkChannelCode $channel */
        $channel = $this->dao->get($id);
        if (! $channel) {
            throw $this->exception('渠道活码不存在');
        }
        if ($status === 0) {
            $this->disableContactWay($channel);
            return (bool) $this->dao->update($id, ['status' => 0, 'contact_config_id' => '', 'qr_code' => '']);
        }

        $this->syncContactWay($channel);
        if ((int) $channel->invite_group === 1) {
            $this->syncJoinWays($id);
        }
        return (bool) $this->dao->update($id, ['status' => 1]);
    }

    /**
     * 处理客户添加后的欢迎语、入群二维码和标签.
     */
    public function sendWelcomeByPayload(array $payload): bool
    {
        $state = (string) ($payload['State'] ?? '');
        if (! str_starts_with($state, self::CHANNEL_STATE_PREFIX)) {
            return true;
        }

        $channelId      = (int) str_replace(self::CHANNEL_STATE_PREFIX, '', $state);
        $externalUserid = (string) ($payload['ExternalUserID'] ?? '');
        $userid         = (string) ($payload['UserID'] ?? '');
        $corpId         = (string) ($payload['ToUserName'] ?? sys_config('wechat_work_corpid'));
        $welcomeCode    = (string) ($payload['WelcomeCode'] ?? '');
        $lockKey        = sprintf('work_channel_code_welcome:%s:%s:%s', $channelId, $userid, $externalUserid);

        return (bool) Cache::lock($lockKey, 10)->get(function () use ($channelId, $externalUserid, $userid, $corpId, $welcomeCode, $state) {
            /** @var WorkChannelCode $channel */
            $channel = $this->dao->get($channelId, with: ['groups']);
            if (! $channel || (int) $channel->status !== 1) {
                Log::warning('渠道活码欢迎语跳过：渠道码不存在或未启用', [
                    'channel_id'       => $channelId,
                    'userid'           => $userid,
                    'external_userid'  => $externalUserid,
                    'channel_exists'   => (bool) $channel,
                    'channel_status'   => $channel?->status ?? null,
                    'has_welcome_code' => $welcomeCode !== '',
                ]);
                return true;
            }

            $inviteGroup = (int) $channel->invite_group === 1;
            $group       = $inviteGroup ? $this->selectAvailableGroup($channel) : null;
            $logService  = app()->get(WorkChannelCodeLogService::class);
            $log         = $logService->firstOrCreate([
                'channel_id'      => $channelId,
                'userid'          => $userid,
                'external_userid' => $externalUserid,
            ], [
                'corp_id'      => $corpId,
                'state'        => $state,
                'welcome_code' => $welcomeCode,
            ]);

            if ($inviteGroup && ! $group) {
                $logService->update($log->id, [
                    'send_status' => 2,
                    'error_msg'   => '未配置可用客户群',
                ]);
                Log::warning('渠道活码欢迎语跳过：未配置可用客户群', [
                    'channel_id'      => $channelId,
                    'userid'          => $userid,
                    'external_userid' => $externalUserid,
                ]);
                return false;
            }

            $logService->update($log->id, [
                'group_id'     => $group?->id ?? 0,
                'corp_id'      => $corpId,
                'state'        => $state,
                'welcome_code' => $welcomeCode,
            ]);

            try {
                $tagIds = $channel->work_tag_ids ?: $this->labelIdsToWorkTagIds($channel->tag_ids ?: []);
                if ($tagIds) {
                    WorkClientSetLabelJob::dispatch([
                        'userid'          => $userid,
                        'external_userid' => $externalUserid,
                        'add_tag'         => $tagIds,
                        'remove_tag'      => [],
                    ]);
                }
                if (! $welcomeCode) {
                    $logService->update($log->id, [
                        'send_status'  => 2,
                        'group_status' => 0,
                        'error_msg'    => '企业微信未返回 welcome_code，无法发送欢迎语',
                    ]);
                    return true;
                }

                $clientName = '';
                if ($this->welcomeMessageHasClientNameVariable($channel)) {
                    $clientName = $this->syncAndGetClientName($corpId, $externalUserid, $userid);
                    if ($clientName === '') {
                        $logService->update($log->id, [
                            'send_status'  => 2,
                            'group_status' => 0,
                            'error_msg'    => '客户信息未写入 work_client，未发送欢迎语',
                        ]);
                        return false;
                    }
                }

                $message  = $this->buildWelcomeMessage($channel, $group, $clientName);
                $response = $this->sendWelcomeMessage($welcomeCode, $message);
                Log::warning('渠道活码欢迎语企微接口返回', [
                    'channel_id'       => $channelId,
                    'userid'           => $userid,
                    'external_userid'  => $externalUserid,
                    'errcode'          => $response['errcode'] ?? null,
                    'errmsg'           => $response['errmsg'] ?? '',
                    'has_text'         => ! empty($message['text']['content'] ?? ''),
                    'attachment_count' => count($message['attachments'] ?? []),
                    'invite_group'     => $inviteGroup,
                ]);
                if (($response['errcode'] ?? 0) !== 0) {
                    $errcode = (int) ($response['errcode'] ?? 0);
                    if ($errcode === 41051) {
                        $logService->update($log->id, [
                            'send_status'  => 1,
                            'group_status' => $inviteGroup ? 1 : 0,
                            'error_msg'    => '',
                        ]);
                        return true;
                    }
                    $logService->update($log->id, [
                        'send_status' => 2,
                        'error_msg'   => mb_substr(($response['errmsg'] ?? '') ?: '发送欢迎语失败', 0, 500),
                    ]);
                    return false;
                }

                $logService->update($log->id, [
                    'send_status'  => 1,
                    'group_status' => $inviteGroup ? 1 : 0,
                    'error_msg'    => '',
                ]);
            } catch (\Throwable $e) {
                $logService->update($log->id, [
                    'send_status' => 2,
                    'error_msg'   => mb_substr($e->getMessage(), 0, 500),
                ]);
                Log::error('渠道活码欢迎语发送失败:' . $e->getMessage(), ['payload' => $payload, 'channel_id' => $channelId]);
                return false;
            }

            return true;
        });
    }

    /**
     * 根据进群二维码 state 标记入群/退群.
     */
    public function markGroupStatusByState(string $state, string $externalUserid, int $groupStatus): bool
    {
        $groupId = $this->groupIdFromState($state);
        if ($groupId < 1) {
            return true;
        }

        $data = ['group_status' => $groupStatus];
        if ($groupStatus === 2) {
            $data['joined_at'] = now()->toDateTimeString();
        }
        if ($groupStatus === 3) {
            $data['left_at'] = now()->toDateTimeString();
        }

        app()->get(WorkChannelCodeLogService::class)->update([
            'group_id'        => $groupId,
            'external_userid' => $externalUserid,
        ], $data);

        return true;
    }

    /**
     * 根据客户群ID标记客户退群.
     */
    public function markLeftByChatId(string $chatId, array $externalUserids): bool
    {
        $externalUserids = array_values(array_filter($externalUserids));
        if (! $chatId || ! $externalUserids) {
            return true;
        }

        $groupIds = app()->get(WorkChannelCodeGroupService::class)->idsByChatId($chatId);
        if (! $groupIds) {
            return true;
        }

        app()->get(WorkChannelCodeLogService::class)->update([
            'group_id'        => $groupIds,
            'external_userid' => $externalUserids,
        ], [
            'group_status' => 3,
            'left_at'      => now()->toDateTimeString(),
        ]);

        return true;
    }

    /**
     * 生成客户群进群二维码回调 state.
     */
    public static function groupState(): string
    {
        return self::GROUP_STATE_PREFIX . self::snowflakeId();
    }

    private function groupIdFromState(string $state): int
    {
        if (! $this->isValidGroupState($state)) {
            return 0;
        }
        return app()->get(WorkChannelCodeGroupService::class)->idByState($state);
    }

    private function isValidGroupState(string $state): bool
    {
        return str_starts_with($state, self::GROUP_STATE_PREFIX) && mb_strlen($state) <= 30;
    }

    private static function snowflakeId(): string
    {
        static $lastTimestamp = -1;
        static $sequence      = 0;

        $epoch        = 1704067200000;
        $milliseconds = (int) floor(microtime(true) * 1000);
        if ($milliseconds < $lastTimestamp) {
            $milliseconds = $lastTimestamp;
        }
        if ($milliseconds === $lastTimestamp) {
            $sequence = ($sequence + 1) & 4095;
            while ($sequence === 0 && $milliseconds <= $lastTimestamp) {
                usleep(1000);
                $milliseconds = (int) floor(microtime(true) * 1000);
            }
        } else {
            $sequence = 0;
        }
        $lastTimestamp = $milliseconds;

        $timestamp = max(0, $milliseconds - $epoch);
        $workerId  = (int) (sprintf('%u', crc32((string) gethostname() . '-' . getmypid())) % 1024);

        return (string) (($timestamp << 22) | ($workerId << 12) | $sequence);
    }

    /**
     * 格式化并校验渠道活码保存数据.
     */
    private function formatSaveData(array $data, int $id = 0): array
    {
        $userids     = $this->normalizeList($data['userids'] ?? []);
        $tagIds      = $this->normalizeList($data['tag_ids'] ?? []);
        $name        = trim((string) ($data['name'] ?? ''));
        $corpId      = (string) ($data['corp_id'] ?? sys_config('wechat_work_corpid'));
        $workUserids = $this->adminIdsToWorkUserids($userids, $corpId);
        if (! $name) {
            throw $this->exception('请填写扫码名称');
        }
        if (! $userids) {
            throw $this->exception('请选择使用员工');
        }
        if (! $workUserids) {
            throw $this->exception('请选择已绑定企微的使用员工');
        }

        $inviteGroup = $this->normalizeInviteGroup($data);
        $codeId = max(0, (int) ($data['code_id'] ?? 0));
        if ($inviteGroup) {
            if (! $codeId) {
                throw $this->exception('请选择邀请加群配置');
            }
            app()->get(WorkChannelCodeGroupService::class)->configForChannel($codeId);
        }

        $workTagIds      = $this->labelIdsToWorkTagIds($tagIds);
        $welcomeMessages = $this->normalizeWelcomeMessages($data);
        $attachmentCount = count(array_filter($welcomeMessages, fn ($message) => ($message['msgtype'] ?? '') !== MediaEnum::TEMP_TEXT));
        if ($attachmentCount + ($inviteGroup ? 1 : 0) > 9) {
            throw $this->exception('欢迎语附件最多支持9个');
        }
        $welcomeText = trim((string) ($this->welcomeInput($data)['content'] ?? $data['welcome_text'] ?? ''));
        if ($welcomeText === '') {
            $textMessage = collect($welcomeMessages)->first(fn ($message) => ($message['msgtype'] ?? '') === MediaEnum::TEMP_TEXT);
            $welcomeText = (string) ($textMessage['text']['content'] ?? '');
        }

        return [
            'uid'                        => $id ? (int) ($data['uid'] ?? auth('admin')->id()) : (int) auth('admin')->id(),
            'corp_id'                    => $corpId,
            'group_id'                   => max(0, (int) ($data['group_id'] ?? 0)),
            'name'                       => $name,
            'userids'                    => $this->nullIfEmptyList($userids),
            'work_userids'               => $this->nullIfEmptyList($workUserids),
            'tag_ids'                    => $this->nullIfEmptyList($tagIds),
            'work_tag_ids'               => $this->nullIfEmptyList($workTagIds),
            'welcome_text'               => $welcomeText,
            'welcome_messages'           => $this->nullIfEmptyList($welcomeMessages),
            'invite_group'               => $inviteGroup,
            'code_id'                => $codeId,
            'skip_verify'                => $this->normalizeSkipVerify($data),
            'status'                     => (int) ($data['status'] ?? 1),
            'remark'                     => (string) ($data['remark'] ?? ''),
        ];
    }

    /**
     * 同步企微联系我二维码配置.
     */
    private function syncContactWay(WorkChannelCode $channel): void
    {
        $work    = app()->get(Work::class);
        $userids = $channel->work_userids ?: $this->adminIdsToWorkUserids($channel->userids ?: [], (string) $channel->corp_id);
        if (! $userids) {
            throw $this->exception('请选择已绑定企微的使用员工');
        }

        $response = $channel->contact_config_id
            ? $work->updateQrCode((int) $channel->id, $userids, $channel->contact_config_id, (bool) $channel->skip_verify)
            : $work->createQrCode((int) $channel->id, $userids, (bool) $channel->skip_verify);

        $result = $this->normalizeWechatResponse($response);
        if (($result['errcode'] ?? 0) !== 0) {
            throw $this->exception($result['errmsg'] ?? '创建联系我二维码失败');
        }

        $this->dao->update((int) $channel->id, [
            'contact_config_id' => (string) ($result['config_id'] ?? $channel->contact_config_id ?? ''),
            'qr_code'           => (string) ($this->responseQrCode($result) ?: ($channel->qr_code ?? '')),
            'work_userids'      => $userids,
        ]);
    }

    /**
     * 删除企微联系我二维码，使已下发的渠道码不可继续扫码添加好友.
     */
    private function disableContactWay(WorkChannelCode $channel): void
    {
        if (! $channel->contact_config_id) {
            return;
        }

        $result = $this->normalizeWechatResponse(app()->get(Work::class)->deleteQrCode($channel->contact_config_id));
        if (($result['errcode'] ?? 0) !== 0) {
            throw $this->exception($result['errmsg'] ?? '关闭渠道码失败');
        }
    }

    /**
     * 同步客户群进群方式并保存二维码.
     */
    public function syncJoinWays(int $channelId): void
    {
        /** @var WorkChannelCode $channel */
        $channel = $this->dao->get($channelId);
        if (! $channel || ! $channel->code_id) {
            return;
        }
        app()->get(WorkChannelCodeGroupService::class)->syncWorkJoinWay((int) $channel->code_id);
    }

    /**
     * 按排序选择可承接客户入群的客户群.
     */
    private function selectAvailableGroup(WorkChannelCode $channel): ?WorkChannelCodeGroup
    {
        $groups = $channel->groups->filter(fn ($group) => (int) $group->status === 1)->sortBy([['sort', 'asc'], ['id', 'asc']]);
        foreach ($groups as $group) {
            foreach ($this->normalizeChatIds($group->chat_ids ?: []) as $chatId) {
                $memberNum = (int) app()->get(WorkGroupChatService::class)->value(['chat_id' => $chatId], 'member_num');
                if ($memberNum < (int) $group->max_member_num) {
                    return $group;
                }
            }
        }
        return $groups->filter(fn ($group) => (int) $group->auto_create_room === 1)->last();
    }

    /**
     * 组装发送给客户的欢迎语和入群二维码消息.
     */
    private function buildWelcomeMessage(WorkChannelCode $channel, ?WorkChannelCodeGroup $group = null, string $clientName = ''): array
    {
        $messages = $this->normalizeWelcomeMessages([
            'welcome_text'     => (string) $channel->welcome_text,
            'welcome_messages' => $channel->welcome_messages ?: [],
        ]);

        $message = [];
        foreach ($messages as $item) {
            $type = (string) ($item['msgtype'] ?? '');
            if ($type === MediaEnum::TEMP_TEXT && empty($message['text'])) {
                $message['text'] = [
                    'content' => $this->replaceClientNameVariables((string) ($item['text']['content'] ?? ''), $clientName),
                ];
                continue;
            }

            $attachment = $this->welcomeMessageToAttachment($item, $clientName);
            if ($attachment) {
                $message['attachments'][] = $attachment;
            }
        }

        if (empty($message['text'])) {
            $message['text'] = [
                'content' => $this->replaceClientNameVariables((string) $channel->welcome_text, $clientName) ?: '欢迎添加。',
            ];
        }

        $qrCode = (string) ($group?->qr_code ?? '');
        if ((int) $channel->invite_group === 1 && $qrCode) {
            $message['attachments'][] = [
                'msgtype' => MediaEnum::TEMP_IMAGE,
                'image'   => [
                    'pic_url' => $qrCode,
                ],
            ];
        }

        return $message;
    }

    private function sendWelcomeMessage(string $welcomeCode, array $message): array
    {
        $work     = app()->get(Work::class);
        $response = [];
        for ($attempt = 0; $attempt < 3; ++$attempt) {
            $response = $this->normalizeWechatResponse($work->message(WorkConfig::TYPE_USER)->sendWelcome($welcomeCode, $message));
            if ((int) ($response['errcode'] ?? 0) !== 41096) {
                return $response;
            }
            usleep(300000);
        }

        return $response;
    }

    /**
     * 批量补齐列表展示所需的成员、标签、群名称基础信息.
     */
    private function appendListBaseInfo(array $list, bool $withDetail = true): array
    {
        if (! $list) {
            return [];
        }

        $adminIds = $tagIds = $chatIds = $channelIds = [];
        foreach ($list as $item) {
            if (! empty($item['id'])) {
                $channelIds[] = (int) $item['id'];
            }
            $adminIds = array_merge($adminIds, $this->normalizeList($item['userids'] ?? []));
            $tagIds   = array_merge($tagIds, $this->normalizeList($item['tag_ids'] ?? []));
            foreach ((array) ($item['groups'] ?? []) as $group) {
                $chatIds = array_merge($chatIds, $this->normalizeChatIds($group['chat_ids'] ?? []));
            }
        }

        $legacyWorkUserids = array_values(array_unique(array_filter(array_diff($adminIds, array_filter($adminIds, fn ($id) => ctype_digit((string) $id))))));
        $legacyWorkTagIds  = array_values(array_unique(array_filter(array_diff($tagIds, array_filter($tagIds, fn ($id) => ctype_digit((string) $id))))));
        $adminIds          = array_values(array_unique(array_filter(array_map('intval', $adminIds))));
        $tagIds            = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        $chatIds           = array_values(array_unique(array_filter($chatIds)));

        $adminMap = $adminIds ? app()->get(AdminService::class)->column(['id' => $adminIds], ['id', 'name', 'avatar', 'uid', 'phone'], 'id') : [];
        $tagMap   = $tagIds ? app()->get(LabelService::class)->column(['id' => $tagIds], ['id', 'name', 'work_tag_id'], 'id') : [];
        $groupMap = $chatIds ? collect(app()->get(WorkGroupChatService::class)->select(
            ['chat_id' => $chatIds],
            ['*'],
            ['admin']
        )?->toArray() ?? [])->keyBy('chat_id')->all() : [];
        $joinCountMap = $withDetail ? $this->getJoinedCustomerCounts($channelIds) : [];

        foreach ($list as &$item) {
            $channelGroups = (array) ($item['groups'] ?? []);
            $channelId     = (int) ($item['id'] ?? 0);
            $users         = $this->mapValues($this->normalizeList($item['userids'] ?? []), $adminMap);
            foreach ($legacyWorkUserids as $userid) {
                if (in_array($userid, $this->normalizeList($item['userids'] ?? []), true)) {
                    $users[] = ['id' => $userid, 'name' => $userid, 'userid' => $userid];
                }
            }
            $tags = $this->mapValues($this->normalizeList($item['tag_ids'] ?? []), $tagMap);
            foreach ($legacyWorkTagIds as $tagId) {
                if (in_array($tagId, $this->normalizeList($item['tag_ids'] ?? []), true)) {
                    $tags[] = ['id' => $tagId, 'name' => $tagId, 'work_tag_id' => $tagId];
                }
            }
            $workGroups  = [];
            $groupNames  = [];
            $seenChatIds = [];
            $itemChatIds = [];

            foreach ($channelGroups as $index => $group) {
                foreach ($this->normalizeChatIds($group['chat_ids'] ?? []) as $chatId) {
                    if (isset($seenChatIds[$chatId])) {
                        continue;
                    }
                    $seenChatIds[$chatId] = true;
                    $itemChatIds[]        = $chatId;
                    $groupInfo = $groupMap[$chatId] ?? [];
                    $name      = (string) ($groupInfo['name'] ?? $group['group_name'] ?? '');
                    if ($withDetail && $groupInfo) {
                        $workGroups[] = $groupInfo;
                    }
                    if ($name) {
                        $groupNames[] = $name;
                    }
                }
            }
            $firstGroup = (array) ($channelGroups[0] ?? []);
            if (! $withDetail) {
                $item = [
                    'id'                         => $channelId,
                    'group_id'                   => (int) ($item['group_id'] ?? 0),
                    'group'                      => $item['group'] ?? null,
                    'qr_code'                    => (string) ($item['qr_code'] ?? ''),
                    'name'                       => (string) ($item['name'] ?? ''),
                    'users'                      => $users,
                    'tags'                       => $tags,
                    'group_names'                => array_values(array_unique($groupNames)),
                    'invite_group'               => (int) ($item['invite_group'] ?? 1),
                    'code_id'                    => (int) ($item['code_id'] ?? 0),
                    'auto_create_room'           => (int) ($firstGroup['auto_create_room'] ?? 1),
                    'status'                     => (int) ($item['status'] ?? 0),
                    'creator'                    => $item['creator'] ?? null,
                    'created_at'                 => $item['created_at'] ?? '',
                ];
                continue;
            }

            $item['join_customer_count']    = (int) ($joinCountMap[$channelId] ?? $joinCountMap[(string) $channelId] ?? 0);
            $item['users']                  = $users;
            $item['tags']                   = $tags;
            $item['groups']                 = $workGroups;
            $item['group_names']            = array_values(array_unique($groupNames));
            $item['chat_ids']               = $itemChatIds;
            $item['welcome']                = $this->formatWelcomeForResponse((array) ($item['welcome_messages'] ?? []), (string) ($item['welcome_text'] ?? ''));
            $item['auto_create_room']       = (int) ($firstGroup['auto_create_room'] ?? 1);
            $item['room_base_name']         = (string) ($firstGroup['room_base_name'] ?? '');
            $item['room_name']              = $item['room_base_name'];
            $item['room_base_id']           = (int) ($firstGroup['room_base_id'] ?? 1);
            $item['room_base_start']        = $item['room_base_id'];
            $item['group_serial']           = $item['room_base_id'];
            $item['group_name']             = (string) ($firstGroup['group_name'] ?? $item['room_base_name']);
            $item['join_way_remark']        = (string) ($firstGroup['join_way_remark'] ?? '');
            $item['scene']                  = (int) ($firstGroup['scene'] ?? 2);
            $item['max_member_num']         = (int) ($firstGroup['max_member_num'] ?? 200);
            $item['verify_type']            = (int) ($item['skip_verify'] ?? 1) === 1 ? 'direct' : 'need_verify';
        }
        unset($item);

        return $list;
    }

    private function getJoinedCustomerCounts(array $channelIds): array
    {
        $channelIds = array_values(array_unique(array_filter(array_map('intval', $channelIds))));
        if (! $channelIds) {
            return [];
        }

        return WorkChannelCodeLog::query()
            ->whereIn('channel_id', $channelIds)
            ->whereIn('group_status', [2, 3])
            ->where('external_userid', '<>', '')
            ->selectRaw('channel_id, COUNT(DISTINCT external_userid) as join_customer_count')
            ->groupBy('channel_id')
            ->pluck('join_customer_count', 'channel_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function mapValues(array $ids, array $map): array
    {
        return collect($ids)
            ->map(fn ($id) => $map[$id] ?? $map[(string) $id] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function formatWelcomeForResponse(array $messages, string $welcomeText): array
    {
        $messages = $this->hydrateWelcomeMessageFiles($messages);
        $content  = $welcomeText;
        $attach   = [];
        foreach ($messages as $message) {
            $message = (array) $message;
            if (($message['msgtype'] ?? '') === MediaEnum::TEMP_TEXT) {
                $content = $content ?: (string) ($message['text']['content'] ?? '');
                continue;
            }
            $attach[] = $this->formatWelcomeAttachForResponse($message);
        }

        return [
            'content' => $content,
            'attach'  => $attach,
        ];
    }

    private function formatWelcomeAttachForResponse(array $message): array
    {
        $type = (string) ($message['msgtype'] ?? $message['types'] ?? '');
        if ($type === 'miniprogram') {
            $type = 'mini_program';
        }
        $fileId = $this->welcomeMessageFileId($message);
        $file   = $this->formatWelcomeFileForResponse($message);

        $data = match ($type) {
            MediaEnum::TEMP_IMAGE => [
                'types'    => MediaEnum::TEMP_IMAGE,
                'file_id'  => $fileId,
                'file_url' => ! empty($message['image']['pic_url']) ? link_file((string) $message['image']['pic_url']) : '',
                'media_id' => (string) ($message['image']['media_id'] ?? ''),
            ],
            MediaEnum::TEMP_LINK => [
                'types'   => MediaEnum::TEMP_LINK,
                'title'   => (string) ($message['link']['title'] ?? ''),
                'info'    => (string) ($message['link']['desc'] ?? ''),
                'link'    => (string) ($message['link']['url'] ?? ''),
                'picurl'  => ! empty($message['link']['picurl']) ? link_file((string) $message['link']['picurl']) : '',
                'file_id' => $fileId,
            ],
            MediaEnum::TEMP_VIDEO, MediaEnum::TEMP_FILE => [
                'types'    => $type,
                'file_id'  => $fileId,
                'media_id' => (string) ($message[$type]['media_id'] ?? ''),
            ],
            MediaEnum::TEMP_MINI_PROGRAM => [
                'types'        => 'mini_program',
                'title'        => (string) ($message['miniprogram']['title'] ?? ''),
                'file_id'      => $fileId,
                'app_id'       => (string) ($message['miniprogram']['appid'] ?? ''),
                'link'         => (string) ($message['miniprogram']['page'] ?? ''),
                'pic_media_id' => (string) ($message['miniprogram']['pic_media_id'] ?? ''),
            ],
            default => $message,
        };
        $data = array_filter($data, fn ($value) => $value !== '' && $value !== 0);
        if ($file) {
            $data['file'] = $file;
        }

        return $data;
    }

    /**
     * 统一企微接口返回结构.
     * @param mixed $response
     */
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

    /**
     * 从企微返回中提取二维码地址.
     */
    private function responseQrCode(array $response): string
    {
        return (string) ($response['qr_code'] ?? $response['join_way']['qr_code'] ?? $response['contact_way']['qr_code'] ?? '');
    }

    /**
     * 将字符串或数组参数规整为非空数组.
     * @param mixed $value
     */
    private function normalizeList($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        return array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
    }

    private function nullIfEmptyList(array $value): ?array
    {
        return $value === [] ? null : $value;
    }

    private function welcomeInput(array $data): array
    {
        return (array) ($data['welcome'] ?? []);
    }

    /**
     * 规整欢迎语消息配置.
     */
    private function normalizeWelcomeMessages(array $data): array
    {
        $welcome  = $this->welcomeInput($data);
        $messages = [];
        $text     = trim((string) ($welcome['content'] ?? $data['welcome_text'] ?? ''));
        if ($text !== '') {
            $messages[] = [
                'msgtype' => MediaEnum::TEMP_TEXT,
                'text'    => ['content' => $text],
            ];
        }

        foreach ($this->normalizeWelcomeAttachList((array) ($welcome['attach'] ?? [])) as $message) {
            $normalized = $this->normalizeWelcomeMessage($message);
            if ($normalized) {
                $messages[] = $normalized;
            }
        }

        if (empty($welcome['attach']) && ! empty($data['welcome_messages'])) {
            foreach ($this->normalizeWelcomeAttachList((array) $data['welcome_messages']) as $message) {
                $normalized = $this->normalizeWelcomeMessage($message);
                if ($normalized) {
                    $messages[] = $normalized;
                }
            }
        }

        $uniqueMessages = [];
        $exists         = [];
        foreach ($messages as $message) {
            $key = md5(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (isset($exists[$key])) {
                continue;
            }
            $exists[$key]     = true;
            $uniqueMessages[] = $message;
        }

        return $uniqueMessages;
    }

    /**
     * 规整欢迎语附件列表，按 file_id 补齐素材文件信息.
     */
    private function normalizeWelcomeAttachList(array $attach): array
    {
        $fileIds = $this->collectWelcomeFileIds($attach);
        $fileMap = $this->getWelcomeFileMap($fileIds);

        return collect($attach)->map(function ($item) use ($fileMap) {
            $item   = (array) $item;
            $fileId = $this->welcomeMessageFileId($item);
            if ($fileId > 0 && isset($fileMap[$fileId])) {
                $item['file_id']   = $fileId;
                $item['file_info'] = $fileMap[$fileId];
            }
            return $item;
        })->all();
    }

    /**
     * 规整单条欢迎语消息.
     * @param mixed $message
     */
    private function normalizeWelcomeMessage($message): array
    {
        $message = is_object($message) && method_exists($message, 'toArray') ? $message->toArray() : (array) $message;
        $type    = (string) ($message['msgtype'] ?? $message['type'] ?? $message['types'] ?? '');
        if (in_array($type, ['mini_program', 'miniprogram'], true)) {
            $type = MediaEnum::TEMP_MINI_PROGRAM;
        }
        if (! in_array($type, [
            MediaEnum::TEMP_TEXT,
            MediaEnum::TEMP_IMAGE,
            MediaEnum::TEMP_LINK,
            MediaEnum::TEMP_VIDEO,
            MediaEnum::TEMP_FILE,
            MediaEnum::TEMP_MINI_PROGRAM,
        ], true)) {
            return [];
        }

        return match ($type) {
            MediaEnum::TEMP_TEXT         => $this->normalizeTextWelcomeMessage($message),
            MediaEnum::TEMP_IMAGE        => $this->normalizeImageWelcomeMessage($message),
            MediaEnum::TEMP_LINK         => $this->normalizeLinkWelcomeMessage($message),
            MediaEnum::TEMP_VIDEO        => $this->normalizeMediaWelcomeMessage($message, MediaEnum::TEMP_VIDEO),
            MediaEnum::TEMP_FILE         => $this->normalizeMediaWelcomeMessage($message, MediaEnum::TEMP_FILE),
            MediaEnum::TEMP_MINI_PROGRAM => $this->normalizeMiniProgramWelcomeMessage($message),
            default                      => [],
        };
    }

    private function normalizeTextWelcomeMessage(array $message): array
    {
        $content = (string) ($message['text']['content'] ?? $message['content'] ?? '');
        $content = trim(htmlspecialchars_decode($content));
        return $content === '' ? [] : [
            'msgtype' => MediaEnum::TEMP_TEXT,
            'text'    => ['content' => $content],
        ];
    }

    private function normalizeImageWelcomeMessage(array $message): array
    {
        $image   = (array) ($message['image'] ?? []);
        $file    = $this->normalizeWelcomeFileData($this->resolveWelcomeFileInfo($message, MediaEnum::TEMP_IMAGE));
        $fileId  = (int) ($message['file_id'] ?? $image['file_id'] ?? $file['id'] ?? 0);
        $picUrl  = (string) ($image['pic_url'] ?? $image['picurl'] ?? $message['pic_url'] ?? $message['url'] ?? $message['file_url'] ?? $file['file_url'] ?? '');
        $mediaId = (string) ($image['media_id'] ?? $message['media_id'] ?? $file['media_id'] ?? '');
        if ($picUrl === '' && $mediaId === '') {
            return [];
        }

        $image = [];
        if ($picUrl !== '') {
            $image['pic_url'] = link_file($picUrl);
        }
        if ($mediaId !== '') {
            $image['media_id'] = $mediaId;
        }
        if ($fileId > 0) {
            $image['file_id'] = $fileId;
        }

        $normalized = [
            'msgtype' => MediaEnum::TEMP_IMAGE,
            'image'   => $image,
        ];
        if ($fileId > 0) {
            $normalized['file_id'] = $fileId;
        }
        if ($file) {
            $normalized['file_info'] = $file;
        }

        return $normalized;
    }

    private function normalizeLinkWelcomeMessage(array $message): array
    {
        $link   = (array) ($message['link'] ?? []);
        $file   = $this->normalizeWelcomeFileData($this->resolveWelcomeFileInfo($message, MediaEnum::TEMP_LINK));
        $fileId = (int) ($message['file_id'] ?? $link['file_id'] ?? $file['id'] ?? 0);
        $picUrl = (string) ($link['picurl'] ?? $link['pic_url'] ?? $message['picurl'] ?? $message['pic_url'] ?? $file['file_url'] ?? '');
        $url    = (string) ($link['url'] ?? $message['url'] ?? $message['link'] ?? '');
        $title  = (string) ($link['title'] ?? $message['title'] ?? '');
        if ($url === '' || $title === '') {
            return [];
        }

        $normalized = [
            'msgtype' => MediaEnum::TEMP_LINK,
            'link'    => [
                'title'  => $title,
                'picurl' => $picUrl !== '' ? link_file($picUrl) : '',
                'desc'   => (string) ($link['desc'] ?? $message['desc'] ?? $message['info'] ?? ''),
                'url'    => $url,
            ],
        ];
        if ($fileId > 0) {
            $normalized['file_id']         = $fileId;
            $normalized['link']['file_id'] = $fileId;
        }
        if ($file) {
            $normalized['file_info'] = $file;
        }

        return $normalized;
    }

    private function normalizeMediaWelcomeMessage(array $message, string $type): array
    {
        $payload = (array) ($message[$type] ?? []);
        $file    = $this->normalizeWelcomeFileData($this->resolveWelcomeFileInfo($message, $type));
        $fileId  = (int) ($message['file_id'] ?? $payload['file_id'] ?? $file['id'] ?? 0);
        $mediaId = (string) ($payload['media_id'] ?? $message['media_id'] ?? $file['media_id'] ?? '');
        if ($mediaId === '' && $fileId < 1) {
            return [];
        }

        $data = [];
        if ($mediaId !== '') {
            $data['media_id'] = $mediaId;
        }
        if ($fileId > 0) {
            $data['file_id'] = $fileId;
        }

        $normalized = [
            'msgtype' => $type,
            $type     => $data,
        ];
        if ($fileId > 0) {
            $normalized['file_id'] = $fileId;
        }
        if ($file) {
            $normalized['file_info'] = $file;
        }

        return $normalized;
    }

    private function normalizeMiniProgramWelcomeMessage(array $message): array
    {
        $miniProgram = (array) ($message['miniprogram'] ?? $message['mini_program'] ?? []);
        $file        = $this->normalizeWelcomeFileData($this->resolveWelcomeFileInfo($message, MediaEnum::TEMP_MINI_PROGRAM));
        $fileId      = (int) ($message['file_id'] ?? $miniProgram['file_id'] ?? $file['id'] ?? 0);
        $title       = (string) ($miniProgram['title'] ?? $message['title'] ?? '');
        $picMediaId  = (string) ($miniProgram['pic_media_id'] ?? $message['pic_media_id'] ?? $file['media_id'] ?? '');
        $appId       = (string) ($miniProgram['appid'] ?? $miniProgram['app_id'] ?? $message['appid'] ?? $message['app_id'] ?? '');
        $page        = (string) ($miniProgram['page'] ?? $message['page'] ?? $message['link'] ?? '');
        if ($title === '' || $appId === '' || $page === '' || ($picMediaId === '' && $fileId < 1)) {
            return [];
        }

        $data = [
            'title' => $title,
            'appid' => $appId,
            'page'  => $page,
        ];
        if ($picMediaId !== '') {
            $data['pic_media_id'] = $picMediaId;
        }
        if ($fileId > 0) {
            $data['file_id'] = $fileId;
        }

        $normalized = [
            'msgtype'     => 'miniprogram',
            'miniprogram' => $data,
        ];
        if ($fileId > 0) {
            $normalized['file_id'] = $fileId;
        }
        if ($file) {
            $normalized['file_info'] = $file;
        }

        return $normalized;
    }

    private function welcomeMessageToAttachment(array $message, string $clientName): array
    {
        $type = (string) ($message['msgtype'] ?? '');
        if ($type === MediaEnum::TEMP_TEXT) {
            return [];
        }
        if ($type === MediaEnum::TEMP_LINK && isset($message['link'])) {
            $message['link']['title'] = $this->replaceClientNameVariables((string) ($message['link']['title'] ?? ''), $clientName);
            $message['link']['desc']  = $this->replaceClientNameVariables((string) ($message['link']['desc'] ?? ''), $clientName);
        }
        if ($type === 'miniprogram' && isset($message['miniprogram'])) {
            $message['miniprogram']['title'] = $this->replaceClientNameVariables((string) ($message['miniprogram']['title'] ?? ''), $clientName);
        }

        unset($message['file_id'], $message['file_info']);

        foreach ([MediaEnum::TEMP_IMAGE, MediaEnum::TEMP_VIDEO, MediaEnum::TEMP_FILE, 'miniprogram'] as $field) {
            if (isset($message[$field]['file_id'])) {
                unset($message[$field]['file_id']);
            }
        }
        if (isset($message['link']['file_id'])) {
            unset($message['link']['file_id']);
        }

        if (in_array($type, [MediaEnum::TEMP_VIDEO, MediaEnum::TEMP_FILE], true) && empty($message[$type]['media_id'])) {
            return [];
        }
        if ($type === 'miniprogram' && empty($message['miniprogram']['pic_media_id'])) {
            return [];
        }

        return $message;
    }

    private function hydrateWelcomeMessageFiles(array $messages): array
    {
        $fileMap = $this->getWelcomeFileMap($this->collectWelcomeFileIds($messages));
        if (! $fileMap) {
            return $messages;
        }

        return collect($messages)->map(function ($message) use ($fileMap) {
            $message = (array) $message;
            $fileId  = $this->welcomeMessageFileId($message);
            if ($fileId > 0 && isset($fileMap[$fileId])) {
                $message['file_info'] = $fileMap[$fileId];
            }
            return $message;
        })->all();
    }

    private function collectWelcomeFileIds(array $messages): array
    {
        return collect($messages)
            ->map(fn ($message) => $this->welcomeMessageFileId((array) $message))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function welcomeMessageFileId(array $message): int
    {
        $type = (string) ($message['msgtype'] ?? $message['type'] ?? $message['types'] ?? '');
        if (in_array($type, ['mini_program', 'miniprogram'], true)) {
            $type = MediaEnum::TEMP_MINI_PROGRAM;
        }

        $fileInfo = (array) ($message['file_info'] ?? []);
        $file     = (array) ($message['file'] ?? []);
        $payload  = (array) ($message[$type] ?? []);
        $link     = (array) ($message['link'] ?? []);

        return (int) (
            $message['file_id']
            ?? $message['attach_id']
            ?? $payload['file_id']
            ?? $payload['attach_id']
            ?? $link['file_id']
            ?? $link['attach_id']
            ?? $fileInfo['id']
            ?? $fileInfo['attach_id']
            ?? $file['id']
            ?? $file['attach_id']
            ?? 0
        );
    }

    private function getWelcomeFileMap(array $fileIds): array
    {
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if (! $fileIds) {
            return [];
        }

        return WorkMedia::query()
            ->whereIn('id', $fileIds)
            ->get()
            ->keyBy('id')
            ->map(fn (WorkMedia $file) => $this->normalizeWelcomeFileData($file->toArray()))
            ->all();
    }

    private function resolveWelcomeFileInfo(array $message, string $type): array
    {
        if (! empty($message['file_info'])) {
            return (array) $message['file_info'];
        }

        $file = (array) ($message['file'] ?? []);
        if ($type !== MediaEnum::TEMP_FILE) {
            return $file;
        }

        return array_intersect_key($file, array_flip(['id', 'attach_id', 'file_name', 'real_name', 'name', 'file_url', 'url', 'file_size', 'size', 'media_id'])) ? $file : [];
    }

    private function normalizeWelcomeFileData(array $file): array
    {
        if (! $file) {
            return [];
        }

        $fileUrl = (string) ($file['file_url'] ?? $file['url'] ?? '');
        return array_filter([
            'id'        => (int) ($file['id'] ?? $file['attach_id'] ?? 0),
            'file_name' => (string) ($file['file_name'] ?? $file['real_name'] ?? $file['name'] ?? ''),
            'file_url'  => $fileUrl !== '' ? link_file($fileUrl) : '',
            'file_size' => (int) ($file['file_size'] ?? $file['size'] ?? 0),
            'media_id'  => (string) ($file['media_id'] ?? ''),
        ], fn ($value) => $value !== '' && $value !== 0);
    }

    private function formatWelcomeFileForResponse(array $message): array
    {
        $file    = $this->normalizeWelcomeFileData((array) ($message['file_info'] ?? []));
        $fileId  = $this->welcomeMessageFileId($message);
        $type    = (string) ($message['msgtype'] ?? $message['types'] ?? '');
        $payload = (array) ($message[$type] ?? []);
        $url     = (string) ($file['file_url'] ?? $message['image']['pic_url'] ?? $message['link']['picurl'] ?? $payload['pic_url'] ?? $message['file_url'] ?? $message['url'] ?? '');

        return array_filter([
            'id'        => (int) ($file['id'] ?? $fileId),
            'attach_id' => (int) ($file['id'] ?? $fileId),
            'name'      => (string) ($file['file_name'] ?? $message['file_name'] ?? $message['name'] ?? ''),
            'real_name' => (string) ($file['file_name'] ?? $message['file_name'] ?? $message['name'] ?? ''),
            'url'       => $url !== '' ? link_file($url) : '',
            'src'       => $url !== '' ? link_file($url) : '',
            'size'      => (int) ($file['file_size'] ?? 0),
        ], fn ($value) => $value !== '' && $value !== 0);
    }

    private function syncWelcomeMediaLinks(int $channelId, array $fileIds): void
    {
        app()->get(WorkMediaService::class)->setLink($fileIds, $channelId, MediaEnum::LINK_TYPE_CHANNEL_CODE);
    }

    /**
     * 替换欢迎语中的客户名称变量.
     */
    private function replaceClientNameVariables(string $content, string $clientName): string
    {
        if ($content === '') {
            return '';
        }

        return str_replace(self::CLIENT_NAME_VARIABLES, $clientName, $content);
    }

    /**
     * 判断欢迎语是否包含客户名称变量，只有包含时才查询 work_client.
     */
    private function welcomeMessageHasClientNameVariable(WorkChannelCode $channel): bool
    {
        $messages = $this->normalizeWelcomeMessages([
            'welcome_text'     => (string) $channel->welcome_text,
            'welcome_messages' => $channel->welcome_messages ?: [],
        ]);

        foreach ($messages as $message) {
            foreach ($this->clientNameVariableContents((array) $message) as $content) {
                if ($this->hasClientNameVariable($content)) {
                    return true;
                }
            }
        }

        return $this->hasClientNameVariable((string) $channel->welcome_text);
    }

    private function clientNameVariableContents(array $message): array
    {
        $type = (string) ($message['msgtype'] ?? '');
        return match ($type) {
            MediaEnum::TEMP_TEXT => [(string) ($message['text']['content'] ?? '')],
            MediaEnum::TEMP_LINK => [
                (string) ($message['link']['title'] ?? ''),
                (string) ($message['link']['desc'] ?? ''),
            ],
            MediaEnum::TEMP_MINI_PROGRAM, 'miniprogram' => [(string) ($message['miniprogram']['title'] ?? '')],
            default => [],
        };
    }

    private function hasClientNameVariable(string $content): bool
    {
        foreach (self::CLIENT_NAME_VARIABLES as $variable) {
            if ($content !== '' && str_contains($content, $variable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 从企微客户表获取客户名称.
     */
    private function syncAndGetClientName(string $corpId, string $externalUserid, string $userid): string
    {
        if ($externalUserid === '') {
            return '';
        }

        $client = $this->getClient($corpId, $externalUserid, $userid);
        if (! $client || trim((string) $client->name) === '') {
            $client = app()->get(WorkClientService::class)->saveOrUpdateClient($corpId, $externalUserid, $userid, false);
            if (! $client) {
                $client = $this->getClient($corpId, $externalUserid, $userid);
            }
        }

        return trim((string) ($client->name ?? ''));
    }

    private function getClient(string $corpId, string $externalUserid, string $userid): ?WorkClient
    {
        $query = WorkClient::query()
            ->where('corp_id', $corpId)
            ->where('external_userid', $externalUserid);
        if ($userid !== '') {
            $query->where('userid', $userid);
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * 将系统员工 ID 或企微 userid 统一解析为企微成员 userid.
     *
     * 关联：admin.id -> admin.work_member_id -> work_member.id -> work_member.userid
     * 解析顺序：
     * 1. 先按 work_member.userid 直接匹配（兼容已传企微 userid，含纯数字 userid）
     * 2. 未命中再按 admin.id 查绑定的 work_member
     */
    private function adminIdsToWorkUserids(array $userids, string $corpId = ''): array
    {
        $identifiers = collect($this->normalizeList($userids))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        if ($identifiers === []) {
            return [];
        }

        $resolved = $this->mapWorkUseridsByMemberUserid($identifiers, $corpId);

        $unresolved = array_values(array_diff($identifiers, array_keys($resolved)));
        if ($unresolved !== []) {
            foreach ($this->mapWorkUseridsByAdminIds($unresolved, $corpId) as $identifier => $workUserid) {
                $resolved[$identifier] = $workUserid;
            }
        }

        return collect($identifiers)
            ->map(fn (string $identifier) => $resolved[$identifier] ?? '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 按 work_member.userid 批量解析.
     */
    private function mapWorkUseridsByMemberUserid(array $identifiers, string $corpId): array
    {
        $where = ['userid' => array_values(array_unique($identifiers))];
        if ($corpId !== '') {
            $where['corp_id'] = $corpId;
        }

        $userids = app()->get(WorkMemberService::class)->column($where, 'userid', 'userid') ?: [];

        return collect($userids)
            ->mapWithKeys(fn ($userid, $key) => [(string) $key => trim((string) $userid)])
            ->filter()
            ->all();
    }

    /**
     * 按 admin.id 批量解析绑定的 work_member.userid.
     */
    private function mapWorkUseridsByAdminIds(array $identifiers, string $corpId): array
    {
        $adminIds = array_values(array_unique(array_filter(array_map(
            static fn (string $identifier) => ctype_digit($identifier) ? (int) $identifier : 0,
            $identifiers
        ))));

        if ($adminIds === []) {
            return [];
        }

        $admins = app()->get(AdminService::class)->select(
            ['id' => $adminIds, 'is_work' => 1],
            ['id', 'work_member_id'],
            with: ['member']
        )?->toArray() ?? [];

        $resolved = [];
        foreach ($admins as $admin) {
            $member = $admin['member'] ?? null;
            if (! is_array($member) || ($member['userid'] ?? '') === '') {
                continue;
            }
            if ($corpId !== '' && (string) ($member['corp_id'] ?? '') !== $corpId) {
                continue;
            }
            $resolved[(string) $admin['id']] = trim((string) $member['userid']);
        }

        return $resolved;
    }

    /**
     * 将客户标签 ID 转换为企微标签 ID，兼容已传入的企微标签 ID.
     */
    private function labelIdsToWorkTagIds(array $tagIds): array
    {
        $tagIds = $this->normalizeList($tagIds);
        if (! $tagIds) {
            return [];
        }

        $labelIds       = [];
        $legacyWorkTags = [];
        foreach ($tagIds as $tagId) {
            $tagId = trim((string) $tagId);
            if ($tagId === '') {
                continue;
            }
            if (ctype_digit($tagId)) {
                $labelIds[] = (int) $tagId;
            } else {
                $legacyWorkTags[] = $tagId;
            }
        }

        $workTagIds = $legacyWorkTags;
        if ($labelIds) {
            $workTagIds = array_merge($workTagIds, app()->get(LabelService::class)->column(['id' => array_values(array_unique($labelIds))], 'work_tag_id') ?? []);
        }

        return collect($workTagIds)
            ->map(fn ($tagId) => trim((string) $tagId))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    private function normalizeBooleanInt($value): int
    {
        return in_array($value, [1, '1', true, 'true', 'on'], true) ? 1 : 0;
    }

    /**
     * 规整邀请加群开关.
     */
    private function normalizeInviteGroup(array $data): int
    {
        return $this->normalizeBooleanInt($data['invite_group'] ?? 1);
    }

    /**
     * 规整客户添加确认方式.
     */
    private function normalizeSkipVerify(array $data): int
    {
        $verifyType = $data['verify_type'] ?? null;
        if ($verifyType !== null && $verifyType !== '') {
            return in_array($verifyType, ['direct', 'direct_pass', 'skip', '1', 1], true) ? 1 : 0;
        }
        return (int) ($data['skip_verify'] ?? 1);
    }
}
