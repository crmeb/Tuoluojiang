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

use App\Constants\CacheEnum;
use App\Http\Dao\Work\WorkGroupChatDao;
use App\Jobs\Work\WorkSaveClientInfoJob;
use App\Http\Service\WorkExternalContact\WorkChannelCodeService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 群聊
 * Class WorkGroupChatService.
 */
class WorkGroupChatService extends BaseService
{
    /**
     * 构造函数.
     */
    public function __construct(WorkGroupChatDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取群列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelect(array $where, array $field = ['chat_id', 'name', 'owner', 'notice', 'member_num', 'group_create_time', 'retreat_group_num'], string $sort = 'id', array $with = ['admin']): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    /**
     * 同步群信息.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function syncWorkGroupChat()
    {
        $work        = app()->get(Work::class);
        $corp_id     = sys_config('wechat_work_corpid');
        $cacheCursor = Cache::tags([CacheEnum::TAG_OTHER])->get(md5('syncWorkGroupChat'));
        $cursor      = $cacheCursor ?: '';
        try {
            $response = $work->getGroupChats(offset: $cursor);
            Cache::tags([CacheEnum::TAG_OTHER])->set(md5('syncWorkGroupChat'), $response['next_cursor'] ?? '', (int) sys_config('system_cache_ttl', 3600));
            foreach ($response['group_chat_list'] as $item) {
                $info = $work->getGroupChat($item['chat_id']);
                $this->dao->updateOrCreate([
                    'corp_id' => $corp_id,
                    'chat_id' => $item['chat_id'],
                ], [
                    'corp_id'           => $corp_id,
                    'chat_id'           => $item['chat_id'],
                    'name'              => $info['group_chat']['name'],
                    'owner'             => $info['group_chat']['owner'],
                    'admin_list'        => json_encode($info['group_chat']['admin_list'], JSON_UNESCAPED_UNICODE),
                    'group_create_time' => $info['group_chat']['create_time'],
                    'notice'            => $info['group_chat']['notice'] ?? '',
                    'status'            => $info['group_chat']['status'] ?? 0,
                    'member_num'        => count($info['group_chat']['member_list'] ?? []),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('企微同步群信息失败:' . $e->getMessage());
        }
    }

    /**
     * 保存群详情.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveWorkGroupChat(string $corpId, string $chatId)
    {
        $response = app()->get(Work::class)->getGroupChat($chatId);
        if ($response['errcode'] !== 0) {
            throw $this->exception($response['errmsg']);
        }
        $groupInfo               = $response['group_chat'] ?? [];
        $groupInfo['admin_list'] = json_encode(array_column($groupInfo['admin_list'], 'userid'));
        $memberList              = $groupInfo['member_list'] ?? [];
        unset($groupInfo['member_list']);
        $group = $this->dao->get(['corp_id' => $corpId, 'chat_id' => $chatId]);
        return $this->transaction(function () use ($chatId, $corpId, $group, $groupInfo, $memberList) {
            if ($group) {
                $group->name              = $groupInfo['name'];
                $group->owner             = $groupInfo['owner'];
                $group->notice            = $groupInfo['notice'] ?? '';
                $group->group_create_time = $groupInfo['create_time'];
                $group->member_num        = count($memberList);
                $group->save();
            } else {
                $group = $this->dao->create([
                    'corp_id'           => $corpId,
                    'chat_id'           => $chatId,
                    'name'              => $groupInfo['name'],
                    'owner'             => $groupInfo['owner'],
                    'notice'            => $groupInfo['notice'] ?? '',
                    'member_num'        => count($memberList),
                    'group_create_time' => $groupInfo['create_time'],
                    'status'            => $groupInfo['status'] ?? 0,
                ]);
            }
            $this->saveMember($memberList, $group->id, $group->member_num);
            return $group->id;
        });
    }

    /**
     * 保存群成员.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function saveMember(array $memberList, int $groupId, int $sum = 0, bool $plus = false)
    {
        $data = [];

        $chatMemberService = app()->get(WorkGroupChatMemberService::class);
        $newUserIds        = array_column($memberList, 'userid');
        $userids           = $chatMemberService->column(['group_id' => $groupId], 'userid');
        $unUserIds         = array_diff($userids, $newUserIds);

        foreach ($memberList as $item) {
            $item['group_id'] = $groupId;
            $state            = $item['state'] ?? '';
            if (isset($item['state'])) {
                unset($item['state']);
            }
            $item['invitor_userid'] = $item['invitor']['userid'] ?? '';
            $unionid                = $item['unionid'] ?? '';
            unset($item['invitor'], $item['unionid']);
            if ($chatMemberService->count(['group_id' => $groupId, 'userid' => $item['userid']])) {
                $chatMemberService->update(['group_id' => $groupId, 'userid' => $item['userid']], [
                    'type'           => $item['type'],
                    'unionid'        => $unionid,
                    'chat_sum'       => $sum,
                    'status'         => 1,
                    'join_time'      => $item['join_time'],
                    'join_scene'     => $item['join_scene'],
                    'invitor_userid' => $item['invitor_userid'],
                    'group_nickname' => $item['group_nickname'],
                ]);
                if ($item['type'] == 2 && $state) {
                    app()->get(WorkChannelCodeService::class)->markGroupStatusByState($state, $item['userid'], 2);
                }
            } else {
                $item['unionid']    = $unionid;
                $item['chat_sum']   = $sum;
                $item['state']      = $state;
                $item['created_at'] = date('Y-m-d H:i:s');
                $data[]             = $item;
            }
        }
        if ($data) {
            $chatMemberService->insert($data);
            // 如果没有客户信息同步客户信息

            $corpId = app()->get(WorkConfig::class)->getCorpId();
            foreach ($data as $item) {
                if ($item['type'] == 2) {
                    WorkSaveClientInfoJob::dispatch($corpId, $item['userid'], '', true);
                    if (! empty($item['state'])) {
                        app()->get(WorkChannelCodeService::class)->markGroupStatusByState($item['state'], $item['userid'], 2);
                    }
                }
            }
        }
        if ($unUserIds) {
            $leftMembers = $chatMemberService->select([
                'group_id' => $groupId,
                ['userid', 'in', $unUserIds],
            ], ['userid', 'type', 'state']);
            foreach ($leftMembers as $member) {
                if ((int) $member->type === 2 && ! empty($member->state)) {
                    app()->get(WorkChannelCodeService::class)->markGroupStatusByState($member->state, $member->userid, 3);
                }
            }

            $chatMemberService->update([
                ['userid', 'in', $unUserIds],
            ], ['status' => 0]);
            $chatId = $this->dao->value($groupId, 'chat_id');
            app()->get(WorkChannelCodeService::class)->markLeftByChatId((string) $chatId, $unUserIds);
        }
        return true;
    }

    /**
     * 企业微信客户群变动.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateGroupChat(array $payload)
    {
        $corpId = $payload['ToUserName'];
        $chatId = $payload['ChatId'];

        $groupInfo = $this->dao->get(['corp_id' => $corpId, 'chat_id' => $chatId]);
        if (! $groupInfo) {
            $groupId   = $this->saveWorkGroupChat($corpId, $chatId);
            $groupInfo = $this->dao->get($groupId);
        }
        $response = app()->get(Work::class)->getGroupChat($chatId);
        if ($response['errcode'] !== 0) {
            throw $this->exception($response['errmsg'] ?? '企业微信查询群详情失败');
        }
        $groupChat  = $response['group_chat'] ?? [];
        $memberList = $groupChat['member_list'];

        $this->transaction(function () use ($payload, $groupInfo, $groupChat, $memberList) {
            switch ($payload['UpdateDetail']) {
                case 'add_member':
                    $groupInfo->member_num++;
                    $this->saveMember($memberList, $groupInfo->id, $groupInfo->member_num, true);

                    break;
                case 'del_member':
                    $groupInfo->member_num--;
                    ++$groupInfo->retreat_group_num;
                    $this->saveMember($memberList, $groupInfo->id, $groupInfo->member_num, false);
                    break;
                case 'change_owner':
                    $groupInfo->owner = $groupChat['owner'];
                    break;
                case 'change_name':
                    $groupInfo->name = $groupChat['name'];
                    break;
                case 'change_notice':
                    $groupInfo->notice = $groupChat['notice'];
                    break;
            }
            if (! empty($groupChat['admin_list'])) {
                $groupInfo->admin_list = json_encode(array_column($groupChat['admin_list'], 'userid'));
            }
            $groupInfo->save();
        });
    }

    /**
     * 解散客户群.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dismissGroupChat(string $corpId, string $chatId)
    {
        $groupChat = $this->dao->get(['corp_id' => $corpId, 'chat_id' => $chatId], ['id']);
        if (! $groupChat) {
            throw $this->exception('没有查询到群');
        }
        return $this->transaction(function () use ($groupChat) {
            $chatMemberService = app()->get(WorkGroupChatMemberService::class);
            $chatMemberService->delete(['group_id' => $groupChat->id]);
            return $groupChat->delete();
        });
    }
}
