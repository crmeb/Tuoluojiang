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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 考核评分记录
 * Class UserAssess.
 */
class UserAssessScore extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess_user_score';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'entid'      => 'integer',
        'assessid'   => 'integer',
        'userid'     => 'integer',
        'check_uid'  => 'integer',
        'test_uid'   => 'integer',
        'score'      => 'decimal:2',
        'total'      => 'decimal:2',
        'grade'      => 'integer',
        'types'      => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getInfoAttribute($val)
    {
        return $val ? json_decode($val, true) : [];
    }

    /**
     * 一对多远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'userid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 一对多远程关联用户.
     * @return HasOne
     */
    public function check()
    {
        return $this->hasOne(Admin::class, 'id', 'check_uid')->select(['id', 'name', 'avatar', 'phone']);
    }

    /**
     * 一对多远程关联用户.
     * @return HasOne
     */
    public function test()
    {
        return $this->hasOne(Admin::class, 'id', 'test_uid')->select(['id', 'name', 'avatar', 'phone']);
    }

    /**
     * @return mixed
     */
    public function scopeUserid($query, $value)
    {
        return $query->where('userid', $value);
    }

    /**
     * @return mixed
     */
    public function scopeTypes($query, $value)
    {
        return $query->where('types', $value);
    }

    /**
     * @return mixed
     */
    public function scopeAssessid($query, $value)
    {
        return $query->where('assessid', $value);
    }
}
