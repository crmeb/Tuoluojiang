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

use App\Constants\KnowledgeEnum;
use App\Http\Dao\Knowledge\KnowledgeQuestionLogDao;
use App\Http\Service\BaseService;

/**
 * 知识库问答服务.
 */
class KnowledgeQuestionService extends BaseService
{
    /**
     * KnowledgeQuestionService constructor.
     */
    public function __construct(KnowledgeQuestionLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 知识库问答.
     */
    public function ask(array $data): array
    {
        $question = trim((string) $data['question']);
        $hits     = app(KnowledgeSearchService::class)->search($question, (array) ($data['kb_ids'] ?? []), (int) (($data['top_k'] ?? 0) ?: 5));
        $answer   = $hits
            ? '已找到相关资料，请结合引用来源确认：' . mb_substr($hits[0]['content'], 0, 500)
            : '未在已授权知识库中检索到相关内容';

        $this->dao->create([
            'kb_id'            => count((array) ($data['kb_ids'] ?? [])) === 1 ? (int) $data['kb_ids'][0] : 0,
            'app_id'           => 0,
            'chat_record_uuid' => '',
            'uid'              => (int) auth('admin')->id(),
            'question'         => $question,
            'hit_chunks'       => $hits,
            'answer_summary'   => mb_substr($answer, 0, 1000),
            'source'           => KnowledgeEnum::SOURCE_KNOWLEDGE_QA,
        ]);

        return ['answer' => $answer, 'references' => $hits];
    }
}
