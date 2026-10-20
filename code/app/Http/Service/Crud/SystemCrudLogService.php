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

use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudLogTypeEnum;
use App\Http\Dao\Crud\SystemCrudLogDao;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 日志.
 */
class SystemCrudLogService extends BaseService
{
    public function __construct(SystemCrudLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function getLogList(SystemCrud $systemCrud, int $id)
    {
        $data = $this->getList(where: ['crud_id' => $systemCrud->id, 'data_id' => $id], sort: 'id', with: ['user' => fn ($q) => $q->select('id', 'name', 'avatar')]);

        $moduleService    = app()->get(CrudModuleService::class);
        $crudService      = app()->get(SystemCrudService::class);
        $adminService     = app()->get(AdminService::class);
        $frameService     = app()->get(FrameService::class);
        $crudFieldService = app()->get(SystemCrudFieldService::class);
        foreach ($data['list'] as &$item) {
            $item['field_name'] = '';
            $item['share_user'] = [
                'name' => '',
                'id'   => 0,
            ];
            switch ($item['log_type']) {
                case CrudLogTypeEnum::SHARE_CREATE:
                    $item['before_value'] = $adminService->value(['id' => $item['before_value']], 'name');
                    break;
                case CrudLogTypeEnum::SHARE_DELETE_TYPE:
                    $item['after_value'] = $adminService->value(['id' => $item['after_value']], 'name');
                    break;
                case CrudLogTypeEnum::SHARE_UPDATE_TYPE:
                    $item['share_user'] = $adminService->get(['id' => (int) $item['change_field_name_en']], ['name', 'id'])?->toArray();
                    break;
                case CrudLogTypeEnum::TRANSFER_TYPE:
                    $item['before_value'] = $adminService->value(['id' => $item['before_value']], 'name');
                    $item['after_value']  = $adminService->value(['id' => $item['after_value']], 'name');
                    break;
                default:
                    if ($item['change_field_name_en']) {
                        $fieldInfo = $crudFieldService->get(['crud_id' => $item['data_crud_id'], 'field_name_en' => $item['change_field_name_en']], ['field_name', 'form_value', 'data_dict_id', 'association_crud_id', 'customize_items', 'data_type']);
                        if (! $fieldInfo) {
                            break;
                        }

                        $item['form_value'] = $fieldInfo['form_value'];
                        $item['field_name'] = $fieldInfo['field_name'];

                        // 查找一对一关联的数据
                        if ($item['form_value'] === CrudFormEnum::FORM_INPUT_SELECT) {
                            if (! $fieldInfo->association_crud_id) {
                                break;
                            }
                            $associationTableName = $crudService->value($fieldInfo->association_crud_id, 'table_name_en');
                            if (! $associationTableName) {
                                break;
                            }
                            $fieldName = $crudFieldService->value(['crud_id' => $fieldInfo->association_crud_id, 'is_main' => 1], 'field_name_en');
                            if (! $fieldName) {
                                break;
                            }
                            $item['before_value'] = $moduleService->model(tableName: $associationTableName)->value(['id' => $item['before_value']], $fieldName);
                            $item['after_value']  = $moduleService->model(tableName: $associationTableName)->value(['id' => $item['after_value']], $fieldName);
                            break;
                        }

                        // 查找其他数据
                        switch ($item['change_field_name_en']) {
                            case 'user_id':
                            case 'update_user_id':
                            case 'owner_user_id':
                                $item['before_value'] = $adminService->value(['id' => $item['before_value']], 'name');
                                $item['after_value']  = $adminService->value(['id' => $item['after_value']], 'name');
                                break;
                            case 'frame_id':
                                $item['before_value'] = $frameService->value(['id' => $item['before_value']], 'name');
                                $item['after_value']  = $frameService->value(['id' => $item['after_value']], 'name');
                                break;
                            default:
                                $before = $moduleService->getDataAttrValue(
                                    value: [$item['change_field_name_en'] => $item['before_value']],
                                    column: [
                                        $item['change_field_name_en'] => [
                                            'data_dict_id'    => $fieldInfo['data_dict_id'],
                                            'form_value'      => $fieldInfo['form_value'],
                                            'customize_items' => $fieldInfo['customize_items'],
                                            'data_type'       => $fieldInfo['data_type'],
                                        ],
                                    ]
                                );
                                $item['before_value'] = $before[$item['change_field_name_en']];
                                $after                = $moduleService->getDataAttrValue(
                                    value: [$item['change_field_name_en'] => $item['after_value']],
                                    column: [
                                        $item['change_field_name_en'] => [
                                            'data_dict_id'    => $fieldInfo['data_dict_id'],
                                            'form_value'      => $fieldInfo['form_value'],
                                            'customize_items' => $fieldInfo['customize_items'],
                                            'data_type'       => $fieldInfo['data_type'],
                                        ],
                                    ]
                                );
                                $item['after_value'] = $after[$item['change_field_name_en']];
                                break;
                        }
                    }
                    break;
            }
        }

        return $data;
    }
}
