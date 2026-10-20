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

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use Alipay\EasySDK\Payment\Common\Models\AlipayTradeFastpayRefundQueryResponse;
use Alipay\EasySDK\Payment\Common\Models\AlipayTradeRefundResponse;
use Alipay\EasySDK\Payment\Wap\Models\AlipayTradeWapPayResponse;
use Tuoluojiang\Open\exceptions\PayException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Url;

/**
 * Class AliPayService.
 */
class AliPayService
{
    public const PAY = ['app', 'h5', 'code'];

    /**
     * 配置.
     * @var array
     */
    protected $config = [
        'appId'              => '',
        'merchantPrivateKey' => '', // 应用私钥
        'alipayPublicKey'    => '', // 支付宝公钥
        'notifyUrl'          => '', // 可设置异步通知接收服务地址
        'encryptKey'         => '', // 可设置AES密钥，调用AES加解密相关接口时需要（可选）
    ];

    /**
     * @var ResponseChecker
     */
    protected $response;

    /**
     * @var string
     */
    protected $payType = 'h5';

    /**
     * @var self
     */
    protected static $instance;

    /**
     * AliPayService constructor.
     */
    protected function __construct(array $config = [])
    {
        if (! $config) {
            $config = [
                'appId'              => sys_config('ali_pay_appid'),
                'merchantPrivateKey' => sys_config('alipay_merchant_private_key'),
                'alipayPublicKey'    => sys_config('alipay_public_key'),
                'notifyUrl'          => sys_config('site_url') . Url::to('/api/pay/notify/alipay'),
            ];
        }
        $this->config = array_merge($this->config, $config);
        $this->initialize();
        $this->response = new ResponseChecker();
    }

    /**
     * 实例化.
     * @return static
     */
    public static function instance(array $config = [])
    {
        if (! self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * 设置调起支付类型.
     * @return $this
     */
    public function setPayType(string $payType)
    {
        if (! in_array($payType, self::PAY)) {
            throw new PayException(__('支付类型错误'));
        }
        $this->payType = $payType;
        return $this;
    }

    /**
     * 创建订单.
     * @param string $title 商品名称
     * @param string $orderId 订单号
     * @param string $totalAmount 支付金额
     * @param string $passbackParams 备注
     * @param string $quitUrl 同步跳转地址
     * @return AlipayTradeWapPayResponse
     */
    public function create(string $title, string $orderId, string $totalAmount, string $passbackParams, string $quitUrl = '', string $siteUrl = '')
    {
        try {
            switch ($this->payType) {
                case 'code':
                    // 二维码支付
                    $result = Factory::payment()->faceToFace()->optional('passback_params', $passbackParams)->precreate($title, $orderId, $totalAmount);
                    break;
                case 'app':
                    // app支付
                    $result = Factory::payment()->app()->optional('passback_params', $passbackParams)->pay($title, $orderId, $totalAmount);
                    break;
                case 'h5':
                    // h5支付
                    $result = Factory::payment()->wap()->optional('passback_params', $passbackParams)->pay($title, $orderId, $totalAmount, $quitUrl, $siteUrl);
                    break;
                default:
                    throw new PayException('支付类型错误');
            }
            if ($this->response->success($result)) {
                return isset($result->body) ? $result->body : $result;
            }
            throw new PayException('失败原因:' . $result->msg . ',' . $result->subMsg);
        } catch (\Exception $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * 订单退款.
     * @param string $outTradeNo 订单号
     * @param string $totalAmount 金额
     * @return AlipayTradeRefundResponse
     */
    public function refund(string $outTradeNo, string $totalAmount)
    {
        try {
            $result = Factory::payment()->common()->refund($outTradeNo, $totalAmount);
            if ($this->response->success($result)) {
                return $result;
            }
            throw new PayException('失败原因:' . $result->msg . ',' . $result->subMsg);
        } catch (\Exception $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * 查询交易退款单号信息.
     * @return AlipayTradeFastpayRefundQueryResponse
     */
    public function queryRefund(string $outTradeNo, string $outRequestNo)
    {
        try {
            $result = Factory::payment()->common()->queryRefund($outTradeNo, $outRequestNo);
            if ($this->response->success($result)) {
                return $result;
            }
            throw new PayException('失败原因:' . $result->msg . ',' . $result->subMsg);
        } catch (\Exception $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * 支付异步回调.
     * @return string
     */
    public static function handleNotify()
    {
        return self::instance()->notify(function ($notify) {});
    }

    /**
     * 异步回调.
     * @return string
     */
    public function notify(callable $notifyFn)
    {
        request()->filter(['trim']);
        $paramInfo = request()->all();
        if (isset($paramInfo['type'])) {
            unset($paramInfo['type']);
        }
        Log::error('支付宝支付回调：' . json_encode($paramInfo));
        // 商户订单号
        $postOrder['out_trade_no'] = $paramInfo['out_trade_no'] ?? '';
        // 支付宝交易号
        $postOrder['trade_no'] = $paramInfo['trade_no'] ?? '';
        // 交易状态
        $postOrder['trade_status'] = $paramInfo['trade_status'] ?? '';
        // 备注
        $postOrder['attach'] = isset($paramInfo['passback_params']) ? urldecode($paramInfo['passback_params']) : '';
        $postOrder['extra']  = isset($paramInfo['extra_common_param']) ? urldecode($paramInfo['extra_common_param']) : '';
        if ($this->verifyNotify($paramInfo)) {
            try {
                if ($notifyFn((object) $postOrder)) {
                    return 'success';
                }
            } catch (\Exception $e) {
                Log::error('支付宝异步会回调成功,执行函数错误。错误单号：' . $postOrder['out_trade_no']);
            }
        }
        return 'fail';
    }

    /**
     * 初始化.
     */
    protected function initialize()
    {
        Factory::setOptions($this->getOptions());
    }

    /**
     * 设置配置.
     * @return Config
     */
    protected function getOptions()
    {
        $options              = new Config();
        $options->protocol    = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType    = 'RSA2';

        $options->appId = $this->config['appId'];
        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = $this->config['merchantPrivateKey'];
        // 注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        $options->alipayPublicKey = $this->config['alipayPublicKey'];
        // 可设置异步通知接收服务地址（可选）
        $options->notifyUrl = $this->config['notifyUrl'];
        // 可设置AES密钥，调用AES加解密相关接口时需要（可选）
        if ($this->config['encryptKey']) {
            $options->encryptKey = $this->config['encryptKey'];
        }

        return $options;
    }

    /**
     * 验签.
     * @return bool
     */
    protected function verifyNotify(array $param)
    {
        try {
            return Factory::payment()->common()->verifyNotify($param);
        } catch (\Exception $e) {
            Log::error('支付宝回调成功,验签发生错误，错误原因:' . $e->getMessage());
        }
        return false;
    }
}
