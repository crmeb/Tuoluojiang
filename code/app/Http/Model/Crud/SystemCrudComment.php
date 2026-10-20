<?php

namespace App\Http\Model\Crud;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;

/**
 * 评论
 */
class SystemCrudComment extends BaseModel
{
    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_comment';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'uid'        => 'integer',
        'crud_id'    => 'integer',
        'data_id'    => 'integer',
        'pid'        => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联用户.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }

    /**
     * 关联自己的下级评论
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reply()
    {
        return $this->hasMany(SystemCrudComment::class, 'pid', 'id');
    }

}
