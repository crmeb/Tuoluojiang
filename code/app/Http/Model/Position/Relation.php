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

namespace App\Http\Model\Position;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 职级类型
 * Class Relation.
 */
class Relation extends BaseModel
{
    /**
     * 自动写入时间关闭.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'rank_relation';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'       => 'integer',
        'value'       => 'integer',
        'entid'    => 'integer',
        'level_id' => 'integer',
        'cate_id'  => 'integer',
        'rank_id'  => 'integer',
        'number'   => 'integer',
        'status'   => 'integer',
    ];

    /**
     * 一对一关联职级.
     * @return HasOne
     */
    public function rank()
    {
        return $this->hasOne(Position::class, 'id', 'rank_id');
    }

    /**
     * 一对多关联职位.
     * @return HasMany
     */
    public function job()
    {
        return $this->hasMany(Job::class, 'rank_id', 'rank_id');
    }

    /**
     * level_id作用域
     */
    public function scopeLevelId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('level_id', $value);
        } elseif ($value !== '') {
            $query->where('level_id', $value);
        }
    }

    /**
     * cate_id作用域
     */
    public function scopeCateId($query, $value)
    {
        if ($value !== '') {
            $query->where('cate_id', $value);
        }
    }

    /**
     * rank_id作用域
     */
    public function scopeRankId($query, $value)
    {
        if ($value !== '') {
            $query->where('rank_id', $value);
        }
    }

    /**
     * ent_id作用域
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            $query->where('entid', $value);
        }
    }
}
