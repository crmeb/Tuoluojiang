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

namespace Tuoluojiang\Open\services\wechat;

use Tuoluojiang\Open\services\wechat\client\work\AuthClient;
use Tuoluojiang\Open\services\wechat\client\work\CheckInClient;
use Tuoluojiang\Open\services\wechat\client\work\ContactWayClient;
use Tuoluojiang\Open\services\wechat\client\work\DepartmentClient;
use Tuoluojiang\Open\services\wechat\client\work\ExternalContactClient;
use Tuoluojiang\Open\services\wechat\client\work\GroupChatClient;
use Tuoluojiang\Open\services\wechat\client\work\MediaClient;
use Tuoluojiang\Open\services\wechat\client\work\MessageClient;
use Tuoluojiang\Open\services\wechat\client\work\MomentClient;
use Tuoluojiang\Open\services\wechat\client\work\SessionClient;
use Tuoluojiang\Open\services\wechat\client\work\UserClient;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\BadResponseException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use EasyWeChat\Work\Application;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\File;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 企业微信
 * Class Work.
 */
class Work extends BaseApplication
{
    public const BASE_WORK_URL = 'https://qyapi.weixin.qq.com';

    protected string $name = 'work';

    protected WorkConfig $config;

    /**
     * @var Application[]
     */
    protected array $application = [];

    protected string $configHandler;

    /**
     * Work constructor.
     */
    public function __construct()
    {
        $this->debug = DefaultConfig::value('logger');
    }

    /**
     * 设置获取配置.
     * @return $this
     */
    public function setConfigHandler(string $handler)
    {
        $this->configHandler = $handler;
        return $this;
    }

    /**
     * @throws BindingResolutionException
     */
    public function instance(): self
    {
        $this->config = app()->get(WorkConfig::class);
        return $this;
    }

    /**
     * @return $this
     */
    public function setConfig(WorkConfig $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * 获取实例化句柄.
     * @throws InvalidArgumentException
     */
    public function application(string $type = WorkConfig::TYPE_USER): Application
    {
        $config = $this->config->all();
        $config = array_merge($config, $this->config->setHandler($this->configHandler)->getAppConfig($type));
        if (! isset($this->application[$type])) {
            $this->application[$type] = new Application($config);
            $this->setRequest($this->application[$type]);
            $this->setCache($this->application[$type]);
        }
        return $this->application[$type];
    }

    /**
     * 服务端.
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function serve(): Response
    {
        $make   = $this->instance();
        $server = $make->application()->getServer();
        $server->with($make->pushMessageHandler);
        $response = $server->serve();
        return response($response->getBody());
    }

    /**
     * 获取应用配置.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2023/10/8
     */
    public function getAppConfig(string $type)
    {
        $instance = $this->instance();
        return $instance->config->setHandler($instance->configHandler)->getAppConfig($type);
    }

    /**
     * 客户.
     * @return ExternalContactClient
     * @throws InvalidArgumentException
     */
    public function externalContact(string $type = WorkConfig::TYPE_USER)
    {
        $client = $this->instance()->application($type)->getClient();

        return new ExternalContactClient($client);
    }

    /**
     * 会话内容存档.
     * @return SessionClient
     */
    public function session()
    {
        return new SessionClient($this->instance()->config);
    }

    /**
     * 授权.
     * @return AuthClient
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function auth(string $type = WorkConfig::TYPE_USER_APP)
    {
        $client = $this->instance()->application($type)->getClient();

        return new AuthClient($client);
    }

    /**
     * 朋友圈.
     * @return MomentClient
     * @throws InvalidArgumentException
     */
    public function moment(string $type = WorkConfig::TYPE_USER_APP)
    {
        $client = $this->instance()->application($type)->getClient();

        return new MomentClient($client);
    }

    /**
     * 群发消息.
     * @return MessageClient
     * @throws InvalidArgumentException
     */
    public function message(string $type = WorkConfig::TYPE_USER_APP)
    {
        $client = $this->instance()->application($type)->getClient();

        return new MessageClient($client);
    }

    /**
     * 客户群聊.
     * @return GroupChatClient
     * @throws InvalidArgumentException
     */
    public function groupChat(string $type = WorkConfig::TYPE_USER)
    {
        $client = $this->instance()->application($type)->getClient();
        return new GroupChatClient($client);
    }

    /**
     * 联系我二维码
     * @return ContactWayClient
     * @throws InvalidArgumentException
     */
    public function contactWay(string $type = WorkConfig::TYPE_USER)
    {
        $client = $this->instance()->application($type)->getClient();

        return new ContactWayClient($client);
    }

    /**
     * 打卡
     * @return CheckInClient
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function checkIn(string $type = WorkConfig::TYPE_USER_APP)
    {
        $client = $this->instance()->application($type)->getClient();

        return new CheckInClient($client);
    }

    /**
     * 创建联系我二维码
     * @return \EasyWeChat\Kernel\HttpClient\Response|ResponseInterface
     * @throws InvalidArgumentException|TransportExceptionInterface*@throws InvalidArgumentException
     */
    public function createQrCode(int $channelCodeId, array $users, bool $skipVerify = true)
    {
        $config = [
            'skip_verify' => $skipVerify,
            'state'       => 'channelCode-' . $channelCodeId,
            'user'        => $users,
        ];

        $response = $this->instance()->contactWay()->create(2, 2, $config);

        $this->logger('创建联系我二维码', $config, $response);

        return $response;
    }

    /**
     * 更新联系我二维码
     * @return \EasyWeChat\Kernel\HttpClient\Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function updateQrCode(int $channelCodeId, array $users, string $wxConfigId, bool $skipVerify = true)
    {
        $config = [
            'skip_verify' => $skipVerify,
            'state'       => 'channelCode-' . $channelCodeId,
            'user'        => $users,
        ];

        $response = $this->instance()->contactWay()->update($wxConfigId, $config);

        $this->logger('更新联系我二维码', compact('config', 'wxConfigId'), $response);

        return $response;
    }

    /**
     * 删除联系我二维码
     * @return \EasyWeChat\Kernel\HttpClient\Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function deleteQrCode(string $wxConfigId)
    {
        $response = $this->instance()->contactWay()->delete($wxConfigId);

        $this->logger('删除联系我二维码', compact('wxConfigId'), $response);

        return $response;
    }

    /**
     * 获取企业已配置的「联系我」方式.
     */
    public function getContactWayByConfigId(string $wxConfigId)
    {
        $response = $this->instance()->contactWay()->get($wxConfigId);

        $this->logger('获取联系我方式', compact('wxConfigId'), $response);

        return $response;
    }

    /**
     * 创建客户群进群方式.
     */
    public function createGroupJoinWay(string $roomName, array $chatIdList, string $state, int $autoCreateRoom = 1, int $roomBaseId = 1, ?string $remark = null, int $scene = 2)
    {
        $response = $this->instance()->groupChat()->addJoinWay($roomName, $chatIdList, $state, $autoCreateRoom, $roomBaseId, $remark, $scene);

        $this->logger('创建客户群进群方式', compact('roomName', 'chatIdList', 'state', 'autoCreateRoom', 'roomBaseId', 'remark', 'scene'), $response);

        return $response;
    }

    /**
     * 更新客户群进群方式.
     */
    public function updateGroupJoinWay(string $configId, string $roomName, array $chatIdList, string $state, int $autoCreateRoom = 1, int $roomBaseId = 1, ?string $remark = null, int $scene = 2)
    {
        $response = $this->instance()->groupChat()->updateJoinWay($configId, $roomName, $chatIdList, $state, $autoCreateRoom, $roomBaseId, $remark, $scene);

        $this->logger('更新客户群进群方式', compact('configId', 'roomName', 'chatIdList', 'state', 'autoCreateRoom', 'roomBaseId', 'remark', 'scene'), $response);

        return $response;
    }

    /**
     * 获取客户群进群方式.
     */
    public function getGroupJoinWay(string $configId)
    {
        $response = $this->instance()->groupChat()->getJoinWay($configId);

        $this->logger('获取客户群进群方式', compact('configId'), $response);

        return $response;
    }

    /**
     * 删除客户群进群方式.
     */
    public function deleteGroupJoinWay(string $configId)
    {
        $response = $this->instance()->groupChat()->deleteJoinWay($configId);

        $this->logger('删除客户群进群方式', compact('configId'), $response);

        return $response;
    }

    /**
     * 添加企业群发消息模板
     * @return array|mixed[]|WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function addMsgTemplate(array $msg)
    {
        $response = $this->instance()->message()->submit($msg);

        $this->logger('添加企业群发消息模板', compact('msg'), $response);

        return $response->toArray();
    }

    /**
     * 添加企业群发消息模板
     * @return WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function cancelMsg(string $msgId)
    {
        $response = $this->instance()->message()->cancel($msgId);

        $this->logger('取消企业群发', compact('msgId'), $response);

        return new WechatResponse($response);
    }

    /**
     * 添加企业群发消息模板
     * @return WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function remind(string $msgId)
    {
        $response = $this->instance()->message()->remind($msgId);

        $this->logger('取消企业群发', compact('msgId'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取群发成员发送任务列表.
     * @return array|mixed[]|WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getGroupmsgTask(string $msgId, ?int $limit = null, ?string $cursor = null)
    {
        $response = $this->instance()->message()->getGroupmsgTask($msgId, $limit, $cursor);

        $this->logger('获取群发成员发送任务列表', compact('msgId', 'limit', 'cursor'), $response);

        return $response->toArray();
    }

    /**
     * 获取企业群发成员执行结果.
     * @return array|mixed[]|WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getGroupmsgSendResult(string $msgId, string $userid, ?int $limit = null, ?string $cursor = null)
    {
        $response = $this->instance()->message()->getGroupmsgSendResult($msgId, $userid, $limit, $cursor);

        $this->logger('获取企业群发成员执行结果', compact('msgId', 'userid', 'limit', 'cursor'), $response);

        return $response->toArray();
    }

    /**
     * 创建发送朋友圈任务
     * @return array|mixed[]|WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function addMomentTask(array $param)
    {
        try {
            $response = $this->instance()->moment()->createTask($param);
        } catch (\Exception $e) {
            throw new WechatException($e->getMessage());
        }

        $this->logger('创建发送朋友圈任务', compact('param'), $response);
        return $response->toArray();
    }

    /**
     * 取消发送朋友圈任务
     * @return WechatResponse
     * @throws TransportExceptionInterface
     */
    public function cancelMomentTask(string $momentId)
    {
        try {
            $response = $this->instance()->moment()->cancelTask($momentId);
        } catch (\Exception $e) {
            throw new WechatException($e->getMessage());
        }

        $this->logger('取消发送朋友圈任务', compact('momentId'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取发送朋友圈任务创建结果.
     * @return array|mixed[]|WechatResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMomentTask(string $jobId)
    {
        try {
            $response = $this->instance()->moment()->getTask($jobId);
        } catch (\Exception $e) {
            throw new WechatException($e->getMessage());
        }
        $this->logger('获取发送朋友圈任务创建结果', compact('jobId'), $response);

        return $response->toArray();
    }

    /**
     * 获取客户朋友圈企业发表的列表.
     * @return array
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMomentTaskInfo(string $momentId, string $cursor = '', int $limit = 500)
    {
        $response = $this->instance()->moment()->getTasks($momentId, $cursor, $limit);

        //        $this->logger('获取客户朋友圈企业发表的列表', compact('momentId', 'cursor', 'limit'), $response);

        return $response->toArray();
    }

    /**
     * 获取客户朋友圈发表后的可见客户列表.
     * @return array|mixed[]
     * @throws BadResponseException
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMomentSendResult(string $momentId, string $userId, string $cursor = '', int $limit = 500)
    {
        $response = $this->instance()->moment()->getSendResult($momentId, $userId, $cursor, $limit);

        //        $this->logger('获取客户朋友圈发表后的可见客户列表', compact('momentId', 'cursor', 'limit'), $response);

        return $response->toArray();
    }

    /**
     * 获取客户朋友圈的互动数据.
     * @return array|mixed[]
     * @throws BadResponseException
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMomentComments(string $momentId, string $userId)
    {
        $response = $this->instance()->moment()->getComments($momentId, $userId);

        $this->logger('获取客户朋友圈的互动数据', compact('momentId'), $response);

        return $response->toArray();
    }

    /**
     * 获取客户朋友圈发表时选择的可见范围.
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws BadResponseException
     * @throws DecodingExceptionInterface
     */
    public function getMomentCustomerList(string $momentId, string $userId, string $cursor, int $limit = 500)
    {
        $response = $this->instance()->moment()->getCustomers($momentId, $userId, $cursor, $limit);

        $this->logger('获取客户朋友圈发表时选择的可见范围', compact('momentId', 'cursor', 'userId', 'limit'), $response);

        return $response->toArray();
    }

    /**
     * 发送应用消息.
     * @return WechatResponse
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function sendMessage(array $message)
    {
        $instance = $this->instance();

        if (empty($message['agentid'])) {
            $config = $instance->getAppConfig(WorkConfig::TYPE_USER_APP);

            if (empty($config['agent_id'])) {
                throw new WechatException('请先配置agent_id');
            }

            $message['agentid'] = $config['agent_id'];
        }

        $messageClient = new MessageClient($instance->application(WorkConfig::TYPE_USER_APP)->getClient());
        $response      = $messageClient->send($message);

        $this->logger('发送应用消息', compact('message'), $response);

        return new WechatResponse($response);
    }

    /**
     * @email 136327134@qq.com
     * @date 2023/9/18
     */
    public function getDepartment(): array
    {
        try {
            $response = $this->department()->simpleList();

            $this->logger('获取部门列表', [], $response);

            $response               = $response->toArray();
            $response['department'] = $response['department_id'] ?? [];
            return $response;
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取子部门ID列表.
     * @email 136327134@qq.com
     * @date 2023/9/18
     */
    public function simpleList(): array
    {
        try {
            $response = $this->department()->simpleList();

            $this->logger('获取子部门ID列表', [], $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取成员ID列表.
     * @email 136327134@qq.com
     * @date 2022/10/9
     */
    public function getUserListIds(int $limit, string $cursor = ''): array
    {
        try {
            $response = $this->department()->getUserListIds($limit, $cursor);

            $this->logger('获取成员ID列表', [], $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取部门详细信息.
     * @return array
     * @email 136327134@qq.com
     * @date 2022/10/10
     */
    public function getDepartmentInfo(int $id)
    {
        try {
            $response = $this->department()->get($id);

            $this->logger('获取部门详细信息', [], $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取部门成员详细信息.
     * @return array
     */
    public function getDetailedDepartmentUsers(int $departmentId)
    {
        try {
            $response = $this->user()->getDetailedDepartmentUsers($departmentId, true);

            $this->logger('获取部门成员详细信息', compact('departmentId'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取部门成员id.
     * @return array|mixed[]
     */
    public function getUserListId(string $cursor = '', int $limit = 100)
    {
        try {
            $response = $this->user(WorkConfig::TYPE_ADDRESS)->getUserListId($cursor, $limit);

            $this->logger('获取部门成员id', compact('cursor', 'limit'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取通讯录成员详情.
     * @return array
     */
    public function getMemberInfo(string $userId)
    {
        try {
            $response = $this->user()->get($userId);

            $this->logger('获取通讯录成员详情', compact('userId'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * userid转openid.
     */
    public function useridByOpenid(string $userId): ?string
    {
        try {
            $response = $this->user()->userIdToOpenid($userId);

            $this->logger('userid转openid', compact('userId'), $response);

            return $response['openid'] ?? null;
        } catch (\Throwable $e) {
            $this->error($e);

            return null;
        }
    }

    /**
     * 获取某个成员下的客户信息.
     */
    public function getClientInfo(string $externalUserID): array
    {
        try {
            $response = $this->instance()->externalContact()->get($externalUserID);

            $this->logger('获取某个成员下的客户信息', compact('externalUserID'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 批量获取客户详情.
     */
    public function getBatchClientList(array $userids, string $cursor = '', int $limit = 50): array
    {
        if ($limit > 100) {
            $limit = 100;
        }
        try {
            $response = $this->instance()->externalContact()->batchGet($userids, $cursor, $limit);

            $this->logger('批量获取客户详情', compact('userids', 'cursor', 'limit'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取客户标签.
     * @return array
     */
    public function getCorpTags(array $tagIds = [], array $groupIds = [])
    {
        try {
            $response = $this->instance()->externalContact()->getCorpTags($tagIds, $groupIds);

            $this->logger('获取客户标签', compact('tagIds', 'groupIds'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 添加客户标签.
     */
    public function addCorpTag(string $groupName, array $tag = [], string $groupId = ''): array
    {
        $params = [
            'tag' => $tag,
        ];
        if ($groupId) {
            $params['group_id'] = $groupId;
        } else {
            $params['group_name'] = $groupName;
        }
        try {
            $response = $this->instance()->externalContact()->addCorpTag($params);

            $this->logger('添加客户标签', compact('groupName', 'groupId', 'tag'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 编辑客户标签或者标签组.
     * @param string $id 标签id或者标签组id
     */
    public function updateCorpTag(string $id, string $name, int $order = 1): array
    {
        try {
            $response = $this->instance()->externalContact()->updateCorpTag($id, $name, $order);

            $this->logger('编辑客户标签', compact('id', 'name', 'order'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 删除客户标签.
     */
    public function deleteCorpTag(array $tagId, array $groupId = []): array
    {
        try {
            $response = $this->instance()->externalContact()->deleteCorpTag($tagId, $groupId);

            $this->logger('删除客户标签', compact('tagId', 'groupId'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 编辑客户标签.
     */
    public function markTags(string $userid, string $externalUserid, array $addTag = [], array $removeTag = []): array
    {
        $params = [
            'userid'          => $userid,
            'external_userid' => $externalUserid,
            'add_tag'         => $addTag,
            'remove_tag'      => $removeTag,
        ];
        try {
            $response = $this->instance()->externalContact()->markTags($params);

            $this->logger('编辑客户标签', compact('params'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取客户群列表.
     */
    public function getGroupChats(array $useridList = [], int $limit = 100, ?string $offset = null): array
    {
        $params = [
            'status_filter' => 0,
            'owner_filter'  => [
                'userid_list' => $useridList,
            ],
            'limit' => $limit,
        ];

        if ($offset) {
            $params['cursor'] = $offset;
        }

        try {
            $response = $this->instance()->externalContact()->getGroupChats($params);

            //            $this->logger('获取客户群列表', compact('params'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);
            throw new WechatException($e->getMessage());
        }
    }

    /**
     * 获取群详情.
     */
    public function getGroupChat(string $chatId): array
    {
        try {
            $response = $this->instance()->externalContact()->getGroupChat($chatId);

            //            $this->logger('获取群详情', compact('chatId'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取群聊数据统计
     */
    public function groupChatStatisticGroupByDay(int $dayBeginTime, int $dayEndTime, array $userIds): array
    {
        try {
            $response = $this->instance()->externalContact()->groupChatStatisticGroupByDay($dayBeginTime, $dayEndTime, $userIds);

            $this->logger('获取群聊数据统计', compact('dayBeginTime', 'dayEndTime', 'userIds'), $response);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 发送欢迎语.
     * @return WechatResponse
     */
    public function sendWelcome(string $welcomeCode, array $message)
    {
        $response = $this->instance()->message(WorkConfig::TYPE_USER)->sendWelcome($welcomeCode, $message);

        $this->logger('发送欢迎语', compact('welcomeCode', 'message'), $response);

        return new WechatResponse($response);
    }

    /**
     * 上传临时素材.
     * @return array|mixed[]
     */
    public function mediaUpload(string $path, string $type = 'image'): array
    {
        try {
            $response = $this->instance()->media()->upload($type, $path);
            $this->logger('上传临时素材', compact('type', 'path'), $response);
            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);
            return ['errcode' => -1, 'errmsg' => $e->getMessage()];
        }
    }

    /**
     * 分片上传临时素材.
     * @return array|mixed[]
     */
    public function mediaUploadUrl(string $mediaType, string $filename, string $url, string $md5): array
    {
        try {
            $response = $this->instance()->media()->uploadByUrl($mediaType, $filename, $url, $md5);
            $this->logger('分片上传临时素材任务', compact('mediaType', 'filename', 'url', 'md5'), $response);
            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);
            return ['errcode' => -1, 'errmsg' => $e->getMessage()];
        }
    }

    /**
     * 分片上传临时素材.
     * @return array|mixed[]
     */
    public function mediaUploadResult(string $jobId): array
    {
        try {
            $response = $this->instance()->media()->uploadResult($jobId);
            $this->logger('分片上传临时素材结果', compact('jobId'), $response);
            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);
            return ['errcode' => -1, 'errmsg' => $e->getMessage()];
        }
    }

    /**
     * 上传临时附件.
     * @return array|mixed[]
     */
    public function attachUpload(string $path, string $mediaType = 'image', string $attachmentType = '1'): array
    {
        try {
            $response = $this->instance()->media()->uploadAttachment($path, $mediaType, $attachmentType);
            $this->logger('上传临时附件', compact('mediaType', 'path', 'attachmentType'), $response);
            return $response->toArray();
        } catch (\Throwable $e) {
            $this->error($e);
            return ['errcode' => -1, 'errmsg' => $e->getMessage()];
        }
    }

    /**
     * 上传附件资源.
     * @return WechatResponse
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     */
    public function mediaUploadAttachment(string $path, string $mediaType = 'image', string $attachmentType = '1')
    {
        if (in_array($mediaType, ['video', 'file', 'voice'])) {
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/media/upload_attachment';
            $url .= '?access_token=' . $this->instance()->application()->getAccessToken()->getToken();
            $url .= '&media_type=' . $mediaType . '&attachment_type=' . $attachmentType;

            $pathAtt  = explode('/', $path);
            $filename = $pathAtt[count($pathAtt) - 1];
            $file     = new File($path);

            $request     = new Request('POST', $url, ['Content-Type' => 'multipart/form-data']);
            $fileuploade = new UploadedFile($filename, $file->getSize(), $file->getOwner(), $path, $file->getMimeType());
            $res         = $request->withBody($fileuploade->getStream());

            $response = json_decode($res->getBody()->getContents(), true);
        } else {
            $response = $this->instance()->media()->uploadAttachment($path, $mediaType, $attachmentType);
        }

        $this->logger('上传附件资源', compact('path', 'mediaType', 'attachmentType'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取临时素材.
     * @return \EasyWeChat\Kernel\HttpClient\Response
     * @throws BindingResolutionException
     * @throws TransportExceptionInterface
     */
    public function getMedia(string $mediaId)
    {
        return $this->instance()->media()->get($mediaId);
    }

    /**
     * 获取jsSDK权限配置.
     * @return array|object
     */
    public function getJsSDK(string $url = '')
    {
        try {
            $application = $this->instance()->application(WorkConfig::TYPE_USER_APP);

            $utils = $application->getUtils();

            return $utils->buildJsSdkConfig(
                url: $url,
                jsApiList: ['getCurExternalContact', 'getCurExternalChat', 'getContext', 'chooseImage', 'openUserProfile', 'openEnterpriseChat','sendChatMessage', 'sendExternalMsg'],
                openTagList: [],
                debug: true,
                beta: true
            );
        } catch (\Throwable $e) {
            $this->error($e);

            return [];
        }
    }

    /**
     * 获取应用配置信息.
     * @throws InvalidArgumentException
     */
    public function getAgentConfig(?string $url = null): array
    {
        $instance = $this->instance();
        $config = $instance->getAppConfig(WorkConfig::TYPE_USER_APP);
        $corpId = $instance->config->getCorpId();

        if (empty($corpId)) {
            throw new WechatException('请先配置企业微信企业ID');
        }

        if (! preg_match('/^ww/i', (string) $corpId)) {
            throw new WechatException('企业微信企业ID配置无效，应填写ww开头的CorpID');
        }

        if (empty($config['agent_id'])) {
            throw new WechatException('请先配置企业微信自建应用AgentId');
        }

        try {
            $cacheKey = 'work.agent_config.' . md5(json_encode([
                'corp_id'  => $corpId,
                'agent_id' => $config['agent_id'],
                'secret'   => $config['secret'] ?? '',
                'url'      => $url ?? '',
            ], JSON_THROW_ON_ERROR));

            return Cache::remember($cacheKey, 5400, function () use ($instance, $config, $url) {
                $application = $instance->application(WorkConfig::TYPE_USER_APP);

                return $application->getUtils()->buildJsSdkAgentConfig(
                    agentId: (int) $config['agent_id'],
                    url: $url,
                    jsApiList: ['getCurExternalContact', 'getCurExternalChat', 'getContext', 'chooseImage', 'openUserProfile', 'openEnterpriseChat', 'sendChatMessage', 'sendExternalMsg'],
                    openTagList: [],
                    debug: true
                );
            });
        } catch (\Throwable $e) {
            $this->error($e);

            throw new WechatException('获取企业微信应用配置失败：' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取打卡记录.
     * @return WechatResponse
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     */
    public function getCheckInData(string $start, string $end, array|string $userId, int $type = 3)
    {
        $response = $this->instance()->checkIn()->checkInData($start, $end, $userId, $type);
        $this->logger('获取打卡记录', compact('start', 'end', 'userId', 'type'), $response);
        return new WechatResponse($response);
    }

    /**
     * @return UserClient
     * @throws InvalidArgumentException
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    protected function user(string $type = WorkConfig::TYPE_USER_APP)
    {
        $client = $this->instance()->application($type)->getClient();

        return new UserClient($client);
    }

    /**
     * @throws InvalidArgumentException
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    protected function department(string $type = WorkConfig::TYPE_ADDRESS): DepartmentClient
    {
        $client = $this->instance()->application($type)->getClient();

        return new DepartmentClient($client);
    }

    protected function media(string $type = WorkConfig::TYPE_USER_APP)
    {
        $client = $this->instance()->application($type)->getClient();

        return new MediaClient($client);
    }
}
