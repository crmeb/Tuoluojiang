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

namespace App\Http\Service\Admin;

use App\Constants\CacheEnum;
use App\Http\Dao\Admin\AdminInfoDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * +----------------------------------------------------------------------
 * | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 * +----------------------------------------------------------------------
 * | Author: CRMEB Team <admin@crmeb.com>
 * +----------------------------------------------------------------------
 * | @author: lunatic<453850531@qq.com>
 * +----------------------------------------------------------------------
 * | @day: 2024/5/16
 * +----------------------------------------------------------------------.
 */
class AdminInfoService extends BaseService
{
    public function __construct(AdminInfoDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     *  获取某个企业下的在职用户id.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getEntUserIdsCache(int $entId, int $page, int $limit): array
    {
        $res = Cache::tags([CacheEnum::TAG_FRAME])->remember(
            md5('enterprise_user_list_' . $entId . '_' . $page . '_' . $limit),
            (int) sys_config('system_cache_ttl', 3600),
            fn () => json_encode($this->dao->selectModel([
                'type' => [1, 2, 3],
            ], ['id', 'uid'])->forPage($page, $limit)->get()->toArray(), JSON_UNESCAPED_UNICODE)
        );
        return $res ? json_decode($res, true) : [];
    }
}
