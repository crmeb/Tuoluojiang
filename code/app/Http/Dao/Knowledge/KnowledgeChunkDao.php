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

use App\Http\Model\Knowledge\KnowledgeChunk;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 知识库分块 Dao.
 */
class KnowledgeChunkDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索知识库分块.
     * @param mixed $where
     */
    public function search($where, ?bool $authWhere = null)
    {
        if (! is_array($where)) {
            return parent::search($where, $authWhere);
        }

        $content = $where['content'] ?? null;
        unset($where['content']);

        return parent::search($where, $authWhere)
            ->when($content !== null && $content !== '', fn ($query) => $query->where('content', 'like', '%' . $content . '%'));
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return KnowledgeChunk::class;
    }
}
