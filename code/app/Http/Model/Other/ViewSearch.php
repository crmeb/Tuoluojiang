<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\Other;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;

/**
 * 视图搜索.
 */
class ViewSearch extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'view_search';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'uid'        => 'integer',
        'types'      => 'integer',
        'is_public'  => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getContentAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function scopeTitle($query, $value)
    {
        $query->where('title', 'like', '%' . $value . '%');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['name', 'id', 'uid', 'avatar']);
    }
}
