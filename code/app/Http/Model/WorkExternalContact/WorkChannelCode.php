<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\WorkExternalContact;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企微渠道活码.
 */
class WorkChannelCode extends BaseModel
{
    use TimeDataTrait;
    use SoftDeletes;

    protected $table = 'work_channel_code';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'                         => 'integer',
        'uid'                        => 'integer',
        'group_id'                   => 'integer',
        'code_id'                    => 'integer',
        'invite_group'               => 'integer',
        'userids'                    => 'array',
        'work_userids'               => 'array',
        'tag_ids'                    => 'array',
        'work_tag_ids'               => 'array',
        'welcome_messages'           => 'array',
    ];

    public function group(): HasOne
    {
        return $this->hasOne(WorkChannelCodeCate::class, 'id', 'group_id')->select(['id', 'name']);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(WorkChannelCodeGroup::class, 'id', 'code_id')->orderBy('sort')->orderBy('id');
    }

    public function inviteGroupConfig(): HasOne
    {
        return $this->hasOne(WorkChannelCodeGroup::class, 'id', 'code_id')->select(['id', 'chat_ids', 'group_name', 'room_base_name', 'room_base_id', 'auto_create_room', 'max_member_num', 'sort', 'status', 'scene', 'join_way_remark']);
    }

    public function creator(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    public function scopeCorpId($query, $value): void
    {
        $query->where('corp_id', $value);
    }

    public function scopeGroupId($query, $value): void
    {
        $query->where('group_id', $value);
    }
}
