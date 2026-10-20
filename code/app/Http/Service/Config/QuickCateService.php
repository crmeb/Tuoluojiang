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

namespace App\Http\Service\Config;

use App\Http\Dao\Category\CategoryDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\CategoryTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 快捷入口配置分类.
 */
class QuickCateService extends BaseService implements ResourceServicesInterface
{
    use CategoryTrait;

    public function __construct(CategoryDao $dao)
    {
        $this->dao = $dao;
        $this->setMaxLevel(1);
    }

    /**
     * 获取修改表单数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if (! ($data = $this->dao->get($id))) {
            throw $this->exception('无效参数！');
        }
        return $this->createElementForm('修改配置分类', $this->createFormRule($data->toArray()), '/ent/config/quickCate/' . $id, 'PUT');
    }

    /**
     * 获取添加表单数据.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('创建配置分类', $this->createFormRule(), '/ent/config/quickCate');
    }

    /**
     * 分级排序列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTierList(array $where, array $field = ['*']): array
    {
        return get_tree_children($this->dao->getTierList($where, $field), 'children', 'value');
    }

    /**
     * 设置分类类型.
     */
    protected function getType(): string
    {
        return 'quickConfig';
    }

    /**
     * 分类层级.
     */
    protected function getLevel(): int
    {
        return 1;
    }

    /**
     * 分类层级.
     */
    protected function getEntId(): int
    {
        return 1;
    }

    /**
     * 获取表单数据.
     */
    protected function createFormRule(array $data = []): array
    {
        return [
            Form::hidden($this->tableField['pid'], 0),
            Form::input($this->tableField['cate_name'], '分类名称', $data[$this->tableField['cate_name']] ?? '')->maxlength(20)->required(),
            Form::hidden($this->tableField['type'], $this->getType()),
            Form::number($this->tableField['sort'], '排序', $data[$this->tableField['sort']] ?? 0)->min(0)->max(999999)->precision(0),
            //            Form::switches($this->tableField['is_show'], '状态', $data[$this->tableField['is_show']] ?? 1)->inactiveValue(0)->activeValue(1)->inactiveText('关闭')->activeText('开启'),
        ];
    }
}
