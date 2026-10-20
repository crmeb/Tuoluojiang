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

namespace App\Http\Model\User;

use App\Http\Model\Company\Company;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class UserCardPerfect.
 */
class UserCardPerfect extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'creator'    => 'integer',
        'user_id'    => 'integer',
        'entid'      => 'integer',
        'card_id'    => 'integer',
        'total'      => 'integer',
        'used'       => 'integer',
        'status'     => 'integer',
        'types'      => 'integer',
        'fail_time'  => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'user_card_perfect';

    public function enterprise(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'entid');
    }

    public function scopeStatus($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('status', $value);
        } elseif ($value !== '') {
            $query->where('status', $value);
        }
    }

    public function scopeTotal($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('total', $value);
        } elseif ($value !== '') {
            $query->where('total', $value);
        }
    }
}
