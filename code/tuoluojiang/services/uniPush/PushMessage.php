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

namespace Tuoluojiang\Open\services\uniPush;

use Tuoluojiang\Open\services\ConfigService;
use Tuoluojiang\Open\services\uniPush\options\AndroidOptions;
use Tuoluojiang\Open\services\uniPush\options\IosOptions;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * uniPush推送服务
 */
class PushMessage
{
    public const BASE_URL = 'https://restapi.getui.com';

    // 向单个用户推送消息，可根据cid指定用户
    public const PUSH_ONE_CID = 'push/single/cid';

    // 获取推送结果
    public const REPORT_PUSH_TASK = 'report/push/task/';

    // 获取24个小时在线用户数
    public const ONLINE_USER = 'report/online_user';

    // 查询用户状态
    public const USER_STATUS = 'user/status/';

    // 绑定别名
    public const USER_ALIAS = 'user/alias';

    // 设置角标
    public const USER_BADGE = 'user/badge/cid/';

    /**
     * @var AbstractAPI
     */
    protected $abstractAPI;

    private array|ConfigService|int|string $appId;

    private array|ConfigService|int|string $appKey;

    private array|ConfigService|int|string $appSecret;

    private array|ConfigService|int|string $masterSecret;

    private \GTClient $uniPush;

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        $this->appId        = sys_config('uni_push_appid', env('UNI_PUSH_APPID', ''));
        $this->appKey       = sys_config('uni_push_appkey', env('UNI_PUSH_APPKEY', ''));
        $this->appSecret    = sys_config('uni_push_secret', env('UNI_PUSH_SECRET', ''));
        $this->masterSecret = sys_config('uni_push_master_secret', env('UNI_PUSH_MASTER_SECRET', ''));
        $this->uniPush      = app()->make(\GTClient::class, ['domainUrl' => self::BASE_URL, 'appkey' => $this->appKey, 'appId' => $this->appId, 'masterSecret' => $this->masterSecret]);
        $this->abstractAPI  = app(AbstractAPI::class)->setAppId($this->appId)->setAppKey($this->appKey)->setMasterSecret($this->masterSecret);
    }

    /**
     * @param mixed $channel
     * @param mixed $clientId
     * @return mixed|true
     * @throws BindingResolutionException
     */
    public function push(\GTPushMessage $message, \GTNotification $notify, $channel, $clientId)
    {
        if (! $clientId) {
            return true;
        }
        $push = app()->get(\GTPushRequest::class);
        $push->setRequestId($this->getNewRequestId());
        //        $notify->setTitle("设置通知标题");
        //        $notify->setBody("设置通知内容");
        // 点击通知后续动作，目前支持以下后续动作:
        // 1、intent：打开应用内特定页面url：打开网页地址。2、payload：自定义消息内容启动应用。3、payload_custom：自定义消息内容不启动应用。4、startapp：打开应用首页。5、none：纯通知，无后续动作
        //        $notify->setClickType('intent');
        $message->setNotification($notify);
        $push->setPushMessage($message);
        $push->setPushChannel($channel);
        $push->setCid($clientId);
        // 处理返回结果
        return $this->uniPush->pushApi()->pushToSingleByCid($push);
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function task(array $taskid)
    {
        return $this->abstractAPI->parseGet(self::REPORT_PUSH_TASK . implode(',', $taskid));
    }

    /**
     * 获取24个小时在线用户数.
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function onlineUser()
    {
        return $this->abstractAPI->parseGet(self::ONLINE_USER);
    }

    /**
     * 查询用户状态
     * @param mixed ...$clientId
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function userStatus(...$clientId)
    {
        return $this->abstractAPI->parseGet(self::USER_STATUS . implode(',', $clientId));
    }

    /**
     * 绑定别名.
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function userAlias(array $data)
    {
        return $this->abstractAPI->parsePost(self::USER_ALIAS, ['data_list' => $data]);
    }

    /**
     * 设置角标.
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function userBadge(array $clientId, string $badge)
    {
        return $this->abstractAPI->parsePost(self::USER_BADGE . implode(',', $clientId), ['badge' => $badge]);
    }

    /**
     * @return string
     */
    public function getNewRequestId()
    {
        [$msec, $sec] = explode(' ', microtime());
        $msectime     = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        return 'uni' . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));
    }

    /**
     * @return array
     */
    public function setChannel(AndroidOptions $androidOptions, IosOptions $iosOptions)
    {
        return [
            'ios'     => $iosOptions->toArray(),
            'android' => $androidOptions->toArray(),
        ];
    }
}
