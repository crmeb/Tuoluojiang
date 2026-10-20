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
use App\Http\Model\Frame\Frame;
use App\Http\Model\Position\Job;
use Carbon\Carbon;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 职位变动表
 * Class UserChange.
 */
class UserChange extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise_user_change';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'uid'          => 'integer',
        'entid'        => 'integer',
        'card_id'      => 'integer',
        'types'        => 'integer',
        'date'         => 'date:Y-m-d',
        'new_frame'    => 'integer',
        'old_frame'    => 'integer',
        'new_position' => 'integer',
        'old_position' => 'integer',
        'link_id'      => 'integer',
        'user_id'      => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @return HasOne
     */
    public function oFrame()
    {
        return $this->hasOne(Frame::class, 'id', 'old_frame')->select(['id', 'name']);
    }

    /**
     * @return HasOne
     */
    public function nFrame()
    {
        return $this->hasOne(Frame::class, 'id', 'new_frame')->select(['id', 'name']);
    }

    /**
     * @return HasOne
     */
    public function oPosition()
    {
        return $this->hasOne(Job::class, 'id', 'old_position')->select(['id', 'name']);
    }

    /**
     * @return HasOne
     */
    public function nPosition()
    {
        return $this->hasOne(Job::class, 'id', 'new_position')->select(['id', 'name']);
    }

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::make($value)->toDateString() : '';
    }
}
