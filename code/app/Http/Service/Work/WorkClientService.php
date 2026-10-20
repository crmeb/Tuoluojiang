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

namespace App\Http\Service\Work;

use App\Http\Dao\Work\WorkClientDao;
use App\Http\Service\Customer\LeadService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\WorkExternalContact\WorkChannelCodeLogService;
use App\Jobs\Work\WorkClientSaveJob;
use App\Jobs\Work\WorkClientSetLabelJob;
use App\Jobs\Work\WorkSaveClientInfoJob;
use App\Jobs\WorkExternalContact\WorkChannelCodeWelcomeJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\WechatResponse;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *  客户
 * Class WorkClientService.
 * @mixin WorkClientDao
 */
class WorkClientService extends BaseService
{
    /**
     * 构造函数.
     */
    public function __construct(WorkClientDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 自动同步客户.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function authGetExternalcontact(int $page = 1, string $cursor = '')
    {
        /** @var WorkConfig $config */
        $config = app()->get(WorkConfig::class);
        $corpId = $config->getCorpId();
        if (! $corpId) {
            return true;
        }

        $memberService = app()->get(WorkMemberService::class);
        $menmberList   = $memberService->getDataList(where: ['corp_id' => $corpId], field: ['userid'], page: $page, limit: 1);
        // 没有数据就返回成功
        if (! $menmberList) {
            return true;
        }

        $tagRes = Cache::tags(['work'])->remember('work_tag_list_' . $corpId, 3600, function () {
            return app()->get(Work::class)->getCorpTags();
        });
        $tagMap = [];
        foreach ($tagRes['tag_group'] ?? [] as $tagGroupItem) {
            foreach ($tagGroupItem['tag'] ?? [] as $tagItem) {
                $tagMap[$tagItem['id']] = $tagItem + ['group_id' => $tagGroupItem['group_id'], 'group_name' => $tagGroupItem['group_name']];
            }
        }

        $userids = array_column($menmberList, 'userid');
        try {
            $response            = app()->get(Work::class)->getBatchClientList($userids, $cursor, 100);
            $externalContactList = $response['external_contact_list'] ?? [];
        } catch (\Throwable $e) {
            WorkClientSaveJob::dispatch($page + 1);
            return true;
        }

        $externalUserids = []; // 客户信息
        $clueService     = app()->get(LeadService::class);
        $customerService = app()->get(CustomerService::class);
        $this->transaction(function () use ($externalContactList, $corpId, $externalUserids, $tagMap, $clueService, $customerService) {
            $followService = app()->get(WorkClientFollowService::class);
            $tagService    = app()->get(WorkClientFollowTagsService::class);
            foreach ($externalContactList as $item) {
                $externalContact = $item['external_contact'];
                $unionid         = $externalContact['unionid'] ?? '';
                if (isset($externalContact['unionid'])) {
                    unset($externalContact['unionid']);
                }
                $corpName = $corpFullName = $position = '';
                if (isset($externalContact['corp_name'])) {
                    $corpName = $externalContact['corp_name'];
                    unset($externalContact['corp_name']);
                }
                if (isset($externalContact['corp_full_name'])) {
                    $corpFullName = $externalContact['corp_full_name'];
                    unset($externalContact['corp_full_name']);
                }
                if (isset($externalContact['position'])) {
                    $position = $externalContact['position'];
                    unset($externalContact['position']);
                }

                $externalContact['position']         = $position;
                $externalContact['external_profile'] = json_encode($externalContact['external_profile'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $followUserData = [
                    'userid'           => $item['follow_info']['userid'],
                    'remark'           => $item['follow_info']['remark'] ?? '',
                    'description'      => $item['follow_info']['description'] ?? '',
                    'createtime'       => $item['follow_info']['createtime'] ?? '',
                    'remark_corp_name' => $item['follow_info']['remark_corp_name'] ?? '',
                    'remark_mobiles'   => json_encode($item['follow_info']['remark_mobiles'] ?? ''),
                    'add_way'          => $item['follow_info']['add_way'] ?? '',
                    'state'            => $item['follow_info']['state'] ?? '',
                    'oper_userid'      => $item['follow_info']['oper_userid'] ?? '',
                    'created_at'       => date('Y-m-d H:i:s'),
                    'tags'             => [],
                ];

                foreach ($item['follow_info']['tag_id'] ?? [] as $tagId) {
                    $tag                      = $tagMap[$tagId] ?? [];
                    $followUserData['tags'][] = [
                        'group_name' => $tag['group_name'] ?? '',
                        'tag_name'   => $tag['name'] ?? '',
                        'type'       => $tag['type'] ?? 1,
                        'tag_id'     => $tag['id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }

                $externalUserids[]                 = $externalContact['external_userid'];
                $externalUserid                    = $externalContact['external_userid'];
                $externalContact['corp_id']        = $corpId;
                $externalContact['unionid']        = $unionid;
                $externalContact['userid']         = $item['follow_info']['userid'];
                $externalContact['corp_name']      = $corpName;
                $externalContact['corp_full_name'] = $corpFullName;
                if ($id = $this->dao->value(['external_userid' => $externalUserid, 'corp_id' => $corpId, 'userid' => $item['follow_info']['userid']], 'id')) {
                    $clientId = $id;
                    unset($externalContact['external_userid']);
                    $this->dao->update($id, $externalContact);
                    $externalContact['external_userid'] = $externalUserid;
                    $externalContact['createtime']      = $followUserData['createtime'];
                    sys_config('wechat_work_client_radio', 'clue') == 'clue' ? $clueService->saveWorkClue($externalContact) : $customerService->saveWorkCustomer($externalContact);
                } else {
                    $res      = $this->dao->create($externalContact);
                    $clientId = $res->id;
                }

                // 写入其他跟进字段
                $tags = $followUserData['tags'] ?? [];
                unset($followUserData['tags']);
                if ($followId = $followService->value(['client_id' => $clientId], 'id')) {
                    $followService->update($followId, [
                        'remark'           => $followUserData['remark'],
                        'description'      => $followUserData['description'],
                        'createtime'       => $followUserData['createtime'],
                        'remark_corp_name' => $followUserData['remark_corp_name'],
                        'remark_mobiles'   => $followUserData['remark_mobiles'],
                        'add_way'          => $followUserData['add_way'],
                        'oper_userid'      => $followUserData['oper_userid'],
                    ]);
                } else {
                    $followUserData['client_id'] = $clientId;
                    $res                         = $followService->create($followUserData);
                    $followId                    = $res->id;
                }
                // 写入标签
                if (! empty($tags)) {
                    $tagService->delete(['follow_id' => $followId]);
                    foreach ($tags as $tag) {
                        $tag['follow_id'] = $followId;
                        $tagService->create($tag);
                    }
                }
                sys_config('wechat_work_client_radio', 'clue') == 'clue' ? $clueService->saveClueLabel($item['follow_info']['userid'], $externalUserid, $tags ? array_column($tags, 'tag_id') : [], $followUserData['createtime']) : $customerService->saveCustomerLabel($item['follow_info']['userid'], $externalUserid, $tags ? array_column($tags, 'tag_id') : []);
            }
        });

        if (isset($response['next_cursor']) && $response['next_cursor']) {
            WorkClientSaveJob::dispatch($page, $response['next_cursor']);
        } elseif (empty($response['next_cursor'])) {
            WorkClientSaveJob::dispatch($page + 1);
        }

        return true;
    }

    /**
     * 创建客户.
     */
    public function createClient(array $payload)
    {
        $corpId         = $payload['ToUserName']; // 企业id
        $externalUserID = $payload['ExternalUserID']; // 外部企业userid
        $state          = $payload['State'] ?? ''; // 扫码值
        $userId         = $payload['UserID']; // 成员userid

        // 保存客户
        $this->saveOrUpdateClient($corpId, $externalUserID, $userId);
    }

    /**
     * 发送渠道码新客欢迎语.
     */
    public function sendChannelCodeWelcome(array $payload): void
    {
        $state = (string) ($payload['State'] ?? '');
        if ($state && str_starts_with($state, 'channelCode-')) {
            Log::warning('渠道活码欢迎语开始处理', [
                'state'            => $state,
                'userid'           => $payload['UserID'] ?? '',
                'external_userid'  => $payload['ExternalUserID'] ?? '',
                'has_welcome_code' => ! empty($payload['WelcomeCode'] ?? ''),
            ]);
            WorkChannelCodeWelcomeJob::dispatchSync($payload);
        }
    }

    /**
     * 更新客户信息.
     */
    public function updateClient(array $payload)
    {
        $corpId         = $payload['ToUserName'];
        $externalUserID = $payload['ExternalUserID'];
        $userId         = $payload['UserID']; // 成员serid

        $this->saveOrUpdateClient($corpId, $externalUserID, $userId);
    }

    /**
     * 企业成员删除客户.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteClient(array $payload)
    {
        $corpId         = $payload['ToUserName'];
        $externalUserID = $payload['ExternalUserID'];
        $userId         = $payload['UserID']; // 成员serid
        $clientInfo     = $this->dao->get(['external_userid' => $externalUserID, 'corp_id' => $corpId, 'userid' => $userId], ['id']);
        if ($clientInfo) {
            $this->transaction(function () use ($clientInfo, $userId) {
                $this->dao->delete($clientInfo->id);

                $followService = app()->get(WorkClientFollowService::class);
                $followService->update(['client_id' => $clientInfo->id, 'userid' => $userId], ['is_del_user' => 1]);
            });
        }
        app()->get(WorkChannelCodeLogService::class)->markCustomerLost($corpId, $userId, $externalUserID);
        return true;
    }

    /**
     * 客户删除企业微信成员.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteFollowClient(array $payload)
    {
        $corpId         = $payload['ToUserName'];
        $externalUserID = $payload['ExternalUserID'];
        $userId         = $payload['UserID']; // 成员serid
        $clientInfo     = $this->dao->get(['external_userid' => $externalUserID, 'userid' => $userId, 'corp_id' => $corpId], ['id']);
        $followService  = app()->get(WorkClientFollowService::class);
        if ($clientInfo) {
            $followService->update(['client_id' => $clientInfo->id, 'userid' => $userId], ['is_del_user' => 1]);
        }
        app()->get(WorkChannelCodeLogService::class)->markCustomerLost($corpId, $userId, $externalUserID);
        return true;
    }

    /**
     * 更新或者添加客户信息.
     * @param bool $isUpdate 是否需要更新 true 为不更新
     * @return mixed
     */
    public function saveOrUpdateClient(string $corpId, string $externalUserID, string $userId, bool $isUpdate = false)
    {
        $response                            = app()->get(Work::class)->getClientInfo($externalUserID);
        $externalContact                     = $response['external_contact'] ?? [];
        $followUser                          = $response['follow_user'] ?? [];
        if (empty($externalContact['external_userid']) || empty($followUser)) {
            Log::warning('企微客户信息为空，未写入客户表', [
                'corp_id'         => $corpId,
                'external_userid' => $externalUserID,
                'userid'          => $userId,
            ]);
            return 0;
        }
        $externalContact['corp_id']          = $corpId;
        $externalContact['external_profile'] = json_encode($externalContact['external_profile'] ?? []);

        try {
            return $this->transaction(function () use ($externalContact, $followUser, $corpId, $isUpdate, $userId) {
                $followService = app()->get(WorkClientFollowService::class);
                $tagService    = app()->get(WorkClientFollowTagsService::class);
                $clueService   = app()->get(LeadService::class);
                $savedClient    = null;
                foreach ($followUser as $item) {
                    $followUserid = (string) ($item['userid'] ?? '');
                    if ($userId !== '' && $followUserid !== $userId) {
                        continue;
                    }
                    if ($followUserid === '') {
                        continue;
                    }
                    $clientId = $this->dao->value(['external_userid' => $externalContact['external_userid'], 'userid' => $followUserid, 'corp_id' => $corpId], 'id');
                    // 如果存在就不用更新
                    if ($isUpdate) {
                        if ($clientId) {
                            $savedClient = $this->dao->get($clientId);
                        }
                        continue;
                    }
                    $clientData = $externalContact;
                    $clientData['userid'] = $followUserid;
                    if ($clientId) {
                        $this->dao->update($clientId, $clientData);
                    } else {
                        $res                       = $this->dao->create($clientData);
                        $clientId                  = $res->id;
                    }
                    $savedClient = $this->dao->get($clientId);

                    if ($id = $followService->value(['client_id' => $clientId], 'id')) {
                        $followService->update($id, [
                            'remark'           => $item['remark'] ?? '',
                            'description'      => $item['description'] ?? '',
                            'remark_corp_name' => $item['remark_corp_name'] ?? '',
                            'state'            => $item['state'] ?? '',
                            'add_way'          => $item['add_way'] ?? '',
                            'oper_userid'      => $item['oper_userid'] ?? '',
                        ]);
                    } else {
                        $res = $followService->create([
                            'client_id'        => $clientId,
                            'userid'           => $followUserid,
                            'remark'           => $item['remark'] ?? '',
                            'description'      => $item['description'] ?? '',
                            'remark_corp_name' => $item['remark_corp_name'] ?? '',
                            'state'            => $item['state'] ?? '',
                            'createtime'       => $item['createtime'] ?? 0,
                            'remark_mobiles'   => json_encode($item['remark_mobiles'] ?? []),
                            'add_way'          => $item['add_way'] ?? '',
                            'oper_userid'      => $item['oper_userid'] ?? '',
                        ]);
                        $id = $res->id;
                    }
                    $tagService->delete(['follow_id' => $id]);
                    if (! empty($item['tags'])) {
                        foreach ($item['tags'] as $tag) {
                            $tag['follow_id'] = $id;
                            $tagService->create($tag);
                        }
                    }
                    $clueService->saveClueLabel($followUserid, $externalContact['external_userid'], ! empty($item['tags']) ? array_column($item['tags'], 'tag_id') : [], $item['createtime'] ?? 0);
                }
                if (! $savedClient && $userId !== '') {
                    $savedClient = $this->dao->get(['external_userid' => $externalContact['external_userid'], 'userid' => $userId, 'corp_id' => $corpId]);
                }
                if (! $savedClient && $userId === '') {
                    $savedClient = $this->dao->get(['external_userid' => $externalContact['external_userid'], 'corp_id' => $corpId]);
                }
                return $savedClient ?: 0;
            });
        } catch (\Throwable $e) {
            Log::error(json_encode([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace(),
            ], JSON_UNESCAPED_UNICODE));
            return 0;
        }
    }

    /**
     * 异步批量设置标签.
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function synchBatchLabel(array $addTag, array $removeTag, array $userId, array $where, int $isAll = 0)
    {
        if ($isAll) {
            $clientList = $this->dao->getList($where, ['external_userid', 'id', 'unionid', 'uid'], with: ['followOne']);
        } else {
            $clientList = $this->dao->getList(['external_userid' => $userId], ['external_userid', 'id', 'unionid', 'uid'], with: ['followOne']);
        }
        $batchClient = [];
        foreach ($clientList as $item) {
            if (! empty($item['followOne'])) {
                $batchClient[] = [
                    'external_userid' => $item['external_userid'],
                    'userid'          => $item['followOne']['userid'],
                    'add_tag'         => $addTag,
                    'remove_tag'      => $removeTag,
                ];
            }
        }
        if ($batchClient) {
            foreach ($batchClient as $item) {
                WorkClientSetLabelJob::dispatch($item);
            }
        }
        return true;
    }

    /**
     * 设置客户标签.
     * @return false|WechatResponse
     */
    public function setClientMarkTag(array $markTag)
    {
        try {
            $addTag    = array_values(array_unique(array_filter($markTag['add_tag'] ?? [])));
            $removeTag = array_values(array_unique(array_filter($markTag['remove_tag'] ?? [])));

            if (! $addTag && ! $removeTag) {
                Log::warning('跳过修改客户标签：无有效企业微信标签ID', [
                    'userid'          => $markTag['userid'] ?? '',
                    'external_userid' => $markTag['external_userid'] ?? '',
                    'add_tag'         => $markTag['add_tag'] ?? [],
                    'remove_tag'      => $markTag['remove_tag'] ?? [],
                ]);
                return false;
            }

            $response = app()->get(Work::class)->markTags($markTag['userid'], $markTag['external_userid'], $addTag, $removeTag);
            Log::warning('修改客户企业微信标签：接口返回', [
                'userid'          => $markTag['userid'],
                'external_userid' => $markTag['external_userid'],
                'add_tag'         => $addTag,
                'remove_tag'      => $removeTag,
                'errcode'         => $response['errcode'] ?? null,
                'errmsg'          => $response['errmsg'] ?? null,
                'response'        => $response,
            ]);
            $res = new WechatResponse($response);
            // 同步标签后同步用户信息
            /** @var WorkConfig $config */
            $config = app()->get(WorkConfig::class);
            WorkSaveClientInfoJob::dispatch($config->getCorpId(), $markTag['external_userid'], $markTag['userid'], false);
            return $res;
        } catch (\Throwable $e) {
            Log::error('修改客户标签发生错误:' . json_encode([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE));
            return false;
        }
    }
}
