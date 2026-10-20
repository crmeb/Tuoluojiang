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

use App\Http\Dao\Company\CompanyUserAssessScoreDao;
use App\Http\Service\BaseService;

/**
 * 评分记录
 * Class UserAssessScoreService.
 */
class UserAssessScoreService extends BaseService
{
    /**
     * UserAssessScoreService constructor.
     */
    public function __construct(CompanyUserAssessScoreDao $dao)
    {
        $this->dao = $dao;
    }

    public function createOrSave($data)
    {
        if (! $this->dao->get($data, ['id'], [], 'id')) {
            $this->dao->create($data);
        }
    }

    /**
     * 评分记录列表.
     * @return array|mixed
     */
    public function getScoreRecord($id)
    {
        $where = [
            'assessid' => $id,
            'types'    => 0,
        ];
        return parent::getList($where, ['id', 'assessid', 'userid', 'score', 'grade', 'total', 'mark', 'created_at'], 'id', ['card']);
    }

    /**
     * 删除记录列表.
     * @param array $where
     * @return array|mixed
     */
    public function getDeleteList($where)
    {
        return parent::getList(
            $where,
            ['id', 'assessid', 'userid', 'check_uid', 'test_uid', 'score', 'grade', 'total', 'mark', 'info', 'created_at'],
            'id',
            ['card', 'check', 'test']
        );
    }
}
