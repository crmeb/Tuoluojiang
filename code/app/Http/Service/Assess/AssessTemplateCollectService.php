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

use App\Http\Dao\Access\TemplateCollectDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 考核模板收藏service.
 */
class AssessTemplateCollectService extends BaseService
{
    /**
     * AssessTemplateCollectService constructor.
     */
    public function __construct(TemplateCollectDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 模板收藏.
     * @return BaseModel|int|Model
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function collectTemp($id, $uid, $entid)
    {
        if ($this->dao->exists(['temp_id' => $id, 'user_id' => $uid, 'entid' => $entid])) {
            return $this->dao->delete(['temp_id' => $id, 'user_id' => $uid, 'entid' => $entid]);
        }
        return $this->dao->create(['temp_id' => $id, 'user_id' => $uid, 'entid' => $entid]);
    }
}
