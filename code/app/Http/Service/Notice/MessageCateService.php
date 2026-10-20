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

namespace App\Http\Service\Notice;

use App\Constants\CacheEnum;
use App\Http\Dao\Category\MessageCategoryDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\synchro\Message;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class MessageCateService extends BaseService
{
    public const CACHE_KEY = 'message_cate';

    public function __construct(MessageCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getListByCache(array $where): mixed
    {
        return Cache::tags([self::CACHE_KEY])->remember(json_encode($where), (int) sys_config('system_cache_ttl', 3600), function () use ($where) {
            return get_tree_children($this->dao->getTierList($where, ['*', 'id as value', 'cate_name as label']), 'children', 'value');
        });
    }

    /**
     * 获取移动端消息分类列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUniNoticeCate(int $userId, int $entId = 1): array
    {
        $noticeService = app()->get(NoticeRecordService::class);
        $list          = $this->getListByCache(['uni_show' => 1]);
        $uuid          = uid_to_uuid($userId) ?: (string) $userId;
        return array_values(array_filter(array_map(function ($item) use ($noticeService, $userId, $entId, $uuid) {
            if (isset($item['children'])) {
                $cateIds   = array_column($item['children'], 'id');
                $cateIds[] = $item['id'];
                $cateIds[] = 2;
            } else {
                $cateIds = $item['id'];
            }

            if (is_array($cateIds)) {
                $key = $this->getKey((string) $item['id']);
                $ttl = (int) sys_config('system_cache_ttl', 3600);
                if ($oldCate = Cache::tags([CacheEnum::TAG_OTHER])->get($key)) {
                    $oldCate !== $cateIds && Cache::put($key, $cateIds, $ttl);
                } else {
                    Cache::add($key, $cateIds, $ttl);
                }
            }
            $where = [
                'entid'   => $entId,
                'to_uid'  => $userId,
                'uid'     => $uuid,
                'cate_id' => $cateIds,
            ];

            $data['count'] = $noticeService->dao->setWhere(array_merge($where, [
                'is_read' => 0,
            ]))->count();
            $data['id']        = $item['id'];
            $data['cate_name'] = $item['cate_name'];
            $data['pic']       = $item['pic'];
            $data['item']      = toArray($noticeService->dao->setWhere($where, ['id', 'message', 'created_at'], 0, 1, 'created_at')->first());
            if ($data['item']) {
                return $data;
            }
        }, $list)));
    }

    /**
     * 同步分类.
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function syncRemoteCate(): bool
    {
        $cateList = app()->get(Message::class)->setConfig()->getCateList();
        if (empty($cateList)) {
            return true;
        }

        $list = [];
        foreach ($cateList as $item) {
            $path   = $item['path'] ?? [];
            $list[] = [
                'id'        => $item['id'] ?? 0,
                'pid'       => $item['pid'] ?? 0,
                'cate_name' => $item['cate_name'] ?? '',
                'path'      => $path ? implode('/', $path) : '',
                'sort'      => $item['sort'] ?? 0,
                'pic'       => $item['pic'] ?? '',
                'is_show'   => $item['is_show'] ?? 0,
                'uni_show'  => $item['uni_show'] ?? 0,
                'level'     => $item['level'] ?? 0,
            ];
        }

        $this->dao->getModel()->truncate();
        return $this->dao->insert($list) && Cache::tags([self::CACHE_KEY])->flush();
    }
}
