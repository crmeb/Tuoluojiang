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

namespace App\Http\Service\Work;

use App\Http\Dao\Work\WorkClientFollowDao;
use App\Http\Service\Customer\LabelService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户跟进
 * Class WorkClientFollowService.
 */
class WorkClientFollowService extends BaseService
{
    public function __construct(WorkClientFollowDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户客户数量.
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserClientCount(array $search)
    {
        $followId    = [];
        $search      = collect($search)->pluck('value', 'field')->all();
        $labelSearch = $search['customer_label'] ?? [];
        if ($labelSearch) {
            $workLabel = app(LabelService::class)->column(['id' => $labelSearch], 'work_tag_id');
            $followId  = collect(app()->get(WorkClientFollowTagsService::class)->column(['tag_id' => $workLabel], 'follow_id') ?? [])->filter()->unique()->all();
        }
        $timeSearch = $search['created_at'] ?? [];
        if ($labelSearch) {
            $followId = array_intersect($followId, collect($this->dao->column(['create_time' => $timeSearch], 'id') ?? [])->filter()->unique()->all());
        }
        return $this->dao->count(['id' => $followId]);
    }
}
