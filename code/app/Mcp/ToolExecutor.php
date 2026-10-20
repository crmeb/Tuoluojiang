<?php
declare(strict_types=1);

namespace App\Mcp;

use App\Constants\ModuleEnum;
use App\Http\Context\DataPermissionContext;
use App\Http\Service\System\ModulePermissionService;
use App\Mcp\Support\McpLogSanitizer;
use App\Mcp\Support\McpMetrics;
use App\Mcp\Support\McpWriteResult;
use App\Mcp\Support\McpArgumentsValidator;
use App\Mcp\Tools\Contracts\ToolInterface;
use Illuminate\Support\Facades\Log;

class ToolExecutor
{
    /**
     * 执行工具
     */
    public function execute(string $toolName, array $arguments): array
    {
        $tool = ToolRegistry::getTool($toolName);

        if (! $tool) {
            return $this->normalizeResult($toolName, [], [
                'error'   => true,
                'message' => "Tool not found: {$toolName}",
            ]);
        }

        $validationBlock = $this->validateArgumentsOrNull($toolName, $tool, $arguments);
        if ($validationBlock !== null) {
            $result = $this->normalizeResult($toolName, $arguments, $validationBlock);
            McpMetrics::recordToolExecution($toolName, 0, true, ToolRegistry::getToolModule($toolName));
            DataPermissionContext::clear();

            return $result;
        }

        try {
            $startedAt = microtime(true);
            $this->hydrateDataPermissionContext($toolName);
            $result = $this->normalizeResult($toolName, $arguments, $tool->execute($arguments));
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            $isError = (bool) ($result['error'] ?? false);

            McpMetrics::recordToolExecution(
                $toolName,
                $durationMs,
                $isError,
                ToolRegistry::getToolModule($toolName)
            );

            Log::channel((string) config('mcp.logging.channel', 'mcp'))->info('MCP tool executed', [
                'tool'     => $toolName,
                'action'   => method_exists($tool, 'getAction') ? $tool->getAction() : 'read',
                'user_id'  => (int) request()->input('mcp_user_db_id', 0),
                'duration' => $durationMs,
                'is_error' => $isError,
                'arguments'=> McpLogSanitizer::summarizeForLog($arguments),
                'result'   => McpLogSanitizer::summarizeForLog($result),
            ]);

            return $result;
        } catch (\Throwable $e) {
            McpMetrics::recordToolExecution(
                $toolName,
                0,
                true,
                ToolRegistry::getToolModule($toolName)
            );

            Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('MCP tool execution error', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->normalizeResult($toolName, [], [
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } finally {
            DataPermissionContext::clear();
        }
    }

    /**
     * 按配置对工具入参做 JSON Schema 校验；enforce 失败时返回错误载荷，warn 仅打日志.
     *
     * @param array<string, mixed> $arguments
     *
     * @return null|array<string, mixed>
     */
    protected function validateArgumentsOrNull(string $toolName, ToolInterface $tool, array $arguments): ?array
    {
        $mode = strtolower((string) config('mcp.arguments.validation_mode', 'enforce'));
        if ($mode === 'off' || $mode === '0' || $mode === 'false') {
            return null;
        }

        $schema = [];
        try {
            $schema = $tool->getInputSchema();
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning('MCP tool schema unavailable', [
                'tool'  => $toolName,
                'error' => $e->getMessage(),
            ]);
            if ($mode === 'enforce') {
                return [
                    'error'      => true,
                    'error_code' => McpArgumentsValidator::ERROR_CODE,
                    'message'    => '工具入参 Schema 不可用: ' . $e->getMessage(),
                    'tool'       => $toolName,
                ];
            }

            return null;
        }

        if (! is_array($schema) || $schema === []) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning('MCP tool schema empty', [
                'tool' => $toolName,
            ]);
            // 空 schema 不阻断执行，由启动/单测自检兜住覆盖率.
            return null;
        }

        $validator = new McpArgumentsValidator();
        $result    = $validator->validate($schema, $arguments);
        if ($result['valid']) {
            return null;
        }

        Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning('MCP tool arguments invalid', [
            'tool'           => $toolName,
            'mode'           => $mode,
            'missing_fields' => $result['missing_fields'],
            'invalid_fields' => $result['invalid_fields'],
            'arguments'      => McpLogSanitizer::summarizeForLog($arguments),
        ]);

        if ($mode !== 'enforce') {
            return null;
        }

        return $validator->toErrorPayload($result, $toolName);
    }

    /**
     * MCP 不经过模块中间件，执行前按工具所属模块初始化数据权限上下文。
     */
    protected function hydrateDataPermissionContext(string $toolName): void
    {
        DataPermissionContext::clear();

        $module = $this->resolvePermissionModule($toolName);
        if (! $module) {
            // context/common 工具自行过滤权限，不依赖 ModuleEnum 数据权限上下文，避免误报
            $toolModule = ToolRegistry::getToolModule($toolName);
            if ($this->isKnownPermissionAwareTool($toolName) && ! in_array($toolModule, ['context', 'common'], true)) {
                Log::channel('mcp')->warning('MCP tool permission module unresolved', [
                    'tool' => $toolName,
                ]);
            }
            return;
        }

        $userId = (int) (request()->input('mcp_user_db_id', 0) ?: auth('admin')->id());
        if (! $userId) {
            return;
        }

        app(ModulePermissionService::class)->hydrateDataPermissionContext($userId, $module);
    }

    protected function resolvePermissionModule(string $toolName): ?string
    {
        $permissionStrategy = ToolRegistry::getToolPermissionStrategy($toolName);
        if ($permissionStrategy !== 'module_data_scope') {
            if ($permissionStrategy !== 'legacy_fallback') {
                return null;
            }
        }

        $toolModule = ToolRegistry::getToolModule($toolName);
        $moduleConfig = ToolRegistry::getModuleConfig($toolModule);
        if (! empty($moduleConfig['permission_module'])) {
            return (string) $moduleConfig['permission_module'];
        }

        foreach ([
            'attendance_' => ModuleEnum::ATTENDANCE,
            'assess_'     => ModuleEnum::ASSESS,
            'schedule_'   => ModuleEnum::SCHEDULE,
            'report_'     => ModuleEnum::REPORT,
            'program_'    => ModuleEnum::PROGRAM,
        ] as $prefix => $module) {
            if (str_starts_with($toolName, $prefix)) {
                return $module;
            }
        }

        foreach ([
            'bill_',
            'contact_',
            'contract_',
            'finance_',
            'customer_detail',
            'invoice_',
            'lead_',
            'customer_list',
            'opportunity_',
            'order_',
            'record_',
            'search_customers',
        ] as $prefix) {
            if (str_starts_with($toolName, $prefix)) {
                return ModuleEnum::CUSTOMER;
            }
        }

        return null;
    }

    protected function isKnownPermissionAwareTool(string $toolName): bool
    {
        foreach ([
            'personnel_',
            'my_data_scope',
            'check_data_permission',
            'my_subordinates',
            'org_',
        ] as $prefix) {
            if (str_starts_with($toolName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 执行工具并返回标准化结果
     */
    public function executeWithResult(string $toolName, array $arguments): array
    {
        $result = $this->execute($toolName, $arguments);

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ],
            ],
            'isError' => $result['error'] ?? false,
        ];
    }

    /**
     * 为所有工具结果补充稳定的业务元信息，不改变既有业务字段.
     *
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    protected function normalizeResult(string $toolName, array $arguments, array $result): array
    {
        $isError = (bool) ($result['error'] ?? false);
        $isWrite = $this->toolAction($toolName) === 'write';
        // 写工具走统一落库证据契约；读工具仅做软校验，避免误伤列表空结果等.
        $succeeded = $isWrite
            ? McpWriteResult::succeeded($result)
            : (! $isError
                && ($result['success'] ?? true) !== false
                && (! isset($result['id']) || (int) $result['id'] > 0)
                && (! isset($result['stats']['created_count']) || (int) $result['stats']['created_count'] > 0));
        if ($isWrite && ! $succeeded) {
            $result['error']   = true;
            $result['message'] = (string) ($result['message'] ?? '写工具未返回有效写入结果');
            $isError           = true;
        }
        $semantics = ToolRegistry::getToolSemantics($toolName);
        $meta      = is_array($result['_meta'] ?? null) ? $result['_meta'] : [];
        $period    = array_filter([
            'start' => $arguments['start_date'] ?? null,
            'end'   => $arguments['end_date'] ?? null,
        ], static fn ($value): bool => $value !== null && $value !== '');
        if ($period === [] && ! empty($arguments['time'])) {
            $period = ['value' => (string) $arguments['time']];
        }
        $scope = array_filter([
            'user_ids'    => $arguments['user_id'] ?? $arguments['uid'] ?? null,
            'target_user' => $arguments['target_user'] ?? null,
        ], static fn ($value): bool => $value !== null && $value !== '' && $value !== []);

        $meta = array_merge([
            'contract_version' => '1.0',
            'tool_name'        => $toolName,
            'module'           => ToolRegistry::getToolModule($toolName),
            'action'           => $this->toolAction($toolName),
            'capability'       => $semantics['capability'] ?? '',
            'capability_name'  => $semantics['name'] ?? '',
            'metric'           => $semantics['metric'] ?? '',
            'period'           => $period,
            'scope'            => $scope,
            'next_actions'     => $this->nextActions((string) ($semantics['capability'] ?? '')),
            'success'          => $succeeded,
        ], $meta);
        $meta['success'] = $succeeded && ($meta['success'] ?? true) !== false;

        if ($isError) {
            $result['error_code'] = (string) ($result['error_code'] ?? $this->errorCode($result));
        }
        $result['_meta'] = $meta;

        return $result;
    }

    private function toolAction(string $toolName): string
    {
        $tool = ToolRegistry::getTool($toolName);

        return $tool && method_exists($tool, 'getAction') ? (string) $tool->getAction() : 'read';
    }

    /** @param array<string, mixed> $result */
    private function errorCode(array $result): string
    {
        $message = (string) ($result['message'] ?? '');
        if (! empty($result['field']) || str_contains($message, '参数')) {
            return 'MISSING_PARAMETER';
        }
        if (preg_match('/权限|无权|禁止访问|未授权/u', $message)) {
            return 'NO_PERMISSION';
        }
        if (preg_match('/不存在|没有数据|未找到|无记录|没有需要/u', $message)) {
            return 'NO_DATA';
        }
        if (preg_match('/超时|timeout|timed out/u', $message)) {
            return 'UPSTREAM_TIMEOUT';
        }
        if (str_starts_with($message, 'Tool not found')) {
            return 'TOOL_UNAVAILABLE';
        }

        return 'TOOL_EXECUTION_FAILED';
    }

    /** @return array<int, string> */
    private function nextActions(string $capability): array
    {
        if (str_contains($capability, 'personnel_performance_ratio')) {
            return ['按月份查看趋势', '按部门汇总', '查看人员明细'];
        }
        if (str_contains($capability, 'sales_performance')
            || str_contains($capability, 'received_payment')
            || str_contains($capability, 'order_performance')
        ) {
            return ['按月份查看趋势', '按人员查看', '查看明细'];
        }
        if (str_contains($capability, 'attendance_summary')) {
            return ['查看异常明细', '按人员筛选'];
        }
        if (str_contains($capability, 'approval_status')) {
            return ['查看审批流程', '查看审批详情'];
        }

        return [];
    }
}
