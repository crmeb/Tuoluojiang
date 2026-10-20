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

namespace App\Http\Service\User;

use App\Constants\MenuEnum;
use App\Http\Dao\User\UserQuickDao;
use App\Http\Service\Config\QuickCateService;
use App\Http\Service\Config\QuickService;
use App\Http\Service\System\MenusService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 个人快捷入口.
 */
class UserQuickService extends BaseService
{
    /**
     * 业绩统计类型.
     * @var array|string[]
     */
    public const STATISTICS_TYPE = [
        'income'            => '本月业绩',
        'today_income'      => '今日业绩',
        'yesterday_income'  => '昨日业绩',
//        'client'            => '累计客户',
        'month_client'      => '本月新增客户',
        'today_client'      => '今日新增客户',
        'incomplete_follow' => '跟进未完成',
        'today_follow'      => '今日跟进记录',
        'today_contract'    => '今日新增订单',
    ];

    public array $defaultPcMenus = ['/user/memorandum'];

    public array $defaultUniMenus = ['/pages/users/memorandum/index'];

    public function __construct(UserQuickDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取工作台快捷方式.
     * @param mixed $admin
     */
    public function getFastEntry($admin, bool $isPc = true): array
    {
        $userId = auth('admin')->user()->is_admin ? 'all' : auth('admin')->id();
        $roles  = app('enforcer')->getRolesForUser($userId);
        if (empty($roles)) {
            return [];
        }
        // 获取角色对应的所有权限策略
        $policies = app('enforcer')->getPolicy();
        // 筛选出菜单类型的权限（排除按钮、接口等类型）
        $uniques = collect($policies)
            ->filter(function ($policy) use ($roles) {
                [$sub, $obj, $act] = $policy;
                return in_array($sub, $roles) && $act === MenuEnum::TYPE_MENU && ! empty($obj);
            })->map(function ($policy) {
                return $policy[1]; // $obj对应菜单的uni_path（或其他唯一标识）
            })->unique()->values()->toArray();
        $menus           = app()->get(MenusService::class)->select(['uniqued' => $uniques, 'status' => 1], ['menu_path', 'uni_path'])?->toArray();
        $field           = ['id', 'name', 'cid', 'pc_url', 'uni_url', 'image'];
        $with            = ['cate' => fn ($q) => $q->select(['id', 'cate_name', 'pic'])];
        $quickWhere      = ['uuid' => $admin->uid, 'entid' => 1];
        $where['cid']    = app()->get(QuickCateService::class)->column(['is_show' => 1], 'id');
        $where['status'] = 1;
        if ($isPc) {
            $where['pc_show'] = 1;
            $userQuickIds     = $this->dao->value($quickWhere, 'pc_menu_id') ?: [];
        } else {
            $where['uni_show'] = 1;
            $userQuickIds      = $this->dao->value($quickWhere, 'app_menu_id') ?: [];
        }
        $quickData = collect(app()->get(QuickService::class)->select($where, $field, $with)?->toArray())->map(function ($item) use ($userQuickIds) {
            foreach ($userQuickIds as $k => $v) {
                if ($item['id'] == $v) {
                    $item['sort'] = $k;
                }
            }
            return $item;
        })->filter(function ($item) use ($isPc, $menus) {
            return $isPc ? in_array('/admin' . $item['pc_url'], array_merge(array_column($menus, 'menu_path'), $this->defaultPcMenus)) : in_array($item['uni_url'], array_merge(array_column($menus, 'uni_path'), $this->defaultUniMenus));
        })->sortByDesc('sort')->unique()->toArray();
        $cates  = array_values(array_column($quickData, 'cate'));
        $cates  = array_intersect_key($cates, array_unique(array_column($cates, 'id')));
        $cates  = array_values($cates);
        $checkd = [];
        foreach ($quickData as &$quick) {
            if (in_array($quick['id'], $userQuickIds)) {
                $quick['checked'] = 1;
                $checkd[]         = $quick;
            } else {
                $quick['checked'] = 0;
            }
            $quick['checked'] = in_array($quick['id'], $userQuickIds) ? 1 : 0;
            unset($quick['cate']);
        }
        $sort = array_column($checkd, 'sort');
        array_multisort($sort, SORT_ASC, $checkd);
        foreach ($cates as &$cate) {
            $cate['fast_entry'] = array_values(array_filter($quickData, fn ($val) => $val['cid'] == $cate['id']));
        }
        return compact('cates', 'checkd');
    }

    /**
     * 自定义快捷入口.
     * @throws BindingResolutionException
     */
    public function setFastEntry(string $uuid, array $data, int $entId = 1, bool $isPc = true): mixed
    {
        $data = array_filter(array_map('intval', $data));
        if (count($data) > 12) {
            throw $this->exception('最多只能选择12个快捷入口');
        }
        $where = [
            'uuid'  => $uuid,
            'entid' => $entId,
        ];
        $data = is_array($data) ? json_encode($data) : $data;
        if ($isPc) {
            $res = $this->dao->updateOrCreate($where, ['pc_menu_id' => $data]);
        } else {
            $res = $this->dao->updateOrCreate($where, ['app_menu_id' => $data]);
        }
        return $res;
    }

    /**
     * 获取统计管理数据.
     * @return string[]
     * @throws BindingResolutionException
     */
    public function getStatisticsType(string $uuid, int $entId = 1): array
    {
        $list           = $select = [];
        $statisticsType = $this->dao->value(['uuid' => $uuid, 'entid' => $entId], 'statistics_type') ?? [];
        foreach ($statisticsType as $item) {
            $select[] = ['id' => $item, 'title' => self::STATISTICS_TYPE[$item]];
        }

        foreach (self::STATISTICS_TYPE as $key => $title) {
            $list[] = ['id' => $key, 'title' => $title];
        }
        return ['list' => $list, 'select' => $select];
    }

    /**
     * 获取统计管理数据.
     * @return string[]
     * @throws BindingResolutionException
     */
    public function getSelectType(string $uuid, int $entId = 1): array
    {
        return array_keys(self::STATISTICS_TYPE);
        $info = toArray($this->dao->get(['uuid' => $uuid, 'entid' => $entId], ['statistics_type']));
        if (! $info || ! isset($info['statistics_type'])) {
            return array_keys(self::STATISTICS_TYPE);
        }
        return $info['statistics_type'];
    }

    /**
     * 统计设置.
     * @throws BindingResolutionException
     */
    public function setStatisticsType(string $uuid, array $data, int $entId = 1): mixed
    {
        foreach ($data as $datum) {
            if ($datum && ! array_key_exists($datum, self::STATISTICS_TYPE)) {
                throw $this->exception('业绩类型错误');
            }
        }

        $where = ['uuid' => $uuid, 'entid' => $entId];
        return $this->dao->updateOrCreate($where, array_merge($where, ['statistics_type' => json_encode($data)]));
    }

    /**
     * 初始化快捷入口.
     * @throws BindingResolutionException
     */
    public function init(string $uuid, int $entId = 1): void
    {
        $where = ['uuid' => $uuid, 'entid' => $entId];
        $this->dao->updateOrCreate(['uuid' => $uuid, 'entid' => $entId], array_merge($where, [
            'statistics_type' => json_encode(array_keys(self::STATISTICS_TYPE)),
            'pc_menu_id'      => $this->getInitQuickDataByUuid($uuid),
            'app_menu_id'     => $this->getInitQuickDataByUuid($uuid, false),
        ]));
    }

    /**
     * 获取用户快捷入口默认数据.
     * @throws BindingResolutionException
     */
    private function getInitQuickDataByUuid(string $uuid, bool $isPc = true, int $entId = 1): string
    {
        $cid      = app()->get(QuickCateService::class)->column(['is_show' => 1], 'id');
        $list     = app()->get(MenusService::class)->getMenusForUser($uuid, $entId, false);
        $menuPath = array_filter(array_column($list, $isPc ? 'menu_path' : 'uni_path'));

        $where              = ['status' => 1, ($isPc ? 'pc' : 'uni') . '_show' => 1, 'cid' => $cid];
        [$path, $needField] = $isPc ? ['admin', 'pc_url'] : ['', 'uni_url'];
        $quickList          = toArray(app()->get(QuickService::class)->select($where, ['id', 'pc_url', 'uni_url']));
        return $quickList ? json_encode(array_slice(array_column(array_filter($quickList, function ($item) use ($menuPath, $path, $needField) {
            return in_array(($path ? '/' : '') . $path . $item[$needField], $menuPath);
        }), 'id'), 0, 8)) : '';
    }
}
