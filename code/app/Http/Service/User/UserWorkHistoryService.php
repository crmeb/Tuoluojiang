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

use App\Http\Dao\User\UserWorkHistoryDao;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 工作经历.
 */
class UserWorkHistoryService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * 名片ID.
     */
    protected int $resumeId;

    protected FormService $build;

    public function __construct(UserWorkHistoryDao $dao, FormService $build)
    {
        $this->dao   = $dao;
        $this->build = $build;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setResumeId()
    {
        $this->resumeId = app()->get(UserResumeService::class)->value(['uid' => $this->uuId(false)], 'id');
    }

    /**
     * 查询数据.
     *
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $this->setResumeId();
        $where['resume_id'] = $this->resumeId;

        return parent::getList($where, $field, 'id');
    }

    /**
     * 创建获取工作经历表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加工作经历', $this->createWorkRule(collect()), '/ent/user/work');
    }

    /**
     * 创建数据.
     *
     * @return mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if (! app()->get(UserResumeService::class)->exists($data['resume_id'])) {
            throw $this->exception('个人简历不存在');
        }

        return $this->dao->create($data);
    }

    /**
     * 修改获取工作经历表单.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if (! ($info = $this->dao->get($id))) {
            throw $this->exception('修改的工作经历不存在');
        }

        return $this->createElementForm('修改工作经历', $this->createWorkRule(collect($info->toArray())), '/ent/user/work/' . $id, 'put');
    }

    public function resourceUpdate($id, array $data)
    {
        if (! ($info = $this->dao->get($id))) {
            throw $this->exception('修改的信息不存在');
        }
        if ($data['start_time']) {
            $data['start_time'] = Carbon::make($data['start_time'])->toDateString();
        }
        if ($data['end_time']) {
            $data['end_time'] = Carbon::make($data['end_time'])->toDateString();
        }
        $this->dao->update($id, $data);
        return true;
    }

    /**
     * 创建工作经历表单规则.
     *
     * @return array
     */
    protected function createWorkRule(Collection $info)
    {
        $this->setResumeId();

        return [
            $this->build->hidden('resume_id', $this->resumeId),
            $this->build->date('start_time', '开始时间', $info->get('start_time', ''))
                ->validate([$this->build->validateStr()->required()->message('请选择开始时间')]),
            $this->build->date('end_time', '结束时间', $info->get('end_time', ''))
                ->validate([$this->build->validateStr()->required()->message('请选择结束时间')]),
            $this->build->input('company', '所在公司', $info->get('company'))->required(),
            $this->build->input('position', '职位', $info->get('position'))->required(),
            $this->build->textarea('describe', '工作描述', $info->get('describe'))->required(),
            $this->build->textarea('quit_reason', '离职原因', $info->get('quit_reason')),
        ];
    }
}
