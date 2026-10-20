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

namespace Tuoluojiang\Open\interfaces;

/**
 * Resource Services 接口
 * Interface ResourceServicesInterface.
 */
interface ResourceServicesInterface
{
    /**
     * 获取列表数据.
     * @param array|string[] $field
     * @param null|array|string $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array;

    /**
     * 创建数据之前获取的数据.
     */
    public function resourceCreate(array $other = []): array;

    /**
     * 创建数据.
     * @return mixed
     */
    public function resourceSave(array $data);

    /**
     * 获取修改数据.
     */
    public function resourceEdit(int $id, array $other = []): array;

    /**
     * 修改数据.
     * @param int $id
     * @return mixed
     */
    public function resourceUpdate($id, array $data);

    /**
     * 删除数据.
     * @param mixed $id
     * @return mixed
     */
    public function resourceDelete($id, ?string $key = null);

    /**
     * 显示隐藏.
     * @param mixed $id
     * @return mixed
     */
    public function resourceShowUpdate($id, array $data);
}
