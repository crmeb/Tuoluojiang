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

namespace App\Console\Commands;

use App\Constants\CacheEnum;
use App\Constants\MenuEnum;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * 将系统接口同步到菜单权限表.
 */
class SyncApiMenusCommand extends Command
{
    /**
     * 命令签名.
     *
     * @var string
     */
    protected $signature = 'menus:sync-api
                            {--path=* : 接口路径前缀，可多次传入，默认 api/ent、api/uni、api/open}
                            {--entid=0 : 菜单归属企业ID}
                            {--dry-run : 仅预览，不写入数据}
                            {--rollback : 回滚本命令生成的接口菜单}
                            {--no-role-sync : 不同步新增接口到 role_all 权限策略}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '扫描路由并按前端真实页面调用写入 system_menus 接口菜单';

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        $paths    = array_values(array_filter((array) $this->option('path'))) ?: ['api/ent', 'api/uni', 'api/open'];
        $entId    = (int) $this->option('entid');
        $isDryRun = (bool) $this->option('dry-run');
        $syncRole = ! (bool) $this->option('no-role-sync');
        if ((bool) $this->option('rollback')) {
            return $this->rollbackApiMenus($paths, $entId, $isDryRun, $syncRole);
        }

        $routes    = $this->getApiRoutes($paths);
        $exists    = $this->getExistsApiKeys($entId);
        $inserted  = 0;
        $skipped   = 0;
        $unmapped  = 0;
        $parentMap = $this->getFixedApiParentMap($entId);

        if ($isDryRun) {
            $this->warn('【Dry Run 模式】此操作不会实际写入菜单数据');
        }

        $this->info('待扫描接口路由：' . count($routes) . ' 条');
        $this->info('固定映射接口：' . count($parentMap) . ' 条');

        DB::beginTransaction();
        try {
            foreach ($routes as $route) {
                $key = $route['api'] . '|' . $route['methods'];
                if (isset($exists[$key])) {
                    ++$skipped;
                    continue;
                }

                $parent = $parentMap[$this->apiKey($route['methods'], $route['api'])] ?? null;
                if (! $parent) {
                    ++$unmapped;
                    continue;
                }

                $data = $this->buildApiMenuData($route, $parent, $entId);

                if ($isDryRun) {
                    ++$inserted;
                    $this->line("[新增] {$data['methods']} {$data['api']} -> {$parent['menu_name']}");
                    continue;
                }

                $id = DB::table('system_menus')->insertGetId($data);
                ++$inserted;
                $exists[$key] = $id;

                if ($syncRole) {
                    app('enforcer')->addPermissionForUser('role_all', $data['api'], $data['methods']);
                }
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                Cache::tags([CacheEnum::TAG_ROLE, 'rules', 'api-rule'])->flush();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('========== 同步完成 ==========');
        $this->info('新增接口：' . $inserted);
        $this->info('跳过已存在接口：' . $skipped);
        $this->info('未匹配前端页面接口：' . $unmapped);

        return self::SUCCESS;
    }

    /**
     * 回滚本命令生成的接口菜单.
     */
    private function rollbackApiMenus(array $paths, int $entId, bool $isDryRun, bool $syncRole): int
    {
        $apiMenus = $this->getRollbackApiMenus($paths, $entId);
        $groups   = $this->getRollbackGroups($entId, $apiMenus->pluck('id')->all());

        if ($isDryRun) {
            $this->warn('【Dry Run 模式】此操作不会实际回滚菜单数据');
        }

        $this->info('待回滚接口菜单：' . $apiMenus->count() . ' 条');
        $this->info('待回滚历史隐藏父级分组：' . $groups->count() . ' 个');

        if ($apiMenus->isEmpty() && $groups->isEmpty()) {
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            if (! $isDryRun) {
                if ($apiMenus->isNotEmpty()) {
                    DB::table('system_menus')
                        ->whereIn('id', $apiMenus->pluck('id')->all())
                        ->delete();
                }

                if ($groups->isNotEmpty()) {
                    DB::table('system_menus')
                        ->whereIn('id', $groups->pluck('id')->all())
                        ->delete();
                }

                if ($syncRole) {
                    $apiMenus->each(fn ($menu) => app('enforcer')->deletePermission($menu->api, $menu->methods));
                    $groups->each(fn ($menu) => app('enforcer')->deletePermission($menu->uniqued, MenuEnum::TYPE_MENU));
                }
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                Cache::tags([CacheEnum::TAG_ROLE, 'rules', 'api-rule'])->flush();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('========== 回滚完成 ==========');
        $this->info('回滚接口：' . $apiMenus->count());
        $this->info('回滚隐藏父级分组：' . $groups->count());

        return self::SUCCESS;
    }

    /**
     * 获取需要回滚的接口菜单.
     */
    private function getRollbackApiMenus(array $paths, int $entId)
    {
        return DB::table('system_menus')
            ->select(['id', 'pid', 'api', 'methods', 'unique_auth', 'uniqued'])
            ->where('type', MenuEnum::TYPE_API)
            ->where('entid', $entId)
            ->where('unique_auth', 'like', 'api:%')
            ->where(function ($query) use ($paths) {
                foreach ($paths as $path) {
                    $query->orWhere('api', 'like', trim($path, '/') . '%');
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * 获取接口回滚后可移除的隐藏父级分组.
     */
    private function getRollbackGroups(int $entId, array $rollbackApiIds)
    {
        return DB::table('system_menus')
            ->select(['id', 'pid', 'menu_name', 'unique_auth', 'uniqued'])
            ->where('type', MenuEnum::TYPE_MENU)
            ->where('entid', $entId)
            ->where('unique_auth', 'like', 'api_group:%')
            ->whereNotExists(function ($query) use ($rollbackApiIds) {
                $query->selectRaw('1')
                    ->from('system_menus as child')
                    ->whereColumn('child.pid', 'system_menus.id')
                    ->whereNull('child.deleted_at');

                if ($rollbackApiIds) {
                    $query->whereNotIn('child.id', $rollbackApiIds);
                }
            })
            ->orderByDesc('id')
            ->get();
    }

    /**
     * 获取固定接口父级菜单映射.
     */
    private function getFixedApiParentMap(int $entId): array
    {
        $config = require config_path('api_menu_parents.php');
        if (! is_array($config)) {
            return [];
        }

        $components = collect($config)
            ->pluck('component')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $components) {
            return [];
        }

        $menus = DB::table('system_menus')
            ->select(['id', 'pid', 'menu_name', 'paths', 'component', 'uniqued'])
            ->where('type', MenuEnum::TYPE_MENU)
            ->where('entid', $entId)
            ->whereIn('component', $components)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $menusByComponent = [];
        foreach ($menus as $menu) {
            $menusByComponent[$menu->component] ??= (array) $menu;
        }

        $map = [];
        foreach ($config as $apiKey => $item) {
            $component = $item['component'] ?? '';
            if ($component !== '' && isset($menusByComponent[$component])) {
                $map[$apiKey] = $menusByComponent[$component];
            }
        }

        return $map;
    }

    /**
     * 生成接口匹配 key，统一动态参数名称.
     */
    private function apiKey(string $method, string $api): string
    {
        $api = preg_replace('/\{[^}]+}/', '{param}', trim($api, '/')) ?? trim($api, '/');

        return strtoupper($method) . '|' . $api;
    }

    /**
     * 获取接口路由.
     */
    private function getApiRoutes(array $paths): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => $this->isTargetRoute($route, $paths))
            ->map(fn (Route $route) => $this->formatRoute($route))
            ->filter()
            ->unique(fn (array $route) => $route['api'] . '|' . $route['methods'])
            ->values()
            ->all();
    }

    /**
     * 判断是否为目标接口路由.
     */
    private function isTargetRoute(Route $route, array $paths): bool
    {
        $uri = $route->uri();
        if (Str::contains($uri, ['{fallbackPlaceholder}', '_debugbar'])) {
            return false;
        }

        foreach ($paths as $path) {
            if (Str::startsWith($uri, trim($path, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 格式化路由信息.
     */
    private function formatRoute(Route $route): ?array
    {
        $method = collect($route->methods())->reject(fn (string $method) => $method === 'HEAD')->first();
        if (! $method) {
            return null;
        }

        return [
            'api'      => $route->uri(),
            'methods'  => strtoupper($method),
            'menuName' => $this->formatMenuName((string) ($route->getName() ?: $route->getActionName())),
        ];
    }

    /**
     * 格式化接口名称.
     */
    private function formatMenuName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '未命名接口';
        }

        return mb_strlen($name) > 60 ? mb_substr($name, 0, 60) : $name;
    }

    /**
     * 获取已存在接口权限.
     */
    private function getExistsApiKeys(int $entId): array
    {
        return DB::table('system_menus')
            ->select(['id', 'api', 'methods'])
            ->where('type', MenuEnum::TYPE_API)
            ->where('entid', $entId)
            ->where('api', '!=', '')
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(fn ($menu) => [$menu->api . '|' . $menu->methods => $menu->id])
            ->all();
    }

    /**
     * 构造接口菜单数据.
     */
    private function buildApiMenuData(array $route, array $parent, int $entId): array
    {
        $now        = now()->toDateTimeString();
        $uniqueAuth = 'api:' . md5($route['methods'] . '|' . $route['api']);

        return [
            'pid'               => (int) $parent['id'],
            'icon'              => '',
            'menu_name'         => $route['menuName'],
            'api'               => $route['api'],
            'methods'           => $route['methods'],
            'unique_auth'       => $uniqueAuth,
            'menu_path'         => '',
            'menu_type'         => 0,
            'crud_id'           => 0,
            'uni_path'          => '',
            'uni_img'           => '',
            'position'          => 0,
            'paths'             => $this->childPaths($parent),
            'component'         => '',
            'level'             => $this->childLevel($parent),
            'other'             => '',
            'sort'              => 0,
            'entid'             => $entId,
            'type'              => MenuEnum::TYPE_API,
            'is_show'           => 1,
            'status'            => 1,
            'uniqued'           => md5($uniqueAuth),
            'parent_uniqued'    => $parent['uniqued'] ?? null,
            'crud_app_id'       => 0,
            'crud_dashboard_id' => 0,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }

    /**
     * 获取子级 paths 字段.
     */
    private function childPaths(array $parent): string
    {
        $paths = array_filter(explode('/', (string) ($parent['paths'] ?? '')));
        if (! empty($parent['id'])) {
            $paths[] = (string) $parent['id'];
        }

        return implode('/', $paths);
    }

    /**
     * 获取子级层级.
     */
    private function childLevel(array $parent): int
    {
        $paths = $this->childPaths($parent);

        return $paths === '' ? 0 : count(explode('/', $paths));
    }
}
