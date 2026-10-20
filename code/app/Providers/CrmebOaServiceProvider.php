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

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory;

/**
 * OA系统自定义服务
 * Class CrmebOaServiceProvider.
 */
class CrmebOaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        /* @var Factory $validator */
        $validator = $this->app['validator'];
        $toString  = static fn (mixed $value): ?string => is_scalar($value) || $value instanceof \Stringable
            ? (string) $value
            : null;
        // Validator extensions
        $validator->extend('captcha_api', function ($attribute, $value, $parameters) use ($toString) {
            $captcha = $toString($value);
            $key     = $toString($parameters[0] ?? null);

            if ($captcha === null || $key === null) {
                return false;
            }

            return captcha_api_check(strtolower($captcha), $key, $parameters[1] ?? 'default');
        });
        // 验证码验证
        $validator->extend('verification_api', function ($attribute, $value, $parameters) use ($toString) {
            $verificationCode = $toString($value);
            $phone            = $toString($parameters[0] ?? null);

            if ($verificationCode === null || $phone === null) {
                return false;
            }

            return verification_api_check($verificationCode, $phone);
        });
        // 时间比较
        $validator->extend('time_contrast_api', function ($attribute, $value, $parameters) use ($toString) {
            $endTime   = $toString($value);
            $startTime = $toString($parameters[0] ?? null);

            if ($endTime === null || $startTime === null) {
                return false;
            }

            return time_contrast_api_check($endTime, $startTime, isset($parameters[1]) ?: false);
        });
        // 密码确认
        $validator->extend('password_confirm_api', function ($attribute, $value, $parameters) use ($toString) {
            $password        = $toString($value);
            $passwordConfirm = $toString($parameters[0] ?? null);

            if ($password === null || $passwordConfirm === null) {
                return false;
            }

            return password_confirm_api_check($password, $passwordConfirm);
        });
    }
}
