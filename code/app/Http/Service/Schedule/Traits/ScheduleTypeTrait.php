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

namespace App\Http\Service\Schedule\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Tuoluojiang\Open\services\FormService as Form;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 日程类型 CRUD 相关方法.
 */
trait ScheduleTypeTrait
{
    /**
     * 获取日程类型列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function typeList(int $uid, array|string $field = ['*']): array
    {
        if (is_array($field)) {
            return $this->typeDao->select(['userid_like' => $uid], $field)?->toArray();
        }
        if (is_string($field)) {
            return $this->typeDao->column(['userid_like' => $uid], $field);
        }
        return [];
    }

    /**
     * 日程类型创建表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function typeCreateForm(): array
    {
        return $this->createElementForm('添加日程类型', $this->getTypeForm(collect()), '/ent/schedule/type/save');
    }

    /**
     * 保存日程类型.
     * @throws BindingResolutionException
     */
    public function saveType(int $uid, array $data): bool
    {
        $data['user_id']   = $uid;
        $data['entid']     = 1;
        $data['is_public'] = 1;
        if (! $data['name']) {
            throw $this->exception('请填写日程类型名称');
        }
        return (bool) $this->typeDao->create($data);
    }

    /**
     * 日程类型修改表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function typeEditForm(int $id, int $uid): array
    {
        $type = $this->typeDao->get(['id' => $id, 'user_id' => $uid])?->toArray();
        if (! $type) {
            throw $this->exception('修改的日程类型不存在');
        }
        return $this->createElementForm('修改日程类型', $this->getTypeForm(collect($type)), '/ent/schedule/type/update/' . $id, 'put');
    }

    /**
     * 修改日程类型.
     * @throws BindingResolutionException
     */
    public function updateType(int $id, int $uid, array $data): bool
    {
        $info = $this->typeDao->get(['id' => $id, 'user_id' => $uid])?->toArray();
        if (! $info) {
            throw $this->exception('修改的日程类型不存在');
        }
        $res = $this->transaction(function () use ($id, $data, $info) {
            if ($info['color'] != $data['color']) {
                $this->dao->update(['cid' => $id], ['color' => $data['color']]);
            }
            return (bool) $this->typeDao->update($id, $data);
        });
        return $res && $this->flushAllScheduleCache();
    }

    /**
     * 删除日程类型.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteType(int $id, int $uid): bool
    {
        if (! $this->typeDao->exists(['id' => $id, 'user_id' => $uid])) {
            throw $this->exception('未找到该日程类型');
        }
        return (bool) $this->typeDao->delete($id);
    }

    /**
     * 日程类型表单规则.
     */
    protected function getTypeForm(Collection $collection): array
    {
        return [
            Form::input('name', '类型名称', $collection->get('name'))->placeholder('请输入类型名称')->maxlength(20)->required(),
            Form::color('color', '颜色标识', $collection->get('color', '#1890FF'))->required(),
            Form::textarea('info', '类型描述', $collection->get('info', ''))->placeholder('请输入类型描述')
                ->maxlength(256)->rows(4),
        ];
    }
}
