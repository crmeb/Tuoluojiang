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

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudSeniorSearchDao;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

class SystemCrudSeniorSearchService extends BaseService
{
    public function __construct(SystemCrudSeniorSearchDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取视图列表.
     * @return array|mixed|mixed[]
     * @throws BindingResolutionException
     */
    public function getSeniorSearchList(SystemCrud $crud, string $name = '', int $uid = 0)
    {
        $searchList = $this->dao->getModel()
            ->where('crud_id', $crud->id)
            ->when($name !== '', fn ($q) => $q->where('senior_title', 'like', '%' . $name . '%'))
            ->when(
                value: $uid,
                callback: fn ($q) => $q->where(fn ($qq) => $qq->where('user_id', $uid)->orWhere('senior_type', 1)),
                default: fn ($q) => $q->where('senior_type', 1)
            )->orderBy('sort', 'desc')->orderBy('id', 'desc')->get()->toArray();

        $fieldList    = $crud->field->toArray();
        $fields       = array_column($fieldList, 'field_name_en');
        $fieldColumns = [];
        foreach ($fieldList as $field) {
            $fieldColumns[$field['field_name_en']] = $field;
        }
        foreach ($searchList as $index => $item) {
            $item['senior_search'] = is_array($item['senior_search']) ? $item['senior_search'] : [];

            $seniorSearch = [];
            foreach ($item['senior_search'] as $value) {
                if (in_array($value['field'], $fields)) {
                    if (isset($fieldColumns[$value['field']])) {
                        $value['show_type'] = $fieldColumns[$value['field']]['show_type'] ?? 1;
                    }
                    $seniorSearch[] = $value;
                }
            }

            if (count($seniorSearch) != count($item['senior_search'])) {
                $this->dao->update($item['id'], ['senior_search' => $seniorSearch]);
            }

            $searchList[$index]['senior_search'] = $seniorSearch;
        }
        return $searchList;
    }
}
