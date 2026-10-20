<?php

namespace App\Http\Model\Crud;

use App\Http\Model\BaseModel;

/**
 * 数据共享模型
 */
class SystemCrudDataShare extends BaseModel
{

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_data_share';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'        => 'integer',
        'share_id'  => 'integer',
        'crud_id'   => 'integer',
        'data_id'   => 'integer',
        'user_id'   => 'integer',
        'is_show'   => 'integer',
        'is_update' => 'integer',
        'is_delete' => 'integer',
    ];

    public $timestamps = false;
}
