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

use App\Http\Model\WorkExternalContact\WorkMedia;
use App\Http\Service\WorkExternalContact\WorkMediaService;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 素材即时上传任务
 */
class WorkMediaInstantUploadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    private WorkMedia $file;

    public function __construct(WorkMedia $file)
    {
        $this->file = $file;
    }

    public function handle(WorkMediaService $workMediaService, Work $work)
    {
        $workMediaService->uploadToWork($this->file, $work);
    }
}
