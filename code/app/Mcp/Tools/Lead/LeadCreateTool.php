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

namespace App\Mcp\Tools\Lead;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Service\Customer\LeadService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomFormFields;

class LeadCreateTool extends BaseTool
{
    use ResolvesCustomFormFields;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增线索，字段根据租户自定义表单动态生成，创建人固定为当前 MCP 用户';
    }

    public function getInputSchema(): array
    {
        return $this->buildCreateInputSchema(CustomEnum::CLUE, [
            'link_id' => ['type' => 'integer', 'description' => '关联 ID'],
        ]);
    }

    public function execute(array $arguments): array
    {
        $data = $this->buildCreatePayload($arguments, CustomEnum::CLUE);
        // saveClue 返回新建线索 ID（int），与 saveCustomer/saveOdds 返回模型不同.
        $id = (int) app(LeadService::class)->saveClue($data, $this->getUserDbId(), ViewSearchEnum::VIEW_CLUE);
        if ($id <= 0) {
            return ['error' => true, 'message' => '线索创建失败'];
        }

        return ['id' => $id, 'success' => true];
    }
}
