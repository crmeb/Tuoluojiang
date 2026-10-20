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

namespace App\Http\Service\WorkExternalContact;

use App\Http\Dao\WorkExternalContact\WorkMassMessagingTempGroupDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Support\Collection;

/**
 * 企微群发消息模板分类.
 */
class WorkMassMessagingTempGroupService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    private string $saveUrl = '/ent/work/mass_messaging_temp_group';

    public function __construct(WorkMassMessagingTempGroupDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加分类', $this->getFormRule(collect($other)), $this->saveUrl);
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $data = $this->dao->get($id);
        if (! $data) {
            throw $this->exception('编辑的分类不存在');
        }
        return $this->createElementForm('编辑分类', $this->getFormRule(collect($data)), $this->saveUrl . '/' . $id, 'PUT');
    }

    private function getFormRule(Collection $collect)
    {
        return [
            FormService::input('name', '分类名称', $collect->get('name', ''))->required(),
            FormService::number('sort', '排序', $collect->get('sort', 0))->min(0)->max(9999999)->required(),
        ];
    }
}
