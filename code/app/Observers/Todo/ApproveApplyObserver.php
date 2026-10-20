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

namespace App\Observers\Todo;

use App\Constants\ApproveEnum;
use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use App\Http\Model\Approve\ApproveApply;
use App\Http\Model\Approve\ApproveUser;
use Illuminate\Database\Eloquent\Model;

/**
 * 审批待办观察者
 */
class ApproveApplyObserver extends BaseObserver
{
    protected function getType(): string
    {
        return TodoEnum::TYPE_APPROVE_SUBMIT;
    }

    protected function getSourceId(Model $model): int
    {
        return (int) $model->id;
    }

    protected function getUserIdsBeforeDelete(Model $model): array
    {
        if (! $model instanceof ApproveApply) {
            return [];
        }
        $userIds      = [$model->user_id];
        $verifierIds  = ApproveUser::where('apply_id', $model->id)->pluck('user_id')->toArray();
        return array_unique(array_filter(array_merge($userIds, $verifierIds)));
    }

    protected function getUserIdsBeforeUpdate(Model $model): array
    {
        if (! $model instanceof ApproveApply) {
            return [];
        }
        $userIds      = [$model->getOriginal('user_id'), $model->user_id];
        $verifierIds  = ApproveUser::where('apply_id', $model->id)->pluck('user_id')->toArray();
        return array_values(array_unique(array_filter(array_merge($userIds, $verifierIds))));
    }

    protected function dispatch(Model $model, string $action, array $userIds = []): void
    {
        try {
            // 终态申请（通过/拒绝/撤回）统一 complete，避免 update/create 把待办重新拉回未完成
            if ($action !== 'delete' && $model instanceof ApproveApply
                && (int) $model->status !== ApproveEnum::APPROVE_PENDING) {
                $action = 'complete';
            }
            foreach ([TodoEnum::TYPE_APPROVE_SUBMIT, TodoEnum::TYPE_APPROVE_PENDING] as $type) {
                BusinessDataChangeEvent::dispatch($type, $this->getSourceId($model), $action, $userIds);
            }
        } catch (\Throwable) {
            // 静默失败，避免影响业务
        }
    }
}
