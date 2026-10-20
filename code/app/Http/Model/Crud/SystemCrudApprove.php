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

namespace App\Http\Model\Crud;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * Class SystemCrudApprove.
 * @email 136327134@qq.com
 * @date 2024/2/28
 */
class SystemCrudApprove extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_approve';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'crud_id'    => 'integer',
        'user_id'    => 'integer',
        'status'     => 'integer',
        'types'      => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * @email 136327134@qq.com
     * @date 2024/4/16
     * @return HasOne
     */
    public function crud()
    {
        return $this->hasOne(SystemCrud::class, 'id', 'crud_id');
    }

    /**
     * 一对一关联用户名片.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * @return HasOne
     */
    public function process()
    {
        return $this->hasOne(SystemCrudApproveProcess::class, 'approve_id', 'id')->where('is_initial', 1);
    }

    /**
     * 一对多关联规则配置.
     * @return HasOne
     */
    public function rule()
    {
        return $this->hasOne(SystemCrudApproveRule::class, 'approve_id', 'id');
    }

    /**
     * id作用域
     * @param Builder $query
     */
    public function scopeId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * id作用域
     * @param Builder $query
     */
    public function scopeNotid($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        }
    }

    /**
     * status作用域
     * @param Builder $query
     */
    public function scopeStatus($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 名称模糊查询.
     * @param Builder $query
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    public function scopeCateId($query, $value)
    {
        if ($value !== '') {
            $query->whereIn('id', function ($query) use ($value) {
                $query->from('system_crud_event')
                    ->whereIn('crud_id', fn ($q) => $q->from('system_crud')->where('cate_ids', 'like', '%/' . $value . '/%')
                        ->select('id'))->select('crud_approve_id');
            });
        }
    }
}
