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
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Train\HayGroupService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业职位.
 */
class PositionJobService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

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
    public function getList(array $where, array $field = ['id', 'name', 'cate_id', 'rank_id', 'user_id', 'describe', 'duty'], $sort = null, array $with = []): array
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
        $data['card_id'] = $data['user_id'] = auth('admin')->id();
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
        $this->transaction(function () use ($id, $data) {
            Cache::tags(['Rank'])->flush();
            return $this->dao->update($id, $data);
        });
        return true;
    }

    /**
     * 删除.
     *
     * @param mixed $id
     * @return int|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(PositionRelationService::class)->exists(['rank_id' => $id, 'entid' => 1])) {
            throw $this->exception('请先取消职位体系图关联，再尝试删除');
        }

        if (app()->get(HayGroupService::class)->checkPositionExist((int) $id)) {
            throw $this->exception('请先取消海氏量表职位关联，再尝试删除');
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
                $jobInfo = $this->dao->get(['entid' => $entId, 'id' => $jobId], ['id', 'job_count']);
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

    /**
     * 职位下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSelectList(array $where = [], array $field = ['id', 'name']): array
    {
        return $this->dao->getList($where, $field, 0, 0, 'id');
    }

    /**
     * 获取下级岗位职责.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getSubordinateList(array $where, array $field = ['id', 'uid', 'name', 'job', 'phone']): mixed
    {
        $userId      = uuid_to_uid($this->uuId(false));
        $frameAssist = app()->get(FrameAssistService::class);
        $userService = app()->get(AdminService::class);

        $subIds  = [];
        $frameId = $frameAssist->value(['user_id' => $userId, 'is_mastart' => 1, 'is_admin' => 1], 'frame_id');
        if ($frameId) {
            $subIds = $frameAssist->column(['frame_id' => $frameId, 'is_mastart' => 1, 'is_admin' => 0], 'user_id');
        }
        $subIds         = array_merge($subIds, $frameAssist->column(['superior_uid' => $userId], 'user_id'));
        [$page, $limit] = $this->getPageValue();
        $list           = $userService->listSearch($where, $page, $limit, ['is_admin', 'frame_assist.is_admin', 'id' => 'asc'], [
            'job'       => fn ($query) => $query->select(['id', 'name', 'updated_at']),
            'user_card' => fn ($query) => $query->select(['work_time', 'id']),
            'frames'    => fn ($query) => $query->orderBy('frame_assist.is_admin', 'desc')->orderByDesc('frame_assist.is_mastart')
                ->select(['frame.id', 'frame.name', 'frame_assist.is_mastart', 'frame_assist.is_admin']),
        ])->get()?->toArray();
        foreach ($list as $item) {
            $item['operate'] = in_array($item['id'], $subIds);
        }
        return $this->listData($list, $userService->listSearch($where)->count('admin.id'));
    }

    /**
     * 获取职责数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSubordinateInfo(int $id): array
    {
        $user = toArray(app()->get(AdminService::class)->get($id, ['id', 'name', 'job']));
        if (! $user) {
            throw $this->exception('成员不存在');
        }

        $job = $this->dao->get($user['job'], ['duty']);
        if (! $job) {
            throw $this->exception('岗位职责不存在');
        }
        return ['id' => $user['id'], 'name' => $user['name'], 'duty' => $job['duty']];
    }

    /**
     * 修改下级职责.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function subordinateUpdate(int $id, string $duty): bool
    {
        $user = toArray(app()->get(AdminService::class)->get($id, ['id', 'name', 'avatar', 'job']));
        if (! $user) {
            throw $this->exception('成员不存在');
        }

        $job = $this->dao->get($user['job'], ['id', 'duty']);
        if (! $job) {
            throw $this->exception('修改的岗位职责不存在');
        }
        $job->duty = $duty;
        return $job->save();
    }
}
