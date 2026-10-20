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

use App\Constants\CacheEnum;
use Tuoluojiang\Open\interfaces\EntUserInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * 企业用户信息
 * Class EntUserService.
 */
class EntUserService
{
    /**
     * services类名.
     * @var string
     */
    protected $userEnterpriseService;

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
     * EntUserService constructor.
     * @param mixed $cache
     */
    public function __construct(string $services, $cache, int $cacheTime = 3600)
    {
        $this->userEnterpriseService = $services;
        $this->cache                 = $cache;
        $this->cacheTime             = $cacheTime;
    }

    /**
     * 静态调用.
     * @return EntUserInterface
     * @throws BindingResolutionException
     */
    public static function instance()
    {
        return app()->get('enterprise_user');
    }

    /**
     * 实例化service.
     * @return EntUserInterface
     * @throws BindingResolutionException
     */
    public function getUserEnterpriseService()
    {
        return app()->get($this->userEnterpriseService);
    }

    /**
     * 根据用户uuid获取企业用户ID.
     * @return array
     * @throws BindingResolutionException
     */
    public function uuidToUid(string $uuid, int $entId = 1, bool $isCaChe = false): int|string
    {
        $callable = function () use ($uuid, $entId) {
            try {
                return $this->getUserEnterpriseService()->uuidToUid($uuid, $entId);
            } catch (\Exception) {
                return 0;
            }
        };
        try {
            $cacheName = md5('uid_' . $uuid . '_' . $entId);
            if ($isCaChe) {
                return $callable();
            }
            return (int) Cache::tags([CacheEnum::TAG_FRAME])->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable) {
            return $callable();
        }
    }

    /**
     * 根据用户uid获取企业用户uuid.
     * @throws BindingResolutionException
     */
    public function uidToUuid(int $uid, bool $isCaChe = false): int|string
    {
        $callable = function () use ($uid) {
            try {
                return $this->getUserEnterpriseService()->uidToUuid($uid);
            } catch (\Exception) {
                return 0;
            }
        };
        try {
            $cacheName = md5('uuid_' . $uid);

            if ($isCaChe) {
                return $callable();
            }

            return Cache::tags([CacheEnum::TAG_FRAME])->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable) {
            return $callable();
        }
    }

    /**
     * 根据用户uid获取企业用户名片id.
     * @throws BindingResolutionException
     */
    public function uidToCardId(int $uid, bool $isCaChe = false): int|string
    {
        $callable = function () use ($uid) {
            try {
                return $this->getUserEnterpriseService()->uidToCardId($uid);
            } catch (\Exception) {
                return 0;
            }
        };
        try {
            $cacheName = md5('card_id_' . $uid);

            if ($isCaChe) {
                return $callable();
            }

            return Cache::tags([CacheEnum::TAG_FRAME])->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable) {
            return $callable();
        }
    }

    /**
     * 根据用户uuid获取企业中的名片ID.
     * @throws BindingResolutionException
     */
    public function uuidToCardId(string $uuid, int $entid = 1, bool $isCaChe = false): int|string
    {
        $callable = function () use ($uuid, $entid) {
            try {
                return $this->getUserEnterpriseService()->uuidToCardid($uuid, $entid);
            } catch (\Exception) {
                return 0;
            }
        };
        try {
            $cacheName = md5('card_' . $uuid . '_' . $entid);

            if ($isCaChe) {
                return $callable();
            }

            return (int) $this->cache->tags(CacheEnum::TAG_FRAME)->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable) {
            return $callable();
        }
    }

    /**
     * 根据用户uuid获取企业中的名片.
     * @return array
     * @throws BindingResolutionException
     */
    public function uuidToCard(string $uuid, int $entid = 1, array|string $field = ['id', 'name', 'avatar'], bool $isCaChe = false)
    {
        $callable = function () use ($uuid, $entid, $field) {
            try {
                return $this->getUserEnterpriseService()->uuidToCard($uuid, $entid, $field);
            } catch (\Exception) {
                return [];
            }
        };
        try {
            $cacheName = md5('card_' . $uuid . '_' . $entid . '_' . json_encode($field));

            if ($isCaChe) {
                return $callable();
            }

            return $this->cache->tags(CacheEnum::TAG_FRAME)->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable) {
            return $callable();
        }
    }

    /**
     * 根据用户uuid获取企业中的名片.
     * @return array
     * @throws BindingResolutionException
     */
    public function cardToUid(int $cardId, int $entid = 1, bool $isCaChe = false): int
    {
        $callable = function () use ($cardId) {
            try {
                return $this->getUserEnterpriseService()->cardToUid($cardId);
            } catch (\Exception) {
                return 0;
            }
        };
        try {
            $cacheName = md5('uid_to_card_' . $cardId . $entid);

            if ($isCaChe) {
                return $callable();
            }

            return $this->cache->tags(CacheEnum::TAG_FRAME)->remember($cacheName, $this->cacheTime, $callable);
        } catch (\Throwable) {
            return $callable();
        }
    }
}
