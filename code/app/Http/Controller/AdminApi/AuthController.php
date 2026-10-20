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

namespace App\Http\Controller\AdminApi;

use App\Constants\CommonEnum;
use App\Http\Requests\ApiRequest;
use App\Http\Controller\BaseController;

/**
 * 基础控制器
 * Class AuthController.
 * @property string $uuid 登录用户UUID
 * @property bool $isEnt 是否登录企业
 * @property array $userInfo 登录工作台用户信息
 * @property int $entId 当前工作台企业ID
 * @property array $entInfo 当前工作台公司信息
 * @property ApiRequest $request
 * @OA\Info(
 *     title="Your API Title",
 *     version="1.0.0",
 *     description="Description of your API"
 * )
 * @mixin BaseController
 */
class AuthController extends BaseController
{
    public string $origin = CommonEnum::ORIGIN_WEB;

    /**
     * @var string[]
     */
    protected $rule = [
        'uuid'     => 'uuId',
        'isEnt'    => 'isEnt',
        'entInfo'  => 'entInfo',
        'entId'    => 'entId',
        'userInfo' => 'userInfo',
    ];
}
