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

namespace App\Http\Model\Config;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 自定义表单内容.
 */
class FormData extends BaseModel
{
    use SoftDeletes;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $table = 'form_data';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'            => 'integer',
        'cate_id'       => 'integer',
        'decimal_place' => 'integer',
        'upload_type'   => 'integer',
        'required'      => 'integer',
        'max'           => 'integer',
        'min'           => 'integer',
        'uniqued'       => 'integer',
        'link_type'     => 'integer',
        'sort'          => 'integer',
        'status'        => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
        'deleted_at'    => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeId($query, $value): void
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * cate_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCateId($query, $value): void
    {
        is_array($value) ? $query->whereIn('cate_id', $value) : $query->where('cate_id', $value);
    }

    /**
     * key 作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeKey($query, $value): void
    {
        is_array($value) ? $query->whereIn('key', $value) : $query->where('key', $value);
    }

    public function dictData()
    {
        return $this->hasMany(DictData::class, 'type_name', 'dict_ident')->select([
            'name as label',
            'name',
            'value',
            'type_name',
            'pid',
        ]);
    }

    public function scopeLinkField($query, $value)
    {
        is_array($value) ? $query->whereIn('link_field', $value) : $query->where('link_field', $value);
    }

    public function scopeCateExists($query, $value)
    {
        $query->whereExists(function ($que) use ($value) {
            $que->from('form_cate')->whereColumn('form_cate.id', $this->table . '.cate_id')
                ->where('form_cate.types', $value)->where('form_cate.status', 1);
        });
    }

    public function options()
    {
        return $this->hasMany(DictData::class, 'type_name', 'dict_ident');
    }

    /**
     * value 修改器.
     * @param mixed $value
     */
    protected function setValueAttribute($value): void
    {
        $this->attributes['value'] = $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * value 获取器.
     * @param mixed $value
     */
    protected function getValueAttribute($value): mixed
    {
        return $value ? (is_string($value) ? json_decode($value, true) : $value) : $value;
    }
}
