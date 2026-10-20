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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 客户文件列表
 * Class ClientFile.
 */
class File extends BaseModel
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'eid'        => 'integer',
        'cid'        => 'integer',
        'fid'        => 'integer',
        'vid'        => 'integer',
        'uid'        => 'integer',
        'entid'      => 'integer',
        'up_type'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'client_file';

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'card_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 附件路径.
     * @param mixed $value
     */
    public function getAttDirAttribute($value): string
    {
        return $value ? link_file($value) : '';
    }

    /**
     * 压缩路径.
     * @param mixed $value
     */
    public function getThumbDirAttribute($value): string
    {
        return $value ? link_file($value) : '';
    }

    /**
     * ID作用域
     * @param mixed $query
     * @param mixed $value
     * @return string
     */
    public function scopeId($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('id', $value);
        }
        if ($value !== '') {
            return $query->where('id', $value);
        }
        return null;
    }

    /**
     * 合同订单id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCid($query, $value)
    {
        return $value !== '' ? $query->where('cid', $value) : null;
    }

    /**
     * 客户id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEid($query, $value)
    {
        return $value !== '' ? $query->where('eid', $value) : null;
    }

    /**
     * 跟进记录id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeFid($query, $value)
    {
        return $value !== '' ? $query->where('fid', $value) : null;
    }

    /**
     * 分类企业id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEntidName($query, $value)
    {
        return $value !== '' ? $query->where('ent_id', $value) : null;
    }

    /**
     * 分类企业id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEntids($query, $value)
    {
        return $value !== '' ? $query->where('entid', $value)->orWhere('entid', 0) : null;
    }

    /**
     * 分类企业id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEntid($query, $value)
    {
        return $value !== '' ? $query->where('entid', $value) : null;
    }

    /**
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        if ($value !== '') {
            return $query->where(function ($query) use ($value) {
                $query->where('name', 'LIKE', '%' . $value . '%')->orWhere('real_name', 'LIKE', '%' . $value . '%');
            });
        }
    }
}
