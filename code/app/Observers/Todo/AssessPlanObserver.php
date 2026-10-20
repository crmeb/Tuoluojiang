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
use App\Events\BusinessDataChangeEvent;
use App\Http\Model\Company\UserAssess;
use App\Http\Service\Frame\FrameService;
use Illuminate\Database\Eloquent\Model;

/**
 * 绩效考核待办观察者
 */
class AssessPlanObserver extends BaseObserver
{
    protected function getType(): string
    {
        return TodoEnum::TYPE_ASSESS_SELF;
    }

    protected function getSourceId(Model $model): int
    {
        return (int) $model->id;
    }

    protected function getUserIdsBeforeDelete(Model $model): array
    {
        if (! $model instanceof UserAssess) {
            return [];
        }
        return $this->getAssessUserIds((int) $model->test_uid, (int) $model->check_uid);
    }

    protected function getUserIdsBeforeUpdate(Model $model): array
    {
        if (! $model instanceof UserAssess) {
            return [];
        }
        return $this->getAssessUserIds(
            (int) $model->getOriginal('test_uid'),
            (int) $model->getOriginal('check_uid')
        );
    }

    protected function dispatch(Model $model, string $action, array $userIds = []): void
    {
        try {
            foreach (TodoEnum::ASSESS_TYPES as $type) {
                BusinessDataChangeEvent::dispatch($type, $this->getSourceId($model), $action, $userIds);
            }
        } catch (\Throwable) {
            // 静默失败，避免影响业务
        }
    }

    /**
     * @return array<int, int>
     */
    private function getAssessUserIds(int $testUid, int $checkUid): array
    {
        $userIds = [$testUid, $checkUid];
        $verifyUid = app()->get(FrameService::class)->getLevelSuper($testUid, 2);
        if ($verifyUid) {
            $userIds[] = $verifyUid;
        }
        return array_values(array_unique(array_filter($userIds)));
    }
}
