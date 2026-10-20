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

namespace App\Observers;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Admin\AdminInfo;
use App\Http\Model\Company\Company;
use App\Http\Service\Company\CompanyService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Webpatser\Uuid\Uuid;

/**
 * 企业用户观察者.
 */
class AdminObserver
{
    /**
     * Handle the Admin "creating" event.
     * @throws \Exception
     */
    public function creating(Admin $model)
    {
        if (! Admin::exists()) {
            $create['type'] = $model->is_admin = 1;
        }
        if (isset($model->birthday)) {
            $model->age = birthday_to_age($model->birthday);
        }
        if (! $model->uid) {
            $model->uid = str_replace('-', '', (string) Uuid::generate(4));
        }
        if (! $model->mcp_key) {
            $model->mcp_key = Admin::generateMcpKey();
        }
        if ($model->id) {
            $create['id'] = $model->id;
        }
        $create['uid'] = $model->uid;
        AdminInfo::create($create);
    }

    /**
     * Handle the Admin "created" event.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public function created()
    {
        if (! Company::exists()) {
            $admin                   = Admin::where('is_admin', 1)->first();
            $data['uid']             = $admin->uid;
            $data['logo']            = 'https://crmebent.oss-cn-hangzhou.aliyuncs.com/attach/3/2022/10/4404a202210311831017540.png';
            $data['enterprise_name'] = '陀螺匠';
            $data['verify']          = 1;
            $data['status']          = 1;
            $data['phone']           = $admin->phone;
            $res                     = Company::create($data)->toArray();
            $res && app()->get(CompanyService::class)->afterVerify($admin->id);
        }
    }
}
