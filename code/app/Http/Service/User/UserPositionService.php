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

namespace App\Http\Service\User;

use App\Http\Model\User\UserPositionDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * 任职经历
 * Class UserPositionService.
 */
class UserPositionService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * 名片ID.
     * @var int
     */
    protected $cardId;

    /**
     * @var FormService
     */
    protected $build;

    /**
     * UserPositionService constructor.
     */
    public function __construct(UserPositionDao $dao, FormService $service)
    {
        $this->dao   = $dao;
        $this->build = $service;
    }

    /**
     * 设置名片ID.
     */
    public function setCardId(int $cardId): void
    {
        $this->cardId = $cardId;
    }

    /**
     * 查询.
     * @param array|string[] $field
     * @param null|string $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        return parent::getList($where, $field, 'id');
    }

    /**
     * 获取修改任职经历表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if (! ($info = $this->dao->get($id))) {
            throw $this->exception('修改的信息不存在');
        }

        return $this->createElementForm('修改任职经历', $this->createPositionRule(collect($info->toArray())), '/ent/enterprise/position?card_id=' . $this->cardId, 'PUT');
    }

    /**
     * 获取添加任职经历表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加任职经历', $this->createPositionRule(collect()), '/ent/enterprise/position?card_id=' . $this->cardId);
    }

    /**
     * 创建数据.
     * @return mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        return $this->dao->create($data);
    }

    /**
     * 创建任职表单规则.
     * @return array
     */
    protected function createPositionRule(Collection $info)
    {
        return [
            $this->build->hidden('card_id', $this->cardId),
            $this->build->date('start_time', '开始时间', $info->get('start_time', ''))
                ->validate([$this->build->validateStr()->required()->message('请选择开始时间')]),
            $this->build->date('end_time', '结束时间', $info->get('end_time', ''))
                ->validate([$this->build->validateStr()->required()->message('请选择结束时间')]),
            $this->build->input('position', '职位', $info->get('position'))->required(),
            $this->build->input('department', '部门', $info->get('department'))->required(),
            $this->build->textarea('remark', '备注', $info->get('remark')),
            $this->build->radio('is_admin', '身份', $info->get('is_admin', 0))->options([['value' => 1, 'label' => '主管'], ['value' => 0, 'label' => '普通员工']]),
            $this->build->radio('status', '任职状态', $info->get('status', 0))->options([['value' => 1, 'label' => '在职'], ['value' => 0, 'label' => '离职']]),
        ];
    }
}
