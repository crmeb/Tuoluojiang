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

namespace App\Http\Model\Company;

use App\Http\Model\User\User;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 企业邀请用户加入申请表.
 */
class CompanyApply extends BaseModel
{
    /**
     * 自动写入时间.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'user_enterprise_apply';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'entid'      => 'integer',
        'frame_id'   => 'integer',
        'status'     => 'integer',
        'verify'     => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * status作用域
     * @return mixed
     */
    public function scopeStatusApply($query, $value)
    {
        if ($value != '') {
            return $query->where('status', $value);
        }
    }

    /**
     * 用户.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->select(['uid', 'phone', 'real_name', 'avatar']);
    }

    /**
     * 企业.
     * @return HasOne
     */
    public function enterprise()
    {
        return $this->hasOne(Company::class, 'id', 'entid');
    }

    /**
     * entids作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeEntids($query, $value)
    {
        return $query->whereIn('entid', $value);
    }

    /**
     * verify作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeVerify($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('verify', $value);
        } elseif ($value !== '') {
            $query->where('verify', $value);
        }
    }

    /**
     * id作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeId($query, $value)
    {
        if ($value !== '') {
            return $query->where('id', $value);
        }
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
     * @param Builder $query
     * @return mixed
     */
    public function scopeStatus($query, $value)
    {
        return is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }
}
