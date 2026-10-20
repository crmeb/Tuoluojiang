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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Config\RelationFieldRegistry;

trait ResolvesCustomFormFields
{
    use ResolvesPersonnelArguments;

    protected function getCreateFieldsByCustomType(int $customType, array $excludeKeys = []): array
    {
        try {
            $fields = app(FormService::class)->getFormDataList($customType) ?? [];
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($fields, function (array $field) use ($excludeKeys) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                return false;
            }

            return ! in_array($key, $excludeKeys, true);
        }));
    }

    protected function getDynamicCreateFieldSchema(array $field): array
    {
        $description = (string) ($field['key_name'] ?? $field['key'] ?? '');
        $inputType   = strtolower((string) ($field['input_type'] ?? 'input'));
        $type        = strtolower((string) ($field['type'] ?? ''));
        $required    = (bool) ($field['required'] ?? false);

        $schema = match ($inputType) {
            'select', 'radio' => $this->buildSelectCreateFieldSchema($field, $description, $type),
            'checked', 'images', 'file' => [
                'type'        => 'array',
                'description' => $description,
                'items'       => ['type' => 'string'],
            ],
            'datetime', 'date' => [
                'type'        => 'string',
                'description' => $description . '（日期时间，格式 YYYY-MM-DD 或 YYYY-MM-DD HH:mm:ss）',
            ],
            default => match ($type) {
                'number' => [
                    'type'        => 'number',
                    'description' => $description,
                ],
                default => [
                    'type'        => 'string',
                    'description' => $description,
                ],
            },
        };

        if ($required) {
            $schema['description'] .= '（必填）';
        }

        $key = (string) ($field['key'] ?? '');
        $relationHint = RelationFieldRegistry::schemaHint($key);
        if ($relationHint !== '') {
            $schema['description'] .= '。' . $relationHint;
        }

        return $schema;
    }

    /**
     * 选择类字段 Schema：附带 enum / x-ai-options，供写预检渲染为下拉而非文本输入.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    protected function buildSelectCreateFieldSchema(array $field, string $description, string $type): array
    {
        $isMultiple = $type === 'multiple';
        $schema     = [
            'type'        => $isMultiple ? 'array' : 'string',
            'description' => $description,
        ];
        if ($isMultiple) {
            $schema['items'] = ['type' => 'string'];
        }

        $aiOptions = [];
        foreach ($this->resolveCustomFieldOptions($field) as $option) {
            if (! is_array($option)) {
                continue;
            }
            $value = $option['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $aiOptions[] = [
                'value' => $value,
                'label' => (string) ($option['label'] ?? $value),
            ];
        }
        if ($aiOptions === []) {
            return $schema;
        }

        if ($isMultiple) {
            $schema['items'] = [
                'type' => 'string',
                'enum' => array_column($aiOptions, 'value'),
            ];
        } else {
            $schema['enum'] = array_column($aiOptions, 'value');
        }
        $schema['x-ai-options'] = $aiOptions;
        $schema['description'] .= '（从以下选项中选择：' . implode('、', array_column($aiOptions, 'label')) . '）';

        return $schema;
    }

    protected function buildCreateInputSchema(int $customType, array $staticProperties = [], array $required = [], array $excludeKeys = []): array
    {
        $properties = $staticProperties;
        $required   = array_values($required);

        foreach ($this->getCreateFieldsByCustomType($customType, $excludeKeys) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            // 静态声明优先：工具显式定义的结构（如 products 的明细对象）比自定义表单推导的更精确，
            // 否则同名表单字段会把它覆盖成宽泛类型（products 被覆盖成字符串数组）.
            if (! array_key_exists($key, $staticProperties)) {
                $properties[$key] = $this->getDynamicCreateFieldSchema($field);
            }
            if (! empty($field['required'])) {
                $required[] = $key;
            }
        }

        $schema = [
            'type'       => 'object',
            'properties' => $properties,
        ];
        if ($required !== []) {
            $schema['required'] = array_values(array_unique($required));
        }

        return $schema;
    }

    protected function buildCreatePayload(array $arguments, int $customType, array $excludeKeys = []): array
    {
        $payload = [];
        foreach ($this->getCreateFieldsByCustomType($customType, $excludeKeys) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '' || ! array_key_exists($key, $arguments)) {
                continue;
            }
            $value = $arguments[$key];
            if ($value === '' || $value === null) {
                continue;
            }
            $payload[$key] = $this->normalizeDynamicCreateValue($field, $value);
        }

        return $payload;
    }

    protected function mergeStaticCreateProperties(array $static): array
    {
        return $static;
    }

    protected function normalizeDynamicCreateValue(array $field, mixed $value): mixed
    {
        $inputType = strtolower((string) ($field['input_type'] ?? ''));

        if ($inputType === 'personnel') {
            return $this->resolvePersonnelIds($value);
        }

        return $value;
    }

    /**
     * 将租户自定义表单转为 MCP/Agent 可填字段清单.
     *
     * @return array{custom_type: int, fields: array<int, array<string, mixed>>, required_fields: array<int, string>, hint: string}
     */
    protected function buildMcpCustomFormPayload(int $customType, array $excludeKeys = [], bool $includeLiaison = false): array
    {
        $fields = [];
        foreach ($this->getCreateFieldsByCustomType($customType, $excludeKeys) as $field) {
            $mapped = $this->mapCustomFormFieldToMcp($field);
            if ($mapped !== null) {
                $fields[] = $mapped;
            }
        }

        if ($includeLiaison && $customType === CustomEnum::CUSTOMER) {
            foreach ($this->getCreateFieldsByCustomType(CustomEnum::LIAISON, ['eid']) as $field) {
                $mapped = $this->mapCustomFormFieldToMcp($field);
                if ($mapped !== null) {
                    $mapped['title'] = '联系人' . ($mapped['title'] !== '' ? '·' . $mapped['title'] : '');
                    $fields[]        = $mapped;
                }
            }
        }

        return [
            'custom_type'     => $customType,
            'fields'          => $fields,
            'required_fields' => array_values(array_map(
                static fn (array $field): string => (string) $field['field'],
                array_filter($fields, static fn (array $field): bool => (bool) ($field['required'] ?? false))
            )),
            'hint'            => '将 fields[].field 作为对应 create 工具的参数键提交',
        ];
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>|null
     */
    protected function mapCustomFormFieldToMcp(array $field): ?array
    {
        $key = (string) ($field['key'] ?? '');
        if ($key === '') {
            return null;
        }

        $inputType = strtolower((string) ($field['input_type'] ?? 'input'));
        $type      = strtolower((string) ($field['type'] ?? ''));
        if (in_array($inputType, ['file', 'images', 'checked'], true)) {
            return null;
        }

        $mapped = [
            'field'    => $key,
            'symbol'   => $key,
            'title'    => (string) ($field['key_name'] ?? $key),
            'required' => (bool) ($field['required'] ?? false),
            'type'     => $this->mapCustomFieldInputType($inputType, $type),
            'hidden'   => false,
            'disabled' => false,
            'options'  => $this->resolveCustomFieldOptions($field),
        ];

        if ($inputType === 'personnel') {
            $mapped['type'] = 'personnel';
        }

        return $mapped;
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return array<int, array<string, mixed>>
     */
    protected function resolveCustomFieldOptions(array $field): array
    {
        $dictIdent = (string) ($field['dict_ident'] ?? '');
        if ($dictIdent === '' || $dictIdent === 'area_cascade') {
            return [];
        }

        try {
            $items = app(DictDataService::class)->getTreeData(
                ['type_name' => $dictIdent, 'status' => 1],
                ['name as label', 'name as text', 'value', 'pid']
            );
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ((array) $items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $value = $item['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $options[] = [
                'value' => $value,
                'label' => (string) ($item['label'] ?? $item['text'] ?? $value),
            ];
        }

        return $options;
    }

    protected function mapCustomFieldInputType(string $inputType, string $type): string
    {
        return match ($inputType) {
            'select', 'radio' => 'select',
            'datetime'        => 'datetime',
            'date'            => 'date',
            'textarea'        => 'textarea',
            default           => match ($type) {
                'number' => 'number',
                default  => 'input',
            },
        };
    }
}
