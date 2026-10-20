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

namespace App\Http\Model\Config;

use App\Http\Model\BaseModel;
use Illuminate\Database\Query\Builder;

/**
 * 组合数据
 * Class Group.
 */
class Group extends BaseModel
{
    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_group';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'cate_id'    => 'integer',
        'entid'      => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 格式化fields字段.
     * @return false|string[]
     */
    public function setFieldsAttribute($value)
    {
        $this->attributes['fields'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 格式化fields字段.
     * @return false|string[]
     */
    public function getFieldsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * entid作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            return $query->where('entid', $value);
        }
    }

    /**
     * group_key作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeGroupKey($query, $value)
    {
        if ($value !== '') {
            return $query->where('group_key', $value);
        }
    }
}
