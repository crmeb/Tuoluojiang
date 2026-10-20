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

namespace App\Http\Service\Storage;

use App\Constants\CacheEnum;
use App\Http\Dao\Storage\StorageRecordDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 物资管理Services.
 * Class StorageRecordService.
 */
class StorageRecordService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(StorageRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取记录列表.
     * @param array|string[] $field
     * @param string $sort
     * @param array|string[] $with
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        return Cache::tags([CacheEnum::TAG_STORAGE])->remember(md5(json_encode($where) . json_encode(sort_mode($sort)) . $page . $limit), (int)sys_config('system_cache_ttl', 3600), function () use ($where, $page, $limit, $sort, $with) {
            if ($where['name'] || $where['cid']) {
                $storageIds      = [];
                $storageServices = app()->get(StorageService::class);
                if ($where['name'] !== '') {
                    $storageIds = array_merge($storageIds, $storageServices->column(['entid' => 1, 'name_like' => $where['name']], 'id') ?? []);
                }
                if ($where['cid'] !== '') {
                    $storageIds = array_merge($storageIds, $storageServices->column(['entid' => 1, 'cid' => $where['cid']], 'id') ?? []);
                }
                $where['ids'] = $storageIds;
            }
            unset($where['name'], $where['cid']);
            $list  = $this->dao->getList($where, ['id', 'storage_id', 'operator', 'user_id', 'creater', 'frame_id', 'mark', 'info', 'price', 'num', 'status', 'types', 'created_at', 'updated_at'], $page, $limit, $sort, $with + [
                    'storage' => function ($query) {
                        $query->select(['id', 'cid', 'name', 'units', 'specs', 'factory', 'number']);
                    },
                    'card'    => function ($query) {
                        $query->select(['id', 'name', 'avatar']);
                    },
                    'frame'   => function ($query) {
                        $query->select(['id', 'name']);
                    },
                    'creater' => function ($query) {
                        $query->select(['id', 'name', 'avatar']);
                    },
                ]);
            $count = $this->dao->count($where);
            return $this->listData($list, $count);
        });
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 保存物资记录.
     * @return mixed|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceSave(array $data)
    {
        $userId = auth('admin')->id();
        $save   = [
            'types'    => $data['types'],
            'mark'     => $data['mark'],
            'entid'    => 1,
            'user_id'  => $data['user_type'] ? 0 : $data['user_id'],
            'frame_id' => $data['user_type'] ? $data['user_id'] : 0,
            'creater'  => $userId,
            'operator' => $userId,
        ];

        $storageService = app()->get(StorageService::class);
        switch ($data['types']) {
            case 4:
                if (!$data['mark']) {
                    throw $this->exception('请填写报废原因');
                }
                $storage              = $storageService->get($data['storage']);
                $save['storage_id']   = $data['storage'];
                $save['storage_type'] = 1;
                $save['num']          = 1;
                $save['total']        = $save['price'] = $this->dao->value(['storage_id' => $data['storage'], 'types' => 0], 'total');
                if ($storage['link_id']) {
                    $save['info'] = '报废来源：' . $this->getLinkUser($storage['link_id']);
                    $this->dao->update($storage['link_id'], ['status' => 4]);
                } else {
                    $save['info'] = '报废来源：仓库';
                }
                $this->dao->create($save);
                break;
            case 3:
                if (!$data['mark']) {
                    throw $this->exception('请填写维修说明');
                }
                $save['storage_id']   = $data['storage'];
                $save['storage_type'] = 1;
                $save['num']          = 1;
                $storage              = $storageService->get($data['storage']);
                if ($storage['link_id']) {
                    $save['info'] = '维修来源：' . $this->getLinkUser($storage['link_id']);
                    $this->dao->update($storage['link_id'], ['status' => 3]);
                } else {
                    $save['info'] = '维修来源：仓库';
                }
                $this->dao->create($save);
                break;
            case 2:
                if (!in_array($data['user_type'], [0, 1])) {
                    throw $this->exception('操作用户类型不正确');
                }
                if (!$data['user_id']) {
                    throw $this->exception('缺少人员/部门ID');
                }
                if (!is_array($data['storage'])) {
                    throw $this->exception('请选择正确的物资编号');
                }
                $save['info'] = '归还对象：' . $this->getLastName($save['user_id'], $save['frame_id']);
                foreach ($data['storage'] as $v) {
                    $save['storage_id']   = $v;
                    $save['storage_type'] = 1;
                    $save['num']          = 1;
                    if ($link_id = $storageService->value($v, 'link_id')) {
                        $save['info'] = '归还对象：' . $this->getLinkUser($link_id);
                        $this->dao->update($link_id, ['status' => 2]);
                    }
                    $this->dao->create($save);
                }
                break;
            case 1:
                if (!in_array($data['user_type'], [0, 1])) {
                    throw $this->exception('操作用户类型不正确');
                }
                if (!$data['user_id']) {
                    throw $this->exception('缺少人员/部门ID');
                }
                if (!is_array($data['storage'])) {
                    throw $this->exception('请选择正确的物资信息');
                }
                $data['storage'] = array_filter($data['storage'], fn($item) => isset($item['id']));
                if (!$data['storage']) {
                    throw $this->exception('请至少选择一个有效的物资');
                }
                $save['info'] = '领取对象：' . $this->getLastName($save['user_id'], $save['frame_id']);
                foreach ($data['storage'] as $v) {
                    if (isset($v['id']) && $v['id'] && $v['num'] > 0) {
                        $save['storage_id']     = $v['id'];
                        $save['num']            = $v['num'];
                        $save['storage_type']   = $v['types'];
                        $save['status']         = 1;
                        $storageSave['link_id'] = $this->dao->create($save)->id;
                        $storageService->update($v['id'], $storageSave);
                    }
                }
                break;
            case 0:
                if (!$data['storage']) {
                    throw $this->exception('请选择正确的物资信息');
                }
                $save['storage_id']   = $data['storage'];
                $save['storage_type'] = 0;
                $save['num']          = $data['other'];
                $save['price']        = $data['price'];
                $save['total']        = bcmul((string)$data['price'], (string)$data['other'], 2);
                $this->dao->create($save);
                break;
            case 5:
                if ($data['price'] === '') {
                    throw $this->exception('请填写维修金额');
                }
                if (!$data['storage']) {
                    throw $this->exception('请选择正确的物资信息');
                }
                $save['storage_id']   = $data['storage'];
                $save['price']        = $save['total'] = $data['price'];
                $save['num']          = 1;
                $save['storage_type'] = 1;
                if ($data['other']) {
                    $storage = $storageService->get($data['storage']);
                    if ($storage['link_id']) {
                        $save['info'] = '报废来源：' . $this->getLinkUser($storage['link_id']);
                        $this->dao->update($storage['link_id'], ['status' => 4]);
                    } else {
                        $save['info'] = '报废来源：仓库';
                    }
                    $remark = '报废时间：' . now()->toDateString();
                    $price  = $this->dao->value(['storage_id' => $data['storage'], 'types' => 0], 'total');
                    $this->dao->create([
                        'storage_id'   => $save['storage_id'],
                        'price'        => $price,
                        'total'        => $price,
                        'info'         => $save['info'],
                        'types'        => 4,
                        'storage_type' => 1,
                        'entid'        => 1,
                        'user_id'      => $data['user_type'] ? 0 : $data['user_id'],
                        'frame_id'     => $data['user_type'] ? $data['user_id'] : 0,
                        'creater'      => $save['creater'],
                    ]);
                } else {
                    $storage      = $storageService->get($data['storage']);
                    $save['info'] = $storage['status'] == 0 ? '入库来源：仓库' : '入库来源：' . $this->getLastUserName($data['storage'], 1, 1);
                    if ($storage['link_id']) {
                        $save['info'] = '归还对象：' . $this->getLinkUser($storage['link_id']);
                        $this->dao->update($storage['link_id'], ['status' => 2]);
                    }
                    $remark = '入库时间：' . now()->toDateString();
                }
                $this->dao->create($save);
                $storageService->update($data['storage'], ['status' => $data['other'], 'remark' => $remark]);
                break;
            default:
        }
        Cache::tags([CacheEnum::TAG_STORAGE])->flush();
        return $storage ?? true;
    }

    /**
     * 获取物资当前关联人员/部门.
     * @param mixed $linkId
     * @return mixed|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getLinkUser($linkId)
    {
        $record = $this->dao->get($linkId, ['user_id', 'frame_id']);
        $name   = '仓库';
        if (isset($record->user_id) && $record->user_id) {
            $name = app()->get(AdminService::class)->value($record->user_id, 'name');
        }
        if (isset($record->frame_id) && $record->frame_id) {
            $name = app()->get(FrameService::class)->value($record->frame_id, 'name');
        }
        return $name;
    }

    /**
     * 获取物资最后处理人.
     * @param mixed $storageId
     * @param mixed $entId
     * @param mixed $types
     * @return mixed|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getLastUserName($storageId, $entId, $types)
    {
        $record = $this->dao->setDefaultSort('id')->get(['types' => $types, 'entid' => $entId, 'storage_id' => $storageId]);
        $name   = '仓库';
        if (isset($record->user_id) && $record->user_id) {
            $name = app()->get(AdminService::class)->value($record->user_id, 'name');
        }
        if (isset($record->frame_id) && $record->frame_id) {
            $name = app()->get(FrameService::class)->value($record->frame_id, 'name');
        }
        return $name;
    }

    /**
     * 获取物资最后处理人.
     * @param mixed $user_id
     * @param mixed $frame_id
     * @return mixed|string
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getLastName($user_id, $frame_id)
    {
        $name = '';
        if ($user_id) {
            $name = app()->get(AdminService::class)->value($user_id, 'name');
        }
        if ($frame_id) {
            $name = app()->get(FrameService::class)->value($frame_id, 'name');
        }
        return $name;
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        return [];
    }

    /**
     * 获取记录关联人员/部门列表.
     * @param mixed $status
     * @param mixed $storageId
     * @return array|mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getRecordUsers($status, $storageId)
    {
        return Cache::tags([CacheEnum::TAG_STORAGE])->remember(md5($status . $storageId), (int)sys_config('system_cache_ttl', 3600), function () use ($status, $storageId) {
            // 已领用物资ID
            if (!$storageId) {
                $storageId = app()->get(StorageService::class)->column(['entid' => 1, 'status' => 1], 'id') ?? [];
            }
            $where = ['entid' => 1, 'types' => $status, 'storage_id' => $storageId];
            if ($status == 1) {
                $where += ['status' => 1];
            }
            // 相关用户
            $userIds = $this->dao->column($where, 'user_id');
            $users   = ($users = app()->get(AdminService::class)->select(['id' => $userIds], ['id', 'name'])->each(function ($item) {
                $item['types'] = 0;
            })) ? $users->toArray() : [];
            // 相关部门
            $frameIds = $this->dao->column($where, 'frame_id');
            $frames   = ($frames = app()->get(FrameService::class)->select(['ids' => $frameIds], ['id', 'name'])->each(function ($item) {
                $item['types'] = 1;
            })) ? $frames->toArray() : [];
            return array_merge($frames, $users);
        });
    }

    /**
     * 获取记录历史关联人员/部门列表.
     * @param mixed $where
     * @return array|mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getRecordHistoryUsers($where)
    {
        return Cache::tags([CacheEnum::TAG_STORAGE])->remember(md5(json_encode($where)), (int)sys_config('system_cache_ttl', 3600), function () use ($where) {
            // 相关用户
            $userIds = $this->dao->column($where, 'user_id');
            $users   = ($users = app()->get(AdminService::class)->select(['id' => $userIds], ['id', 'name'])->each(function ($item) {
                $item['types'] = 0;
            })) ? $users->toArray() : [];
            // 相关部门
            $frameIds = $this->dao->column($where, 'frame_id');
            $frames   = ($frames = app()->get(FrameService::class)->select(['ids' => $frameIds], ['id', 'name'])->each(function ($item) {
                $item['types'] = 1;
            })) ? $frames->toArray() : [];
            return array_merge($frames, $users);
        });
    }

    /**
     * 获取物资统计
     * @param mixed $where
     * @return array
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getRecordCensus($where)
    {
        $where['entid'] = 1;
        if (!$where['time']) {
            throw $this->exception('请选择查询时间');
        }
        [$start, $end] = explode('-', $where['time']);
        if ((Carbon::make($end)->timestamp - Carbon::make($start)->timestamp) > 365 * 86400) {
            throw $this->exception('超过最大可选时间范围');
        }
        $storage_price  = [
            'put_price'    => $this->dao->sum(array_merge($where, ['types' => 0]), 'total'),
            'scrap_price'  => $this->dao->sum(array_merge($where, ['types' => 4]), 'total'),
            'repair_price' => $this->dao->sum(array_merge($where, ['types' => 5]), 'total'),
        ];
        $storageService = app()->get(StorageService::class);
        $storageIds1    = $storageService->column(['entid' => 1, 'types' => 0], 'id') ?? [];
        $storageIds2    = $storageService->column(['entid' => 1, 'types' => 1], 'id') ?? [];
        $storage_count  = [
            'put_count'    => (int)$this->dao->sum(array_merge($where, ['types' => 0]), 'num'),
            'fixed_count'  => $this->dao->count(array_merge($where, ['types' => 1, 'storage_id' => $storageIds2])),
            'temp_count'   => (int)$this->dao->sum(array_merge($where, ['types' => 1, 'storage_id' => $storageIds1]), 'num'),
            'repair_count' => $this->dao->count(array_merge($where, ['types' => 3])),
            'scrap_count'  => $this->dao->count(array_merge($where, ['types' => 4])),
        ];
        return compact('storage_price', 'storage_count');
    }

    /**
     * 获取维修记录详情.
     * @param mixed $id
     * @return array|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getRepair($id)
    {
        if (app()->get(StorageService::class)->value($id, 'status') != 3) {
            throw $this->exception('状态错误，当前物资未处于维修中');
        }
        $record = $this->dao->setDefaultSort('id')->get(['storage_id' => $id, 'entid' => 1, 'types' => 3], ['id', 'storage_id', 'mark', 'created_at']);
        return $record ? $record->toArray() : throw $this->exception('未找到相关维修记录');
    }
}
