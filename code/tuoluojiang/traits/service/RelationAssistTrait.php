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

namespace Tuoluojiang\Open\traits\service;

use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 关联辅助
 * Trait RelationAssistTrait.
 * @mixin BaseService
 */
trait RelationAssistTrait
{
    /**
     * 创建关联.
     * @param mixed $relationIds
     * @return mixed
     */
    public function createRelation($relationIds, array $passiveIds, array $other = [])
    {
        $data = [];
        if (! is_array($relationIds)) {
            $relationIds = [$relationIds];
        }
        $otherKey   = array_keys($other);
        $otherValue = array_values($other);
        foreach ($relationIds as $relationId) {
            foreach ($passiveIds as $passiveId) {
                $key    = array_merge($this->tableFeildArr(), $otherKey);
                $value  = array_merge([$relationId, $passiveId], $otherValue);
                $data[] = array_combine($key, $value);
            }
        }

        return $this->dao->insert($data);
    }

    /**
     * 删除关联.
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteRelation(int $relationId, ?string $field = null)
    {
        return $this->dao->delete([$field ?: $this->deleteFeildStr() => $relationId]);
    }

    /**
     * 关联删除字段.
     */
    abstract protected function deleteFeildStr(): string;

    /**
     * 字段.
     */
    abstract protected function tableFeildArr(): array;
}
