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

namespace App\Task\folder;

use App\Http\Service\Cloud\CloudFileService;
use Tuoluojiang\Open\services\UploadService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 删除企业空间.
 */
class SpaceDestroyTask extends Task
{
    /**
     * @var CloudFileService|mixed
     */
    private CloudFileService $service;

    /**
     * @param int $id 删除资源 id
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected int $id)
    {
        $this->service = app()->get(CloudFileService::class);
    }

    /**
     * 执行.
     */
    public function handle()
    {
        try {
            $file = $this->service->getWithTrashed($this->id)->first();
            if ($file) {
                $this->deepRm($file);
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    public function deepRm($file)
    {
        if (! $file->type) {
            $this->rmFile($file);
        } else {
            $this->service->getWithTrashed(['pid' => $file->id])->get()->each(function ($file) {
                $this->deepRm($file);
            });
        }
        $this->service->forceDelete($file->id);
    }

    public function rmFile($file)
    {
        $upload = UploadService::init($file->upload_type);
        if ($file->upload_type == 1) {
            $upload->delete($file->file_url);
        } else {
            $upload->delete($file->file_name);
        }
    }
}
