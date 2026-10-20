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

namespace App\Http\Contract\Common;

interface CommonInterface
{
    /**
     * 图像验证码
     * @return mixed
     */
    public function captcha(): array;

    /**
     * 获得短信发送key.
     */
    public function smsVerifyKey(): array;

    /**
     * 短信验证码
     * @param mixed $phone
     * @param mixed $key
     * @param mixed $types
     */
    public function smsVerifyCode($phone, $key, $types): bool;

    /**
     * 文件上传.
     * @return mixed
     */
    public function uploadFromFile(string $file, array $option): array;

    /**
     * 文件内容上传.
     * @return mixed
     */
    public function uploadFromResource(string $file, array $option): array;

    /**
     * 通过链接上传.
     * @return mixed
     */
    public function uploadFromUrl(string $url, array $option): array;

    /**
     * 城市数据树形结构.
     */
    public function getCityTree(): array;
}
