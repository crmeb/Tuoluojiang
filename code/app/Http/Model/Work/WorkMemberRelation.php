<?php

namespace App\Http\Model\Work;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

class WorkMemberRelation extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'                => 'integer',
        'member_id'         => 'integer',
        'department'        => 'integer',
        'srot'              => 'integer',
        'is_leader_in_dept' => 'integer',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'work_member_relation';


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
