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

namespace App\Http\Service\System;

use App\Constants\CacheEnum;
use App\Constants\MenuEnum;
use App\Constants\RuleEnum;
use App\Http\Contract\System\MenusInterface;
use App\Http\Contract\System\RolesInterface;
use App\Http\Dao\Auth\SystemMenusDao;
use App\Http\Model\System\Menus;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Crud\SystemCrudDashboardService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\SwooleTaskService;
use Tuoluojiang\Open\socket\Room;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\MenusRouteTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 菜单.
 * @mixin SystemMenusDao
 */
class MenusService extends BaseService implements MenusInterface
{
    use MenusRouteTrait;

    private array $founderRule = [];

    private array $founderApi = [];

    private array $initUserRule = [];

    private array $initUserApi = [];

    private array $initCompanyRule = [];

    private array $initCompanyApi = [];

    /**
     * @var mixed[]
     */
    private ?array $menuMap = null;

    private ?array $idToUniqued = null;

    public function __construct(SystemMenusDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取菜单列表不分页.
     */
    public function getMenusList(array $where, int $page = 0, int $limit = 0, array $field = ['*'], array|string $sort = 'id', array $with = []): array
    {
        $where['type'] = 0;
        $menuList      = $this->dao->getMenusList($where, ['id as value', 'pid', 'menu_name as label', 'status'], 0, 0, ['sort' => 'desc', 'id' => 'asc']);
        $menuStatusIds = $this->dao->column($where);
        return ['tree' => get_tree_children($menuList, 'children', 'value'), 'ids' => $menuStatusIds];
    }

    /**
     * 获取全部菜单列表.
     */
    public function getAllMenusList(array $where): array
    {
        $menuList = $this->dao->setDefaultSort('sort')->select($where, ['id', 'pid', 'menu_name', 'menu_type', 'icon', 'is_show', 'type', 'sort'])?->toArray();
        return get_tree_children($menuList);
    }

    /**
     * 获取企业全部菜单.
     * @param int $entId 企业ID
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getMenusForCompany(int $entId, bool $disableId = false): array
    {
        /** @var RolesInterface $roleService */
        $roleService  = app()->get(RolesInterface::class);
        $where['ids'] = $menuStatusIds = $roleService->getCompanySuperRole($entId);
        if (isset($where['ids'])) {
            $where['ids'] = $this->ruleByMenusIds($where['ids']);
        }
        $where['type'] = MenuEnum::TYPE_MENU;
        $menuList      = $this->dao->getMenusList($where, ['id as value', 'pid', 'menu_name as label', 'status'], 0, 0, ['sort' => 'desc', 'id' => 'asc']);
        if ($menuList && $disableId) {
            foreach ($menuList as &$value) {
                $value['disabled'] = $disableId;
            }
        }
        return ['tree' => get_tree_children($menuList, 'children', 'value'), 'ids' => array_map('intval', $menuStatusIds)];
    }

    /**
     * @throws BindingResolutionException
     */
    public function saveMenusForCompany(int $entId, array $rules = [], array $apis = []): bool
    {
        /** @var RolesInterface $roleService */
        $roleService = app()->get(RolesInterface::class);
        return $roleService->saveSystemRole($entId, $rules, $apis);
    }

    /**
     * 获取初始用户菜单.
     * @param string $uuid 用户ID
     * @return mixed
     */
    public function getMenusForInitialUser(string $uuid): array
    {
        return [];
    }

    /**
     * 获取初始企业用户菜单.
     * @return mixed
     */
    public function getMenusForCompanyUser(string $uuid): array
    {
        return [];
    }

    /**
     * 获取用户全部菜单.
     * @return mixed
     */
    public function getMenusForUser(string $uuid, int $entId, bool $isTree = true, array|string $field = ['id', 'pid', 'menu_name', 'menu_path', 'uni_path', 'uni_img', 'icon', 'paths', 'entid', 'position', 'unique_auth', 'is_show', 'component']): array
    {
        $roleService     = app()->get(RolesInterface::class);
        $where['type']   = MenuEnum::TYPE_MENU;
        $where['status'] = 1;
        $is_admin        = app()->get(AdminService::class)->value(['uid' => $uuid], 'is_admin');
        if (! $is_admin) {
            $where['ids'] = $roleService->getRolesForUser($uuid, $entId, withApi: false);
            if (isset($where['ids'])) {
                $where['ids'] = $this->ruleByMenusIds($where['ids']);
            }
        }
        if (is_string($field)) {
            return $this->dao->column($where, $field);
        }
        $menuList = toArray($this->dao->getMenusList($where, $field, 0, 0, 'sort'));
        if ($isTree) {
            return $this->getTreeChildren($menuList);
        }

        return $menuList;
    }

    /**
     * 获取移动端用户菜单.
     * @return mixed
     */
    public function getMenusForUni(string $uuid, int $entId): array
    {
        $userService = app()->get(AdminService::class);
        $ident       = $userService->value(['uid' => $uuid], 'is_admin');
        /** @var RolesInterface $roleService */
        $roleService = app()->get(RolesInterface::class);
        if ($ident) {
            $ids = $roleService->getCompanySuperRole($entId, withApi: false);
        } else {
            $ids = $roleService->getRolesForUser($uuid, $entId, withApi: false);
        }
        $ids      = $this->ruleByMenusIds($ids);
        $topMenus = $this->dao->getMenusList([
            'ids'    => $ids,
            'type'   => 0,
            'pid'    => 0,
            'status' => 1,
        ], ['id as value', 'menu_name'], 0, 0, ['sort' => 'desc', 'id' => 'asc']);
        $menus = [];
        foreach ($topMenus as &$topMenu) {
            $topMenu['children'] = $this->dao->getMenusList([
                'path_like' => $topMenu['value'],
                'ids'       => $ids,
                'type'      => 0,
                'status'    => 1,
                'is_show'   => 1,
                'uni_path'  => true,
            ], ['id as value', 'menu_name', 'uni_path', 'uni_img'], 0, 0, ['sort' => 'desc', 'id' => 'asc']);
            if ($topMenu['children']) {
                $menus[] = $topMenu;
            }
        }
        return $menus;
    }

    /**
     * 获取移动端用户菜单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserMenusForUni(string $userId): array
    {
        $roles = app('enforcer')->getRolesForUser($userId);
        if (empty($roles)) {
            return [];
        }
        // 获取角色对应的所有权限策略
        $policies = app('enforcer')->getPolicy();
        // 筛选出菜单类型的权限（排除按钮、接口等类型）
        $uniques = collect($policies)
            ->filter(function ($policy) use ($roles) {
                [$sub, $obj, $act] = $policy;
                // 条件：角色匹配 + 操作类型为菜单（假设菜单类型的act为'M'）
                return in_array($sub, $roles) && $act === MenuEnum::TYPE_MENU && ! empty($obj);
            })
            ->map(function ($policy) {
                return $policy[1]; // $obj对应菜单的uni_path（或其他唯一标识）
            })
            ->unique()
            ->values()
            ->toArray();
        if (empty($uniques)) {
            return [];
        }
        // 查询所有有权限的菜单（一次性查出，减少数据库查询）
        $allPermittedMenus = $this->dao->getList([
            'uniqued' => $uniques, // 用Enforcer筛选的菜单标识
        ], ['id', 'menu_name', 'uni_path', 'uni_img', 'pid', 'sort', 'parent_uniqued', 'uniqued', 'is_show', 'status'], 0, 0, ['sort', 'id']);
        return collect($allPermittedMenus)
            ->whereNull('parent_uniqued')
            ->map(function ($topMenu) use ($allPermittedMenus) {
                // 查找所有属于当前顶级菜单的子菜单（无论层级，通过祖先链判断）
                $childMenus = $this->findAllChildren(collect($allPermittedMenus), $topMenu['uniqued']);
                // 格式化顶级菜单
                return [
                    'value'     => $topMenu['id'],
                    'menu_name' => $topMenu['menu_name'],
                    'uni_path'  => $topMenu['uni_path'],
                    'uni_img'   => $topMenu['uni_img'] ?? '',
                    'children'  => $childMenus,
                    'is_show'   => $topMenu['is_show'],
                    'status'    => $topMenu['status'],
                ];
            })
            // 过滤掉子菜单为空的顶级菜单
            ->filter(function ($menu) {
                return $menu['children'] && $menu['status'] && $menu['is_show'];
            })->sortByDesc('sort')->values()->toArray();
    }

    /**
     * 保存菜单.
     * @throws BindingResolutionException
     */
    public function saveMenu(array $menuInfo, int $entId, int $id = 0): array
    {
        $oldMenu  = collect($id ? $this->dao->get($id)?->toArray() : [])->filter();
        $menuInfo = collect($menuInfo)->map(function ($value, $key) {
            if ($key === 'unique_auth' && empty($value)) {
                return uniqid('menus');
            }
            return $value;
        })->toArray();
        $pid = (int) ($menuInfo['pid'] ?? 0);
        if ($id && ($pid === $id || ($pid && $this->dao->exists(['id' => $pid, 'path_like' => $id])))) {
            throw $this->exception('上级菜单不能选择自己或自己的下级菜单');
        }
        if ($oldMenu->isEmpty()) {
            $exists = $this->dao->exists(['unique_auth' => $menuInfo['unique_auth'], 'not_id' => $id]);
            if ($exists) {
                throw $this->exception('菜单标识已存在,不允许重复！');
            }
            $menuInfo['uniqued'] = md5(collect($menuInfo)->get('unique_auth', (string) time()) ?: '');
        }
        Arr::forget($menuInfo, 'path');
        $menuInfo = collect($menuInfo)->tap(function (Collection $col) {
            $menuPath = $col->get('menu_path') ?: '';
            $crudId   = $col->get('crud_id', 0);
            $menuType = $col->get('menu_type');
            // 类型1：列表页逻辑
            if ($menuPath && empty($crudId) && $menuType == 1) {
                $tableName = str_replace(['/crud/module/', '/list'], '', $menuPath);
                if ($tableName) {
                    $crudIdVal = app()->get(SystemCrudService::class)->value(['table_name_en' => $tableName], 'id');
                    $col->put('crud_id', $crudIdVal ?: 0);
                    $col->put('uni_path', "/pages/module/list?tablename={$tableName}");
                }
            }
            // 类型2：仪表盘逻辑
            if ($menuPath && $menuType == 2) {
                $crudIdVal = str_replace(['/crud/module/', '/dashboard'], '', $menuPath);
                $col->put('crud_dashboard_id', $crudIdVal ?: 0);
                $col->put('uni_path', "/pages/module/dashboard?id={$crudIdVal}");
            }
            // 处理父级 uniqued，菜单移到顶级时同步清空旧父级标识
            $parentUniqued = $col->get('pid') ? $this->dao->value(['id' => $col->get('pid')], 'uniqued') : null;
            $col->put('parent_uniqued', $parentUniqued ?: null);
        })->toArray();
        if ($id) {
            $this->dao->update($id, $menuInfo);
            $menuCollection = $oldMenu->merge($menuInfo); // 合并旧数据和新数据
        } else {
            $createResult   = $this->dao->create($menuInfo);
            $menuCollection = collect($createResult?->toArray() ?: []);
        }
        if ($menuCollection->isEmpty()) {
            throw $this->exception('保存菜单数据失败');
        }
        $menus       = $menuCollection->toArray();
        $roleService = app()->get(RolesService::class);
        $roleService->saveMenuRole(
            collect($menuInfo)->get('status') ? $menus : [],
            $oldMenu->toArray()
        );
        $roleService->updateSuperRole($menus);
        Cache::tags([CacheEnum::TAG_ROLE, 'rules', 'api-rule'])->flush();
        $this->notifyPermissionChangedForEnterprise($entId, $id ? 'menu_updated' : 'menu_created');
        return $menus;
    }

    public function getEditForm(): array
    {
        return [];
    }

    /**
     * 删除菜单.
     */
    public function deleteMenu(int $id, int $entId): bool
    {
        if ($this->dao->count(['pid' => $id])) {
            throw $this->exception('请先删除下级菜单');
        }
        $info = $this->dao->get($id);
        app()->get(RolesService::class)->saveMenuRole(oldInfo: $info?->toArray());
        $res = $info->delete() && Cache::tags([CacheEnum::TAG_ROLE, 'rules', 'api-rule'])->flush();
        $res && $this->notifyPermissionChangedForEnterprise($entId, 'menu_deleted');
        return $res;
    }

    public function isShow($id, $edit)
    {
        $res = $this->dao->update($id, $edit);
        $res && Cache::tags([CacheEnum::TAG_ROLE, 'rules', 'api-rule'])->flush();
        $res && $this->notifyPermissionChangedForEnterprise(1, 'menu_visibility_changed');
        return $res;
    }

    protected function notifyPermissionChangedForEnterprise(int $entId, string $reason): void
    {
        try {
            $fds = Room::userFd('ent', '', $entId);
            SwooleTaskService::ent()
                ->entid($entId)
                ->data('ent', [
                    'reason' => $reason,
                    'time'   => time(),
                ])
                ->type('permission_changed')
                ->to([])
                ->push();
            Log::info('菜单权限变更 websocket 推送成功', [
                'reason' => $reason,
                'entid'  => $entId,
                'fds'    => $fds,
                'count'  => count($fds),
            ]);
        } catch (\Throwable $e) {
            Log::error('菜单权限变更 websocket 推送失败：' . $e->getMessage(), [
                'reason' => $reason,
                'entid'  => $entId,
            ]);
        }
    }

    /**
     * @param array $menuIds
     * @param mixed $pid
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getAllSubMenus($pid, $menuIds = [])
    {
        if (! $pid) {
            return [];
        }
        $menuId  = ($menuId = $this->dao->column(['pid' => $pid], 'id')) ? $menuId : [];
        $menuIds = array_merge($menuIds, $menuId);
        if (count($menuId) && $this->dao->exists(['pid' => $menuId])) {
            return $this->getAllSubMenus($menuId, $menuIds);
        }
        return $menuIds;
    }

    /**
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMenuUnique(array|object $ids, bool $isRule = true): array
    {
        if ($isRule) {
            return $this->dao->column(['id' => $ids], 'unique_auth');
        }
        $data = [];
        foreach ($ids as $key => $val) {
            $data[$this->dao->value(['id' => $key], 'unique_auth')] = $this->dao->column(['id' => $val], 'unique_auth');
        }
        return $data;
    }

    /**
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMenuId(array|object $uniques, bool $isRule = true): array
    {
        if ($isRule) {
            return $this->dao->column(['unique_auth' => array_filter($uniques)], 'id');
        }
        $data = [];
        foreach ($uniques as $key => $val) {
            $data[$this->dao->value(['unique_auth' => $key], 'id')] = $this->dao->column(['unique_auth' => $val], 'id');
        }
        return $data;
    }

    /**
     * 获取没有添加的菜单.
     * @return string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNoSaveMenusList(int $entId = 0)
    {
        return Cache::tags([CacheEnum::TAG_ROLE])->remember(md5('no-save-menus-list' . $entId), (int) sys_config('system_cache_ttl', 3600), function () use ($entId) {
            $menusList = $this->dao->getMenusList(['type' => MenuEnum::TYPE_API, 'entid' => $entId], ['menu_path', 'methods as method', 'menu_name as name', 'api']);
            $ent       = array_merge($this->diffAssoc($menusList, fn ($val) => str_contains($val['uri'], 'api/ent')));
            $uni       = array_merge($this->diffAssoc($menusList, fn ($val) => str_contains($val['uri'], 'api/uni')));
            return compact('ent', 'uni');
        });
    }

    /**
     * 获取阶梯型菜单数据.
     * @param mixed $disabled
     * @param mixed $isDefault
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCascaderMenus(array $where = ['status' => 1], $disabled = false, $isDefault = []): array
    {
        $menuList = $this->dao->getMenusList($where, ['id as value', 'pid', 'menu_name as label', 'entid', 'component', 'type'], 0, 0, ['sort' => 'desc', 'id' => 'desc']);
        foreach ($menuList as &$value) {
            $value['disabled']   = (bool) $disabled;
            $value['is_default'] = in_array($value['value'], $isDefault);
        }
        return $this->getMenusTree($menuList, 'children', 'value');
    }

    /**
     * 获取创建菜单表单.
     */
    public function getCreateForm(int $entid): array
    {
        return $this->createElementForm('创建菜单', $this->createForm([], $entid), '/admin/system/menus?entid=' . $entid, 'post');
    }

    /**
     * 修改获取菜单表单.
     * @return array
     * @throws BindingResolutionException
     */
    public function getUpdateForm(string $id, int $entid)
    {
        $menu = $this->dao->get($id);
        if (! $menu) {
            throw $this->exception('修改的菜单数据不存在');
        }
        $menu->menu_path = $menu->getAttributes()['menu_path'];
        return $this->createElementForm('修改菜单', $this->createForm($menu, $entid), '/admin/system/menus/' . $id . '?entid=' . $entid, 'put');
    }

    /**
     * 获取登录后的菜单.
     * @param mixed $isTree
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getLoginMenus(int $entid = 0, array $where = [], $isTree = true)
    {
        $defaultWhere = ['status' => 1, 'entid' => $entid];

        if (isset($where['ids'])) {
            $where['ids'] = $this->ruleByMenusIds($where['ids']);
        }
        if ($where) {
            $defaultWhere = array_merge($defaultWhere, $where);
        }
        $menuList   = $this->dao->getMenusList($defaultWhere + ['type' => 0], ['id', 'pid', 'menu_name', 'menu_path', 'icon', 'paths', 'entid', 'position', 'unique_auth', 'is_show'], 0, 0, 'sort');
        $uniqueAuth = collect($this->dao->column($defaultWhere, 'unique_auth'))->uniqueStrict()->filter(function ($val) {
            return (bool) $val;
        })->all();
        if ($isTree) {
            return ['menus' => $this->getTreeChildren($menuList), 'unique_auth' => $uniqueAuth];
        }

        return $menuList;
    }

    /**
     * 获取某个菜单下的权限.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getPidMenusRule(int $pid, array $field = ['menu_name', 'id', 'status', 'type'])
    {
        return $this->dao->getList(['pid' => $pid, 'type' => [MenuEnum::TYPE_API, MenuEnum::TYPE_BUTTON]], $field, 0, 0, 'sort');
    }

    /**
     * 获取某个管理员角色下的权限.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMenusAuth(array $roles, bool $ent = false)
    {
        $menusId        = app()->get(RolesService::class)->getAdminRole($roles, 'apis');
        $menusId        = $this->ruleByMenusIds($menusId);
        $defaultMenusId = $this->getDefaultRuleIds($ent);
        $menusId        = array_unique(array_merge($menusId, $defaultMenusId));
        return $this->getAuthApis($menusId, $ent);
    }

    /**
     * 用菜单id获取接口权限并缓存.
     * @return mixed
     */
    public function getAuthApis(array $apiIds, bool $ent = false)
    {
        return Cache::tags(['api-rule'])->remember(md5(implode(',', $apiIds)), (int) sys_config('system_cache_ttl', 3600), function () use ($apiIds, $ent) {
            return $this->dao->column(['ids' => $apiIds, 'entid' => $ent ? 1 : 0, 'type' => 1], 'api,methods', 'id');
        });
    }

    /**
     * 获取权限选中的完整菜单.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function ruleByMenusIds(array $menusId)
    {
        $paths = $this->dao->column(['ids' => $menusId], 'paths');
        $ids   = [];
        foreach ($paths as $path) {
            $ids = array_merge($ids, $path);
        }
        return array_filter(array_unique(array_merge($ids, $menusId)));
    }

    /**
     * 获取超级管理员权限.
     * @param int $entid
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSuperMenusAll($entid = 0)
    {
        $ruleIds = app()->get(RolesService::class)->getSuperRoleAll();
        return $this->getCascaderMenus(['status' => 1, 'ids' => $ruleIds, 'type' => 0]);
    }

    /**
     * 获取企业顶级菜单列表.
     * @param mixed $entId
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSuperMenus(string $name = '', int $entId = 0): array
    {
        $roleDao = app()->get(RolesService::class)->roleDao;
        // 默认企业权限
        $allRule = $roleDao->get(['entid_like' => 0, 'type' => RuleEnum::FOUNDER], ['rules', 'apis']);
        // 当前企业权限
        $rules = $roleDao->get(['entid_like' => $entId, 'type' => RuleEnum::ENTERPRISE_TYPE, 'level' => 0], ['rules', 'apis']);
        $paths = $this->dao->column(['ids' => $allRule['rules']], 'paths');
        $ids   = [];
        foreach ($paths as $path) {
            $ids = array_merge($ids, $path);
        }
        $menuIds  = array_map('intval', array_filter(array_unique(array_merge($ids, $allRule['rules']))));
        $menuList = $this->dao->getList(['ids' => $menuIds, 'status' => 1, 'name_like' => $name], ['id as value', 'id', 'pid', 'menu_name as label', 'entid'], 0, 0, ['sort' => 'desc', 'id' => 'asc']);
        $menus    = get_tree_children($menuList, 'children', 'value');
        return compact('menus', 'rules');
    }

    /**
     * 获取默认权限id.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDefaultRuleIds(bool $ent = false)
    {
        return Cache::tags(['rules'])->remember('DefaultRuleIds' . ($ent ? 1 : 0), (int) sys_config('system_cache_ttl', 3600), function () use ($ent) {
            return $this->dao->column(['type' => 1, 'entid' => $ent ? 1 : 0]);
        });
    }

    /**
     * 获取tree型数据.
     *
     * @param array $data 数据
     * @param string $childrenName 子数据名
     * @param string $keyName 数据key名
     * @param string $pidName 数据上级key名
     */
    public function getTreeChildren(array $data, string $childrenName = 'children', string $keyName = 'id', string $pidName = 'pid', string $positionChildName = 'top_position', string $positionName = 'position'): array
    {
        $tree = $list = [];
        foreach ($data as $value) {
            $list[$value[$keyName]] = $value;
        }
        foreach ($list as $item) {
            if (isset($list[$item[$pidName]])) {
                $list[$item[$pidName]][$item[$positionName] == 1 ? $positionChildName : $childrenName][] = &$list[$item[$keyName]];
            } else {
                $tree[] = &$list[$item[$keyName]];
            }
        }

        return $tree;
    }

    /**
     * @return true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @deprecated
     */
    public function syncCompanyMenus()
    {
        //        /** @var Common $service */
        //        $service = app()->get(Common::class);
        //        $menus   = $service->syncMenus();
        //        /** @var RolesService $roleService */
        $roleService = app()->get(RolesService::class);
        // //        DB::table('system_menus')->truncate();
        //        $roleService->roleDao->delete(['entid_like' => 0]);
        //        $this->initMenus($this->dao, $menus);
        // //        return $menus;
        //        $res = $this->transaction(function () use ($menus, $roleService) {
        // //            $roleService->roleDao->create([
        // //                'rules'     => $this->founderRule,
        // //                'apis'      => $this->founderApi,
        // //                'type'      => 0,
        // //                'role_name' => '企业超级角色(创始人)',
        // //            ]);
        //            return $roleService->roleDao->update(['entid_like' => true], [
        //                'rules' => $this->founderRule,
        //                'apis'  => $this->founderApi,
        //            ]);
        // //            $roleService->roleDao->create([
        // //                'rules'     => $this->initUserRule,
        // //                'apis'      => $this->initUserApi,
        // //                'type'      => 1,
        // //                'role_name' => '初始角色(无企业)',
        // //            ]);
        // //            $roleService->roleDao->create([
        // //                'rules'     => $this->initCompanyRule,
        // //                'apis'      => $this->initCompanyApi,
        // //                'type'      => 2,
        // //                'role_name' => '初始角色(有企业)',
        // //            ]);
        //            return true;
        //        });
        //        if ($res) {
        //            $roleService->syncRoles();
        //        }
        $roleService->syncRoles();
        return true;
    }

    /**
     * 获取当前用户权限菜单.
     * @param mixed $uid
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserMenus($uid)
    {
        // 获取用户所有角色
        $roles = app('enforcer')->getRolesForUser($uid);
        // 查询这些主体拥有的所有权限规则
        $policies = app('enforcer')->getPolicy();
        // 提取所有菜单（obj）并去重
        $uniques = collect($policies)->filter(function ($policy) use ($roles) {
            // 筛选出主体在目标列表中的规则
            [$sub] = $policy;
            return in_array($sub, $roles);
        })->map(function ($policy) {
            // 提取菜单标识（obj）
            [, $obj] = $policy;
            return $obj;
        })->unique()->values()->all();
        // 查询所有状态为1的菜单（status=0的直接排除）
        $field    = ['id', 'pid', 'menu_name', 'menu_path', 'uni_path', 'uni_img', 'icon', 'entid', 'position', 'unique_auth', 'is_show', 'component', 'uniqued', 'parent_uniqued'];
        $allMenus = $this->dao->getList(['status' => 1, 'type' => ['M']], $field, sort: 'sort');
        // 构建完整树形结构（根菜单parent_uniqued=null）
        $menuTree = $this->buildMenuTree($allMenus);
        // 按权限过滤菜单（核心逻辑）
        return $this->filterMenuByPermission($menuTree, $uniques);
    }

    /**
     * 获取角色权限菜单树形结构.
     * @param mixed $disabled
     * @param mixed $isDefault
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRolesMenuTree(string $uid = 'all', $disabled = false, $isDefault = [])
    {
        // 获取用户所有角色
        $roles = app('enforcer')->getRolesForUser($uid);
        // 查询这些主体拥有的所有权限规则
        $policies = app('enforcer')->getPolicy();
        // 提取所有菜单（obj）并去重
        $uniques = collect($policies)
            ->filter(function ($policy) use ($roles) {
                // 筛选出主体在目标列表中的规则
                [$sub] = $policy;
                return in_array($sub, $roles);
            })
            ->map(function ($policy) {
                // 提取菜单标识（obj）
                [, $obj] = $policy;
                return $obj;
            })->unique()->values(); // 重置索引
        // 查询所有状态为1的菜单（status=0的直接排除）
        $field    = ['id as value', 'pid', 'menu_name as label', 'entid', 'component', 'type', 'uniqued', 'parent_uniqued'];
        $allMenus = collect($this->dao->getList(['status' => 1], $field, sort: ['sort' => 'desc', 'id' => 'desc']))->each(function (&$item) use ($disabled, $isDefault) {
            $item['disabled']   = (bool) $disabled;
            $item['is_default'] = in_array($item['value'], $isDefault);
        })->all();
        // 构建完整树形结构（根菜单parent_uniqued=null）
        return $this->getMenusTree($allMenus, keyName: 'value');
    }

    /**
     * 获取用户按钮权限.
     * @param mixed $userId
     * @return mixed
     */
    public function getUserButtons($userId)
    {
        // 获取用户关联的所有角色
        $roles = app('enforcer')->getRolesForUser($userId);
        // 获取所有权限规则
        $policies = app('enforcer')->getPolicy();
        // 筛选并整理按钮权限
        // 转为一维数组
        return collect($policies)
            ->filter(function ($policy) use ($roles) {
                [$sub, $obj, $act] = $policy;
                // 基础过滤：主体匹配 + 非空校验
                if (! in_array($sub, $roles) || empty($obj) || empty($act)) {
                    return false;
                }
                // 过滤菜单类型（假设菜单类型的act为'M'）
                if ($act === MenuEnum::TYPE_MENU) {
                    return false;
                }
                // 过滤接口类型（假设接口类型的act是HTTP方法，如GET/POST/PUT/DELETE）
                $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
                if (in_array(strtoupper($act), $httpMethods)) {
                    return false;
                }
                // 剩余的视为按钮权限
                return true;
            })
            ->map(function ($policy) {
                return $policy[2]; // 直接提取按钮权限标识
            })->unique()->values()->toArray();
    }

    /**
     * 通过菜单ID获取所有父级ID.
     * @param int $menuId 菜单ID
     * @param bool $reverse 是否反转顺序
     */
    public function getParentIdsById(int $menuId, bool $reverse = false): array
    {
        $this->ensureMenuMaps();

        // 通过ID获取对应的uniqued
        $unique = $this->idToUniqued[$menuId] ?? null;
        if (! $unique) {
            return [];
        }

        $parentIds     = [];
        $visited       = [];
        $currentUnique = $unique;

        while (true) {
            $menu         = $this->menuMap[$currentUnique] ?? null;
            $parentUnique = $menu['parent_uniqued'] ?? null;

            // 终止条件：无父级、父级不存在或循环引用
            if (! $parentUnique || ! isset($this->menuMap[$parentUnique]) || in_array($parentUnique, $visited)) {
                break;
            }

            // 收集父级ID并继续向上查找
            $parentIds[]   = $this->menuMap[$parentUnique]['id'];
            $visited[]     = $parentUnique;
            $currentUnique = $parentUnique;
        }

        return $reverse ? array_reverse($parentIds) : $parentIds;
    }

    /**
     * 批量通过ID数组获取所有父级ID（去重）.
     * @param array $menuIds 菜单ID数组
     */
    public function batchParentIdsByIds(array $menuIds): array
    {
        $this->ensureMenuMaps();

        $ids = [];
        foreach ($menuIds as $id) {
            $ids = array_merge($ids, $this->getParentIdsById((int)$id));
        }
        return array_values(array_unique($ids));
    }

    private function ensureMenuMaps(): void
    {
        if ($this->menuMap !== null && $this->idToUniqued !== null) {
            return;
        }

        $menus = Menus::query()
            ->select(['id', 'uniqued', 'parent_uniqued'])
            ->get()
            ->toArray();

        $this->menuMap     = array_column($menus, null, 'uniqued');
        $this->idToUniqued = array_column($menus, 'uniqued', 'id');
    }

    /**
     * 查找所有属于指定父菜单的子菜单（无论层级）.
     */
    protected function findAllChildren(Collection $menuCollection, string $parent): array
    {
        // 先找出直接子菜单
        $directChildren = $menuCollection->where('parent_uniqued', $parent)->pluck('uniqued')->all();
        if (empty($directChildren)) {
            return [];
        }
        // 找出所有间接子菜单
        $allChildIds = $directChildren;
        $currentIds  = $directChildren;
        // 循环查找所有层级的子菜单
        while (true) {
            $nextLevelIds = $menuCollection->whereIn('parent_uniqued', $currentIds)->pluck('uniqued')->diff($allChildIds)->all();
            if (empty($nextLevelIds)) {
                break; // 没有更多子菜单，退出循环
            }
            $allChildIds = array_merge($allChildIds, $nextLevelIds);
            $currentIds  = $nextLevelIds;
        }
        // 筛选出所有子菜单并格式化
        return $menuCollection
            ->whereIn('uniqued', $allChildIds)
            ->map(function ($child) {
                return [
                    'value'     => $child['id'],
                    'menu_name' => $child['menu_name'],
                    'uni_path'  => $child['uni_path'],
                    'uni_img'   => $child['uni_img'] ?? '',
                    'children'  => [], // 强制为空，只保留二级
                    'is_show'   => $child['is_show'],
                    'status'    => $child['status'],
                ];
            })
            ->filter(function ($value) {
                return $value['uni_path'] && $value['status'] && $value['is_show'];
            })
            ->sortByDesc('sort') // 按sort降序
            ->values()
            ->toArray();
    }

    /**
     * @deprecated
     * @param mixed $menuDao
     * @param mixed $menus
     * @param mixed $path
     * @param mixed $pid
     * @param mixed $level
     */
    protected function initMenus($menuDao, $menus, $path = [], $pid = 0, $level = 0)
    {
        foreach ($menus as $menu) {
            if ($level == $menu['level']) {
                array_walk($menu, function (&$item, $key) use ($pid) {
                    if ($key == 'other') {
                        $item = json_encode($item);
                    }
                    if ($key == 'pid') {
                        $item = $pid;
                    }
                });
                unset($menu['id']);
                $children    = $menu['children'] ?? [];
                $founder     = $menu['founder'];
                $initUser    = $menu['initUser'];
                $initCompany = $menu['initCompany'];
                unset($menu['children'], $menu['founder'], $menu['initUser'], $menu['initCompany']);
                $res = $menuDao->create($menu);
                if ($founder['rule']) {
                    $this->pushRuleId($res->id);
                }
                if ($founder['api']) {
                    $this->pushApiId($pid, $res->id);
                }
                if ($initUser['rule']) {
                    $this->pushRuleId($res->id, 1);
                }
                if ($initUser['api']) {
                    $this->pushApiId($pid, $res->id, 1);
                }
                if ($initCompany['rule']) {
                    $this->pushRuleId($res->id, 2);
                }
                if ($initCompany['api']) {
                    $this->pushApiId($pid, $res->id, 2);
                }
                if ($children) {
                    $this->initMenus($menuDao, $children, array_merge($path, [$res->id]), $res->id, $menu['level'] + 1);
                }
            }
        }
    }

    /**
     * 创建表单.
     * @param mixed $menu
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function createForm($menu, int $entId = 0)
    {
        $menuId    = (int) ($menu['id'] ?? 0);
        $where     = ['type' => MenuEnum::TYPE_MENU];
        if ($menuId) {
            $where['not_id'] = array_merge([$menuId], $this->dao->column(['path_like' => $menuId], 'id'));
        }
        $cascader  = $this->getCascaderMenus($where);
        $cascader  = array_merge([['value' => 0, 'label' => '顶级菜单']], $cascader);
        $path      = $this->getFormPath($menu);
        $menu_path = isset($menu['menu_path']) ? [str_replace(['/admin/crud/module/', '/list'], '', $menu['menu_path'])] : [];
        if (! $menu) {
            $realMenuPath = '';
        } elseif (str_starts_with($menu['menu_path'], '/admin')) {
            $realMenuPath = substr($menu['menu_path'], strlen('/admin'));
        } else {
            $realMenuPath = $menu['menu_path'];
        }
        $crudService = app()->get(SystemCrudService::class);
        $crudTree    = $crudService->getCrudTree(['crud_id' => 0, 'not_name' => $this->dao->getModelMenusName(other: $menu_path)]);
        foreach ($crudTree as &$value) {
            if (isset($value['children'])) {
                foreach ($value['children'] as &$children) {
                    $children['value'] = '/crud/module/' . $children['table_name_en'] . '/list';
                }
            }
        }
        $listValue = [];
        if ($menu && $menu['crud_id']) {
            $crudCate = $crudService->setTrashed()->get($menu['crud_id'], ['cate_ids', 'table_name_en'])?->toArray();
            if ($crudCate && $crudCate['cate_ids']) {
                $listValue = [$crudCate['cate_ids'][0], '/crud/module/' . $crudCate['table_name_en'] . '/list'];
            } else {
                $listValue = [0, '/crud/module/' . $crudCate['table_name_en'] . '/list'];
            }
        }
        return [
            Form::cascader('path', '选择上级菜单')
                ->options($cascader)->value($path)->props(['props' => ['checkStrictly' => true]])->col(24),
            Form::select('type', '权限类型', $menu['type'] ?? 'M')->col(24)->options([
                ['value' => 'M', 'label' => '菜单'],
                ['value' => 'B', 'label' => '按钮'],
                ['value' => 'A', 'label' => '接口'],
            ])->control([
                [
                    'value' => 'M',
                    'rule'  => [
                        Form::input('menu_name', '菜单名称', $menu['menu_name'] ?? '')->required(),
                        Form::frameInput('icon', '菜单图标', get_roule_mobu() . '/setting/icons?field=icon', $menu['icon'] ?? '')->icon('el-icon-circle-plus-outline')->height('590px')->width('1250px')->modal(['modal' => true]),
                        Form::radio('menu_type', '路由类型', $menu['menu_type'] ?? 0)->options([['value' => 0, 'label' => '系统链接'], ['value' => 1, 'label' => '关联实体'], ['value' => 2, 'label' => '数据看板']])->control([
                            [
                                'value' => 0,
                                'rule'  => [
                                    Form::input('menu_path', '路由路径', isset($menu['menu_path']) && $menu['menu_type'] == 0 ? $realMenuPath : '')->required(),
                                    Form::input('component', '前端路径', isset($menu['component']) && $menu['menu_type'] == 0 ? $menu['component'] : ''),
                                    //                                    Form::radio('position', '菜单位置', $menu['position'] ?? 0)->options([['value' => 0, 'label' => '侧方'], ['value' => 1, 'label' => '顶部']]),
                                    Form::input('uni_path', '移动端路径', isset($menu['uni_path']) && $menu['menu_type'] == 0 ? $menu['uni_path'] : '')->maxlength(200),
                                    Form::frameImage('uni_img', '移动端图标', get_image_frame_url(['field' => 'uni_img']), $menu['uni_img'] ?? '')->handleIcon(false)->width('1250px')->height('590px')->modal(['modal' => false, 'showCancelButton' => false, 'showConfirmButton' => false]),
                                ],
                            ],
                            [
                                'value' => 1,
                                'rule'  => [
                                    Form::cascader('list_path', '关联实体')->value((isset($menu['menu_path']) && $menu['menu_type'] == 1) ? $listValue : '')->props([
                                        'props' => [
                                            'emitPath' => false,
                                        ],
                                    ])->options($crudTree)->col(24)->clearable(true),
                                    //                                    Form::radio('position', '菜单位置', $menu['position'] ?? 0)->options([['value' => 0, 'label' => '侧方'], ['value' => 1, 'label' => '顶部']]),
                                    Form::frameImage('uni_img', '移动端图标', get_image_frame_url(['field' => 'uni_img']), $menu['uni_img'] ?? '')->handleIcon(false)->width('1250px')->height('590px')->modal(['modal' => false, 'showCancelButton' => false, 'showConfirmButton' => false]),
                                ],
                            ],
                            [
                                'value' => 2,
                                'rule'  => [
                                    Form::select('dash_path', '关联看板', isset($menu['menu_path']) && $menu['menu_type'] == 2 ? $realMenuPath : '')->options(app()->get(SystemCrudDashboardService::class)->select([
                                        'not_id' => $this->dao->getModelMenusName('dashboard', $menu_path),
                                    ], ['id', 'name as label'])->each(function ($item) {
                                        $item['value'] = '/crud/module/' . $item['id'] . '/dashboard';
                                    })?->toArray())->required()->filterable(true)->col(24)->clearable(true),
                                    //                                    Form::radio('position', '菜单位置', $menu['position'] ?? 0)->options([['value' => 0, 'label' => '侧方'], ['value' => 1, 'label' => '顶部']]),
                                    Form::frameImage('uni_img', '移动端图标', get_image_frame_url(['field' => 'uni_img']), $menu['uni_img'] ?? '')->handleIcon(false)->width('1250px')->height('590px')->modal(['modal' => false, 'showCancelButton' => false, 'showConfirmButton' => false]),
                                ],
                            ],
                        ]),
                        Form::number('sort', '排序', $menu['sort'] ?? 0)->min(0)->max(999999),
                        Form::switches('is_show', '隐藏菜单', $menu['is_show'] ?? 0)->inactiveValue(0)->activeValue(1)->inactiveText('隐藏')->activeText('展示'),
                    ],
                ], [
                    'value' => 'B',
                    'rule'  => [
                        Form::input('menu_name', '按钮名称', $menu['menu_name'] ?? '')->required(),
                        Form::input('unique_auth', '权限标识', $menu['unique_auth'] ?? '')->required(),
                        Form::number('sort', '排序', $menu['sort'] ?? 0)->min(0)->max(999999),
                    ],
                ], [
                    'value' => 'A',
                    'rule'  => [
                        Form::frameInput('menu_name', '权限名称', get_roule_mobu() . '/setting/auth?field=rule&entid=' . $entId, $menu['menu_name'] ?? '')->icon('el-icon-s-grid')->height('590px')->width('1250px')->modal(['modal' => true])->required()->validate(['type' => 'string']),
                        Form::input('api', '权限路由', $menu['api'] ?? '')->required(),
                        Form::select('methods', '请求方式', $menu['methods'] ?? '')->options([
                            ['value' => 'GET', 'label' => 'GET'],
                            ['value' => 'PUT', 'label' => 'PUT'],
                            ['value' => 'POST', 'label' => 'POST'],
                            ['value' => 'DELETE', 'label' => 'DELETE'],
                        ])->required(),
                        Form::number('sort', '排序', $menu['sort'] ?? 0)->min(0)->max(999999),
                    ],
                ],
            ]),
            Form::switches('status', '是否可用', $menu['status'] ?? 1)->inactiveValue(0)->activeValue(1)->inactiveText('禁用')->activeText('启用'),
        ];
    }

    protected function getFormPath($menu): array
    {
        if (! $menu || empty($menu['id'])) {
            return [0];
        }
        return $this->getParentIdsById((int) $menu['id'], true) ?: [0];
    }

    /**
     * 构建菜单树.
     */
    protected function buildMenuTree(array $menus, mixed $parent = ''): array
    {
        // 转为集合，按 parent_uniqued 分组（一次性分组，避免递归中重复遍历）
        $menuCollection = collect($menus)->groupBy('parent_uniqued');
        // 递归处理树结构（提取为闭包，避免重复写集合转换）
        $buildTree = function (mixed $currentParent) use (&$buildTree, $menuCollection) {
            // 获取当前父级的所有子菜单，无则返回空集合
            return $menuCollection->get($currentParent, collect())->map(function (array $menu) use (&$buildTree) {
                $children = $buildTree($menu['uniqued']);
                if (! $children->isEmpty()) {
                    $menu['children'] = $children->toArray();
                }
                return $menu;
            });
        };
        return $buildTree($parent)->toArray();
    }

    /**
     * 菜单权限过滤.
     * @param mixed $menuTree
     * @param mixed $allowedIds
     * @return array
     */
    protected function filterMenuByPermission($menuTree, $allowedIds)
    {
        $filtered = [];
        foreach ($menuTree as $menu) {
            // 1. 先处理子菜单（递归过滤）
            $hasChildren      = ! empty($menu['children']);
            $filteredChildren = [];
            if ($hasChildren) {
                $filteredChildren = $this->filterMenuByPermission($menu['children'], $allowedIds);
            }
            // 2. 判断当前菜单是否需要显示
            $shouldShow = false;
            // 条件1：自身有权限
            if (in_array($menu['uniqued'], $allowedIds)) {
                $shouldShow = true;
            }
            // 条件2：自身无权限，但子菜单有过滤后的有效菜单
            elseif (! empty($filteredChildren)) {
                $shouldShow = true;
            }

            // 3. 符合条件则加入结果，并更新子菜单为过滤后的数据
            if ($shouldShow) {
                if ($hasChildren) {
                    $menu['children'] = $filteredChildren; // 用过滤后的子菜单替换原数组
                }else{
                    $menu['children'] = [];
                }
                $filtered[] = $menu;
            }
        }
        return $filtered;
    }

    private function pushRuleId($ruleId, $type = 0)
    {
        switch ($type) {
            case 0:
                $this->founderRule[] = $ruleId;
                break;
            case 1:
                $this->initUserRule[] = $ruleId;
                break;
            case 2:
                $this->initCompanyRule[] = $ruleId;
                break;
        }
    }

    private function pushApiId($pid, $apiId, $type = 0)
    {
        switch ($type) {
            case 0:
                $this->founderApi[$pid][] = $apiId;
                break;
            case 1:
                $this->initUserApi[$pid][] = $apiId;
                break;
            case 2:
                $this->initCompanyApi[$pid][] = $apiId;
                break;
        }
    }

    /**
     * 获取tree型数据.
     *
     * @param array $data 数据
     * @param string $children 子数据名
     * @param string $keyName 数据key名
     * @param string $pidName 数据上级key名
     * @param mixed $apiName
     */
    private function getMenusTree(array $data, string $children = 'children', string $keyName = 'id', string $pidName = 'pid', string $apiName = 'apis')
    {
        $list = [];
        foreach ($data as $value) {
            $list[$value[$keyName]] = $value;
        }
        $tree = []; // 格式化好的树
        foreach ($list as $item) {
            if (isset($list[$item[$pidName]])) {
                if ($list[$item[$keyName]]['type'] == MenuEnum::TYPE_MENU) {
                    $list[$item[$pidName]][$children][] = &$list[$item[$keyName]];
                } else {
                    $list[$item[$pidName]][$apiName][] = &$list[$item[$keyName]];
                }
            } else {
                $tree[] = &$list[$item[$keyName]];
            }
        }

        return $tree;
    }
}
