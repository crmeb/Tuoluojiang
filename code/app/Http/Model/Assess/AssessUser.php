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
use App\Http\Model\Admin\AdminInfo;
use App\Http\Model\Frame\FrameAssist;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 绩效考核人员关联
 * Class AssessUser.
 */
class AssessUser extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess_user';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'        => 'integer',
        'scheme_id' => 'integer',
        'user_id'   => 'integer',
    ];

    /**
     * 部门关联.
     * @return HasOne
     */
    public function frame()
    {
        return $this->hasOne(FrameAssist::class, 'user_id', 'user_id')->with(['framename'])->where('is_mastart', 1);
    }

    /**
     * @return HasOne
     */
    public function userent()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')
            ->where(['verify' => 1, 'status' => 1])
            ->select(['id', 'uid'])
            ->with([
                'card' => function ($query) {
                    $query->select(['id', 'name']);
                }, ]);
    }

    /**
     * 考核计划作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopePlanid($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('planid', $value);
        }
        if ($value !== '') {
            return $query->where('planid', $value);
        }
    }

    /**
     * 考核人作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopeCheckUid($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('check_uid', $value);
        }
        if ($value !== '') {
            return $query->where('check_uid', $value);
        }
    }
}
