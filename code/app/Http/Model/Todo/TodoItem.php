<?php

declare(strict_types=1);

namespace App\Http\Model\Todo;

use App\Http\Model\BaseModel;

class TodoItem extends BaseModel
{
    protected $table = 'todo_items';

    protected $casts = [
        'extra'             => 'array',
        'source_created_at' => 'datetime:Y-m-d H:i:s',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
        'id'                => 'integer',
        'user_id'           => 'integer',
        'source_id'         => 'integer',
        'status'            => 'integer',
    ];

    public const STATUS_PENDING  = 1;
    public const STATUS_DONE     = 2;
}
