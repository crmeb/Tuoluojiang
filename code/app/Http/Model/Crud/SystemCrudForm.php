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
 * Class SystemCrudForm.
 * @email 136327134@qq.com
 * @date 2024/2/26
 */
class SystemCrudForm extends BaseModel
{
    use SoftDeletes;

    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_form';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'crud_id'    => 'integer',
        'version'    => 'integer',
        'is_index'   => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
        'is_master'  => 'integer',
    ];

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = $value ? json_encode($value) : '';
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    public function setGlobalOptionsAttribute($value)
    {
        $this->attributes['global_options'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getGlobalOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/4/1
     */
    public function setFieldsAttribute($value)
    {
        $this->attributes['fields'] = json_encode($value);
    }

    /**
     * @return array|mixed
     * @email 136327134@qq.com
     * @date 2024/4/1
     */
    public function getFieldsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function crud()
    {
        return $this->belongsTo(SystemCrud::class, 'crud_id', 'id');
    }
}
