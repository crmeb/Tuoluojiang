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

namespace App\Jobs\Customer;

use App\Http\Service\Customer\LabelService;
use App\Http\Service\Customer\LeadService;
use App\Jobs\Work\WorkClientSetLabelJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 替换客户线索标签
 * Class ReplaceCustomerClueLabelJob.
 */
class ReplaceCustomerClueLabelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    private LabelService $labelService;

    private LeadService $clueService;

    public function __construct(protected array $labelInfo, protected int $replaceLabelId, protected int $page = 1, protected int $limit = 50)
    {
        $this->labelService = app()->get(LabelService::class);
        $this->clueService  = app()->get(LeadService::class);
    }

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function handle()
    {
        $list   = $this->clueService->dao->select(['customer_label' => $this->labelInfo['id']], ['id', 'external_userid', 'customer_label', 'userid'], page: $this->page, limit: $this->limit);
        $addTag = $this->labelService->idByWorkTagId([$this->replaceLabelId]);
        collect($list)->each(function ($item) use ($addTag) {
            $jsonArray = $item->customer_label;
            $index     = array_search($this->labelInfo['id'], $jsonArray);
            if ($index !== false) {
                $jsonArray[$index] = $this->replaceLabelId;
            } else {
                $jsonArray[] = $this->replaceLabelId;
            }
            $item->customer_label = $jsonArray;
            $item->save();
            // 更新企业微信的标签
            if ($item->userid && $item->external_userid) {
                WorkClientSetLabelJob::dispatch([
                    'userid'          => $item->userid,
                    'external_userid' => $item->external_userid,
                    'add_tag'         => $addTag,
                    'remove_tag'      => [$this->labelInfo['work_tag_id']],
                ]);
            }
        });
        if (count($list) == $this->limit) {
            ++$this->page;
            ReplaceCustomerClueLabelJob::dispatch($this->labelInfo, $this->replaceLabelId, $this->page, $this->limit);
        }
    }
}
