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

namespace App\Http\Service\Ai\Agent\Meta;

use App\Http\Service\Ai\Agent\Decision\McpToolAllowlist;
use App\Http\Service\Ai\AiMcpService;
use App\Http\Service\Ai\AiToolCallService;
use App\Mcp\ToolRegistry;

/**
 * load_tools 元工具：按模块补挂核心短名单（无 keyword 不整模块倾倒）.
 */
final class LoadToolsHandler
{
    /**
     * @param array<string, mixed> $toolCall
     * @param array<string, mixed> $args
     * @param array{
     *     guestMode: bool,
     *     loadableModules: array<int, string>,
     *     loadableToolNames: array<int, string>,
     *     addedToolNames: array<string, true>,
     *     pendingLoadedTools: array<int, array<string, mixed>>,
     *     loadedToolCount: int,
     *     preparedConfiguredTools: null|array<int, string>
     * } $state
     *
     * @return array{envelope: array<string, mixed>, state: array<string, mixed>}
     */
    public static function execute(array $toolCall, array $args, array $state): array
    {
        $module  = trim((string) ($args['module'] ?? ''));
        $keyword = trim((string) ($args['keyword'] ?? ''));
        $guestMode = (bool) ($state['guestMode'] ?? false);
        $loadableModules = (array) ($state['loadableModules'] ?? []);
        if ($guestMode || ! in_array($module, $loadableModules, true)) {
            $result = [
                'error'      => true,
                'error_code' => 'TOOL_UNAVAILABLE',
                'message'    => $loadableModules === []
                    ? '当前会话不支持加载工具'
                    : '模块不可用，可加载模块：' . implode('、', $loadableModules),
            ];

            return [
                'envelope' => self::envelope($toolCall, $result),
                'state'    => $state,
            ];
        }

        $addedToolNames = (array) ($state['addedToolNames'] ?? []);
        $loadableToolNames = (array) ($state['loadableToolNames'] ?? []);
        $pendingLoadedTools = (array) ($state['pendingLoadedTools'] ?? []);
        $loadedToolCount = (int) ($state['loadedToolCount'] ?? 0);
        $preparedConfiguredTools = (array) ($state['preparedConfiguredTools'] ?? []);

        $candidates = [];
        foreach (app(AiMcpService::class)->localBusinessToolsMetaForModules([$module]) as $tool) {
            $name = (string) ($tool['name'] ?? '');
            if ($name === '' || isset($addedToolNames[$name])) {
                continue;
            }
            if (! in_array($name, $loadableToolNames, true)) {
                continue;
            }
            $candidates[$name] = $tool;
        }
        foreach ($pendingLoadedTools as $tool) {
            unset($candidates[(string) ($tool['name'] ?? '')]);
        }

        if ($keyword !== '') {
            $matched = array_filter(
                $candidates,
                static fn (array $tool): bool => mb_stripos((string) ($tool['name'] ?? ''), $keyword) !== false
                    || mb_stripos((string) ($tool['description'] ?? ''), $keyword) !== false
            );
            if ($matched === []) {
                return [
                    'envelope' => self::envelope($toolCall, [
                        'module'    => $module,
                        'loaded'    => [],
                        'available' => array_keys($candidates),
                        'message'   => $candidates === []
                            ? '该模块没有可补充加载的工具（可能已全部加载）'
                            : '关键词未匹配到工具，未加载；如需要请用 available 中的工具名作为 keyword 重新加载',
                    ]),
                    'state' => $state,
                ];
            }
            $candidates = $matched;
        }

        $ordered = [];
        foreach (McpToolAllowlist::moduleCoreFallback($module, true, array_keys($candidates)) as $name) {
            if (isset($candidates[$name])) {
                $ordered[$name] = $candidates[$name];
            }
        }
        if ($keyword !== '') {
            $ordered += $candidates;
        }

        $max      = max(1, (int) config('ai.plugins.tools.load_tools_max', 12));
        $totalMax = (int) config('ai.plugins.tools.load_tools_total_max', 24);
        if ($totalMax > 0) {
            $max = min($max, $totalMax - $loadedToolCount);
        }
        if ($max <= 0) {
            return [
                'envelope' => self::envelope($toolCall, [
                    'error'      => true,
                    'error_code' => 'TOOL_LIMIT_REACHED',
                    'message'    => '本轮已加载的工具数量达到上限，请用现有工具完成，或如实告知用户当前无法处理',
                ]),
                'state' => $state,
            ];
        }

        $loaded = array_slice($ordered, 0, $max, true);
        $loadedToolCount += count($loaded);
        $rest = array_slice(array_keys($ordered), $max);

        $preparedConfiguredTools = array_values(array_unique(array_merge(
            $preparedConfiguredTools,
            array_keys($loaded)
        )));
        foreach ($loaded as $tool) {
            $pendingLoadedTools[] = $tool;
        }

        $result = [
            'module'  => $module,
            'loaded'  => array_values(array_map(static fn (array $tool): array => [
                'name'        => (string) $tool['name'],
                'description' => mb_substr((string) ($tool['description'] ?? ''), 0, 80),
            ], $loaded)),
            'message' => $loaded === []
                ? '该模块没有可补充加载的工具（可能已全部加载）'
                : '已加载，下一步可直接调用上述工具',
        ];
        if ($rest !== []) {
            $result['not_loaded'] = $rest;
            $result['message']   = ($result['message'] ?? '')
                . '；仍有未加载工具，请用 keyword 继续 load_tools（可直接用 not_loaded 中的工具名）';
        }

        $state['pendingLoadedTools'] = $pendingLoadedTools;
        $state['loadedToolCount'] = $loadedToolCount;
        $state['preparedConfiguredTools'] = $preparedConfiguredTools;

        return [
            'envelope' => self::envelope($toolCall, $result),
            'state'    => $state,
        ];
    }

    /**
     * @param array<int, string> $modules
     *
     * @return array<string, mixed>
     */
    public static function definition(array $modules): array
    {
        $lines = [];
        foreach ($modules as $module) {
            $config = ToolRegistry::getModuleConfig($module);
            $desc   = (string) ($config['description'] ?? '');
            $desc   = preg_split('/[。；]/u', $desc)[0] ?? '';
            $lines[] = $module . '（' . (string) ($config['name'] ?? $module) . '）：' . mb_substr($desc, 0, 60);
        }

        return [
            'name'        => AiToolCallService::LOAD_TOOLS_NAME,
            'description' => '按业务模块补充加载工具。当前工具列表里没有完成用户请求所需的工具、且该能力属于下列模块时，'
                . '先调用本工具加载该模块，工具结果返回后即可直接调用新加载的工具。'
                . '只能加载下列模块；不在列表里的能力说明当前应用未开通，直接如实告知，禁止到其他模块里用关键词反复查找。'
                . '无 keyword 时仅加载该模块核心短名单；需要更多工具时用 keyword 按名续挂。'
                . '可加载模块：' . implode('；', $lines) . '。',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'module'  => [
                        'type'        => 'string',
                        'enum'        => array_values($modules),
                        'description' => '要加载的业务模块',
                    ],
                    'keyword' => [
                        'type'        => 'string',
                        'description' => '可选：按工具名或说明过滤；未命中时不加载，只返回该模块可用工具名',
                    ],
                ],
                'required'   => ['module'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $toolCall
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private static function envelope(array $toolCall, array $result): array
    {
        return [
            'id'      => (string) ($toolCall['id'] ?? ''),
            'name'    => AiToolCallService::LOAD_TOOLS_NAME,
            'result'  => $result,
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE) ?: '',
        ];
    }
}
