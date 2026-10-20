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
use App\Http\Service\Cloud\CloudFileService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 文件删除提醒
 * Class CloudFileDeleteRemind.
 */
class CloudFileDeleteRemind extends Task
{
    public function __construct(protected $entId, protected $uuid, protected $folderInfo) {}

    public function handle()
    {
        try {
            Task::deliver(new StatusChangeTask(CloudEnum::FILE_NOTICE, CloudEnum::FILE_DELETE, $this->entId, $this->folderInfo['id']));
            // 自己删除自己的文件不提醒
            if ($this->uuid !== $this->folderInfo['uid']) {
                $path = $this->getPathAttribute($this->folderInfo['path']);
                if (! isset($path[0])) {
                    return;
                }
                $folderPid = app()->get(CloudFileService::class)->get($path[0], ['name', 'uid', 'created_at'])?->toArray();
                if (! $folderPid) {
                    return;
                }
                event(new SystemMessageEvent(
                    type: NoticeEnum::CLOUD_FILE_DELETE_TYPE,
                    params: [
                        '删除人'   => app(AdminService::class)->value(['uid' => $this->uuid], 'name'),
                        '删除时间' => date('Y-m-d H:i:s'),
                        '文件名称' => $this->folderInfo['name'],
                        '空间名称' => $folderPid['name'] ?? '',
                        '创建时间' => $folderPid['created_at'] ?? '',
                        '创建人'   => $userInfo['name'] ?? '',
                    ],
                    receiverIds: (int) $this->folderInfo['user_id'],
                    other: [
                        'id' => $this->folderInfo['id'],
                    ],
                    linkId: $this->folderInfo['id'],
                    linkStatus: CloudEnum::FILE_DELETE
                ));
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    public function getPathAttribute($value)
    {
        return $value ? array_map('intval', array_merge(array_filter(explode('/', $value)))) : [];
    }
}
