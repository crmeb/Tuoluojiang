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

namespace App\Http\Service\Notice;

use App\Http\Dao\Category\CategoryDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\CategoryTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class NoticeCateService extends BaseService implements ResourceServicesInterface
{
    use CategoryTrait;

    /**
     * NoticeCateService constructor.
     * @param array $showIds
     */
    public function __construct(CategoryDao $dao, protected $showIds = [])
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
        return $this->createElementForm('修改通知分类', $this->createFormRule($data->toArray()), '/ent/notice/category/' . $id, 'PUT');
    }

    /**
     * 获取添加表单数据.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('创建通知分类', $this->createFormRule(), '/ent/notice/category');
    }

    /**
     * 删除.
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(NoticeService::class)->count(['cate_id' => $id])) {
            throw $this->exception('该类别下存在公告信息，不可删除');
        }
        return $this->dao->delete($id, $key);
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
        $where['type'] = $this->getType();
        return get_tree_children($this->dao->getList($where, $field, 0, 0, $sort));
    }

    /**
     * 设置分类类型.
     */
    protected function getType(): string
    {
        return 'enterprseNotice';
    }

    /**
     * 分类层级.
     */
    protected function getLevel(): int
    {
        return 1;
    }

    /**
     * 获取表单数据.
     */
    protected function createFormRule(array $data = []): array
    {
        return [
            Form::input($this->tableField['cate_name'], '分类名称', $data[$this->tableField['cate_name']] ?? '')->required()->maxlength(20)->showWordLimit(true),
            Form::hidden($this->tableField['type'], $this->getType()),
            Form::number($this->tableField['sort'], '排序', $data[$this->tableField['sort']] ?? 0)->min(0)->max(999999)->precision(0),
            Form::switches($this->tableField['is_show'], '状态', $data[$this->tableField['is_show']] ?? 1)->inactiveValue(0)->activeValue(1)->inactiveText('关闭')->activeText('开启'),
        ];
    }
}
