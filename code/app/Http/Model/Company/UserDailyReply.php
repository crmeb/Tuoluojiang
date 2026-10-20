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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 用户日报回复记录
 * Class UserDaily.
 */
class UserDailyReply extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise_user_daily_reply';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'daily_id';

    protected $casts = [
        'id'         => 'integer',
        'pid'        => 'integer',
        'daily_id'   => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 用户名片关联.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'uid', 'uid');
    }

    /**
     * @return HasOne
     */
    public function paentUser()
    {
        return $this->hasOne(self::class, 'pid', 'id');
    }

    /**
     * id作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeIds($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('id', $value);
        }
        return $query->where('id', $value);
    }
}
