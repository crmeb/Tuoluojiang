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

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudTableUserDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 实体列表用户相关配置
 * Class SystemCrudTableUserService.
 * @email 136327134@qq.com
 * @date 2024/3/9
 */
class SystemCrudTableUserService extends BaseService
{
    /**
     * SystemCrudTableUserService constructor.
     */
    public function __construct(SystemCrudTableUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存用户的表格字段展示和自定义搜索信息.
     * @return BaseModel|bool|Model
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    public function saveUserTable(int $crudId, int $uid, array $seniorSearch = [], array $showField = [], array $options = [])
    {
        $userTableInfo = $this->dao->get(['crud_id' => $crudId, 'user_id' => $uid]);
        if ($userTableInfo) {
            if ($seniorSearch) {
                $userTableInfo->senior_search = $seniorSearch;
            }
            if ($showField) {
                $userTableInfo->show_field = $showField;
            }
            if ($options) {
                $dbOptions = $userTableInfo->options;

                $dbOptions['create'] = $options['create'] ?? $dbOptions['create'] ?? [];

                if (isset($options['tab'])) {
                    $dbOptions['tab'] = $options['tab'];
                } else {
                    $dbOptions['tab'] = $dbOptions['tab'] ?? [];
                }
                $userTableInfo->options = $dbOptions;
            }
            $res = $userTableInfo->save();
        } else {
            $res = $this->dao->create([
                'crud_id'       => $crudId,
                'user_id'       => $uid,
                'senior_search' => $seniorSearch,
                'show_field'    => $showField,
                'options'       => $options,
            ]);
        }

        event('system.crud');

        return $res;
    }
}
