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

namespace App\Http\Service\Rank;

use App\Constants\CacheEnum;
use App\Http\Dao\Position\PositionLevelDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 职级体系
 * Class RankLevelService.
 */
class RankLevelService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    /**
     * RankLevelService constructor.
     */
    public function __construct(PositionLevelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 职位体系图列表数据.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $tag  = 'Rank';
        $keys = 'RankLevel_' . $where['entid'];
        if (Cache::tags([$tag])->has($keys)) {
            $list = json_decode(Cache::tags([$tag])->get($keys), true);
        } else {
            $list        = $this->dao->setEach()->getList($where, $field, 0, 0, 'id');
            $cateService = app()->get(RankCategoryService::class);
            $cates       = $cateService->select(['entid' => $where['entid']]);
            if ($list) {
                /** @var RankRelationService $relationService */
                $relationService = app()->get(RankRelationService::class);
                $list->each(function ($item) use ($cates, $relationService) {
                    $info = [];
                    foreach ($cates as $key => $val) {
                        $info[] = [
                            'name' => $val['name'],
                            'id'   => $val['id'],
                            'info' => $relationService->get(['level_id' => $item['id'], 'cate_id' => $val['id']], ['id', 'level_id', 'rank_id'], [
                                'rank' => function ($query) {
                                    $query->select(['id', 'name', 'alias']);
                                },
                                'job' => function ($query) use ($val) {
                                    $query->where('cate_id', $val['id'])->select(['rank_id', 'cate_id', 'name']);
                                },
                            ]),
                        ];
                    }
                    $item['info'] = $info;
                });
            }
            $list = $list ? $list->toArray() : [];
            Cache::tags([$tag])->put($keys, json_encode($list));
        }
        return $list;
    }

    /**
     * 保存数据.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $data['min_level'] = (int) $data['min_level'];
        $data['max_level'] = (int) $data['max_level'];
        if ($data['max_level'] < $data['min_level']) {
            throw $this->exception('请输入正确的职等范围！');
        }
        $res = $this->transaction(function () use ($data) {
            return $this->dao->create($data);
        });
        $res && Cache::tags([CacheEnum::TAG_OTHER])->flush();
        return $res;
    }

    /**
     * 修改数据.
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        $info = $this->dao->get($id);
        if ($info->entid != 1) {
            throw $this->exception('您无权限修改该条记录');
        }
        $data['min_level'] = (int) $data['min_level'];
        $data['max_level'] = (int) $data['max_level'];
        if ($data['max_level'] < $data['min_level']) {
            throw $this->exception('请输入正确的职等范围！');
        }
        $res = $this->transaction(function () use ($id, $data) {
            return $this->dao->update($id, $data);
        });
        $res && Cache::tags([CacheEnum::TAG_OTHER])->flush();
        return $res;
    }

    /**
     * 删除数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $info = $this->dao->get($id);
        if ($info->entid != 1) {
            throw $this->exception('您无权限删除该条记录');
        }
        $res = $this->transaction(function () use ($id) {
            $res             = $this->dao->delete($id);
            $relationService = app()->get(RankRelationService::class);
            $relationService->delete($id, 'level_id');
            return $res;
        });
        $res && Cache::tags([CacheEnum::TAG_OTHER])->flush();
        return $res;
    }

    /**
     * 批量设置职等.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function batchSection(int $batch, $entid)
    {
        if (! $batch || $batch < 0) {
            throw $this->exception('请填写正确的区间！');
        }
        $list = $this->dao->select(['entid' => $entid], ['id', 'min_level', 'max_level']);
        if ($list) {
            $list = $list->toArray();
            $res  = $this->transaction(function () use ($list, $batch) {
                foreach ($list as $key => $value) {
                    $this->dao->update($value['id'], [
                        'min_level' => 1 + ($key * $batch),
                        'max_level' => ($key + 1) * $batch,
                    ]);
                }
                return true;
            });
            $res && Cache::tags([CacheEnum::TAG_OTHER])->flush();
            return $res;
        }
        throw $this->exception('暂无职等，无法设置！');
    }
}
