<?php

namespace App\Http\Model\Crud;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;

/**
 * 问卷调查
 */
class SystemCrudQuestionnaire extends BaseModel
{

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_questionnaire';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'crud_id'      => 'integer',
        'user_id'      => 'integer',
        'role_type'    => 'integer',
        'invalid_time' => 'datetime:Y-m-d H:i:s',
        'status'       => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * crud
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function crud()
    {
        return $this->hasOne(SystemCrud::class, 'id', 'crud_id');
    }

}
