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

/**
 * 实体数据权限.
 */
class SystemCrudRole extends BaseModel
{
    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_role';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'role_id'    => 'integer',
        'crud_id'    => 'integer',
        'created'    => 'integer',
        'reade'      => 'integer',
        'updated'    => 'integer',
        'deleted'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'transfer'   => 'integer',
        'share'      => 'integer',
    ];

    public function setReadeFrameAttribute($value)
    {
        $this->attributes['reade_frame'] = json_encode($value);
    }

    public function getReadeFrameAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setUpdatedFrameAttribute($value)
    {
        $this->attributes['updated_frame'] = json_encode($value);
    }

    public function getUpdatedFrameAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setDeletedFrameAttribute($value)
    {
        $this->attributes['deleted_frame'] = json_encode($value);
    }

    public function getDeletedFrameAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTransferFrameAttribute($value)
    {
        $this->attributes['transfer_frame'] = json_encode($value);
    }

    public function getTransferFrameAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setShareFrameAttribute($value)
    {
        $this->attributes['share_frame'] = json_encode($value);
    }

    public function getShareFrameAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 角色ID作用域
     */
    public function scopeRoleId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('role_id', $value);
        } else {
            $query->where('role_id', $value);
        }
    }
}
