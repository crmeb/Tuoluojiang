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

namespace App\Mcp\Support;

/**
 * 按工具 JSON Schema（getInputSchema）做轻量入参校验。
 * 只负责协议边界（类型 / required / enum）；业务校验仍由工具自身处理。
 */
final class McpArgumentsValidator
{
    public const ERROR_CODE = 'INVALID_ARGUMENTS';

    /**
     * @param array<string, mixed> $schema JSON Schema（通常为 type=object + properties）
     * @param array<string, mixed> $arguments
     *
     * @return array{
     *     valid: bool,
     *     missing_fields: list<string>,
     *     invalid_fields: array<string, string>,
     *     message: string
     * }
     */
    public function validate(array $schema, array $arguments): array
    {
        $missing = [];
        $invalid = [];

        if ($schema === []) {
            return $this->ok();
        }

        $type = (string) ($schema['type'] ?? 'object');
        if ($type !== '' && $type !== 'object') {
            return [
                'valid'          => false,
                'missing_fields' => [],
                'invalid_fields' => ['_root' => 'schema type must be object'],
                'message'        => '工具入参 Schema 非 object',
            ];
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $required   = [];
        foreach ((array) ($schema['required'] ?? []) as $field) {
            $name = trim((string) $field);
            if ($name !== '') {
                $required[] = $name;
            }
        }

        foreach ($required as $field) {
            if (! array_key_exists($field, $arguments) || $this->isEmptyRequired($arguments[$field])) {
                $missing[] = $field;
            }
        }

        foreach ($arguments as $field => $value) {
            $fieldName = (string) $field;
            if ($fieldName === '' || ! isset($properties[$fieldName])) {
                continue;
            }
            if ($value === null) {
                continue;
            }
            $propSchema = is_array($properties[$fieldName]) ? $properties[$fieldName] : [];
            $error      = $this->validateValue($fieldName, $value, $propSchema);
            if ($error !== null) {
                $invalid[$fieldName] = $error;
            }
        }

        if ($missing === [] && $invalid === []) {
            return $this->ok();
        }

        $parts = [];
        if ($missing !== []) {
            $parts[] = '缺少必填字段: ' . implode(', ', $missing);
        }
        if ($invalid !== []) {
            $details = [];
            foreach ($invalid as $field => $reason) {
                $details[] = $field . '(' . $reason . ')';
            }
            $parts[] = '字段类型或取值不合法: ' . implode(', ', $details);
        }

        return [
            'valid'          => false,
            'missing_fields' => array_values($missing),
            'invalid_fields' => $invalid,
            'message'        => implode('；', $parts),
        ];
    }

    /**
     * 组装 ToolExecutor / MCP 可直接返回的错误载荷.
     *
     * @param array{
     *     valid: bool,
     *     missing_fields: list<string>,
     *     invalid_fields: array<string, string>,
     *     message: string
     * } $result
     *
     * @return array<string, mixed>
     */
    public function toErrorPayload(array $result, string $toolName = ''): array
    {
        return [
            'error'          => true,
            'error_code'     => self::ERROR_CODE,
            'message'        => (string) ($result['message'] ?? 'Invalid arguments'),
            'missing_fields' => array_values((array) ($result['missing_fields'] ?? [])),
            'invalid_fields' => (array) ($result['invalid_fields'] ?? []),
            'tool'           => $toolName,
        ];
    }

    /**
     * @return array{valid: true, missing_fields: list<string>, invalid_fields: array<string, string>, message: string}
     */
    private function ok(): array
    {
        return [
            'valid'          => true,
            'missing_fields' => [],
            'invalid_fields' => [],
            'message'        => '',
        ];
    }

    private function isEmptyRequired(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $propSchema
     */
    private function validateValue(string $field, mixed $value, array $propSchema): ?string
    {
        $expectedType = $propSchema['type'] ?? null;
        if (is_array($expectedType)) {
            $matched = false;
            foreach ($expectedType as $candidate) {
                if ($this->matchesType($value, (string) $candidate)) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return 'expected type one of [' . implode('|', array_map('strval', $expectedType)) . ']';
            }
        } elseif (is_string($expectedType) && $expectedType !== '' && ! $this->matchesType($value, $expectedType)) {
            return 'expected type ' . $expectedType;
        }

        if (isset($propSchema['enum']) && is_array($propSchema['enum']) && ! $this->inEnum($value, $propSchema['enum'])) {
            return 'value not in enum';
        }

        if (($expectedType === 'array' || (is_array($expectedType) && in_array('array', $expectedType, true)))
            && is_array($value)
            && isset($propSchema['items'])
            && is_array($propSchema['items'])
        ) {
            $itemType = $propSchema['items']['type'] ?? null;
            if (is_string($itemType) && $itemType !== '') {
                foreach ($value as $index => $item) {
                    if (! $this->matchesType($item, $itemType)) {
                        return 'items[' . $index . '] expected type ' . $itemType;
                    }
                }
            }
        }

        return null;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string'  => is_string($value),
            'integer' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
            'number'  => is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)),
            'boolean' => is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1' || $value === 'true' || $value === 'false',
            'array'   => is_array($value) && $this->isListArray($value),
            'object'  => is_array($value) && ! $this->isListArray($value),
            'null'    => $value === null,
            default   => true,
        };
    }

    /**
     * @param list<mixed> $allowed
     */
    private function inEnum(mixed $value, array $allowed): bool
    {
        foreach ($allowed as $candidate) {
            if ($value === $candidate) {
                return true;
            }
            if (is_bool($value) || is_bool($candidate)) {
                continue;
            }
            if (is_numeric($value) && is_numeric($candidate) && (float) $value === (float) $candidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * PHP 8.0 兼容的 list 判定（等价 array_is_list）.
     *
     * @param array<mixed> $value
     */
    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }
        $expected = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expected) {
                return false;
            }
            ++$expected;
        }

        return true;
    }
}
