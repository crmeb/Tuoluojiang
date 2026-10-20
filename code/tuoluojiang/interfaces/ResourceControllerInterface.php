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
 * Resource Controller 接口
 * Interface ResourceController.
 */
interface ResourceControllerInterface
{
    /**
     * 展示数据.
     * @return mixed
     */
    public function index();

    /**
     * 创建数据.
     * @return mixed
     */
    public function create();

    /**
     * 添加.
     * @return mixed
     */
    public function store();

    /**
     * 隐藏展示.
     * @param mixed $id
     * @return mixed
     */
    public function show($id);

    /**
     * 获取修改数据.
     * @param mixed $id
     * @return mixed
     */
    public function edit($id);

    /**
     * 修改数据.
     * @param mixed $id
     * @return mixed
     */
    public function update($id);

    /**
     * 删除数据.
     * @param mixed $id
     * @return mixed
     */
    public function destroy($id);
}
