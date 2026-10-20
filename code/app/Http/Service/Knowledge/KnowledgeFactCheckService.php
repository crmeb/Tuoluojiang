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

namespace App\Http\Service\Knowledge;

use App\Http\Service\BaseService;

/**
 * 知识库回答事后事实校验服务.
 *
 * 供会话层在答案就绪后调用；features.fact_check 关闭时透传原答案。
 * 本期为预留实现：开启后仍不调用模型、不改写答案，便于后续接入对照 references 的校验。
 * KnowledgeRagService 不调用本服务（生成发生在会话层）。
 */
class KnowledgeFactCheckService extends BaseService
{
    /**
     * 无 DAO 依赖；避免调用 BaseService 带参构造.
     */
    public function __construct() {}

    /**
     * 校验模型回答是否与检索资料一致.
     *
     * @param string $answer 模型生成的回答
     * @param array<int, array<string, mixed>> $references 检索引用
     *
     * @return array{answer: string, adjusted: bool}
     */
    public function verify(string $answer, array $references): array
    {
        if (! (bool) config('knowledge.features.fact_check')) {
            return ['answer' => $answer, 'adjusted' => false];
        }

        // 预留：后续可对照 $references 校正或拒答，当前透传以免打断会话.
        return ['answer' => $answer, 'adjusted' => false];
    }
}
