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

namespace App\Http\Service\Assess;

use App\Http\Dao\Access\AssessSpaceDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考核内容维度
 * Class AssessSpaceService.
 */
class AssessSpaceService extends BaseService
{
    private $targetFieldSelf = [
        'finish_info',
        'finish_ratio',
    ];

    private $spaceField = [
        'name',
        'ratio',
    ];

    private $targetFieldSuperior = [
        'ratio',
        'name',
        'content',
        'info',
        'check_info',
        'max',
        'score',
    ];

    /**
     * AssessSpaceService constructor.
     */
    public function __construct(AssessSpaceDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 绩效考核详情.
     * @param array|string[] $field
     * @return Collection
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getAssess(int $assessid, array $field = ['*'], array $with = [])
    {
        return $this->dao->select(['assessid' => $assessid], $field, $with + ['target']);
    }

    /**
     * 绩效考核详情.
     * @param array|string[] $field
     * @return Collection
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getAssessTemp(int $targetid, array $field = ['*'], array $with = [])
    {
        return $this->dao->select(['targetid' => $targetid], $field, $with + ['target']);
    }

    /**
     * TODO 保存考核目标详情.
     *
     * @param int $is_temp
     * @param mixed $is_draft
     * @param mixed $data
     * @param mixed $id
     * @param mixed $entid
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function saveAssessInfo($data, $id, $entid, $is_temp = 0, $is_draft = 1)
    {
        if (! $data || ! count($data) || ! count($data[0]['target'])) {
            throw $this->exception('内容不能为空');
        }

        if (! $is_draft) {
            if ($this->getComputeMode()) {
                $maxScore = $this->getMaxScore();
                if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                    throw $this->exception('维度总分必须为' . $maxScore . '分');
                }
            } else {
                if (array_sum(array_column($data, 'ratio')) != 100) {
                    throw $this->exception('维度权重总占比必须为100%');
                }
                foreach ($data as $item) {
                    if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                        throw $this->exception('维度中指标权重总占比必须为100%');
                    }
                }
            }
        }
        if ($is_temp) {
            return $this->saveTemp($id, $data, $entid, $is_temp, false);
        }
        return $this->saveInfo($id, $data, $entid, $is_temp, false);
    }

    /**
     * 保存模板
     *
     * @param mixed $id
     * @param mixed $data
     * @param mixed $entId
     * @param mixed $isTemp
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveAssess($id, $data, $entId, $isTemp, bool $transaction = true)
    {
        if (! $data || ! count($data) || ! count($data[0]['target'])) {
            throw $this->exception('内容不能为空');
        }
        if (sys_config('assess_compute_mode')) {
            $maxScore = (int) app()->get(AssessScoreService::class)->max([], 'max');
            if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                throw $this->exception('维度总分必须为' . $maxScore . '分');
            }
            foreach ($data as $item) {
                if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                    throw $this->exception('维度中指标总分必须为' . $item['ratio'] . '分');
                }
            }
        } else {
            if (array_sum(array_column($data, 'ratio')) != 100) {
                throw $this->exception('维度权重总占比必须为100%');
            }
            foreach ($data as $item) {
                if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                    throw $this->exception('维度中指标权重总占比必须为100%');
                }
            }
        }
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $this->dao->column(['assessid' => $id], 'id');
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?? [];

        return $this->transaction(function () use ($targetService, $data, $entId, $id, $spaceIds, $targetIds, $isTemp) {
            if ($spaceIds) {
                $this->dao->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            foreach ($data as $item) {
                $space['entid'] = $entId;
                if ($isTemp) {
                    $space['targetid'] = $id;
                } else {
                    $space['assessid'] = $id;
                }
                $space['name']  = $item['name'];
                $space['ratio'] = $item['ratio'];
                $res            = $this->dao->create($space);
                foreach ($item['target'] as $value) {
                    $target['spaceid'] = $res->id;
                    $target['ratio']   = $value['ratio'];
                    $target['name']    = $value['name'];
                    $target['content'] = $value['content'];
                    $target['info']    = $value['info'];
                    $targetService->create($target);
                }
                unset($res);
            }
            return true;
        }, $transaction);
    }

    /**
     * 更新自评.
     *
     * @param mixed $data
     * @return mixed
     * @throws BindingResolutionException
     */
    public function updateSelfAssess($data)
    {
        if (! $data || ! count($data) || ! count($data[0]['target'])) {
            throw $this->exception('内容不能为空');
        }
        $targetService = app()->get(AssessTargetService::class);
        return $this->transaction(function () use ($targetService, $data) {
            foreach ($data as $item) {
                foreach ($item['target'] as $value) {
                    $targetService->update($value['id'], getFieldData($value, $this->targetFieldSelf));
                }
            }
            return true;
        });
    }

    /**
     * 更新上级评价.
     *
     * @param mixed $id
     * @param mixed $entId
     * @param mixed $data
     * @return mixed
     * @throws BindingResolutionException
     */
    public function updateSuperAssess($id, $data, $entId = 1)
    {
        if (! $data || ! count($data) || ! count($data[0]['target'])) {
            throw $this->exception('内容不能为空');
        }
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $this->dao->column(['assessid' => $id], 'id');
        $newSpaceId    = array_column($data, 'id');
        $delSpaceId    = array_diff($spaceIds, $newSpaceId);
        $delTargetId   = $targetService->column(['spaceid' => $delSpaceId], 'id');
        return $this->transaction(function () use ($targetService, $data, $delSpaceId, $delTargetId, $id, $entId) {
            $this->dao->delete($delSpaceId, 'id');
            $targetService->delete($delTargetId, 'id');
            foreach ($data as $index => $item) {
                $space         = getFieldData($item, $this->spaceField);
                $space['sort'] = $index + 1;
                if ($item['id']) {
                    $this->dao->updateOrCreate(['id' => $item['id']], $space);
                    $spaceId = $item['id'];
                } else {
                    $space['entid']    = $entId;
                    $space['assessid'] = $id;
                    $saved             = $this->dao->create($space);
                    $spaceId           = $saved->id;
                }
                $newTargetId = array_column($item['target'], 'id');
                $targetService->delete(['not_id' => $newTargetId, 'spaceid' => $spaceId]);
                foreach ($item['target'] as $key => $value) {
                    $target         = getFieldData($value, $this->targetFieldSuperior);
                    $target['sort'] = $key + 1;
                    if ($value['id']) {
                        $targetService->updateOrCreate(['id' => $value['id']], $target);
                    } else {
                        $target['spaceid'] = $spaceId;
                        $targetService->create($target);
                    }
                }
            }
            return true;
        });
    }
}
