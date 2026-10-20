<?php

declare(strict_types=1);

namespace App\Mcp\Resolvers;

use App\Http\Model\Admin\Admin;
use App\Http\Service\Frame\FrameAssistService;

/**
 * 将自然语言中的人员指代解析为系统用户 ID。
 */
class McpTargetUserResolver
{
    public function resolve(mixed $value, int $currentUserId): array
    {
        $items = $this->normalizeItems($value);
        if ($items === []) {
            return $this->success([]);
        }

        $users = [];
        foreach ($items as $item) {
            if ($this->isCurrentUserAlias($item)) {
                $user = Admin::query()->where('id', $currentUserId)->where('status', 1)->first();
                if ($user) {
                    $users[$user->id] = $this->formatUser($user);
                }
                continue;
            }

            $matched = $this->matchOne($item);
            if (! ($matched['success'] ?? false)) {
                return $matched;
            }

            foreach ($matched['users'] ?? [] as $user) {
                $users[(int) $user['id']] = $user;
            }
        }

        return $this->success(array_values($users));
    }

    private function matchOne(string $value): array
    {
        if ($value === '') {
            return $this->success([]);
        }

        $query = Admin::query()->where('status', 1);
        if (ctype_digit($value)) {
            $query->where('id', (int) $value);
        } elseif ($this->looksLikePhone($value)) {
            $query->where('phone', $value);
        } else {
            $query->where('account', $value);
        }

        $exact = $query->select(['id', 'uid', 'name', 'avatar', 'phone', 'account'])->limit(5)->get();
        if ($exact->isNotEmpty()) {
            return $this->success($exact->map(fn (Admin $user) => $this->formatUser($user))->all());
        }

        $nameExact = Admin::query()
            ->where('status', 1)
            ->where('name', $value)
            ->select(['id', 'uid', 'name', 'avatar', 'phone', 'account'])
            ->limit(5)
            ->get();
        if ($nameExact->count() === 1) {
            return $this->success([$this->formatUser($nameExact->first())]);
        }
        if ($nameExact->count() > 1) {
            return $this->ambiguous($value, $nameExact->map(fn (Admin $user) => $this->formatUser($user))->all());
        }

        $nameLike = Admin::query()
            ->where('status', 1)
            ->where('name', 'like', '%' . $value . '%')
            ->select(['id', 'uid', 'name', 'avatar', 'phone', 'account'])
            ->limit(6)
            ->get();
        if ($nameLike->count() === 1) {
            return $this->success([$this->formatUser($nameLike->first())]);
        }
        if ($nameLike->count() > 1) {
            return $this->ambiguous($value, $nameLike->map(fn (Admin $user) => $this->formatUser($user))->all());
        }

        return [
            'success' => false,
            'error' => [
                'error' => true,
                'type' => 'person_not_found',
                'message' => "未找到员工：{$value}",
            ],
        ];
    }

    private function normalizeItems(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $items = array_merge($items, $this->normalizeItems($item));
            }
            return $items;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeItems($decoded);
            }
        }

        return array_values(array_filter(array_map(
            static fn ($item) => trim($item),
            preg_split('/[,，、;；]+/', $value) ?: []
        ), static fn ($item) => $item !== ''));
    }

    private function isCurrentUserAlias(string $value): bool
    {
        return in_array($value, ['我', '本人', '自己', '当前用户', '当前登录人'], true);
    }

    private function looksLikePhone(string $value): bool
    {
        return (bool) preg_match('/^1[3-9]\d{9}$/', $value);
    }

    private function formatUser(Admin $user): array
    {
        return [
            'id' => (int) $user->id,
            'uid' => (string) $user->uid,
            'name' => (string) $user->name,
            'avatar' => (string) ($user->avatar ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'account' => (string) ($user->account ?? ''),
            'frames' => $this->getFrames((string) $user->uid),
        ];
    }

    private function getFrames(string $uuid): array
    {
        if ($uuid === '') {
            return [];
        }

        try {
            $frames = app(FrameAssistService::class)->getUserFrames($uuid);
            $frames = $frames ? (is_array($frames) ? $frames : $frames->toArray()) : [];
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_map(function (array $item) {
            $frame = $item['frame'] ?? [];

            return [
                'id' => (int) ($frame['id'] ?? $item['frame_id'] ?? 0),
                'name' => (string) ($frame['name'] ?? ''),
                'is_main' => (bool) ($item['is_mastart'] ?? false),
            ];
        }, $frames));
    }

    private function success(array $users): array
    {
        return [
            'success' => true,
            'user_ids' => array_values(array_unique(array_map(fn (array $user) => (int) $user['id'], $users))),
            'users' => array_values($users),
        ];
    }

    private function ambiguous(string $value, array $users): array
    {
        return [
            'success' => false,
            'error' => [
                'error' => true,
                'type' => 'ambiguous_person',
                'message' => "找到多个匹配员工：{$value}，请补充部门、手机号或账号",
                'candidates' => array_values($users),
            ],
        ];
    }
}
