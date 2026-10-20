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

namespace App\Http\Controller\AdminApi\Notepad;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\user\UserMemorialCategoryRequest;
use App\Http\Service\Notepad\NotepadCateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;

/**
 * 备忘录分类控制器
 * Class UserMemorialCategoryController.
 */
class NotepadCateController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    /**
     * UserMemorialCategoryController constructor.
     */
    public function __construct(NotepadCateService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    public function createCategory($pid = 0)
    {
        return $this->success($this->service->resourceCreate(['pid' => $pid, 'uid' => $this->uuid]));
    }

    protected function getRequestFields(): array
    {
        return [
            ['path', []],
            ['name', ''],
            ['pid', 0],
            ['uid', $this->uuid],
        ];
    }

    protected function getRequestClassName(): string
    {
        return UserMemorialCategoryRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['uid', $this->uuid],
        ];
    }
}
