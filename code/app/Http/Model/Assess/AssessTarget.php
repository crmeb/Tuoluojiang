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

namespace App\Http\Model\Assess;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 指标模板
 * Class AssessTarget.
 */
class AssessTarget extends BaseModel
{
    use TimeDataTrait;
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess_target';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'spaceid'      => 'integer',
        'ratio'        => 'integer',
        'sort'         => 'integer',
        'finish_ratio' => 'integer',
        'max'          => 'integer',
        'score'        => 'integer',
        'deleted_at'   => 'datetime:Y-m-d H:i:s',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 一对一关联企业用户.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'uid', 'uid');
    }

    /**
     * 一对一关联模板类型.
     * @return HasOne
     */
    public function cate()
    {
        return $this->hasOne(AssessTargetCategory::class, 'id', 'cate_id');
    }

    /**
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        $query->where(function ($q) use ($value) {
            $q->where('name', 'like', '%' . $value . '%')->orWhere('content', 'LIKE', '%' . $value . '%');
        });
    }

    public function scopeSpaceid($query, $value)
    {
        is_array($value) ? $query->whereIn('spaceid', $value) : $query->where('spaceid', $value);
    }

    /**
     * id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }
}
