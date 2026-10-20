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

namespace App\Support;

/**
 * 开源版授权兼容桩（原 commercial BaseAuth 能力已拆分，不做证书校验）。
 */
final class OpenAuth
{
    public static $code = 'open-source';

    private $request;

    public function __construct($request = null)
    {
        $this->request = $request ?? request();
    }

    public static function __callStatic($name, $arguments)
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new self();
        }
        $instance->request = request();

        return call_user_func_array([$instance, $name], $arguments);
    }

    public function __call($name, $arguments)
    {
        return match ($name) {
            'checkAuthDecrypt' => true,
            'getAuth'          => app('json')->success(['status' => 1, 'authCode' => 'open-source', 'day' => 99999]),
            'syncAuthInfo'     => app('json')->success('开源版无需同步商业授权'),
            'authCopyright'    => app('json')->success(['status' => 1, 'copyright' => '']),
            'syncStatus'       => true,
            '_kcvi7e7a4'       => true,
            'authorizationDecryptCrmeb' => true,
            'sdGte3cdDs2'      => null,
            default            => throw new \BadMethodCallException('The method of execution does not exist'),
        };
    }
}
