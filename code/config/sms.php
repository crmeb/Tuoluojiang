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
return [
    // 默认短信发送模式
    'default' => 'chuanglan',
    'setting' => [
        // 单个手机每日发送上限
        'maxPhoneCount' => 10,
        // 验证码每分钟发送上线
        'maxMinuteCount' => 2,
        // 单个IP每日发送上限
        'maxIpCount' => 50,
    ],
    // 驱动模式
    'stores' => [
        // 云信
        'yunxin' => [
            // 短信模板id
            'template_id' => [
                // 验证码自定义时效
                'VERIFICATION_CODE_TIME' => 538393,
                // 验证码
                'VERIFICATION_CODE' => 518076,
                // 支付成功
                'PAY_SUCCESS_CODE' => 520268,
                // 发货提醒
                'DELIVER_GOODS_CODE' => 520269,
                // 确认收货提醒
                'TAKE_DELIVERY_CODE' => 520271,
                // 管理员下单提醒
                'ADMIN_PLACE_ORDER_CODE' => 520272,
                // 管理员退货提醒
                'ADMIN_RETURN_GOODS_CODE' => 520274,
                // 管理员支付成功提醒
                'ADMIN_PAY_SUCCESS_CODE' => 520273,
                // 管理员确认收货
                'ADMIN_TAKE_DELIVERY_CODE' => 520422,
                // 改价提醒
                'PRICE_REVISION_CODE' => 528288,
                // 订单未支付
                'ORDER_PAY_FALSE' => 528116,
                // 管理员订单处理
                'ADMIN_ORDER_UID' => 596678,
            ],
        ],
        // 云信
        'chuanglan' => [
            // 短信模板id
            'template_id' => [
                // 验证码自定义时效
                'VERIFICATION_CODE_TIME' => 435250,
                // 验证码
                'VERIFICATION_CODE' => 441495,
                // 支付成功
                'PAY_SUCCESS_CODE' => 440396,
                // 发货提醒
                'DELIVER_GOODS_CODE' => 441596,
                // 确认收货提醒
                'TAKE_DELIVERY_CODE' => 440402,
                // 管理员下单提醒
                'ADMIN_PLACE_ORDER_CODE' => 520272,
                // 管理员退货提醒
                'ADMIN_RETURN_GOODS_CODE' => 520274,
                // 管理员支付成功提醒
                'ADMIN_PAY_SUCCESS_CODE' => 520273,
                // 管理员确认收货
                'ADMIN_TAKE_DELIVERY_CODE' => 520422,
                // 改价提醒
                'PRICE_REVISION_CODE' => 528288,
                // 订单未支付
                'ORDER_PAY_FALSE' => 528116,
            ],
        ],
        // 阿里云
        'aliyun' => [
            'template_id' => [
                // 验证码
                'VERIFICATION_CODE' => '',
                // 支付成功
                'PAY_SUCCESS_CODE' => '',
                // 发货提醒
                'DELIVER_GOODS_CODE' => '',
                // 确认收货提醒
                'TAKE_DELIVERY_CODE' => '',
                // 管理员下单提醒
                'ADMIN_PLACE_ORDER_CODE' => '',
                // 管理员退货提醒
                'ADMIN_RETURN_GOODS_CODE' => '',
                // 管理员支付成功提醒
                'ADMIN_PAY_SUCCESS_CODE' => '',
                // 管理员确认收货
                'ADMIN_TAKE_DELIVERY_CODE' => '',
                // 改价提醒
                'PRICE_REVISION_CODE' => '',
                // 订单未支付
                'ORDER_PAY_FALSE' => '',
            ],
            'sign_name'         => '',
            'access_key_id'     => '',
            'access_key_secret' => '',
            'region_id'         => '',
        ],
    ],
    'verification' => env('VERIFICATION', true),
];
