<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Traits;

trait FiltersPersonnelOutput
{
    protected function filterPersonnelListResult(array $result): array
    {
        if (! isset($result['list']) || ! is_array($result['list'])) {
            return $result;
        }

        $result['list'] = array_values(array_map(
            fn (mixed $item) => $this->filterPersonnelItem($item),
            $result['list']
        ));

        return $result;
    }

    protected function filterPersonnelItem(mixed $item): array
    {
        if (! is_array($item)) {
            return [];
        }

        $safe = $this->onlyPersonnelKeys($item, [
            'id',
            'uid',
            'account',
            'name',
            'avatar',
            'phone',
            'birthday',
            'sex',
            'sex_name',
            'job',
            'status',
            'type',
            'work_time',
            'quit_time',
            'created_at',
            'updated_at',
        ]);

        $safe['job']           = $this->filterPersonnelJob($item['job'] ?? []);
        $safe['frames']        = $this->filterPersonnelList($item['frames'] ?? [], $this->safePersonnelFrameFields());
        $safe['manage_frames'] = $this->filterPersonnelList($item['manage_frames'] ?? [], $this->safePersonnelFrameFields());
        $safe['superior']      = $this->onlyPersonnelKeys($item['superior'] ?? ($item['super'] ?? []), $this->safePersonnelSuperiorFields());
        $safe['sex_name']      = $safe['sex_name'] ?? $this->formatSexName($safe['sex'] ?? null);

        return $safe;
    }

    protected function formatSexName(mixed $sex): string
    {
        return match ((int) $sex) {
            1       => '男',
            2       => '女',
            3       => '其他',
            default => '未知',
        };
    }

    protected function filterPersonnelJob(mixed $job): array
    {
        if (is_array($job)) {
            return $this->onlyPersonnelKeys($job, $this->safePersonnelJobFields());
        }

        return ['id' => (int) $job];
    }

    protected function filterPersonnelList(mixed $items, array $fields): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $item) => $this->onlyPersonnelKeys($item, $fields),
            $items
        ));
    }

    protected function onlyPersonnelKeys(mixed $item, array $fields): array
    {
        if (! is_array($item)) {
            return [];
        }

        return array_intersect_key($item, array_flip($fields));
    }

    protected function safePersonnelJobFields(): array
    {
        return ['id', 'name', 'describe'];
    }

    protected function safePersonnelFrameFields(): array
    {
        return ['id', 'name', 'user_count', 'is_mastart', 'is_admin', 'superior_uid'];
    }

    protected function safePersonnelSuperiorFields(): array
    {
        return ['id', 'uid', 'name', 'avatar'];
    }
}
