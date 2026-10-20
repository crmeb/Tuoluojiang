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

namespace App\Http\Requests\Traits;

use App\Constants\CacheEnum;
use App\Constants\CodeEnum;
use App\Http\Service\Config\FormService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * 动态表单验证 Trait
 * 用于根据数据库中的字段定义动态生成验证规则.
 */
trait DynamicFormValidate
{
    /**
     * 排除的ID（用于更新场景）.
     */
    protected int $excludeId = 0;

    /**
     * 排除的字段列表.
     * @var array<string>
     */
    protected array $excludeFields = [];

    /**
     * 单字段编辑时的字段名.
     */
    protected ?string $singleField = null;

    /**
     * 允许额外接收的非自定义表单字段.
     */
    protected array $extraSingleFieldRules = [];

    /**
     * 设置排除的ID.
     */
    public function setExcludeId(int $id): self
    {
        $this->excludeId = $id;
        return $this;
    }

    /**
     * 自动验证（覆盖父类，支持单字段编辑）.
     * @return bool
     * @throws ValidationException
     */
    public function check(array $data = [], array $rules = [])
    {
        if ($this->currentScene) {
            $this->getScene($this->currentScene);
        }

        // 注册自定义验证规则
        $this->registerCustomRules();

        if (empty($rules)) {
            $rules = $this->rules() ?: $this->rules;
        }

        if (empty($this->message) && method_exists($this, 'message')) {
            $this->message = $this->message();
        }

        $emptyData = empty($data);

        // 单字段编辑时，从 value 参数获取数据
        if ($this->singleField) {
            $data = [$this->singleField => request()->input('value')];
        } elseif (! empty($this->only)) {
            $sceneRules = [];
            $method     = strtolower($this->request()->method());
            if ($method !== 'post') {
                $method = 'get';
            }
            foreach ($this->only as $key => $value) {
                if (array_key_exists($value, $rules)) {
                    $sceneRules[$value] = $rules[$value];
                    if ($emptyData) {
                        $data[$value] = request()->{$method}($value);
                    }
                }
            }
            $rules = $sceneRules;
        } else {
            if ($emptyData) {
                $data = request()->all();
            }
        }

        $validator          = app('validator')->make($data, $rules, $this->message, $this->attributes());
        $this->currentScene = null;
        if ($validator->fails()) {
            return $this->failedValidation($validator);
        }
        $this->data = $validator->validated();
        return true;
    }

    /**
     * 生成动态错误消息.
     * @throws \ReflectionException
     */
    public function message(): array
    {
        $list     = $this->getFormFields();
        $messages = [];

        foreach ($list as $item) {
            $key       = $item['key'] ?? '';
            $keyName   = $item['key_name'] ?? $key;
            $inputType = strtolower($item['input_type'] ?? '');
            $type      = strtolower($item['type'] ?? '');
            $min       = $item['min'] ?? null;
            $max       = $item['max'] ?? null;

            if (empty($key)) {
                continue;
            }

            // 必填消息
            if ($item['required'] ?? false) {
                $messages[$key . '.required'] = '请输入' . $keyName;
            }

            // 根据 input_type 生成消息
            switch ($inputType) {
                case 'input':
                    if ($type === 'number') {
                        if ($max !== null && $max !== '') {
                            $messages[$key . '.max'] = $keyName . '最大值为' . $max;
                        }
                        if ($min !== null && $min !== '') {
                            $messages[$key . '.min'] = $keyName . '最小值为' . $min;
                        }
                    } else {
                        $text = '字';
                        if ($max !== null && $max !== '') {
                            $messages[$key . '.max'] = $keyName . '最多输入' . $max . '个' . $text;
                        }
                        if ($min !== null && $min !== '') {
                            $messages[$key . '.min'] = $keyName . '最少输入' . $min . '个' . $text;
                        }
                    }
                    break;
                case 'select':
                    if ($type !== 'single') {
                        if ($max) {
                            $messages[$key . '.max'] = $keyName . '最多选择' . $max . '个';
                        }
                        if ($min) {
                            $messages[$key . '.min'] = $keyName . '最少选择' . $min . '个';
                        }
                    }
                    break;
                case 'checked':
                case 'file':
                case 'images':
                    if ($max) {
                        $messages[$key . '.max'] = $keyName . '最多选择' . $max . '个';
                    }
                    if ($min) {
                        $messages[$key . '.min'] = $keyName . '最少选择' . $min . '个';
                    }
                    break;
                case 'datetime':
                    break;
                case 'date':
                    if (datetime_timestamp((string) $max) !== false && datetime_timestamp((string) $max) > 0) {
                        $messages[$key . '.before_or_equal'] = $keyName . '不能晚于' . $max;
                    }
                    if (datetime_timestamp((string) $min) !== false && datetime_timestamp((string) $min) > 0) {
                        $messages[$key . '.after_or_equal'] = $keyName . '不能早于' . $min;
                    }
                    break;
                case 'oawangeditor':
                    if ($max !== null && $max !== '') {
                        $messages[$key . '.max'] = $keyName . '内容过长';
                    }
                    if ($min !== null && $min !== '') {
                        $messages[$key . '.min'] = $keyName . '内容过少';
                    }
                    break;
            }
        }

        return $messages;
    }

    /**
     * 生成动态字段名称.
     * @throws \ReflectionException
     */
    public function attributes(): array
    {
        return collect($this->getFormFields())
            ->filter(fn ($item) => ! empty($item['key']))
            ->mapWithKeys(fn ($item) => [$item['key'] => $item['key_name'] ?: $item['key']])
            ->all();
    }

    /**
     * 注册自定义验证规则.
     */
    public function registerCustomRules(): void
    {
        $validator = app('validator');

        // 注册客户重复检查规则
        $validator->extend('customer_duplicate_check', function ($attribute, $value, $parameters, $validator) {
            if (empty($value)) {
                return true;
            }

            // 如果 force=1，跳过重复校验
            $force = request()->post('force', 0);
            if ((int) $force === 1) {
                return true;
            }

            $table = $this->getTableName();
            $query = DB::table($table)->where($attribute, $value);

            if ($this->excludeId > 0) {
                $query->where('id', '!=', $this->excludeId);
            }

            $exists = $query->exists();

            if ($exists) {
                // 使用 MessageBag 直接添加消息，消息中嵌入错误码
                $message = '该客户信息已存在，是否继续' . ($this->excludeId > 0 ? '修改客户' : '添加客户') . '[CODE:' . CodeEnum::VERIFY_CODE . ']';
                $validator->getMessageBag()->add($attribute, $message);
                return false;
            }

            return true;
        }, '该客户信息已存在，是否继续添加客户？[CODE:' . CodeEnum::VERIFY_CODE . ']');

        // 注册唯一性检查规则
        $validator->extend('dynamic_unique', function ($attribute, $value, $parameters, $validator) {
            return $this->validateDynamicUnique($attribute, $value, $parameters);
        }, '该值已存在');
    }

    /**
     * 设置需要排除的字段.
     * @param array<string> $fields 需要排除的字段列表
     * @return $this
     */
    public function setExcludeFields(array $fields): self
    {
        $this->excludeFields = $fields;
        return $this;
    }

    /**
     * 处理单字段编辑场景
     * 在子类构造函数中调用.
     */
    protected function handleSingleFieldEdit(): void
    {
        // 使用 input() 而不是 post()，因为 JSON 请求体中数据无法通过 post() 获取
        $field = request()->input('field');
        if ($field && request()->has('value')) {
            $this->singleField = $field;
            $this->only        = [$field];
        }
    }

    /**
     * 获取需要排除的字段.
     * @return array<string>
     */
    protected function getExcludeFields(): array
    {
        return $this->excludeFields ?? [];
    }

    /**
     * 获取表单字段列表（带缓存）.
     * @throws \ReflectionException
     */
    protected function getFormFields(): array
    {
        $cacheKey = md5('dynamic_validate_fields_' . $this->formType);

        return Cache::tags([CacheEnum::TAG_FORM])->remember($cacheKey, 3600, function () {
            return app(FormService::class)->getFormDataList($this->formType) ?? [];
        });
    }

    /**
     * 生成动态验证规则.
     * @throws \ReflectionException
     */
    protected function rules(): array
    {
        $list  = $this->getFormFields();
        $rules = [];

        // 单字段编辑时，只生成该字段的规则
        $targetField = $this->singleField;

        // 获取需要排除的字段
        $excludeFields = $this->getExcludeFields();

        foreach ($list as $item) {
            $key = $item['key'] ?? '';
            if (empty($key)) {
                continue;
            }

            // 排除指定字段
            if (in_array($key, $excludeFields)) {
                continue;
            }

            // 单字段编辑时，只处理目标字段
            if ($targetField && $key !== $targetField) {
                continue;
            }

            // buildDynamicRule 现在返回数组
            $dynamicRules = $this->buildDynamicRule($item);
            // 即使规则为空也添加字段，确保前端参数能被 validated() 接收
            $rules[$key] = ! empty($dynamicRules) ? $dynamicRules : ['nullable'];

            // 处理唯一性校验（uniqued字段，排除customer_name和customer_tel）
            if (($item['uniqued'] ?? false) && ! in_array($key, ['customer_name', 'customer_tel'])) {
                $uniqueRule = $this->buildUniqueRule($item);
                if ($uniqueRule) {
                    // 合并 Rule 对象到规则数组中
                    if (isset($rules[$key])) {
                        $rules[$key][] = $uniqueRule;
                    } else {
                        $rules[$key] = [$uniqueRule];
                    }
                }
            }
        }
        $extraRules = $this->getExtraSingleFieldRules();
        foreach ($extraRules as $key => $rule) {
            if ($targetField && $key !== $targetField) {
                continue;
            }
            $rules[$key] = $rule ?: ($rules[$key] ?? ['nullable']);
        }
        // 添加客户名称和电话的特殊校验（重复检查）
        return $this->addCustomerDuplicateRules($rules, $list);
    }

    /**
     * 获取额外允许字段的验证规则.
     */
    protected function getExtraSingleFieldRules(): array
    {
        return $this->extraSingleFieldRules;
    }

    protected function getRequiredArrayRules(string $key): array
    {
        return $this->isFormFieldRequired($key) ? ['required', 'array', 'min:1'] : ['array'];
    }

    protected function isFormFieldRequired(string $key): bool
    {
        return collect($this->getFormFields())->contains(fn ($item) => ($item['key'] ?? '') === $key && (int) ($item['required'] ?? 0) === 1);
    }

    /**
     * 构建唯一性验证规则.
     */
    protected function buildUniqueRule(array $item): object
    {
        $key       = $item['key'] ?? '';
        $inputType = strtolower($item['input_type'] ?? '');
        $type      = strtolower($item['type'] ?? '');

        // 根据字段类型处理唯一性值
        $value = match ($inputType) {
            'select' => $type === 'single'
                ? 'integer'
                : 'array',
            'radio' => 'integer',
            default => in_array($inputType, ['date', 'input', 'oawangeditor'])
                ? 'string'
                : 'string',
        };

        // 使用动态表名
        $table = $this->getTableName();

        $rule = Rule::unique($table);

        if ($this->excludeId > 0) {
            $rule->ignore($this->excludeId);
        }

        return $rule->where(function ($query) use ($key, $value) {
            $query->where($key, $value);
        });
    }

    /**
     * 获取对应的表名.
     */
    protected function getTableName(): string
    {
        return match ($this->formType) {
            2       => 'contract',      // CustomEnum::CONTRACT
            3       => 'customer_liaison',       // CustomEnum::LIAISON
            4       => 'customer_clue',          // CustomEnum::CLUE
            5       => 'customer_odds',          // CustomEnum::ODDS
            6       => 'customer_product',       // CustomEnum::PRODUCT
            default => 'customer',
        };
    }

    /**
     * 添加客户重复校验规则.
     */
    protected function addCustomerDuplicateRules(array $rules, array $list): array
    {
        $hasCustomerName = false;
        $hasCustomerTel  = false;

        foreach ($list as $item) {
            if (($item['key'] ?? '') === 'customer_name') {
                $hasCustomerName = true;
            }
            if (($item['key'] ?? '') === 'customer_tel') {
                $hasCustomerTel = true;
            }
        }

        // 使用自定义验证规则
        if ($hasCustomerName) {
            if (isset($rules['customer_name'])) {
                $rules['customer_name'][] = 'customer_duplicate_check';
            } else {
                $rules['customer_name'] = ['customer_duplicate_check'];
            }
        }

        if ($hasCustomerTel) {
            if (isset($rules['customer_tel'])) {
                $rules['customer_tel'][] = 'customer_duplicate_check';
            } else {
                $rules['customer_tel'] = ['customer_duplicate_check'];
            }
        }
        return $rules;
    }

    /**
     * 生成单个字段的验证规则.
     * @return array<string> 规则数组
     */
    protected function buildDynamicRule(array $item): array
    {
        $inputType = strtolower($item['input_type'] ?? '');
        $type      = strtolower($item['type'] ?? '');
        $min       = $item['min'] ?? null;
        $max       = $item['max'] ?? null;
        $required  = (int) ($item['required'] ?? 0) === 1;

        $rules = [];

        // 处理必填
        if ($required) {
            switch ($inputType) {
                case 'checked':
                case 'select':
                case 'radio':
                case 'datetime':
                case 'date':
                case 'images':
                case 'file':
                case 'input':
                    $rules[] = 'required';
                    break;
            }
        } else {
            $rules[] = 'nullable';
        }

        // 根据 input_type 生成规则
        switch ($inputType) {
            case 'input':
                if ($type === 'number') {
                    $rules[] = 'numeric';
                    if ($min !== null && $min !== '') {
                        $rules[] = 'min:' . (float) $min;
                    }
                    if ($max !== null && $max !== '') {
                        $rules[] = 'max:' . (float) $max;
                    }
                } else {
                    // 文本类型
                    if ($min !== null && $min !== '') {
                        $rules[] = 'min:' . (int) $min;
                    }
                    if ($max !== null && $max !== '') {
                        $rules[] = 'max:' . (int) $max;
                    }
                }
                break;
            case 'select':
                if ($type !== 'single') {
                    $rules[] = 'array';
                    // 只有在必填时才添加 min 验证，非必填时允许空数组
                    if ($required && $min !== null && $min !== '') {
                        $rules[] = 'min:' . (int) $min;
                    }
                    if ($max !== null && $max !== '') {
                        $rules[] = 'max:' . (int) $max;
                    }
                }
                break;
            case 'checked':
            case 'file':
            case 'images':
                $rules[] = 'array';
                // 只有在必填时才添加 min 验证，非必填时允许空数组
                if ($required && $min !== null && $min !== '') {
                    $rules[] = 'min:' . (int) $min;
                }
                if ($max !== null && $max !== '') {
                    $rules[] = 'max:' . (int) $max;
                }
                break;
            case 'date':
            case 'datetime':
                $rules[] = 'date';
                if ($inputType === 'datetime') {
                    break;
                }
                if ($max) {
                    $rules[] = 'before_or_equal:' . $max;
                }
                if ($min) {
                    $rules[] = 'after_or_equal:' . $min;
                }
                break;
            case 'oawangeditor':
            case 'radio':
                // 富文本和单选不校验
                break;
            case 'textarea':
                if ($min !== null && $min !== '') {
                    $rules[] = 'min:' . (int) $min;
                }
                if ($max !== null && $max !== '') {
                    $rules[] = 'max:' . (int) $max;
                }
                break;
            case 'member':
                // 人员选择
                if ($type == 'singlemember') {
                    $rules[] = 'array';
                }
                break;
        }

        return $rules;
    }

    /**
     * 验证客户重复.
     * @return array{bool, string}|true
     */
    protected function validateCustomerDuplicate(string $attribute, mixed $value): array|bool
    {
        if (empty($value)) {
            return true;
        }

        $table = $this->getTableName();
        $query = DB::table($table)->where($attribute, $value);

        if ($this->excludeId > 0) {
            $query->where('id', '!=', $this->excludeId);
        }

        $exists = $query->exists();

        if ($exists) {
            return [
                false,
                '该客户信息已存在，是否继续' . ($this->excludeId > 0 ? '修改客户' : '添加客户'),
            ];
        }

        return true;
    }

    /**
     * 验证动态唯一性.
     */
    protected function validateDynamicUnique(string $attribute, mixed $value, array $parameters): bool
    {
        if (empty($value)) {
            return true;
        }

        $table  = $parameters[0] ?? $this->getTableName();
        $column = $parameters[1] ?? $attribute;

        $query = DB::table($table)->where($column, $value);

        if ($this->excludeId > 0) {
            $query->where('id', '!=', $this->excludeId);
        }

        return ! $query->exists();
    }
}
