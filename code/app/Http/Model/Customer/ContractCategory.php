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

use App\Http\Model\Finance\BillCategory;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\PathAttrTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 客户订单分类
 * Class ClientContractCategory.
 */
class ContractCategory extends BaseModel
{
    use PathAttrTrait;

    /**
     * @var string
     */
    protected $table = 'client_contract_category';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'pid'          => 'integer',
        'level'        => 'integer',
        'entid'        => 'integer',
        'bill_cate_id' => 'integer',
        'sort'         => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * path修改器.
     * @param mixed $value
     */
    public function setBillCatePathAttribute($value)
    {
        $this->attributes['bill_cate_path'] = $value ? implode('/', $value) : '';
    }

    /**
     * path获取器.
     * @param mixed $value
     * @return false|string[]
     */
    public function getBillCatePathAttribute($value)
    {
        return $value ? array_map('intval', explode('/', $value)) : [];
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotid($query, $value)
    {
        if ($value) {
            $query->where('id', '<>', $value);
        }
    }

    /**
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if ($value) {
            return $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * bill_cate_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeBillCateId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('bill_cate_id', $value);
        } elseif ($value) {
            $query->where('bill_cate_id', $value);
        }
    }

    /**
     * 账目分类.
     * @return HasOne
     */
    public function billCategory()
    {
        return $this->hasOne(BillCategory::class, 'id', 'bill_cate_id')->select(['id', 'name']);
    }

    /**
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNames($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('name', $value);
        }

        if ($value) {
            return $query->where('name', $value);
        }
    }

    /**
     * path作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopePath($query, $value)
    {
        if (! is_array($value) && $value !== '') {
            $query->where('path', 'like', "%/{$value}/%");
        }
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
    }

    public function scopeLtLevel($query, $value)
    {
        $query->where('level', '<', $value);
    }
}
