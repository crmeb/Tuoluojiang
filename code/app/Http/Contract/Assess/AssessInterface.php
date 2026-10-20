<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Contract\Assess;

interface AssessInterface
{
    /**
     * 获取列表.
     * @return mixed
     */
    public function getAssessList(int $uid, array $where = [], int $types = 0);

    /**
     * 查看详情.
     * @param mixed $id
     * @return mixed
     */
    public function getAssessInfo($id);

    /**
     * 创建考核.
     * @return mixed
     */
    public function createAssess(int $uid, array $data, int $entId = 1);

    /**
     * 自我评价.
     * @return mixed
     */
    public function setSelfAssess(int $id, array $data);

    /**
     * 上级评价.
     * @return mixed
     */
    public function setSuperiorAssess(int $id, int $uid, array $data, int $entId, bool $isSubmit = false);

    /**
     * 绩效审核.
     * @return mixed
     */
    public function setExamineAssess(int $id, int $uid, array $data, int $entId, bool $isSubmit = false);

    /**
     * 获取评分记录.
     * @param mixed $id
     * @return mixed
     */
    public function getAssessScore($id);
}
