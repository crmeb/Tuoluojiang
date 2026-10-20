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

use App\Http\Dao\User\UserEducationHistoryDao;
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
 * 教育经历
 * Class UserEducationHistoryService.
 */
class UserEducationHistoryService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    protected int $resumeId;

    protected FormService $build;

    public function __construct(UserEducationHistoryDao $dao, FormService $build)
    {
        $this->dao   = $dao;
        $this->build = $build;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setResumeId(): void
    {
        $this->resumeId = app()->get(UserResumeService::class)->value(['uid' => $this->uuId(false)], 'id');
    }

    /**
     * 查询.
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
     * 获取创建教育经历表单.
     */
    public function resourceCreate(array $other = []): array
    {
        $this->setResumeId();

        return $this->createElementForm('添加教育经历', $this->createEducationRule(collect()), '/ent/user/education?resume_id=' . $this->resumeId);
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
        if ($data['start_time']) {
            $data['start_time'] = Carbon::make($data['start_time'])->toDateString();
        }
        if ($data['end_time']) {
            $data['end_time'] = Carbon::make($data['end_time'])->toDateString();
        }

        return $this->dao->create($data);
    }

    /**
     * 编辑教育经历获取表单.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if (! ($info = $this->dao->get($id))) {
            throw $this->exception('修改的信息不存在');
        }

        return $this->createElementForm('修改教育经历', $this->createEducationRule(collect($info->toArray())), '/ent/user/education/' . $id, 'put');
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
     * 创建学历表单规则.
     *
     * @return array
     */
    protected function createEducationRule(Collection $info)
    {
        $this->setResumeId();

        return [
            $this->build->hidden('resume_id', $this->resumeId),
            $this->build->date('start_time', '入学时间', $info->get('start_time', ''))
                ->validate([$this->build->validateStr()->required()->message('请选择入学时间')]),
            $this->build->date('end_time', '毕业时间', $info->get('end_time', ''))
                ->validate([$this->build->validateStr()->required()->message('请选择毕业时间')]),
            $this->build->input('school_name', '学校名称', $info->get('school_name'))->required(),
            $this->build->input('major', '所学专业', $info->get('major'))->required(),
            $this->build->input('education', '学历', $info->get('education'))->required()->maxlength(15)->showWordLimit(true),
            $this->build->input('academic', '学位', $info->get('academic')),
            $this->build->textarea('remark', '备注', $info->get('remark')),
        ];
    }
}
