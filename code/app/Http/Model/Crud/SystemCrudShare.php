<?php

namespace App\Http\Model\Crud;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;

/**
 * 数据共享记录
 */
class SystemCrudShare extends BaseModel
{

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_share';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'crud_id'         => 'integer',
        'user_id'         => 'integer',
        'role_type'       => 'integer',
        'operate_user_id' => 'integer',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
    ];


    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    public function operate()
    {
        return $this->hasOne(Admin::class, 'id', 'operate_user_id');
    }

    public function scopeIds($query, $value)
    {
        if ($value) {
            $query->whereIn('id', $value);
        }
    }
}
