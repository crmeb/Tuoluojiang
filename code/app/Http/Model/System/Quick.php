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

namespace App\Http\Model\System;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 菜单.
 */
class Quick extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_quick';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'       => 'integer',
        'cid'        => 'integer',
        'sort'       => 'integer',
        'types'      => 'integer',
        'pc_show'    => 'integer',
        'uni_show'   => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联查询分类.
     * @return HasOne
     */
    public function cate()
    {
        return $this->hasOne(Category::class, 'id', 'cid');
    }

    public function scopeNotId($query, $value)
    {
        if ($value !== '') {
            $query->where('id', '<>', $value);
        }
    }

    public function scopeCid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('cid', $value);
        } elseif ($value !== '') {
            $query->where('cid', $value);
        }
    }

    public function scopeNameLike($query, $value)
    {
        if ($value !== '') {
            $query->where('name', 'LIKE', "%{$value}%");
        }
    }
}
