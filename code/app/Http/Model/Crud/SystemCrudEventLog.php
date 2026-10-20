<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\Crud;

use App\Http\Model\BaseModel;

/**
 * Class SystemCrudEventLog.
 * @email 136327134@qq.com
 * @date 2024/3/14
 */
class SystemCrudEventLog extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_event_log';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'crud_id'    => 'integer',
        'event_id'   => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     * @param mixed $value
     */
    public function setParameterAttribute($value)
    {
        $this->attributes['parameter'] = json_encode($value);
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     * @param mixed $value
     * @return mixed
     */
    public function getParameterAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     * @param mixed $value
     */
    public function setLogAttribute($value)
    {
        $this->attributes['log'] = json_encode($value);
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     * @param mixed $value
     * @return mixed
     */
    public function getLogAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getActionAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function crud()
    {
        return $this->hasOne(SystemCrud::class, 'id', 'crud_id');
    }
}
