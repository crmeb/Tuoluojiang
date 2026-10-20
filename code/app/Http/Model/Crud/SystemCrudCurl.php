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

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * curl.
 */
class SystemCrudCurl extends BaseModel
{
    use SoftDeletes;

    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_curl';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'             => 'integer',
        'is_pre'         => 'integer',
        'pre_cache_time' => 'integer',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
    ];

    public function setPreHeadersAttribute($value)
    {
        $this->attributes['pre_headers'] = json_encode($value);
    }

    /**
     * @return mixed
     */
    public function getPreHeadersAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setPreDataAttribute($value)
    {
        $this->attributes['pre_data'] = json_encode($value);
    }

    /**
     * @return mixed
     */
    public function getPreDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setHeadersAttribute($value)
    {
        $this->attributes['headers'] = json_encode($value);
    }

    /**
     * @return mixed
     */
    public function getHeadersAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * 标题搜索.
     */
    public function scopeTitle($query, $value)
    {
        if ($value !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%' . $value . '%')
                ->orWhere('url', 'like', '%' . $value . '%'));
        }
    }

    public function scopeMethod($query, $value)
    {
        if ($value !== '') {
            $query->where('method', $value);
        }
    }
}
