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

namespace App\Http\Service\System;

use App\Constants\CacheEnum;
use App\Constants\CommonEnum;
use App\Constants\DataPermissionLevelEnum;
use App\Constants\MenuEnum;
use App\Constants\ModuleEnum;
use App\Constants\RuleEnum;
use App\Http\Contract\System\MenusInterface;
use App\Http\Contract\System\RolesInterface;
use App\Http\Dao\Auth\RoleDao;
use App\Http\Dao\Auth\RoleUserDao;
use App\Http\Dao\Auth\SystemRoleDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Crud\SystemCrudRoleService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Jobs\SystemRoleJob;
use Casbin\Exceptions\CasbinException;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\SwooleTaskService;
use Tuoluojiang\Open\socket\Room;
use Tuoluojiang\Open\traits\service\RolesTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 权限规则.
 */
class RolesService extends BaseService implements RolesInterface
{
    use RolesTrait;

    public SystemRoleDao $roleDao;

    private RoleUserDao $roleUserDao;

    public function __construct(RoleDao $dao, SystemRoleDao $roleDao, RoleUserDao $roleUserDao)
    {
        $this->dao         = $dao;
        $this->roleDao     = $roleDao;
        $this->roleUserDao = $roleUserDao;
    }

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCompanySuperRole(int $entId, bool $withRule = true, bool $withApi = true, string $origin = CommonEnum::ORIGIN_OWN): array
    {
        if ($origin === CommonEnum::ORIGIN_OWN) {
            $types = [];
            if ($withRule) {
                $types[] = MenuEnum::TYPE_MENU;
            }
            if ($withApi) {
                $types[] = MenuEnum::TYPE_BUTTON;
                $types[] = MenuEnum::TYPE_API;
            }
            return app()->get(MenusService::class)->column(['type' => $types], 'id');
        }
        $result = $this->roleDao->get(['type' => MenuEnum::TYPE_SUPER_COMPANY, 'entid_like' => $entId], ['rules', 'apis', 'id'])?->toArray();
        $roles  = [];
        if ($withRule) {
            $roles = array_merge($roles, $result['rules'] ?? []);
        }
        if ($withApi) {
            $roles = array_merge($roles, $result['apis']);
        }
        return array_unique($roles);
    }

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRolesForInitialUser(bool $withRule = true, bool $withApi = true): array
    {
        $roles  = [];
        $result = $this->roleDao->get(['type' => RuleEnum::INITIAL_USER, 'entid_like' => 0], ['rules', 'apis'])?->toArray();
        if (! $result) {
            return $roles;
        }
        if ($withRule) {
            $roles = array_merge($roles, $result['rules']);
        }
        if ($withApi) {
            $roles = array_merge($roles, $result['apis']);
        }
        return array_unique($roles);
    }

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRolesForCompanyUser(bool $withRule = true, bool $withApi = true): array
    {
        $roles  = [];
        $result = $this->roleDao->get(['type' => RuleEnum::INITIAL_COMPANY, 'entid_like' => 0], ['rules', 'apis'])?->toArray();
        if (! $result) {
            return $roles;
        }
        if ($withRule) {
            $roles = array_merge($roles, $result['rules']);
        }
        if ($withApi) {
            $roles = array_merge($roles, $result['rules']);
        }
        return array_unique($roles);
    }

    /**
     * 获取用户权限.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRolesForUser(string $uuid, int $entId, bool $withRule = true, bool $withApi = true): array
    {
        $roles = [];
        if (! $uuid) {
            return $roles;
        }
        $roles    = array_merge($roles, $this->getRolesForInitialUser($withRule, $withApi));
        $userInfo = toArray(app()->get(AdminService::class)->get(['uid' => $uuid]));
        if (! $userInfo) {
            return $roles;
        }
        $roles = array_merge($roles, $this->getRolesForCompanyUser($withRule, $withApi));
        if ($userInfo['is_admin']) {
            $roleIds = $this->getCompanySuperRole($entId, $withRule, $withApi);
            return array_merge($roles, $roleIds);
        }
        if (! $userInfo['roles']) {
            return $roles;
        }
        $roleInfo = array_map(function ($item) {
            $apis = [];
            foreach ((array) $item['apis'] as $api) {
                if (is_array($api)) {
                    $apis = array_merge($apis, $api);
                } else {
                    $apis[] = $api;
                }
            }
            $item['apis'] = $apis;
            return $item;
        }, toArray($this->dao->select(['ids' => $userInfo['roles']])));
        if ($withRule) {
            foreach ($roleInfo as $value) {
                $roles = array_merge($roles, $value['rules']);
            }
        }
        if ($withApi) {
            foreach ($roleInfo as $value) {
                $roles = array_merge($roles, $value['apis']);
            }
        }
        return array_unique($roles);
    }

    /**
     * 获取权限对应菜单ID.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRolesId(array $roleIds)
    {
        $roles = array_map(function ($item) {
            $apis = [];
            foreach ((array) $item['apis'] as $api) {
                if (is_array($api)) {
                    $apis = array_merge($apis, $api);
                } else {
                    $apis[] = $api;
                }
            }
            $item['all'] = array_merge($item['rules'], $apis);
            return $item;
        }, toArray($this->dao->select(['id' => $roleIds], ['rules', 'apis'])));
        $roleIds = [];
        foreach ($roles as $role) {
            $roleIds = array_merge($roleIds, $role['all']);
        }
        return $roleIds;
    }

    /**
     * 获取角色列表分页.
     * @param null $sort
     */
    public function getRolesPageList(array $where, int $page, int $limit, array $field, $sort, array $with): array
    {
        return [];
    }

    /**
     * 获取角色列表.
     */
    public function getRolesList(array $where, array $field = ['*'], array|string $sort = 'id', array $with = []): array
    {
        $service = app()->get(FrameService::class);
        $userIds = app()->get(AdminService::class)->column(['status' => 1], 'id');
        return $this->dao->setDefaultSort($sort)->select($where, $field, $with)
            ->each(function (&$item) use ($service, $userIds) {
                $summary            = $this->summarizeModulePermissions($item['module_permissions'] ?? []);
                $item['data_level'] = $summary['data_level'];
                $item['frame_id']   = $summary['frame_id'];
                $item['directly']   = $summary['directly'];
                $item['frame']      = toArray($service->select(['ids' => $item['frame_id']], ['id', 'name']));
                $item['user_count'] = $this->roleUserDao->getCount(['role_id' => $item['id'], 'user_ids' => $userIds]);
                unset($item['module_permissions']);
            })?->toArray();
    }

    /**
     * 汇总模块权限为列表兼容字段；不再读取角色表旧数据权限列.
     */
    protected function summarizeModulePermissions(array $modulePermissions): array
    {
        $permissions = collect(ModuleEnum::getModuleFieldConfig())
            ->map(function ($item, $module) use ($modulePermissions) {
                if (! isset($modulePermissions[$module])) {
                    return null;
                }

                return [
                    'data_level' => (int) ($modulePermissions[$module]['data_level'] ?? DataPermissionLevelEnum::SELF),
                    'frame_id'   => (array) ($modulePermissions[$module]['frame_id'] ?? []),
                    'directly'   => ! empty($modulePermissions[$module]['directly']) ? 1 : 0,
                ];
            })
            ->filter();

        if ($permissions->isEmpty()) {
            return [
                'data_level' => DataPermissionLevelEnum::NONE,
                'frame_id'   => [],
                'directly'   => 0,
            ];
        }

        $maxLevel = $permissions->max(fn ($permission) => $permission['data_level']);
        return [
            'data_level' => $maxLevel,
            'frame_id'   => $permissions->filter(fn ($permission) => $permission['data_level'] === $maxLevel)
                ->flatMap(fn ($permission) => $permission['frame_id'])
                ->unique()
                ->values()
                ->all(),
            'directly'   => $permissions->contains(fn ($permission) => $permission['directly']) ? 1 : 0,
        ];
    }

    /**
     * 获取角色菜单树.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRoleInfo(int $entId, int $id = 0): array
    {
        return Cache::tags(CacheEnum::TAG_ROLE)->remember(md5('role_info_' . $entId . $id), (int) sys_config('system_cache_ttl', 3600), function () use ($id) {
            $ruleInfo     = [];
            $apis         = [];
            $frameService = app()->get(FrameService::class);
            if ($id) {
                $ruleInfo = $this->dao->get($id);
                if (! $ruleInfo) {
                    throw $this->exception('修改的角色不存在');
                }
                $summary                = $this->summarizeModulePermissions($ruleInfo['module_permissions'] ?? []);
                $ruleInfo['data_level'] = $summary['data_level'];
                $ruleInfo['frame_id']   = $summary['frame_id'];
                $ruleInfo['directly']   = $summary['directly'];
                $ruleInfo['frame']      = $frameService->select(['ids' => $ruleInfo['frame_id']], ['id', 'name'])?->toArray();
                $apis                   = $ruleInfo['apis'];
            }
            $modules           = ModuleEnum::all();
            $modulePermissions = $ruleInfo['module_permissions'] ?? [];
            $moduleFrameIds    = collect($modulePermissions)
                ->flatMap(fn ($permission) => (array) ($permission['frame_id'] ?? []))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $moduleFrames      = $moduleFrameIds
                ? ($frameService->select(['ids' => $moduleFrameIds], ['id', 'name'])?->keyBy('id') ?? collect())
                : collect();
            $module_permission = collect();
            collect(ModuleEnum::getModuleFieldConfig())->each(function ($item, $key) use ($modules, &$module_permission, $modulePermissions, $moduleFrames) {
                $frameIds = (array) ($modulePermissions[$key]['frame_id'] ?? []);
                $frames   = collect($frameIds)
                    ->map(fn ($frameId) => $moduleFrames->get($frameId))
                    ->filter()
                    ->map(fn ($frame) => [
                        'id'   => (int) $frame['id'],
                        'name' => $frame['name'],
                    ])
                    ->values()
                    ->all();
                $module_permission->put($key, [
                    'data_level'  => $modulePermissions[$key]['data_level'] ?? DataPermissionLevelEnum::SELF,
                    'directly'    => $modulePermissions[$key]['directly'] ?? 0,
                    'frame_id'    => $frameIds,
                    'frames'      => $frames,
                    'frame_names' => array_column($frames, 'name'),
                    'module_name' => $modules[$key],
                ]);
            });
            $tree = app()->get(MenusService::class)->getRolesMenuTree(isDefault: $apis);
            $crud = collect(app()->get(SystemCrudService::class)->setDefaultSort('id')->select(['crud_id' => 0], ['id', 'table_name', 'table_name_en', 'cate_ids'], [
                'role' => fn ($q) => $q->where('role_id', $id)->select(['created', 'reade', 'reade_frame', 'updated', 'updated_frame', 'deleted', 'deleted_frame', 'role_id', 'crud_id', 'transfer', 'transfer_frame', 'share', 'share_frame']),
            ]))->each(function ($item) use ($frameService, $id) {
                if (! $id) {
                    $item['role'] = [];
                }
                $item['created']        = $item['role']['created'] ?? 0;
                $item['reade']          = $item['role']['reade'] ?? 0;
                $item['reade_frame']    = $item['role']['reade_frame'] ?? [];
                $item['reade_frames']   = $frameService->select(['ids' => $item['reade_frame']], ['id', 'name', 'pid']) ?? [];
                $item['updated']        = $item['role']['updated'] ?? 0;
                $item['updated_frame']  = $item['role']['updated_frame'] ?? [];
                $item['updated_frames'] = $frameService->select(['ids' => $item['updated_frame']], ['id', 'name', 'pid']) ?? [];
                $item['deleted']        = $item['role']['deleted'] ?? 0;
                $item['deleted_frame']  = $item['role']['deleted_frame'] ?? [];
                $item['deleted_frames'] = $frameService->select(['ids' => $item['deleted_frame']], ['id', 'name', 'pid']) ?? [];

                $item['transfer']        = $item['role']['transfer'] ?? 0;
                $item['transfer_frame']  = $item['role']['transfer_frame'] ?? [];
                $item['transfer_frames'] = $frameService->select(['ids' => $item['transfer_frame']], ['id', 'name', 'pid']) ?? [];

                $item['share']        = $item['role']['share'] ?? 0;
                $item['share_frame']  = $item['role']['share_frame'] ?? [];
                $item['share_frames'] = $frameService->select(['ids' => $item['share_frame']], ['id', 'name', 'pid']) ?? [];

                unset($item['role']);
            })->toArray();
            unset($ruleInfo['module_permissions']);
            return ['tree' => $tree, 'module_permission' => $module_permission->all(), 'crud' => $crud, 'rule' => $ruleInfo];
        });
    }

    /**
     * 添加角色权限.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveRole(string $name = '', array $rules = [], array $apis = [], int $status = 1, array $modulePermissions = [], array $crud = []): bool
    {
        $name        = trim($name);
        $entId       = 1;
        $menuService = app()->get(MenusInterface::class);
        $this->checkRoleNameUnique($entId, $name);
        $save        = $this->transaction(function () use ($rules, $apis, $name, $status, $menuService, $crud, $modulePermissions, $entId) {
            $save = $this->dao->create([
                'rules'       => $rules,
                'rule_unique' => $menuService->getMenuUnique($rules),
                'apis'        => collect($apis)->filter(fn ($api) => in_array($api, $menuService->column(['status' => 1], 'id')))->all(),
                'api_unique'  => $menuService->getMenuUnique($apis),
                'entid'       => $entId,
                'role_name'   => $name,
                'status'      => $status,
            ]);
            $crud && app()->get(SystemCrudRoleService::class)->saveRoles($save->id, $crud);
            $modulePermissions && app(ModulePermissionService::class)->setRoleModulePermissions($save->id, $modulePermissions);
            return $save;
        });
        SystemRoleJob::dispatch($save->id, array_merge($rules, $menuService->batchParentIdsByIds($rules)), $apis);
        return $save->id && Cache::tags([CacheEnum::TAG_ROLE])->flush();
    }

    /**
     * 角色启用/禁用.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function changeRole(int $entId, int $roleId, int $status): bool
    {
        $role = $this->dao->get($roleId)?->toArray();
        if (! $role) {
            throw $this->exception('未找到可修改的角色');
        }
        if ($role['status'] == $status) {
            return true;
        }
        $affectedUserIds   = $this->getRoleUserIds($roleId);
        $affectedUserUuids = $this->getUserUuids($affectedUserIds);
        if ($status) {
            $res = $this->transaction(function () use ($roleId, $status, $affectedUserIds) {
                if ($affectedUserIds) {
                    foreach ($affectedUserIds as $userId) {
                        app('enforcer')->addRoleForUser((string) $userId, 'role_' . $roleId);
                    }
                    $this->roleUserDao->update(['role_id' => $roleId], ['status' => $status]);
                }
                return (bool) $this->dao->update($roleId, ['status' => $status]);
            });
        } else {
            $res = $this->transaction(function () use ($roleId, $status) {
                app('enforcer')->deleteRole('role_' . $roleId);
                $this->roleUserDao->update(['role_id' => $roleId], ['status' => $status]);
                return (bool) $this->dao->update($roleId, ['status' => $status]);
            });
        }
        if ($res) {
            Cache::tags([CacheEnum::TAG_ROLE])->flush();
            $this->notifyPermissionChangedForRoleStatus($roleId, $affectedUserIds, $affectedUserUuids, $entId);
        }
        return (bool) $res;
    }

    /**
     * 修改角色权限.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateRole(int $id, string $name = '', array $rules = [], array $apis = [], int $status = 1, array $modulePermissions = [], array $crud = []): bool
    {
        $name  = trim($name);
        $entId = 1;
        if (! $this->dao->exists(['id' => $id, 'entid' => $entId])) {
            throw $this->exception('未找到可修改的角色');
        }
        $this->checkRoleNameUnique($entId, $name, $id);
        $menuService = app()->get(MenusService::class);
        $save        = $this->dao->update($id, [
            'rules'       => $rules,
            'rule_unique' => $menuService->getMenuUnique($rules),
            'apis'        => collect($apis)->filter(fn ($api) => in_array($api, $menuService->column(['status' => 1], 'id')))->all(),
            'api_unique'  => $menuService->getMenuUnique($apis),
            'entid'       => $entId,
            'role_name'   => $name,
            'status'      => $status,
        ]);
        $crud && app()->get(SystemCrudRoleService::class)->saveRoles($id, $crud) && Cache::tags([CacheEnum::TAG_ROLE])->flush();
        $modulePermissions && app(ModulePermissionService::class)->setRoleModulePermissions($id, $modulePermissions) && Cache::tags([CacheEnum::TAG_ROLE])->flush();
        SystemRoleJob::dispatch($id, array_merge($rules, $menuService->batchParentIdsByIds($rules)), $apis, true);
        Cache::tags([CacheEnum::TAG_ROLE])->flush();
        return (bool) $save;
    }

    /**
     * 校验同企业下角色名称唯一.
     */
    protected function checkRoleNameUnique(int $entId, string $name, int $ignoreId = 0): void
    {
        if ($name === '') {
            throw $this->exception('角色名称必须填写');
        }

        $where = [
            'entid'     => $entId,
            'role_name' => $name,
        ];
        if ($ignoreId) {
            $where['not_id'] = $ignoreId;
        }
        if ($this->dao->exists($where)) {
            throw $this->exception('角色名称已存在，请勿重复添加');
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveSystemRole(int $entId, array $rules = [], array $apis = []): bool
    {
        Cache::tags([CacheEnum::TAG_ROLE])->flush();
        $res = $this->roleDao->updateOrCreate(['type' => 'enterprise', 'entid_like' => $entId], [
            'rules' => $rules,
            'apis'  => $apis,
            'entid' => $entId,
        ]);
        if ($res) {
            $this->notifyPermissionChangedForEnterpriseOnline('enterprise_menu_changed', $entId);
        }
        return $res;
    }

    /**
     * 删除角色.
     */
    public function deleteRole(int $id, int $entId): bool
    {
        if ($this->dao->exists(['id' => $id, 'entid' => $entId])) {
            $userIds = toArray($this->roleUserDao->column(['role_id' => $id], 'user_id'));
            $res     = $this->transaction(function () use ($id) {
                app('enforcer')->deleteRole('role_' . $id);
                $this->roleUserDao->delete(['role_id' => $id]);
                app()->get(SystemCrudRoleService::class)->delete(['role_id' => $id]);
                return (bool) $this->dao->delete($id);
            });
            if ($res) {
                Cache::tags([CacheEnum::TAG_ROLE])->flush();
                $this->notifyPermissionChanged($this->getUserUuids($userIds), 'role_deleted', $entId);
            }
            return (bool) $res;
        }
        throw $this->exception('未找到可删除的角色');
    }

    /**
     * 获取角色下的用户UUID.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRoleUser(int $id, int $entId): array
    {
        if (! $this->dao->exists(['id' => $id, 'entid' => $entId])) {
            throw $this->exception('无效的角色ID');
        }
        [$page, $limit] = $this->getPageValue();
        $list           = $this->roleUserDao->select(['role_id' => $id, 'entid' => $entId], with: [
            'user' => fn ($q) => $q->where('status', 1)->select('id', 'name', 'avatar', 'uid')->with('frame'),
        ], page: $page, limit: $limit)
            ->filter(fn ($item) => isset($item['user']['id']))
            ->map(function ($item) {
                $item['id']     = $item['user']['id'];
                $item['name']   = $item['user']['name'];
                $item['avatar'] = $item['user']['avatar'];
                $item['uid']    = $item['user']['uid'];
                $item['frame']  = $item['user']['frame'];
                unset($item['user']);
                return $item;
            })->values()->all();
        $count = count($list);
        return $this->listData($list, $count);
    }

    /**
     * 获取用户角色.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserRole(int $entId, int $userId): array
    {
        $roleIds  = $this->roleUserDao->column(['user_id' => $userId, 'entid' => $entId], 'role_id');
        $roleList = $this->getRolesList(['entid' => $entId]);
        /** @var MenusInterface $service */
        $service = app()->get(MenusInterface::class);
        $menus   = $service->getMenusForCompany($entId, true)['tree'];
        return [
            'menus'    => $menus,
            'roles'    => $roleIds,
            'roleList' => collect($roleList)->map(function ($item) {
                return ['value' => $item['id'], 'label' => $item['role_name'], 'rules' => $item['rules'], 'apis' => $item['apis']];
            })->all(),
        ];
    }

    /**
     * 用户修改角色.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function changeUserRole(int $entId, int $userId, array $roleIds = []): bool
    {
        $roleIds      = $this->formatRoleIds($roleIds);
        $adminService = app()->get(AdminService::class);
        if ($roleIds) {
            $res = $this->transaction(function () use ($entId, $userId, $roleIds, $adminService) {
                app('enforcer')->deleteRolesForUser((string) $userId);
                $this->roleUserDao->delete(['user_id' => $userId]);
                app('enforcer')->addRolesForUser((string) $userId, array_map(function ($val) {
                    return 'role_' . $val;
                }, $roleIds));
                foreach ($roleIds as $roleId) {
                    $this->roleUserDao->updateOrCreate([
                        'role_id' => $roleId,
                        'user_id' => $userId,
                    ], [
                        'role_id' => $roleId,
                        'user_id' => $userId,
                        'entid'   => $entId,
                        'status'  => 1,
                    ]);
                }
                return (bool) $adminService->update($userId, ['roles' => $roleIds]);
            }) && Cache::tags([CacheEnum::TAG_ROLE])->flush();
        } else {
            $res = $this->transaction(function () use ($userId, $roleIds, $adminService) {
                app('enforcer')->deleteRolesForUser((string) $userId);
                $this->roleUserDao->delete(['user_id' => $userId]);
                return (bool) $adminService->update($userId, ['roles' => $roleIds]);
            }) && Cache::tags([CacheEnum::TAG_ROLE])->flush();
        }
        $res && $this->notifyPermissionChanged($this->getUserUuids([$userId]), 'user_role_changed', $entId);
        return $res;
    }

    /**
     * 角色添加用户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function addRoleUser(int $entId, int $roleId, array $userIds = [], array $frameIds = []): bool
    {
        if (! $roleId) {
            throw $this->exception('角色id不能为空');
        }
        $assistService = app()->get(FrameAssistService::class);
        $userIds       = array_merge($userIds, $assistService->frameIdByUserId($frameIds, $entId));
        $userIds       = array_merge(array_unique($userIds));
        $userIdsList   = $this->roleUserDao->column(['user_ids' => $userIds, 'role_id' => $roleId], 'user_id');
        $res           = $this->transaction(function () use ($userIds, $userIdsList, $roleId, $entId) {
            $data = $newUserId = [];
            foreach ($userIds as $userId) {
                if (! in_array($userId, $userIdsList)) {
                    $data[] = [
                        'role_id' => $roleId,
                        'entid'   => $entId,
                        'user_id' => $userId,
                        'status'  => 1,
                    ];
                    $newUserId[] = $userId;
                }
            }
            if (! $data) {
                throw $this->exception('您选择的用户已全部加入该角色下');
            }
            app()->get(AdminService::class)->updateRole($newUserId, $roleId);
            foreach ($newUserId as $value) {
                app('enforcer')->addRoleForUser((string) $value, 'role_' . $roleId);
            }
            $this->dao->inc($roleId, count($userIds), 'user_count');
            return $this->roleUserDao->insert($data);
        });
        if ($res) {
            Cache::tags([CacheEnum::TAG_ROLE])->flush();
            $this->notifyPermissionChanged($this->getUserUuids($userIds), 'role_user_added', $entId);
        }
        return (bool) $res;
    }

    /**
     * 修改角色用户状态
     * @param mixed $status
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function changeRoleUser(int $uid, int $entId, int $roleId, $status): bool
    {
        $info = $this->roleUserDao->get(['role_id' => $roleId, 'user_id' => $uid]);
        if ($info) {
            if ($info->status == $status) {
                return true;
            }
            $info->status = $status;
            $info->save();
            if ($status) {
                app('enforcer')->addRoleForUser((string) $uid, 'role_' . $roleId);
            } else {
                app('enforcer')->deleteRoleForUser((string) $uid, 'role_' . $roleId);
            }
            Cache::tags([CacheEnum::TAG_ROLE])->flush();
            $this->notifyPermissionChanged($this->getUserUuids([$uid]), 'role_user_status_changed', $entId);
            return (bool) $info;
        }
        throw $this->exception('修改的成员不存在!');
    }

    /**
     * 角色删除用户.
     * @param mixed $entId
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function delRoleUser(int $uid, int $entId, int $roleId): bool
    {
        if ($this->roleUserDao->exists(['role_id' => $roleId, 'user_id' => $uid])) {
            $res = $this->transaction(function () use ($uid, $roleId) {
                $adminService = app()->get(AdminService::class);
                $res = $this->roleUserDao->delete(['role_id' => $roleId, 'user_id' => $uid]);
                $this->dao->dec($roleId, 1, 'user_count');
                $roles = $adminService->value($uid, 'roles');
                $roles = is_string($roles) ? json_decode($roles, true) : $roles;
                $roles = array_values(array_filter((array) $roles, fn ($value) => (int) $value !== $roleId));
                $adminService->update($uid, ['roles' => $roles]);
                if (app('enforcer')->hasRoleForUser((string) $uid, 'role_' . $roleId)) {
                    app('enforcer')->deleteRoleForUser((string) $uid, 'role_' . $roleId);
                }
                return (bool) $res;
            });
            if ($res) {
                Cache::tags([CacheEnum::TAG_ROLE])->flush();
                $this->notifyPermissionChanged($this->getUserUuids([$uid]), 'role_user_deleted', $entId);
            }
            return (bool) $res;
        }
        throw $this->exception('删除的成员不存在!');
    }

    protected function getRoleUserUuids(int $roleId): array
    {
        return $this->getUserUuids($this->getRoleUserIds($roleId));
    }

    protected function getRoleUserIds(int $roleId): array
    {
        return toArray($this->roleUserDao->column(['role_id' => $roleId], 'user_id'));
    }

    protected function getUserUuids(array $userIds): array
    {
        $userIds = array_values(array_filter(array_unique(array_map('intval', $userIds))));
        if (! $userIds) {
            return [];
        }

        $uuids = app()->get(AdminService::class)->column(['id' => $userIds], 'uid');
        return array_values(array_filter(array_unique(array_map('strval', $uuids))));
    }

    protected function notifyPermissionChangedByRole(int $roleId, string $reason, int $entId = 1): void
    {
        $this->notifyPermissionChanged($this->getRoleUserUuids($roleId), $reason, $entId);
    }

    protected function notifyPermissionChangedForRoleStatus(int $roleId, array $userIds, array $userUuids, int $entId = 1): void
    {
        $fdCounts = $this->getSocketFdCounts($userUuids, $entId);
        Log::info('角色启用禁用 websocket 推送准备', [
            'role_id'       => $roleId,
            'entid'         => $entId,
            'user_ids'      => array_values(array_unique(array_map('intval', $userIds))),
            'user_uuids'    => $userUuids,
            'fd_counts'     => $fdCounts,
            'matched_count' => array_sum($fdCounts),
        ]);

        if ($userUuids && array_sum($fdCounts) > 0) {
            $this->notifyPermissionChanged($userUuids, 'role_status_changed', $entId);
            return;
        }

        $fds = Room::userFd('ent', '', $entId);
        Log::warning('角色启用禁用 websocket 推送目标未命中在线房间，使用企业在线用户兜底', [
            'role_id'        => $roleId,
            'entid'          => $entId,
            'user_ids'       => array_values(array_unique(array_map('intval', $userIds))),
            'user_uuids'     => $userUuids,
            'fallback_fds'   => $fds,
            'fd_counts'      => $fdCounts,
        ]);
        $this->notifyPermissionChangedForEnterpriseOnline('role_status_changed', $entId);
    }

    protected function notifyPermissionChanged(array $userIds, string $reason, int $entId = 1): void
    {
        $userIds = array_values(array_filter(array_unique(array_map('strval', $userIds))));
        if (! $userIds) {
            Log::warning('权限变更 websocket 推送跳过：目标用户为空', [
                'reason' => $reason,
                'entid'  => $entId,
            ]);
            return;
        }

        try {
            $fdCounts = $this->getSocketFdCounts($userIds, $entId);
            SwooleTaskService::ent()
                ->entid($entId)
                ->data('ent', [
                    'reason' => $reason,
                    'time'   => time(),
                ])
                ->type('permission_changed')
                ->to($userIds)
                ->push();
            Log::info('权限变更 websocket 推送成功', [
                'reason' => $reason,
                'users'  => $userIds,
                'count'  => count($userIds),
                'entid'  => $entId,
                'fds'    => $fdCounts,
            ]);
        } catch (\Throwable $e) {
            Log::error('权限变更 websocket 推送失败：' . $e->getMessage(), [
                'reason' => $reason,
                'users'  => $userIds,
                'entid'  => $entId,
            ]);
        }
    }

    protected function notifyPermissionChangedForEnterpriseOnline(string $reason, int $entId = 1): void
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
            Log::info('权限变更 websocket 企业在线用户广播成功', [
                'reason' => $reason,
                'entid'  => $entId,
                'fds'    => $fds,
                'count'  => count($fds),
            ]);
        } catch (\Throwable $e) {
            Log::error('权限变更 websocket 企业在线用户广播失败：' . $e->getMessage(), [
                'reason' => $reason,
                'entid'  => $entId,
            ]);
        }
    }

    protected function getSocketFdCounts(array $userIds, int $entId): array
    {
        $counts = [];
        foreach ($userIds as $userId) {
            $userId          = (string) $userId;
            $counts[$userId] = count(Room::userFd('ent', $userId, $entId));
        }
        return $counts;
    }

    /**
     * 规范化角色ID列表.
     */
    private function formatRoleIds(array $roleIds): array
    {
        $roles = [];
        foreach ($roleIds as $roleId) {
            $roleId = $this->parseRoleId($roleId);
            if ($roleId && ! in_array($roleId, $roles, true)) {
                $roles[] = $roleId;
            }
        }
        return $roles;
    }

    /**
     * 解析角色ID，仅接受正整数或纯数字字符串.
     *
     * @param mixed $roleId
     */
    private function parseRoleId($roleId): int
    {
        if (is_int($roleId)) {
            return $roleId > 0 ? $roleId : 0;
        }

        if (is_string($roleId) && ctype_digit(trim($roleId))) {
            return (int) $roleId;
        }

        return 0;
    }

    /**
     * 同步权限信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function syncRoles()
    {
        $roles = toArray($this->dao->select([]));
        /** @var MenusInterface $menuService */
        $menuService = app()->get(MenusInterface::class);
        foreach ($roles as $role) {
            $this->dao->update($role['id'], [
                'rules' => $menuService->getMenuId($role['rule_unique']),
                'apis'  => $menuService->getMenuId($role['api_unique'], false),
            ]);
        }
    }

    public function checkAuth($uri, $userInfo, $entInfo, $method)
    {
        if (! $entInfo) {
            throw $this->exception('接口未授权,无法访问!');
        }
        if (! $userInfo['is_admin'] && app('enforcer')->enforce('all', $uri, $method) && ! app('enforcer')->enforce((string) $userInfo['id'], $uri, $method)) {
            throw $this->exception('接口未授权,无法访问!');
        }
    }

    /**
     * 初始化权限数据.
     * @param mixed $entId
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function initRules(int $entId = 1): void
    {
        if (! app('enforcer')->hasRoleForUser('all', 'role_all')) {
            $service   = app()->get(MenusService::class);
            $ruleMenus = $service->select(['status' => 1], ['id', 'api', 'methods', 'menu_path', 'methods', 'uni_path', 'type', 'uniqued', 'unique_auth'])?->toArray();
            $rules     = $this->dao->column(['entid' => $entId], ['id', 'rules', 'apis']);

            $menusById = array_column($ruleMenus, null, 'id');
            $policies  = [];
            foreach ($ruleMenus as $menu) {
                $this->pushPolicy($policies, $this->buildMenuPolicy('role_all', $menu));
            }

            $roleIds = [];
            foreach ($rules as &$rule) {
                $roleIds[]     = (int) $rule['id'];
                $rule['rules'] = array_values(array_unique(array_merge($rule['rules'] ?? [], $service->batchParentIdsByIds($rule['rules'] ?? []))));
                $rule['apis']  = array_values(array_unique($rule['apis'] ?? []));
            }
            unset($rule);

            foreach ($rules as $rule) {
                $roleKey = 'role_' . $rule['id'];
                foreach ($rule['rules'] as $menuId) {
                    if (($menusById[$menuId]['type'] ?? null) === MenuEnum::TYPE_MENU) {
                        $this->pushPolicy($policies, $this->buildMenuPolicy($roleKey, $menusById[$menuId]));
                    }
                }
                foreach ($rule['apis'] as $menuId) {
                    if (isset($menusById[$menuId]) && ($menusById[$menuId]['type'] ?? null) !== MenuEnum::TYPE_MENU) {
                        $this->pushPolicy($policies, $this->buildMenuPolicy($roleKey, $menusById[$menuId]));
                    }
                }
            }

            if (! empty($policies)) {
                app('enforcer')->addPolicies(array_values($policies));
            }

            $userRoles = [];
            if ($roleIds) {
                $this->roleUserDao->getModel()
                    ->whereIn('role_id', array_unique($roleIds))
                    ->select(['id', 'role_id', 'user_id'])
                    ->chunkById(1000, function ($items) use (&$userRoles) {
                        foreach ($items as $item) {
                            if (empty($item->user_id)) {
                                continue;
                            }
                            $userRoles[$item->user_id]['role_' . $item->role_id] = true;
                        }
                    });
            }

            foreach ($userRoles as $userId => $roles) {
                if (! empty($roles)) {
                    app('enforcer')->addRolesForUser((string) $userId, array_keys($roles));
                }
            }
            app('enforcer')->addRoleForUser('all', 'role_all');
        }
    }

    private function buildMenuPolicy(string $role, array $menu): array
    {
        return match ($menu['type']) {
            MenuEnum::TYPE_MENU => [$role, $menu['uniqued'], $menu['type']],
            MenuEnum::TYPE_API  => [$role, $menu['api'], $menu['methods']],
            default             => [$role, $menu['uniqued'], $menu['unique_auth']],
        };
    }

    private function pushPolicy(array &$policies, array $policy): void
    {
        $policies[implode("\0", array_map('strval', $policy))] = $policy;
    }

    /**
     * 检测权限状态.
     * @param mixed $admin
     * @param mixed $uri
     * @param mixed $method
     * @throws CasbinException
     */
    public function checkAuthStatus($admin, $uri, $method): bool
    {
        return ! $admin->is_admin && app('enforcer')->enforce('all', $uri, $method) && app('enforcer')->enforce((string) $admin->id, $uri, $method);
    }

    /**
     * TODO 获取某个身份下的用户user_id.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRuleUserIds(int $entId, string $type = RuleEnum::FINANCE_TYPE)
    {
        $menusService = app()->get(MenusService::class);
        $users        = app()->get(AdminService::class)->column(['status' => 1], ['uid', 'id']);
        $menuIds      = match ($type) {
            RuleEnum::FINANCE_TYPE        => $menusService->column(['type' => 0, 'menu_path' => '/fd/'], 'id'),
            RuleEnum::PERSONNEL_TYPE      => $menusService->column(['type' => 0, 'menu_path' => '/hr/'], 'id'),
            RuleEnum::ADMINISTRATION_TYPE => $menusService->column(['type' => 0, 'menu_path' => '/administration/'], 'id'),
            default                       => $menusService->column(['type' => 0], 'id'),
        };
        $userIds = [];
        foreach ($users as $user) {
            $userRoles = $this->getRolesForUser($user['uid'], $entId, withApi: false);
            if ($userRoles && array_intersect($userRoles, $menuIds)) {
                $userIds[] = $user['id'];
            }
        }
        return $userIds;
    }

    /**
     * 验证系统默认角色权限.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function checkDefaultRule(int $userId, int $entId, string $type = RuleEnum::FINANCE_TYPE): bool
    {
        $ident = app()->get(AdminService::class)->value($userId, 'is_admin');
        return in_array($userId, $this->getRuleUserIds($entId, $type)) || $ident;
    }

    /**
     * 获取某个管理员的权限ID.
     * @return string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAdminRole(array $ids, string $field = 'rules')
    {
        return Cache::tags(CacheEnum::TAG_ROLE)->remember(md5('admin_rule' . $field . implode(',', $ids)), (int) sys_config('system_cache_ttl', 3600), function () use ($ids, $field) {
            $rules   = $this->dao->column(['id' => $ids], $field, 'id');
            $newRule = [];
            $service = app()->get(MenusService::class);
            if ($field === 'rules') {
                $rulesId = [];
                foreach ($rules as $rule) {
                    $rulesId = array_merge($rulesId, $rule);
                }
                $rulesId = array_merge(array_unique($rulesId));
                return $service->ruleByMenusIds($rulesId);
            }
            foreach ($rules as $rule) {
                if ($field === 'apis') {
                    foreach ($rule as $value) {
                        $newRule = array_merge($newRule, is_array($value) ? $value : [$value]);
                    }
                } else {
                    $newRule = array_merge($newRule, $rule);
                }
            }
            return array_unique($newRule);
        });
    }

    /**
     * 获取总后台超级角色权限.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSuperRoleAll(bool $isAll = true)
    {
        $rules = $this->roleDao->column(['type' => array_keys(RuleEnum::ROLE_TYPE), 'entid_like' => 0], ['rules', 'apis', 'id']);
        $data  = [];
        $apis  = [];
        foreach ($rules as $rule) {
            $data = array_merge($data, $rule['rules']);
            foreach ($rule['apis'] as $k => $v) {
                $apis[$k] = $v;
            }
        }
        return $isAll ? array_unique($data) : [$data, $apis];
    }

    /**
     * 添加角色.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addSysRole(array $data): bool
    {
        return $this->roleDao->create($data) && Cache::tags([CacheEnum::TAG_ROLE])->flush();
    }

    /**
     * 更新菜单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateSuperRole(array $menus): void
    {
        $roles = $this->roleDao->select(['type' => [0, 'enterprise']]);
        foreach ($roles as $role) {
            if ($menus['type'] == 0) {
                $role->rules = array_unique(array_merge($role->rules, [$menus['id']]));
                $role->save();
            }
        }
    }

    /**
     * 获取用户数据范围下的用户ID.
     * @return string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getDataUids(int $userId, string $module = '', int $type = 0, bool $normal = true, int $crudId = 0, int $crudRoleType = 1)
    {
        return Cache::tags(CacheEnum::TAG_ROLE)->remember(
            md5($userId . $module . $type . $normal . $crudId . $crudRoleType),
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($userId, $module, $type, $normal, $crudId, $crudRoleType) {
                if ($module && ModuleEnum::isValid($module)) {
                    return app(ModulePermissionService::class)->getAccessibleUserIds($userId, $module, $normal);
                }

                $roleIds       = $this->roleUserDao->column(['user_id' => $userId, 'status' => 1], 'role_id');
                $assistService = app()->get(FrameAssistService::class);
                $frameService  = app()->get(FrameService::class);
                if ($module) {
                    $crud = DB::table('system_crud')->where('table_name_en', $module)->whereNull('deleted_at')->first(['id', 'table_name']);
                    if ($crud) {
                        $crudName = $crud->table_name ?: $module;
                        $roles    = app()->get(SystemCrudRoleService::class)->select(['role_id' => $roleIds, 'crud_id' => $crud->id])?->toArray();
                        $userIds = [];
                        switch ($type) {
                            case 1:// 查看
                                foreach ($roles as $role) {
                                    $userIds = array_merge($userIds, $this->getUserids($userId, $role['reade'], $role['reade_frame'], $frameService, $assistService));
                                }
                                break;
                            case 2:// 新增
                                foreach ($roles as $role) {
                                    $userIds = array_merge($userIds, $this->getUserids($userId, $role['created'], [], $frameService, $assistService));
                                }
                                if (! in_array($userId, $userIds)) {
                                    throw $this->exception('暂无权限在' . $crudName . '模块中新增数据！');
                                }
                                break;
                            case 3:// 修改
                                foreach ($roles as $role) {
                                    $userIds = array_merge($userIds, $this->getUserids($userId, $role['updated'], $role['updated_frame'], $frameService, $assistService));
                                }
                                if (! in_array($userId, $userIds)) {
                                    throw $this->exception('暂无权限在' . $crudName . '模块中更新该数据！');
                                }
                                break;
                            case 4:// 删除
                                foreach ($roles as $role) {
                                    $userIds = array_merge($userIds, $this->getUserids($userId, $role['deleted'], $role['deleted_frame'], $frameService, $assistService));
                                }
                                if (! in_array($userId, $userIds)) {
                                    throw $this->exception('暂无权限在' . $crudName . '模块中删除该数据！');
                                }
                                break;
                            case 5:// 分配
                                foreach ($roles as $role) {
                                    $userIds = array_merge($userIds, $this->getUserids($userId, $role['transfer'], $role['transfer_frame'], $frameService, $assistService));
                                }
                                if (! in_array($userId, $userIds)) {
                                    throw $this->exception('暂无权限在' . $crudName . '模块中分配该数据！');
                                }
                                break;
                            case 6:// 分享
                                foreach ($roles as $role) {
                                    $userIds = array_merge($userIds, $this->getUserids($userId, $role['share'], $role['share_frame'], $frameService, $assistService));
                                }
                                if (! in_array($userId, $userIds)) {
                                    throw $this->exception('暂无权限在' . $crudName . '模块中分享该数据！');
                                }
                                break;
                        }
                    }
                } elseif ($crudId) {
                    if (! $roleIds) {
                        return array_filter([$userId]);
                    }
                    $roles    = $this->dao->select(['ids' => $roleIds, 'status' => 1], ['id'])?->toArray();
                    $userIds  = [$userId];
                    $levelMap = match ($crudRoleType) {
                        3       => ['updated', 'updated_frame'],
                        4       => ['deleted', 'deleted_frame'],
                        5       => ['transfer', 'transfer_frame'],
                        6       => ['share', 'share_frame'],
                        default => ['reade', 'reade_frame'],
                    };
                    foreach ($roles as $role) {
                        $crudRole = app()->get(SystemCrudRoleService::class)->get(['role_id' => $role['id'], 'crud_id' => $crudId], $levelMap);
                        if (! $crudRole) {
                            continue;
                        }
                        $userIds = array_merge($userIds, $this->getUserids($userId, $crudRole[$levelMap[0]], $crudRole[$levelMap[1]], $frameService, $assistService, $normal));
                    }
                }

                if (isset($userIds)) {
                    if ($normal) {
                        $userIds = array_unique(array_intersect($userIds, app(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id')));
                    }
                    return array_filter($userIds);
                }

                $userIds = $normal
                    ? array_intersect(
                        app(AdminService::class)->column(['status' => 1], 'id'),
                        app(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id')
                    )
                    : app(AdminService::class)->column([], 'id');

                if ($normal) {
                    $userIds = array_unique($userIds);
                }
                return array_filter($userIds);
            }
        );
    }

    /**
     * 获取用户数据范围下的组织架构ID.
     * @param int $crudRoleType 1:查看 2:新增 3:修改 4:删除 5:分配 6:分享
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getDataFrames(int|string $uuid, int $entId = 1, bool $withScope = false, int $crudId = 0, int $crudRoleType = 1)
    {
        $userId = is_string($uuid) ? uuid_to_uid($uuid, $entId) : $uuid;
        return Cache::tags([CacheEnum::TAG_ROLE])->remember(md5('frames_' . $userId . (int) $withScope . $crudId . $crudRoleType), (int) sys_config('system_cache_ttl', 3600), function () use ($userId, $entId, $withScope, $crudId, $crudRoleType) {
            if ($crudId) {
                $roleIds = $this->roleUserDao->column(['user_id' => $userId, 'status' => 1], 'role_id');
                if (! $roleIds) {
                    return [[], []];
                }

                $roles    = toArray($this->dao->select(['ids' => $roleIds, 'status' => 1], ['id']));
                $frameIds = [];
                $levels   = [];
                $levelMap = match ($crudRoleType) {
                    3       => ['updated', 'updated_frame'],
                    4       => ['deleted', 'deleted_frame'],
                    5       => ['transfer', 'transfer_frame'],
                    6       => ['share', 'share_frame'],
                    default => ['reade', 'reade_frame'],
                };
                foreach ($roles as $role) {
                    $crudRole = app()->get(SystemCrudRoleService::class)->get(['role_id' => $role['id'], 'crud_id' => $crudId], $levelMap);
                    if (! $crudRole) {
                        continue;
                    }
                    $levels[] = $crudRole[$levelMap[0]];
                    $frameIds = array_merge($frameIds, $this->getFrameIds($userId, $crudRole[$levelMap[0]], $crudRole[$levelMap[1]]));
                }

                if ($withScope) {
                    return [
                        array_unique($frameIds),
                        array_unique($levels),
                    ];
                }
                return array_unique($frameIds);
            }

            $frameIds = app(FrameService::class)->column(['entid' => $entId, 'is_show' => 1], 'id');
            if ($withScope) {
                return [
                    array_unique($frameIds),
                    [RuleEnum::DATA_ALL],
                ];
            }
            return array_unique($frameIds);
        });
    }

    /**
     * @param int $userId 用户ID
     * @param int $level 级别：
     * @param array $frameIds 指定部门ID
     * @param null|mixed $frameService
     * @param null|mixed $assistService
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function getUserids(int $userId, int $level, array $frameIds = [], ?FrameService $frameService = null, ?FrameAssistService $assistService = null, ?bool $normal = true)
    {
        $userIds = match ($level) {
            RuleEnum::DATA_SUB     => $assistService->getSubUid($userId, $normal),
            RuleEnum::DATA_ALL     => $normal ? app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id') : app()->get(AdminService::class)->column([], 'id'),
            RuleEnum::DATA_APPOINT => $this->filterNormalUsers($assistService->setTrashed($normal)->column(['frame_id' => $frameIds], 'user_id'), $normal),
            RuleEnum::DATA_CURRENT => $frameService->getFrameSubUids($userId),
            RuleEnum::DATA_SELF    => [$userId],
            default                => [],
        };
        return array_unique($userIds);
    }

    /**
     * 过滤在职用户（type为1、2、3）.
     */
    private function filterNormalUsers(array $userIds, bool $normal = true): array
    {
        if (! $normal || empty($userIds)) {
            return $userIds;
        }
        $normalUserIds = app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id');
        return array_intersect($userIds, $normalUserIds);
    }

    private function getFrameIds($userId, $level, $frameIds = [])
    {
        $assistService = app()->get(FrameAssistService::class);
        $userFrame     = $assistService->column(['user_id' => $userId], 'frame_id');
        return match ($level) {
            RuleEnum::DATA_ALL     => app()->get(FrameService::class)->column([], 'id'),
            RuleEnum::DATA_APPOINT => $frameIds,
            RuleEnum::DATA_CURRENT => $userFrame,
            default                => [],
        };
    }
}
