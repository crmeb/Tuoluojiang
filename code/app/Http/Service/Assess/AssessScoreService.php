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

use App\Http\Dao\Access\AssessScoreDao;
use App\Http\Dao\BaseDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 绩效考核分数
 * Class AssessScoreService.
 * @method int max($where, string $field) 获取最大值
 * @method BaseDao setDefaultWhere(array $where)
 */
class AssessScoreService extends BaseService
{
    public $dao;

    /**
     * AssessScoreService constructor.
     */
    public function __construct(AssessScoreDao $dao)
    {
        $this->dao = $dao;
    }

    public function getScoreLevel($entid, $score)
    {
        return $this->dao->value(['entid' => $entid, 'score' => $score], 'level');
    }

    /**
     * 批量修改或者更新.
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function bachAddOrSave(int $entid, array $data)
    {
        $this->dao->delete(['entid' => $entid]);
        return $this->dao->insert($data);
    }

    /**
     * 删除绩效考核方案被考核人.
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteSchemeUser(int $schemId)
    {
        return $this->dao->delete(['scheme_id' => $schemId]);
    }

    /**
     * 获取和绩效方案绑定的被考核人.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSchemeUser(int $schemId)
    {
        return $this->dao->column(['scheme_id' => $schemId], 'user_id');
    }

    /**
     * 查询关联用户.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSchemeUserList(int $schemId, array $with = [])
    {
        return $this->dao->getList(['scheme_id' => $schemId], ['*'], 0, 0, 'id', $with);
    }

    /**
     * 考核方案id获取考核人员id.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSchemeUserIds(array $schemIds)
    {
        return $this->dao->column(['scheme_ids' => $schemIds], 'user_id');
    }

    /**
     * 获取绩效总得分.
     * @param int $id 绩效考核ID
     * @return null|string
     * @throws BindingResolutionException
     */
    public function getScoreFromId(int $id)
    {
        $assessService = app()->get(AssessService::class);
        $spaceService  = app()->get(AssessSpaceService::class);
        if (! $assessService->exists($id)) {
            throw $this->exception('未找到相关考核信息');
        }
        $types = $assessService->value($id, 'types');
        $total = 0;
        if ($types) {
            $spaceIds = $spaceService->column(['assessid' => $id], 'id');
            $scores   = app()->get(AssessTargetService::class)->column(['spaceid' => $spaceIds], 'score');
            $total    = bcadd((string) $total, (string) array_sum($scores), 2);
        } else {
            $spaces = toArray($spaceService->select(['assessid' => $id], ['id', 'ratio'], [
                'target' => fn ($query) => $query->select(['ratio', 'spaceid', 'score']),
            ]));
            foreach ($spaces as $space) {
                $score = 0;
                foreach ($space['target'] as $target) {
                    $score = bcadd((string) $score, bcdiv(bcmul((string) $target['ratio'], (string) $target['score'], 2), '100', 2), 2);
                }
                $total = bcadd((string) $total, bcdiv(bcmul((string) $space['ratio'], (string) $score, 2), '100', 2), 2);
            }
        }
        return floatval($total);
    }

    /**
     * 评分等级列表.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getScoreList(int $entId = 1): array
    {
        $scores    = $this->dao->column(['entid' => $entId], ['name', 'level']) ?: [];
        $scoreList = [];
        if ($scores) {
            foreach ($scores as $score) {
                $scoreList[$score['level']] = $score['name'];
            }
        }
        return $scoreList;
    }
}
