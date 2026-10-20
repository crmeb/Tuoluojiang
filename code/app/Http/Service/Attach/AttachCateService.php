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

namespace App\Http\Service\Attach;

use App\Http\Dao\Category\CategoryDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\CategoryTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class AttachCateService extends BaseService implements ResourceServicesInterface
{
    use CategoryTrait;

    /**
     * @var string
     */
    protected $types = 'systemAttach';

    /**
     * @var string
     */
    protected $level = 1;

    /**
     * 保存/修改地址
     * @var string
     */
    protected $saveUrl = '/ent/system/attach_cate';

    public function __construct(CategoryDao $dao)
    {
        $this->dao = $dao->setDefaultWhere(['type' => $this->getType()]);
    }

    /**
     * 获取修改表单数据.
     * @throws BindingResolutionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $data = $this->dao->get($id);
        if (! $data) {
            throw $this->exception('没有查询到数据！');
        }
        return $this->createElementForm('修改分类', $this->createFormRule($data->toArray()), $this->saveUrl . '/' . $id, 'PUT', []);
    }

    /**
     * 获取添加表单数据.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('创建分类', $this->createFormRule(), $this->saveUrl);
    }

    /**
     * 删除数据.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(AttachService::class)->count(['cid' => $id])) {
            throw $this->exception('删除失败，该分类下有内容！');
        }
        return $this->delete($id, $key);
    }

    /**
     * 分类树状结构.
     * @param array|string[] $field
     * @param null|string $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = []): array
    {
        $where1['entid'] = 1;
        $where1['type']  = $this->getType();
        return get_tree_children($this->dao->getList($where1, $field, 0, 0, $sort));
    }

    /**
     * 分类类型.
     */
    protected function getType(): string
    {
        return $this->types;
    }

    /**
     * 分类级别.
     */
    protected function getLevel(): int
    {
        return $this->level;
    }

    /**
     * 企业ID.
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
        $where = [
            'entid'    => 1,
            'type'     => $this->getType(),
            'lt_level' => $this->getLevel(),
        ];
        return [
            $this->getLevel() == 1 ?
                Form::select($this->tableField['pid'], '父级分类', $data[$this->tableField['pid']] ?? 0)
                    ->options(array_merge([['value' => 0, 'label' => '顶级分类']], $this->menus($where)))
                :
                Form::cascader($this->tableField['path'], '父级分类')
                    ->options($this->menus($where))
                    ->value($data[$this->tableField['path']] ?? [])
                    ->props(['props' => ['checkStrictly' => true]]),
            Form::input($this->tableField['cate_name'], '分类名称', $data[$this->tableField['cate_name']] ?? '')->maxlength(20)->showWordLimit(true)->required(),
            Form::hidden($this->tableField['type'], $this->getType()),
            Form::number($this->tableField['sort'], '排序', $data[$this->tableField['sort']] ?? 0)->min(0)->max(999999)->precision(0),
            //            Form::switches($this->tableField['is_show'], '状态', $data[$this->tableField['is_show']] ?? 1)->inactiveValue(0)->activeValue(1)->inactiveText('关闭')->activeText('开启'),
        ];
    }
}
