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

use App\Constants\Ai\AiConversationEnum;
use App\Http\Dao\Ai\AiMcpServiceDao;
use App\Http\Model\Ai\AiApplication;
use App\Mcp\ExternalMcpClient;
use App\Mcp\ToolRegistry;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MCP 服务管理：chat_mcp_services CRUD、单条 server 配置绑定与应用侧工具发现.
 */
class AiMcpService extends BaseService
{
    public const TOOL_PREFIX = 'ext_mcp_';

    /** 人事请假/加班等写操作对应的提交工具. */
    public const PERSONNEL_APPROVE_WRITE_TOOL = 'approve_create';

    public function __construct(AiMcpServiceDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建 MCP 服务.
     *
     * 名称与简介缺省时自动从标准 MCP 配置 JSON（server 节点 name/description 字段）提取.
     *
     * @param array<string, mixed> $data
     */
    public function resourceSave(array $data)
    {
        // 规范化会丢弃 server 节点 name/description，须先从原始标准 JSON 提取名称与简介
        [$name, $info, $serverKey] = $this->extractNameInfoFromConfig($this->arrayValue($data['config_json'] ?? []));
        $data               = $this->normalizeServiceRecord($data, true);
        $data['is_default'] = 0;
        $data['name']       = trim((string) ($data['name'] ?? '')) ?: ($name !== '' ? $name : $serverKey);
        $data['info']       = trim((string) ($data['info'] ?? '')) ?: $info;
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw $this->exception('无法提取MCP名称，请在配置JSON中填写 name 字段或使用 mcpServers 键名');
        }
        $this->assertServiceRecordBindable($data);

        return $this->dao->create($data);
    }

    /**
     * 更新 MCP 服务.
     *
     * 新配置可从标准 MCP JSON 提取到名称/简介时同步更新，否则保留原值；手动传入优先.
     *
     * @param int|string $id
     * @param array<string, mixed> $data
     */
    public function resourceUpdate($id, array $data)
    {
        $this->assertDefaultServiceMutable((int) $id);

        $existing = $this->modelToArray($this->dao->get((int) $id));
        // 规范化会丢弃 server 节点 name/description，须先从原始标准 JSON 提取名称与简介
        [$name, $info] = $this->extractNameInfoFromConfig($this->arrayValue($data['config_json'] ?? []));
        $data = $this->normalizeServiceRecord($data, false);
        if ($data !== []) {
            $merged = array_merge($existing, $data);
            $this->assertServiceRecordBindable($merged);
            if ($name !== '' && ! isset($data['name'])) {
                $data['name'] = $name;
            }
            if ($info !== '' && ! isset($data['info'])) {
                $data['info'] = $info;
            }
        }

        return $this->dao->update((int) $id, $data);
    }

    public function resourceDelete($id, ?string $key = null)
    {
        $this->assertDefaultServiceMutable((int) $id);

        return $this->dao->delete((int) $id);
    }

    /**
     * 获取 MCP 服务编辑详情.
     *
     * @param int $id MCP 服务 ID
     * @param array<string, mixed> $other 预留扩展参数
     *
     * @return array<string, mixed> 服务详情
     *
     * @throws \Throwable 记录不存在
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if ($id <= 0) {
            throw $this->exception('缺少必要参数');
        }

        $data = $this->modelToArray($this->dao->get($id));
        if ($data === []) {
            throw $this->exception('MCP服务不存在');
        }

        return $data;
    }

    /**
     * 获取创建表单附加数据.
     *
     * @param array<string, mixed> $other 预留扩展参数
     *
     * @return array<string, mixed>
     */
    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 修改 MCP 服务状态（含 show 接口切换 status）.
     *
     * @param mixed $id
     * @param array<string, mixed> $data
     */
    public function resourceShowUpdate($id, array $data)
    {
        $this->assertDefaultServiceMutable((int) $id);

        return $this->showUpdate($id, $data);
    }

    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, ['*'], $page, $limit, $sort);
        $count          = $this->dao->count($where);

        return $this->listData($list, $count);
    }

    public function assertBindableMcpJson(
        array $mcpJson,
        int $appType = AiConversationEnum::APP_TYPE_INTERNAL,
        ?array $serviceIds = null
    ): void {
        foreach ($this->resolveBindingServices($serviceIds) as $service) {
            if ($appType === AiConversationEnum::APP_TYPE_EXTERNAL && $this->isBuiltinService($service)) {
                throw $this->exception('外部应用不支持关联内置MCP');
            }
        }
        if (McpConfigParser::servers($mcpJson) !== []) {
            try {
                McpConfigParser::assertBindable($mcpJson, false);
            } catch (\InvalidArgumentException $e) {
                throw $this->exception($e->getMessage());
            }
        }
    }

    /**
     * 根据前端传入的 MCP 服务 ID 列表生成应用绑定（mcp_json 仅由后端从表数据合并写入）.
     *
     * @param array<int, int> $serviceIds
     *
     * @return array{mcp_service_ids: ?array<int, int>, mcp_json: ?array<string, mixed>}
     */
    public function normalizeApplicationMcpBinding(array $serviceIds, int $appType): array
    {
        $serviceIds = $this->normalizeServiceIds($serviceIds);
        if ($serviceIds === []) {
            return ['mcp_service_ids' => null, 'mcp_json' => null];
        }

        $mergedServers = [];
        foreach ($serviceIds as $serviceId) {
            $stored = $this->modelToArray($this->dao->get($serviceId));
            if ($stored === []) {
                throw $this->exception('MCP服务不存在：' . $serviceId);
            }
            if ((int) ($stored['status'] ?? 1) !== 1) {
                throw $this->exception('MCP服务已禁用：' . ($stored['name'] ?? $serviceId));
            }
            foreach (McpConfigParser::servers($this->arrayValue($stored['config_json'] ?? [])) as $key => $server) {
                $mergedServers[$key] = $server;
            }
        }

        if ($mergedServers === []) {
            throw $this->exception('所选 MCP 服务配置无效');
        }

        $payload = McpConfigParser::buildApplicationJson($mergedServers);
        $this->assertBindableMcpJson($payload, $appType, $serviceIds);

        return [
            'mcp_service_ids' => $serviceIds,
            'mcp_json'        => $payload,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function servicesFromApplication(AiApplication $app): array
    {
        $serviceIds = $this->normalizeServiceIds($app->mcp_service_ids ?? null);
        if ($serviceIds === []) {
            return [];
        }

        $result = [];
        foreach ($serviceIds as $serviceId) {
            $stored = $this->modelToArray($this->dao->get($serviceId));
            if ($stored !== []) {
                $result[] = $stored;
            }
        }

        return $result;
    }

    /**
     * 按应用绑定顺序返回 MCP 服务基本信息（不含 config_json）.
     *
     * 已删除的服务 ID 会跳过，不出现在结果中.
     *
     * @param AiApplication $app AI 应用
     *
     * @return array<int, array{id: int, name: string, info: string, status: int, is_default: int, sort: int}>
     */
    public function basicInfoFromApplication(AiApplication $app): array
    {
        $basics = [];
        foreach ($this->servicesFromApplication($app) as $service) {
            $basics[] = [
                'id'         => (int) ($service['id'] ?? 0),
                'name'       => (string) ($service['name'] ?? ''),
                'info'       => (string) ($service['info'] ?? ''),
                'status'     => (int) ($service['status'] ?? 0),
                'is_default' => (int) ($service['is_default'] ?? 0),
                'sort'       => (int) ($service['sort'] ?? 0),
            ];
        }

        return $basics;
    }

    public function isBuiltinService(array $service): bool
    {
        if ((int) ($service['is_default'] ?? 0) === 1) {
            return true;
        }

        foreach (McpConfigParser::servers($this->arrayValue($service['config_json'] ?? [])) as $server) {
            if (McpConfigParser::isBuiltinServer($server)) {
                return true;
            }
        }

        return false;
    }

    public function testConnection(array $data): array
    {
        $record = $this->normalizeServiceRecord($data, false);
        $config = $this->arrayValue($record['config_json'] ?? $data['config_json'] ?? []);
        McpConfigParser::assertBindable($config, (int) ($data['is_default'] ?? 0) === 1);

        $resolved = $this->resolvedConfigFromRecord(array_merge($data, $record));
        if ($resolved['local']) {
            $tools = ToolRegistry::getToolsMeta($resolved['module']);

            return ['success' => true, 'message' => '连接成功', 'server' => [], 'tool_count' => count($tools)];
        }
        if ($resolved['url'] === '') {
            throw $this->exception('服务地址不能为空');
        }

        return (new ExternalMcpClient($resolved['url'], $resolved['headers'], $resolved['timeout']))->testConnection();
    }

    public function tools(array|int $service): array
    {
        if (is_int($service)) {
            $service = $this->modelToArray($this->dao->get($service));
        }
        if ($service === []) {
            throw $this->exception('MCP服务不存在');
        }

        $resolved = $this->resolvedConfigFromRecord($service);
        if ($resolved['local']) {
            return ToolRegistry::getToolsMeta($resolved['module']);
        }
        if ($resolved['url'] === '') {
            throw $this->exception('服务地址不能为空');
        }

        $tools = (new ExternalMcpClient($resolved['url'], $resolved['headers'], $resolved['timeout']))->listTools();
        if (isset($tools['error'])) {
            throw $this->exception((string) ($tools['message'] ?? 'MCP工具获取失败'));
        }

        return $tools;
    }

    /** @return array<int, string> */
    public function modulesForApp(AiApplication $app): array
    {
        $modules = [];
        foreach ($this->configsForApp($app) as $config) {
            if (empty($config['local']) || empty($config['module'])) {
                continue;
            }
            $module = trim((string) $config['module']);
            if ($module !== '' && ToolRegistry::getModuleConfig($module) !== []) {
                $modules[] = $module;
            }
        }

        return array_values(array_unique($modules));
    }

    /**
     * @param array<int, string> $modules
     * @param null|array<int, string> $onlyNames
     */
    public function localBusinessToolsMetaForModules(array $modules, ?array $onlyNames = null): array
    {
        $seen   = [];
        $result = [];
        foreach (array_values(array_unique(array_filter(array_map('strval', $modules)))) as $module) {
            foreach (ToolRegistry::getBusinessToolsMeta($module, $onlyNames) as $tool) {
                $name = (string) ($tool['name'] ?? '');
                if ($name === '' || isset($seen[$name])) {
                    continue;
                }
                $seen[$name] = true;
                $result[]    = $tool;
            }
        }

        return $result;
    }

    /**
     * @param null|array<int, string> $onlyNames null=管理端/调试全量发现；会话路径应传具体名单（空数组=零业务工具）
     *
     * @return array<string, array<string, mixed>>
     */
    public function toolsForApp(AiApplication $app, ?array $onlyNames = null): array
    {
        $uid         = (int) auth('admin')->id();
        $onlyNames   = $onlyNames === null
            ? null
            : array_values(array_unique(array_filter(array_map('strval', $onlyNames))));
        // 注意：会话编排禁止把「空白名单」翻成 null；空数组表示不发现业务工具.
        $fingerprint = md5(json_encode([
            'ids'   => $app->mcp_service_ids ?? [],
            'json'  => $app->mcp_json ?? [],
            'tools' => $onlyNames,
        ], JSON_UNESCAPED_UNICODE));
        $key = 'ai_mcp_tools:' . (int) ($app->id ?? 0) . ':' . $uid . ':' . $fingerprint;

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $tools = $this->discoverToolsForApp($app, $onlyNames);
        Cache::put(
            $key,
            $tools,
            $tools === [] ? AiConversationEnum::MCP_TOOLS_EMPTY_CACHE_SECONDS : AiConversationEnum::MCP_TOOLS_CACHE_SECONDS
        );

        return $tools;
    }

    /** @return array<int, array<string, mixed>> */
    public function configsForApp(AiApplication $app): array
    {
        $allowLocal = (int) ($app->app_type ?? AiConversationEnum::APP_TYPE_INTERNAL)
            === AiConversationEnum::APP_TYPE_INTERNAL;

        $mcpJson = $this->arrayValue($app->mcp_json ?? []);
        $servers = McpConfigParser::servers($mcpJson);
        if ($servers === []) {
            $binding = $this->normalizeApplicationMcpBinding(
                $this->normalizeServiceIds($app->mcp_service_ids ?? null),
                (int) ($app->app_type ?? AiConversationEnum::APP_TYPE_INTERNAL)
            );
            $servers = McpConfigParser::servers($this->arrayValue($binding['mcp_json'] ?? []));
        }

        $result = [];
        foreach ($servers as $serverKey => $server) {
            if (! is_array($server)) {
                continue;
            }
            if (isset($server['command'])) {
                Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning(
                    'Skip stdio/command MCP config; only HTTP SSE is supported',
                    ['server_key' => $serverKey]
                );
                continue;
            }

            $config = McpConfigParser::resolveServer($serverKey, $server);
            if ($config['local']) {
                if (! $allowLocal) {
                    continue;
                }
                $result[] = $config;
                continue;
            }
            if ($config['url'] === '') {
                continue;
            }
            $result[] = $config;
        }

        return $result;
    }

    /** @param array<string, mixed> $service */
    public function resolvedConfigFromRecord(array $service): array
    {
        $servers = McpConfigParser::servers($this->arrayValue($service['config_json'] ?? []));
        if ($servers === [] && isset($service['service_url'])) {
            $servers = McpConfigParser::servers(McpConfigParser::fromLegacyServiceRecord($service));
        }
        if ($servers === []) {
            return [
                'url'      => '',
                'headers'  => [],
                'timeout'  => 30,
                'module'   => null,
                'local'    => false,
                'internal' => false,
            ];
        }

        $serverKey = array_key_first($servers);

        return McpConfigParser::resolveServer((string) $serverKey, (array) $servers[$serverKey]);
    }

    /**
     * 规范化 MCP 服务 ID 数组.
     *
     * @param mixed $serviceIds 原始 ID 列表
     *
     * @return array<int, int>
     */
    public function normalizeServiceIds(mixed $serviceIds): array
    {
        if (is_string($serviceIds)) {
            $decoded    = json_decode($serviceIds, true);
            $serviceIds = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($serviceIds)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $serviceIds
        ), static fn (int $id): bool => $id > 0));
    }

    /** @param null|array<int, int> $serviceIds */
    private function resolveBindingServices(?array $serviceIds = null): array
    {
        $ids = $this->normalizeServiceIds($serviceIds);
        if ($ids === []) {
            return [];
        }

        $result = [];
        foreach ($ids as $serviceId) {
            $stored = $this->modelToArray($this->dao->get($serviceId));
            if ($stored === []) {
                throw $this->exception('MCP服务不存在：' . $serviceId);
            }
            $result[] = $stored;
        }

        return $result;
    }

    /**
     * 从标准 MCP 配置 JSON 提取显式配置的名称与简介.
     *
     * 兼容 mcpServers 包装与单条 server 扁平格式：名称取 server 节点 name 字段、
     * 简介取 description 字段，未配置时返回空串（不使用派生 key 兜底，避免更新时覆盖原值）.
     *
     * @param array<string, mixed> $config 原始（未规范化）MCP 配置 JSON
     *
     * @return array{0: string, 1: string, 2: string} [名称, 简介, serverKey]，均截断至字段长度上限
     */
    private function extractNameInfoFromConfig(array $config): array
    {
        $servers = McpConfigParser::servers($config);
        if ($servers === []) {
            return ['', '', ''];
        }

        $serverKey = (string) array_key_first($servers);
        $server    = (array) $servers[$serverKey];

        return [
            mb_substr(trim((string) ($server['name'] ?? '')), 0, 100),
            mb_substr(trim((string) ($server['description'] ?? '')), 0, 200),
            mb_substr($serverKey, 0, 100),
        ];
    }

    /** @param array<string, mixed> $record */
    private function assertServiceRecordBindable(array $record): void
    {
        $config = $this->arrayValue($record['config_json'] ?? []);
        if ($config === []) {
            throw $this->exception('MCP配置不能为空');
        }

        try {
            McpConfigParser::assertBindable($config, (int) ($record['is_default'] ?? 0) === 1);
        } catch (\InvalidArgumentException $e) {
            throw $this->exception($e->getMessage());
        }
    }

    /** 系统默认 MCP（is_default=1）不允许修改或删除. */
    private function assertDefaultServiceMutable(int $id): void
    {
        if ((int) $this->dao->value($id, 'is_default') === 1) {
            throw $this->exception('系统默认MCP不允许修改或删除');
        }
    }

    /** @param array<string, mixed> $data */
    private function normalizeServiceRecord(array $data, bool $create): array
    {
        if (! $create) {
            $data = array_filter($data, static fn ($value): bool => $value !== '' && $value !== []);
            unset($data['is_default']);
        }

        if (! isset($data['config_json'])) {
            return $data;
        }

        $config = $this->arrayValue($data['config_json']);
        try {
            $data['config_json'] = McpConfigParser::normalizeServiceRecordConfig($config);
        } catch (\InvalidArgumentException $e) {
            throw $this->exception($e->getMessage());
        }

        return $data;
    }

    /**
     * @param null|array<int, string> $onlyNames
     *
     * @return array<string, array<string, mixed>>
     */
    private function discoverToolsForApp(AiApplication $app, ?array $onlyNames = null): array
    {
        $allowed = $onlyNames === null ? null : array_fill_keys($onlyNames, true);
        $tools   = [];
        foreach ($this->configsForApp($app) as $config) {
            try {
                $items = $config['local']
                    ? ToolRegistry::getBusinessToolsMeta($config['module'], $onlyNames)
                    : (new ExternalMcpClient($config['url'], $config['headers'], $config['timeout']))->listTools();
                if (isset($items['error'])) {
                    continue;
                }
                foreach ($items as $item) {
                    $name = (string) ($item['name'] ?? '');
                    if ($name === '' || ($allowed !== null && ! isset($allowed[$name]))) {
                        continue;
                    }
                    $tools[$name] = [
                        'name'        => $name,
                        'description' => (string) ($item['description'] ?? ''),
                        'inputSchema' => (array) ($item['inputSchema'] ?? ['type' => 'object', 'properties' => []]),
                        'config'      => $config,
                    ];
                }
            } catch (\Throwable $e) {
                Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning('AI external MCP discovery failed', [
                    'url'   => $config['url'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $tools;
    }

    /** @return array<string, mixed> */
    private function modelToArray(mixed $model): array
    {
        if ($model === null) {
            return [];
        }
        if (is_array($model)) {
            return $model;
        }
        if (is_object($model) && method_exists($model, 'toArray')) {
            return $model->toArray();
        }
        if (is_object($model)) {
            return get_object_vars($model);
        }

        return [];
    }

    /** @return array<string, mixed> */
    private function arrayValue(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * 当前应用是否启用了 MCP 数据源.
     */
    public function mcpEnabledForApp(AiApplication $app): bool
    {
        return AiConversationEnum::hasSource($app->sourceTypeMask(), AiConversationEnum::SOURCE_MCP);
    }

    /**
     * 当前应用本轮实际可注入的本地业务工具名.
     *
     * @return array<int, string>
     */
    public function localToolNamesForApp(AiApplication $app): array
    {
        if (! $this->mcpEnabledForApp($app)) {
            return [];
        }

        $configured = $this->configuredToolsForApp($app);
        $modules    = $this->modulesForApp($app);

        if ($configured !== []) {
            $pool = $modules !== []
                ? $this->moduleToolNames($modules)
                : ToolRegistry::getToolNames(null, false);

            return array_values(array_intersect($pool, $configured));
        }

        if ($modules !== []) {
            return $this->moduleToolNames($modules);
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function configuredToolsForApp(AiApplication $app): array
    {
        $tools   = [];
        $mcpJson = (array) ($app->mcp_json ?? []);
        if (isset($mcpJson['tools']) && is_array($mcpJson['tools'])) {
            $tools = $mcpJson['tools'];
        }
        $advanced = (array) ($app->json ?? []);
        if (isset($advanced['tools']) && is_array($advanced['tools'])) {
            $tools = array_merge($tools, $advanced['tools']);
        }

        return array_values(array_unique(array_filter(array_map(static function ($tool): string {
            if (is_array($tool)) {
                $tool = $tool['name'] ?? '';
            }

            return trim((string) $tool);
        }, $tools))));
    }

    /**
     * @param array<int, string> $modules
     *
     * @return array<int, string>
     */
    private function moduleToolNames(array $modules): array
    {
        $names = [];
        foreach ($modules as $module) {
            foreach (ToolRegistry::getModuleToolNames((string) $module, false) as $name) {
                $names[] = (string) $name;
            }
        }

        return array_values(array_unique($names));
    }

}
