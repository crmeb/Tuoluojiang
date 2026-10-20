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

namespace App\Http\Service\Ai;

use App\Constants\Ai\AiApplicationPromptEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Constants\Ai\AiModelEnum;
use App\Constants\KnowledgeEnum;
use App\Http\Dao\Ai\AiApplicationDao;
use App\Http\Dao\Ai\AiModelDao;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiConversation;
use App\Http\Model\Ai\AiConversationMessage;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\Guest\AiGuestAuthService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Knowledge\KnowledgeBaseService;
use App\Http\Service\Knowledge\KnowledgeBindingParser;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AI 应用配置服务：维护应用 CRUD、成员权限、知识库绑定、MCP/SQL 数据源及外部分享与每日使用限制。
 *
 * 另提供快速新增（未发布）与按类型流式生成提示内容（人设/开场白等，SSE 推送、不落库）.
 * 关联插件约束：知识库可绑内部/外部；MCP 按应用类型校验；数据库仅只读查询（SELECT）。
 * 保存只写入 draft_json，不改已发布列与 share_key；发布才把草稿提升为已发布配置并打开状态。
 * 已有 share_key 在保存、发布时都不轮换；外部应用尚无密钥时才生成一次。
 * 正式会话只读已发布列。调试请求（内部预览 JWT / 访客 share_key）有草稿时改读草稿。
 * 详情接口会附带 mcp_services、knowledge 基本信息（不含 MCP config_json），编辑页回填草稿。
 * 调用链：AdminApi 应用管理控制器 → 本服务 → AiApplicationDao；流式对话前由 AiConversationService 调用 checkUsable/checkUsableForGuest 校验可用性。
 */
class AiApplicationService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * 列表查询字段：含计算 auth/usable 所需的 uid/auth_ids/edit_ids，响应前会裁剪.
     */
    private const LIST_FIELDS = [
        'id',
        'name',
        'info',
        'pic',
        'app_type',
        'status',
        'share_key',
        'prologue_text',
        'prologue_list',
        'uid',
        'auth_ids',
        'edit_ids',
    ];

    /**
     * 列表对外响应字段（对齐应用卡片：名称/简介/图标/类型/状态/创建者/分享密钥/开场白/编辑与使用权限）.
     */
    private const LIST_RESPONSE_KEYS = [
        'id',
        'name',
        'info',
        'pic',
        'app_type',
        'status',
        'share_key',
        'prologue_text',
        'prologue_list',
        'uid',
        'user',
        'auth',
        'usable',
    ];

    /**
     * 草稿与发布提升共用的配置字段。不含 status、share_key、share_enabled.
     *
     * @var array<int, string>
     */
    private const DRAFT_FIELDS = [
        'name',
        'info',
        'pic',
        'models_id',
        'enable_thinking',
        'tooltip_text',
        'prologue_text',
        'prologue_list',
        'use_limit',
        'count_number',
        'source_types',
        'tables',
        'content',
        'keyword',
        'data_arrange_text',
        'mcp_json',
        'mcp_service_ids',
        'knowledge_ids',
        'agent_json',
        'json',
        'app_type',
        'auth_ids',
        'edit_ids',
        'sort',
    ];

    /**
     * @param AiApplicationDao $dao 应用数据访问对象
     */
    public function __construct(AiApplicationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 分页获取 AI 应用列表（卡片精简字段），并附加当前用户是否可编辑 / 可使用标识。
     *
     * 非管理员仅返回：本人创建、本人可使用（auth_ids 或已发布外部应用）、本人可编辑（edit_ids）的应用。
     * 支持 app_type（内/外部）、status（发布/未发布）等筛选。
     *
     * @param array<string, mixed> $where 查询条件
     * @param array<int, string> $field 查询字段，默认 LIST_FIELDS
     * @param array<int, string>|string $sort 排序规则
     * @param array<int, string> $with 预加载关联，默认仅 user
     *
     * @return array<string, mixed> 含 list 与 count 的分页数据
     */
    public function getList(array $where, array $field = self::LIST_FIELDS, $sort = ['sort', 'id'], array $with = ['user']): array
    {
        [$page, $limit] = $this->getPageValue();
        $uid            = (int) auth('admin')->id();
        if (! $this->isAdmin()) {
            $where['accessible_uid'] = $uid;
        }
        $list         = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $responseKeys = array_flip(self::LIST_RESPONSE_KEYS);
        foreach ($list as &$item) {
            $item['auth']   = $this->canEdit($item, $uid);
            $item['usable'] = $this->canUse($item, $uid);
            $item           = array_intersect_key($item, $responseKeys);
        }
        unset($item);

        return $this->listData($list, $this->dao->count($where));
    }

    /**
     * 统计绑定指定知识库、且当前登录人可见的 AI 应用数量。
     *
     * 与 getList 共用同一套可见范围（accessible_uid），知识库卡片上的「关联应用」数
     * 才会和详情页「关联应用」页签的条数一致——否则会把无权访问的应用也算进去。
     *
     * @param int $kbId 知识库 ID
     */
    public function countByKnowledge(int $kbId): int
    {
        if ($kbId <= 0) {
            return 0;
        }

        $where = ['knowledge_id' => $kbId];
        if (! $this->isAdmin()) {
            $where['accessible_uid'] = (int) auth('admin')->id();
        }

        return (int) $this->dao->count($where);
    }

    /**
     * 创建 AI 应用并按 source_type 同步插件配置。
     *
     * 未启用知识库时清空知识库关联；MCP/数据库配置在 normalize 中按 source_type 清空。
     *
     * @param array<string, mixed> $data 应用表单数据
     *
     * @return mixed 新建的应用模型
     *
     * @throws \Throwable 事务失败或校验异常
     */
    public function resourceSave(array $data)
    {
        return DB::transaction(function () use ($data) {
            $normalized = $this->normalize($data);

            return $this->dao->create($normalized);
        });
    }

    /**
     * 通过基本信息快速新增未发布的 AI 应用.
     *
     * 写入名称、可选简介与模型、应用类型（默认内部）；status=0；当前用户写入 edit_ids，仅内部应用写入 auth_ids.
     *
     * @param array{name?: string, info?: string, models_id?: int, app_type?: int} $data 快速创建参数
     *
     * @return mixed 新建的应用模型
     *
     * @throws \Throwable 模型不可用或写入失败
     */
    public function quickCreate(array $data)
    {
        $uid      = (int) auth('admin')->id();
        $modelsId = (int) ($data['models_id'] ?? 0);
        if ($modelsId > 0) {
            $this->resolveUsableModel($modelsId);
        }

        return $this->dao->create($this->normalize([
            'name'      => (string) ($data['name'] ?? ''),
            'info'      => (string) ($data['info'] ?? ''),
            'models_id' => $modelsId,
            'app_type'  => (int) ($data['app_type'] ?? AiConversationEnum::APP_TYPE_INTERNAL),
            'auth_ids'  => [$uid],
            'edit_ids'  => [$uid],
            'status'    => AiConversationEnum::STATUS_DISABLED,
        ]));
    }

    /**
     * 按类型调用指定模型流式生成提示内容（不落库）.
     *
     * 通过 AiStreamService 向前端推送 SSE 正文分片，结束时发出 [DONE].
     *
     * @param int $modelsId 模型配置 ID
     * @param string $content 生成上下文（如应用名称、简介或草稿）
     * @param string $type 生成类型，见 AiApplicationPromptEnum
     *
     * @return array{content: string, status: int} 聚合后的正文与状态（供调用方可选使用）
     *
     * @throws \Throwable 类型非法、模型不可用或流式准备失败
     */
    public function generatePrompt(int $modelsId, string $content, string $type): array
    {
        if (! in_array($type, AiApplicationPromptEnum::TYPES, true)) {
            throw $this->exception('生成类型错误');
        }

        $model = $this->resolveUsableModel($modelsId);

        $option           = app(AiModelService::class)->makeChatOption($model, false);
        $option->stream   = true;
        $option->tools    = [];
        $option->messages = [
            [
                'role'    => AiConversationEnum::ROLE_SYSTEM,
                'content' => $this->promptGenerationSystemPrompt($type),
            ],
            [
                'role'    => AiConversationEnum::ROLE_USER,
                'content' => $this->promptGenerationUserPrompt($type, $content),
            ],
        ];
        // max_tokens 限制输出；豆包 seed 默认 thinking 会额外消耗大量 reasoning_tokens，表单生成场景关闭之.
        // BaseOption::options() 为整表覆盖，此处直接写入完整附加参数.
        $option->options($this->generatePromptRequestOptions($model, $type));

        $messageUuid   = (string) Str::uuid();
        $streamService = app(AiStreamService::class);
        $streamService->message($messageUuid);

        $result = $streamService->stream(trim((string) $model->api_key), $option, $messageUuid);

        return [
            'content' => trim((string) ($result['answer'] ?? '')),
            'status'  => (int) ($result['status'] ?? AiConversationEnum::MESSAGE_FAILED),
        ];
    }

    /**
     * 把表单存为草稿，不改已发布配置、发布状态和已有 share_key.
     *
     * 外部应用尚无 share_key 时在已发布列生成一次，供调试预览挂载；已有密钥不轮换。
     * 请求中的 status 忽略，保存不再等于下架。
     *
     * @param int|string $id 应用 ID
     * @param array<string, mixed> $data 待更新字段
     *
     * @return mixed 更新结果
     *
     * @throws \Throwable 无编辑权限或事务失败
     */
    public function resourceUpdate($id, array $data)
    {
        $this->ensureEdit((int) $id);

        return DB::transaction(function () use ($id, $data) {
            unset($data['uid'], $data['status']);

            $app = $this->dao->get((int) $id);
            if (! $app) {
                throw $this->exception('AI应用不存在');
            }

            return $this->dao->update((int) $id, $this->draftSavePayload($app, $data));
        });
    }

    /**
     * 发布应用：把本次提交（或已有草稿）写入已发布列，并只把状态改为已发布.
     *
     * 不轮换已有 share_key。外部应用尚无密钥时生成一次，并随发布打开 share_enabled。
     * 未传表单时提升 draft_json；没有草稿则只改状态。成功后清空草稿。
     *
     * @param int $id 应用 ID
     * @param array<string, mixed>|null $data 发布时一并提交的表单；null 表示只提升已存草稿
     *
     * @return mixed 更新结果
     *
     * @throws \Throwable 无编辑权限或事务失败
     */
    public function publish(int $id, ?array $data = null)
    {
        $this->ensureEdit($id);

        return DB::transaction(function () use ($id, $data) {
            if (is_array($data)) {
                unset($data['uid'], $data['status']);
            }

            $app = $this->dao->get($id);
            if (! $app) {
                throw $this->exception('AI应用不存在');
            }

            $existingShareKey = (string) ($app->share_key ?? '');
            if (is_array($data)) {
                $columns = $this->configurationFromInput($data, $app);
            } else {
                $stored  = is_array($app->draft_json) ? $app->draft_json : [];
                $columns = $stored === [] ? [] : $this->runtimeAttributesFromDraft($stored);
            }

            $appType                   = (int) ($columns['app_type'] ?? $app->app_type ?? AiConversationEnum::APP_TYPE_INTERNAL);
            $columns['status']         = AiConversationEnum::STATUS_ENABLED;
            $columns['share_enabled']  = $appType === AiConversationEnum::APP_TYPE_EXTERNAL ? 1 : 0;
            $columns['draft_json']     = null;
            if ($appType === AiConversationEnum::APP_TYPE_EXTERNAL && $existingShareKey === '') {
                $columns['share_key'] = app(AiGuestAuthService::class)->generateShareKey();
            }

            return $this->dao->update($id, $columns);
        });
    }

    /**
     * 获取应用编辑详情，含已绑定 MCP / 知识库 ID 及其基本信息。
     *
     * 有草稿时用草稿回填表单；status、share_key 仍取已发布列。
     * mcp_service_ids、knowledge_ids 为存储的 ID 数组；mcp_services、knowledge 为对应可见记录的基本信息（不含 MCP config_json）。
     *
     * @param int $id 应用 ID
     * @param array<string, mixed> $other 预留扩展参数
     *
     * @return array<string, mixed> 应用详情
     *
     * @throws \Throwable 应用不存在或无编辑权限
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $this->ensureEdit($id);
        $app = $this->dao->get($id);
        if (! $app) {
            throw $this->exception('AI应用不存在');
        }
        $hasDraft = $this->hasDraft($app);
        if ($hasDraft) {
            $this->applyDraftConfig($app);
        }
        $data                    = $app->toArray();
        unset($data['draft_json']);
        $mcpService              = app(AiMcpService::class);
        $data['mcp_service_ids'] = $mcpService->normalizeServiceIds($data['mcp_service_ids'] ?? null);
        $data['mcp_services']    = $mcpService->basicInfoFromApplication($app);
        $data['knowledge_ids']   = KnowledgeBindingParser::normalizeIds($data['knowledge_ids'] ?? null);
        $data['knowledge']       = app(KnowledgeBaseService::class)->basicInfoByIds($data['knowledge_ids']);
        $data['has_draft']       = $hasDraft;

        return $data;
    }

    /**
     * 获取创建表单附加数据。
     *
     * @param array<string, mixed> $other 预留扩展参数
     *
     * @return array<string, mixed> 创建表单默认值
     */
    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 搜索可授权给 AI 应用的业务数据表列表（只读 SQL 插件配置用）.
     *
     * @param string $keyword 表名关键字
     *
     * @return array<int, array<string, mixed>>
     */
    public function databaseTables(string $keyword = ''): array
    {
        return app(SystemCrudService::class)->getCrudTableFieldList(['keyword' => $keyword]);
    }

    /**
     * 根据所选数据表生成 SQL 查询提示词模板，供应用 tooltip_text/content 配置使用。
     *
     * @param array<int, scalar|string> $tables 表名列表
     *
     * @return string 提示词正文（最长 10000 字符）
     *
     * @throws \Throwable 表名非法或表不存在
     */
    public function databaseTooltip(array $tables): string
    {
        if (! $tables) {
            throw $this->exception('请选择表');
        }

        $prefix       = (string) config('database.connections.mysql.prefix');
        $tableContent = '';
        $refContent   = '';
        foreach ($tables as $table) {
            if (! is_scalar($table)) {
                throw $this->exception('表名不符合规范');
            }
            $tableName = $this->normalizeTableName((string) $table, $prefix);
            $this->ensureValidDatabaseTable($tableName);
            $sql = (array) DB::select("SHOW CREATE TABLE `{$tableName}`");
            $tableContent .= $sql[0]->{'Create Table'} . "\n\n";
            $refContent .= '- ' . $tableName . ' 表的 uid 字段关联了 ' . $this->normalizeTableName('admin', $prefix) . " 表的 id 字段；\n";
        }
        $tableContent = str_replace('COLLATE utf8mb4_unicode_ci', '', $tableContent);
        $tableContent = str_replace('SET utf8mb4', '', $tableContent);
        $tooltipText  = <<<MD
# MySQL查询语句生成提示词
---
用户使用注意事项：
1.  本提示词仅用于生成SELECT查询语句，不支持生成增删改语句；
2.  如果多个表记得手动修改下面的表之间引用关系；
3.  选择数据库只是为了快速生成提示词，您可以自行修改本提示词内容；
4.  这块内容是提示词注释模块，您可以删除；
---

# 数据库表结构
{$tableContent}
# 表之间引用关系
{$refContent}
### 记得手动修改上面表的引用关系哦！

## 查询规则
返回字段：[逗号分隔的字段列表，如 id, name AS 姓名]
筛选条件：[如 is_delete = 1 AND id > 1]
排序规则：[如 ORDER BY updated_at DESC, id ASC]
分组与聚合：[如 GROUP BY num HAVING COUNT(*) > 5]
连接操作：[如 LEFT JOIN a ON a.id = b.id]
限制结果：[如 LIMIT 10 OFFSET 20]
其他操作：[如 DISTINCT, 子查询等]

## 补充说明
{$this->normalizeTableName('client_bill', $prefix)}.status = 1 才算业绩

MD;

        if (strlen($tooltipText) > 10000) {
            $tooltipText = substr($tooltipText, 0, 10000);
        }

        return $tooltipText;
    }

    /**
     * 删除 AI 应用及其关联会话、消息与知识库绑定。
     *
     * @param int|string $id 应用 ID
     * @param null|string $key 软删除键（未使用）
     *
     * @return mixed 删除结果
     *
     * @throws \Throwable 无编辑权限或事务失败
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $id = (int) $id;
        $this->ensureEdit($id);

        return DB::transaction(function () use ($id) {
            $conversationIds = AiConversation::query()->where('chat_application_id', $id)->pluck('id');
            AiConversationMessage::query()->whereIn('chat_history_id', $conversationIds)->delete();
            AiConversationMessage::query()->where('chat_applications_id', $id)->delete();
            AiConversation::query()->where('chat_application_id', $id)->delete();

            return $this->dao->delete($id);
        });
    }

    /**
     * 更新应用启用/停用（发布）状态。
     *
     * 置为已发布时提升草稿（若有）并打开状态，不重置 share_key。
     * 置为未发布时只改 status，外部应用同时关闭 share_enabled；不改配置、不改 share_key、不删草稿。
     *
     * @param int|string $id 应用 ID
     * @param array<string, mixed> $data 含 status 字段
     *
     * @return mixed 更新结果
     *
     * @throws \Throwable 无编辑权限
     */
    public function resourceShowUpdate($id, array $data)
    {
        $id     = (int) $id;
        $status = (int) ($data['status'] ?? AiConversationEnum::STATUS_DISABLED);
        if ($status === AiConversationEnum::STATUS_ENABLED) {
            return $this->publish($id);
        }

        $this->ensureEdit($id);
        $app  = $this->dao->get($id);
        $save = ['status' => AiConversationEnum::STATUS_DISABLED];
        if ($app && (int) ($app->app_type ?? AiConversationEnum::APP_TYPE_INTERNAL) === AiConversationEnum::APP_TYPE_EXTERNAL) {
            $save['share_enabled'] = 0;
        }

        return $this->dao->update($id, $save);
    }

    /**
     * 获取应用已绑定的知识库列表（含知识库详情）。
     *
     * @param int $id 应用 ID
     *
     * @return array<int, array<string, mixed>> 绑定记录列表
     *
     * @throws \Throwable 无编辑权限
     */
    public function knowledge(int $id): array
    {
        $this->ensureEdit($id);
        $app = $this->dao->get($id);
        if (! $app) {
            throw $this->exception('AI应用不存在');
        }

        $baseService = app(KnowledgeBaseService::class);
        $app         = $this->applyDraftConfig($app);
        $result      = [];
        foreach (KnowledgeBindingParser::normalizeIds($app->knowledge_ids ?? null) as $kbId) {
            try {
                $base = $baseService->checkVisible($kbId);
            } catch (\Throwable) {
                continue;
            }
            $result[] = [
                'kb_id'           => $kbId,
                'top_k'           => KnowledgeEnum::DEFAULT_TOP_K,
                'score_threshold' => KnowledgeEnum::defaultScoreThreshold(),
                'status'          => KnowledgeEnum::STATUS_ENABLED,
                'base'            => $base->toArray(),
            ];
        }

        return $result;
    }

    /**
     * 绑定知识库；支持内部与外部知识库，不按应用类型限制 space_type.
     *
     * @param int $id 应用 ID
     * @param array<int, int> $knowledgeIds 知识库 ID 列表
     *
     * @throws \Throwable 无权限、知识库类型非法或已停用
     */
    public function bindKnowledge(int $id, array $knowledgeIds): void
    {
        $this->ensureEdit($id);
        $ids = KnowledgeBindingParser::normalizeIds($knowledgeIds);
        $this->assertKnowledgeIds($ids);

        DB::transaction(function () use ($id, $ids): void {
            $this->updateDraft($id, function (array $draft) use ($ids): array {
                $draft['knowledge_ids'] = $ids;
                $types                  = array_map('intval', (array) ($draft['source_types'] ?? []));
                if (! in_array(AiConversationEnum::SOURCE_KNOWLEDGE, $types, true)) {
                    $types[] = AiConversationEnum::SOURCE_KNOWLEDGE;
                }
                $draft['source_types'] = array_values(array_unique($types));

                return $draft;
            });
        });
    }

    /**
     * 校验知识库可绑定到 AI 应用（内部/外部空间均可）.
     *
     * @param mixed $base 知识库模型
     *
     * @throws \Throwable 空间类型非法或已停用
     */
    public function assertBindableKnowledge($base): void
    {
        $spaceType = (int) ($base->space_type ?? 0);
        if (! in_array($spaceType, [KnowledgeEnum::SPACE_INTERNAL, KnowledgeEnum::SPACE_EXTERNAL], true)) {
            throw $this->exception('知识库空间类型错误');
        }
        if ((int) ($base->status ?? 0) !== KnowledgeEnum::STATUS_ENABLED) {
            throw $this->exception('不能绑定已停用的知识库');
        }
    }

    /**
     * 解绑指定知识库并同步应用数据源类型。
     *
     * @param int $id 应用 ID
     * @param int $kbId 知识库 ID
     *
     * @throws \Throwable 无编辑权限
     */
    public function unbindKnowledge(int $id, int $kbId): void
    {
        $this->ensureEdit($id);
        $this->updateDraft($id, function (array $draft) use ($kbId): array {
            $ids                    = KnowledgeBindingParser::removeId($draft['knowledge_ids'] ?? null, $kbId);
            $draft['knowledge_ids'] = $ids;
            $types                  = array_map('intval', (array) ($draft['source_types'] ?? []));
            if ($ids === []) {
                $types = array_values(array_filter(
                    $types,
                    static fn (int $type): bool => $type !== AiConversationEnum::SOURCE_KNOWLEDGE
                ));
            } elseif (! in_array(AiConversationEnum::SOURCE_KNOWLEDGE, $types, true)) {
                $types[] = AiConversationEnum::SOURCE_KNOWLEDGE;
            }
            $draft['source_types'] = $types ?: [AiConversationEnum::SOURCE_NORMAL];

            return $draft;
        });
    }

    /**
     * 校验内部用户是否可使用该应用（权限、启用状态、日限额）。
     *
     * 内部应用预览：请求携带与 Authorization 一致的有效 X-Ai-Guest-Debug（登录 JWT）时，
     * 可跳过内部应用的发布状态校验，并在有草稿时改用草稿配置；外部应用仍须已发布.
     *
     * @param int $id 应用 ID
     * @param int $uid 用户 ID
     *
     * @return mixed 含 model、knowledge 关联的应用模型
     *
     * @throws \Throwable 应用不可用、无权限或已达日上限
     */
    public function checkUsable(int $id, int $uid)
    {
        $preview = app(AiGuestAuthService::class)->isInternalPreviewDebugRequest(request());
        $where   = ['id' => $id];
        if (! $preview) {
            $where['status'] = AiConversationEnum::STATUS_ENABLED;
        }

        $app = $this->dao->get($where, with: ['model']);
        if (! $app) {
            throw $this->exception('AI应用不存在或已禁用');
        }

        // 预览仅放宽内部应用发布状态；外部应用仍要求已发布.
        if ($preview
            && (int) ($app->app_type ?? AiConversationEnum::APP_TYPE_INTERNAL) === AiConversationEnum::APP_TYPE_EXTERNAL
            && (int) ($app->status ?? 0) !== AiConversationEnum::STATUS_ENABLED
        ) {
            throw $this->exception('AI应用不存在或已禁用');
        }

        if (! $this->canUse($app->toArray(), $uid)) {
            throw $this->exception('暂无权限访问此应用');
        }
        if ($preview) {
            $app = $this->applyDraftConfig($app);
        }
        if ((int) $app->use_limit > 0) {
            // 使用次数按用户当天发出的 user 消息统计，重新生成不额外计数.
            $used = app(AiConversationMessageService::class)->dailyCount($uid, $id);
            if ($used >= (int) $app->use_limit) {
                throw $this->exception('今日使用已达到上限');
            }
        }

        return $app;
    }

    /**
     * 校验外部应用可用于访客会话（分享开启、未过期、日限额）。
     *
     * 访客联调模式下（guest_debug=share_key）可跳过发布状态与分享开关/过期校验，
     * 并在有草稿时改用草稿配置。正式访客只读已发布列.
     *
     * @param int $id 应用 ID
     * @param string $guestToken 访客 UUID（封禁校验用；日限额按应用合计，不按个人）
     *
     * @return AiApplication 可用应用模型
     *
     * @throws \Throwable 应用不可用或已达上限
     */
    public function checkUsableForGuest(int $id, string $guestToken)
    {
        $app = $this->dao->get(['id' => $id], with: ['model']);
        if (! $app) {
            throw $this->exception('AI应用不存在');
        }

        $debug = app(AiGuestAuthService::class)->isGuestDebugRequest(request(), (string) ($app->share_key ?? ''));
        if (! $debug && (int) ($app->status ?? 0) !== AiConversationEnum::STATUS_ENABLED) {
            throw $this->exception('AI应用不存在或已禁用');
        }
        if ((int) $app->app_type !== AiConversationEnum::APP_TYPE_EXTERNAL) {
            throw $this->exception('该应用未开放外部访问');
        }
        if (! $debug) {
            if (! (int) ($app->share_enabled ?? 0)) {
                throw $this->exception('外部分享已关闭');
            }
            $expireAt = $app->share_expire_at ?? null;
            if ($expireAt && strtotime((string) $expireAt) < time()) {
                throw $this->exception('分享链接已过期');
            }
        }

        app(AiGuestAuthService::class)->assertNotBanned($guestToken, $id);

        app(AiGuestAuthService::class)->assertGuestDailyLimit($app, $guestToken);

        if ($debug) {
            $app = $this->applyDraftConfig($app);
        }

        return $app;
    }

    /**
     * 获取外部分享配置（含 Widget 嵌入代码）。
     *
     * 返回前将 share_enabled 与当前发布状态对齐（发布=1，未发布=0）.
     *
     * @param int $id 应用 ID
     *
     * @return array<string, mixed> 分享配置
     *
     * @throws \Throwable 非外部应用或无编辑权限
     */
    public function getShareSettings(int $id): array
    {
        $this->ensureEdit($id);
        $app = $this->dao->get($id);
        if (! $app) {
            throw $this->exception('AI应用不存在');
        }
        if ((int) $app->app_type !== AiConversationEnum::APP_TYPE_EXTERNAL) {
            throw $this->exception('仅外部应用支持分享配置');
        }

        $shareEnabled = (int) ($app->status ?? 0) === AiConversationEnum::STATUS_ENABLED ? 1 : 0;
        if ((int) ($app->share_enabled ?? 0) !== $shareEnabled) {
            $this->dao->update($id, ['share_enabled' => $shareEnabled]);
            $app = $this->dao->get($id);
        }

        return app(AiGuestAuthService::class)->shareSettings($app);
    }

    /**
     * 更新分享过期时间、访客限额与 guest_json。
     *
     * share_enabled 与发布状态绑定，不接受独立开关；发布即开启，未发布即关闭.
     *
     * @param int $id 应用 ID
     * @param array<string, mixed> $data share_expire_at、guest_use_limit、guest_json（share_enabled 忽略）
     *
     * @return array<string, mixed> 更新后的分享配置
     *
     * @throws \Throwable 非外部应用或无编辑权限
     */
    public function updateShareSettings(int $id, array $data): array
    {
        $this->ensureEdit($id);
        $app = $this->dao->get($id);
        if (! $app || (int) $app->app_type !== AiConversationEnum::APP_TYPE_EXTERNAL) {
            throw $this->exception('仅外部应用支持分享配置');
        }

        $guestJson = is_array($data['guest_json'] ?? null)
            ? AiGuestAuthService::resolveGuestConfig($data['guest_json'])
            : ($app->guest_json ?? null);

        $save = [
            'share_enabled'   => (int) ($app->status ?? 0) === AiConversationEnum::STATUS_ENABLED ? 1 : 0,
            'share_expire_at' => ($data['share_expire_at'] ?? null) ?: null,
            'guest_use_limit' => (int) ($data['guest_use_limit'] ?? $app->guest_use_limit ?? 0),
            'guest_json'      => $guestJson,
        ];
        if (empty($app->share_key)) {
            $save['share_key'] = app(AiGuestAuthService::class)->generateShareKey();
        }
        $this->dao->update($id, $save);
        $app = $this->dao->get($id);

        return app(AiGuestAuthService::class)->shareSettings($app);
    }

    /**
     * 重新生成 share_key（旧链接与 Widget 嵌入立即失效）。
     *
     * @param int $id 应用 ID
     *
     * @return array<string, mixed> 更新后的分享配置
     *
     * @throws \Throwable 非外部应用或无编辑权限
     */
    public function regenerateShareKey(int $id): array
    {
        $this->ensureEdit($id);
        $app = $this->dao->get($id);
        if (! $app || (int) $app->app_type !== AiConversationEnum::APP_TYPE_EXTERNAL) {
            throw $this->exception('仅外部应用支持分享配置');
        }

        $shareKey = app(AiGuestAuthService::class)->generateShareKey();
        $this->dao->update($id, ['share_key' => $shareKey]);
        $app = $this->dao->get($id);

        return app(AiGuestAuthService::class)->shareSettings($app);
    }

    /**
     * 校验当前登录用户是否具备应用编辑权限。
     */
    private function ensureEdit(int $id): void
    {
        $app = $this->dao->get($id);
        if (! $app || ! $this->canEdit($app->toArray(), (int) auth('admin')->id())) {
            throw $this->exception('AI应用不存在或无权限操作');
        }
    }

    /**
     * 调试请求有草稿时，用草稿覆盖内存中的运行配置.
     *
     * 不写库，也不改 status / share_key / share_enabled。没有草稿时原样返回。
     * models_id 变化且已预加载 model 时重新加载模型，避免会话仍用已发布模型。
     */
    public function applyDraftConfig(AiApplication $app): AiApplication
    {
        $draft = $app->draft_json;
        if (! is_array($draft) || $draft === []) {
            return $app;
        }

        $attributes = $this->runtimeAttributesFromDraft($draft);
        if ($attributes === []) {
            return $app;
        }

        $previousModelId = (int) $app->models_id;
        foreach ($attributes as $key => $value) {
            $app->setAttribute($key, $value);
        }
        if ((int) $app->models_id !== $previousModelId && $app->relationLoaded('model')) {
            $app->unsetRelation('model');
            $app->load('model');
        }
        $app->syncOriginal();

        return $app;
    }

    /**
     * 是否存在未发布草稿.
     */
    private function hasDraft(AiApplication $app): bool
    {
        return is_array($app->draft_json) && $app->draft_json !== [];
    }

    /**
     * 在已有草稿上打补丁；尚无草稿时先复制已发布配置，避免直接改线上列.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $mutator
     */
    private function updateDraft(int $id, callable $mutator): void
    {
        $app = $this->dao->get($id);
        if (! $app) {
            throw $this->exception('AI应用不存在');
        }

        $draft = $this->hasDraft($app) ? $app->draft_json : $this->publishedConfiguration($app);
        $draft = $mutator($draft);
        $this->dao->update($id, ['draft_json' => $this->runtimeAttributesFromDraft($draft)]);
    }

    /**
     * 组装草稿写入数据。已有 share_key 不出现在结果里.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function draftSavePayload(AiApplication $app, array $data): array
    {
        $draft   = $this->configurationFromInput($data, $app);
        $save    = ['draft_json' => $draft];
        $appType = (int) ($draft['app_type'] ?? AiConversationEnum::APP_TYPE_INTERNAL);
        if ($appType === AiConversationEnum::APP_TYPE_EXTERNAL && (string) ($app->share_key ?? '') === '') {
            $save['share_key'] = app(AiGuestAuthService::class)->generateShareKey();
        }

        return $save;
    }

    /**
     * 从草稿取出可作用于运行配置的字段，丢弃发布身份字段.
     *
     * @param array<string, mixed> $draft
     *
     * @return array<string, mixed>
     */
    private function runtimeAttributesFromDraft(array $draft): array
    {
        $attributes = [];
        foreach (self::DRAFT_FIELDS as $key) {
            if (array_key_exists($key, $draft)) {
                $attributes[$key] = $draft[$key];
            }
        }

        return $attributes;
    }

    /**
     * 已发布列的配置快照，结构与草稿字段一致.
     *
     * @return array<string, mixed>
     */
    private function publishedConfiguration(AiApplication $app): array
    {
        return [
            'name'              => (string) $app->name,
            'info'              => (string) $app->info,
            'pic'               => (string) $app->pic,
            'models_id'         => (int) $app->models_id,
            'enable_thinking'   => (int) $app->enable_thinking === AiModelEnum::THINKING_OFF
                ? AiModelEnum::THINKING_OFF
                : AiModelEnum::THINKING_ON,
            'tooltip_text'      => (string) $app->tooltip_text,
            'prologue_text'     => (string) $app->prologue_text,
            'prologue_list'     => $this->nullableArray($app->prologue_list),
            'use_limit'         => (int) $app->use_limit,
            'count_number'      => (int) (($app->count_number ?? 0) ?: 6),
            'source_types'      => $app->source_types,
            'tables'            => $this->nullableArray($app->tables),
            'content'           => (string) $app->content,
            'keyword'           => $this->nullableArray($app->keyword),
            'data_arrange_text' => (string) $app->data_arrange_text,
            'mcp_json'          => $this->nullableArray($app->mcp_json),
            'mcp_service_ids'   => $this->nullableArray($app->mcp_service_ids),
            'knowledge_ids'     => KnowledgeBindingParser::normalizeIds($app->knowledge_ids ?? null),
            'agent_json'        => $this->nullableArray($app->agent_json),
            'json'              => $this->nullableArray($app->json),
            'app_type'          => (int) $app->app_type,
            'auth_ids'          => $this->ids($app->auth_ids ?? []),
            'edit_ids'          => $this->ids($app->edit_ids ?? []),
            'sort'              => (int) $app->sort,
        ];
    }

    /**
     * 判断用户是否具备应用使用权限.
     *
     * 外部应用：仅已发布可对登录成员使用；内部应用：创建者、auth_ids、edit_ids 或管理员.
     */
    private function canUse(array $app, int $uid): bool
    {
        if ((int) ($app['app_type'] ?? AiConversationEnum::APP_TYPE_INTERNAL) === AiConversationEnum::APP_TYPE_EXTERNAL) {
            return (int) ($app['status'] ?? 0) === AiConversationEnum::STATUS_ENABLED;
        }

        $authIds = array_map('intval', (array) ($app['auth_ids'] ?? []));
        $editIds = array_map('intval', (array) ($app['edit_ids'] ?? []));

        return $this->isAdmin()
            || (int) ($app['uid'] ?? 0) === $uid
            || in_array($uid, $authIds, true)
            || in_array($uid, $editIds, true);
    }

    /** 判断用户是否具备应用编辑权限（创建者、edit_ids 或管理员）。 */
    private function canEdit(array $app, int $uid): bool
    {
        $editIds = array_map('intval', (array) ($app['edit_ids'] ?? []));

        return $this->isAdmin() || (int) ($app['uid'] ?? 0) === $uid || in_array($uid, $editIds, true);
    }

    /** 判断当前登录用户是否为企业管理员。 */
    private function isAdmin(): bool
    {
        $user = auth('admin')->user();

        return (bool) ($user && ($user->is_admin ?? false));
    }

    /**
     * 规范化应用保存字段，创建时写入已发布列并自动写入 uid。
     *
     * 外部应用（app_type=1）在创建时若尚无 share_key 则自动生成；更新草稿不走本方法。
     * 外部应用 share_enabled 与 status 绑定：发布即开启，未发布即关闭；内部应用强制关闭分享.
     * 插件配置以 source_types 为准：未启用 MCP 清空 mcp_json/mcp_service_ids，未启用数据库清空 tables，未启用知识库清空 knowledge_ids。
     */
    private function normalize(array $data, bool $create = true, string $existingShareKey = ''): array
    {
        $save   = $this->configurationFromInput($data);
        $status = (int) (($data['status'] ?? null) ?? AiConversationEnum::STATUS_ENABLED);
        $save['status'] = $status;
        $save['share_enabled'] = (int) $save['app_type'] === AiConversationEnum::APP_TYPE_EXTERNAL
            && $status === AiConversationEnum::STATUS_ENABLED
            ? 1
            : 0;

        if ((int) $save['app_type'] === AiConversationEnum::APP_TYPE_EXTERNAL
            && ($create || $existingShareKey === '')) {
            $save['share_key'] = app(AiGuestAuthService::class)->generateShareKey();
        }

        if ($create) {
            $save['uid'] = (int) auth('admin')->id();
        }

        return $save;
    }

    /**
     * 从表单提取可写入草稿或发布列的配置，不含状态与分享身份.
     *
     * 应用类型在创建后固定，以已保存类型为准；智能客服移除数据库插件。
     * 未传 knowledge_ids 时保留草稿中的绑定，否则保留已发布绑定，避免部分更新把知识库打回线上值。
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function configurationFromInput(array $data, ?AiApplication $current = null): array
    {
        if ($current && ! array_key_exists('knowledge_ids', $data)) {
            $draft = is_array($current->draft_json) ? $current->draft_json : null;
            $data['knowledge_ids'] = is_array($draft) && array_key_exists('knowledge_ids', $draft)
                ? $draft['knowledge_ids']
                : KnowledgeBindingParser::normalizeIds($current->knowledge_ids ?? null);
        }

        $appType     = (int) ($current ? $current->app_type : ($data['app_type'] ?? AiConversationEnum::APP_TYPE_INTERNAL));
        $sourceTypes = $this->sourceTypes($data);
        if ($appType === AiConversationEnum::APP_TYPE_EXTERNAL) {
            $sourceTypes = array_values(array_filter(
                $sourceTypes,
                static fn (int $type): bool => $type !== AiConversationEnum::SOURCE_SQL
            )) ?: [AiConversationEnum::SOURCE_NORMAL];
        }

        $mcpJson       = null;
        $mcpServiceIds = null;
        if (in_array(AiConversationEnum::SOURCE_MCP, $sourceTypes, true)) {
            $binding = app(AiMcpService::class)->normalizeApplicationMcpBinding(
                $this->arrayValue($data['mcp_service_ids'] ?? []),
                $appType
            );
            $mcpServiceIds = $this->nullableArray($binding['mcp_service_ids'] ?? null);
            $mcpJson       = $this->nullableArray($binding['mcp_json'] ?? null);
        }

        $tables = in_array(AiConversationEnum::SOURCE_SQL, $sourceTypes, true)
            ? $this->nullableArray($data['tables'] ?? null)
            : null;

        $knowledgeIds = [];
        if (in_array(AiConversationEnum::SOURCE_KNOWLEDGE, $sourceTypes, true)) {
            $knowledgeIds = array_key_exists('knowledge_ids', $data)
                ? KnowledgeBindingParser::normalizeIds($data['knowledge_ids'])
                : [];
            $this->assertKnowledgeIds($knowledgeIds);
        }

        return [
            'name'              => (string) ($data['name'] ?? ''),
            'info'              => (string) ($data['info'] ?? ''),
            'pic'               => (string) ($data['pic'] ?? ''),
            'models_id'         => (int) ($data['models_id'] ?? 0),
            'enable_thinking'   => (int) ($data['enable_thinking'] ?? AiModelEnum::THINKING_ON) === AiModelEnum::THINKING_OFF
                ? AiModelEnum::THINKING_OFF
                : AiModelEnum::THINKING_ON,
            'tooltip_text'      => (string) ($data['tooltip_text'] ?? ''),
            'prologue_text'     => (string) ($data['prologue_text'] ?? ''),
            'prologue_list'     => $this->nullableArray($data['prologue_list'] ?? null),
            'use_limit'         => (int) ($data['use_limit'] ?? 0),
            'count_number'      => (int) (($data['count_number'] ?? 0) ?: 6),
            'source_types'      => $sourceTypes,
            'tables'            => $tables,
            'content'           => (string) ($data['content'] ?? ''),
            'keyword'           => $this->nullableArray($data['keyword'] ?? null),
            'data_arrange_text' => (string) ($data['data_arrange_text'] ?? ''),
            'mcp_json'          => $mcpJson,
            'mcp_service_ids'   => $mcpServiceIds,
            'knowledge_ids'     => $knowledgeIds === [] ? [] : $this->nullableArray($knowledgeIds),
            'agent_json'        => $this->nullableArray($data['agent_json'] ?? null),
            'json'              => $this->nullableArray($data['json'] ?? null),
            'app_type'          => $appType,
            'auth_ids'          => $appType === AiConversationEnum::APP_TYPE_EXTERNAL
                ? []
                : $this->ids($data['auth_ids'] ?? []),
            'edit_ids'          => $this->ids($data['edit_ids'] ?? []),
            'sort'              => (int) ($data['sort'] ?? 0),
        ];
    }

    /** 空数组转 null，便于 JSON 字段存储。 */
    private function nullableArray(mixed $value): ?array
    {
        $array = $this->arrayValue($value);

        return $array === [] ? null : $array;
    }

    /** 将字符串 JSON 或数组统一转为数组。 */
    private function arrayValue(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /** 解析可绑定插件类型；以请求中的 source_types/source_type 为准，不因附属配置自动追加。 */
    private function sourceTypes(array $data): array
    {
        if (is_array($data['source_types'] ?? null)) {
            $value = $data['source_types'];
        } elseif (is_array($data['source_type'] ?? null)) {
            $value = $data['source_type'];
        } else {
            $value = [AiConversationEnum::SOURCE_NORMAL];
        }

        $types = AiConversationEnum::sourceTypes(AiConversationEnum::sourceTypeValue($value));
        $types = array_values(array_filter(
            $types,
            static fn (int $type): bool => in_array($type, AiConversationEnum::BINDABLE_SOURCE_TYPES, true)
        ));

        return $types ?: [AiConversationEnum::SOURCE_NORMAL];
    }

    /**
     * @param array<int, int> $knowledgeIds
     */
    private function assertKnowledgeIds(array $knowledgeIds): void
    {
        $baseService = app(KnowledgeBaseService::class);
        foreach ($knowledgeIds as $kbId) {
            if ($kbId <= 0) {
                throw $this->exception('知识库绑定参数错误');
            }
            $base = $baseService->checkVisible($kbId);
            $this->assertBindableKnowledge($base);
        }
    }

    /** 将成员 ID 列表（含对象格式）规范化为去重整型数组。 */
    private function ids(mixed $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($item) => (int) (is_array($item) ? ($item['id'] ?? 0) : $item),
            (array) $value
        ))));
    }

    /**
     * 解析可用的聊天模型配置（存在、已启用且含 API Key）.
     *
     * @param int $modelsId 模型配置 ID
     *
     * @throws \Throwable 模型不存在或不可用
     */
    private function resolveUsableModel(int $modelsId): AiModel
    {
        if ($modelsId <= 0) {
            throw $this->exception('请选择AI模型');
        }

        $model = app(AiModelDao::class)->get($modelsId);
        if (! $model) {
            throw $this->exception('AI模型不存在');
        }
        if ((int) $model->status !== AiModelEnum::STATUS_ENABLED) {
            throw $this->exception('AI模型已停用');
        }
        if (trim((string) $model->api_key) === '') {
            throw $this->exception(app(AiModelService::class)->missingApiKeyMessage($model, 'AI模型未配置API Key'));
        }

        return $model;
    }

    /**
     * 组装 generate-prompt 的模型请求附加参数（限长 + 关闭深度思考）.
     *
     * @return array<string, mixed>
     */
    private function generatePromptRequestOptions(AiModel $model, string $type): array
    {
        $maxTokens = (int) match ($type) {
            AiApplicationPromptEnum::TYPE_PERSONA_REPLY      => config('ai.generate_prompt.max_tokens.persona_reply', 900),
            AiApplicationPromptEnum::TYPE_PROLOGUE           => config('ai.generate_prompt.max_tokens.prologue', 160),
            AiApplicationPromptEnum::TYPE_PROLOGUE_QUESTIONS => config('ai.generate_prompt.max_tokens.prologue_questions', 280),
            default                                          => 900,
        };
        if ($maxTokens < 64) {
            $maxTokens = 64;
        }

        $options = ['max_tokens' => $maxTokens];

        // generate-prompt 固定关闭深度思考，避免表单生成长时间等待（不读应用开关 / env）.
        $options['thinking'] = ['type' => 'disabled'];

        return $options;
    }

    /** 按生成类型返回系统指令（控制篇幅）. */
    private function promptGenerationSystemPrompt(string $type): string
    {
        $label = AiApplicationPromptEnum::label($type);

        return match ($type) {
            AiApplicationPromptEnum::TYPE_PERSONA_REPLY => <<<PROMPT
你是企业 AI 应用配置助手。请根据上下文生成「{$label}」中文正文，控制在 800 字以内。
按以下结构输出（保留标题）：
#角色规范
#思考规范
#回复规范
要求：身份/语气/能力边界清晰，回复风格简洁可执行；不要代码块、编号解释或前后缀说明。
PROMPT,
            AiApplicationPromptEnum::TYPE_PROLOGUE => <<<PROMPT
你是企业 AI 应用配置助手。请生成一段不超过 120 字的「{$label}」中文欢迎语。
只输出开场白正文，不要代码块或解释。
PROMPT,
            AiApplicationPromptEnum::TYPE_PROLOGUE_QUESTIONS => <<<PROMPT
你是企业 AI 应用配置助手。请生成 3～5 条「{$label}」中文引导问题，每条不超过 30 字。
每行一条，不要编号、代码块或解释。
PROMPT,
            default => throw $this->exception('生成类型错误'),
        };
    }

    /**
     * 组装生成 Prompt 的用户消息.
     *
     * @param string $type 生成类型
     * @param string $content 用户上下文
     */
    private function promptGenerationUserPrompt(string $type, string $content): string
    {
        $label   = AiApplicationPromptEnum::label($type);
        $context = trim($content);

        return "生成类型：{$label}\n上下文：\n"
            . ($context !== '' ? $context : '（未提供，请按通用企业助手合理生成）')
            . "\n请直接输出结果正文。";
    }

    /** 补全表名前缀并去除重复前缀片段。 */
    private function normalizeTableName(string $table, string $prefix = ''): string
    {
        $prefix = $prefix !== '' ? $prefix : (string) config('database.connections.mysql.prefix');
        $table  = trim($table);
        $table  = preg_replace('/^(?:' . preg_quote($prefix, '/') . '|eb_)/i', '', $table);

        return $prefix . ltrim((string) $table, '_');
    }

    /** 校验表名格式合法且在当前库中存在。 */
    private function ensureValidDatabaseTable(string $tableName): void
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $tableName)) {
            throw $this->exception('表名不符合规范');
        }

        $exists = DB::selectOne(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
            [$tableName]
        );
        if (! $exists) {
            throw $this->exception('数据表不存在');
        }
    }
}
