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

namespace App\Jobs;

use App\Constants\MenuEnum;
use App\Http\Dao\Auth\RoleUserDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\System\MenusService;
use Tuoluojiang\Open\services\SwooleTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 权限队列任务
 */
class SystemRoleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public function __construct(private int $roleId = 0, private array $rules = [], private array $apis = [], private bool $isEdit = false) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            app('enforcer')->deletePermissionsForUser('role_' . $this->roleId);
            $ruleMenus = app(MenusService::class)->select(['status' => 1, 'id' => array_unique(array_merge($this->rules, $this->apis))], ['id', 'api', 'methods', 'methods', 'type', 'uniqued', 'unique_auth'])?->toArray();
            $policies  = collect($ruleMenus)->map(function ($menu) {
                $role = 'role_' . $this->roleId; // 角色/用户标识
                return match ($menu['type']) {
                    MenuEnum::TYPE_MENU => [$role, $menu['uniqued'], $menu['type']],
                    MenuEnum::TYPE_API  => [$role, $menu['api'], $menu['methods']],
                    default             => [$role, $menu['uniqued'], $menu['unique_auth']],
                };
            })->all();
            app('enforcer')->addPolicies($policies);
            $this->pushPermissionChanged();
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    protected function pushPermissionChanged(): void
    {
        $users = app(RoleUserDao::class)->select(['role_id' => $this->roleId], ['user_id', 'entid'])?->toArray() ?: [];
        if (! $users) {
            return;
        }

        collect($users)
            ->groupBy('entid')
            ->each(function ($items, $entId) {
                $userIds = $items->pluck('user_id')->filter()->unique()->values()->all();
                if (! $userIds) {
                    return;
                }
                $userIds = app(AdminService::class)->column(['id' => $userIds], 'uid');
                $userIds = array_values(array_filter(array_unique(array_map('strval', $userIds))));
                if (! $userIds) {
                    Log::warning('角色权限 websocket 推送跳过：未找到用户UUID', [
                        'role_id' => $this->roleId,
                        'entid'   => $entId,
                    ]);
                    return;
                }

                SwooleTaskService::ent()
                    ->entid((int) $entId)
                    ->data('ent', [
                        'reason' => $this->isEdit ? 'role_updated' : 'role_created',
                        'time'   => time(),
                    ])
                    ->type('permission_changed')
                    ->to($userIds)
                    ->push();
                Log::info('角色权限 websocket 推送成功', [
                    'role_id' => $this->roleId,
                    'reason'  => $this->isEdit ? 'role_updated' : 'role_created',
                    'users'   => $userIds,
                    'count'   => count($userIds),
                    'entid'   => $entId,
                ]);
            });
    }
}
