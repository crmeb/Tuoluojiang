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

use App\Http\Model\Knowledge\KnowledgeDocument;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 知识库文档 Dao.
 */
class KnowledgeDocumentDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索知识库文档.
     * @param mixed $where
     */
    public function search($where, ?bool $authWhere = null)
    {
        if (! is_array($where)) {
            return parent::search($where, $authWhere);
        }

        $title = $where['title'] ?? null;
        unset($where['title']);

        return parent::search($where, $authWhere)
            ->when($title !== null && $title !== '', fn ($query) => $query->where('title', 'like', '%' . $title . '%'));
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return KnowledgeDocument::class;
    }
}
