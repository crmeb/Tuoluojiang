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

namespace App\Http\Service\Assess;

use App\Constants\AssessEnum;
use App\Constants\CacheEnum;
use App\Constants\NoticeEnum;
use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use App\Events\SystemMessageEvent;
use App\Http\Dao\Company\CompanyUserAssessDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\Other\TaskService;
use App\Http\Service\User\UserRemindLogService;
use App\Task\message\UserAssessAppealRemind;
use App\Task\message\UserAssessAppealResRemind;
use App\Task\message\UserAssessEndRemind;
use App\Task\message\UserAssessReleaseRemind;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\options\TaskOptions;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业用户绩效记录
 * Class UserAssessService.
 *
 * @method updateAssessScore($id, $is_draft, $verify = 0, $uid = 0) 计算考核分数
 * @method getUserAssessAbnormalList(array $where)
 * @method getTimerUserAssessList(array $where, array $field = [], int $page = 0, int $limit = 0, array $with = [])
 */
class UserAssessService extends BaseService
{
    /**
     * UserAssessService constructor.
     *
     * @throws BindingResolutionException
     */
    public function __construct(CompanyUserAssessDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取企业最高分数.
     * @throws BindingResolutionException
     */
    public function getMaxScore(): int
    {
        return (int) app()->get(AssessScoreService::class)->max([], 'max');
    }

    /**
     * 获取维度计算方式.
     */
    public function getComputeMode(): int
    {
        return intval(sys_config('assess_compute_mode', 0));
    }

    /**
     * 绩效考核本年统计图.
     *
     * @return mixed
     */
    public function getUserAssessStatistics(int $timeType)
    {
        return $this->dao->getDateGroupList($timeType, 'created_at', ['status' => 1, 'created_at_year' => now()->get('year')], ['id', 'score']);
    }

    /**
     * 获取考核人员的企业分组.
     *
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function getUserAssessEntListCache(array $where)
    {
        return Cache::tags([CacheEnum::TAG_FRAME])->remember(
            'user_assess_entid_list',
            (int) sys_config('system_cache_ttl', 3600),
            fn () => $this->dao->getUserAssessGroupEntList($where),
        );
    }

    /**
     * 获取绩效其他信息.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getAssessExplain(int $id, string $uuid, int $entId)
    {
        $assessInfo = $this->dao->get($id);
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        $assessInfo = $assessInfo->toArray();
        if ($assessInfo['entid'] != $entId) {
            throw $this->exception('您暂无权限查看该考核记录');
        }
        $uid          = uuid_to_uid($uuid, $entId);
        $replyService = app()->get(AssessReplyService::class);
        $field        = ['user_id', 'content', 'types', 'status', 'updated_at'];
        $reply        = $replyService->select(['assessid' => $assessInfo['id'], 'is_own' => 0], $field, ['user']) ?? [];
        if ($assessInfo['check_uid'] == $uid) {
            $mark = $replyService->get(['assessid' => $assessInfo['id'], 'is_own' => 1], $field, ['user']) ?? [];
        } else {
            $mark = [];
        }
        $explain = sys_config('assess_score_mark', '');
        $appeal  = $replyService->get(['assessid' => $assessInfo['id'], 'is_own' => 0, 'types' => 1, 'status' => 0], $field, ['user'], 'id') ?? [];

        return compact('reply', 'explain', 'appeal', 'mark');
    }

    /**
     * TODO 我的当前绩效考核.
     *
     * @param mixed $uid
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getNowAssess($uid)
    {
        $scoreList = $this->getScoreLevel();
        $where     = [
            'test_uid' => uuid_to_uid($uid),
            'entid'    => 1,
            'status'   => 1,
            'end_time' => now()->toDateTimeString(),
            'is_show'  => 1,
        ];

        $data = $this->dao->getUserAssessMode($where)->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = isset($scoreList) && isset($scoreList[$item['grade']]) ? $scoreList[$item['grade']] : '无';
            } else {
                $item['level'] = '--';
                $item['score'] = '--';
            }
        }
        unset($item);

        return $data;
    }

    /**
     * 我的当前及上月绩效考核.
     *
     * @param mixed $uid
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getUserAssessData($uid)
    {
        $scoreList = $this->getScoreLevel();
        $where     = [
            'test_uid'   => uuid_to_uid($uid),
            'start_time' => now()->subMonths(1)->startOfMonth()->toDateTimeString(),
            'end_time'   => now()->endOfMonth()->toDateTimeString(),
            'is_show'    => 1,
        ];

        $data = $this->dao->getUserAssessMode($where, ['*'], 0, 0, ['id' => 'asc'])->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = isset($scoreList) && isset($scoreList[$item['grade']]) ? $scoreList[$item['grade']] : '无';
            } else {
                $item['level'] = '--';
                $item['score'] = '--';
            }
        }
        unset($item);

        return $data;
    }

    /**
     * TODO 我的绩效考核列表.
     *
     * @param mixed $uid
     * @param mixed $where
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getMineAssess($where, $uid)
    {
        [$page, $limit] = $this->getPageValue();
        $scoreList      = $this->getScoreLevel();
        $data           = $this->dao->getUserAssessMode($where + ['test_uid' => $uid, 'entid' => 1, 'is_show' => 1], ['*'], $page, $limit, 'id', ['test', 'check'])->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = isset($scoreList) && isset($scoreList[$item['grade']]) ? $scoreList[$item['grade']] : '无';
            } else {
                $item['level'] = '--';
                $item['score'] = '--';
            }
            $item['verify'] = $this->dao->getVerifyInfo($uid);
        }
        unset($item);
        $count = $this->dao->getUserAssessMode($where + ['test_uid' => $uid, 'entid' => 1, 'is_show' => 1])->count();

        return $this->listData($data, $count);
    }

    /**
     * TODO 获取下级考核列表.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getSubAssess(array $where, string $uid)
    {
        $uids = app()->get(FrameService::class)->subUserInfo($uid, 1, withAdmin: true, withSelf: true);
        if ($uids) {
            $scoreList = $this->getScoreLevel();
            if (! $where['test_uid']) {
                $where['test_uid'] = $uids;
            }
            [$page, $limit] = $this->getPageValue();
            $data           = $this->dao->getUserAssessMode($where, ['*'], $page, $limit, ['start_time', 'id'], ['frame', 'test'])->get()->toArray();
            foreach ($data as &$item) {
                if ($item['status'] > 2) {
                    $item['level'] = isset($scoreList) && isset($scoreList[$item['grade']]) ? $scoreList[$item['grade']] : '无';
                } else {
                    $item['level'] = '--';
                    $item['score'] = '--';
                }
                if (datetime_timestamp($item['start_time']) > time()) {
                    $item['status'] = 5;
                }
                // TODO 审核人信息
                $item['verify'] = $this->dao->getVerifyInfo($item['test_uid'], $item['entid']);
            }
            unset($item);
            $count = $this->dao->getUserAssessMode($where)->count();
        } else {
            $data  = [];
            $count = 0;
        }

        return $this->listData($data, $count);
    }

    /**
     * 获取审核考核列表.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getVerifyAssess(array $where, int $uid)
    {
        $uids = app()->get(FrameService::class)->getLevelSub($uid, 2);
        if ($uids) {
            $where['test_uid'] = $where['test_uid'] ?: $uids;
            [$page, $limit]    = $this->getPageValue();
            $scoreList         = $this->getScoreLevel();
            $data              = $this->dao->getUserAssessMode($where, ['*'], $page, $limit, 'start_time', ['frame', 'test'])->get()->toArray();
            foreach ($data as &$item) {
                if (datetime_timestamp($item['start_time']) > time()) {
                    $item['status'] = 5;
                }
                if ($item['status'] > 2) {
                    $item['level'] = $scoreList[$item['grade']] ?? '无';
                } else {
                    $item['level'] = '--';
                    $item['score'] = '--';
                }
                //                    $item['check'] = $frameService->getUpUser($item['test_uid'], $item['entid']);
            }
            unset($item);
            $count = $this->dao->getUserAssessMode($where)->count();
        } else {
            $data  = [];
            $count = 0;
        }

        return $this->listData($data, $count);
    }

    /**
     * TODO 人事获取考核列表.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getAssessList(array $where)
    {
        $scoreList      = $this->getScoreLevel();
        [$page, $limit] = $this->getPageValue();
        $data           = $this->dao->getUserAssessMode($where, ['*'], $page, $limit, ['start_time', 'id'], [
            'frame', 'test', 'check',
        ])->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = isset($scoreList) && isset($scoreList[$item['grade']]) ? $scoreList[$item['grade']] : '无';
            } else {
                $item['level'] = '--';
                $item['score'] = '--';
            }
            //                $item['check']  = $frameService->getUpUser($item['test_uid'], $item['entid']);
            $item['verify'] = $this->dao->getVerifyInfo($item['test_uid'], $item['entid']);
        }
        unset($item);
        $count = $this->dao->getUserAssessMode($where)->count();

        return $this->listData($data, $count);
    }

    /**
     * 获取考核分数统计
     *
     * @param mixed $where
     * @param mixed $uid
     * @param mixed $entId
     * @return array
     * @throws BindingResolutionException
     */
    public function getAssessStatistics($where, $uid, $entId)
    {
        $testUid                       = [];
        $where['test_uid'] && $testUid = is_array($where['test_uid'])
            ? array_filter(array_map('intval', $where['test_uid'])) : (int) $where['test_uid'];

        if ($testUid) {
            $where['test_uid'] = $testUid;
        } else {
            $where['test_uid'] = app()->get(AdminService::class)->column(['status' => 1], 'id');
        }
        $score = app()->get(AssessScoreService::class)->select(['entid' => $entId], ['min', 'max', 'name', 'level', 'mark'])->toArray();
        $uid   = uuid_to_uid($uid, $entId);
        switch ($where['types']) {
            case 2:
                break;
            case 1:
                $where['check_uid'] = $uid;
                break;
            default:
                $where['test_uid'] = $uid;
                unset($where['frame_id']);
        }
        unset($where['types']);
        $count = 0;
        if (is_array($where['time'])) {
            $where['time'] = implode('-', $where['time']);
        }
        foreach ($score as &$v) {
            $v['count'] = $this->dao->setTimeField('start_time')->count($where + ['grade' => $v['level']]);
            $count      = bcadd((string) $count, (string) $v['count']);
        }
        unset($where['frame_id']);
        $frame_ids = $this->dao->column($where, 'frame_id');
        $frame     = app()->get(FrameService::class)->select(['ids' => $frame_ids], ['id', 'name'], ['user']);

        return compact('score', 'frame', 'count');
    }

    /**
     * 获取考核分数统计
     *
     * @param mixed $where
     * @param mixed $uid
     * @param mixed $entId
     * @return array
     * @throws BindingResolutionException
     */
    public function getAssessCensusLine($where, $uid, $entId)
    {
        $adminService = app()->get(AdminService::class);
        $uids = [];
        switch ($where['types']) {
            case 2:// 人事
                $uids = array_values(array_filter(array_map('intval', (array) ($where['uid'] ?? []))));
                unset($where['frame_id']);
                break;
            case 1:// 下级
                $frameService = app()->get(FrameService::class);
                if ($where['frame_id']) {
                    $uids = $frameService->scopeUser((int) $where['frame_id']);
                } else {
                    $uids = $frameService->subUserInfo($uid, $entId, false, withAdmin: true);
                }
                unset($where['frame_id']);
                break;
            default:// 自己
                $uids = [uuid_to_uid($uid, $entId)];
        }
        if (strlen($where['end']) == 4) {
            $start = $startTime = Carbon::parse($where['start'] . '0101')->timezone(config('app.timezone'))->startOfYear()->timestamp;
            $end   = $endTime = Carbon::parse($where['end'] . '0101')->timezone(config('app.timezone'))->addYear()->startOfYear()->timestamp;
        } else {
            $start = $startTime = Carbon::parse($where['start'])->timezone(config('app.timezone'))->timestamp;
            $end   = $endTime = Carbon::parse($where['end'])->timezone(config('app.timezone'))->timestamp;
        }
        $dayCount = ($endTime - $startTime) / 86400 + 1;
        $timeType = '%H';
        switch ($where['period']) {
            case 1:// 周考核
                $num = $dayCount / 7;
                if (! Carbon::parse($startTime)->timezone(config('app.timezone'))->isMonday()) {
                    $start = $startTime = Carbon::parse($startTime)->timezone(config('app.timezone'))->addWeek()->timestamp;
                    $end   = $endTime = Carbon::parse($endTime)->timezone(config('app.timezone'))->addWeek()->timestamp;
                }
                while ($startTime < $endTime) {
                    if ($num <= 53) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y') . '-' . Carbon::parse($startTime)->timezone(config('app.timezone'))->weekOfYear;
                        $startTime = strtotime('+1 week', $startTime);
                        $timeType  = '%Y-%u';
                    } else {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y') . '-' . Carbon::parse($startTime)->timezone(config('app.timezone'))->weekOfYear;
                        $startTime = strtotime('+3 week', $startTime);
                        $timeType  = '%Y-%u';
                    }
                }
                $group = 'weeks';
                break;
            case 2:// 月考核
                $num = $dayCount / 30;
                while ($startTime < $endTime) {
                    if ($num <= 53) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y-m');
                        $startTime = strtotime('+1 month', $startTime);
                        $timeType  = '%Y-%m';
                    } else {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y-m');
                        $startTime = strtotime('+2 month', $startTime);
                        $timeType  = '%Y-%m';
                    }
                }
                $group = 'month';
                break;
            case 3:// 年考核
                while ($startTime < $endTime) {
                    $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y');
                    $startTime = strtotime('+1 year', $startTime);
                    $timeType  = '%Y';
                }
                $group = 'year';
                break;
            case 4:// 半年考核
                $num = $dayCount / 90;
                while ($startTime < $endTime) {
                    if ($num <= 24) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y') . '年' . (Carbon::parse($startTime)->timezone(config('app.timezone'))->format('m') > 6 ? '下半年' : '上半年');
                        $startTime = Carbon::parse($startTime)->addMonths(6)->timestamp;
                        $timeType  = '%Y-%m';
                    } else {
                        $xAxis[]   = date('Y-m', $startTime);
                        $startTime = strtotime('+2 month', $startTime);
                        $timeType  = '%Y-%m';
                    }
                }
                $group = 'month';
                break;
            case 5:// 季度考核
                while ($startTime < $endTime) {
                    $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y') . '年第' . Carbon::parse($startTime)->timezone(config('app.timezone'))->quarter . '季度';
                    $startTime = Carbon::parse($startTime)->addQuarter()->timestamp;
                    $timeType  = '%Y-%m';
                }
                $group = 'quarter';
                break;
        }
        $data = $series = $income = [];
        if ($uids) {
            foreach ($uids as $v) {
                $income[$v] = [];
                $startDate  = Carbon::parse($start)->timezone(config('app.timezone'))->toDateTimeString();
                $endDate    = Carbon::parse($end)->timezone(config('app.timezone'))->toDateTimeString();
                $info       = $this->dao->getTrend([$startDate, $endDate], $timeType, 'score', ['test_uid' => $v, 'period' => $where['period']], $group);
                switch ($where['period']) {
                    case 5:
                    case 4:
                        foreach ($info as $inf) {
                            $key        = $this->getCensusKey($inf[$group], $where['period']);
                            $income[$v] = [$key => $inf['grade']];
                        }
                        break;
                    default:
                        $income[$v] = array_column($info, 'grade', $group);
                }
            }
        }
        foreach ($xAxis as $item) {
            foreach ($income as $key => $v) {
                $data[$key][] = isset($v[$item]) ? floatval($v[$item]) : 0;
            }
        }
        foreach ($data as $key => $value) {
            $series[] = [
                'name'   => ($info = $adminService->get(['id' => $key], ['*'], ['card'])) ? $info->card->name : '',
                'data'   => $value,
                'type'   => 'line',
                'smooth' => 'true',
            ];
        }

        return compact('series', 'xAxis');
    }

    /**
     * 资金趋势
     *
     * @param mixed $where
     * @param mixed $excel
     * @return array
     */
    public function getTrend($where, $excel = false)
    {
        $time = explode('-', $where['time']);
        if (count($time) != 2) {
            throw $this->exception('参数错误');
        }
        $dayCount = (datetime_timestamp($time[1]) - datetime_timestamp($time[0])) / 86400 + 1;
        $data     = [];
        if ($dayCount == 1) {
            $data = $this->trend($time, 0, $excel);
        } elseif ($dayCount > 1 && $dayCount <= 31) {
            $data = $this->trend($time, 1, $excel);
        } elseif ($dayCount > 31 && $dayCount <= 92) {
            $data = $this->trend($time, 3, $excel);
        } elseif ($dayCount > 92) {
            $data = $this->trend($time, 30, $excel);
        }

        return $data;
    }

    /**
     * 资金趋势
     *
     * @param false $excel
     * @param mixed $time
     * @param mixed $num
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function trend($time, $num, $excel = false)
    {
        if ($num == 0) {
            $xAxis    = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
            $timeType = '%H';
        } elseif ($num != 0) {
            $dt_start = datetime_timestamp($time[0]);
            $dt_end   = datetime_timestamp($time[1]);
            while ($dt_start <= $dt_end) {
                if ($num == 30) {
                    $xAxis[]  = date('Y-m', $dt_start);
                    $dt_start = strtotime('+1 month', $dt_start);
                    $timeType = '%Y-%m';
                } else {
                    $xAxis[]  = date('m-d', $dt_start);
                    $dt_start = strtotime("+{$num} day", $dt_start);
                    $timeType = '%m-%d';
                }
            }
        }
        $income = array_column($this->dao->getTrend($time, $timeType, 'count(grade)', 1), 'num', 'days');
        $expend = array_column($this->dao->getTrend($time, $timeType, 'sum(num)', 0), 'num', 'days');
        $data   = $series = [];
        foreach ($xAxis as $item) {
            $data['收入金额'][] = isset($income[$item]) ? floatval($income[$item]) : 0;
            $data['支出金额'][] = isset($expend[$item]) ? floatval($expend[$item]) : 0;
        }
        foreach ($data as $key => $item) {
            if ($key == '消费金额' || $key == '充值金额' || $key == '系统增加' || $key == '系统扣除') {
                $series[] = [
                    'name'   => $key,
                    'data'   => $item,
                    'type'   => 'line',
                    'smooth' => 'true',
                ];
            } else {
                $series[] = [
                    'name'       => $key,
                    'data'       => $item,
                    'type'       => 'bar',
                    'yAxisIndex' => 1,
                ];
            }
        }
        $incomeRank = $this->dao->getBillRank($time, 1, [
            'cate' => function ($query) {
                $query->select(['id', 'name']);
            }, ], $this->dao->getSum($time, 1));
        $expendRank = $this->dao->getBillRank($time, 0, [
            'cate' => function ($query) {
                $query->select(['id', 'name']);
            }, ], $this->dao->getSum($time, 0));

        return compact('xAxis', 'series', 'incomeRank', 'expendRank');
    }

    /**
     * 获取考核分组统计
     *
     * @param mixed $where
     * @param mixed $uid
     * @param mixed $entId
     * @return array
     * @throws BindingResolutionException
     */
    public function getAssessGroup($where, $uid, $entId)
    {
        [$page, $limit] = $this->getPageValue();
        $count          = $this->dao->getGroup($where, 'number');
        $list           = $this->dao->setTimeField('start_time')->getGroupList(
            $where,
            'number',
            $page,
            $limit,
            ['planInfo'],
            [DB::raw('count(*) as count'), 'number', 'planid', 'make_status', 'make_time', 'test_status', 'start_time', 'end_time', 'check_end', 'verify_status', 'verify_time']
        );

        return $this->listData($list, $count);
    }

    /**
     * 获取考核详情.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAssessInfo(int $id, int $entId)
    {
        $assessInfo = $this->dao->getAssessInfo($id);
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        if ($assessInfo['entid'] != $entId) {
            throw $this->exception('您暂无权限查看该考核记录');
        }
        $info = app()->get(AssessSpaceService::class)->getAssess($id);
        if (! $info) {
            throw $this->exception('考核内容不存在');
        }
        $info              = $info->toArray();
        $assessInfo['max'] = app()->get(AssessScoreService::class)->max(['entid' => $entId], 'max');
        $explain           = sys_config('assess_score_mark', '');
        return compact('assessInfo', 'info', 'explain');
    }

    /**
     * TODO 考核目标制定保存.
     *
     * @param mixed $entid
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function saveAssess(int $uid, array $data, int $entid = 1)
    {
        $is_draft = $data['is_draft']; // 草稿
        $is_temp  = $data['is_temp'];  // 是否为模板
        $info     = $data['info'];     // 模板简介
        $name     = $data['name'];     // 模板名称
        $test_uid = $data['test_uid']; // 被考核人
        $period   = $data['period'];   // 周期
        $time     = $data['time'];     // 考核时间
        $types    = $data['types'];    // 记分方式
        $data     = $data['data'];     // 考核目标
        if ($is_temp) {
            if (! $name) {
                throw $this->exception('请填写模板名称');
            }

            return $this->transaction(function () use ($entid, $uid, $name, $info, $types, $data, $is_temp) {
                $tempInfo = app()->get(AssessTemplateService::class)->create([
                    'entid'   => $entid,
                    'user_id' => $uid,
                    'name'    => $name,
                    'info'    => $info,
                    'types'   => $types,
                ]);

                return $this->saveAssessInfo($data, $tempInfo->id, $entid, $is_temp, 0);
            });
        } else {
            if ($period == 3) {
                $year = $time;
            } else {
                [$year, $week] = explode('-', $time);
            }
            if (! $test_uid) {
                throw $this->exception('保存失败，请选择被考核人');
            }
            $uids = app()->get(FrameService::class)->getLevelSub($uid);
            foreach ($test_uid as $v) {
                if (! in_array($v, $uids)) {
                    throw $this->exception('保存失败，被考核人必须为下级用户');
                }
            }
            $planInfo      = app()->get(AssessPlanService::class)->get(['entid' => $entid, 'period' => $period]);
            $moreYear      = abs($year - intval(date('Y', time())));
            $assistService = app()->get(FrameAssistService::class);
            foreach ($test_uid as $v) {
                $save              = $spaceIds = $targetIds = [];
                $save['test_uid']  = $v;
                $save['check_uid'] = $uid;
                $save['entid']     = $entid;
                $save['period']    = $period;
                $save['types']     = $types;
                $save['frame_id']  = $assistService->value(['user_id' => $v, 'is_mastart' => 1], 'frame_id');
                switch ($period) {
                    case 1:// 周考核
                        [$year, $week] = explode('-', $time);
                        $save['name']  = $year . '年第' . $week . '周考核';
                        if ($year >= intval(date('Y', time()))) {
                            if (intval(date('W', time())) > $week) {
                                $save['start_time'] = now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek(), false);
                            } elseif (intval(date('W', time())) < $week) {
                                $save['start_time'] = now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek(), false);
                            } else {
                                $save['start_time'] = now()->addYears($moreYear)->startOfWeek()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->endOfWeek()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfWeek());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfWeek(), false);
                            }
                            $save['check_end']   = $check_end->toDateTimeString();
                            $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        } else {
                            if (intval(date('W', time())) > $week) {
                                $save['start_time'] = now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek(), false);
                            } elseif (intval(date('W', time())) < $week) {
                                $save['start_time'] = now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek(), false);
                            } else {
                                $save['start_time'] = now()->subYears($moreYear)->startOfWeek()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->endOfWeek()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfWeek());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfWeek(), false);
                            }
                            $save['check_end']   = $check_end->toDateTimeString();
                            $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        }
                        break;
                    case 2:// 月考核
                        [$year, $month] = explode('-', $time);
                        $save['name']   = $year . '年' . $month . '月考核';
                        if ($year >= intval(date('Y', time()))) {
                            if (intval(date('m', time())) < $month) {
                                $save['start_time'] = now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth(), false);
                            } elseif (intval(date('m', time())) == $month) {
                                $save['start_time'] = now()->addYears($moreYear)->startOfMonth()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->endOfMonth()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfMonth());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfMonth(), false);
                            } else {
                                $save['start_time'] = now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth(), false);
                            }
                            $save['check_end']   = $check_end->toDateTimeString();
                            $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        } else {
                            if (intval(date('m', time())) < $month) {
                                $save['start_time'] = now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth(), false);
                            } elseif (intval(date('m', time())) == $month) {
                                $save['start_time'] = now()->subYears($moreYear)->startOfMonth()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->endOfMonth()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfMonth());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfMonth(), false);
                            } else {
                                $save['start_time'] = now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth(), false);
                            }
                            $save['check_end']   = $check_end->toDateTimeString();
                            $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        }
                        break;
                    case 3:// 年考核
                        $save['name'] = $year . '年考核';
                        if ($year >= intval(date('Y', time()))) {
                            $save['start_time'] = now()->addYears($moreYear)->startOfYear()->toDateTimeString();
                            $save['end_time']   = now()->addYears($moreYear)->endOfYear()->toDateTimeString();
                            $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear());
                            $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfYear(), false);
                        } else {
                            $save['start_time'] = now()->subYears($moreYear)->startOfYear()->toDateTimeString();
                            $save['end_time']   = now()->subYears($moreYear)->endOfYear()->toDateTimeString();
                            $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear());
                            $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfYear(), false);
                        }
                        $save['check_end']   = $check_end->toDateTimeString();
                        $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        break;
                    case 4:// 半年考核
                        [$year, $index] = explode('-', $time);
                        if ($year >= intval(date('Y', time()))) {
                            if ($index == 1) {
                                $save['name']       = $year . '上半年考核';
                                $save['start_time'] = now()->addYears($moreYear)->startOfYear()->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(6), false);
                            } else {
                                $save['name']       = $year . '下半年考核';
                                $save['start_time'] = now()->addYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6)->toDateTimeString();
                                $save['end_time']   = now()->addYears($moreYear)->startOfYear()->addMonths(6)->endOfYear()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6));
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(6)->endOfYear(), false);
                            }
                            $save['check_end']   = $check_end->toDateTimeString();
                            $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        } else {
                            if ($index == 1) {
                                $save['name']       = $year . '上半年考核';
                                $save['start_time'] = now()->subYears($moreYear)->startOfYear()->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear());
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(6), false);
                            } else {
                                $save['name']       = $year . '下半年考核';
                                $save['start_time'] = now()->subYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6)->toDateTimeString();
                                $save['end_time']   = now()->subYears($moreYear)->startOfYear()->addMonths(6)->endOfYear()->toDateTimeString();
                                $save['make_time']  = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6));
                                $check_end          = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(6)->endOfYear(), false);
                            }
                            $save['check_end']   = $check_end->toDateTimeString();
                            $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                        }
                        break;
                    case 5:// 季度考核
                        [$year, $index] = explode('-', $time);
                        if ($year >= intval(date('Y', time()))) {
                            switch ($index) {
                                case 1:
                                    $save['name']        = $year . '第一季度考核';
                                    $save['start_time']  = now()->addYears($moreYear)->startOfYear()->toDateTimeString();
                                    $save['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(3)->subSeconds()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear());
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(3), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                                case 2:
                                    $save['name']        = $year . '第二季度考核';
                                    $save['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(3)->toDateTimeString();
                                    $save['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(6)->subSeconds()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addMonths(3)->startOfYear());
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(6), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                                case 3:
                                    $save['name']        = $year . '第三季度考核';
                                    $save['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                                    $save['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(9)->subSeconds()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addMonths(6)->startOfYear());
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(9), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                                case 4:
                                    $save['name']        = $year . '第四季度考核';
                                    $save['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(9)->toDateTimeString();
                                    $save['end_time']    = now()->addYears($moreYear)->endOfYear()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear()->addMonths(9));
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfYear(), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                            }
                        } else {
                            switch ($index) {
                                case 1:
                                    $save['name']        = $year . '第一季度考核';
                                    $save['start_time']  = now()->subYears($moreYear)->startOfYear()->toDateTimeString();
                                    $save['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(3)->subSeconds()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear());
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(3), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                                case 2:
                                    $save['name']        = $year . '第二季度考核';
                                    $save['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(3)->toDateTimeString();
                                    $save['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(6)->subSeconds()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addMonths(3)->startOfYear());
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(6), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                                case 3:
                                    $save['name']        = $year . '第三季度考核';
                                    $save['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                                    $save['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(9)->subSeconds()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addMonths(6)->startOfYear());
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(9), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                                case 4:
                                    $save['name']        = $year . '第四季度考核';
                                    $save['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(9)->toDateTimeString();
                                    $save['end_time']    = now()->subYears($moreYear)->endOfYear()->toDateTimeString();
                                    $save['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear()->addMonths(9));
                                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfYear(), false);
                                    $save['check_end']   = $check_end->toDateTimeString();
                                    $save['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                                    break;
                            }
                        }
                        break;
                    default:
                        throw $this->exception('保存失败，无效的考核周期');
                }
                if ($this->dao->exists([
                    'test_uid' => $save['test_uid'],
                    'entid'    => $save['entid'],
                    'period'   => $save['period'],
                    'name'     => $save['name'],
                ])) {
                    throw $this->exception('【' . app()->get(AdminService::class)->value($save['test_uid'], 'name') . '】' . $save['name'] . '的记录已存在，请勿重复添加！');
                }
                $save['planid']      = $planInfo->id;
                $save['make_status'] = $is_draft ? 2 : 1;
                $maxScore            = $this->getMaxScore();
                $computeMode         = $this->getComputeMode();
                if ($is_draft) {
                    if ($computeMode) {
                        if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                            $save['intact'] = 0;
                        } else {
                            foreach ($data as $item) {
                                if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                                    $save['intact'] = 0;
                                }
                                break;
                            }
                        }
                    } else {
                        if (array_sum(array_column($data, 'ratio')) != 100) {
                            $save['intact'] = 0;
                        } else {
                            foreach ($data as $item) {
                                if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                                    $save['intact'] = 0;
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    if ($computeMode) {
                        if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                            throw $this->exception('维度总分必须为' . $maxScore . '分');
                        }
                        foreach ($data as $item) {
                            if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                                throw $this->exception('维度中指标总分必须为' . $item['ratio'] . '分');
                            }
                        }
                    } else {
                        if (array_sum(array_column($data, 'ratio')) != 100) {
                            throw $this->exception('维度权重总占比必须为100%');
                        }
                        foreach ($data as $item) {
                            if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                                throw $this->exception('维度中指标权重总占比必须为100%');
                            }
                        }
                    }
                    $save['status'] = 1;
                    $save['total']  = $maxScore;
                }
                $this->transaction(function () use ($save, $data, $entid, $is_draft) {
                    $assessInfo = $this->dao->create($save);
                    $res1       = $this->saveAssessInfo($data, $assessInfo->id, $entid, 0, $is_draft);
                    if (! $res1) {
                        throw $this->exception('保存失败');
                    }
                });
                // 清除缓存
                Cache::tags([CacheEnum::TAG_FRAME])->clear();

                unset($save, $assessInfo);
            }

            return true;
        }
    }

    /**
     * TODO 修改绩效考核内容.
     *
     * @param mixed $id
     * @param mixed $data
     * @param mixed $entid
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function editAssess(string $uuid, $id, $data, $entid)
    {
        $assessInfo = $this->dao->get($id);
        if (! $assessInfo) {
            throw $this->exception('无效的绩效考核！');
        }
        if ($assessInfo['is_show']) {
            throw $this->exception('绩效考核已启用，无法修改！');
        }
        $is_draft = $data['is_draft']; // 草稿
        $is_temp  = $data['is_temp'];  // 是否为模板
        $info     = $data['info'];     // 模板简介
        $name     = $data['name'];     // 模板名称
        $types    = $data['types'];    // 模板名称
        $data     = $data['data'];     // 考核目标
        $uid      = uuid_to_uid($uuid, $entid);
        if ($is_temp) {
            if (! $name) {
                throw $this->exception('请填写模板名称');
            }
            $tempService = app()->get(AssessTemplateService::class);

            return $this->transaction(function () use ($tempService, $entid, $uid, $name, $info, $types, $data, $is_temp) {
                $tempInfo = $tempService->create([
                    'entid'   => $entid,
                    'user_id' => $uid,
                    'name'    => $name,
                    'info'    => $info,
                    'types'   => $types,
                ]);

                return $this->saveAssessInfo($data, $tempInfo->id, $entid, $is_temp, 0);
            });
        } else {
            return $this->transaction(function () use ($is_draft, $assessInfo, $data, $id, $entid) {
                $maxScore    = $this->getMaxScore();
                $computeMode = $this->getComputeMode();
                if (! $is_draft) {
                    if ($computeMode) {
                        if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                            throw $this->exception('维度总分必须为' . $maxScore . '分');
                        }
                        foreach ($data as $item) {
                            if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                                throw $this->exception('维度中指标总分必须为' . $item['ratio'] . '分');
                            }
                        }
                    } else {
                        if (array_sum(array_column($data, 'ratio')) != 100) {
                            throw $this->exception('维度权重总占比必须为100%');
                        }
                        foreach ($data as $item) {
                            if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                                throw $this->exception('维度中指标权重总占比必须为100%');
                            }
                        }
                    }
                    $this->dao->update(['id' => $assessInfo->id], ['make_status' => 1, 'is_show' => 1, 'status' => 1]);
                    $this->syncAssessTodo((int) $assessInfo->id);
                } else {
                    $save['intact'] = 1;
                    if ($computeMode) {
                        if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                            $save['intact'] = 0;
                        } else {
                            foreach ($data as $item) {
                                if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                                    $save['intact'] = 0;
                                }
                                break;
                            }
                        }
                    } else {
                        if (array_sum(array_column($data, 'ratio')) != 100) {
                            $save['intact'] = 0;
                        } else {
                            foreach ($data as $item) {
                                if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                                    $save['intact'] = 0;
                                    break;
                                }
                            }
                        }
                    }
                    $save['make_status'] = 2;
                    $this->dao->update(['id' => $assessInfo->id], $save);
                }

                return $this->saveAssessInfo($data, $id, $entid, 0, $is_draft);
            });
        }
    }

    /**
     * 考核内容修改保存(自评/上级评价/审核/人事修改).
     *
     * @return false|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function updateAssess(int $assessid, string $uuid, int $entid, array $data)
    {
        $is_draft   = $data['is_draft'];  // 草稿
        $types      = $data['types'];     // 类型
        $mark       = $data['mark'];      // 总结/备注
        $hide_mark  = $data['hide_mark']; // 仅自身可见备注
        $data       = $data['data'];
        $assessInfo = $this->dao->get($assessid);
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        if ($assessInfo['entid'] != $entid) {
            throw $this->exception('考核记录不存在');
        }
        $uid           = uuid_to_uid($uuid, $entid);
        $targetService = app()->get(AssessTargetService::class);
        $replyService  = app()->get(AssessReplyService::class);
        $frameService  = app()->get(FrameService::class);
        foreach ($data as $v) {
            if (! $targetService->count(['id' => $v['target']])) {
                throw $this->exception('保存失败，存在无效的指标');
            }
        }
        $res = false;
        switch ($types) {
            case 0:// 自评
                if ($assessInfo['test_uid'] != $uid) {
                    throw $this->exception('您暂无权限操作该考核记录');
                }
                if (datetime_timestamp($assessInfo['start_time']) > time()) {
                    throw $this->exception('保存失败，不在考核期内');
                }
                $res = $this->transaction(function () use ($data, $targetService, $is_draft, $assessid, $replyService, $mark, $entid, $uid, $assessInfo) {
                    foreach ($data as $v) {
                        $targetService->update($v['target'], [
                            'finish_info'  => $v['finish_info'],
                            'finish_ratio' => $v['finish_ratio'],
                        ]);
                    }
                    if (! $is_draft && $assessInfo['status'] == 1) {
                        $this->dao->update(['id' => $assessid], ['test_status' => 1, 'status' => 2]);
                        $this->syncAssessTodo((int) $assessid);
                    }
                    if ($mark) {
                        $replyService->createOrUpdate([
                            'assessid' => $assessid,
                            'entid'    => $entid,
                            'user_id'  => $uid,
                            'content'  => $mark,
                            'is_own'   => 0,
                            'types'    => -1,
                        ]);
                    }

                    return true;
                });
                break;
            case 1:// 上级评价
                $uids = $frameService->getLevelSub($uid);
                if (! in_array($assessInfo['test_uid'], $uids)) {
                    throw $this->exception('您暂无权限操作该考核记录');
                }
                if (datetime_timestamp($assessInfo['start_time']) > time()) {
                    throw $this->exception('保存失败，不在评价期内');
                }
                $res = $this->transaction(function () use ($data, $targetService, $assessid, $replyService, $mark, $hide_mark, $entid, $uid, $assessInfo) {
                    foreach ($data as $v) {
                        $targetService->update($v['target'], [
                            'check_info' => $v['check_info'],
                            'score'      => $v['score'],
                            'name'       => $v['name'],
                            'content'    => $v['content'],
                        ]);
                    }
                    if ($mark) {
                        $replyService->createOrUpdate([
                            'assessid' => $assessid,
                            'entid'    => $entid,
                            'user_id'  => $uid,
                            'content'  => $mark,
                            'is_own'   => 0,
                            'types'    => 0,
                        ]);
                    }
                    if ($hide_mark) {
                        $replyService->createOrUpdate([
                            'assessid' => $assessid,
                            'entid'    => $entid,
                            'user_id'  => $uid,
                            'content'  => $hide_mark,
                            'is_own'   => 1,
                            'types'    => 0,
                        ]);
                    }
                    if ($assessInfo['status'] == 2) {
                        return $this->dao->updateAssessScore($assessid, 0, $uid);
                    }
                });
                break;
            case 2:// 绩效审核
                if ($frameService->getLevelSuper($assessInfo['test_uid'], 2) != $uid) {
                    throw $this->exception('您暂无权限操作该考核记录');
                }
                if (datetime_timestamp($assessInfo['start_time']) > time()) {
                    throw $this->exception('保存失败，不在审核期内');
                }
                $res = $this->transaction(function () use ($data, $targetService, $assessid, $replyService, $uid, $assessInfo) {
                    foreach ($data as $v) {
                        $targetService->update($v['target'], [
                            'name'       => $v['name'],
                            'content'    => $v['content'],
                            'check_info' => $v['check_info'],
                            'score'      => $v['score'],
                        ]);
                    }
                    $replyService->update(['assessid' => $assessid, 'user_id' => $assessInfo['test_uid'], 'types' => 1], ['status' => 1]);

                    return $this->dao->updateAssessScore($assessid, 1, $uid);
                });
                break;
        }
        Cache::tags([CacheEnum::TAG_FRAME])->flush();
        $res && $this->syncAssessTodo((int) $assessid);

        return $res;
    }

    /**
     * 考核人添加备注信息.
     *
     * @return BaseModel|int|Model
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function saveMark(int $assessid, int $uid, string $mark)
    {
        $assessInfo = $this->dao->get($assessid)?->toArray();
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }

        $uids = app()->get(FrameService::class)->getLevelSub($uid);
        if (! in_array($assessInfo['test_uid'], $uids)) {
            throw $this->exception('您暂无权限操作该考核记录');
        }

        return app()->get(AssessReplyService::class)->createOrUpdate([
            'assessid' => $assessid,
            'entid'    => 1,
            'user_id'  => $uid,
            'content'  => $mark,
            'is_own'   => 1,
            'types'    => 0,
        ]);
    }

    /**
     * 添加备注/评价信息.
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function saveMarks(int $uid, int $entid, int $types, int $assessid, string $mark = '', string $hide_mark = '')
    {
        $replyService = app()->get(AssessReplyService::class);
        return $this->transaction(function () use ($uid, $entid, $types, $assessid, $mark, $hide_mark, $replyService) {
            switch ($types) {
                case 0:
                    if ($mark) {
                        $replyService->createOrUpdate([
                            'assessid' => $assessid,
                            'entid'    => $entid,
                            'user_id'  => $uid,
                            'content'  => $mark,
                            'is_own'   => 0,
                            'types'    => -1,
                        ]);
                    }
                    break;
                case 1:
                    if ($mark) {
                        $replyService->createOrUpdate([
                            'assessid' => $assessid,
                            'entid'    => $entid,
                            'user_id'  => $uid,
                            'content'  => $mark,
                            'is_own'   => 0,
                            'types'    => 0,
                        ]);
                    }
                    if ($hide_mark) {
                        $replyService->createOrUpdate([
                            'assessid' => $assessid,
                            'entid'    => $entid,
                            'user_id'  => $uid,
                            'content'  => $hide_mark,
                            'is_own'   => 1,
                            'types'    => 0,
                        ]);
                    }
                    break;
            }

            return true;
        });
    }

    /**
     * 申诉/驳回.
     *
     * @return Model
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function saveAssessAppeal(int $assessid, string $uuid, int $entid, array $data)
    {
        $types   = $data['types'];   // 类型
        $content = $data['content']; // 类型
        if (! $content) {
            throw $this->exception('请填写内容');
        }
        $assessInfo = $this->dao->get($assessid);
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        if ($assessInfo['entid'] != $entid) {
            throw $this->exception('考核记录不存在');
        }
        $uid          = uuid_to_uid($uuid, $entid);
        $replyService = app()->get(AssessReplyService::class);
        switch ($types) {
            case 0:// 申诉
                if ($assessInfo['test_uid'] != $uid) {
                    throw $this->exception('您暂无权限操作该考核记录');
                }
                if ($assessInfo['check_status'] != 1) {
                    throw $this->exception('申诉失败，上级未评价打分');
                }
                if ($assessInfo['status'] == 4) {
                    throw $this->exception('申诉失败，考核已结束');
                }
                $res = $replyService->create([
                    'assessid' => $assessid,
                    'entid'    => $entid,
                    'user_id'  => $assessInfo['test_uid'],
                    'content'  => $content,
                    'types'    => 1,
                ]);
                $this->dao->update($assessid, ['status' => AssessEnum::ASSESS_APPEAL]);
                // 绩效申诉提醒
                Task::deliver(new UserAssessAppealRemind($entid, $assessInfo->toArray()));
                break;
            case 1:// 驳回
                $replyService->update(['assessid' => $assessid, 'user_id' => $assessInfo['test_uid'], 'types' => 1], ['status' => 1]);
                $res = $replyService->create([
                    'assessid' => $assessid,
                    'entid'    => $entid,
                    'user_id'  => $uid,
                    'content'  => $content,
                    'types'    => 1,
                    'status'   => 2,
                ]);
                $this->dao->update($assessid, ['status' => AssessEnum::ASSESS_REJECT]);
                // 绩效申诉结果
                Task::deliver(new UserAssessAppealResRemind($entid, $assessInfo->toArray()));
                break;
        }

        $this->syncAssessTodo((int) $assessid, $types ? [(int) $uid] : []);
        return $res;
    }

    /**
     * TODO 保存考核目标详情.
     *
     * @param int $is_temp
     * @param mixed $is_draft
     * @param mixed $data
     * @param mixed $id
     * @param mixed $entid
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function saveAssessInfo($data, $id, $entid, $is_temp = 0, $is_draft = 1)
    {
        if (! $data || ! count($data) || ! count($data[0]['target'])) {
            throw $this->exception('内容不能为空');
        }

        if (! $is_draft) {
            if ($this->getComputeMode()) {
                $maxScore = $this->getMaxScore();
                if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                    throw $this->exception('维度总分必须为' . $maxScore . '分');
                }
            } else {
                if (array_sum(array_column($data, 'ratio')) != 100) {
                    throw $this->exception('维度权重总占比必须为100%');
                }
                foreach ($data as $item) {
                    if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                        throw $this->exception('维度中指标权重总占比必须为100%');
                    }
                }
            }
        }
        if ($is_temp) {
            return $this->saveTemp($id, $data, $entid, $is_temp, false);
        }
        return $this->saveInfo($id, $data, $entid, $is_temp, false);
    }

    /**
     * TODO 保存考核内容(启用阶段).
     *
     * @param int $id 考核ID
     * @param string $uuid 用户ID
     * @param int $entid 企业ID
     * @param array $data 考核内容
     * @param int $types 操作类型
     * @param string $mark 上级评价/自评内容
     * @param string $hideMark 考核人备注
     * @param array $spaceIds 删除维度ID
     * @param array $targetIds 删除指标ID
     *
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function save(int $id, string $uuid, int $entid, array $data, int $types, string $mark = '', string $hideMark = '', array $spaceIds = [], array $targetIds = [], bool $isUni = false)
    {
        $assessInfo = toArray($this->dao->get($id));
        if (! $assessInfo) {
            throw $this->exception('未找到该条考核记录');
        }
        if ($assessInfo['entid'] != $entid) {
            throw $this->exception('您无权操作该考核记录');
        }
        $this->checkData($data, $assessInfo['types'], $assessInfo['total']);
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        $this->deleteData($spaceService, $targetService, $spaceIds, $targetIds);
        $uid  = $this->checkAuth($uuid, $entid, $assessInfo, $types);
        $res1 = $this->transaction(function () use ($spaceService, $targetService, $data, $entid, $id, $types) {
            foreach ($data as $item) {
                if (! $item['id']) {
                    if (! $types) {
                        throw $this->exception('您暂无权限修改考核内容');
                    }
                    $space['entid']    = $entid;
                    $space['assessid'] = $id;
                    $space['name']     = $item['name'];
                    $space['ratio']    = $item['ratio'];
                    $res               = $spaceService->create($space);
                    foreach ($item['target'] as $value) {
                        $target['spaceid'] = $res->id;
                        $target['ratio']   = $value['ratio'];
                        $target['name']    = $value['name'];
                        $target['content'] = $value['content'];
                        $target['info']    = $value['info'];
                        $targetService->create($target);
                    }
                    unset($res);
                } else {
                    if ($types) {
                        $space['name']  = $item['name'];
                        $space['ratio'] = $item['ratio'];
                        $spaceService->update($item['id'], $space);
                    }
                    foreach ($item['target'] as $value) {
                        if (! $value['id']) {
                            if (! $types) {
                                throw $this->exception('您暂无权限修改考核内容');
                            }
                            $target['spaceid']    = $item['id'];
                            $target['ratio']      = $value['ratio'];
                            $target['name']       = $value['name'];
                            $target['content']    = $value['content'];
                            $target['info']       = $value['info'];
                            $target['check_info'] = $value['check_info'];
                            $target['score']      = $value['score'];
                            $targetService->create($target);
                        } else {
                            if (! $types) {
                                $target['finish_info']  = $value['finish_info'];
                                $target['finish_ratio'] = $value['finish_ratio'];
                            } else {
                                $target['ratio']      = $value['ratio'];
                                $target['name']       = $value['name'];
                                $target['content']    = $value['content'];
                                $target['info']       = $value['info'] ?? '';
                                $target['check_info'] = $value['check_info'];
                                $target['score']      = $value['score'];
                            }
                            $targetService->update($value['id'], $target);
                        }
                    }
                }
                unset($space, $target);
            }

            return true;
        });
        $res2 = ! $isUni || $this->saveMarks($uid, $entid, $types, $id, $mark, $hideMark);
        if ($types) {
            $verify = $types > 1 ? 1 : 0;
            $res3   = $this->dao->updateAssessScore($id, $verify, $uid);
        //            $res3   = UserAssessScoreJob::dispatch($id, $verify, $uid);
        } else {
            if ($assessInfo['status'] == 1) {
                $this->dao->update(['id' => $id], ['status' => 2]);
                $this->syncAssessTodo((int) $id);
            }
            $res3 = true;
        }
        // 修改审核状态
        $replyService = app()->get(AssessReplyService::class);
        $replyCount   = $replyService->count(['entid' => $entid, 'assessid' => $id, 'types' => 1]);
        if ($replyCount) {
            $replyService->update(['entid' => $entid, 'assessid' => $id, 'types' => 1], ['status' => 1]);
            // 申诉结果消息提醒
            Task::deliver(new UserAssessAppealResRemind($entid, $assessInfo));
        }
        // 修改关联消息处理状态
        app()->get(NoticeRecordService::class)->update(['to_uid' => $assessInfo['test_uid'], 'other_id' => $assessInfo['id'], 'template_type' => MessageType::ASSESS_SELF_TYPE], ['is_handle' => 1]);
        Cache::tags([CacheEnum::TAG_FRAME])->flush();
        ($res1 && $res2 && $res3) && $this->syncAssessTodo((int) $id);

        return $res1 && $res2 && $res3;
    }

    /**
     * 保存考核内容.
     *
     * @param mixed $transaction
     * @param mixed $id
     * @param mixed $data
     * @param mixed $entid
     * @param mixed $is_temp
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function saveInfo($id, $data, $entid, $is_temp, $transaction = true)
    {
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $spaceService->column(['assessid' => $id], 'id') ?? [];
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?? [];

        return $this->transaction(function () use ($spaceService, $targetService, $data, $entid, $id, $spaceIds, $targetIds, $is_temp) {
            if ($spaceIds) {
                $spaceService->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            foreach ($data as $item) {
                $space['entid'] = $entid;
                if ($is_temp) {
                    $space['targetid'] = $id;
                } else {
                    $space['assessid'] = $id;
                }
                $space['name']  = $item['name'];
                $space['ratio'] = $item['ratio'];
                $res            = $spaceService->create($space);
                foreach ($item['target'] as $value) {
                    $target['spaceid'] = $res->id;
                    $target['ratio']   = $value['ratio'];
                    $target['name']    = $value['name'];
                    $target['content'] = $value['content'];
                    $target['info']    = $value['info'] ?? '';
                    $targetService->create($target);
                }
                unset($res);
            }
            Cache::tags([CacheEnum::TAG_FRAME])->flush();

            return true;
        }, $transaction);
    }

    /**
     * 保存模板
     *
     * @param mixed $transaction
     * @param mixed $id
     * @param mixed $data
     * @param mixed $entid
     * @param mixed $is_temp
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function saveTemp($id, $data, $entid, $is_temp, $transaction = true)
    {
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $spaceService->column(['assessid' => $id], 'id') ?? [];
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?? [];

        return $this->transaction(function () use ($spaceService, $targetService, $data, $entid, $id, $spaceIds, $targetIds, $is_temp) {
            if ($spaceIds) {
                $spaceService->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            foreach ($data as $item) {
                $space['entid'] = $entid;
                if ($is_temp) {
                    $space['targetid'] = $id;
                } else {
                    $space['assessid'] = $id;
                }
                $space['name']  = $item['name'];
                $space['ratio'] = $item['ratio'];
                $res            = $spaceService->create($space);
                foreach ($item['target'] as $value) {
                    $target['spaceid'] = $res->id;
                    $target['ratio']   = $value['ratio'];
                    $target['name']    = $value['name'];
                    $target['content'] = $value['content'];
                    $target['info']    = $value['info'];
                    $targetService->create($target);
                }
                unset($res);
            }

            return true;
        }, $transaction);
    }

    /**
     * 创建提醒.
     *
     * @param mixed $id
     * @param mixed $entid
     * @return bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function createNotice($id, $entid)
    {
        $assessInfo    = $this->dao->getAssessInfo($id);
        $noticeService = app()->get(NoticeRecordService::class);
        switch ($assessInfo['status']) {
            case 0:
                $res = $noticeService->i($entid)->to($assessInfo['check_uid'])->message('您有一条绩效考核目标未制定，请尽快处理！')
                    ->noticeType(2)
                    ->url('/ent/user/work/assessment')
                    ->uniUrl('/pages/users/assessment/default')
                    ->ent($entid)->send();
                break;
            case 1:
                $res = $noticeService->i($entid)->to($assessInfo['test_uid'])->message('您有一条待自评的绩效考核信息，请尽快处理！')
                    ->noticeType(2)
                    ->url('/ent/user/work/assessment')
                    ->uniUrl('/pages/users/assessment/default')
                    ->ent($entid)->send();
                break;
            default:
                $res = false;
        }
        if (! $res) {
            throw $this->exception('考核状态错误，提醒失败');
        }
        return true;
    }

    /**
     * TODO 绩效考核定时任务
     *
     * @param mixed $assessInfo
     * @return bool
     * @throws BindingResolutionException
     */
    public function openAssessTask($assessInfo)
    {
        if (is_object($assessInfo)) {
            $assessInfo = $assessInfo->toArray();
        }
        $option            = new TaskOptions($assessInfo['name']);
        $option->persist   = 1;
        $option->className = 'user.assess';
        $option->entid     = $assessInfo['entid'];
        // 设置任务参数
        $option->uniqued        = md5($assessInfo['entid'] . $assessInfo['id'] . 'start');
        $option->period         = 'day';
        $option->intervalHour   = (int) Carbon::parse($assessInfo['start_time'])->timezone(config('app.timezone'))->format('H');
        $option->intervalMinute = (int) Carbon::parse($assessInfo['start_time'])->timezone(config('app.timezone'))->format('i');
        $option->intervalSecond = (int) Carbon::parse($assessInfo['start_time'])->timezone(config('app.timezone'))->format('s') + 1;
        $taskService            = app()->get(TaskService::class);
        if ($taskService->exists(['uniqued' => $option->uniqued])) {
            $taskService->delete($option->uniqued, 'uniqued');
        }
        $option->setParameter($assessInfo['id']);

        return $taskService->addTask($option);
    }

    /**
     * 启用考核.
     *
     * @param mixed $status
     * @param mixed $id
     *
     * @return int
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function showAssess($id, $status)
    {
        $info = toArray($this->dao->get($id));
        if (! in_array($status, [0, 1])) {
            throw $this->exception('无效的状态！');
        }
        if (! $info) {
            throw $this->exception('未找到考核记录！');
        }
        if ($info['status'] > 1) {
            throw $this->exception('考核已完成自评，无法修改状态！');
        }
        if (! $info['intact'] && $status) {
            throw $this->exception('考核已启失败，请按规范完善内容！');
        }
        $entid = 1;

        return $this->transaction(function () use ($id, $info, $entid, $status) {
            $res1 = $this->dao->update($id, ['is_show' => $status, 'status' => $status, 'make_status' => $status]);
            $res1 && $this->syncAssessTodo((int) $id);
            if ($status) {
                // 考核目标发布提醒
                Task::deliver(new UserAssessReleaseRemind($entid, $info['test_uid'], $info));
                $res2 = $this->openAssessTask($info);
                Cache::tags([CacheEnum::TAG_FRAME])->flush();
                return $res1 && $res2;
            }
            $uniqued = md5($entid . $id . 'start');
            app()->get(TaskService::class)->delete(['uniqued' => $uniqued]);
            Cache::tags([CacheEnum::TAG_FRAME])->flush();
            return true;
        });
    }

    /**
     * 删除绩效表单.
     *
     * @param mixed $id
     * @return array
     */
    public function deleteForm($id)
    {
        $form = [
            Form::textarea('mark', '删除原因', '')->placeholder('请输入删除原因')->required(),
        ];

        return $this->createElementForm('删除绩效考核', $form, '/ent/user/assess/delete/' . $id);
    }

    /**
     * 删除绩效考核.
     *
     * @param mixed $id
     * @param mixed $mark
     * @param mixed $uuid
     * @param mixed $entid
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function deleteAssess($id, $mark, $uuid, $entid)
    {
        if (! $mark) {
            throw $this->exception('删除失败，您填写删除原因！');
        }
        $info    = $this->dao->get($id);
        $uid     = uuid_to_uid($uuid, $entid);
        $service = app()->get(UserAssessScoreService::class);
        if ($info['check_uid'] != $uid && app()->get(FrameService::class)->getLevelSuper($info['check_uid'])) {
            throw $this->exception('删除失败，您暂无删除权限！');
        }
        $spaceService = app()->get(AssessSpaceService::class);
        $info['info'] = $spaceService->getAssess((int) $id);
        $save         = [
            'assessid'  => $id,
            'entid'     => $info['entid'],
            'userid'    => $uid,
            'check_uid' => $info['check_uid'],
            'test_uid'  => $info['test_uid'],
            'score'     => $info['score'],
            'total'     => $info['total'],
            'grade'     => $info['grade'],
            'info'      => json_encode($info),
            'mark'      => $mark,
            'types'     => 1,
        ];
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $spaceService->column(['assessid' => $id], 'id') ?: [];
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?: [];

        return $this->transaction(function () use ($id, $save, $service, $spaceService, $targetService, $spaceIds, $targetIds) {
            $res1 = $service->create($save);
            if ($spaceIds) {
                $spaceService->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            $res2 = $this->dao->delete($id, 'id');
            if (! $res1 || ! $res2) {
                throw $this->exception('删除失败！');
            }
            Cache::tags([CacheEnum::TAG_FRAME])->flush();

            return true;
        });
    }

    /**
     * 获取某个人.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getUserAssessList(int $userId)
    {
        [$page, $limit] = $this->getPageValue();
        [$data, $count] = $this->dao->getUserAssessList(['type' => ''], [], $userId, ['*'], $page, $limit, 'id');

        return $this->listData($data, $count);
    }

    /**
     * 获取统计字段名.
     *
     * @param mixed $date
     * @param mixed $period
     * @return string
     */
    public function getCensusKey($date, $period)
    {
        [$year, $month] = explode('-', $date);
        if ($period == 5) {
            switch ((int) $month) {
                case 1:
                case 2:
                case 3:
                    return $year . '年第1季度';
                    break;
                case 4:
                case 5:
                case 6:
                    return $year . '年第2季度';
                    break;
                case 7:
                case 8:
                case 9:
                    return $year . '年第3季度';
                    break;
                default:
                    return $year . '年第4季度';
                    break;
            }
        } else {
            switch ((int) $month) {
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                    return $year . '年上半年';
                    break;
                default:
                    return $year . '年下半年';
                    break;
            }
        }
    }

    /**
     * 定时执行发送消息任务
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function timer(int $page, int $limit, array $where)
    {
        $where['period'] = 1;
        $list            = Cache::tags([CacheEnum::TAG_FRAME])->remember(
            md5('user_assess_list_' . $page . '_' . $limit . '_' . json_encode($where)),
            (int) sys_config('system_cache_ttl', 3600),
            fn () => $this->dao->getTimerUserAssessList(
                $where,
                ['id', 'entid', 'planid', 'period', 'name', 'test_uid', 'is_show', 'check_uid', 'make_status', 'test_status', 'check_status', 'start_time', 'end_time'],
                $page,
                $limit,
                [
                    'frame',
                    'plan'  => fn ($q) => $q->select(['id', 'make_type', 'make_day', 'eval_type', 'eval_day']),
                    'test'  => fn ($q) => $q->with(['frame']),
                    'check' => fn ($q) => $q->with(['frame']),
                ]
            )
        );
        $now = now();
        foreach ($list as $item) {
            switch ((int) $item['period']) {
                case 1:// 周考核
                    // 自评提醒类型
                    $this->handleMessageSend(MessageType::ASSESS_SELF_TYPE, ['year' => $now->year, 'user_id' => $item['test_uid'], 'week' => $now->week, 'relation_id' => $item['id']], $item);
                    // 开启考核提醒
                    $this->handleMessageSend(MessageType::ASSESS_START_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'week' => $now->week, 'relation_id' => $item['id']], $item);
                    // 考核上级评价提醒
                    $this->handleMessageSend(MessageType::ASSESS_EXAMINE_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'week' => $now->week, 'relation_id' => $item['id']], $item, 'eval_day');
                    break;
                case 2:// 月考核
                    // 自评提醒类型
                    $this->handleMessageSend(MessageType::ASSESS_SELF_TYPE, ['year' => $now->year, 'user_id' => $item['test_uid'], 'month' => $now->month, 'relation_id' => $item['id']], $item);
                    // 开启考核提醒
                    $this->handleMessageSend(MessageType::ASSESS_START_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'month' => $now->month, 'relation_id' => $item['id']], $item);
                    // 考核上级评价提醒
                    $this->handleMessageSend(MessageType::ASSESS_EXAMINE_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'month' => $now->month, 'relation_id' => $item['id']], $item, 'eval_day');
                    break;
                case 3:// 年考核
                    // 自评提醒类型
                    $this->handleMessageSend(MessageType::ASSESS_SELF_TYPE, ['year' => $now->year, 'user_id' => $item['test_uid'], 'relation_id' => $item['id']], $item);
                    // 开启考核提醒
                    $this->handleMessageSend(MessageType::ASSESS_START_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'relation_id' => $item['id']], $item);
                    // 考核上级评价提醒
                    $this->handleMessageSend(MessageType::ASSESS_EXAMINE_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'relation_id' => $item['id']], $item, 'eval_day');
                    break;
                case 5:// 季度考核
                    // 自评提醒类型
                    $this->handleMessageSend(MessageType::ASSESS_SELF_TYPE, ['year' => $now->year, 'user_id' => $item['test_uid'], 'quarter' => $now->quarter, 'relation_id' => $item['id']], $item);
                    // 开启考核提醒
                    $this->handleMessageSend(MessageType::ASSESS_START_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'quarter' => $now->quarter, 'relation_id' => $item['id']], $item);
                    // 考核上级评价提醒
                    $this->handleMessageSend(MessageType::ASSESS_EXAMINE_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'quarter' => $now->quarter, 'relation_id' => $item['id']], $item, 'eval_day');
                    break;
                case 4:// 半年考核
                    $time = get_start_and_end_time(4);
                    // 自评提醒类型
                    $this->handleMessageSend(MessageType::ASSESS_SELF_TYPE, ['year' => $now->year, 'user_id' => $item['test_uid'], 'time' => implode('-', $time), 'relation_id' => $item['id']], $item);
                    // 开启考核提醒
                    $this->handleMessageSend(MessageType::ASSESS_START_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'time' => implode('-', $time), 'relation_id' => $item['id']], $item);
                    // 考核上级评价提醒
                    $this->handleMessageSend(MessageType::ASSESS_EXAMINE_TYPE, ['year' => $now->year, 'user_id' => $item['check_uid'], 'time' => implode('-', $time), 'relation_id' => $item['id']], $item, 'eval_day');
                    break;
            }
        }
    }

    /**
     * 执行发送逻辑.
     *
     * @param mixed $item
     * @return false
     * @throws BindingResolutionException
     */
    public function handleMessageSend(string $type, array $where, $item, string $key = 'make_day')
    {
        // 已经自评过
        if ($type == MessageType::ASSESS_SELF_TYPE && $item['test_status'] == 1) {
            return false;
        }
        // 上级已经评价
        if ($type == MessageType::ASSESS_EXAMINE_TYPE && $item['check_status'] == 1) {
            return false;
        }
        // 已经开启考核
        if ($type == MessageType::ASSESS_START_TYPE && $item['make_status'] == 1) {
            return false;
        }

        $now = now();

        if (! $item['plan']) {
            return false;
        }

        $service              = app()->get(UserRemindLogService::class);
        $where['remind_type'] = $type;
        $where['entid']       = $item['entid'];

        if (in_array($type, [MessageType::ASSESS_START_TYPE, MessageType::ASSESS_EXAMINE_TYPE])) {
            $where['user_id'] = $item['check_uid'];
        } else {
            $where['user_id'] = $item['test_uid'];
        }

        $res = $service->exists($where);
        if ($res) {
            return false;
        }

        // 自评提醒类型
        if ($key == 'eval_day') {
            $makeType = $item['plan']['eval_type'];
            $makeDay  = $item['plan']['eval_day'];
        } else {
            $makeType = $item['plan']['make_type'];
            $makeDay  = $item['plan']['make_day'];
        }

        $message = app()->get(MessageService::class)->getMessageContent((int) $item['entid'], $type);
        switch ($makeType) {
            case 'before':// 考核结束后
                $endTime = now()->setTimeFromTimeString($item['end_time'])->addDays((int) $makeDay);
                if ($endTime->day == $now->day && $endTime->month == $now->month && $endTime->year == $now->year && $message && ! empty($message['remind_time']) && date('H:i') == $message['remind_time']) {
                    $this->sendUserTemind($service, $type, $item);
                }
                break;
            case 'start':// 考核开始后
                $endTime = now()->setTimeFromTimeString($item['start_time'])->addDays((int) $makeDay);
                if ($endTime->day == $now->day && $endTime->month == $now->month && $endTime->year == $now->year && $message && ! empty($message['remind_time']) && date('H:i') == $message['remind_time']) {
                    $this->sendUserTemind($service, $type, $item);
                }
                break;
            case 'after':// 考核结束前
                $endTime = now()->setTimeFromTimeString($item['end_time'])->subDays((int) $makeDay);
                if ($endTime->day == $now->day && $endTime->month == $now->month && $endTime->year == $now->year && $message && ! empty($message['remind_time']) && date('H:i') == $message['remind_time']) {
                    $this->sendUserTemind($service, $type, $item);
                }
                break;
        }
    }

    /**
     * 执行发送
     *
     * @param mixed $item
     * @return Model
     */
    public function sendUserTemind(UserRemindLogService $service, string $type, $item)
    {
        $timeStr = match ((int) $item['period']) {
            1       => '周',
            2       => '月',
            3       => '年',
            4       => '季度',
            5       => '半年',
            default => ''
        };

        $toUid = 0;
        // 自我评价提醒
        if ($type === NoticeEnum::ASSESS_SELF_TYPE) {
            event(new SystemMessageEvent(
                type: $type,
                params: [
                    '考核名称'     => $item['name'],
                    '考核开始时间' => $item['start_time'],
                    '考核结束时间' => $item['end_time'],
                    '考核人'       => $item['check']['name'] ?? '',
                    '考核人部门'   => $item['check']['frame']['name'] ?? '',
                    '时间'         => $timeStr,
                    '时间1'        => $timeStr,
                    '时间2'        => $timeStr,
                    '时间3'        => $timeStr,
                ],
                receiverIds: (int) $item['test_uid'],
                other: ['id' => $item['id']],
                linkId: $item['id'],
            ));
            $toUid = $item['test_uid'];
        } elseif ($type === NoticeEnum::ASSESS_START_TYPE) {
            // 上级是否开启提醒
            if (! $item['is_show']) {
                event(new SystemMessageEvent(
                    type: $type,
                    params: [
                        '负责部门'     => $item['check']['frame']['name'] ?? '',
                        '考核类型'     => $timeStr . '考核',
                        '考核周期'     => $timeStr,
                        '考核开始时间' => $item['start_time'],
                        '考核结束时间' => $item['end_time'],
                        '时间1'        => $timeStr,
                        '时间2'        => $timeStr,
                        '时间3'        => $timeStr,
                    ],
                    receiverIds: (int) $item['check_uid'],
                    other: ['id' => $item['id']],
                    linkId: $item['id'],
                ));
                $toUid = $item['check_uid'];
            }
        } elseif ($type === NoticeEnum::ASSESS_EXAMINE_TYPE) {
            // 考核上级评价提醒
            event(new SystemMessageEvent(
                type: $type,
                params: [
                    '负责部门'     => $item['check']['frame']['name'] ?? '',
                    '考核名称'     => $item['name'],
                    '考核类型'     => $timeStr . '考核',
                    '考核周期'     => $timeStr,
                    '开始时间'     => $item['start_time'],
                    '结束时间'     => $item['end_time'],
                    '被考核人'     => $item['test']['name'] ?? '',
                    '被考核人部门' => $item['test']['frame']['name'] ?? '',
                    '时间'         => $timeStr,
                ],
                receiverIds: (int) $item['check_uid'],
                other: ['id' => $item['id']],
                linkId: $item['id'],
            ));
            $toUid = $item['check_uid'];
        }
        if ($toUid) {
            // 记录发送日志保证只发送一次
            $service->create([
                'remind_type' => $type,
                'user_id'     => $toUid,
                'relation_id' => $item['id'],
                'entid'       => $item['entid'],
                'year'        => now()->year,
                'day'         => now()->day,
                'month'       => now()->month,
                'week'        => now()->week,
                'quarter'     => now()->quarter,
            ]);
        }
    }

    /**
     * 运行自动结束考核.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function runAssessEndTimer(int $page, int $limit, array $where)
    {
        $list = $this->dao->getTimerUserAssessList(
            $where,
            ['id', 'entid', 'test_uid', 'verify_time'],
            $page,
            $limit,
        );

        foreach ($list as $item) {
            $updated = datetime_timestamp($item['verify_time']);
            if ($updated <= time()) {
                // 修改为已结束
                $this->dao->update($item['id'], ['status' => 4]);
                $this->syncAssessTodo((int) $item['id']);
                // 发送考核结束提醒
                Task::deliver(new UserAssessEndRemind($item['entid'], $item['test_uid'], $item['id']));
            }
        }
    }

    private function syncAssessTodo(int $assessId, array $userIds = []): void
    {
        if (! $assessId) {
            return;
        }

        try {
            foreach (TodoEnum::ASSESS_TYPES as $type) {
                BusinessDataChangeEvent::dispatch($type, $assessId, 'update', $userIds);
            }
        } catch (\Throwable) {
        }
    }

    /**
     * TODO 验证模板内容完整性.
     *
     * @param mixed $total
     * @param mixed $data
     * @param mixed $types
     *
     * @return bool
     */
    protected function checkData($data, $types, $total)
    {
        if ($types) {
            if (array_sum(array_column($data, 'ratio')) != $total) {
                throw $this->exception('维度总分必须为' . $total . '分');
            }
            foreach ($data as $item) {
                if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                    throw $this->exception('维度中指标总分必须为' . $item['ratio'] . '分');
                }
            }
        } else {
            if (array_sum(array_column($data, 'ratio')) != 100) {
                throw $this->exception('维度权重总占比必须为100%');
            }
            foreach ($data as $item) {
                if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                    throw $this->exception('维度中指标权重总占比必须为100%');
                }
            }
        }

        return true;
    }

    /**
     * TODO 删除已删除维度或指标.
     *
     * @param array $spaceIds
     * @param array $targetIds
     * @param mixed $spaceService
     * @param mixed $targetService
     *
     * @return bool
     */
    protected function deleteData($spaceService, $targetService, $spaceIds = [], $targetIds = [])
    {
        if ($spaceIds) {
            $spaceService->delete($spaceIds, 'id');
            $targetIds = array_merge($targetIds, $targetService->column(['spaceid' => $spaceIds], 'id') ?: []);
        }
        if ($targetIds) {
            $targetService->delete($targetIds, 'id');
        }

        return true;
    }

    /**
     * 验证用户操作权限.
     * @param mixed $uuid
     * @param mixed $entid
     * @param mixed $assessInfo
     * @param mixed $types
     * @return array|int|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function checkAuth($uuid, $entid, $assessInfo, $types)
    {
        $uid = uuid_to_uid($uuid, $entid);
        switch ($types) {
            case 0:
                if ($assessInfo['test_uid'] != $uid) {
                    throw $this->exception('您暂无权限操作该考核记录');
                }
                if (datetime_timestamp($assessInfo['start_time']) > time()) {
                    throw $this->exception('保存失败，不在考核期内');
                }
                break;
            case 1:
                $uids = app()->get(FrameService::class)->getLevelSub($uid);
                if (! in_array($assessInfo['test_uid'], $uids)) {
                    throw $this->exception('您暂无权限操作该考核记录');
                }
                if (datetime_timestamp($assessInfo['start_time']) > time()) {
                    throw $this->exception('保存失败，不在评价期内');
                }
                break;
            case 2:
                if (datetime_timestamp($assessInfo['start_time']) > time()) {
                    throw $this->exception('保存失败，不在审核期内');
                }
                break;
            default:
                throw $this->exception('无效的操作类型');
        }

        return $uid;
    }

    /**
     * 获取时间.
     *
     * @param bool $toString
     * @param mixed $type
     * @param mixed $time
     *
     * @return int
     */
    protected function getDateTime($type, int $day, $time, $toString = true)
    {
        switch ($type) {
            case 'before':
                $newDay = $time->subDays($day);
                break;
            case 'after':
                $newDay = $time->addDays($day);
                break;
            default:
                $newDay = $time;
        }
        if ($toString) {
            return $newDay->toDateTimeString();
        }
        return $newDay;
    }

    /**
     * 评分等级.
     *
     * @param mixed $entid
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    protected function getScoreLevel($entid = 1)
    {
        $scores = app()->get(AssessScoreService::class)->column(['entid' => $entid], ['name', 'level']) ?: [];
        if ($scores) {
            foreach ($scores as $score) {
                $scoreList[$score['level']] = $score['name'];
            }
        } else {
            $scoreList = [];
        }

        return $scoreList;
    }
}
