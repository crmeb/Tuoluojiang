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

namespace App\Http\Dao\Report;

use App\Http\Model\User\UserDaily;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;

/**
 * 日报
 * Class UserDailyDao.
 */
class UserDailyDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;

    /**
     * 用户今日有没有写过日志.
     *
     * @param mixed $entid
     * @param mixed $types
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDayExists(string $uid, $entid, $types = 0)
    {
        switch ($types) {
            case 1:
                $status = $this->getModel(false)->where(['uid' => $uid, 'entid' => $entid, 'types' => $types])
                    ->whereDate('created_at', '>=', now()->timezone(config('app.timezone'))->startOfWeek()->toDateString())
                    ->whereDate('created_at', '<', now()->timezone(config('app.timezone'))->endOfWeek()->toDateString())->exists();
                break;
            case 2:
                $status = $this->getModel(false)->where(['uid' => $uid, 'entid' => $entid, 'types' => $types])
                    ->whereYear('created_at', now()->timezone(config('app.timezone'))->year)
                    ->whereMonth('created_at', now()->timezone(config('app.timezone'))->month)->exists();
                break;
            default:
                $status = $this->getModel(false)->where(['uid' => $uid, 'entid' => $entid, 'types' => $types])->whereDate('created_at', now()->toDateString())->exists();
                break;
        }

        return $status;
    }

    /**
     * 分组搜索.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupSearch(array $where = [], string $field = "DATE_FORMAT(created_at,'%Y-%m-%d') as days,user_id")
    {
        return $this->search($where)->selectRaw($field)->groupBy('days');
    }

    /**
     * 分组统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupCount(array $where): int
    {
        return $this->getGroupSearch($where)->get()->count();
    }

    /**
     * 按天分组查询.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupList(array $where = [], array $field = ['*'], int $page = 0, int $limit = 0, null|array|string $sort = null, array $with = []): array
    {
        return $this->getGroupSearch($where)->when(isset($where['daily_id']) && $where['daily_id'], function ($query) use ($where) {
            $query->where('daily_id', $where['daily_id']);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->when($sort, function ($query) use ($sort) {
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
        })->get()->each(function (&$value) use ($where, $field, $sort, $with) {
            $where['day'] = $value['days'];
            $list         = $this->getList($where, $field, 0, 0, $sort, $with);
            foreach ($list as &$item) {
                $item['name']   = $item['user']['name'] ?? '';
                $item['avatar'] = $item['user']['avatar'] ?? '';
                unset($item['user']);
            }
            $value['data'] = $list;
        })->toArray();
    }

    /**
     * 获取用户汇报统计
     * @return BaseModel[]|\Illuminate\Database\Eloquent\Collection
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getOnceList($where)
    {
        return $this->search($where)->groupBy('date')->select(['*', DB::raw('DATE(created_at) as date')])->get();
    }

    /**
     * 按天统计已提交用户数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDailySubmitUserCount(array $where): array
    {
        return $this->search($where)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as period, COUNT(DISTINCT user_id) as submit_count")
            ->groupBy('period')
            ->get()
            ->pluck('submit_count', 'period')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return UserDaily::class;
    }
}
