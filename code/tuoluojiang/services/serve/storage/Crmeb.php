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

namespace Tuoluojiang\Open\services\serve\storage;

use Tuoluojiang\Open\services\BaseStorage;
use Tuoluojiang\Open\services\AccessTokenServeService;
use Illuminate\Validation\ValidationException;

/**
 * Class Crmeb.
 */
class Crmeb extends BaseStorage
{
    protected $accessToken;

    /**
     * Crmeb constructor.
     */
    public function __construct(string $name, AccessTokenServeService $service, ?string $configFile = null)
    {
        $this->accessToken = $service;
    }

    /**
     * 获取用户信息.
     * @return array|mixed
     */
    public function getUser()
    {
        return $this->accessToken->httpRequest('user/info');
    }

    /**
     * 用户登录.
     * @return array|mixed
     */
    public function login(string $account, string $secret)
    {
        return $this->accessToken->httpRequest('user/login', ['account' => $account, 'secret' => $secret], 'POST', false);
    }

    /**
     * 注册平台.
     * @return array|mixed
     */
    public function register(array $data)
    {
        return $this->accessToken->httpRequest('user/register', $data, 'POST', false);
    }

    /**
     * 平台用户消息记录.
     * @return array|mixed
     */
    public function userBill(int $page, int $limit)
    {
        return $this->accessToken->httpRequest('user/bill', ['page' => $page, 'limit' => $limit]);
    }

    /**
     * 找回账号.
     * @return array|mixed
     */
    public function forget(array $data)
    {
        return $this->accessToken->httpRequest('user/forget', $data, 'POST', false);
    }

    /**
     * 修改密码
     * @return array|mixed
     */
    public function modify(array $data)
    {
        return $this->accessToken->httpRequest('user/modify', $data, 'POST', false);
    }

    /**
     * 获取验证码
     * @param mixed $type
     * @return array|mixed
     */
    public function code(string $phone, $type = 0)
    {
        return $this->accessToken->httpRequest('user/code', ['phone' => $phone, 'types' => $type], 'POST', false);
    }

    /**
     * 验证验证码
     * @return array|mixed
     */
    public function checkCode(string $phone, string $verify_code)
    {
        return $this->accessToken->httpRequest('user/code/verify', ['phone' => $phone, 'verify_code' => $verify_code], 'POST', false);
    }

    /**
     * 套餐列表.
     * @param string $type 套餐类型：sms,短信；query,物流查询；dump,电子面单；copy,产品复制
     * @return array|mixed
     */
    public function mealList(string $type)
    {
        return $this->accessToken->httpRequest('meal/list', ['type' => $type]);
    }

    /**
     * 套餐支付.
     * @return array|mixed
     */
    public function payMeal(array $data)
    {
        return $this->accessToken->httpRequest('meal/code', $data);
    }

    /**
     * 用量记录.
     * @param mixed $status
     * @return array|mixed
     */
    public function record(int $page, int $limit, int $type, $status = '')
    {
        $typeContent = [1 => 'sms', 2 => 'expr_dump', 3 => 'expr_query', 4 => 'copy'];
        if (! isset($typeContent[$type])) {
            throw new ValidationException('参数类型不正确');
        }
        $data = ['page' => $page, 'limit' => $limit, 'type' => $typeContent[$type]];
        if ($type == 1 && $status != '') {
            $data['status'] = $status;
        }
        return $this->accessToken->httpRequest('user/record', $data);
    }

    /**
     * 修改手机号.
     * @return array|mixed
     */
    public function modifyPhone(array $data)
    {
        return $this->accessToken->httpRequest('user/modify/phone', $data);
    }

    protected function initialize(array $config)
    {
        // TODO: Implement initialize() method.
    }
}
