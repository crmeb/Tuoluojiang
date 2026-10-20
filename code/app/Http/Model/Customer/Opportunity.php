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

namespace App\Http\Model\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Model\Customer\Traits\CustomFormCasts;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 商机.
 */
class Opportunity extends BaseModel
{
    use CustomFormCasts;
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'value'       => 'integer',
        'uid'         => 'integer',
        'before_uid'  => 'integer',
        'creator_uid' => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_odds';

    public function getEidAttribute($value)
    {
        return is_numeric($value) ? (int) $value : $value;
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopeEid($query, $value)
    {
        is_array($value) ? $query->whereIn('eid', $value) : $query->where('eid', $value);
    }

    public function product()
    {
        return $this->hasMany(ProductAssist::class, 'link_id', 'id')->where('link_type', CustomEnum::ODDS);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'eid');
    }

    public function follows()
    {
        return $this->hasMany(FollowUp::class, 'eid', 'id')->where('link_type', 'odds');
    }
}
