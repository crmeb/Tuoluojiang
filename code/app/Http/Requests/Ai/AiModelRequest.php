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

namespace App\Http\Requests\Ai;

use App\Constants\Ai\AiModelEnum;
use App\Constants\Ai\AiModelPresetEnum;
use App\Http\Requests\ApiValidate;
use Illuminate\Validation\Rule;

/**
 * AI 模型配置表单校验.
 *
 * 场景 store/update：新建与编辑模型配置（一行仅一个模型）.
 * 场景 test：测试模型连接；按 model_type 测会话或向量，
 * 预设与一号通不传 base_url（服务端按类型补全），自定义才必填.
 * 场景 fetch：从供应商远程拉取模型列表.
 * 字段：name 配置名称（唯一）、provider 供应商、config_type 配置类型（1 预设/2 自定义/3 一号通）、
 * model_id 模型 ID、model_type 类型（1 会话 / 2 向量）、api_key 密钥、
 * base_url 请求地址（仅自定义 / OpenAI 兼容 / 开启完整 URL 时必填）、is_full_url 是否完整 URL、
 * enable_thinking 思考模式（0/1/2）、json 高级配置（上下文窗口、工具轮次、采样参数）、
 * id 可选模型主键（update 路由带 id；test/fetch 编辑态可传，便于留空 api_key 时复用库内密钥）.
 * 一号通由服务端自动补全 base_url 与 api_key；预设未传时由服务端按 provider 取默认 base_url.
 * 编辑/测试/拉取：有模型 id 时可留空 api_key，由服务端回填已存密钥；新建仍必填.
 */
class AiModelRequest extends ApiValidate
{
    /**
     * 验证场景.
     */
    protected $scene = [
        'store'  => ['name', 'provider', 'config_type', 'model_id', 'model_type', 'api_key', 'base_url', 'is_full_url', 'enable_thinking', 'json'],
        'update' => ['name', 'provider', 'config_type', 'model_id', 'model_type', 'api_key', 'base_url', 'is_full_url', 'enable_thinking', 'json'],
        // test/fetch 可带可选 id：编辑态留空 api_key 时由服务端按 id 复用库内密钥
        'test'   => ['id', 'provider', 'config_type', 'model_id', 'model_type', 'api_key', 'base_url', 'is_full_url'],
        'fetch'  => ['id', 'provider', 'config_type', 'api_key', 'base_url'],
    ];

    /**
     * 验证提示.
     */
    public function message(): array
    {
        return [
            'name.required'      => '请填写模型名称',
            'name.unique'        => '模型名称不能重复',
            'provider.required'  => '请选择模型供应商',
            'provider.in'        => '模型供应商不存在',
            'config_type.in'     => '模型配置类型错误',
            'model_id.required'  => '请选择或输入模型',
            'model_id.string'    => '模型ID格式错误',
            'model_type.required'=> '请选择模型类型',
            'model_type.in'      => '模型类型错误',
            'api_key.required'   => '请填写API Key',
            'base_url.required'  => '请填写模型请求地址',
            'base_url.url'       => '模型请求地址格式错误',
            'is_full_url.in'     => '完整URL开关取值错误',
            'enable_thinking.in' => '思考模式取值错误',
            'json.array'         => '高级配置格式错误',
        ];
    }

    /**
     * 验证规则.
     */
    protected function rules(): array
    {
        return [
            'name'        => ['required', Rule::unique('chat_models')->ignore($this->route('id'))->whereNull('deleted_at')],
            'provider'    => [
                Rule::requiredIf(fn () => ! $this->isYihaotongRequest()),
                'nullable',
                Rule::in(AiModelPresetEnum::drivers()),
            ],
            'config_type' => [
                'nullable',
                Rule::in([
                    AiModelEnum::CONFIG_PRESET,
                    AiModelEnum::CONFIG_CUSTOM,
                    AiModelEnum::CONFIG_YIHAOTONG,
                ]),
            ],
            'model_id'    => ['required', 'string', 'max:100'],
            'model_type'  => [
                'required',
                Rule::in([AiModelEnum::MODEL_TYPE_CHAT, AiModelEnum::MODEL_TYPE_EMBEDDING]),
            ],
            'id'          => ['nullable', 'integer', 'min:1'],
            'api_key'     => [
                Rule::excludeIf(fn () => $this->isYihaotongRequest()),
                // 有模型 id（路由或 body）时可留空，由服务端复用库内密钥；新建仍必填
                Rule::requiredIf(fn () => ! $this->allowsEmptyApiKey()),
                'nullable',
                'string',
                'max:255',
            ],
            'is_full_url' => ['nullable', Rule::in([AiModelEnum::FULL_URL_OFF, AiModelEnum::FULL_URL_ON])],
            'base_url'    => [
                Rule::excludeIf(fn () => $this->isYihaotongRequest()),
                Rule::requiredIf(fn () => $this->requiresClientBaseUrl()),
                'nullable',
                'max:255',
                Rule::when(filled(request('base_url')), ['url']),
            ],
            'enable_thinking' => [
                'nullable',
                Rule::in([
                    AiModelEnum::THINKING_OFF,
                    AiModelEnum::THINKING_ON,
                    AiModelEnum::THINKING_DEFAULT,
                ]),
            ],
            'json'        => ['nullable', 'array'],
            'json.' . AiModelEnum::ADVANCED_INPUT_TOKENS    => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'json.' . AiModelEnum::ADVANCED_OUTPUT_TOKENS   => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'json.' . AiModelEnum::ADVANCED_MAX_TOOL_ROUNDS => ['nullable', 'integer', 'min:1', 'max:10000'],
            'json.' . AiModelEnum::ADVANCED_TEMPERATURE     => ['nullable', 'numeric', 'min:0', 'max:2'],
            'json.' . AiModelEnum::ADVANCED_TOP_P           => ['nullable', 'numeric', 'min:0', 'max:1'],
            'json.' . AiModelEnum::ADVANCED_TOP_K           => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * 当前请求是否为一号通配置.
     */
    private function isYihaotongRequest(): bool
    {
        return (int) request('config_type') === AiModelEnum::CONFIG_YIHAOTONG
            || request('provider') === AiModelEnum::DRIVER_YIHAOTONG;
    }

    /**
     * 是否允许 api_key 留空（由服务端按模型 id 复用已存密钥）.
     *
     * update 走路由 id；test/fetch 编辑态可在 body 传 id.
     */
    private function allowsEmptyApiKey(): bool
    {
        if ((int) request()->route('id') > 0) {
            return true;
        }

        return (int) request('id') > 0;
    }

    /**
     * 是否必须由客户端传入 base_url（自定义 / OpenAI 兼容 / 完整 URL）.
     *
     * 预设与一号通不需要传，由服务端按类型解析默认地址.
     */
    private function requiresClientBaseUrl(): bool
    {
        return (int) request('is_full_url') === AiModelEnum::FULL_URL_ON
            || (int) request('config_type') === AiModelEnum::CONFIG_CUSTOM
            || request('provider') === AiModelEnum::DRIVER_OPENAI_COMPATIBLE;
    }
}
