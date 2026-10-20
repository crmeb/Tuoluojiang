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

namespace App\Task\message;

use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Company\CompanyService;
use App\Http\Service\News\NewsService;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 企业动态通知.
 */
class NewsRemind extends Task
{
    public function __construct(protected $entId, protected $newsId) {}

    public function handle()
    {
        try {
            $news = app()->get(NewsService::class)->get($this->newsId, ['id', 'title', 'push_type', 'push_time', 'status'])?->toArray();
            if (! $news) {
                return;
            }
            if ($news['status'] != 1) {
                return;
            }
            $userIds = app()->get(AdminService::class)->column(['status' => 1], 'id');
            if ($news['push_type']) {
                $delay = Carbon::make($news['push_time'])->isAfter(now()) ? Carbon::make($news['push_time'])->diffInMinutes(now()) : 0;
            } else {
                $delay = 0;
            }
            event(new SystemMessageEvent(
                type: NoticeEnum::COMPANY_NEWS,
                params: [
                    '企业名称' => app()->get(CompanyService::class)->value(1, 'enterprise_name') ?? '',
                    '文章标题' => $news['title'] ?? '',
                ],
                receiverIds: $userIds,
                other: ['id' => $this->newsId],
                linkId: $this->newsId,
                linkStatus: $news['status'],
                setDelay: $delay
            ));
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
