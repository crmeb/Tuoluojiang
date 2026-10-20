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

use App\Constants\CloudEnum;
use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Cloud\CloudAuthService;
use App\Http\Service\Cloud\CloudFileService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 文件创建提醒
 * Class CloudFileCreateRemind.
 */
class CloudFileCreateRemind extends Task
{
    public function __construct(protected $entId, protected $uid, protected $fileInfo, protected $pid) {}

    public function handle()
    {
        try {
            $folderPid = app()->get(CloudFileService::class)->get($this->pid, ['name', 'uid', 'created_at']);
            if (! $folderPid) {
                return;
            }
            $uids = app()->get(CloudAuthService::class)->column(['folder_id' => $this->pid, 'not_uid' => $this->uid], 'user_id');
            event(new SystemMessageEvent(
                type: NoticeEnum::CLOUD_FILE_CREATE_TYPE,
                params: [
                    '创建人'   => app(AdminService::class)->value(['id' => $this->fileInfo['user_id']], 'name'),
                    '创建时间' => date('Y-m-d H:i:s'),
                    '文件名称' => $this->fileInfo['name'],
                    '空间名称' => $folderPid['name'],
                ],
                receiverIds: $uids,
                other: [
                    'id' => $this->fileInfo['id'],
                ],
                linkId: $this->fileInfo['id'],
                linkStatus: CloudEnum::FILE_READ
            ));
            Task::deliver(new StatusChangeTask(CloudEnum::FILE_NOTICE, CloudEnum::FILE_READ, $this->entId, $this->fileInfo['id']));
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
