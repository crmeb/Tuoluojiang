<?php

namespace App\Http\Model\Crud;

use App\Http\Model\BaseModel;

/**
 * 操作按钮模型
 */
class SystemCrudOperate extends BaseModel
{
    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_operate';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'                  => 'integer',
        'crud_id'             => 'integer',
        'sort'                => 'integer',
        'system_crud_form_id' => 'integer',
        'operate_type'        => 'integer',
        'status'              => 'integer',
        'action_type'         => 'integer',
        'created_at'          => 'datetime:Y-m-d H:i:s',
        'updated_at'          => 'datetime:Y-m-d H:i:s',
    ];


    public function scopeStatus($query, $value = '')
    {
        if ('' !== $value) {
            $query->where('status', $value);
        }
    }

    public function setUseRuleAttribute($value)
    {
        $this->attributes['use_rule'] = json_encode($value);
    }

    /**
     * @return mixed
     */
    public function getUseRuleAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value);
    }

    /**
     * @return mixed
     */
    public function getOptionsAttribute($value)
    {
        return json_decode($value, true);
    }

}
