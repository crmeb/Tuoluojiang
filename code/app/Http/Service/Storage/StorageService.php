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
use App\Http\Dao\Storage\StorageDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 物资管理Services.
 * Class StorageService.
 */
class StorageService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(StorageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取物资列表.
     * @param array|string[] $field
     * @param string $sort
     * @param array|string[] $with
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'cid', 'creater', 'name', 'specs', 'factory', 'units', 'mark', 'remark', 'stock', 'used', 'number', 'types', 'status', 'created_at'], $sort = 'id', array $with = []): array
    {
        Cache::tags([CacheEnum::TAG_STORAGE])->flush();
        $where = Cache::tags([CacheEnum::TAG_STORAGE])->remember(md5(json_encode($where)), (int) sys_config('system_cache_ttl', 3600), function () use ($where) {
            if ($where['types'] == 1 && $where['status'] == 1 && $where['receive']) {
                $recordService = app()->get(StorageRecordService::class);
                $ids           = [];
                if ($where['user_id']) {
                    $ids = array_merge($ids, $recordService->column(['entid' => 1, 'types' => 1, 'user_id' => $where['user_id'], 'status' => 1], 'storage_id') ?? []);
                }
                if ($where['frame_id']) {
                    $ids = array_merge($ids, $recordService->column(['entid' => 1, 'types' => 1, 'frame_id' => $where['frame_id'], 'status' => 1], 'storage_id') ?? []);
                }
                $where['ids'] = $ids;
            }
            unset($where['user_id'], $where['frame_id'], $where['receive']);
            if ($where['cid']) {
                $cids         = app()->get(StorageCategoryService::class)->getSubCates($where['cid']) ?? [];
                $cids[]       = (int) $where['cid'];
                $where['cid'] = $cids;
            }
            return $where;
        });
        return parent::getList($where, $field, $sort, $with + [
            'receiveUser' => fn ($query) => $query->select(['admin.id', 'name','storage_record.created_at']),
            'receiveFrame' => fn ($query) => $query->select(['frame.id', 'name','storage_record.created_at']),
            'cate' => fn ($query) => $query->select(['id', 'pid', 'cate_name', 'path']),
            'record' => fn ($query) => $query->where('status', 0)->select(['storage_id', 'price', 'num']),
            ]);
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 保存物资.
     * @return mixed|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceSave(array $data)
    {
        $id   = isset($data['id']) && $data['id'] ? $data['id'] : 0;
        $save = [
            'cid'        => $data['cid'],
            'name'       => $data['name'],
            'specs'      => $data['specs'],
            'factory'    => $data['factory'],
            'units'      => $data['units'],
            'mark'       => $data['mark'],
            'types'      => $data['types'],
            'entid'      => 1,
            'created_at' => now()->toDateTimeString(),
        ];
        $stock = [
            'num'      => $data['number'],
            'price'    => $data['price'],
            'total'    => $data['price'],
            'mark'     => $data['remark'],
            'entid'    => 1,
            'creater'  => auth('admin')->id(),
            'operator' => auth('admin')->id(),
        ];
        $numbers = [];
        $numbers = $this->transaction(function () use ($id, $save, $stock, $numbers) {
            $addNum        = $stock['num'];
            $storageRecord = app()->get(StorageRecordService::class);
            if ($save['types']) {
                if ($id) {
                    $numbers[] = $this->dao->update($id, $save);
                    if ($stock['price'] !== '') {
                        $storageRecord->update(['storage_id' => $id, 'types' => 0], ['price' => $stock['price']]);
                    }
                } else {
                    $maxNumber = (int) substr((string) ($this->dao->value(['entid' => 1, 'types' => 1, 'time' => 'today'], 'number') ?? 0), -5, 5);
                    if ($addNum) {
                        for ($i = 1; $i < $addNum + 1; ++$i) {
                            $number              = $this->getEntNumber(1, $maxNumber + $i);
                            $numbers[]           = $number;
                            $save['number']      = $number;
                            $save['stock']       = 1;
                            $stock['storage_id'] = $this->dao->getIncId($save);
                            $stock['num']        = 1;
                            $storageRecord->create($stock);
                        }
                    } else {
                        $this->dao->create($save);
                    }
                }
            } else {
                if ($id) {
                    $this->dao->update($id, $save);
                } else {
                    $id = $this->dao->getIncId($save);
                }
                if ($addNum) {
                    $this->setStock($id, $stock);
                }
                $numbers[] = $save;
            }
            return $numbers;
        });
        Cache::tags([CacheEnum::TAG_STORAGE])->flush();
        return $numbers;
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        return [];
    }

    /**
     * 删除物资及记录.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        return $this->transaction(function () use ($id, $key) {
            app()->get(StorageRecordService::class)->delete(['storage_id' => $id, 'entid' => 1]);
            return $this->dao->delete($id, $key);
        });
    }

    /**
     * 获取企业固定物资编号.
     * @param mixed $entid
     * @param mixed $start
     * @return string
     */
    public function getEntNumber($entid, $start)
    {
        return date('Ymd', time()) . $entid . str_pad((string) $start, 5, '0', STR_PAD_LEFT);
    }

    /**
     * 修改物资分类.
     * @param array $ids
     * @param int $cateId
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateCate(array $ids, int $cateId)
    {
        if (! $ids || ! $cateId) {
            throw $this->exception('缺少物资ID或移动分类ID');
        }
        return $this->dao->update(['ids' => $ids], ['cid' => $cateId]);
    }

    /**
     * 设置库存.
     * @param mixed $storage_id
     * @param mixed $stock
     * @throws BindingResolutionException
     */
    protected function setStock($storage_id, $stock)
    {
        $stock['storage_id'] = $storage_id;
        $stock['total']      = bcmul((string) $stock['price'], (string) $stock['num'], 2);
        app()->get(StorageRecordService::class)->create($stock);
    }
}
