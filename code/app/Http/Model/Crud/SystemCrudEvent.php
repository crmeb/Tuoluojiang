<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------.
 */

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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SystemCrudEvent.
 * @email 136327134@qq.com
 * @date 2024/2/28
 */
class SystemCrudEvent extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_event';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'                              => 'integer',
        'crud_id'                         => 'integer',
        'sort'                            => 'integer',
        'timer'                           => 'integer',
        'timer_type'                      => 'integer',
        'target_crud_id'                  => 'integer',
        'crud_approve_id'                 => 'integer',
        'curl_id'                         => 'integer',
        'send_type'                       => 'integer',
        'additional_search_boolean'       => 'integer',
        'aggregate_target_search_boolean' => 'integer',
        'aggregate_data_search_boolean'   => 'integer',
        'other_webhook_status'            => 'integer',
        'ding_webhook_status'             => 'integer',
        'work_webhook_status'             => 'integer',
        'sms_status'                      => 'integer',
        'system_status'                   => 'integer',
        'status'                          => 'integer',
        'created_at'                      => 'datetime:Y-m-d H:i:s',
        'updated_at'                      => 'datetime:Y-m-d H:i:s',
        'deleted_at'                      => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联实体.
     * @return HasOne
     * @email 136327134@qq.com
     * @date 2024/4/16
     */
    public function crud()
    {
        return $this->hasOne(SystemCrud::class, 'id', 'crud_id');
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getUpdateFieldOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setUpdateFieldOptionsAttribute($value)
    {
        $this->attributes['update_field_options'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : (object) [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setFieldOptionsAttribute($value)
    {
        $this->attributes['field_options'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getFieldOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setAdditionalSearchAttribute($value)
    {
        $this->attributes['additional_search'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getAdditionalSearchAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setActionAttribute($value)
    {
        $this->attributes['action'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getActionAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setAggregateTargetSearchAttribute($value)
    {
        $this->attributes['aggregate_target_search'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getAggregateTargetSearchAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setAggregateDataSearchAttribute($value)
    {
        $this->attributes['aggregate_data_search'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getAggregateDataSearchAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setAggregateFieldRuleAttribute($value)
    {
        $this->attributes['aggregate_field_rule'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getAggregateFieldRuleAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/3/22
     */
    public function scopeName($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/4/16
     */
    public function scopeCrudId($query, $value)
    {
        if ($value) {
            $query->where('crud_id', $value);
        }
    }

    public function scopeCateId($query, $value)
    {
        if ($value) {
            $query->whereIn('crud_id', fn ($q) => $q->from('system_crud')->where('cate_ids', 'like', '%/' . $value . '/%')
                ->select('id'));
        }
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setAggregateDataFieldAttribute($value)
    {
        $this->attributes['aggregate_data_field'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getAggregateDataFieldAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setSendUserAttribute($value)
    {
        $this->attributes['send_user'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getSendUserAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 发送类型.
     * @email 136327134@qq.com
     * @date 2024/4/12
     */
    public function setNotifyTypeAttribute($value)
    {
        $this->attributes['notify_type'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getNotifyTypeAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTimerOptionsAttribute($value)
    {
        $this->attributes['timer_options'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getTimerOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : (object) [];
    }
}
