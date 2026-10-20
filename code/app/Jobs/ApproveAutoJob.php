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

namespace App\Jobs;

use App\Http\Service\Approve\ApproveApplyService;
use App\Task\approve\ApprovedTask;
use App\Task\message\BusinessAdoptCcRemind;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApproveAutoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     * @param mixed $applyId
     * @param mixed $entId
     * @param mixed $userService
     */
    public function __construct(protected array $process, protected $applyId, protected $entId, protected $userService) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->process) {
            $applyService = app()->get(ApproveApplyService::class);
            $types        = array_column($this->process, 'types');
            if (! in_array(1, $types)) {
                $this->userService->update(['apply_id' => $this->applyId, 'node_id' => array_column($this->process, 'uniqued')], ['status' => 1]);
                $applyService->update(['id' => $this->applyId], ['status' => 1, 'node_id' => $this->process[count($this->process) - 1]['uniqued']]);
                Task::deliver(new ApprovedTask((int) $this->applyId));
            } else {
                foreach ($this->process as $key => $value) {
                    if (! $key) {
                        if ($value['types'] == 2) {
                            $this->userService->update(['apply_id' => $this->applyId, 'node_id' => $value['uniqued']], ['status' => 1]);
                            // 抄送人【业务类型】审批通过提醒
                            Task::deliver(new BusinessAdoptCcRemind($this->entId, $this->applyId, $value['uniqued']));
                            Task::deliver(new ApprovedTask((int) $this->applyId));
                            if (! isset($this->process[$key + 1])) {
                                $applyService->update(['id' => $this->applyId], ['node_id' => $value['uniqued'], 'status' => 1]);
                            } else {
                                $applyService->update(['id' => $this->applyId], ['node_id' => $this->process[$key + 1]['uniqued']]);
                            }
                        }
                    } else {
                        if ($value['types'] == 2 && $this->process[$key - 1]['types'] == 2) {
                            $this->userService->update(['apply_id' => $this->applyId, 'node_id' => $value['uniqued']], ['status' => 1]);

                            // 抄送人【业务类型】审批通过提醒
                            Task::deliver(new BusinessAdoptCcRemind($this->entId, $this->applyId, $value['uniqued']));
                            Task::deliver(new ApprovedTask((int) $this->applyId));

                            if (! isset($this->process[$key + 1])) {
                                $applyService->update(['id' => $this->applyId], ['status' => 1, 'node_id' => $value['uniqued']]);
                            } else {
                                $applyService->update(['id' => $this->applyId], ['node_id' => $value['uniqued']]);
                            }
                        } else {
                            break;
                        }
                    }
                }
            }
        }
    }
}
