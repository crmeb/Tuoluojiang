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

use App\Constants\Ai\AiModelEnum;
use App\Constants\Ai\AiModelLogoEnum;
use App\Constants\Ai\AiModelPresetEnum;
use App\Constants\Ai\AiModelThinkingEnum;
use App\Constants\System\ConfigEnum;
use App\Http\Dao\Ai\AiModelDao;
use App\Http\Model\Ai\AiModel;
use App\Support\YihaotongFactory;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Tuoluojiang\Open\services\ai\BaseOption;
use Tuoluojiang\Open\services\ai\OpenAiCompatibleOption;
use Tuoluojiang\Open\services\HttpService;
use Tuoluojiang\Open\services\SmsService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI 模型配置服务：维护供应商预设、自定义模型 CRUD 与连接测试。
 *
 * 调用链：AdminApi 模型管理控制器 → 本服务 → AiModelDao；流式对话时由本服务 makeChatOption / makeEmbeddingOption 生成请求参数。
 */
class AiModelService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * @param AiModelDao $dao 模型数据访问对象
     */
    public function __construct(AiModelDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * 创建 OpenAI 兼容聊天请求参数。
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     * @param bool $applyAdvanced 是否写入模型高级采样/输出上限（分类器等确定性调用可关）
     *
     * @throws HttpServiceExceptions 不支持的模型供应商
     */
    public function makeChatOption(AiModel|array $model, bool $applyAdvanced = true): BaseOption
    {
        $data   = $model instanceof AiModel ? $this->optionData($model) : $model;
        $preset = AiModelPresetEnum::get((string) ($data['provider'] ?? ''));
        if (! $preset) {
            throw new HttpServiceExceptions('不支持的模型供应商');
        }

        $option          = new OpenAiCompatibleOption();
        $option->baseUrl = rtrim((string) (($data['base_url'] ?? '') ?: $preset['base_url']), '/');
        $option->url     = $this->resolveOptionPath(
            (int) ($data['is_full_url'] ?? AiModelEnum::FULL_URL_OFF),
            (string) (($data['chat_path'] ?? '') ?: $preset['chat_path'] ?: AiModelEnum::DEFAULT_CHAT_PATH)
        );
        $option->model = (string) ($data['model_id'] ?? '');
        // 流式默认请求末包 usage，供 chat_record / agent_runs 统计 token 消耗.
        $option->streamOptions = ['include_usage' => true];
        if ($applyAdvanced) {
            $this->applyAdvancedOptions($option, $data);
        }

        return $option;
    }

    /**
     * 解析模型高级配置（上下文窗口、工具轮次、采样参数）.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     *
     * @return array{
     *     input_tokens: ?int,
     *     output_tokens: ?int,
     *     max_tool_rounds: ?int,
     *     temperature: ?float,
     *     top_p: ?float,
     *     top_k: ?int
     * }
     */
    public function advancedSettings(AiModel|array $model): array
    {
        $raw = $model instanceof AiModel
            ? (array) ($model->json ?? [])
            : (array) ($model['json'] ?? []);

        return [
            // 未配置时使用内置默认，保证调用侧始终有可用的上下文与工具轮次上限.
            AiModelEnum::ADVANCED_INPUT_TOKENS    => $this->nullablePositiveInt($raw[AiModelEnum::ADVANCED_INPUT_TOKENS] ?? null)
                ?? AiModelEnum::DEFAULT_INPUT_TOKENS,
            AiModelEnum::ADVANCED_OUTPUT_TOKENS   => $this->nullablePositiveInt($raw[AiModelEnum::ADVANCED_OUTPUT_TOKENS] ?? null),
            AiModelEnum::ADVANCED_MAX_TOOL_ROUNDS => $this->nullablePositiveInt($raw[AiModelEnum::ADVANCED_MAX_TOOL_ROUNDS] ?? null)
                ?? AiModelEnum::DEFAULT_MAX_TOOL_ROUNDS,
            AiModelEnum::ADVANCED_TEMPERATURE     => $this->nullableFloat($raw[AiModelEnum::ADVANCED_TEMPERATURE] ?? null, 0.0, 2.0),
            AiModelEnum::ADVANCED_TOP_P           => $this->nullableFloat($raw[AiModelEnum::ADVANCED_TOP_P] ?? null, 0.0, 1.0),
            AiModelEnum::ADVANCED_TOP_K           => $this->nullableIntInRange($raw[AiModelEnum::ADVANCED_TOP_K] ?? null, 1, 100),
        ];
    }

    /**
     * 模型是否支持图片输入（vision），用于决定 RAG 检索图片是否注入模型.
     *
     * 读取 ai_model.json 的 supports_vision；显式配置优先生效。
     * 未配置时：会话模型（model_type=chat）默认开启，向量等其它类型默认关闭.
     *
     * @param AiModel|array<string, mixed>|null $model 模型记录或配置数组
     */
    public function supportsVision(AiModel|array|null $model): bool
    {
        if ($model === null) {
            return false;
        }

        $raw = $model instanceof AiModel
            ? (array) ($model->json ?? [])
            : (array) ($model['json'] ?? []);
        if (array_key_exists(AiModelEnum::ADVANCED_SUPPORTS_VISION, $raw)) {
            return (bool) $raw[AiModelEnum::ADVANCED_SUPPORTS_VISION];
        }

        $modelType = $model instanceof AiModel
            ? (int) ($model->model_type ?? AiModelEnum::MODEL_TYPE_CHAT)
            : (int) ($model['model_type'] ?? AiModelEnum::MODEL_TYPE_CHAT);

        return $modelType === AiModelEnum::MODEL_TYPE_CHAT;
    }

    /**
     * 将模型高级采样/输出上限写入聊天 Option.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     */
    public function applyAdvancedOptions(BaseOption $option, AiModel|array $model): void
    {
        $settings = $this->advancedSettings($model);
        $extras   = [];

        if ($settings[AiModelEnum::ADVANCED_TEMPERATURE] !== null) {
            // BaseOption::$temperature 为 int|null，浮点温度走 options 以免被截断.
            $extras['temperature'] = $settings[AiModelEnum::ADVANCED_TEMPERATURE];
        }

        if ($settings[AiModelEnum::ADVANCED_TOP_P] !== null) {
            $option->topP = $settings[AiModelEnum::ADVANCED_TOP_P];
        }

        if ($settings[AiModelEnum::ADVANCED_OUTPUT_TOKENS] !== null) {
            $extras['max_tokens'] = $settings[AiModelEnum::ADVANCED_OUTPUT_TOKENS];
        }
        if ($settings[AiModelEnum::ADVANCED_TOP_K] !== null) {
            $extras['top_k'] = $settings[AiModelEnum::ADVANCED_TOP_K];
        }
        $this->mergeOptionExtras($option, $extras);
    }

    /**
     * 读取模型思考模式：0 关闭 / 1 开启 / 2 跟随默认.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     */
    public function modelThinkingMode(AiModel|array $model): int
    {
        $value = $model instanceof AiModel
            ? (int) ($model->enable_thinking ?? AiModelEnum::THINKING_DEFAULT)
            : (int) ($model['enable_thinking'] ?? AiModelEnum::THINKING_DEFAULT);

        return in_array($value, [
            AiModelEnum::THINKING_OFF,
            AiModelEnum::THINKING_ON,
            AiModelEnum::THINKING_DEFAULT,
        ], true) ? $value : AiModelEnum::THINKING_DEFAULT;
    }

    /**
     * 合并应用侧与模型侧思考开关：应用关闭或模型关闭时返回 OFF，否则返回 ON（表示允许思考）.
     *
     * 模型侧「跟随默认」(DEFAULT) 在此归一为 ON；调用方若需区分 DEFAULT/ON，应再读 modelThinkingMode().
     *
     * @param int $appThinking 应用侧：0 关闭 / 1 开启
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     */
    public function resolveThinkingMode(int $appThinking, AiModel|array $model): int
    {
        if ($appThinking === AiModelEnum::THINKING_OFF) {
            return AiModelEnum::THINKING_OFF;
        }

        $modelThinking = $this->modelThinkingMode($model);
        if ($modelThinking === AiModelEnum::THINKING_OFF) {
            return AiModelEnum::THINKING_OFF;
        }

        return AiModelEnum::THINKING_ON;
    }

    /**
     * 创建 embedding 请求参数。
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     *
     * @throws HttpServiceExceptions 不支持的模型供应商
     */
    public function makeEmbeddingOption(AiModel|array $model): OpenAiCompatibleOption
    {
        $data   = $model instanceof AiModel ? $this->optionData($model) : $model;
        $preset = AiModelPresetEnum::get((string) ($data['provider'] ?? ''));
        if (! $preset) {
            throw new HttpServiceExceptions('不支持的模型供应商');
        }

        $option          = new OpenAiCompatibleOption();
        $option->baseUrl = rtrim((string) (($data['base_url'] ?? '') ?: $preset['base_url']), '/');
        $option->url     = $this->resolveOptionPath(
            (int) ($data['is_full_url'] ?? AiModelEnum::FULL_URL_OFF),
            (string) (($data['embedding_path'] ?? '') ?: $preset['embedding_path'] ?: AiModelEnum::DEFAULT_EMBEDDING_PATH)
        );
        $option->model  = (string) ($data['model_id'] ?? '');
        $option->stream = false;

        return $option;
    }

    /**
     * 按是否完整 URL 决定是否拼接接口路径.
     */
    private function resolveOptionPath(int $isFullUrl, string $path): string
    {
        if ($isFullUrl === AiModelEnum::FULL_URL_ON) {
            return '';
        }

        return $this->path($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function optionData(AiModel $model): array
    {
        return [
            'provider'        => $model->provider,
            'base_url'        => $model->base_url,
            'is_full_url'     => (int) $model->is_full_url,
            'chat_path'       => $model->chat_path,
            'embedding_path'  => $model->embedding_path,
            'model_id'        => $model->model_id,
            'model_type'      => (int) ($model->model_type ?? AiModelEnum::MODEL_TYPE_CHAT),
            'enable_thinking' => (int) ($model->enable_thinking ?? AiModelEnum::THINKING_DEFAULT),
            'json'            => (array) ($model->json ?? []),
        ];
    }

    /**
     * 按思考模式组装聊天附加参数.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     * @param int $enableThinking 最终思考模式：0 关闭 / 1 开启 / 2 跟随默认
     *
     * @return array<string, mixed>
     */
    public function chatExtraOptions(AiModel|array $model, int $enableThinking = AiModelEnum::THINKING_ON): array
    {
        if (! $this->supportsThinkingControl($model)) {
            return [];
        }

        if ($enableThinking === AiModelEnum::THINKING_OFF) {
            return ['thinking' => ['type' => 'disabled']];
        }

        if ($enableThinking === AiModelEnum::THINKING_ON) {
            return ['thinking' => ['type' => 'enabled']];
        }

        // THINKING_DEFAULT：不注入 thinking，交由供应商默认行为
        return [];
    }

    /**
     * 将深度思考参数合并写入聊天 Option（保留已有 max_tokens / top_k 等 extras）.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     * @param int $enableThinking 最终思考模式：0 关闭 / 1 开启 / 2 跟随默认
     */
    public function applyThinkingOption(BaseOption $option, AiModel|array $model, int $enableThinking = AiModelEnum::THINKING_ON): void
    {
        $this->mergeOptionExtras($option, $this->chatExtraOptions($model, $enableThinking));
    }

    /**
     * 合并写入 BaseOption::$options，避免后写覆盖先写的 max_tokens 等字段.
     *
     * @param array<string, mixed> $extra 待合并的附加参数
     */
    private function mergeOptionExtras(BaseOption $option, array $extra): void
    {
        if ($extra === []) {
            return;
        }

        $payload = $option->toArray();
        // temperature 可能仅存在于 options（浮点无法写入 BaseOption::$temperature），
        // 不可当 baseKeys 剔除，否则后续合并 thinking 会丢掉采样温度.
        $baseKeys = [
            'messages',
            'model',
            'top_p',
            'frequency_penalty',
            'presence_penalty',
            'stream',
            'tools',
            'stream_options',
        ];
        $existing = array_diff_key($payload, array_flip($baseKeys));
        // 属性上的整型 temperature 已在 toArray 中，继续保留；与 options 同源键合并时以后写为准.
        $option->options(array_merge($existing, $extra));
    }

    /**
     * 判断模型是否支持 thinking 开关（豆包 seed / 火山方舟等）.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     */
    public function supportsThinkingControl(AiModel|array $model): bool
    {
        $data     = $model instanceof AiModel ? $this->optionData($model) : $model;
        $provider = strtolower((string) ($data['provider'] ?? ''));
        $modelId  = strtolower((string) ($data['model_id'] ?? ''));

        return $provider === AiModelEnum::DRIVER_VOLCENGINE
            || str_contains($modelId, 'doubao')
            || str_contains($modelId, 'seed');
    }

    /**
     * 推荐问题的采样参数：默认限长，思考强弱以 AiModelThinkingEnum 目录为准.
     *
     * @param AiModel|array<string, mixed> $model 模型记录或配置数组
     *
     * @return array<string, mixed>
     */
    public function followUpSamplingOptions(AiModel|array $model): array
    {
        $data = $model instanceof AiModel ? $this->optionData($model) : $model;

        return array_merge(
            // 3 条 40 字内推荐 ≈ 120 tokens：收紧上限让弱模型尽快收口，不再拖长生成
            ['max_tokens' => 160],
            AiModelThinkingEnum::followUpExtras(
                (string) ($data['provider'] ?? ''),
                (string) ($data['model_id'] ?? '')
            )
        );
    }

    /**
     * 获取模型配置列表，并为每条记录附加供应商 Logo。
     *
     * @param array<string, mixed> $where 查询条件
     * @param array<int, string> $field 查询字段
     * @param array<int, string>|string $sort 排序规则
     * @param array<int, string> $with 预加载关联
     *
     * @return array<string, mixed> 含 list 与 count 的分页数据
     */
    public function getList(array $where, array $field = ['*'], $sort = ['id'], array $with = ['user']): array
    {
        $result = parent::getList($where, $field, $sort, $with);
        if (! empty($result['list']) && is_array($result['list'])) {
            $result['list'] = array_map(function ($item) {
                $item = is_array($item) ? $item : (array) $item;
                $item['logo'] = $this->providerLogo((string) ($item['provider'] ?? ''));

                return $this->hideApiKeySecrets($item);
            }, $result['list']);
        }

        return $result;
    }

    /**
     * 获取预设供应商选项（含 Logo 与默认地址）。
     *
     * 一号通不向前端下发 base_url / api_key_url，由服务端按 config_type 自行补全。
     * 一号通 enabled：须 AppId/AppSecret 已填且能换取可用 API Key（连通性校验），不回传密钥；
     * 可用时置顶，不可用时沉底.
     *
     * @return array<int, array<string, mixed>> 供应商下拉选项
     */
    public function presetOptions(): array
    {
        $yihaotongEnabled = $this->isYihaotongAvailable();

        $options = collect(AiModelPresetEnum::all())->map(function (array $item, string $driver) use ($yihaotongEnabled) {
            $isYihaotong = ! empty($item['yihaotong']);

            return [
                'label'       => $item['label'],
                'value'       => $driver,
                'logo'        => $this->providerLogo($driver),
                'base_url'    => $isYihaotong ? '' : ($item['base_url'] ?? ''),
                'api_key_url' => $isYihaotong ? '' : ($item['api_key_url'] ?? ''),
                'config_type' => $this->resolveConfigType($item),
                'enabled'     => $isYihaotong ? $yihaotongEnabled : true,
            ];
        });

        // 可用：一号通置顶；不可用：一号通沉底，避免排在首位却不可用
        $yihaotong = $options->first(fn (array $item) => ($item['config_type'] ?? 0) === AiModelEnum::CONFIG_YIHAOTONG);
        $others    = $options->reject(fn (array $item) => ($item['config_type'] ?? 0) === AiModelEnum::CONFIG_YIHAOTONG)->values();

        if (! $yihaotong) {
            return $others->all();
        }

        return $yihaotongEnabled
            ? $others->prepend($yihaotong)->values()->all()
            : $others->push($yihaotong)->values()->all();
    }

    /**
     * 是否已填写一号通 AppId / AppSecret（仅非空判断，不回传密钥）.
     */
    public function isYihaotongConfigured(): bool
    {
        $appId  = trim((string) sys_config(ConfigEnum::YIHAOTONG_APPID['key'], ''));
        $secret = trim((string) sys_config(ConfigEnum::YIHAOTONG_APPSECRET['key'], ''));

        return $appId !== '' && $secret !== '';
    }

    /**
     * 一号通是否可用：已填写且能成功换取 API Key（连通性校验，不回传密钥）.
     *
     * 供 options.enabled 使用；换取结果沿用 resolveYihaotongApiKey 缓存，避免重复打上游.
     */
    public function isYihaotongAvailable(): bool
    {
        if (! $this->isYihaotongConfigured()) {
            return false;
        }

        try {
            $this->resolveYihaotongApiKey();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 同步一号通 AI 模型配置.
     *
     * - 确保默认会话 / 向量各一条；模型列表走 fetchModels，名称取对应分组第一条
     * - 所有一号通类型（config_type）已有模型：配置变更后统一补全或置空 api_key
     */
    public function syncYihaotongDefaultModels(): void
    {
        $this->forgetYihaotongApiKeyCache();

        $apiKey             = '';
        $chatModelId        = null;
        $embeddingModelId   = null;
        $shouldUpdateModels = false;

        if ($this->isYihaotongConfigured()) {
            try {
                $groups = $this->fetchModels([
                    'provider'    => AiModelEnum::DRIVER_YIHAOTONG,
                    'config_type' => AiModelEnum::CONFIG_YIHAOTONG,
                ]);
                $apiKey             = $this->resolveYihaotongApiKey();
                $chatModelId        = $this->firstFetchedModel($groups, 'LLM');
                $embeddingModelId   = $this->firstFetchedModel($groups, 'Embedding');
                $shouldUpdateModels = true;
            } catch (\Throwable $e) {
                Log::warning('syncYihaotongDefaultModels failed, clearing api_key', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
                $apiKey = '';
            }
        }

        $this->upsertYihaotongDefaultModel(
            isEmbedding: false,
            apiKey: $apiKey,
            modelId: ($shouldUpdateModels && $chatModelId !== '') ? $chatModelId : null
        );
        $this->upsertYihaotongDefaultModel(
            isEmbedding: true,
            apiKey: $apiKey,
            modelId: ($shouldUpdateModels && $embeddingModelId !== '') ? $embeddingModelId : null
        );

        // 列表中已有的一号通类型模型一并同步 api_key（含用户自建）
        $this->syncAllYihaotongModelsApiKey($apiKey);
    }

    /**
     * 取 fetchModels 分组结果中某一类的第一条模型名.
     *
     * @param array<int, array<string, mixed>> $groups fetchModels 返回的会话 / 向量分组
     * @param string $groupValue LLM 或 Embedding
     */
    private function firstFetchedModel(array $groups, string $groupValue): string
    {
        foreach ($groups as $group) {
            if (($group['value'] ?? '') !== $groupValue) {
                continue;
            }

            return (string) ($group['children'][0]['value'] ?? '');
        }

        return '';
    }

    /**
     * 清除一号通 AI API Key 缓存.
     */
    protected function forgetYihaotongApiKeyCache(): void
    {
        Cache::forget(AiModelEnum::YIHAOTONG_API_KEY_CACHE);
    }

    /**
     * 将所有一号通类型模型的 api_key 同步为指定值（可获取则补全，否则置空）.
     *
     * @param string $apiKey 一号通 API Key，获取失败时传空串
     */
    protected function syncAllYihaotongModelsApiKey(string $apiKey): void
    {
        $this->dao->update(
            ['config_type' => AiModelEnum::CONFIG_YIHAOTONG],
            ['api_key' => $apiKey]
        );
    }

    /**
     * Upsert 一号通默认会话或向量模型配置.
     *
     * 按系统创建行（uid=0 + 会话/向量类型）定位，避免覆盖用户自建一号通行。
     * 配置名称取模型列表中对应分类的第一条，不再使用固定名称。
     *
     * @param bool $isEmbedding 是否向量配置
     * @param string $apiKey API Key（无效时传空串）
     * @param null|string $modelId 远端模型 ID，同时作为配置名称；null 表示本次不改名称与模型字段
     */
    private function upsertYihaotongDefaultModel(bool $isEmbedding, string $apiKey, ?string $modelId): void
    {
        $existing = $this->findYihaotongDefaultModel($isEmbedding);

        if ($existing) {
            $update = [
                'api_key'    => $apiKey,
                'base_url'   => AiModelEnum::YIHAOTONG_BASE_URL,
                'model_type' => $isEmbedding ? AiModelEnum::MODEL_TYPE_EMBEDDING : AiModelEnum::MODEL_TYPE_CHAT,
                'status'     => AiModelEnum::STATUS_ENABLED,
            ];
            if ($modelId !== null && $modelId !== '') {
                $update['name']     = $modelId;
                $update['model_id'] = $modelId;
            }
            $this->dao->update($existing->id, $update);

            return;
        }

        if ($modelId === null || $modelId === '') {
            return;
        }

        $this->dao->create([
            'uid'             => 0,
            'name'            => $modelId,
            'provider'        => AiModelEnum::DRIVER_YIHAOTONG,
            'config_type'     => AiModelEnum::CONFIG_YIHAOTONG,
            'base_url'        => AiModelEnum::YIHAOTONG_BASE_URL,
            'is_full_url'     => AiModelEnum::FULL_URL_OFF,
            'chat_path'       => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'  => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'model_id'        => (string) $modelId,
            'model_type'      => $isEmbedding ? AiModelEnum::MODEL_TYPE_EMBEDDING : AiModelEnum::MODEL_TYPE_CHAT,
            'api_key'         => $apiKey,
            'status'          => AiModelEnum::STATUS_ENABLED,
            'enable_thinking' => AiModelEnum::THINKING_DEFAULT,
            'json'            => [],
        ]);
    }

    /**
     * 查找系统创建的一号通默认模型行（含软删恢复）.
     *
     * @param bool $isEmbedding 是否向量配置
     *
     * @return null|AiModel
     */
    protected function findYihaotongDefaultModel(bool $isEmbedding): ?AiModel
    {
        $where = [
            'uid'         => 0,
            'provider'    => AiModelEnum::DRIVER_YIHAOTONG,
            'config_type' => AiModelEnum::CONFIG_YIHAOTONG,
            'model_type'  => $isEmbedding ? AiModelEnum::MODEL_TYPE_EMBEDDING : AiModelEnum::MODEL_TYPE_CHAT,
        ];

        $existing = AiModel::query()->where($where)->orderBy('id')->first();
        if ($existing instanceof AiModel) {
            return $existing;
        }

        $trashed = AiModel::withTrashed()
            ->where($where)
            ->orderByDesc('id')
            ->first();
        if ($trashed instanceof AiModel && $trashed->trashed()) {
            $trashed->restore();

            return $trashed;
        }

        return $trashed instanceof AiModel ? $trashed : null;
    }

    /**
     * 获取供应商可选模型（本地预设兜底）。
     *
     * @param string $driver 供应商标识
     *
     * @return array<int, array<string, mixed>> 分组模型选择树
     *
     * @throws \Throwable 供应商不存在
     */
    public function select(string $driver): array
    {
        $preset = AiModelPresetEnum::get($driver);
        if (! $preset) {
            throw $this->exception('模型供应商不存在');
        }

        return $this->formatSelect(
            $preset['chat_models'] ?? [],
            $preset['embedding_models'] ?? [],
            (bool) ($preset['custom'] ?? false)
        );
    }

    /**
     * 从预设供应商远程获取模型列表。
     *
     * 一号通配置：通过 SmsService 获取 API Key（缓存 3 分钟），再请求 https://api.crmeb.net/v1/models。
     *
     * @param array<string, mixed> $data 含 provider、config_type、api_key、base_url
     *
     * @return array<int, array<string, mixed>> 分组模型选择树
     *
     * @throws \Throwable 供应商不存在、API Key 无效或未获取到模型
     */
    public function fetchModels(array $data): array
    {
        $data     = $this->fillStoredApiKey($data);
        $data     = $this->applyYihaotongCredentials($data);
        $provider = (string) ($data['provider'] ?? '');
        $preset   = AiModelPresetEnum::get($provider);
        if (! $preset) {
            throw $this->exception('模型供应商不存在');
        }

        $apiKey = (string) ($data['api_key'] ?? '');
        if (! $apiKey) {
            throw $this->exception('请填写API Key');
        }

        $baseUrl = rtrim((string) (($data['base_url'] ?? '') ?: ($preset['base_url'] ?? '')), '/');
        if (! $baseUrl) {
            throw $this->exception('请填写模型请求地址');
        }

        $modelsPath = $this->path((string) (($preset['models_path'] ?? '') ?: AiModelEnum::DEFAULT_MODELS_PATH));
        $response   = $this->requestModels($baseUrl, $modelsPath, $apiKey, (string) ($preset['auth'] ?? AiModelEnum::AUTH_BEARER));
        [$chatModels, $embeddingModels] = $this->classifyModels($this->parseModelIds($response));
        if (! $chatModels && ! $embeddingModels) {
            $chatModels      = $preset['chat_models'] ?? [];
            $embeddingModels = $preset['embedding_models'] ?? [];
            if (! $chatModels && ! $embeddingModels) {
                throw $this->exception('未获取到可用模型，请检查API Key或供应商地址');
            }
        }

        return $this->formatSelect($chatModels, $embeddingModels, (bool) ($preset['custom'] ?? false));
    }

    /**
     * 获取启用模型下拉数据。
     *
     * @return array<int, array<string, mixed>> 模型下拉选项
     */
    public function list(): array
    {
        return $this->dao->search([
            'status'     => AiModelEnum::STATUS_ENABLED,
            'model_type' => AiModelEnum::MODEL_TYPE_CHAT,
        ])
            ->get()
            ->map(fn ($model) => [
                'label'      => $model->name,
                'value'      => $model->id,
                'logo'       => $this->providerLogo((string) $model->provider),
                'model_id'   => $model->model_id,
                'model_type' => (int) $model->model_type,
            ])->all();
    }

    /**
     * 保存模型配置。
     *
     * @param array<string, mixed> $data 模型表单数据
     *
     * @return mixed 新建的模型记录
     *
     * @throws \Throwable 供应商不存在或会话/向量配置无效
     */
    public function resourceSave(array $data)
    {
        $normalized = $this->normalize($data);
        $this->assertModelChoice($normalized);

        return $this->dao->create($normalized);
    }

    /**
     * 更新模型配置。
     *
     * @param mixed $id 模型 ID
     * @param array<string, mixed> $data 待更新字段
     *
     * @return mixed 更新结果
     *
     * @throws \Throwable 供应商不存在或会话/向量配置无效
     */
    public function resourceUpdate($id, array $data)
    {
        unset($data['uid']);
        // 编辑留空表示不修改密钥：按 id 回填库内 api_key 后再规范化入库.
        $data['id'] = (int) $id;
        $data       = $this->fillStoredApiKey($data);
        unset($data['id'], $data['has_api_key']);
        if (! $this->isYihaotongConfig($data) && trim((string) ($data['api_key'] ?? '')) === '') {
            throw $this->exception('请填写API Key');
        }
        $normalized = $this->normalize($data);
        $this->assertModelChoice($normalized);

        return $this->dao->update($id, $normalized);
    }

    /**
     * 获取模型配置详情。
     *
     * @param int $id 模型 ID
     * @param array<string, mixed> $other 预留扩展参数
     *
     * @return array<string, mixed> 模型详情
     *
     * @throws \Throwable 缺少必要参数
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if (! $id) {
            throw $this->exception('缺少必要参数');
        }

        $data = $this->dao->get($id)?->toArray() ?: [];
        if ($data !== []) {
            $data['logo'] = $this->providerLogo((string) ($data['provider'] ?? ''));
            $data         = $this->hideApiKeySecrets($data);
        }

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
     * 测试模型连接（按 model_type 测会话或向量）.
     *
     * model_type=2 测内容向量化；否则测聊天补全。
     * 预设 / 一号通可不传 base_url，由 normalize 按 config_type 补全。
     *
     * @param array<string, mixed> $data 模型配置
     *
     * @return array<string, string> 连接成功消息
     *
     * @throws \Throwable API Key 无效或模型无响应
     */
    public function testConnection(array $data): array
    {
        // 编辑态可只传 id、不传 api_key，由 fillStoredApiKey 复用库内密钥.
        $data = $this->fillStoredApiKey($data);
        $data = $this->normalize($data);
        $this->assertModelChoice($data);
        if (! $data['api_key']) {
            throw $this->exception('请填写API Key');
        }

        $preset = AiModelPresetEnum::get((string) $data['provider']);
        $auth   = (string) ($preset['auth'] ?? AiModelEnum::AUTH_BEARER);

        if ((int) ($data['model_type'] ?? 0) === AiModelEnum::MODEL_TYPE_EMBEDDING) {
            $this->testEmbeddingConnection($data, $preset, $auth);

            return [
                'message'           => '向量模型连接成功',
                'embedding_message' => '向量模型连接成功',
            ];
        }

        $option         = $this->makeChatOption($data);
        $option->stream = false;
        $option->setMessage('请回复 ok', BaseOption::RULE_USER);

        try {
            $response = (new BaseCurl($data['api_key'], $option->baseUrl, $auth))->setBody($option)->send(url: $option->url, timeout: 30);
        } catch (\Throwable $e) {
            // BaseCurl 对非 JSON 响应（如 404 页面）只抛「Failed to parse JSON」，
            // 直接透出对用户没有指导意义，这里换成能对照表单字段排查的提示
            throw $this->exception($this->formatRequestError($e, '会话模型', $option->baseUrl . $option->url));
        }

        if (! isset($response['choices'][0]['message']['content'])) {
            throw $this->exception((string) ($response['error']['message'] ?? '模型连接测试失败'));
        }

        return ['message' => '会话模型连接成功'];
    }

    /**
     * 获取供应商 Logo 完整 URL(供前端渲染).
     */
    private function providerLogo(string $driver): string
    {
        return link_file(AiModelLogoEnum::path($driver));
    }

    /**
     * 规范模型配置.
     *
     * 一行仅一个模型：写入 provider / model_id / model_type，剔除旧字段.
     * 基础地址按 config_type 解析：一号通 / 预设由服务端补全，自定义须前端传入.
     */
    private function normalize(array $data): array
    {
        $data     = $this->applyYihaotongCredentials($data);
        $provider = (string) ($data['provider'] ?? '');
        $preset   = AiModelPresetEnum::get($provider);
        if (! $preset) {
            throw $this->exception('模型供应商不存在');
        }

        $modelId   = trim((string) ($data['model_id'] ?? ''));
        $modelType = (int) ($data['model_type'] ?? AiModelEnum::MODEL_TYPE_CHAT);
        if (! in_array($modelType, [AiModelEnum::MODEL_TYPE_CHAT, AiModelEnum::MODEL_TYPE_EMBEDDING], true)) {
            $modelType = AiModelEnum::MODEL_TYPE_CHAT;
        }

        $data['provider']    = $provider;
        $data['config_type']    = $this->resolveConfigType($preset, (int) ($data['config_type'] ?? 0));
        $data['base_url']       = $this->resolveBaseUrlByConfigType($data, $preset);
        $data['is_full_url']    = (int) ($data['is_full_url'] ?? AiModelEnum::FULL_URL_OFF) === AiModelEnum::FULL_URL_ON
            ? AiModelEnum::FULL_URL_ON
            : AiModelEnum::FULL_URL_OFF;
        $data['chat_path']      = $this->path((string) (($data['chat_path'] ?? '') ?: $preset['chat_path'] ?: AiModelEnum::DEFAULT_CHAT_PATH));
        $data['embedding_path'] = $this->path((string) (($data['embedding_path'] ?? '') ?: $preset['embedding_path'] ?: AiModelEnum::DEFAULT_EMBEDDING_PATH));
        $data['status']         = (int) ($data['status'] ?? AiModelEnum::STATUS_ENABLED);
        $data['model_id']       = $modelId;
        $data['model_type']     = $modelType;
        $data['enable_thinking'] = $this->normalizeThinkingMode($data['enable_thinking'] ?? AiModelEnum::THINKING_DEFAULT);
        $data['json']            = $this->normalizeAdvancedJson($data['json'] ?? []);

        // 剔除旧契约字段与仅接口层字段，避免写入库
        unset(
            $data['id'],
            $data['has_api_key'],
            $data['driver'],
            $data['chat_model'],
            $data['embedding_model'],
            $data['enabled_embedding']
        );

        if ($data['is_full_url'] === AiModelEnum::FULL_URL_ON && $data['base_url'] === '') {
            throw $this->exception('开启完整URL时请填写模型请求地址');
        }

        return $data;
    }

    /**
     * 规范化思考模式取值.
     *
     * @param mixed $value 原始取值
     */
    private function normalizeThinkingMode(mixed $value): int
    {
        $mode = (int) $value;

        return in_array($mode, [
            AiModelEnum::THINKING_OFF,
            AiModelEnum::THINKING_ON,
            AiModelEnum::THINKING_DEFAULT,
        ], true) ? $mode : AiModelEnum::THINKING_DEFAULT;
    }

    /**
     * 规范化高级配置 json（空值剔除，供入库与调用共用）.
     *
     * @param mixed $json 原始高级配置
     *
     * @return array<string, int|float>
     */
    private function normalizeAdvancedJson(mixed $json): array
    {
        $raw      = is_array($json) ? $json : [];
        $settings = [
            AiModelEnum::ADVANCED_INPUT_TOKENS    => $this->nullablePositiveInt($raw[AiModelEnum::ADVANCED_INPUT_TOKENS] ?? null),
            AiModelEnum::ADVANCED_OUTPUT_TOKENS   => $this->nullablePositiveInt($raw[AiModelEnum::ADVANCED_OUTPUT_TOKENS] ?? null),
            AiModelEnum::ADVANCED_MAX_TOOL_ROUNDS => $this->nullablePositiveInt($raw[AiModelEnum::ADVANCED_MAX_TOOL_ROUNDS] ?? null),
            AiModelEnum::ADVANCED_TEMPERATURE     => $this->nullableFloat($raw[AiModelEnum::ADVANCED_TEMPERATURE] ?? null, 0.0, 2.0),
            AiModelEnum::ADVANCED_TOP_P           => $this->nullableFloat($raw[AiModelEnum::ADVANCED_TOP_P] ?? null, 0.0, 1.0),
            AiModelEnum::ADVANCED_TOP_K           => $this->nullableIntInRange($raw[AiModelEnum::ADVANCED_TOP_K] ?? null, 1, 100),
        ];

        return array_filter(
            $settings,
            static fn ($value) => $value !== null
        );
    }

    /**
     * @param mixed $value
     */
    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * @param mixed $value
     */
    private function nullableIntInRange(mixed $value, int $min, int $max): ?int
    {
        $int = $this->nullablePositiveInt($value);
        if ($int === null) {
            return null;
        }

        return ($int >= $min && $int <= $max) ? $int : null;
    }

    /**
     * @param mixed $value
     */
    private function nullableFloat(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $float = (float) $value;
        if ($float < $min || $float > $max) {
            return null;
        }

        return $float;
    }

    /**
     * 按配置类型解析模型基础地址.
     *
     * - 一号通：固定官方地址（applyYihaotongCredentials 已写入）
     * - 预设：未传则取供应商默认 base_url；已传（如编辑态自定义中转）则保留
     * - 自定义：必须使用客户端传入的地址
     *
     * @param array<string, mixed> $data 请求数据
     * @param array<string, mixed> $preset 供应商预设
     */
    private function resolveBaseUrlByConfigType(array $data, array $preset): string
    {
        $configType = (int) ($data['config_type'] ?? 0);
        $clientUrl  = rtrim(trim((string) ($data['base_url'] ?? '')), '/');

        if ($configType === AiModelEnum::CONFIG_YIHAOTONG) {
            return rtrim((string) ($clientUrl !== '' ? $clientUrl : AiModelEnum::YIHAOTONG_BASE_URL), '/');
        }

        if ($configType === AiModelEnum::CONFIG_PRESET) {
            $baseUrl = $clientUrl !== '' ? $clientUrl : rtrim((string) ($preset['base_url'] ?? ''), '/');
            if ($baseUrl === '') {
                throw $this->exception('该供应商未配置默认请求地址');
            }

            return $baseUrl;
        }

        // 自定义 / OpenAI 兼容
        if ($clientUrl === '') {
            throw $this->exception('请填写模型请求地址');
        }

        return $clientUrl;
    }

    /**
     * 一号通配置自动写入供应商、基础地址与 API Key.
     *
     * 前端不传 / 不展示 base_url、api_key，一律按 config_type 在服务端补全，忽略客户端传入值。
     *
     * @param array<string, mixed> $data 请求或表单数据
     *
     * @return array<string, mixed>
     */
    private function applyYihaotongCredentials(array $data): array
    {
        if (! $this->isYihaotongConfig($data)) {
            return $data;
        }

        if (! $this->isYihaotongConfigured()) {
            throw $this->exception('请先在系统设置中配置一号通AppId与AppSecret');
        }

        $data['config_type'] = AiModelEnum::CONFIG_YIHAOTONG;
        $data['provider']    = AiModelEnum::DRIVER_YIHAOTONG;
        $data['base_url']    = AiModelEnum::YIHAOTONG_BASE_URL;
        $data['api_key']     = $this->resolveYihaotongApiKey();
        $data['is_full_url'] = AiModelEnum::FULL_URL_OFF;

        return $data;
    }

    /**
     * 列表/详情对外脱敏：永不回传明文 api_key.
     *
     * 非一号通：有密钥时 api_key 置空并标记 has_api_key=true，供编辑表单「留空不修改」.
     * 一号通：额外清空 base_url，has_api_key 固定 false（密钥由系统配置，前端不展示输入框）.
     *
     * @param array<string, mixed> $data 模型数据
     *
     * @return array<string, mixed>
     */
    private function hideApiKeySecrets(array $data): array
    {
        $hasKey = trim((string) ($data['api_key'] ?? '')) !== '';

        if ($this->isYihaotongConfig($data)) {
            $data['api_key']     = '';
            $data['base_url']    = '';
            $data['has_api_key'] = false;

            return $data;
        }

        $data['has_api_key'] = $hasKey;
        $data['api_key']     = '';

        return $data;
    }

    /**
     * 编辑/测试/拉取时：客户端未传 api_key 则按 id 回填库内密钥.
     *
     * 一号通密钥由 applyYihaotongCredentials 补全，此处跳过.
     *
     * @param array<string, mixed> $data 请求数据，可含 id、api_key
     *
     * @return array<string, mixed>
     */
    private function fillStoredApiKey(array $data): array
    {
        if ($this->isYihaotongConfig($data)) {
            return $data;
        }

        $apiKey = trim((string) ($data['api_key'] ?? ''));
        if ($apiKey !== '') {
            $data['api_key'] = $apiKey;

            return $data;
        }

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            return $data;
        }

        $stored = $this->dao->get($id);
        if ($stored && trim((string) $stored->api_key) !== '') {
            $data['api_key'] = (string) $stored->api_key;
        }

        return $data;
    }

    /**
     * 模型缺少 API Key 时的提示文案.
     *
     * 一号通模型的 api_key 由系统配置同步，缺失即说明一号通尚未配置，引导去后台配置.
     *
     * @param AiModel $model 缺少 API Key 的模型
     * @param string $default 非一号通模型的提示文案
     *
     * @return string 提示文案
     */
    public function missingApiKeyMessage(AiModel $model, string $default): string
    {
        return $this->isYihaotongConfig($model) ? '尚未配置一号通，请前往管理后台进行配置' : $default;
    }

    /**
     * 是否为一号通配置.
     *
     * @param AiModel|array<string, mixed> $data 请求或表单数据，或模型实例
     */
    private function isYihaotongConfig(AiModel|array $data): bool
    {
        $configType = (int) ($data['config_type'] ?? 0);
        $provider   = (string) ($data['provider'] ?? '');

        return $configType === AiModelEnum::CONFIG_YIHAOTONG
            || $provider === AiModelEnum::DRIVER_YIHAOTONG;
    }

    /**
     * 解析供应商配置类型.
     *
     * @param array<string, mixed> $preset 供应商预设
     * @param int $fallback 请求中的 config_type（非预设/自定义时回退）
     */
    private function resolveConfigType(array $preset, int $fallback = 0): int
    {
        if (! empty($preset['yihaotong'])) {
            return AiModelEnum::CONFIG_YIHAOTONG;
        }
        if (! empty($preset['custom'])) {
            return AiModelEnum::CONFIG_CUSTOM;
        }
        if ($fallback === AiModelEnum::CONFIG_YIHAOTONG) {
            return AiModelEnum::CONFIG_YIHAOTONG;
        }

        return $fallback ?: AiModelEnum::CONFIG_PRESET;
    }

    /**
     * 获取一号通 API Key，缓存 3 分钟.
     *
     * @throws \Throwable 一号通未配置或获取失败
     */
    protected function resolveYihaotongApiKey(): string
    {
        $cached = Cache::get(AiModelEnum::YIHAOTONG_API_KEY_CACHE);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        // 队列进程等场景下 Factory 静态缓存可能仍持有旧凭证的 Client，取 Key 前先重置
        YihaotongFactory::reset();
        $apiKey = app(SmsService::class)->getAiApiKey();
        if (! is_string($apiKey) || $apiKey === '') {
            throw $this->exception('获取一号通API Key失败，请检查一号通AppId/AppSecret配置');
        }

        Cache::put(AiModelEnum::YIHAOTONG_API_KEY_CACHE, $apiKey, AiModelEnum::YIHAOTONG_API_KEY_TTL);

        return $apiKey;
    }

    /**
     * 规范接口路径.
     */
    private function path(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    /**
     * 格式化模型选择数据.
     */
    private function formatSelect(array $chatModels, array $embeddingModels, bool $custom = false): array
    {
        return [
            [
                'label'    => '会话模型',
                'value'    => 'LLM',
                'custom'   => $custom,
                'children' => array_map(fn (string $model) => ['label' => $model, 'value' => $model], array_values($chatModels)),
            ],
            [
                'label'    => '向量模型',
                'value'    => 'Embedding',
                'custom'   => $custom,
                'children' => array_map(fn (string $model) => ['label' => $model, 'value' => $model], array_values($embeddingModels)),
            ],
        ];
    }

    /**
     * 请求供应商模型列表.
     */
    private function requestModels(string $baseUrl, string $modelsPath, string $apiKey, string $auth): array
    {
        $headers = ['Content-Type: application/json'];
        if ($auth === AiModelEnum::AUTH_API_KEY) {
            $headers[] = 'api-key: ' . $apiKey;
        } else {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        try {
            [$httpCode, $content] = (new HttpService())->setHeader($headers)->requests(
                $baseUrl . $modelsPath,
                'GET',
                '',
                true,
                30
            );
        } catch (\Throwable $e) {
            throw $this->exception('获取模型列表失败：' . $e->getMessage());
        }

        if (! $httpCode) {
            throw $this->exception('获取模型列表失败：请求中断');
        }

        $response = json_decode((string) $content, true, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($response)) {
            throw $this->exception('获取模型列表失败：响应解析错误');
        }

        if ((int) $httpCode >= 400) {
            $message = (string) ($response['error']['message'] ?? $response['message'] ?? $response['msg'] ?? ('HTTP ' . $httpCode));
            throw $this->exception('获取模型列表失败：' . $message);
        }

        if (isset($response['error'])) {
            $message = is_array($response['error'])
                ? (string) ($response['error']['message'] ?? json_encode($response['error'], JSON_UNESCAPED_UNICODE))
                : (string) $response['error'];
            throw $this->exception('获取模型列表失败：' . $message);
        }

        return $response;
    }

    /**
     * 解析模型 ID 列表.
     */
    private function parseModelIds(array $response): array
    {
        $items = [];
        if (isset($response['data']) && is_array($response['data'])) {
            $items = $response['data'];
        } elseif (isset($response['models']) && is_array($response['models'])) {
            $items = $response['models'];
        } elseif ($this->isListArray($response)) {
            $items = $response;
        }

        $ids = [];
        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $ids[] = $item;
                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? $item['model'] ?? $item['name'] ?? '');
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * 区分聊天模型与向量模型.
     */
    private function classifyModels(array $ids): array
    {
        $chat       = [];
        $embedding  = [];
        $skipTokens = ['tts', 'asr', 'whisper', 'dall-e', 'stable-diffusion', 'voice', 'rerank', 'image', 'moderation'];

        foreach ($ids as $id) {
            $lower = strtolower($id);
            if ($this->isEmbeddingModel($lower)) {
                $embedding[] = $id;
                continue;
            }
            foreach ($skipTokens as $token) {
                if (str_contains($lower, $token)) {
                    continue 2;
                }
            }
            $chat[] = $id;
        }

        return [$chat, $embedding];
    }

    /**
     * 是否为向量模型.
     */
    private function isEmbeddingModel(string $id): bool
    {
        return str_contains($id, 'embed')
            || str_contains($id, 'bge-')
            || str_contains($id, 'm3e')
            || str_contains($id, 'tao-')
            || str_contains($id, 'text-embedding');
    }

    /**
     * 判断是否为列表数组(兼容 PHP 8.0).
     */
    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * 校验模型 ID 与类型，并校验向量模型供应商约束.
     *
     * @param array<string, mixed> $data 规范化后的配置
     *
     * @throws \Throwable 模型 ID 为空、类型非法或向量模型配置非法
     */
    private function assertModelChoice(array $data): void
    {
        $modelId   = trim((string) ($data['model_id'] ?? ''));
        $modelType = (int) ($data['model_type'] ?? 0);

        if ($modelId === '') {
            throw $this->exception('请填写模型ID');
        }

        if (! in_array($modelType, [AiModelEnum::MODEL_TYPE_CHAT, AiModelEnum::MODEL_TYPE_EMBEDDING], true)) {
            throw $this->exception('模型类型无效');
        }

        if ($modelType !== AiModelEnum::MODEL_TYPE_EMBEDDING) {
            return;
        }

        if (($data['provider'] ?? '') === AiModelEnum::DRIVER_VOLCENGINE
            && ! str_starts_with($modelId, 'ep-')) {
            throw $this->exception('火山引擎模型ID必须填写 Embedding 接入点 ID（以 ep- 开头），不能填写模型展示名称');
        }
    }

    /**
     * 测试 embedding 连接.
     */
    private function testEmbeddingConnection(array $data, array $preset, string $auth): void
    {
        $this->assertModelChoice($data);

        $option = $this->makeEmbeddingOption($data);

        try {
            $response = (new BaseCurl($data['api_key'], $option->baseUrl, $auth))->setBody($option)->send(
                url: $option->url,
                body: ['model' => $option->model, 'input' => ['ping']],
                timeout: 30
            );
        } catch (\Throwable $e) {
            // 供应商不提供 /embeddings 时通常返回 HTML 404，BaseCurl 只抛 JSON 解析失败
            throw $this->exception($this->formatRequestError($e, '向量模型', $option->baseUrl . $option->url));
        }

        if (! empty($response['error'])) {
            $error   = $response['error'];
            $message = is_array($error)
                ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE))
                : (string) $error;
            throw $this->exception($this->formatEmbeddingApiError($message, (string) ($data['provider'] ?? '')));
        }

        $embedding = (array) ($response['data'][0]['embedding'] ?? []);
        if ($embedding === []) {
            throw $this->exception('向量模型未返回有效向量');
        }
    }

    /**
     * 把请求层异常转成可排查的提示.
     *
     * BaseCurl 位于 crmeb 内核不可改动，非 JSON 响应与网络中断都只抛出底层文案，
     * 这里按异常内容补上「该往哪儿查」的信息。
     *
     * @param \Throwable $e        底层异常
     * @param string     $scene    场景名（会话模型 / 向量模型）
     * @param string     $endpoint 实际请求的完整地址
     */
    private function formatRequestError(\Throwable $e, string $scene, string $endpoint): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Failed to parse JSON')) {
            return sprintf(
                '%s连接测试失败：%s 未返回有效的接口响应，该服务商可能不提供此接口，请检查请求地址与模型名称是否填写正确',
                $scene,
                $endpoint
            );
        }

        return sprintf('%s连接测试失败：%s', $scene, $message !== '' ? $message : '请求异常，请检查请求地址与网络');
    }

    private function formatEmbeddingApiError(string $message, string $provider): string
    {
        if ($provider !== AiModelEnum::DRIVER_VOLCENGINE) {
            return $message !== '' ? $message : '向量模型连接测试失败';
        }

        if (str_contains($message, 'does not support this api') || str_contains($message, '不支持')) {
            return $message . '。当前接入点绑定的是聊天/多模态模型，请在火山方舟控制台单独创建 Embedding 模型接入点（ep-xxx），填入模型ID';
        }

        if (str_contains($message, 'does not exist') || str_contains($message, '不存在')) {
            return $message . '。火山方舟模型ID需填写 Embedding 模型接入点 ID（ep-xxx）';
        }

        return $message !== '' ? $message : '向量模型连接测试失败';
    }
}
