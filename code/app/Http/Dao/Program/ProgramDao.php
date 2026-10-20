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

namespace App\Http\Dao\Program;

use App\Http\Model\Program\Program;
use App\Http\Service\Program\ProgramMemberService;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;

/**
 * 项目
 * Class ProgramDao.
 */
class ProgramDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;

    /**
     * 搜索.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null): mixed
    {
        $listWhere = [];
        if (is_array($where) && isset($where['types'])) {
            // 1：负责；2：参与；3：创建；
            $where['types'] && $where = array_merge($where, match ((int) $where['types']) {
                1       => ['uid' => $where['admin_uid']],
                2       => ['id' => app()->get(ProgramMemberService::class)->column(['uid' => $where['admin_uid']], 'program_id')],
                3       => ['creator_uid' => $where['admin_uid']],
                default => []
            });

            $listWhere = $where;
            if ($where['types'] > 1) {
                unset($where['uid'], $listWhere['uid']);
            }

            if ($where['types'] < 1) {
                unset($where['uid']);
            }
            unset($where['types'], $where['admin_uid'], $where['status']);
        }

        return parent::search($where, $authWhere)
            ->when(isset($listWhere['types']) && $listWhere['types'] < 1, function ($query) use ($listWhere) {
                $query->where(function ($query) use ($listWhere) {
                    $query->orWhereIn('creator_uid', $listWhere['uid'])->orWhereIn('uid', $listWhere['uid'])
                        ->orWhereIn('id', app()->get(ProgramMemberService::class)->column(['uid' => $listWhere['uid']], 'program_id'));
                });
            })->when(isset($listWhere['status']) && $listWhere['status'], function ($query) use ($listWhere) {
                $date         = Carbon::today(config('app.timezone'))->toDateString();
                $statusSearch = array_filter(is_array($listWhere['status']) ? $listWhere['status'] : [$listWhere['status']]);
                $query->where(function ($query) use ($statusSearch, $date) {
                    foreach ($statusSearch as $status) {
                        match ((int) $status) {
                            1 => $query->orWhere('status', 1),
                            2 => $query->orWhere('status', 2),
                            3 => $query->orWhere(function ($query) use ($date) {
                                $query->where('status', 0)->whereDate('start_date', '>', $date)->where(function ($query) use ($date) {
                                    $query->whereNull('end_date')->orWhere(function ($query) use ($date) {
                                        $query->whereDate('end_date', '>', $date);
                                    });
                                });
                            }),
                            4 => $query->orWhere(function ($query) use ($date) {
                                $query->where('status', 0)->where(function ($query) use ($date) {
                                    $query->where('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->orWhere(function ($query) {
                                        $query->whereNull('start_date')->whereNull('end_date');
                                    });
                                });
                            }),
                            5 => $query->orWhere(function ($query) use ($date) {
                                $query->where('status', 0)->whereDate('end_date', '<', $date);
                            }),
                            default => '',
                        };
                    }
                });
            });
    }

    protected function setModel(): string
    {
        return Program::class;
    }
}
