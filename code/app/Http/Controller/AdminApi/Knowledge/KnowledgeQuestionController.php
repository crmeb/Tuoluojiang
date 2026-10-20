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

namespace App\Http\Controller\AdminApi\Knowledge;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Knowledge\KnowledgeQuestionRequest;
use App\Http\Service\Knowledge\KnowledgeQuestionService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 知识库问答.
 */
#[Prefix('ent/knowledge/question')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class KnowledgeQuestionController extends AuthController
{
    /**
     * KnowledgeQuestionController constructor.
     */
    public function __construct(KnowledgeQuestionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 知识库问答.
     * @throws BindingResolutionException
     */
    #[Post('/', '知识库问答')]
    public function ask(): mixed
    {
        $request = app(KnowledgeQuestionRequest::class)->scene('ask');
        $request->check();

        return $this->success($this->service->ask($this->request->postMore([
            ['question', ''],
            ['kb_ids', []],
            ['top_k', 5],
        ])));
    }
}
