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
use App\Http\Requests\enterprise\user\EnterpriseUserWorkRequest;
use App\Http\Service\User\UserWorkHistoryService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 工作经历
 * Class WorkController.
 */
#[Prefix('ent/user/work')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取个人工作经历列表接口',
    'create'  => '获取个人工作经历创建接口',
    'store'   => '保存个人工作经历接口',
    'edit'    => '修改个人工作经历表单接口',
    'update'  => '修改个人工作经历接口',
    'destroy' => '删除个人工作经历接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class UserWorkHistoryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(UserWorkHistoryService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取请求数据.
     */
    protected function getRequestFields(): array
    {
        return [
            ['resume_id', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['company', ''],
            ['position', ''],
            ['describe', ''],
            ['quit_reason', ''],
            ['uid', $this->uuid],
        ];
    }

    /**
     * 设置数据验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserWorkRequest::class;
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['resume_id', 0],
        ];
    }
}
