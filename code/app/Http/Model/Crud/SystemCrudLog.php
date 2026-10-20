<?php

namespace App\Http\Model\Crud;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;

/**
 * 操作日志
 */
class SystemCrudLog extends BaseModel
{
    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_log';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'uid'          => 'integer',
        'crud_id'      => 'integer',
        'data_id'      => 'integer',
        'data_crud_id' => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }
}
