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
use App\Constants\ModuleEnum;
use App\Http\Dao\Auth\RoleUserDao;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 数据权限缓存服务
 * Class DataPermissionCacheService.
 */
class DataPermissionCacheService extends BaseService
{
    /**
     * 缓存前缀
     */
    protected const CACHE_PREFIX = 'data_perm';

    /**
     * 缓存TTL（秒）.
     */
    protected const CACHE_TTL = 300;

    protected RoleUserDao $roleUserDao;

    protected FrameAssistService $frameAssistService;

    public function __construct(
        RoleUserDao $roleUserDao,
        FrameAssistService $frameAssistService
    ) {
        $this->roleUserDao        = $roleUserDao;
        $this->frameAssistService = $frameAssistService;
    }

    /**
     * 从缓存获取权限用户ID列表.
     *
     * @param int|string $userId 用户ID
     * @param int|string $roleId 角色ID
     * @param string $module 模块标识
     * @return null|array 缓存未命中返回null
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAccessibleUserIds(int|string $userId, int|string $roleId, string $module): ?array
    {
        if (! ModuleEnum::isValid($module)) {
            return null;
        }
        $userId = (int) $userId;
        $roleId = (int) $roleId;

        $cacheKey = $this->getCacheKey($userId, $roleId, $module);

        try {
            $cached = Cache::tags([CacheEnum::TAG_ROLE])->get($cacheKey);

            if ($cached !== null) {
                Log::debug('DataPermissionCache hit', [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'module'  => $module,
                    'count'   => count($cached),
                ]);
                return $cached;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('DataPermissionCache get error', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'module'  => $module,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 设置缓存.
     *
     * @param int|string $userId 用户ID
     * @param int|string $roleId 角色ID
     * @param string $module 模块标识
     * @param array $uids 允许访问的用户ID列表
     */
    public function setAccessibleUserIds(int|string $userId, int|string $roleId, string $module, array $uids): void
    {
        if (! ModuleEnum::isValid($module)) {
            return;
        }
        $userId = (int) $userId;
        $roleId = (int) $roleId;
        $uids   = array_values(array_unique(array_filter(array_map('intval', $uids))));

        $cacheKey = $this->getCacheKey($userId, $roleId, $module);

        try {
            Cache::tags([CacheEnum::TAG_ROLE])->put($cacheKey, $uids, self::CACHE_TTL);
            $this->rememberCacheKey($userId, $roleId, $module, $cacheKey);

            Log::debug('DataPermissionCache set', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'module'  => $module,
                'count'   => count($uids),
                'ttl'     => self::CACHE_TTL,
            ]);
        } catch (\Exception $e) {
            Log::error('DataPermissionCache set error', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'module'  => $module,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除用户所有缓存.
     *
     * @param int|string $userId 用户ID
     */
    public function clearUserCache(int|string $userId): void
    {
        try {
            $userId    = (int) $userId;
            $cacheKeys = $this->getIndexedCacheKeys($this->getUserIndexKey($userId));
            $roleIds   = [];
            try {
                $roleIds = array_map('intval', $this->roleUserDao->column(['user_id' => $userId, 'status' => 1], 'role_id'));
            } catch (\Exception) {
            }

            foreach (ModuleEnum::all() as $module => $name) {
                foreach ($roleIds as $roleId) {
                    $cacheKeys[] = $this->getCacheKey($userId, $roleId, $module);
                }
            }
            $this->forgetCacheKeys($cacheKeys);
            Cache::tags([CacheEnum::TAG_ROLE])->forget($this->getUserIndexKey($userId));

            Log::info('DataPermissionCache cleared for user', [
                'user_id'    => $userId,
                'role_count' => count($roleIds),
            ]);
        } catch (\Exception $e) {
            Log::error('DataPermissionCache clear user error', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除角色所有缓存.
     *
     * @param int|string $roleId 角色ID
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function clearRoleCache(int|string $roleId): void
    {
        try {
            $roleId    = (int) $roleId;
            $cacheKeys = $this->getIndexedCacheKeys($this->getRoleIndexKey($roleId));
            $userIds   = [];
            try {
                $userIds = array_map('intval', $this->roleUserDao->column(['role_id' => $roleId, 'status' => 1], 'user_id'));
            } catch (\Exception) {
            }

            foreach (ModuleEnum::all() as $module => $name) {
                foreach ($userIds as $userId) {
                    $cacheKeys[] = $this->getCacheKey($userId, $roleId, $module);
                }
            }
            $this->forgetCacheKeys($cacheKeys);
            Cache::tags([CacheEnum::TAG_ROLE])->forget($this->getRoleIndexKey($roleId));

            Log::info('DataPermissionCache cleared for role', [
                'role_id'    => $roleId,
                'user_count' => count($userIds),
            ]);
        } catch (\Exception $e) {
            Log::error('DataPermissionCache clear role error', [
                'role_id' => $roleId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除模块所有缓存.
     *
     * @param string $module 模块标识
     */
    public function clearModuleCache(string $module): void
    {
        if (! ModuleEnum::isValid($module)) {
            return;
        }

        try {
            $cacheKey = $this->getGlobalCacheKey($module);
            Cache::tags([CacheEnum::TAG_ROLE])->forget($cacheKey);
            $this->forgetCacheKeys($this->getIndexedCacheKeys($this->getModuleIndexKey($module)));
            Cache::tags([CacheEnum::TAG_ROLE])->forget($this->getModuleIndexKey($module));

            Log::info('DataPermissionCache cleared for module', [
                'module' => $module,
            ]);
        } catch (\Exception $e) {
            Log::error('DataPermissionCache clear module error', [
                'module' => $module,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除所有缓存.
     */
    public function clearAllCache(): void
    {
        try {
            Cache::tags([CacheEnum::TAG_ROLE])->flush();

            Log::info('DataPermissionCache cleared all');
        } catch (\Exception $e) {
            Log::error('DataPermissionCache clear all error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 批量清除缓存（组织变更、部门调动时调用）.
     *
     * @param array $userIds 受影响的用户ID列表
     * @param null|string $module 指定模块，为空则清除所有模块
     */
    public function batchClearCache(array $userIds, ?string $module = null): void
    {
        try {
            $modules = $module ? [$module => ModuleEnum::all()[$module]] : ModuleEnum::all();
            $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

            foreach ($userIds as $userId) {
                $indexedCacheKeys = $this->getIndexedCacheKeys($this->getUserIndexKey($userId));
                foreach ($modules as $mod => $name) {
                    $roleIds = [];
                    try {
                        $roleIds = array_map('intval', $this->roleUserDao->column(['user_id' => $userId, 'status' => 1], 'role_id'));
                    } catch (\Exception) {
                    }

                    foreach ($roleIds as $roleId) {
                        $indexedCacheKeys[] = $this->getCacheKey($userId, $roleId, $mod);
                    }
                }
                if ($module) {
                    $indexedCacheKeys = array_filter($indexedCacheKeys, fn (string $cacheKey) => str_ends_with($cacheKey, ':' . $module));
                }
                $this->forgetCacheKeys($indexedCacheKeys);
                if (! $module) {
                    Cache::tags([CacheEnum::TAG_ROLE])->forget($this->getUserIndexKey($userId));
                }
            }

            Log::info('DataPermissionCache batch cleared', [
                'user_count' => count($userIds),
                'module'     => $module ?? 'all',
            ]);
        } catch (\Exception $e) {
            Log::error('DataPermissionCache batch clear error', [
                'user_ids' => $userIds,
                'module'   => $module,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * 预热缓存（后台任务调用）.
     *
     * @param int|string $userId 用户ID
     * @param int|string $roleId 角色ID
     * @param string $module 模块标识
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function warmupCache(
        int|string $userId,
        int|string $roleId,
        string $module,
        ModulePermissionService $permissionService
    ): array {
        $userId = (int) $userId;
        $roleId = (int) $roleId;
        $uids   = $permissionService->getAccessibleUserIds($userId, $module);

        $this->setAccessibleUserIds($userId, $roleId, $module, $uids);

        return $uids;
    }

    /**
     * 获取缓存统计信息.
     */
    public function getCacheStats(): array
    {
        try {
            return [
                'tag'    => CacheEnum::TAG_ROLE,
                'ttl'    => self::CACHE_TTL,
                'prefix' => self::CACHE_PREFIX,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取缓存key.
     *
     * @param int|string $userId 用户ID
     * @param int|string $roleId 角色ID
     * @param string $module 模块标识
     */
    protected function getCacheKey(int|string $userId, int|string $roleId, string $module): string
    {
        $userId = (int) $userId;
        $roleId = (int) $roleId;
        return sprintf('%s:%d:%d:%s', self::CACHE_PREFIX, $userId, $roleId, $module);
    }

    /**
     * 获取全局权限列表缓存key.
     *
     * @param string $module 模块标识
     */
    protected function getGlobalCacheKey(string $module): string
    {
        return sprintf('%s:global:%s', self::CACHE_PREFIX, $module);
    }

    protected function getUserIndexKey(int $userId): string
    {
        return sprintf('%s:index:user:%d', self::CACHE_PREFIX, $userId);
    }

    protected function getRoleIndexKey(int $roleId): string
    {
        return sprintf('%s:index:role:%d', self::CACHE_PREFIX, $roleId);
    }

    protected function getModuleIndexKey(string $module): string
    {
        return sprintf('%s:index:module:%s', self::CACHE_PREFIX, $module);
    }

    protected function getIndexedCacheKeys(string $indexKey): array
    {
        $keys = Cache::tags([CacheEnum::TAG_ROLE])->get($indexKey, []);
        return is_array($keys) ? $keys : [];
    }

    protected function rememberCacheKey(int $userId, int $roleId, string $module, string $cacheKey): void
    {
        foreach ([
            $this->getUserIndexKey($userId),
            $this->getRoleIndexKey($roleId),
            $this->getModuleIndexKey($module),
        ] as $indexKey) {
            $keys   = $this->getIndexedCacheKeys($indexKey);
            $keys[] = $cacheKey;
            Cache::tags([CacheEnum::TAG_ROLE])->put($indexKey, array_values(array_unique($keys)), self::CACHE_TTL);
        }
    }

    protected function forgetCacheKeys(array $cacheKeys): void
    {
        foreach (array_unique(array_filter($cacheKeys)) as $cacheKey) {
            Cache::tags([CacheEnum::TAG_ROLE])->forget($cacheKey);
        }
    }
}
