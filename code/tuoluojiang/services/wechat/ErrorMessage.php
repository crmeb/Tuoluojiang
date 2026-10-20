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

/**
 * 错误消息处理
 * Class ErrorMessage.
 */
class ErrorMessage
{
    public const MSG_CODE = [
        '1'       => '未创建直播间',
        '1003'    => '商品id不存在',
        '47001'   => '入参格式不符合规范',
        '200002'  => '入参错误',
        '300001'  => '禁止创建/更新商品 或 禁止编辑&更新房间',
        '300002'  => '名称长度不符合规则',
        '300006'  => '图片上传失败',
        '300022'  => '此房间号不存在',
        '300023'  => '房间状态 拦截',
        '300024'  => '商品不存在',
        '300025'  => '商品审核未通过',
        '300026'  => '房间商品数量已经满额',
        '300027'  => '导入商品失败',
        '300028'  => '房间名称违规',
        '300029'  => '主播昵称违规',
        '300030'  => '主播微信号不合法',
        '300031'  => '直播间封面图不合规',
        '300032'  => '直播间分享图违规',
        '300033'  => '添加商品超过直播间上限',
        '300034'  => '主播微信昵称长度不符合要求',
        '300035'  => '主播微信号不存在',
        '300036'  => '主播微信号未实名认证',
        '300037'  => '购物直播频道封面图不合规',
        '300038'  => '未在小程序管理后台配置客服',
        '9410000' => '直播间列表为空',
        '9410001' => '获取房间失败',
        '9410002' => '获取商品失败',
        '9410003' => '获取回放失败',
        '300003'  => '价格输入不合规',
        '300004'  => '商品名称存在违规违法内容',
        '300005'  => '商品图片存在违规违法内容',
        '300007'  => '线上小程序版本不存在该链接',
        '300008'  => '添加商品失败',
        '300009'  => '商品审核撤回失败',
        '300010'  => '商品审核状态不对',
        '300011'  => '操作非法',
        '300012'  => '没有提审额度',
        '300013'  => '提审失败',
        '300014'  => '审核中，无法删除',
        '300017'  => '商品未提审',
        '300018'  => '图片尺寸不符合要求',
        '300021'  => '商品添加成功，审核失败',
        '40001'   => 'AppSecret错误或者AppSecret不属于这个小程序，请确认AppSecret 的正确性',
        '40002'   => '请确保grant_type字段值为client_credential',
        '40013'   => '不合法的AppID，请检查AppID的正确性，避免异常字符，注意大小写',
        '40125'   => '小程序配置无效，请检查配置',
        '40164'   => 'IP地址不在白名单中，请检查设置',
        '41002'   => '缺少appid参数',
        '41004'   => '缺少secret参数',
        '43104'   => 'appid与openid不匹配',
        '48001'   => '微信接口暂无权限，请先去获取',
        '-1'      => '系统错误',
    ];

    public const WORK_ERROR_MESSAGE = [
        40098 => '成员尚未实名认证',
        40068 => '不合法的标签/标签组ID',
    ];

    /**
     * 处理返回错误信息友好提示.
     * @return array|mixed|string
     */
    public static function getMessage(string $message)
    {
        if (strstr($message, 'Request AccessToken fail') !== false || strstr($message, 'Request access_token fail') !== false) {
            $message = str_replace('Request AccessToken fail. response:', '', $message);
            $message = str_replace('Request access_token fail:', '', $message);
            $message = trim($message);
            $message = json_decode($message, true) ?: [];
            $errcode = $message['errcode'] ?? false;
        }
        return $message;
    }

    /**
     * 解析错误.
     * @return array|mixed|string
     */
    public static function getValidMessgae(\Throwable $e)
    {
        $message = '';
        if (! isset(self::MSG_CODE[$e->getCode()]) && (strstr($e->getMessage(), 'Request AccessToken fail') !== false || strstr($e->getMessage(), 'Request access_token fail') !== false)) {
            $message = str_replace('Request AccessToken fail. response:', '', $e->getMessage());
            $message = str_replace('Request access_token fail:', '', $message);
            $message = trim($message);
            $message = json_decode($message, true) ?: [];
            $errcode = $message['errcode'] ?? false;
            if ($errcode) {
                $message = self::MSG_CODE[$errcode] ?? $message;
            }
        }
        return $message ?: self::MSG_CODE[$e->getCode()] ?? $e->getMessage();
    }

    /**
     * 获取企业微信错误提示.
     * @return null|string
     */
    public static function getWorkMessage(int $errcode, ?string $message = null)
    {
        if (isset(self::WORK_ERROR_MESSAGE[$errcode])) {
            return self::WORK_ERROR_MESSAGE[$errcode];
        }
        return $message;
    }
}
