<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\Work;

use App\Http\Model\Admin\Admin;
use App\Http\Service\Admin\AdminService;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

class WorkGroupChat extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'                => 'integer',
        'group_create_time' => 'integer',
        'member_num'        => 'integer',
        'retreat_group_num' => 'integer',
        'status'            => 'integer',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'work_group_chat';

    public function getGroupCreateTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s',(int) $value) : '';
    }

    public function scopeAdminList($query, $value)
    {
        if (is_array($value)) {
            $query->where(function ($que) use ($value) {
                foreach ($value as $v) {
                    $que->orWhereJsonContains('admin_list', $v)->orWhereJsonContains('admin_list', (string) $v);
                }
            });
        } else {
            $query->where(function ($que) use ($value) {
                $que->orWhereJsonContains('admin_list', $value)->orWhereJsonContains('admin_list', (string) $value);
            });
        }
    }

    public function scopeAdminId($query, $value)
    {
        if (is_array($value)) {
            $value = collect(app()->get(AdminService::class)->select(['id' => $value], with: ['work']))->pluck('work.userid')->filter()->unique()->values()->all();
            $query->whereIn('owner', $value);
        } else {
            $value = collect(app()->get(AdminService::class)->select(['id' => $value], with: ['work']))->pluck('work.userid')->filter()->unique()->values()->all();
            $query->where('owner', end($value));
        }
    }

    public function scopeOwner($query, $value)
    {
        is_array($value) ? $query->whereIn('owner', $value) : $query->where('owner', $value);
    }

    public function scopeChatId($query, $value)
    {
        is_array($value) ? $query->whereIn('chat_id', $value) : $query->where('chat_id', $value);
    }

    public function scopeNameLike($query, $value)
    {
        $query->where('name', 'like', "%{$value}%");
    }

    public function admin()
    {
        return $this->hasOneThrough(Admin::class, WorkMember::class, 'userid', 'work_member_id', 'owner', 'id')->select(['admin.id', 'admin.name', 'admin.avatar', 'admin.phone']);
    }
}
