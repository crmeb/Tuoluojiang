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

namespace App\Jobs\Attend;

use App\Http\Service\Attendance\AttendanceClockService;
use App\Http\Service\Other\UploadService;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 企微考勤素材获取.
 */
class AttendMediaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    private Work $work;

    private AttendanceClockService $clock;

    public function __construct(protected int $clockId, protected array|string $mediaId)
    {
        $this->clock = app()->get(AttendanceClockService::class);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->work = app()->get(Work::class);
        $upload     = UploadService::init();
        $time       = now()->toArray();
        $dir        = $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        try {
            $media = collect();
            $save  = collect();
            is_array($this->mediaId) ? collect($this->mediaId)->each(function ($mediaId) use ($media) {
                $result = $this->work->getMedia($mediaId);
                $media->put($mediaId, $result);
            }) : $media->put($this->mediaId, $this->work->getMedia($this->mediaId));
            if ($media->isNotEmpty()) {
                $media->each(function ($item, $key) use ($upload, $dir, $save) {
                    $mediaName  = $this->getResourceFileName($item->getHeaders(), $key);
                    $stream     = $item->toStream();
                    $contentStr = '';
                    // 判断类型并处理
                    if (is_resource($stream)) {
                        // 原生资源句柄 → 用PHP原生函数
                        while (! feof($stream)) { // 原生feof()函数
                            $contentStr .= fread($stream, 1024 * 1024);
                        }
                        fclose($stream); // 关闭原生资源
                    } else {
                        // Stream对象 → 用对象方法
                        while (! $stream->eof()) {
                            $contentStr .= $stream->read(1024 * 1024);
                        }
                        $stream->close(); // 关闭Stream对象
                    }
                    if (! $upload->to('media/' . $dir . '/')->validate()->stream($contentStr, $mediaName)) {
                        throw new UploadException($upload->getError());
                    }
                    $fileInfo = $upload->getUploadInfo();
                    $save->push(link_file($fileInfo['dir']));
                });
            }
            if ($save->isNotEmpty()) {
                $this->clock->update($this->clockId, ['image' => $save->toArray()]);
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    public function getResourceFileName(array $headers, string $mediaId): string
    {
        $disposition = $headers['content-disposition'][0] ?? '';
        if (! empty($disposition)) {
            // 匹配规则：兼容 filename="xxx" 或 filename=xxx 格式，支持中文/特殊字符
            if (preg_match('/filename=([\'"]?)(.*?)\1/', $disposition, $matches)) {
                $fileName = urldecode($matches[2]); // 解码URL编码的文件名（如%E5%9B%BE%7D.jpg）
                // 过滤非法字符（避免系统不兼容）
                $fileName = $this->filterIllegalFileName($fileName);
                if (! empty($fileName)) {
                    return $fileName;
                }
            }
        }
        return $mediaId . '.jpg';
    }

    /**
     * 过滤文件名中的非法字符（适配Windows/Linux）.
     * @param string $fileName 原始文件名
     * @return string 过滤后的合法文件名
     */
    private function filterIllegalFileName(string $fileName): string
    {
        $illegalChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"];
        $fileName     = str_replace($illegalChars, '_', $fileName);
        // 截断过长文件名（避免系统限制）
        $maxLength = 255;
        if (mb_strlen($fileName) > $maxLength) {
            $ext      = pathinfo($fileName, PATHINFO_EXTENSION);
            $name     = mb_substr($fileName, 0, $maxLength - mb_strlen($ext) - 1);
            $fileName = $name . '.' . $ext;
        }
        return trim($fileName);
    }
}
