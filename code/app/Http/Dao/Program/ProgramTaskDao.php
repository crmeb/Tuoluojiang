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

use App\Http\Model\Program\ProgramTask;
use App\Http\Service\Program\ProgramTaskMemberService;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 项目任务
 * Class ProgramTaskDao.
 */
class ProgramTaskDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;
    use TogetherSearchTrait;

    /**
     * 搜索.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null): mixed
    {
        if (isset($where['time'])) {
            if (in_array($where['time_field'], ['plan_start', 'plan_end', 'created_at'])) {
                $where[$where['time_field']] = $where['time'];
            }
            unset($where['time_field'], $where['time']);
        }

        if (isset($where['pid']) && $where['pid'] > 0 & isset($where['types'])) {
            unset($where['types'], $where['admin_uid'], $where['uid_or_creator_uid']);
        }

        $members = $where['members'] ?? null;
        if (isset($where['members'])) {
            unset($where['members']);
        }

        $admins = $where['admins'] ?? null;
        if (isset($where['admins'])) {
            unset($where['admins']);
        }

        $types = $where['types'] ?? null;
        if (isset($where['types'])) {
            unset($where['types']);
        }
        $adminUid = $where['admin_uid'] ?? 0;
        if (isset($where['admin_uid'])) {
            unset($where['admin_uid']);
        }

        $uid = $where['uid'] ?? 0;
        if ($types < 1 && isset($where['uid'])) {
            unset($where['uid']);
        }

        $memberService = app()->get(ProgramTaskMemberService::class);
        return parent::search($where, $authWhere)
            ->when(!is_null($types), function ($query) use ($adminUid, $types, $uid, $memberService) {
                $query->where(function ($query) use ($adminUid, $types, $uid, $memberService) {
                    // 0：全部；1：负责；2：参与；3：创建；
                    switch ($types) {
                        case 0:
                            $query->whereIn('uid', $uid)->orWhereIn('program_id', fn($q) => $q->from('program')->where('uid', $adminUid)->orWhere('creator_uid', $adminUid)->select('id'));
                            break;
                        case 2:
                            $query->where(function ($query) use ($adminUid, $memberService) {
                                $query->where('uid', $adminUid)->orWhereIn('id', $memberService->column(['uid' => $adminUid], 'task_id'));
                            });
                            break;
                        case 3:
                            $query->where('creator_uid', $adminUid);
                            break;
                        default:
                            $query->where('uid', $adminUid);
                            break;
                    }
                });
            })->when($members, function ($query) use ($members, $memberService) {
                $query->whereIn('id', $memberService->column(['uid' => $members], 'task_id'));
            })->when($admins, function ($query) use ($admins) {
                if (is_array($admins)) {
                    $query->whereIn('uid', $admins);
                } else {
                    $query->where('uid', $admins);
                }
            });
    }

    protected function setModel(): string
    {
        return ProgramTask::class;
    }
}
