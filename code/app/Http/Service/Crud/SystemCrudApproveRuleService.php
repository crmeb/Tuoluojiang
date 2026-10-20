<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudApproveRuleDao;
use App\Http\Service\BaseService;

class SystemCrudApproveRuleService extends BaseService
{
    public function __construct(SystemCrudApproveRuleDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 处理规则配置.
     */
    public function checkRuleConfig($data, $userId, $type = 'ruleConfig'): array
    {
        return [
            'abnormal' => $data[$type]['abnormal'] ?: 0,
            'auto'     => $data[$type]['auto'] !== '' ? $data[$type]['auto'] : 2,
            'edit'     => $data[$type]['edit'] ?? '',
            'recall'   => $data[$type]['recall'],
            'refuse'   => $data[$type]['refuse'] ?? 0,
            'user_id'  => $userId,
            'is_transfer'  => $data[$type]['is_transfer'] ?? 1,
            'is_sign'  => $data[$type]['is_sign'] ?? 0,
        ];
    }
}
