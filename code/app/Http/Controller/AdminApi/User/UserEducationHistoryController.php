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

namespace App\Http\Controller\AdminApi\User;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\user\EnterpriseUserEducationRequest;
use App\Http\Service\User\UserEducationHistoryService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 个人教育经历
 * Class UserEducationHistoryController.
 */
#[Prefix('ent/user/education')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取个人教育经历列表接口',
    'create'  => '获取个人教育经历创建接口',
    'store'   => '保存个人教育经历接口',
    'edit'    => '修改个人教育经历表单接口',
    'update'  => '修改个人教育经历接口',
    'destroy' => '删除个人教育经历接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class UserEducationHistoryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    /**
     * EducationController constructor.
     */
    public function __construct(UserEducationHistoryService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取保存和修改字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['resume_id', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['school_name', ''],
            ['major', ''],
            ['education', ''],
            ['academic', ''],
            ['remark', ''],
            ['uid', $this->uuid],
        ];
    }

    /**
     * 字段验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserEducationRequest::class;
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['resume_id', ''],
        ];
    }
}
