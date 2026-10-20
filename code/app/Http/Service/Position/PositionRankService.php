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

namespace App\Http\Service\Position;

use App\Http\Dao\Position\PositionJobDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 企业职位/职位
 * Class EnterpriseRankJobServices.
 */
class PositionRankService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    /**
     * EnterpriseRankJobServices constructor.
     */
    public function __construct(PositionJobDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 职位列表数据.
     *
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['id', 'name', 'cate_id', 'rank_id', 'card_id', 'describe', 'duty'], $sort = null, array $with = []): array
    {
        return parent::getList($where, $field, 'id', $with + [
            'card' => function ($query) {
                $query->select(['id', 'name', 'avatar']);
            },
            'cate' => function ($query) {
                $query->select(['id', 'name']);
            },
            'rank' => function ($query) {
                $query->select(['id', 'name', 'info', 'alias']);
            },
        ]);
    }

    /**
     * 获取修改数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $jobInfo = $this->dao->get($id);
        if (! $jobInfo) {
            throw $this->exception('修改的职位不存在');
        }
        $this->jobCount((int) $jobInfo->entid, $id, $jobInfo);
        return $jobInfo->toArray();
    }

    /**
     * 获取创建职位数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceCreate(array $other = []): array
    {
        return [
            'tree'    => $this->dao->getList(['status' => 1, 'entid' => 1], ['name', 'id'], 0, 0, 'id'),
            'jobInfo' => (object) [],
        ];
    }

    /**
     * 保存数据.
     *
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $data['status']  = 1;
        $data['card_id'] = uuid_to_card_id($this->uuId(false), $data['entid']);
        return $this->transaction(function () use ($data) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->create($data);
        });
    }

    /**
     * 修改职位.
     *
     * @param int $id
     *
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $data['card_id'] = uuid_to_card_id($this->uuId(false), $data['entid']);
        $this->transaction(function () use ($id, $data) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->update($id, $data);
        });
        return true;
    }

    /**
     * 删除.
     *
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(PositionRelationService::class)->exists(['rank_id' => $id, 'entid' => 1])) {
            throw $this->exception('请先取消职位体系图关联，再尝试删除');
        }
        return $this->transaction(function () use ($id) {
            if (str_contains($id, ',')) {
                $this->dao->delete(['id' => explode(',', $id)]);
                Cache::tags(['Rank'])->flush();
                return true;
            }
            Cache::tags(['Rank'])->flush();
            return $this->dao->delete($id, 'id');
        });
    }

    /**
     * 职位人数统计
     * @param null $jobInfo
     * @throws BindingResolutionException
     */
    public function jobCount(int $entId, int $jobId, $jobInfo = null)
    {
        return Cache::tags(['rank_job_count_' . $entId])->remember('job_count_' . $entId . '_' . $jobId, (int) sys_config('system_cache_ttl', 3600), function () use ($entId, $jobId, $jobInfo) {
            if (! $jobId && ! $jobInfo) {
                return 0;
            }
            if (! $jobInfo) {
                $jobInfo = $this->get(['entid' => $entId, 'id' => $jobId], ['id', 'job_count']);
                if (! $jobInfo) {
                    throw $this->exception('职位不存在');
                }
            }
            $count = app()->get(AdminService::class)->count(['job' => $jobId, 'status' => 1]);
            if ($jobInfo->job_count == $count) {
                return $count;
            }
            $jobInfo->job_count = $count;
            $jobInfo->save();
            return $count;
        });
    }
}
