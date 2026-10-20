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

namespace App\Constants;

final class KnowledgeEnum
{
    /**
     * 内部知识库.
     */
    public const SPACE_INTERNAL = 1;

    /**
     * 外部知识库.
     */
    public const SPACE_EXTERNAL = 2;

    /**
     * 全员可见.
     */
    public const VISIBILITY_ALL = 'all';

    /**
     * 自定义可见.
     */
    public const VISIBILITY_CUSTOM = 'custom';

    /**
     * 自动分块（知识库默认切分策略 / 分块来源=自动切分）.
     */
    public const CHUNK_AUTO = 1;

    /**
     * 手动分块（知识库手动模式 / 分块来源=用户手动新增）.
     */
    public const CHUNK_MANUAL = 2;

    /**
     * 禁用状态（知识库/分块 enabled=关闭）.
     */
    public const STATUS_DISABLED = 0;

    /**
     * 启用状态（知识库/分块 enabled=开启）.
     */
    public const STATUS_ENABLED = 1;

    /**
     * 文档待处理.
     */
    public const DOCUMENT_PENDING = 0;

    /**
     * 文档处理中.
     */
    public const DOCUMENT_PROCESSING = 1;

    /**
     * 文档已完成.
     */
    public const DOCUMENT_DONE = 2;

    /**
     * 文档处理失败.
     */
    public const DOCUMENT_FAILED = 3;

    /**
     * 分块待向量化.
     */
    public const CHUNK_PENDING = 0;

    /**
     * 分块已完成.
     */
    public const CHUNK_DONE = 1;

    /**
     * 分块处理失败.
     */
    public const CHUNK_FAILED = 2;

    /**
     * 知识库问答来源.
     */
    public const SOURCE_KNOWLEDGE_QA = 'knowledge_qa';

    /**
     * AI 会话来源.
     */
    public const SOURCE_AI_CONVERSATION = 'ai_conversation';

    /**
     * 智能客服来源.
     */
    public const SOURCE_CUSTOMER_SERVICE = 'customer_service';

    /**
     * 默认分块长度.
     */
    public const DEFAULT_CHUNK_SIZE = 800;

    /**
     * 默认重叠长度.
     */
    public const DEFAULT_CHUNK_OVERLAP = 100;

    /**
     * 单文件最大尺寸.
     */
    public const MAX_FILE_SIZE = 20971520;

    /**
     * 单知识库最大文档数.
     */
    public const MAX_DOCUMENTS = 500;

    /**
     * 单次上传最大附件数.
     */
    public const MAX_UPLOAD_ATTACH_IDS = 20;

    /**
     * 单文档最大分块数.
     */
    public const MAX_CHUNKS_PER_DOCUMENT = 2000;

    /**
     * 当前支持解析的文件类型.
     */
    public const PARSEABLE_TYPES = ['txt', 'md', 'docx', 'xlsx', 'pptx', 'pdf'];

    /**
     * 知识库检索缓存标签（Redis tag）.
     */
    public const CACHE_TAG_SEARCH = 'knowledge_search';

    /**
     * 检索结果缓存秒数.
     */
    public const SEARCH_CACHE_TTL = 300;

    /**
     * 查询向量缓存秒数.
     */
    public const EMBEDDING_CACHE_TTL = 3600;

    /**
     * 评测用例草稿（待人工补黄金切片）.
     */
    public const EVAL_CASE_DRAFT = 0;

    /**
     * 评测用例已确认，可参与离线跑批.
     */
    public const EVAL_CASE_CONFIRMED = 1;

    /**
     * 评测用例停用，不参与跑批.
     */
    public const EVAL_CASE_DISABLED = 2;

    /**
     * 评测运行待执行.
     */
    public const EVAL_RUN_PENDING = 0;

    /**
     * 评测运行进行中.
     */
    public const EVAL_RUN_RUNNING = 1;

    /**
     * 评测运行已完成.
     */
    public const EVAL_RUN_DONE = 2;

    /**
     * 评测运行失败.
     */
    public const EVAL_RUN_FAILED = 3;

    /**
     * 评测用例来源：人工/JSON 导入.
     */
    public const EVAL_SOURCE_MANUAL = 'manual';

    /**
     * 评测用例来源：问答日志草稿.
     */
    public const EVAL_SOURCE_QUESTION_LOG = 'question_log';

    /**
     * 知识库检索默认 TopK.
     */
    public const DEFAULT_TOP_K = 5;

    /**
     * 知识库检索默认相似度阈值.
     */
    public const DEFAULT_SCORE_THRESHOLD = 0.55;

    /** 读取系统默认相似度阈值（config 优先）. */
    public static function defaultScoreThreshold(): float
    {
        $value = config('knowledge.retrieve.score_threshold', self::DEFAULT_SCORE_THRESHOLD);

        return max(0.0, min(1.0, (float) $value));
    }
}
