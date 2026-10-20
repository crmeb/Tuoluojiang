<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------.
 */

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

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudForm;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class SystemCrudFormDao.
 * @email 136327134@qq.com
 * @date 2024/2/26
 */
class SystemCrudFormDao extends BaseDao
{
    use BatchSearchTrait;
    use TogetherSearchTrait;
    use ListSearchTrait;

    /**
     * 获取当前住配置之外的公共数据.
     * @return null|mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    public function getGlobalOptions(int $crudId)
    {
        return $this->getModel()
            ->orderByDesc('id')
            ->where('crud_id', $crudId)
            ->where('is_index', 0)->value('global_options');
    }

    /**
     * 更新当前主配置.
     * @return bool
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    public function updateIndex(int $crudId, int $id)
    {
        return $this->getModel()
            ->where('crud_id', $crudId)
            ->where('id', '<>', $id)->update(['is_index' => 0]);
    }

    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    protected function setModel()
    {
        return SystemCrudForm::class;
    }
}
