<?php

namespace App\Http\Model\Work;

use App\Http\Model\BaseModel;

class WorkMessageSeq extends BaseModel
{

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'seq'        => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'work_message_seq';
}
