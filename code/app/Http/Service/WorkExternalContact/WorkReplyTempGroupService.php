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

use App\Http\Dao\WorkExternalContact\WorkReplyTempGroupDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Support\Collection;

/**
 * 快捷回复分组.
 */
class WorkReplyTempGroupService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    protected string $saveUrl = '/ent/work/reply_temp_group';

    public function __construct(WorkReplyTempGroupDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加分组', $this->getFormRule(collect($other)), $this->saveUrl);
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $data = $this->dao->get($id);
        if (! $data) {
            throw $this->exception('编辑的分组不存在');
        }
        return $this->createElementForm('编辑分组', $this->getFormRule(collect($data)), $this->saveUrl . '/' . $id, 'PUT');
    }

    private function getFormRule(Collection $collect)
    {
        return [
            FormService::input('name', '分组名称', $collect->get('name', ''))->required(),
            FormService::number('sort', '排序', $collect->get('sort', 0))->min(0)->max(9999999)->required(),
        ];
    }
}
