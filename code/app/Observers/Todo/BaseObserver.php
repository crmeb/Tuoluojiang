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

use App\Events\BusinessDataChangeEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 待办同步观察者基类
 * 所有业务模型的待办观察者应继承此类.
 */
abstract class BaseObserver
{
    /**
     * @var array<string, array<int, int>>
     */
    protected array $deletingUserIds = [];

    /**
     * 待办类型
     */
    abstract protected function getType(): string;

    /**
     * 获取资源ID
     */
    abstract protected function getSourceId(Model $model): int;

    /**
     * 创建后
     */
    public function created(Model $model): void
    {
        $this->dispatch($model, 'create');
    }

    /**
     * 更新后
     */
    public function updated(Model $model): void
    {
        $userIds = $this->getUserIdsBeforeUpdate($model);
        $this->dispatch($model, 'update', $userIds);
    }

    /**
     * 删除前，提前获取参与者用户ID，因为删除后模型关联可能已不可查询.
     */
    public function deleting(Model $model): void
    {
        $this->deletingUserIds[$this->getDeleteCacheKey($model)] = $this->getUserIdsBeforeDelete($model);
    }

    /**
     * 删除后
     */
    public function deleted(Model $model): void
    {
        $cacheKey = $this->getDeleteCacheKey($model);
        $userIds  = $this->deletingUserIds[$cacheKey] ?? $this->getUserIdsBeforeDelete($model);
        unset($this->deletingUserIds[$cacheKey]);
        $this->dispatch($model, 'delete', $userIds);
    }

    /**
     * 在模型删除前获取关联的用户ID列表
     * 子类可覆盖以提供特定业务的用户ID获取逻辑
     * @return array<int, int>
     */
    protected function getUserIdsBeforeDelete(Model $model): array
    {
        return [];
    }

    /**
     * 在模型更新前后的归属人可能变化，子类可返回更新前的用户ID用于清理旧待办.
     * @return array<int, int>
     */
    protected function getUserIdsBeforeUpdate(Model $model): array
    {
        return [];
    }

    /**
     * 派发事件
     */
    protected function dispatch(Model $model, string $action, array $userIds = []): void
    {
        try {
            BusinessDataChangeEvent::dispatch($this->getType(), $this->getSourceId($model), $action, $userIds);
        } catch (\Throwable $e) {
            // 静默失败，避免影响业务
        }
    }

    protected function getDeleteCacheKey(Model $model): string
    {
        return $model::class . ':' . $this->getSourceId($model);
    }
}
