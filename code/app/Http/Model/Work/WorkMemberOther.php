<?php

namespace App\Http\Model\Work;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

class WorkMemberOther extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'member_id'  => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'work_member_other';

    public function scopeMemberId($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('member_id', $value);
            } else {
                $query->where('member_id', $value);
            }
        }
    }

}
