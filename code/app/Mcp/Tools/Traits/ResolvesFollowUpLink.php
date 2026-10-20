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

namespace App\Mcp\Tools\Traits;

use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\Lead;
use App\Http\Model\Customer\Opportunity;
use Illuminate\Database\Eloquent\Builder;

/**
 * 用客户、线索或商机名称定位跟进对象，避免向用户索要业务 ID.
 */
trait ResolvesFollowUpLink
{
    use ResolvesNaturalRecord;

    /**
     * 按已定位 ID 或名称解析关联业务。多个名称命中时返回可选名称，不返回给用户看的 ID 说明.
     *
     * @param array<string, mixed> $arguments 工具入参
     * @param string $linkType 关联类型
     *
     * @return array{id: int, name: string}|array<string, mixed>
     */
    protected function resolveFollowUpEntity(array $arguments, string $linkType): array
    {
        $meta = $this->followUpEntityMeta($linkType);
        if ($meta === null) {
            return ['error' => true, 'message' => '跟进记录类型不匹配'];
        }
        [$nameColumn, $label] = $meta;

        $eid = (int) ($arguments['eid'] ?? 0);
        $entityName = $this->firstFilledString($arguments, ['entity_name', 'customer_name', 'lead_name', 'opportunity_name', 'name']);
        if ($eid <= 0 && $entityName === '') {
            return ['error' => true, 'message' => '请填写' . $label . '名称', 'field' => 'entity_name'];
        }

        $query = $this->followUpEntityQuery($linkType);
        if (! $query instanceof Builder) {
            return ['error' => true, 'message' => '跟进记录类型不匹配'];
        }
        if ($eid > 0) {
            $byId = $this->followUpEntityById(clone $query, $nameColumn, $label, $eid);
            if ($entityName === '' || (! isset($byId['error']) && $this->followUpNameAgrees((string) $byId['name'], $entityName))) {
                return $byId;
            }
        }

        $this->limitToCustomerScope($query);
        $rows = $this->matchEntitiesByName($query, $nameColumn, $entityName);
        if ($rows->isEmpty()) {
            return ['error' => true, 'message' => '没有找到名称匹配的' . $label, 'field' => 'entity_name'];
        }
        if ($rows->count() > 1) {
            $candidates = $rows->map(fn ($row): array => [
                'id'   => (int) $row->id,
                'name' => (string) $row->{$nameColumn},
            ])->all();
            $names          = array_map(static fn (array $item): string => $item['name'], $candidates);
            $namesAreUnique = count($names) === count(array_unique($names));

            return [
                'error'           => true,
                'needs_selection' => true,
                'field_key'       => $namesAreUnique ? 'entity_name' : 'eid',
                'field_label'     => $label . '名称',
                'message'         => '找到多个' . $label . '，请选择要跟进的' . $label,
                'candidates'      => $namesAreUnique
                    ? array_map(static fn (array $item): array => [
                        'value' => $item['name'],
                        'label' => $item['name'],
                    ], $candidates)
                    : $candidates,
            ];
        }

        $row = $rows->first();

        return ['id' => (int) $row->id, 'name' => (string) $row->{$nameColumn}];
    }

    /**
     * 关联类型对应的名称列和用户可见称呼，不访问数据库.
     *
     * @return array{0: string, 1: string}|null
     */
    private function followUpEntityMeta(string $linkType): ?array
    {
        return match ($linkType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => ['customer_name', '客户'],
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS         => ['name', '线索'],
            ViewSearchEnum::VIEW_ODDS                                          => ['name', '商机'],
            default                                                            => null,
        };
    }

    /**
     * 按关联类型建立查询.
     */
    private function followUpEntityQuery(string $linkType): ?Builder
    {
        return match ($linkType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => Customer::query(),
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS         => Lead::query(),
            ViewSearchEnum::VIEW_ODDS                                          => Opportunity::query(),
            default                                                            => null,
        };
    }

    /**
     * 在数据权限内按主键读取关联业务.
     *
     * @return array{id: int, name: string}|array<string, mixed>
     */
    private function followUpEntityById(Builder $query, string $nameColumn, string $label, int $eid): array
    {
        $this->limitToCustomerScope($query);
        $row = $query->whereKey($eid)->first(['id', $nameColumn]);
        if ($row === null) {
            return ['error' => true, 'message' => '没有找到这个' . $label, 'field' => 'entity_name'];
        }

        return ['id' => (int) $row->id, 'name' => (string) $row->{$nameColumn}];
    }

    /**
     * 已有内部 ID 时，仅在它和用户说的名称指向同一对象时才采用.
     */
    private function followUpNameAgrees(string $recordName, string $entityName): bool
    {
        return $recordName === $entityName
            || str_contains($recordName, $entityName)
            || str_contains($entityName, $recordName);
    }

    /**
     * 先按原名精确匹配，再模糊匹配；用户把「客户」写进名称时，去掉这类前缀重试.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function matchEntitiesByName(Builder $query, string $nameColumn, string $entityName): \Illuminate\Support\Collection
    {
        $names    = [$entityName];
        $stripped = trim((string) (preg_replace('/^(客户|线索|商机)/u', '', $entityName) ?? $entityName));
        if ($stripped !== '' && $stripped !== $entityName) {
            $names[] = $stripped;
        }

        foreach ([true, false] as $exact) {
            foreach ($names as $name) {
                $lookup = (clone $query)->orderByDesc('id')->limit(6);
                if ($exact) {
                    $lookup->where($nameColumn, $name);
                } else {
                    $lookup->where($nameColumn, 'like', '%' . $this->escapeLike($name) . '%');
                }
                $rows = $lookup->get(['id', $nameColumn]);
                if ($rows->isNotEmpty()) {
                    return $rows;
                }
            }
        }

        return collect();
    }

    /**
     * 非管理员只查客户模块数据权限内的负责人.
     */
    private function limitToCustomerScope(Builder $query): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $uids = $this->getDataUids(ModuleEnum::CUSTOMER);
        $query->whereIn('uid', $uids !== [] ? $uids : [-1]);
    }
}
