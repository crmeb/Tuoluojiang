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

namespace Tuoluojiang\Open\services;

use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ConfigInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * 获取组合数据配置
 * Class GroupDataService.
 */
class GroupDataService extends BaseService
{
    /**
     * services类名.
     * @var string
     */
    protected $groupDataService;

    /**
     * 缓存时间.
     * @var int
     */
    protected $cacheTime;

    /**
     * 缓存句柄.
     * @var Cache
     */
    protected $cache;

    protected $entid;

    /**
     * GroupDataService constructor.
     * @param mixed $cache
     */
    public function __construct(string $services, $cache, int $cacheTime = 3600)
    {
        $this->groupDataService = $services;
        $this->cache            = $cache;
        $this->cacheTime        = $cacheTime;
    }

    /**
     * 静态调用.
     * @return GroupDataService
     * @throws BindingResolutionException
     */
    public static function instance()
    {
        return app()->get('group_config');
    }

    /**
     * 实例化service.
     * @return ConfigInterface
     * @throws BindingResolutionException
     */
    public function getGroupDataService()
    {
        return app()->get($this->groupDataService);
    }

    /**
     * 获取单个值
     * @param string $config_name 配置名称
     * @param int $limit 截取多少条
     * @param int $entid 企业ID
     * @param int $page 页数
     * @param bool $isCaChe 是否读取缓存
     * @throws BindingResolutionException
     */
    public function getData(string $config_name, int $limit = 0, int $entid = 1, int $page = 0, bool $isCaChe = false): array
    {
        $callable = function () use ($config_name, $limit, $entid, $page) {
            try {
                return $this->getGroupDataService()->getConfigLimit($config_name, $limit, $entid, $page);
            } catch (\Exception $e) {
                return [];
            }
        };
        try {
            $cacheName = $limit ? "data_{$config_name}_{$limit}_{$page}_{$entid}" : "data_{$config_name}_{$entid}";

            if ($isCaChe) {
                return $callable();
            }

            return $this->cache->tags("data_{$config_name}_{$entid}")->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable $e) {
            return $callable();
        }
    }

    /**
     * 根据id 获取单个值
     * @param bool $isCaChe 是否读取缓存
     */
    public function getDataNumber(int $id, bool $isCaChe = false): array
    {
        $callable = function () use ($id) {
            try {
                return $this->getGroupDataService()->getConfig($id);
            } catch (\Exception $e) {
                return [];
            }
        };
        try {
            $cacheName = "data_number_{$id}";

            if ($isCaChe) {
                return $callable();
            }

            return $this->cache->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable $e) {
            return $callable();
        }
    }
}
