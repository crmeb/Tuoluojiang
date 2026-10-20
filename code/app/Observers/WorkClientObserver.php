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

namespace App\Observers;

use App\Constants\CustomEnum\ClueEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Model\Customer\Lead;
use App\Http\Model\Work\WorkClient;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\RecordService;
use App\Http\Service\Work\WorkMemberService;
use Illuminate\Support\Facades\Log;

/**
 * 企微客户观察者.
 */
class WorkClientObserver
{
    public function created(WorkClient $model)
    {
        try {
            $other          = $model->load('followOne.tags.tag');
            $work_member_id = app()->get(WorkMemberService::class)->value(['userid' => $model->userid], 'id');
            $uid            = app()->get(AdminService::class)->value(['work_member_id' => $work_member_id], 'id') ?: 0;
            $res            = Lead::create([
                'name'            => $model->name,
                'source'          => 'wework',
                'external_userid' => $model->external_userid,
                'userid'          => $model->userid,
                'uid'             => $uid,
                'createtime'      => date('Y-m-d H:i:s', $other->followOne?->createtime),
            ]);
            app()->get(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CLUE, [
                'eid'            => $res->id,
                'type'           => ClueEnum::OPERATE_CREATE,
                'creator_uid'    => $uid,
                'record_version' => 0,
                'reason'         => '企微同步线索“' . $model->name . '”',
            ]);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
