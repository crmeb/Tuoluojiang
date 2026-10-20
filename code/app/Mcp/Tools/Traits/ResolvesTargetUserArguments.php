<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Traits;

use App\Mcp\Guards\McpDataPermissionGuard;
use App\Mcp\Resolvers\McpTargetUserResolver;

trait ResolvesTargetUserArguments
{
    protected function targetUserSchemaProperties(): array
    {
        return [
            'target_user' => [
                'type' => 'string',
                'description' => '查询对象，支持员工姓名、手机号、账号、用户ID、“我/本人”。例如“小王的客户有哪些”传“小王”',
            ],
            'target_user_role' => [
                'type' => 'string',
                'description' => '查询对象与业务数据的关系：owner=负责人，creator=创建人，previous_owner=前负责人',
                'enum' => ['owner', 'creator', 'previous_owner'],
                'default' => 'owner',
            ],
        ];
    }

    protected function applyTargetUserToViewWhere(array $arguments, array &$where, string $permissionModule): ?array
    {
        if (! $this->hasTargetUserArgument($arguments)) {
            return null;
        }

        $resolved = app(McpTargetUserResolver::class)->resolve($arguments['target_user'] ?? null, $this->getUserDbId());
        if (! ($resolved['success'] ?? false)) {
            return $resolved['error'] ?? [
                'error' => true,
                'type' => 'person_resolve_failed',
                'message' => '人员解析失败',
            ];
        }

        $targetUserIds = $resolved['user_ids'] ?? [];
        if (! $targetUserIds) {
            return null;
        }

        $guardResult = app(McpDataPermissionGuard::class)->filterAuthorizedTargetUsers(
            $this->getUserDbId(),
            $permissionModule,
            $targetUserIds
        );

        $authorizedIds = $guardResult['authorized_ids'] ?? [];
        if (! $authorizedIds) {
            return app(McpDataPermissionGuard::class)->permissionDeniedResult($permissionModule, $guardResult['denied_ids'] ?? $targetUserIds);
        }

        $where[$this->getTargetUserViewField((string) ($arguments['target_user_role'] ?? 'owner'))] = $authorizedIds;

        return null;
    }

    protected function resolveAuthorizedUserIds(array $arguments, string $permissionModule, string $userIdArgument = 'user_id'): array
    {
        if ($this->hasTargetUserArgument($arguments)) {
            $resolved = app(McpTargetUserResolver::class)->resolve($arguments['target_user'] ?? null, $this->getUserDbId());
            if (! ($resolved['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => $resolved['error'] ?? [
                        'error' => true,
                        'type' => 'person_resolve_failed',
                        'message' => '人员解析失败',
                    ],
                ];
            }

            return $this->authorizeUserIds($permissionModule, $resolved['user_ids'] ?? [], true);
        }

        if (! array_key_exists($userIdArgument, $arguments) || $arguments[$userIdArgument] === '' || $arguments[$userIdArgument] === null) {
            return [
                'success' => true,
                'has_filter' => false,
                'user_ids' => [],
            ];
        }

        return $this->authorizeUserIds($permissionModule, $this->normalizeTargetUserIds($arguments[$userIdArgument]), true);
    }

    protected function authorizeUserIds(string $permissionModule, array $userIds, bool $hasFilter): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return [
                'success' => true,
                'has_filter' => $hasFilter,
                'user_ids' => [],
            ];
        }

        $guardResult = app(McpDataPermissionGuard::class)->filterAuthorizedTargetUsers(
            $this->getUserDbId(),
            $permissionModule,
            $userIds
        );

        $authorizedIds = $guardResult['authorized_ids'] ?? [];
        if (! $authorizedIds) {
            return [
                'success' => false,
                'error' => app(McpDataPermissionGuard::class)->permissionDeniedResult($permissionModule, $guardResult['denied_ids'] ?? $userIds),
            ];
        }

        return [
            'success' => true,
            'has_filter' => $hasFilter,
            'user_ids' => $authorizedIds,
            'denied_ids' => $guardResult['denied_ids'] ?? [],
        ];
    }

    protected function hasTargetUserArgument(array $arguments): bool
    {
        return isset($arguments['target_user']) && trim((string) $arguments['target_user']) !== '';
    }

    protected function getTargetUserViewField(string $role): string
    {
        return match ($role) {
            'creator' => 'creator',
            'previous_owner' => 'before_salesman',
            default => 'salesman',
        };
    }

    private function normalizeTargetUserIds(mixed $value): array
    {
        if (is_array($value)) {
            $ids = [];
            foreach ($value as $item) {
                $ids = array_merge($ids, $this->normalizeTargetUserIds($item));
            }
            return $ids;
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item) => (int) trim($item),
            preg_split('/[,，、;；]+/', $value) ?: []
        )));
    }

    /**
     * 解析客户模块业绩统计用的人员范围（对齐 withScopeFrame + uid 交集）.
     *
     * @return array{success:bool,user_ids?:array<int>,error?:array}
     */
    protected function resolveScopedCustomerUserIds(array $arguments): array
    {
        if (! array_key_exists('user_id', $arguments) && array_key_exists('uid', $arguments)) {
            $arguments['user_id'] = $arguments['uid'];
        }

        $authorized = $this->resolveAuthorizedUserIds($arguments, \App\Constants\ModuleEnum::CUSTOMER);
        if (! ($authorized['success'] ?? false)) {
            return [
                'success' => false,
                'error'   => $authorized['error'] ?? [
                    'error'   => true,
                    'message' => '人员数据权限不足',
                ],
            ];
        }

        if (! empty($authorized['has_filter'])) {
            $userIds = array_values(array_map('intval', $authorized['user_ids'] ?? []));
            if ($userIds === [] && ! $this->isAdmin()) {
                $userIds = [-1];
            }

            return ['success' => true, 'user_ids' => $userIds];
        }

        $userIds = $this->isAdmin()
            ? []
            : ($this->getDataUids(\App\Constants\ModuleEnum::CUSTOMER, 1) ?: [-1]);

        return ['success' => true, 'user_ids' => array_values(array_map('intval', $userIds))];
    }

    /**
     * 保留管理端分类路径结构（[[父,子]]），供 getStatisticsCategoryIds 取 leaf.
     */
    protected function resolvePerformanceCategoryIds(array $arguments): array
    {
        $raw = $arguments['category_id'] ?? $arguments['cate_ids'] ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }

        return (array) $raw;
    }
}
