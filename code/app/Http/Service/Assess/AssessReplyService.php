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

use App\Http\Dao\Access\AssessReplyDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 考核评价表
 * Class AssessReplyService.
 */
class AssessReplyService extends BaseService
{
    /**
     * AssessReplyService constructor.
     */
    public function __construct(AssessReplyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建或修改信息.
     * @return BaseModel|int|Model
     * @throws BindingResolutionException
     */
    public function createOrUpdate(array $data)
    {
        if ($this->dao->exists(['assessid' => $data['assessid'], 'types' => $data['types'], 'is_own' => $data['is_own'], 'user_id' => $data['user_id']])) {
            return $this->dao->update(['assessid' => $data['assessid'], 'types' => $data['types'], 'is_own' => $data['is_own'], 'user_id' => $data['user_id']], $data);
        }
        return $this->dao->create($data);
    }
}
