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

namespace App\Jobs\WorkExternalContact;

use App\Constants\CacheEnum;
use App\Http\Service\WorkExternalContact\WorkMediaService;
use Tuoluojiang\Open\services\wechat\Work;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 素材上传定时任务
 */
class WorkMediaUploadCronJob extends CronJob
{
    /**
     * 频率：每5m运行一次
     * @return int
     */
    public function interval()
    {
        return 30000;
    }

    public function run()
    {
        try {
            if (!sys_config('wechat_work_corpid')){
                return true;
            }
            $workMediaService = app(WorkMediaService::class);
            $work             = app(Work::class);
            $where            = ['normal' => true];
            $failId = Cache::tags([CacheEnum::TAG_MEDIA])->get('work_media_fail_id',[]);
            if ($failId){
                $where['not_id'] = $failId;
            }
            $files            = $workMediaService->select($where, ['id', 'file_type', 'file_name', 'file_url', 'up_type', 'file_size', 'media_id', 'job_id']);
            foreach ($files as $file) {
                $workMediaService->uploadToWork($file, $work);
            }
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
