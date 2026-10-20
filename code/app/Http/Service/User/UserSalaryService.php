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

use App\Http\Model\User\UserSalaryDao;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 薪资变更.
 */
class UserSalaryService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(UserSalaryDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = 'take_date', array $with = []): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        if (! $id) {
            throw $this->exception('缺少必要参数');
        }
        return $this->dao->get($id)->toArray();
    }

    /**
     * 获取调薪记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSalaryList(array $where): array
    {
        $list              = [];
        $crudModuleService = app()->get(CrudModuleService::class);
        $crudInfo          = app()->get(SystemCrudService::class)->getCrudInfoCache('gongzitiaojilu');

        $modelWhere = ['show_search_type' => 0, 'uid' => (int) $where['id'], 'user_id' => $where['user_id']];
        $viewSearch = [['field_name' => 'yuangong', 'operator' => 'in', 'value' => (int) $where['id']]];
        $showField  = $crudModuleService->getShowTableField($crudInfo->id);
        $modelList  = $crudModuleService->getModuleList(request(), $crudInfo, $modelWhere, viewSearch: $viewSearch, viewSearchBoolean: 1);
        foreach ($modelList['list'] as $val) {
            $total     = $id = 0;
            $content   = [];
            $createdAt = '';
            foreach ($showField as $item) {
                $value = $val[$item['field_name_en']] ?? null;
                if ($item['field_name_en'] == 'created_at') {
                    $createdAt = $value;
                }
                if ($item['field_name_en'] == 'id') {
                    $id = $value;
                }

                if (in_array($item['field_name_en'], ['created_at', 'yuangong', 'id'])) {
                    continue;
                }
                $content[] = ['label' => $item['field_name'], 'value' => (string) $value];
                $total     = bcadd((string) $total, (string) floatval($value), 2);
            }
            $list[] = ['id' => $id, 'total' => $total, 'content' => $content, 'created_at' => $createdAt, 'take_date' => Carbon::parse($createdAt)->format('Y-m-d')];
        }
        return $list;
    }
}
