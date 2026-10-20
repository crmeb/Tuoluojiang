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

namespace App\Http\Dao\WorkExternalContact;

use App\Http\Model\WorkExternalContact\WorkChannelCode;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Database\Eloquent\Builder;

class WorkChannelCodeDao extends BaseDao
{
    use ListSearchTrait;

    public function search($where, ?bool $authWhere = null)
    {
        $autoCreateRoom = is_array($where) ? ($where['auto_create_room'] ?? '') : '';
        if (is_array($where)) {
            unset($where['auto_create_room']);
        }

        return parent::search($where, $authWhere)
            ->when($autoCreateRoom !== '', function (Builder $query) use ($autoCreateRoom) {
                $query->whereHas('groups', function (Builder $query) use ($autoCreateRoom) {
                    $query->where('auto_create_room', (int) $autoCreateRoom);
                });
            });
    }

    protected function setModel()
    {
        return WorkChannelCode::class;
    }
}
