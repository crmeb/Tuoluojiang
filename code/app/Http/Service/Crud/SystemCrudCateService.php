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

use App\Http\Dao\Crud\SystemCrudCateDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * 实体分类
 * Class SystemCrudCateService.
 * @email 136327134@qq.com
 * @date 2024/2/29
 */
class SystemCrudCateService extends BaseService
{
    /**
     * SystemCrudCateService constructor.
     */
    public function __construct(SystemCrudCateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取分类名称.
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/16
     */
    public function idsByNameColumn(array $ids)
    {
        $list = $this->dao->idsByName($ids);
        $column = [];
        foreach ($list as $item) {
            $column[$item['id']] = $item['name'];
        }
        return $column;
    }

    /**
     * 获取分类列表.
     * @param array $where
     * @param array $field
     * @param $sort
     * @param array $with
     * @return array
     * @throws BindingResolutionException
     */
    public function getCateList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $data = $this->getList($where, $field, $sort, $with);

        foreach ($data['list'] as $key => $item) {
            $data['list'][$key]['crud_count'] = Cache::tags(SystemCrudService::TAG_NAME)->remember('crud_cate_count_' . $item['id'], 3600, function () use ($item) {
                return app()->make(SystemCrudService::class)->getSearchModel(['cate_id' => $item['id']])->count();
            });
            $data['list'][$key]['menu']['path'] = $item['menu']['paths'] ?? [];
            $data['list'][$key]['menu']['icon'] = $item['menu']['icon'] ?? '';
        }

        return $data;
    }
}
