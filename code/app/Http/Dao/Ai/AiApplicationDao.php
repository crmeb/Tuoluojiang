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

use App\Constants\Ai\AiConversationEnum;
use App\Http\Model\Ai\AiApplication;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * AI 应用数据访问层.
 *
 * 绑定模型 AiApplication（chat_applications）.
 * 扩展 search：名称模糊、创建人 uids、accessible_uid 可见范围、source_type 插件类型、
 * knowledge_id 绑定知识库组合查询；
 * status（发布/未发布）、app_type（内/外部）由父类等值条件处理.
 */
class AiApplicationDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索AI应用.
     *
     * where.accessible_uid：仅返回该用户创建 / 可使用（auth_ids）/ 可编辑（edit_ids）的应用；
     * 已发布外部应用对全体登录成员视为可使用.
     * where.knowledge_id：仅返回 knowledge_ids 中包含该知识库的应用（知识库详情的关联应用页签）.
     *
     * @param mixed $where
     */
    public function search($where, ?bool $authWhere = null)
    {
        if (! is_array($where)) {
            return parent::search($where, $authWhere);
        }

        $name          = $where['name'] ?? null;
        $uids          = $where['uids'] ?? null;
        $sourceType    = (int) ($where['source_type'] ?? 0);
        $accessibleUid = $where['accessible_uid'] ?? null;
        $knowledgeId   = (int) ($where['knowledge_id'] ?? 0);
        unset($where['name'], $where['uids'], $where['source_type'], $where['accessible_uid'], $where['knowledge_id']);

        return parent::search($where, $authWhere)
            ->when($name !== null && $name !== '', fn ($query) => $query->where('name', 'like', '%' . $name . '%'))
            ->when(is_array($uids) && $uids, fn ($query) => $query->whereIn('uid', $uids))
            ->when($accessibleUid !== null && $accessibleUid !== '', function ($query) use ($accessibleUid) {
                $uid = (int) $accessibleUid;
                $query->where(function ($query) use ($uid) {
                    $query->where('uid', $uid)
                        ->orWhereJsonContains('auth_ids', $uid)
                        ->orWhereJsonContains('edit_ids', $uid)
                        ->orWhere(function ($query) {
                            $query->where('app_type', AiConversationEnum::APP_TYPE_EXTERNAL)
                                ->where('status', AiConversationEnum::STATUS_ENABLED);
                        });
                });
            })
            ->when($knowledgeId > 0, fn ($query) => $query->whereJsonContains('knowledge_ids', $knowledgeId))
            ->when($sourceType && AiConversationEnum::hasSource($sourceType, AiConversationEnum::SOURCE_SQL), fn ($query) => $query->whereJsonContains('source_type', AiConversationEnum::SOURCE_SQL))
            ->when($sourceType && AiConversationEnum::hasSource($sourceType, AiConversationEnum::SOURCE_MCP), fn ($query) => $query->whereJsonContains('source_type', AiConversationEnum::SOURCE_MCP))
            ->when(
                $sourceType && AiConversationEnum::hasSource($sourceType, AiConversationEnum::SOURCE_KNOWLEDGE),
                fn ($query) => $query->where(function ($query) {
                    $query->whereJsonContains('source_type', AiConversationEnum::SOURCE_KNOWLEDGE)
                        ->orWhereNotNull('knowledge_ids')
                        ->whereRaw('JSON_LENGTH(knowledge_ids) > 0');
                })
            );
    }

    protected function setModel(): string
    {
        return AiApplication::class;
    }
}
