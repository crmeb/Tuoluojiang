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

namespace App\Mcp\Tools\Bill;

use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\CreatesBillPayment;

class ExpenseCreateTool extends BaseTool
{
    use CreatesBillPayment;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增支出（仅审批模式）：customer_biz_form_get → 本工具（form + process；自选节点时先 approve_process_get）';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['form'],
            'properties' => $this->billApprovalInputSchema(),
        ];
    }

    public function execute(array $arguments): array
    {
        return $this->executeBillCreate($arguments, 'contract_disburse_switch');
    }
}
