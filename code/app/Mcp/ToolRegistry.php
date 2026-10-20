<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Mcp\Tools\Contracts\ToolInterface;
use Illuminate\Support\Facades\Cache;

class ToolRegistry
{
    private const META_VERSION_KEY = 'mcp:tools_meta_version';

    /** @var array<string, class-string<ToolInterface>>|null */
    private static ?array $toolClassMap = null;

    /** @var array<string, string> */
    private static array $toolDirs = [];

    /**
     * 自动发现所有工具实例（会实例化全部工具，仅在确需全量实例时使用）.
     *
     * @return array<string, ToolInterface>
     */
    public static function discover(): array
    {
        self::ensureDiscovered();

        return self::instantiateTools(array_keys(self::$toolClassMap));
    }

    /**
     * 获取已注册工具数量（不实例化）.
     */
    public static function countTools(): int
    {
        self::ensureDiscovered();

        return count(self::$toolClassMap);
    }

    /**
     * 获取工具名称列表.
     *
     * @return array<int, string>
     */
    public static function getToolNames(?string $module = null, bool $includeCommon = true): array
    {
        self::ensureDiscovered();

        if ($module === null || $module === '') {
            $names = array_keys(self::$toolClassMap);
            if (! $includeCommon) {
                $excluded = array_fill_keys(self::getAttachedContextToolNames(), true);
                $names = array_values(array_filter(
                    $names,
                    static fn (string $name): bool => ! isset($excluded[$name])
                ));
            }

            return $names;
        }

        return self::getModuleToolNames($module, $includeCommon);
    }

    /**
     * 扫描模块目录获取所有工具类.
     *
     * @return array<string, array<int, class-string<ToolInterface>>>
     */
    protected static function scanModules(string $basePath): array
    {
        $modules = [];

        if (! is_dir($basePath)) {
            return $modules;
        }

        $dirs = array_filter(
            scandir($basePath),
            fn ($d) => $d !== '.' && $d !== '..' && $d !== 'Abstract' && $d !== 'Traits' && is_dir($basePath . '/' . $d)
        );

        foreach ($dirs as $module) {
            $modulePath = $basePath . '/' . $module;
            $toolClasses = self::findToolClasses($modulePath, $module);
            if ($toolClasses !== []) {
                $modules[$module] = $toolClasses;
            }
        }

        return $modules;
    }

    /**
     * 在模块目录中查找工具类.
     *
     * @return array<int, class-string<ToolInterface>>
     */
    protected static function findToolClasses(string $modulePath, string $moduleNamespace): array
    {
        $classes = [];
        $files = glob($modulePath . '/*Tool.php') ?: [];

        foreach ($files as $file) {
            $className = self::getClassNameFromFile($file, $moduleNamespace);
            if ($className) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * 从文件获取类名.
     *
     * @return class-string<ToolInterface>|null
     */
    protected static function getClassNameFromFile(string $file, string $moduleNamespace): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        if (preg_match('/namespace\s+([^;]+)/', $content, $matches)) {
            $namespace = trim($matches[1]);
            if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
                return $namespace . '\\' . $classMatches[1];
            }
        }

        return null;
    }

    /**
     * 实例化工具.
     *
     * @param class-string<ToolInterface> $className
     */
    protected static function instantiateTool(string $className): ?ToolInterface
    {
        try {
            $mcpRequest = app(\App\Http\Controller\Mcp\McpRequest::class);

            return new $className($mcpRequest);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, string> $toolNames
     *
     * @return array<string, ToolInterface>
     */
    protected static function instantiateTools(array $toolNames): array
    {
        self::ensureDiscovered();

        $tools = [];
        foreach ($toolNames as $name) {
            $toolClass = self::$toolClassMap[$name] ?? null;
            if (! $toolClass) {
                continue;
            }
            $tool = self::instantiateTool($toolClass);
            if ($tool) {
                $tools[$name] = $tool;
            }
        }

        return $tools;
    }

    /**
     * 获取所有工具列表.
     *
     * @return array<string, ToolInterface>
     */
    public static function getTools(): array
    {
        return self::discover();
    }

    /**
     * 获取模块工具列表.
     *
     * @return array<string, ToolInterface>
     */
    public static function getModuleTools(?string $module = null, bool $includeCommon = true): array
    {
        return self::instantiateTools(self::getToolNames($module, $includeCommon));
    }

    /**
     * 获取指定工具.
     */
    public static function getTool(string $name, ?string $module = null): ?ToolInterface
    {
        self::ensureDiscovered();

        if ($module !== null && $module !== '') {
            if (! in_array($name, self::getModuleToolNames($module), true)) {
                return null;
            }
        } elseif (! isset(self::$toolClassMap[$name])) {
            return null;
        }

        $toolClass = self::$toolClassMap[$name] ?? null;
        if (! $toolClass) {
            return null;
        }

        $tool = self::instantiateTool($toolClass);

        return $tool && self::isToolEnabled($tool, $module) ? $tool : null;
    }

    /**
     * 获取上下文工具元数据（common + org/personnel，供 AI 单次注入）.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getContextToolsMeta(): array
    {
        $result = [];
        foreach (self::getAttachedContextToolNames() as $name) {
            $meta = self::resolveToolMeta($name, null);
            if ($meta !== null) {
                $result[] = $meta;
            }
        }

        return $result;
    }

    /**
     * 获取业务工具元数据（不含 common/context）.
     *
     * @param null|array<int, string> $onlyNames
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getBusinessToolsMeta(?string $module = null, ?array $onlyNames = null): array
    {
        return self::getToolsMeta($module, false, $onlyNames);
    }

    /**
     * 获取工具元数据列表 (用于 tools/list).
     *
     * @param null|array<int, string> $onlyNames
     */
    public static function getToolsMeta(?string $module = null, bool $includeCommon = true, ?array $onlyNames = null): array
    {
        $toolNames = self::getToolNames($module, $includeCommon);
        if ($onlyNames !== null) {
            $allowed = array_fill_keys(array_values(array_filter(array_map('strval', $onlyNames))), true);
            $toolNames = array_values(array_filter(
                $toolNames,
                static fn (string $name): bool => isset($allowed[$name])
            ));
        }
        $result = [];

        foreach ($toolNames as $name) {
            $meta = self::resolveToolMeta($name, $module);
            if ($meta !== null) {
                $result[] = $meta;
            }
        }

        return $result;
    }

    /**
     * 工具 inputSchema 是否动态构建（禁止缓存）.
     */
    public static function isDynamicSchemaTool(string $toolName): bool
    {
        self::ensureDiscovered();

        $dir = self::$toolDirs[$toolName] ?? '';
        if ($dir === '') {
            return false;
        }

        foreach (self::getConfiguredModules() as $config) {
            if (empty($config['dynamic_schema'])) {
                continue;
            }
            if (in_array($dir, (array) ($config['tool_dirs'] ?? []), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取工具所属 MCP 业务模块.
     */
    public static function getToolModule(string $toolName): ?string
    {
        self::ensureDiscovered();

        $dir = self::$toolDirs[$toolName] ?? null;
        if (! $dir) {
            return null;
        }

        foreach (self::getConfiguredModules() as $module => $config) {
            if (in_array($dir, (array) ($config['tool_dirs'] ?? []), true)) {
                return $module;
            }
        }

        if (in_array($toolName, self::getCommonToolNames(), true)) {
            return 'common';
        }

        if (in_array($toolName, self::getContextToolNames(), true)) {
            return 'context';
        }

        return null;
    }

    /**
     * 获取工具的权限策略。
     *
     * 工具可通过模块配置中的 permission_strategies 按名称覆盖默认策略；
     * common/context 工具默认由自身完成权限过滤，审批工具默认走业务 guard。
     */
    public static function getToolPermissionStrategy(string $toolName): string
    {
        $module = self::getToolModule($toolName);
        if (in_array($module, ['common', 'context'], true)) {
            return 'context_only';
        }

        $moduleConfig = self::getModuleConfig($module);
        $overrides = (array) ($moduleConfig['permission_strategies'] ?? []);
        if (isset($overrides[$toolName]) && is_string($overrides[$toolName])) {
            return (string) $overrides[$toolName];
        }

        $strategy = trim((string) ($moduleConfig['permission_strategy'] ?? ''));
        if ($strategy !== '') {
            return $strategy;
        }

        if (! empty($moduleConfig['permission_module'])) {
            return 'module_data_scope';
        }

        return $module === null ? 'legacy_fallback' : 'business_guard';
    }

    /**
     * 检查已注册工具的权限策略配置。
     *
     * @return array<int, string>
     */
    public static function validatePermissionStrategies(): array
    {
        $errors = [];
        foreach (self::getToolNames(null, true) as $toolName) {
            $strategy = self::getToolPermissionStrategy($toolName);
            if (! in_array($strategy, ['module_data_scope', 'business_guard', 'context_only', 'legacy_fallback'], true)) {
                $errors[] = $toolName . ': unknown permission strategy ' . $strategy;
                continue;
            }

            if ($strategy === 'module_data_scope') {
                $module = self::getToolModule($toolName);
                $moduleConfig = self::getModuleConfig($module);
                if (empty($moduleConfig['permission_module'])) {
                    $errors[] = $toolName . ': module_data_scope requires permission_module';
                }
            }
        }

        return $errors;
    }

    /**
     * 判断工具是否允许在指定模块端点中暴露或调用.
     */
    public static function isToolAvailableInModule(string $toolName, ?string $module, bool $includeCommon = true): bool
    {
        self::ensureDiscovered();

        if ($module === null || $module === '') {
            return isset(self::$toolClassMap[$toolName]);
        }

        return in_array($toolName, self::getModuleToolNames($module, $includeCommon), true);
    }

    /**
     * 获取配置的 MCP 模块.
     */
    public static function getConfiguredModules(): array
    {
        $modules = config('mcp.tool_modules', []);

        return is_array($modules) ? $modules : [];
    }

    /**
     * 获取模块配置.
     */
    public static function getModuleConfig(?string $module): array
    {
        if ($module === null || $module === '') {
            return [];
        }

        $modules = self::getConfiguredModules();

        return $modules[$module] ?? [];
    }

    /**
     * 获取业务能力目录，供 MCP 信息接口、管理端和 AI 语义适配使用.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getBusinessCapabilities(): array
    {
        $capabilities = config('mcp.business_capabilities', []);

        return is_array($capabilities) ? $capabilities : [];
    }

    /**
     * 获取工具对应的业务语义.
     *
     * @return array{capability: string, name: string, aliases: array<int, string>, metric: string, tools: array<int, string>}
     */
    public static function getToolSemantics(string $toolName): array
    {
        $matches = [];
        foreach (self::getBusinessCapabilities() as $capability => $definition) {
            if (! is_array($definition) || ! in_array($toolName, (array) ($definition['tools'] ?? []), true)) {
                continue;
            }

            $matches[] = [
                'capability' => (string) $capability,
                'name'       => (string) ($definition['name'] ?? $capability),
                'aliases'    => array_values(array_filter(array_map('strval', (array) ($definition['aliases'] ?? [])))),
                'metric'     => (string) ($definition['metric'] ?? ''),
                'tools'      => array_values(array_filter(array_map('strval', (array) ($definition['tools'] ?? [])))),
            ];
        }

        if ($matches === []) {
            return [];
        }

        return [
            'capability' => implode(',', array_column($matches, 'capability')),
            'name'       => implode(' / ', array_column($matches, 'name')),
            'aliases'    => array_values(array_unique(array_merge(...array_column($matches, 'aliases')))),
            'metric'     => implode('；', array_values(array_filter(array_column($matches, 'metric')))),
            'tools'      => array_values(array_unique(array_merge(...array_column($matches, 'tools')))),
        ];
    }

    /**
     * 获取模块允许的工具名称.
     *
     * @return array<int, string>
     */
    public static function getModuleToolNames(string $module, bool $includeCommon = true): array
    {
        self::ensureDiscovered();

        $config = self::getModuleConfig($module);
        if ($config === []) {
            return [];
        }

        $toolDirs = (array) ($config['tool_dirs'] ?? []);
        $names = [];
        foreach (self::$toolDirs as $toolName => $dir) {
            if (in_array($dir, $toolDirs, true)) {
                $names[] = $toolName;
            }
        }

        if ($includeCommon) {
            $names = array_merge($names, self::getAttachedContextToolNames());
        }

        return array_values(array_unique(array_filter($names, fn ($name) => isset(self::$toolClassMap[$name]))));
    }

    /**
     * 获取组织/人员上下文工具名称.
     *
     * @return array<int, string>
     */
    public static function getContextToolNames(): array
    {
        if ((int) sys_config('mcp_context_tools_enabled', 1) !== 1) {
            return [];
        }

        $tools = config('mcp.context_tools', []);

        return is_array($tools) ? array_values(array_filter(array_map('strval', $tools))) : [];
    }

    /**
     * 获取模块端点自动附加的上下文工具（身份 + 组织/人员）.
     *
     * @return array<int, string>
     */
    public static function getAttachedContextToolNames(): array
    {
        return array_values(array_unique(array_merge(
            self::getCommonToolNames(),
            self::getContextToolNames()
        )));
    }

    /**
     * 获取模块路由 pattern（用于 routes/mcp.php）.
     */
    public static function getModuleRoutePattern(): string
    {
        $keys = array_keys(self::getConfiguredModules());

        return $keys === [] ? 'never-match' : implode('|', $keys);
    }

    /**
     * 获取通用上下文工具名称.
     *
     * @return array<int, string>
     */
    public static function getCommonToolNames(): array
    {
        $tools = config('mcp.common_tools', []);

        return is_array($tools) ? array_values(array_filter(array_map('strval', $tools))) : [];
    }

    /**
     * 清除缓存的工具类映射与元数据版本.
     */
    public static function clearCache(): void
    {
        self::$toolClassMap = null;
        self::$toolDirs = [];
        Cache::increment(self::META_VERSION_KEY);
    }

    /**
     * 扫描并缓存工具类映射（不含实例，可安全跨 Swoole 请求复用）.
     */
    private static function ensureDiscovered(): void
    {
        if (self::$toolClassMap !== null) {
            return;
        }

        $toolClassMap = [];
        $toolDirs = [];
        $basePath = app_path('Mcp/Tools');
        $modules = self::scanModules($basePath);

        foreach ($modules as $module => $toolClasses) {
            foreach ($toolClasses as $toolClass) {
                if (! is_subclass_of($toolClass, ToolInterface::class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($toolClass);
                if ($reflection->isAbstract()) {
                    continue;
                }

                $toolName = self::toolNameFromClass($toolClass);
                $toolClassMap[$toolName] = $toolClass;
                $toolDirs[$toolName] = $module;
            }
        }

        self::$toolClassMap = $toolClassMap;
        self::$toolDirs = $toolDirs;
    }

    /**
     * @return null|array{name: string, description: string, inputSchema: array}
     */
    private static function resolveToolMeta(string $name, ?string $module): ?array
    {
        $ttl = (int) config('mcp.cache.tools_meta_ttl', 3600);
        if ($ttl <= 0 || self::isDynamicSchemaTool($name)) {
            return self::buildToolMetaEntry($name, $module);
        }

        $cacheKey = 'mcp:tool_meta:' . $name . ':' . self::metaCacheVersionSuffix();

        /** @var null|array{name: string, description: string, inputSchema: array} $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $meta = self::buildToolMetaEntry($name, $module);
        if ($meta !== null) {
            Cache::put($cacheKey, $meta, $ttl);
        }

        return $meta;
    }

    /**
     * @return null|array{name: string, description: string, inputSchema: array}
     */
    private static function buildToolMetaEntry(string $name, ?string $module): ?array
    {
        $tool = self::getTool($name, $module);
        if (! $tool || ! self::isToolEnabled($tool, $module)) {
            return null;
        }

        $description = $tool->getDescription();
        $semantics   = self::getToolSemantics($name);
        if ($semantics !== []) {
            $description .= '。业务能力：' . $semantics['name'];
            if ($semantics['aliases'] !== []) {
                $description .= '；常见说法：' . implode('、', $semantics['aliases']);
            }
            if ($semantics['metric'] !== '') {
                $description .= '；口径/调用提示：' . $semantics['metric'];
            }
        }

        $action = method_exists($tool, 'getAction') ? (string) $tool->getAction() : 'read';

        return [
            'name' => $tool->getName(),
            'description' => $description,
            'inputSchema' => $tool->getInputSchema(),
            'annotations' => [
                'readOnlyHint'    => $action === 'read',
                'destructiveHint' => $action === 'write',
            ],
        ];
    }

    private static function metaCacheVersionSuffix(): string
    {
        return implode(':', [
            (int) Cache::get(self::META_VERSION_KEY, 0),
            (int) sys_config('mcp_enabled', 1),
            (int) sys_config('mcp_write_enabled', 1),
            (int) sys_config('mcp_read_enabled', 1),
            (int) sys_config('mcp_context_tools_enabled', 1),
            md5((string) json_encode(self::getBusinessCapabilities(), JSON_UNESCAPED_UNICODE)),
        ]);
    }

    private static function isToolEnabled(ToolInterface $tool, ?string $module): bool
    {
        if ((int) sys_config('mcp_enabled', 1) !== 1) {
            return false;
        }

        $action = method_exists($tool, 'getAction') ? $tool->getAction() : 'read';
        if ($action === 'write' && (int) sys_config('mcp_write_enabled', 0) !== 1) {
            return false;
        }

        if ($action === 'read' && (int) sys_config('mcp_read_enabled', 1) !== 1) {
            return in_array($tool->getName(), self::getAttachedContextToolNames(), true);
        }

        return true;
    }

    /**
     * 从工具类名推导工具名称（与 BaseTool::getName 规则一致）.
     *
     * @param class-string<ToolInterface> $className
     */
    private static function toolNameFromClass(string $className): string
    {
        $shortName = $className;
        if (str_contains($className, '\\')) {
            $shortName = substr($className, strrpos($className, '\\') + 1);
        }
        $shortName = preg_replace('/Tool$/', '', $shortName) ?? $shortName;

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName) ?? $shortName);
    }
}
