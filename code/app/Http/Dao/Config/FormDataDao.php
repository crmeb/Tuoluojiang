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

namespace App\Http\Dao\Config;

use App\Http\Model\Config\FormData;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 自定义表单分组 Dao
 * Class FormDataDao.
 * @method bool insert(array $data) 插入数据
 */
class FormDataDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;
    use BatchSearchTrait;

    /**
     * 获取树状结构.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTreeStructure(array $where, array $field = ['*'], array $with = []): array
    {
        return $this->setDefaultSort('sort')->select($where, $field, $with)->when((bool) $with, function ($query) {
            $query->each(function (&$item) {
                $item['dict'] = $item['dictData'] ? get_tree_children(toArray($item['dictData']), keyName: 'value') : [];
                unset($item['dictData']);
            });
        })?->toArray();
    }

    protected function setModel(): string
    {
        return FormData::class;
    }
}
