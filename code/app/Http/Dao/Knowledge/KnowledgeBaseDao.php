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

namespace App\Http\Dao\Knowledge;

use App\Http\Model\Knowledge\KnowledgeBase;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 知识库 Dao.
 */
class KnowledgeBaseDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索知识库.
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
        return KnowledgeBase::class;
    }
}
