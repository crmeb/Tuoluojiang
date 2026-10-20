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
use App\Http\Service\Company\CompanyUserEducationService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 教育经历
 * Class EducationController.
 */
class EducationController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(CompanyUserEducationService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取保存和修改字段.
     * @return array|\string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['user_id', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['school_name', ''],
            ['major', ''],
            ['education', ''],
            ['academic', ''],
            ['remark', ''],
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
            ['user_id', ''],
        ];
    }
}
