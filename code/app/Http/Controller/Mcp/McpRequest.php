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

namespace App\Http\Controller\Mcp;

use App\Constants\ModuleEnum;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\System\RolesService;
use App\Mcp\ToolRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Constants\CacheEnum;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * MCP 请求上下文
 * Class McpRequest.
 *
 * 用于在 Tool 中获取当前用户信息和数据权限
 */
class McpRequest
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * 请求级用户信息缓存，避免 Swoole 跨请求静态污染.
     *
     * @var array|null
     */
    private ?array $userInfoCache = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 获取用户ID (数据库ID).
     */
    public function getUserId(): int
    {
        return (int) ($this->request->input('mcp_user_id') ?? 0);
    }

    /**
     * 获取用户数据库ID (与getUserId相同，为了兼容性保留).
     */
    public function getUserDbId(): int
    {
        return (int) ($this->request->input('mcp_user_db_id') ?? 0);
    }

    /**
     * 获取用户信息数组.
     */
    public function getUserInfo(): array
    {
        $userDbId = $this->getUserDbId();
        if ($this->userInfoCache !== null && (int) ($this->userInfoCache['id'] ?? 0) !== $userDbId) {
            $this->userInfoCache = null;
        }

        if ($this->userInfoCache !== null) {
            return $this->userInfoCache;
        }

        $user = $this->request->input('mcp_user');
        if ($user) {
            $this->userInfoCache = $user->toArray();

            return $this->userInfoCache;
        }

        // AI 写确认等路径若未 merge mcp_user，仍可从 admin 守卫回填，避免空 uid 写入.
        $authUser = auth('admin')->user();
        if ($authUser) {
            $this->userInfoCache = $authUser->toArray();

            return $this->userInfoCache;
        }

        return [];
    }

    /**
     * 清除请求级缓存（Swoole 请求结束由 McpCleaner 调用）.
     */
    public function resetRequestCache(): void
    {
        $this->userInfoCache = null;
    }

    /**
     * 获取用户信息字段.
     */
    public function getUserField(string $key, mixed $default = null): mixed
    {
        $info = $this->getUserInfo();
        return $info[$key] ?? $default;
    }

    /**
     * 判断是否为管理员(创始人).
     */
    public function isAdmin(): bool
    {
        return (bool) ($this->request->input('mcp_is_admin') ?? false);
    }

    /**
     * 获取用户所属部门ID列表.
     */
    public function getFrameIds(): array
    {
        return $this->request->input('mcp_frame_ids') ?? [];
    }

    /**
     * 获取用户主部门ID.
     */
    public function getMainFrameId(): int
    {
        return (int) ($this->request->input('mcp_main_frame_id') ?? 0);
    }

    /**
     * 获取数据权限范围内的用户ID.
     *
     * @param string $module 模块名（如 customer, assess, report 等）
     * @param int $type CRUD类型：1=查看, 2=新增, 3=修改, 4=删除, 5=分配, 6=分享
     * @param bool $normal 是否只返回在职用户
     * @return array
     */
    public function getDataUids(string $module = '', int $type = 1, bool $normal = true): array
    {
        $userId = $this->getUserDbId();

        // 如果是管理员(创始人)，返回空数组表示全部数据
        if ($this->isAdmin()) {
            return [];
        }

        $cacheKey = 'mcp_data_uids:' . $userId . ':' . $module . ':' . $type . ':' . (int) $normal;

        return Cache::tags([CacheEnum::TAG_ROLE])->remember($cacheKey, 300, function () use ($userId, $module, $type, $normal) {
            // 对于 ModuleEnum 中的 6 个模块，使用 enterprise_role.module_permissions
            $enumModules = ModuleEnum::all();
            if (isset($enumModules[$module])) {
                try {
                    /** @var ModulePermissionService $modulePermService */
                    $modulePermService = app()->get(ModulePermissionService::class);
                    return $modulePermService->getAccessibleUserIds($userId, $module, $normal);
                } catch (\Exception $e) {
                    // 出错时返回本人
                    return [$userId];
                }
            }

            if ($module && DB::table('system_crud')->where('table_name_en', $module)->whereNull('deleted_at')->exists()) {
                return app()->get(RolesService::class)->getDataUids($userId, $module, $type, $normal, 0, $type);
            }

            return app()->get(AdminService::class)->column($normal ? ['status' => 1] : [], 'id');
        });
    }

    /**
     * 获取数据权限范围内的部门ID.
     *
     * @param int $crudRoleType CRUD类型
     * @return array [frameIds, levels]
     */
    public function getDataFrames(int $crudRoleType = 1): array
    {
        $userId = $this->getUserDbId();

        // 如果是管理员，返回全部
        if ($this->isAdmin()) {
            return [[], []];
        }

        $cacheKey = 'mcp_data_frames:' . $userId . ':' . $crudRoleType;

        return Cache::tags([CacheEnum::TAG_ROLE])->remember($cacheKey, 300, function () use ($userId, $crudRoleType) {
            $frameIds = app()->get(\App\Http\Service\Frame\FrameService::class)->column(['is_show' => 1, 'entid' => 1], 'id');
            return [$frameIds, []];
        });
    }

    /**
     * 检查用户对指定人员是否有数据权限.
     *
     * @param int $targetUserId 目标用户数据库ID
     * @param string $module 模块名
     * @return bool
     */
    public function hasPermissionToUser(int $targetUserId, string $module = ''): bool
    {
        // 管理员可以访问所有人
        if ($this->isAdmin()) {
            return true;
        }

        $allowedUids = $this->getDataUids($module, 1, true);
        return in_array($targetUserId, $allowedUids, true);
    }

    /**
     * 清除数据权限缓存.
     */
    public function clearDataUidsCache(): void
    {
        $userId = $this->getUserDbId();
        Cache::tags([CacheEnum::TAG_ROLE])->flush();
    }

    /**
     * 获取原始请求对象.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * 获取企业ID.
     */
    public function getEntId(): int
    {
        return 1; // 默认企业ID
    }

    /**
     * 获取模块的数据权限配置.
     *
     * @param string $module
     * @return array ['data_scope_type' => int, 'name' => string]
     */
    public function getModuleConfig(string $module): array
    {
        $config = ToolRegistry::getModuleConfig($module);

        return $config !== [] ? $config : [
            'name' => $module,
            'data_scope_type' => 1,
        ];
    }
}
