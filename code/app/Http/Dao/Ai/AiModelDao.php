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

namespace App\Http\Dao\Ai;

use App\Http\Model\Ai\AiModel;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * AI 模型配置数据访问层.
 *
 * 绑定模型 AiModel（chat_models）.
 * 扩展 search：名称模糊、provider/config_type/status/model_type 等标准列表筛选.
 */
class AiModelDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索模型配置.
     * @param mixed $where
     */
    public function search($where, ?bool $authWhere = null)
    {
        if (! is_array($where)) {
            return parent::search($where, $authWhere);
        }

        $name = $where['name'] ?? null;
        unset($where['name']);

        return parent::search($where, $authWhere)
            ->when($name !== null && $name !== '', fn ($query) => $query->where('name', 'like', '%' . $name . '%'));
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AiModel::class;
    }
}
