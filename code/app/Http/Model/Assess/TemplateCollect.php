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

use App\Http\Model\BaseModel;

/**
 * Class TemplateCollect.
 */
class TemplateCollect extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise_template_collect';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'entid'      => 'integer',
        'user_id'    => 'integer',
        'temp_id'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @return mixed
     */
    public function scopeUserId($query, $val)
    {
        if ($val !== '') {
            return $query->where('user_id', $val);
        }
    }

    /**
     * @return mixed
     */
    public function scopeTempId($query, $val)
    {
        if ($val !== '') {
            return $query->where('temp_id', $val);
        }
    }
}
