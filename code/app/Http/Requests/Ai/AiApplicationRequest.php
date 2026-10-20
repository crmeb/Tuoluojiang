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

use App\Constants\Ai\AiApplicationPromptEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Constants\Ai\AiModelEnum;
use App\Http\Requests\ApiValidate;
use Illuminate\Validation\Rule;

/**
 * AI 应用表单校验.
 *
 * 场景 store/update：新建与编辑应用，校验名称、模型、插件类型、应用类型与权限成员.
 * 场景 quick：快速新增基础应用，校验名称与可选应用类型；简介与模型可选.
 * 场景 generate_prompt：按类型生成提示内容，校验 models_id、content、type.
 * 场景 knowledge：绑定知识库，校验 knowledge_ids 数组.
 * 字段：name 应用名称、info 应用简介、models_id 模型 ID、
 * enable_thinking 是否启用模型深度思考、
 * content/type 仅 generate_prompt 必填（新增编辑可空）、
 * source_types 可绑定插件（MCP/知识库，不含数据库）、app_type 应用类型、
 * auth_ids 可使用成员（内部应用必填）、edit_ids 编辑权限、knowledge_ids 知识库 ID 列表.
 */
class AiApplicationRequest extends ApiValidate
{
    protected $scene = [
        'store'           => ['name', 'models_id', 'enable_thinking', 'source_type', 'source_types', 'app_type', 'auth_ids', 'edit_ids', 'knowledge_ids'],
        'update'          => ['name', 'models_id', 'enable_thinking', 'source_type', 'source_types', 'app_type', 'auth_ids', 'edit_ids', 'knowledge_ids'],
        'quick'           => ['name', 'info', 'models_id', 'app_type'],
        'generate_prompt' => ['models_id', 'content', 'type'],
        'knowledge'       => ['knowledge_ids'],
    ];

    public function message(): array
    {
        return [
            'name.required'              => '请填写应用名称',
            'name.max'                   => '应用名称不能超过100个字符',
            'info.max'                   => '应用简介不能超过500个字符',
            'models_id.required'         => '请选择AI模型',
            'models_id.integer'          => 'AI模型参数错误',
            'models_id.min'              => '请选择AI模型',
            'enable_thinking.in'         => '深度思考开关取值错误',
            'content.required'           => '请填写生成上下文内容',
            'content.max'                => '生成上下文内容过长',
            'type.required'              => '请选择生成类型',
            'type.in'                    => '生成类型错误',
            'source_type.array'          => '应用插件类型错误',
            'source_type.*.in'           => '应用插件类型错误',
            'source_types.array'         => '应用插件类型错误',
            'source_types.*.in'          => '应用插件类型错误',
            'app_type.in'                => '应用类型错误',
            'auth_ids.required_unless'   => '内部应用请选择可使用成员',
            'auth_ids.array'             => '可使用成员参数错误',
            'edit_ids.array'             => '编辑权限参数错误',
            'knowledge_ids.array'        => '知识库绑定参数错误',
        ];
    }

    protected function rules(): array
    {
        $modelsIdRequired   = $this->currentScene !== 'quick';
        // ResourceControllerTrait 的 store/update 不挂场景，会校验全部 rules；
        // content/type 仅 generate_prompt 必填，避免空 content 误拦新增/编辑.
        $isGeneratePrompt   = $this->currentScene === 'generate_prompt';

        return [
            'name'            => ['required', 'string', 'max:100'],
            'info'            => ['nullable', 'string', 'max:500'],
            'models_id'       => $modelsIdRequired
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],
            'enable_thinking' => ['nullable', Rule::in([AiModelEnum::THINKING_OFF, AiModelEnum::THINKING_ON])],
            'content'         => $isGeneratePrompt
                ? ['required', 'string', 'max:10000']
                : ['nullable', 'string', 'max:10000'],
            'type'            => $isGeneratePrompt
                ? ['required', 'string', Rule::in(AiApplicationPromptEnum::TYPES)]
                : ['nullable', 'string', Rule::in(AiApplicationPromptEnum::TYPES)],
            'source_type'     => ['nullable', 'array'],
            'source_type.*'   => ['integer', Rule::in(AiConversationEnum::BINDABLE_SOURCE_TYPES)],
            'source_types'    => ['nullable', 'array'],
            'source_types.*'  => ['integer', Rule::in(AiConversationEnum::BINDABLE_SOURCE_TYPES)],
            'app_type'        => ['nullable', Rule::in(AiConversationEnum::APP_TYPES)],
            'auth_ids'        => ['required_unless:app_type,' . AiConversationEnum::APP_TYPE_EXTERNAL, 'array'],
            'auth_ids.*'      => ['integer', 'min:1'],
            'edit_ids'        => ['nullable', 'array'],
            'edit_ids.*'      => ['integer', 'min:1'],
            'knowledge_ids'   => ['nullable', 'array'],
            'knowledge_ids.*' => ['integer', 'min:1'],
        ];
    }
}
