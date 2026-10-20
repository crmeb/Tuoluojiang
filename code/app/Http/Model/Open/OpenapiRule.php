<?php

namespace App\Http\Model\Open;

use App\Http\Model\BaseModel;

class OpenapiRule extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'openapi_rule';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'pid'        => 'integer',
        'type'       => 'integer',
        'crud_id'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 请求地址提取.
     * @return mixed
     */
    public function getUrlAttribute($value): string
    {
        return "api/" . $value;
    }
}
