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

namespace App\Http\Controller\AdminApi\Program;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Program\ProgramVersionService;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 项目版本.
 * Class ProgramVersionController.
 */
#[Prefix('ent/program_version')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ProgramVersionController extends AuthController
{
    use SearchTrait;

    public function __construct(ProgramVersionService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取版本.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('/', '项目版本')]
    public function getVersion(): mixed
    {
        [$programId] = $this->request->getMore([
            ['program_id', 0],
        ], true);
        return $this->success($this->service->getList(['program_id' => (int) $programId]));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     */
    #[Post('{id}', '保存项目版本')]
    public function setVersion($id): mixed
    {
        if (! $id) {
            throw $this->exception(__('common.empty.attrs'));
        }

        [$data] = $this->request->postMore([
            ['data', []],
        ], true);

        $this->service->saveVersion((array) $data, (int) $id);
        return $this->success('common.operation.succ');
    }

    /**
     * 获取下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('select', '项目版本下拉列表')]
    public function select(): mixed
    {
        [$programId] = $this->request->getMore([
            ['program_id', 0],
        ], true);
        return $this->success($this->service->getSelectList($this->uuid, (int) $programId));
    }
}
