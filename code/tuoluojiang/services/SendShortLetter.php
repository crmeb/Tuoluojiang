<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services;

use App\Http\Service\Company\CompanyService;
use Tuoluojiang\Open\services\sms\Sms;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * 短信发送
 * Class SendShortLetter.
 */
class SendShortLetter
{
    /**
     * 缓存.
     * @var Cache
     */
    protected $cache;

    /**
     * 验证码长度.
     * @var int
     */
    protected $length = 6;

    /**
     * 缓存前缀
     * @var string
     */
    protected $prefix;

    /**
     * 短信验证码过期时间.
     * @var int
     */
    protected $smsttl = 300;

    /**
     * 配置参数.
     * @var array
     */
    protected $config = [];

    /**
     * 短信发送service.
     * @var Sms
     */
    protected $service;

    protected $entId;

    protected $uniqued = 'd644bd6469684a45bc6f5c23b5fd5dd5';

    /**
     * SendShortLetter constructor.
     */
    public function __construct(array $config = [])
    {
        $this->cache  = app()->cache;
        $this->config = $config;
        $this->initService();
    }

    /**
     * 设置service.
     * @param null $service
     * @return $this
     */
    public function initService($service = null, array $config = [])
    {
        if (! $service) {
            $service = new Sms($this->getConfig());
        }
        if ($config) {
            $this->config = $config;
        }
        $this->service = $service;
        return $this;
    }

    /**
     * 设置短讯验证码过期时间.
     * @return $this
     */
    public function setSmsTtl(int $ttl)
    {
        $this->smsttl = $ttl;
        return $this;
    }

    /**
     * 设置短信企业用户.
     * @return $this
     */
    public function setEnterprise(int $entid)
    {
        $this->entId   = $entid;
        $this->uniqued = app()->get(CompanyService::class)->value($entid, 'uniqued');
        return $this;
    }

    /**
     * 获取短信验证码过期时间.
     * @return int
     */
    public function getSmsttl()
    {
        return $this->smsttl;
    }

    /**
     * 设置缓存前缀
     * @return $this
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 发送验证码
     * @return array|mixed
     */
    public function captcha(string $phone, string $label)
    {
        return $this->service->captcha($phone, $label);
    }

    /**
     * 发送短信
     * @throws ValidationException
     */
    public function send(string $phone, string $template, array $data = [])
    {
        return $this->service->send($phone, $template, $data);
    }

    /**
     * 短信用户信息.
     * @return mixed
     */
    public function info()
    {
        return Cache::remember('sms_user_info' . $this->entId, 30, function () {
            return $this->service->info();
        });
    }

    /**
     * 短信用户信息.
     * @param mixed $page
     * @param mixed $limit
     * @return mixed
     */
    public function record($page, $limit)
    {
        return $this->service->record($page, $limit);
    }

    /**
     * 短信套餐.
     * @return mixed
     */
    public function meals(int $page, int $limit)
    {
        return $this->service->meals($page, $limit);
    }

    /**
     * 短信套餐.
     * @param mixed $uuid
     * @return mixed
     */
    public function payCode(int $mealId, $uuid = '')
    {
        return $this->service->payCode($mealId, $uuid);
    }

    /**
     * 短信套餐.
     * @return mixed
     */
    public function modify(string $sign)
    {
        return $this->service->modify($sign);
    }

    /**
     * 发送短信验证码
     * @return array|mixed
     * @throws ValidationException
     */
    public function sendSms(string $phone, string $template, array $data = [])
    {
        if (! isset($data['code'])) {
            $data['code'] = $this->getCode();
        }
        $res = $this->send($phone, $template, $data);
        if ($res) {
            $this->cache->add($this->prefix . $phone, $data['code'], $this->smsttl);
        }
        return $res;
    }

    /**
     * 验证码验证
     * @return bool
     */
    public function check(string $phone, string $code)
    {
        try {
            $this->service->checkCode($phone, $code);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 获取验证码
     * @return string
     */
    public function getCode()
    {
        $number = [0, 1, 2, 4, 5, 6, 7, 8, 9];
        $code   = [];
        for ($i = 0; $i < $this->length; ++$i) {
            mt_srand();
            $code[] = $number[mt_rand(0, 8)];
        }
        return implode('', $code);
    }

    /**
     * 检测手机号是否发送过短信
     * @return bool
     */
    public function hasCode(string $phone)
    {
        return $this->cache->has($this->prefix . $phone);
    }

    /**
     * 获取配置.
     * @return array
     */
    protected function getConfig()
    {
        return array_merge($this->config, [
            'account' => $this->uniqued,
            'secret'  => md5('crmeb666'),
        ]);
    }
}
