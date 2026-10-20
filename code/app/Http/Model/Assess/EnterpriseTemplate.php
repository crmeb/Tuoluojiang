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
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * Class EnterpriseTemplate.
 */
class EnterpriseTemplate extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise_template';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'entid'      => 'integer',
        'user_id'    => 'integer',
        'cate_id'    => 'integer',
        'status'     => 'integer',
        'types'      => 'integer',
        'way'        => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @return HasOne
     */
    public function cate()
    {
        return $this->hasOne(AssessTargetCategory::class, 'id', 'cate_id');
    }

    /**
     * @return HasOne
     */
    public function collect()
    {
        return $this->hasOne(TemplateCollect::class, 'temp_id', 'id');
    }

    /**
     * 一对多远程关联用户.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * id作用域
     */
    public function scopeId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', $value);
        }
    }

    /**
     * cate_id作用域
     */
    public function scopeCateId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('cate_id', $value);
        } elseif ($value !== '') {
            $query->where('cate_id', $value);
        }
    }

    /**
     * name作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        if ($value !== '') {
            return $query->where('name', 'like', '%' . $value . '%')->orWhere('info', 'LIKE', '%' . $value . '%');
        }
    }

    /**
     * types作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeTypes($query, $value)
    {
        if ($value !== '') {
            return $query->where('types', $value);
        }
    }
}
