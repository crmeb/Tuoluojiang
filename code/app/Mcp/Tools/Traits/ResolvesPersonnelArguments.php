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

use App\Constants\CustomEnum\ClueEnum;
use App\Constants\CustomEnum\ContractEnum;
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\CustomEnum\LiaisonEnum;
use App\Constants\CustomEnum\OddsEnum;
use App\Constants\CustomEnum\ProductEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Config\SalesmanCustomService;

trait ResolvesPersonnelArguments
{
    protected function getSearchFieldsByCustomType(string $customType): array
    {
        try {
            $service    = app(SalesmanCustomService::class);
            $customData = $service->salesmanCustomField($this->getUserDbId(), $customType);
            return $this->mergeSearchFields($this->getEnumSearchFields($customType), $customData['search'] ?? []);
        } catch (\Throwable) {
            return $this->getEnumSearchFields($customType);
        }
    }

    protected function getDynamicSearchFieldSchema(array $field): array
    {
        $fieldType   = $field['input_type'] ?? 'input';
        $description = $field['name'] ?? ($field['field'] ?? '');

        return match ($fieldType) {
            'select', 'radio' => [
                'type'        => 'string',
                'description' => $description . '（筛选）',
            ],
            'checkbox', 'multiple' => [
                'type'        => 'array',
                'description' => $description . '（多选筛选）',
                'items'       => ['type' => 'string'],
            ],
            'date', 'datetime' => [
                'type'        => 'string',
                'description' => $description . '（日期筛选，格式：YYYY-MM-DD 或 YYYY-MM-DD - YYYY-MM-DD）',
            ],
            'number' => [
                'type'        => 'string',
                'description' => $description . '（数值筛选）',
            ],
            'personnel' => [
                'type'        => 'array',
                'description' => $description . '（员工ID数组）',
                'items'       => ['type' => 'integer'],
            ],
            default => [
                'type'        => 'string',
                'description' => $description . '（关键词搜索）',
            ],
        };
    }

    protected function normalizeDynamicSearchValue(array $field, mixed $value): mixed
    {
        if (($field['input_type'] ?? '') !== 'personnel') {
            return $value;
        }

        return $this->resolvePersonnelIds($value);
    }

    protected function resolvePersonnelIds(mixed $value): array
    {
        $items = $this->normalizePersonnelItems($value);
        if ($items === []) {
            return [-1];
        }

        $ids   = [];
        $names = [];
        foreach ($items as $item) {
            if (is_numeric($item)) {
                $ids[] = (int) $item;
                continue;
            }
            $names[] = $item;
        }

        if ($names) {
            $adminService = app(AdminService::class);
            $exactUsers   = $adminService->column(['name_eq' => $names], ['id', 'name']);
            $exactNames   = [];
            foreach ($exactUsers as $user) {
                $ids[]        = (int) $user['id'];
                $exactNames[] = $user['name'];
            }

            $missingNames = array_diff($names, $exactNames);
            foreach ($missingNames as $name) {
                $ids = array_merge($ids, array_map('intval', $adminService->column(['name_like' => $name], 'id')));
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        return $ids ?: [-1];
    }

    private function normalizePersonnelItems(mixed $value): array
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $items = array_merge($items, $this->normalizePersonnelItems($item));
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
                return $this->normalizePersonnelItems($decoded);
            }
        }

        return array_values(array_filter(array_map(
            static fn ($item) => trim($item),
            preg_split('/[,，、;；]+/', $value) ?: []
        ), static fn ($item) => $item !== ''));
    }

    private function getEnumSearchFields(string $customType): array
    {
        return match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => $this->mergeSearchFields(
                CustomerEnum::CUSTOMER_SEARCH_FIELD,
                CustomerEnum::CUSTOMER_VIEWER_SEARCH_FIELD,
                CustomerEnum::CUSTOMER_CHARGE_SEARCH_FIELD,
                CustomerEnum::CUSTOMER_HEIGHT_SEAS_SEARCH_FIELD,
            ),
            ViewSearchEnum::VIEW_CUSTOMER_SEAS => $this->mergeSearchFields(
                CustomerEnum::CUSTOMER_SEARCH_FIELD,
                CustomerEnum::CUSTOMER_HEIGHT_SEAS_SEARCH_FIELD,
            ),
            ViewSearchEnum::VIEW_CONTRACT => $this->mergeSearchFields(
                ContractEnum::CONTRACT_SEARCH_FIELD,
                ContractEnum::CONTRACT_VIEWER_SEARCH_FIELD,
                ContractEnum::CONTRACT_CHARGE_SEARCH_FIELD,
            ),
            ViewSearchEnum::VIEW_CLUE => $this->mergeSearchFields(
                ClueEnum::CLUE_SEARCH_FIELD,
                ClueEnum::CLUE_HEIGHT_SEARCH_FIELD,
                ClueEnum::CLUE_HEIGHT_SEAS_SEARCH_FIELD,
                ClueEnum::CLUE_SEAS_SEARCH_FIELD,
                ClueEnum::CLUE_VIEWER_SEARCH_FIELD,
                ClueEnum::CLUE_CHARGE_SEARCH_FIELD,
            ),
            ViewSearchEnum::VIEW_CLUE_SEAS => $this->mergeSearchFields(
                ClueEnum::CLUE_SEARCH_FIELD,
                ClueEnum::CLUE_HEIGHT_SEAS_SEARCH_FIELD,
                ClueEnum::CLUE_SEAS_SEARCH_FIELD,
            ),
            ViewSearchEnum::VIEW_ODDS => $this->mergeSearchFields(
                OddsEnum::ODDS_SEARCH_FIELD,
                OddsEnum::ODDS_VIEWER_SEARCH_FIELD,
                OddsEnum::ODDS_CHARGE_SEARCH_FIELD,
            ),
            ViewSearchEnum::VIEW_LIAISON => LiaisonEnum::LIAISON_SEARCH_FIELD,
            ViewSearchEnum::VIEW_PRODUCT => $this->mergeSearchFields(
                ProductEnum::PRODUCT_SEARCH_FIELD,
                ProductEnum::PRODUCT_CHARGE_SEARCH_FIELD,
            ),
            default => [],
        };
    }

    private function mergeSearchFields(array ...$fieldGroups): array
    {
        $fields = [];
        foreach ($fieldGroups as $fieldGroup) {
            foreach ($fieldGroup as $field) {
                if (empty($field['field'])) {
                    continue;
                }
                $fields[$field['field']] = array_merge($fields[$field['field']] ?? [], $field);
            }
        }
        return array_values($fields);
    }
}
