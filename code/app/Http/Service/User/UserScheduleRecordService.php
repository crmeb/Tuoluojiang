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

namespace App\Http\Service\User;

use App\Constants\CacheEnum;
use App\Http\Dao\User\UserScheduleRecordDao;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Cache;

class UserScheduleRecordService extends BaseService
{
    /**
     * 日程完成记录
     * UserScheduleRecordService constructor.
     */
    public function __construct(UserScheduleRecordDao $dao)
    {
        $this->dao = $dao;
    }

    public function saveRecord($id, $data)
    {
        if (! $id) {
            throw $this->exception('操作失败：缺少必要参数');
        }

        $info = app()->get(UserScheduleService::class)->get($id);
        if (! $info) {
            throw $this->exception('未找到待操作的记录');
        }
        if (! $data['time']) {
            throw $this->exception('未找到待操作的记录');
        }
        if ($info['uid'] != $data['uid']) {
            throw $this->exception('无效的提醒信息!');
        }

        $time  = date('Y-m-d H:i:s');
        $where = ['uid' => $data['uid'], 'schedultid' => $id, 'remind_day' => $data['time']];
        $this->dao->updateOrCreate($where, array_merge($where, ['status' => $data['status'], 'created_at' => $time, 'updated_at' => $time]));
        Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
    }
}
