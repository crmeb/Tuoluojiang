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

namespace App\Http\Model\Assess;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Cloud\File;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 企业绩效考核方案
 * Class AssessScheme.
 */
class AssessScheme extends BaseModel
{
    use SoftDeletes;

    /**
     * 伪删除字段.
     */
    public const DELETED_AT = 'delete';

    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess_scheme';

    /**
     * 设置主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'                    => 'integer',
        'entid'                 => 'integer',
        'period'                => 'integer',
        'create_month'          => 'integer',
        'create_day'            => 'integer',
        'own_appraise_month'    => 'integer',
        'own_appraise_day'      => 'integer',
        'leader_appraise_month' => 'integer',
        'leader_appraise_day'   => 'integer',
        'user_id'               => 'integer',
        'user_count'            => 'integer',
        'status'                => 'integer',
        'delete'                => 'datetime:Y-m-d H:i:s',
        'created_at'            => 'datetime:Y-m-d H:i:s',
        'updated_at'            => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * other修改器.
     */
    public function setOtherAttribute($value)
    {
        $this->attributes['other'] = $value && is_array($value) ? json_encode($value) : $value;
    }

    /**
     * other获取器.
     * @return false|string[]
     */
    public function getOtherAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * 一对多关联用户.
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(AssessUser::class, 'scheme_id', 'id');
    }

    /**
     * 一对一关联用户.
     * @return HasOne
     */
    public function userEnt()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->with(['card' => function ($query) {
            $query->select(['id', 'name']);
        }])->select(['id', 'uid']);
    }

    /**
     * 一对一关联文件.
     * @return HasOne
     */
    public function file()
    {
        return $this->hasOne(File::class, 'file_id', 'file_id')->where('is_master', 1);
    }

    /**
     * name作用域
     * @param Builder $query
     */
    public function scopeNameLike($query, $value)
    {
        if ($value != '') {
            return $query->where('name', 'like', '%' . $value . '%');
        }
    }
}
