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

use App\Constants\TodoEnum;
use App\Http\Model\Program\ProgramTask;
use App\Http\Service\Program\ProgramTaskMemberService;
use Illuminate\Database\Eloquent\Model;

/**
 * 项目任务待办观察者
 */
class ProgramTaskObserver extends BaseObserver
{
    protected function getType(): string
    {
        return TodoEnum::TYPE_TASK;
    }

    protected function getSourceId(Model $model): int
    {
        return (int) $model->id;
    }

    protected function getUserIdsBeforeDelete(Model $model): array
    {
        if (! $model instanceof ProgramTask) {
            return [];
        }
        return $this->getTaskUserIds((int) $model->id, (int) $model->uid);
    }

    protected function getUserIdsBeforeUpdate(Model $model): array
    {
        if (! $model instanceof ProgramTask) {
            return [];
        }
        return $this->getTaskUserIds((int) $model->id, (int) $model->getOriginal('uid'));
    }

    /**
     * @return array<int, int>
     */
    private function getTaskUserIds(int $taskId, int $uid): array
    {
        $members = app()->get(ProgramTaskMemberService::class)->column(['task_id' => $taskId], 'uid') ?: [];
        return array_values(array_unique(array_filter(array_merge([$uid], $members))));
    }
}
