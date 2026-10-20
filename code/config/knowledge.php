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

return [
    /*
    |--------------------------------------------------------------------------
    | 向量检索驱动
    |--------------------------------------------------------------------------
    |
    | auto  : 优先 Redis Stack（RediSearch），不可用时对小规模候选集做 MySQL 精确扫描
    | redis : 仅 Redis Stack，不返回错误的 MySQL 随机截断向量结果
    | mysql : 仅 MySQL JSON 线性扫描（不访问向量索引）
    |
    */
    'vector_driver' => env('KNOWLEDGE_VECTOR_DRIVER', 'auto'),

    'fulltext' => [
        'enabled'      => (bool) env('KNOWLEDGE_FULLTEXT_ENABLED', true),
        'driver'       => env('KNOWLEDGE_FULLTEXT_DRIVER', 'redis'),
        'index_prefix' => env('KNOWLEDGE_FULLTEXT_INDEX_PREFIX', 'kb_text'),
        'doc_prefix'   => env('KNOWLEDGE_FULLTEXT_DOC_PREFIX', 'kb:text:'),
    ],

    /** MySQL 向量降级仅对小规模候选集做精确扫描，避免随机截断造成错误命中. */
    'mysql_vector_max_candidates' => (int) env('KNOWLEDGE_MYSQL_VECTOR_MAX_CANDIDATES', 5000),

    /*
    |--------------------------------------------------------------------------
    | Redis 连接名（config/database.php redis.knowledge）
    |--------------------------------------------------------------------------
    |
    | 复用 REDIS_HOST / REDIS_PORT / REDIS_PASSWORD。
    | RediSearch 只能在 DB 0 创建索引，请保持 REDIS_KNOWLEDGE_DB=0，
    | 与业务数据用 kb:chunk: / kb_vec: 前缀隔离；缓存仍用 REDIS_CACHE_DB。
    |
    */
    'redis_connection' => env('KNOWLEDGE_REDIS_CONNECTION', 'knowledge'),

    /*
    |--------------------------------------------------------------------------
    | RediSearch 索引与文档 key 前缀（落在 REDIS_KNOWLEDGE_DB）
    |--------------------------------------------------------------------------
    */
    'index_prefix' => env('KNOWLEDGE_VECTOR_INDEX_PREFIX', 'kb_vec'),

    'doc_prefix' => env('KNOWLEDGE_VECTOR_DOC_PREFIX', 'kb:chunk:'),

    /*
    |--------------------------------------------------------------------------
    | 知识库 RAG 功能开关
    |--------------------------------------------------------------------------
    */
    'features' => [
        'semantic_chunk' => (bool) env('KNOWLEDGE_FEATURE_SEMANTIC_CHUNK', true),
        'query_rewrite'  => (bool) env('KNOWLEDGE_FEATURE_QUERY_REWRITE', false),
        // 默认开启本地词法精排（无外部模型也可抑制无关命中）；关闭则仅 TopK 截断后再做质量过滤.
        'rerank'         => (bool) env('KNOWLEDGE_FEATURE_RERANK', true),
        'strict_prompt'  => (bool) env('KNOWLEDGE_FEATURE_STRICT_PROMPT', true),
        'fact_check'     => (bool) env('KNOWLEDGE_FEATURE_FACT_CHECK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF OCR（多图/扫描件文字识别，需系统 tesseract + poppler）
    |--------------------------------------------------------------------------
    |
    | enabled=true 且服务器已安装 tesseract（含 chi_sim 等语言包）时，
    | 在 pdftotext 之后对 PDF 页面图片做 OCR 并合并进 content_text；未安装则自动跳过.
    |
    */
    'pdf_ocr' => [
        'enabled'           => (bool) env('KNOWLEDGE_PDF_OCR', true),
        'lang'              => env('KNOWLEDGE_PDF_OCR_LANG', 'chi_sim+eng'),
        'dpi'               => (int) env('KNOWLEDGE_PDF_OCR_DPI', 150),
        // 0 表示 OCR 全部页面（每页一图一任务，受单图 job_timeout 约束）.
        'max_pages'         => (int) env('KNOWLEDGE_PDF_OCR_MAX_PAGES', 0),
        // 单次 tesseract/convert 超时（秒）.
        'command_timeout'   => (int) env('KNOWLEDGE_PDF_OCR_COMMAND_TIMEOUT', 30),
        // 单图 OCR 队列任务墙钟超时（秒）；0 表示 command_timeout+5；超时保留原文本与 image_urls.
        'job_timeout'       => (int) env('KNOWLEDGE_PDF_OCR_JOB_TIMEOUT', 0),
        'parse_timeout'     => (int) env('KNOWLEDGE_PDF_PARSE_TIMEOUT', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | 分块 Markdown 内嵌图片 OCR
    |--------------------------------------------------------------------------
    |
    | preprocess：识别前用 ImageMagick 灰度标准化（需 convert；不可用则跳过）.
    | psm：Tesseract 页面分割模式，3=全自动（默认），0=使用引擎默认.
    | queue：OCR 专用队列名；空则走默认队列。专用队列时 worker 需同时监听该队列，
    |        建议 OCR worker 并发 1～2；代码侧另有全局 WithoutOverlapping 互斥.
    |
    */
    'chunk_ocr' => [
        'max_images' => (int) env('KNOWLEDGE_CHUNK_OCR_MAX_IMAGES', 10),
        'preprocess' => (bool) env('KNOWLEDGE_CHUNK_OCR_PREPROCESS', true),
        'psm'        => (int) env('KNOWLEDGE_CHUNK_OCR_PSM', 3),
        'queue'      => env('KNOWLEDGE_OCR_QUEUE', ''),
        /**
         * OCR 文本入向量方式：
         * auto          截图/演示表只写 metadata.ocr_text，正文型 OCR 合并进 content（默认）
         * caption_only  永不合并 OCR 进 content，全部只存 metadata
         * full          旧行为：OCR 一律合并进 content
         */
        'embed_mode' => env('KNOWLEDGE_OCR_EMBED_MODE', 'auto'),
        /** 截图/演示表命中在质量过滤前的分数折减（0.1～1）. */
        'screenshot_score_factor' => (float) env('KNOWLEDGE_OCR_SCREENSHOT_SCORE_FACTOR', 0.85),
    ],

    /*
    |--------------------------------------------------------------------------
    | 检索参数
    |--------------------------------------------------------------------------
    */
    'retrieve' => [
        'coarse_top_n'         => (int) env('KNOWLEDGE_COARSE_TOP_N', 40),
        'rerank_top_k'         => (int) env('KNOWLEDGE_RERANK_TOP_K', 5),
        // 精排关闭时进入阈值过滤前至少保留的条数（避免只留 top_k=5）.
        'no_rerank_keep'       => (int) env('KNOWLEDGE_NO_RERANK_KEEP', 12),
        // 默认 0.55：与 ai.plugins.rag.min_score 对齐；0.7 易导致有粗排无引用.
        'score_threshold'      => (float) env('KNOWLEDGE_SCORE_THRESHOLD', 0.55),
        'min_chunk_chars'      => (int) env('KNOWLEDGE_MIN_CHUNK_CHARS', 4),
        // 相对榜首分差；过小会裁掉次优但仍相关的多文档命中.
        'relative_score_gap'   => (float) env('KNOWLEDGE_RELATIVE_SCORE_GAP', 0.2),
    ],

    /*
    |--------------------------------------------------------------------------
    | 查询改写
    |--------------------------------------------------------------------------
    */
    'rewrite' => [
        'model_id'   => (int) env('KNOWLEDGE_REWRITE_MODEL_ID', 0),
        'timeout_ms' => (int) env('KNOWLEDGE_REWRITE_TIMEOUT_MS', 3000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 重排序（Rerank）
    |--------------------------------------------------------------------------
    */
    'rerank' => [
        'model_id'   => (int) env('KNOWLEDGE_RERANK_MODEL_ID', 0),
        'timeout_ms' => (int) env('KNOWLEDGE_RERANK_TIMEOUT_MS', 5000),
    ],
];
