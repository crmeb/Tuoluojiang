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

namespace App\Http\Contract\Frame;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Model;

/**
 * 企业部门.
 */
interface FrameInterface
{
    public function getDepartmentTreeList(array $where, array $field = ['*'], null|array|string $sort = null, array $with = []): array;

    public function createDepartment(array $data): BaseModel|Model;

    public function getDepartmentInfo(array $where, array $field = ['*'], array $with = []): array;

    public function updateDepartment(array $where, array $data): void;

    public function deleteDepartment(int $id): bool;
}
