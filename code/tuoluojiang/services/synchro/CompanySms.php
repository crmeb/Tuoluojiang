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

namespace Tuoluojiang\Open\services\synchro;

use Tuoluojiang\Open\exceptions\ApiException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 短信服务
 * Class CompanySms.
 */
class CompanySms extends TokenService
{
    /**
     * 修改签名.
     */
    public const SMS_MODIFY = '/api/v2/sms/modify';

    /**
     * 上传营业执照.
     */
    public const SMS_UPLOAD = '/api/v2/sms/upload';

    /**
     * 用户信息.
     */
    public const SMS_INFO = '/api/v2/sms/info';

    /**
     * 发送短信
     */
    public const SMS_SEND = '/api/v2/sms/send';

    public const SMS_TEMPS = '/api/v2/sms/temps';

    /**
     * 短信套餐.
     */
    public const SMS_MEALS = '/api/v2/sms/meal';

    /**
     * 付款码
     */
    public const SMS_PAY_CODE = '/api/v2/sms/code';

    /**
     * 发送记录.
     */
    public const SMS_RECORD = '/api/v2/sms/record';

    /**
     * 获取短信发送状态
     */
    public const SMS_STSTUS = '/api/v2/sms/status';

    /**
     * 获取短信发送状态
     */
    public const COMMON_CAPTCH = '/api/v2/sms/captcha';

    /**
     * 验证码长度.
     */
    protected int $length = 6;

    /**
     * 短信验证码过期时间.
     */
    protected int $smsttl = 180;

    /**
     * 短信签名.
     */
    protected string $sign = '';

    /**
     * 短信标签.
     */
    protected string $label = '';

    protected string $prefix = '';

    protected $cache;

    /**
     * 模板id.
     * @var array
     */
    protected mixed $templateIds = [];

    private string $templateCode;

    /**
     * CompanySms constructor.
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->templateIds = Config::get('sms.stores.chuanglan.template_id', []);
        $this->cache       = app()->cache;
    }

    public function setConfig(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function setTemplateCode(string $templateCode): static
    {
        $this->templateCode = $templateCode;
        return $this;
    }

    /**
     * 设置缓存前缀
     * @return $this
     */
    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 设置签名.
     * @return $this
     */
    public function setSign($sign): static
    {
        $this->sign = $sign;
        return $this;
    }

    /**
     * 获取验证码
     * @return array|mixed
     * @throws ValidationException
     */
    public function captcha(string $phone): mixed
    {
        $data = [
            'code' => $this->getCode(),
            'time' => 3,
        ];
        $res = $this->send($phone, (string) $this->templateIds['VERIFICATION_CODE_TIME'], $data);
        if ($res) {
            $this->cache->add($this->prefix . $phone, $data['code'], $this->smsttl);
        }
        return $res;
    }

    /**
     * 获取公共验证码
     * @return array|mixed
     */
    public function commonCaptcha(string $phone): mixed
    {
        return $this->httpRequest(self::COMMON_CAPTCH, compact('phone'), isHeader: false);
    }

    /**
     * 获取验证码
     */
    public function getCode(): string
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
     * 验证验证码
     * @throws InvalidArgumentException
     */
    public function checkCode(string $phone, $code): bool
    {
        return $this->cache->has($this->prefix . $phone) && $this->cache->get($this->prefix . $phone) == $code;
    }

    /**
     * 修改签名.
     * @return array|mixed
     */
    public function modify(string $sign, $license): mixed
    {
        $param = [
            'sign'    => $sign,
            'license' => $license,
        ];
        return $this->httpRequest(self::SMS_MODIFY, $param);
    }

    /**
     * 上传营业执照.
     * @return array|mixed
     */
    public function upload(string $image): mixed
    {
        return $this->httpRequest(self::SMS_UPLOAD, compact('image'));
    }

    /**
     * 获取用户信息.
     * @return array|bool|mixed
     * @throws InvalidArgumentException
     */
    public function info(): mixed
    {
        return $this->httpRequest(self::SMS_INFO);
    }

    /**
     * 获取短信模板
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function temps(int $page = 0, int $limit = 10, int $type = 1): mixed
    {
        $param = [
            'page'      => $page,
            'limit'     => $limit,
            'temp_type' => $type,
        ];
        return $this->httpRequest(self::SMS_TEMPS, $param);
    }

    /**
     * 获取短信套餐.
     * @return array|mixed
     */
    public function meals(int $page = 0, int $limit = 20): mixed
    {
        $param = [
            'page'  => $page,
            'limit' => $limit,
        ];
        return $this->httpRequest(self::SMS_MEALS, $param);
    }

    /**
     * 获取套餐付款码
     * @return array|mixed
     */
    public function payCode(int $mealId, $uuid): mixed
    {
        $param = [
            'meal_id' => $mealId,
            'uuid'    => $uuid,
        ];
        return $this->httpRequest(self::SMS_PAY_CODE, $param);
    }

    /**
     * 发送短信
     * @return array|mixed
     */
    public function send(string $phone, string $templateId, array $data = []): mixed
    {
        if (! $phone) {
            throw new ApiException('手机号不能为空');
        }
        $param = [
            'phone'   => $phone,
            'temp_id' => $templateId,
        ];

        if (! $param['temp_id']) {
            $param['temp_id'] = $this->templateCode ?: $this->getTemplateCode($templateId);
        }

        if (! $param['temp_id']) {
            throw new ApiException('模版ID不存在');
        }
        $param['temp_param'] = $data;
        return $this->httpRequest(self::SMS_SEND, $param);
    }

    /**
     * 发送记录.
     * @return array|bool|mixed
     * @throws InvalidArgumentException
     */
    public function record($page, $limit): mixed
    {
        $param = [
            'page'  => $page,
            'limit' => $limit,
        ];
        return $this->httpRequest(self::SMS_RECORD, $param);
    }

    /**
     * 获取发送状态
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function getStatus(array $recordIds): mixed
    {
        $data['record_id'] = json_encode($recordIds);
        return $this->httpRequest(self::SMS_STSTUS, $data, isHeader: false);
    }

    /**
     * 检测手机号是否发送过短信
     */
    public function hasCode(string $phone): mixed
    {
        return app()->cache->has($this->prefix . $phone);
    }

    /**
     * 提取模板code.
     */
    protected function getTemplateCode(string $templateId): mixed
    {
        return $this->templateIds[$templateId] ?? null;
    }
}
