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

namespace App\Http\Model\Attach;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * Class SystemAttach.
 */
class SystemAttach extends BaseModel
{
    use TimeDataTrait;

    /*sss
     * 表名
     * @var string
     */
    protected $table = 'system_attach';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'            => 'integer',
        'entid'         => 'integer',
        'cid'           => 'integer',
        'up_type'       => 'integer',
        'way'           => 'integer',
        'relation_type' => 'integer',
        'relation_id'   => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

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
     * 附件路径.
     * @param mixed $value
     */
    public function getSrcAttribute($value): string
    {
        return $value ? link_file($value) : '';
    }

    /**
     * 附件路径.
     * @param mixed $value
     */
    public function getUrlAttribute($value): string
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
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 分类id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCid($query, $value)
    {
        return $value !== '' ? $query->where('cid', $value) : null;
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
        $query->where(function ($query) use ($value) {
            $query->where('name', 'LIKE', '%' . $value . '%')->orWhere('real_name', 'LIKE', '%' . $value . '%');
        });
    }

    /**
     * relation_type作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeRelationType($query, $value)
    {
        is_array($value) ? $query->whereIn('relation_type', $value) : $query->where('relation_type', $value);
    }

    public function scopeRelationId($query, $value)
    {
        is_array($value) ? $query->whereIn('relation_id', $value) : $query->where('relation_id', $value);
    }

    /**
     * file_ext作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeFileExt($query, $value)
    {
        is_array($value) ? $query->whereIn('file_ext', $value) : $query->where('file_ext', $value);
    }

    /**
     * 一对一关联创建人.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'uid', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }
}
