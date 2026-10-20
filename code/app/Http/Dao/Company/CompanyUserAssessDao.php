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

namespace App\Http\Dao\Company;

use App\Constants\AssessEnum;
use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Model\Company\UserAssess;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Assess\AssessScoreService;
use App\Http\Service\Assess\AssessSpaceService;
use App\Http\Service\Assess\AssessTargetService;
use App\Http\Service\Assess\UserAssessScoreService;
use App\Http\Service\Frame\FrameService;
use App\Task\message\StatusChangeTask;
use App\Task\message\UserAssessEndRemind;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\GroupDateSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;

/**
 * 企业用户绩效考核记录表
 * Class CompanyUserAssessDao.
 */
class CompanyUserAssessDao extends BaseDao
{
    use BatchSearchTrait;
    use GroupDateSearchTrait;
    use TogetherSearchTrait;

    /**
     * 获取考核列表.
     *
     * @param array|string[] $field
     * @param null $sort
     *
     * @return Builder
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getUserAssessMode(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        $status     = $where['status'] ?? '';
        $types      = $where['type'] ?? '';
        $handle     = (int) ($where['handle'] ?? 0) === 1 ? 1 : '';
        $orCheckUid = isset($where['or_check_uid']) && $where['or_check_uid'] !== '' ? (int) $where['or_check_uid'] : 0;
        $testUids   = $where['test_uid'] ?? null;
        $filterTestUids = $where['filter_test_uid'] ?? [];
        if (isset($where['date']) && $where['date']) {
            $date = $where['date'];
            unset($where['time'], $where['date']);
        } else {
            $date = '';
        }
        unset($where['type'], $where['status'], $where['verify'], $where['handle'], $where['or_check_uid'], $where['filter_test_uid']);
        // 数据权限 ∪ 考核人：由下方 where 闭包处理，避免 search 先 AND 再 OR
        if ($orCheckUid) {
            unset($where['test_uid']);
        }

        return $this->setTimeField('start_time')->search($where)->select($field)
            ->when($orCheckUid, function ($query) use ($orCheckUid, $testUids) {
                $query->where(function ($query) use ($orCheckUid, $testUids) {
                    $hasScope = is_array($testUids) ? count($testUids) > 0 : ($testUids !== null && $testUids !== '');
                    if ($hasScope) {
                        $query->where(function ($query) use ($testUids) {
                            is_array($testUids)
                                ? $query->whereIn('test_uid', $testUids)
                                : $query->where('test_uid', $testUids);
                        })->orWhere('check_uid', $orCheckUid);
                    } else {
                        $query->where('check_uid', $orCheckUid);
                    }
                });
            })->when($filterTestUids, function ($query) use ($filterTestUids) {
                // 人员筛选必须 AND 可见范围，不能参与数据权限或考核职责的并集。
                $query->whereIn('test_uid', $filterTestUids);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->when($sort = sort_mode($sort), function ($query) use ($sort) {
                if (is_array($sort)) {
                    foreach ($sort as $k => $v) {
                        if (is_numeric($k)) {
                            $query->orderByDesc($v);
                        } else {
                            $query->orderBy($k, $v);
                        }
                    }
                } else {
                    $query->orderByDesc($sort);
                }
            })->when($types !== '' && $handle !== '', function ($query) use ($types) {
                switch ((int) $types) {
                    case 1:// 评价待处理
                        $query->where('status', 2)->where('is_show', 1);
                        break;
                    case 2:// 审核待处理
                        $query->where('status', 3)->where('is_show', 1);
                        break;
                    default:// 自评待处理
                        $query->where('status', 1)->where('is_show', 1);
                }
            })->when($status !== '', function ($query) use ($status) {
                if (! is_array($status)) {
                    $status = [$status];
                }
                $query->where(function ($query) use ($status) {
                    foreach ($status as $item) {
                        $query->OrWhere(function ($query) use ($item) {
                            switch ((int) $item) {
                                case 0:// 目标制定期
                                    $query->where('status', $item);
                                    break;
                                case 5:// 未开始
                                    $query->where('start_time', '>', now()->toDateTimeString());
                                    break;
                                case -1:
                                    $query->where('make_time', '<', now()->toDateTimeString())->where('make_status', 0);
                                    break;
                                default:
                                    $query->where('status', $item)->where('start_time', '<', now()->toDateTimeString());
                            }
                        });
                    }
                });
            })->when($date !== '', function ($query) use ($date) {
                $query->whereDate('start_time', $date);
            })->with($with);
    }

    /**
     * 获取用户绩效考核列表.
     *
     * @param array|string[] $field
     * @param null $sort
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getUserAssessList(array $where, array $userIds, int $userId, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        $scoreService = app()->get(AssessScoreService::class);
        $data         = $this->getUserAssessMode($where, $field, $page, $limit, $sort, $with)->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = $scoreService->value(['level' => $item['grade'], 'entid' => $item['entid']], 'name') ?? '无';
            } else {
                $item['level'] = '暂无';
                $item['score'] = '未评分';
            }
        }
        unset($item);
        $count = $this->getUserAssessMode($where, $userIds, $userId)->count();

        return [$data, $count];
    }

    /**
     * 获取详情.
     *
     * @param mixed $id
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getAssessInfo($id)
    {
        $assessInfo = $this->get($id, ['*'], [
            'frame', 'check', 'test',
        ]);
        if (! $assessInfo) {
            return [];
        }
        $assessInfo = $assessInfo->toArray();
        if (datetime_timestamp($assessInfo['start_time']) > time()) {
            $assessInfo['status'] = 5;
        }

        if ($assessInfo['status'] > 2) {
            $assessInfo['level'] = app()->get(AssessScoreService::class)->value(['level' => $assessInfo['grade'], 'entid' => $assessInfo['entid']], 'name') ?? '无';
        } else {
            $assessInfo['level'] = '暂无';
            $assessInfo['score'] = '未评分';
        }
        $assessInfo['verify'] = $this->getVerifyInfo($assessInfo['test_uid']);

        return $assessInfo;
    }

    /**
     * 计算考核分数.
     *
     * @param int $verify 是否为绩效审核/上级评价
     * @param mixed $uid
     * @param mixed $id
     *
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateAssessScore($id, $verify = 0, $uid = 0)
    {
        $targetService = app()->get(AssessTargetService::class);
        $scoreService  = app()->get(AssessScoreService::class);
        $spaces        = app()->get(AssessSpaceService::class)->select(['assessid' => $id], ['id', 'ratio'])->toArray() ?? [];
        $total         = 0;
        $info          = toArray($this->get(['id' => $id], ['test_uid', 'name', 'types', 'entid', 'status', 'total', 'id']));
        $name          = $info['name'] ?? '';
        unset($info['name']);
        if ($info['types']) {
            foreach ($spaces as $space) {
                $targetIds = $targetService->select(['spaceid' => $space['id']], ['ratio', 'score'])->toArray() ?? [];
                foreach ($targetIds as $target) {
                    $total = bcadd((string) $total, (string) $target['score'], 2);
                }
            }
        } else {
            foreach ($spaces as $space) {
                $targetIds = $targetService->select(['spaceid' => $space['id']], ['ratio', 'score'])->toArray() ?? [];
                $score     = 0;
                foreach ($targetIds as $target) {
                    $score = bcadd((string) $score, bcmul((string) $target['ratio'], (string) $target['score'], 2), 2);
                }
                $total = bcadd((string) $total, bcmul((string) $space['ratio'], (string) $score, 2), 2);
            }
            $total = bcdiv(bcmul((string) $total, (string) $info['total']), '1000000', 2);
        }
        $grade = $scoreService->getScoreLevel($info['entid'], $total);
        $edit  = [
            'score' => $total,
            'grade' => $grade,
            'total' => $info['total'],
        ];
        $res = false;
        if (in_array($info['status'], [AssessEnum::ASSESS_EVALUATION, AssessEnum::ASSESS_VERIFY, AssessEnum::ASSESS_FINISH])) {
            if ($verify) {// 绩效审核
                $edit['verify_status'] = 1;
                $edit['status']        = $info['status'] < 4 ? $info['status'] + 1 : $info['status'];
                $res                   = $this->search(['id' => $id])->update($edit);
                if ($res) {
                    // 考核结束提醒给上级提醒和给自己提醒分数
                    Task::deliver(new UserAssessEndRemind($info['entid'], $info['test_uid'], $id));
                }
            } else {
                $edit['check_status'] = 1;
                $edit['status']       = $info['status'] < 4 ? $info['status'] + 1 : $info['status'];
                $res                  = $this->search(['id' => $id])->update($edit);
                if ($res) {
                    // 上级评价发送提醒给自己分数
                    event(new SystemMessageEvent(
                        type: NoticeEnum::ASSESS_EVALUATE_RESULT_TYPE,
                        params: [
                            '考核名称'     => $info['name'],
                            '上级评价得分' => $total,
                        ],
                        receiverIds: (int) $info['test_uid'],
                        other: ['id' => $id],
                        linkId: $id,
                        linkStatus: $info['status']
                    ));
                    Task::deliver(new StatusChangeTask(AssessEnum::LINK_NOTICE, AssessEnum::ASSESS_VERIFY, 1, $id));
                }
            }
        }
        app()->get(UserAssessScoreService::class)->createOrSave([
            'entid'    => $info['entid'],
            'assessid' => $id,
            'userid'   => $uid,
            'score'    => $total,
            'grade'    => $grade,
            'total'    => $info['total'],
        ]);

        return $res;
    }

    /**
     * 分组统计
     *
     * @param int $page
     * @param int $limit
     * @param mixed $with
     * @param mixed $field
     * @param mixed $where
     * @param mixed $group
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getGroupList($where, $group, $page = 1, $limit = 0, $with = [], $field = ['*'])
    {
        $status = $where['status'];
        unset($where['status']);

        $data = $this->search($where)->select($field)->when($status !== '', function ($query) use ($status) {
            switch ((int) $status) {
                case 0:// 目标制定期
                    $query->where('make_time', '>', now()->toDateTimeString())->where('make_status', '<>', 1);
                    break;
                case 5:// 未开始
                    $query->where('start_time', '>', now()->toDateTimeString())->where('make_status', 1);
                    break;
                case -1:
                    $query->where('make_time', '<', now()->toDateTimeString())->where('make_status', 0);
                    break;
                default:
                    $query->where('status', $status);
            }
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->when($group, function ($query) use ($group) {
            $query->groupBy($group);
        })->with($with)->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = app()->get(AssessScoreService::class)->value(['level' => $item['grade'], 'entid' => $item['entid']], 'name') ?? '无';
            } else {
                $item['level'] = '暂无';
                $item['score'] = '未评分';
            }
        }
        unset($item);

        return $data;
    }

    /**
     * 分组统计数量.
     *
     * @param mixed $where
     * @param mixed $group
     * @return int
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getGroup($where, $group)
    {
        unset($where['status']);

        return $this->search($where)->get()->groupBy($group)->count();
    }

    /**
     * 资金记录时间查询.
     *
     * @param mixed $where
     * @param mixed $group
     * @param mixed $time
     * @param mixed $timeType
     * @param mixed $str
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function getTrend($time, $timeType, $str, $where = [], $group = 'days')
    {
        return $this->search($where)
            ->where(function ($query) use ($time) {
                if ($time[0] == $time[1]) {
                    $query->whereDate('start_time', $time[0]);
                } else {
                    $query->whereBetween('start_time', $time);
                }
            })
            ->selectRaw("DATE_FORMAT(start_time,'{$timeType}') as '{$group}',{$str} as grade")
            ->groupBy($group)
            ->get()->toArray();
    }

    /**
     * TODO 获取审核人信息.
     *
     * @param mixed $uid
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getVerifyInfo($uid)
    {
        $verifyUid = app()->get(FrameService::class)->getLevelSuper($uid, 2);
        if ($verifyUid) {
            $verify = app()->get(AdminService::class)->get($verifyUid, ['uid', 'id', 'name', 'avatar']);
        } else {
            $verify = [];
        }

        return $verify;
    }

    /**
     * 获取定时任务中的考核任务
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getTimerUserAssessList(array $where, array $field = [], int $page = 0, int $limit = 0, array $with = [])
    {
        return $this->search($where)->select($field)
            ->when($page && $limit, fn ($q) => $q->forPage($page, $limit))
            ->when(! $page && $limit, fn ($q) => $q->limit($limit))
            ->with($with)->get()->toArray();
    }

    /**
     * 根据用户获取近一年内最后一个考核.
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function getUserAssessAbnormalList(array $where)
    {
        return $this->getModel(false)
            ->whereIn('test_uid', $where['test_uid'])
            ->where('entid', $where['entid'])
            ->where('status', 1)
            ->where('period', $where['period'])
            ->whereBetween('created_at', [now()->startOfYear()->toDateTimeString(), now()->endOfYear()->toDateTimeString()])
            ->orderBy('id')
            ->groupBy('test_uid')
            ->with([
                'userEnt',
                'frame',
            ])
            ->select(['id', 'test_uid', 'name', 'period', 'frame_id', 'planid', 'created_at'])
            ->get()
            ->toArray();
    }

    /**
     * 获取考核人员的企业id.
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function getUserAssessGroupEntList(array $where)
    {
        return $this->getModel(false)
            ->whereIn('status', $where['status'])
            ->where('intact', $where['intact'])
            ->groupBy('entid')
            ->select(['entid'])
            ->get()
            ->toArray();
    }

    /**
     * 设置模型.
     *
     * @return string
     */
    protected function setModel()
    {
        return UserAssess::class;
    }
}
