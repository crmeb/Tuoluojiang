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

namespace App\Http\Service\Assess;

use App\Constants\CacheEnum;
use App\Http\Dao\Access\EnterpriseTargetDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\services\synchro\Company;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考核指标service
 * Class AssessTargetService.
 */
class AssessTargetService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * AssessTargetService constructor.
     */
    public function __construct(EnterpriseTargetDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['id', 'name', 'content', 'info', 'status', 'updated_at', 'cate_id'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        return Cache::tags([CacheEnum::TAG_ASSESS])->remember(md5(json_encode($where) . $page . $limit), 60, function () use ($where, $page, $limit, $field) {
            return app()->get(Company::class)->assessTarget($where, $page, $limit, $field);
        });
    }

    /**
     * 创建指标模板获取表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加指标模板', $this->getRankRuleForm(collect()), '/admin/enterprise/assess/target');
    }

    /**
     * 修改指标模板获取表单信息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankInfo = $this->dao->get($id);
        if (! $rankInfo) {
            throw $this->exception('修改的指标模板不存在');
        }
        return $this->createElementForm('添加指标模板', $this->getRankRuleForm(collect($rankInfo->toArray())), '/admin/enterprise/assess/target/' . $id, 'put');
    }

    /**
     * 修改指标模板
     * @param int $id
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $this->dao->update($id, $data);
        return true;
    }

    /**
     * 保存指标模板
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $this->dao->create($data);
        return true;
    }

    /**
     * 考核指标自评.
     * @param mixed $assessId
     * @param mixed $targetId
     * @param mixed $spaceId
     * @param mixed $data
     * @param mixed $uid
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function selfEvalTarget($assessId, $targetId, $spaceId, $data, $uid)
    {
        $info = toArray(app()->get(AssessService::class)->get($assessId));
        if (empty($info)) {
            throw $this->exception('未找到相关考核信息');
        }
        if ($info['test_uid'] != $uid) {
            throw $this->exception('您暂无权限修改该考核信息');
        }
        $space = toArray(app()->get(AssessSpaceService::class)->get(['id' => $spaceId, 'assessid' => $assessId]));
        if (empty($space)) {
            throw $this->exception('未找到相关考核信息');
        }
        if ($this->dao->value($targetId, 'spaceid') != $space['id']) {
            throw $this->exception('无效的考核信息');
        }
        return $this->dao->update($targetId, $data);
    }

    /**
     * 获取指标模板表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getRankRuleForm(Collection $collection)
    {
        $cateService = app()->get(AssessTargetCateService::class);
        return [
            Form::cascader('cate_id', '模板分类')
                ->options($cateService->getTargetCateTree(['types' => 0]))->appendRule('value', (int) $collection->get('cate_id', 0))
                ->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])
                ->validate([Form::validateNum()->required()->message('请选择模板分类')]),
            Form::input('name', '模板名称', $collection->get('name')),
            Form::textarea('content', '模板简介', $collection->get('content'))->placeholder('模板简介')->rows(3),
            Form::radio('status', '状态', (int) ($collection->get('status') ?? 1))->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]),
        ];
    }
}
