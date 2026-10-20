<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Open;

use App\Constants\Crud\CrudFormEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Http\Dao\Open\OpenapiRuleDao;
use App\Http\Service\Config\FormService;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudCateService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @mixin OpenapiRuleDao
 */
class OpenapiRuleService extends BaseService
{
    public function __construct(OpenapiRuleDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存某个实体的权限.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveCrudRule(string $name, int $crudId, string $tableName)
    {
        $route = [
            [
                'name'    => '获取列表',
                'crud_id' => $crudId,
                'method'  => 'POST',
                'url'     => 'open/module/' . $tableName . '/list',
                'type'    => 1,
            ],
            [
                'name'    => '保存数据',
                'crud_id' => $crudId,
                'method'  => 'POST',
                'url'     => 'open/module/' . $tableName . '/save',
                'type'    => 1,
            ],
            [
                'name'    => '更新数据',
                'crud_id' => $crudId,
                'method'  => 'PUT',
                'url'     => 'open/module/' . $tableName . '/update/{id}',
                'type'    => 1,
            ],
            [
                'name'    => '获取数据',
                'crud_id' => $crudId,
                'method'  => 'GET',
                'url'     => 'open/module/' . $tableName . '/find/{id}',
                'type'    => 1,
            ],
            [
                'name'    => '删除数据',
                'crud_id' => $crudId,
                'method'  => 'DELETE',
                'url'     => 'open/module/' . $tableName . '/delete/{id}',
                'type'    => 1,
            ],
        ];

        $rule = $this->dao->get(['pid' => 0, 'crud_id' => $crudId, 'type' => 0]);

        return $this->transaction(function () use ($rule, $name, $crudId, $route) {
            if ($rule) {
                $rule->name = $name;
                $rule->save();
            } else {
                $rule = $this->dao->create([
                    'name'    => $name,
                    'crud_id' => $crudId,
                    'type'    => 0,
                ]);
            }

            foreach ($route as $item) {
                $item['pid'] = $rule->id;
                $routeInfo   = $this->dao->get($item);
                if (! $routeInfo) {
                    $this->dao->create($item);
                }
            }
        });
    }

    /**
     * 获取规则.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|\ReflectionException
     */
    public function getRoleTree(array $field = ['id', 'pid', 'name', 'method', 'crud_id', 'url'], string $type = 'rule')
    {
        $list = $this->dao->select([], $field)->toArray();

        $tree = get_tree_children($list);

        $crudIds = [];
        foreach ($tree as $item) {
            if ($item['crud_id']) {
                $crudIds[] = $item['crud_id'];
            }
        }

        $cateList = $noCateIdCrud = [];

        if ($crudIds) {
            $crudList = app()->get(SystemCrudService::class)->crudIdByCateIds($crudIds);
            $cateIds  = [];
            foreach ($crudList as $item) {
                if ($item['cate_ids']) {
                    $cateIds = array_merge($cateIds, $item['cate_ids']);
                } else {
                    $noCateIdCrud[] = $item['id'];
                }

                foreach ($tree as $index => $value) {
                    if ($value['crud_id'] === $item['id']) {
                        $tree[$index]['cate_ids'] = $item['cate_ids'];
                    }
                }
            }

            if ($cateIds) {
                $cateIds  = array_merge(array_unique($cateIds));
                $cateList = app()->get(SystemCrudCateService::class)->idsByNameColumn($cateIds);
            }
        }

        $rule = $systemOpenApi = $notCateCrud = [];

        foreach ($tree as $index => $item) {
            if ($item['crud_id']) {
                if (in_array($item['crud_id'], $noCateIdCrud)) {
                    $notCateCrud[] = $item;
                    unset($tree[$index]);
                }
            } else {
                if ($type != 'doc' && $item['name'] == '对外接口授权') {
                    continue;
                }
                $systemOpenApi[] = $item;
                unset($tree[$index]);
            }
        }

        $tree = array_merge($tree);
        if ($systemOpenApi) {
            if ($type == 'doc') {
                $rule = $systemOpenApi;
            } else {
                $rule[] = [
                    'id'       => 0,
                    'pid'      => 0,
                    'name'     => '系统对外接口',
                    'children' => $systemOpenApi,
                ];
            }
        }
        $cataIdNum = 50000;
        foreach ($cateList as $id => $cata) {
            $ruleItem = [];
            foreach ($tree as $item) {
                if (! empty($item['cate_ids']) && in_array($id, $item['cate_ids'])) {
                    $ruleItem[] = $item;
                }
            }

            if ($ruleItem) {
                $rule[] = [
                    'id'       => $cataIdNum + $id,
                    'pid'      => 0,
                    'name'     => $cata,
                    'children' => $ruleItem,
                ];
            }
        }
        if ($notCateCrud) {
            $rule[] = [
                'id'       => $cataIdNum,
                'pid'      => 0,
                'name'     => '未关联应用实体',
                'children' => $notCateCrud,
            ];
        }

        return $rule;
    }

    /**
     * 获取某个实体的文档内容.
     * @return array
     * @throws BindingResolutionException
     */
    public function getCrudApiDoc(array $crudData)
    {
        $moduleService = app()->get(CrudModuleService::class);
        switch ($crudData['method']) {
            case 'GET':
                $crudData['path_prams'] = [
                    [
                        'name'      => 'id',
                        'form_type' => 'int',
                        'is_must'   => true,
                        'message'   => '自增ID',
                    ],
                ];
                $crudData['response_data'] = [
                    [
                        'name'      => 'data',
                        'form_type' => 'array',
                        'message'   => '数据',
                        'children'  => [
                            [
                                'name'      => 'id',
                                'form_type' => 'int',
                                'message'   => '自增ID',
                            ],
                        ],
                    ],
                    [
                        'name'      => 'status',
                        'form_type' => 'int',
                        'message'   => '状态',
                    ],
                    [
                        'name'      => 'message',
                        'form_type' => 'string',
                        'message'   => '说明',
                    ],
                ];

                $formField = $moduleService->getFormField($crudData['crud_id']);

                foreach ($formField as $item) {
                    if ($item['crud']['id'] == $crudData['crud_id']) {
                        $crudData['response_data'][0]['children'][] = [
                            'name'      => $item['field_name_en'],
                            'form_type' => $this->getFeildType($item['form_value']),
                            'message'   => $item['field_name'],
                        ];
                    }
                }
                break;
            case 'POST':
                if (str_contains($crudData['url'], 'list')) {
                    $crudData['post_prams'] = [
                        [
                            'name'      => 'order_by',
                            'form_type' => 'array',
                            'is_must'   => false,
                            'message'   => '排序',
                        ],
                        [
                            'name'      => 'view_search',
                            'form_type' => 'array',
                            'is_must'   => false,
                            'message'   => '高级搜索',
                        ],
                        [
                            'name'      => 'view_search_boolean',
                            'form_type' => 'string',
                            'is_must'   => false,
                            'message'   => '高级搜索条件',
                        ],
                        [
                            'name'      => 'keyword_default',
                            'form_type' => 'string',
                            'is_must'   => false,
                            'message'   => '默认的字符串搜索',
                        ],
                    ];

                    $crudData['response_data'] = [
                        [
                            'name'      => 'data',
                            'form_type' => 'array',
                            'message'   => '数据',
                            'children'  => [
                                [
                                    'name'      => 'id',
                                    'form_type' => 'int',
                                    'message'   => '自增ID',
                                ],
                            ],
                        ],
                        [
                            'name'      => 'status',
                            'form_type' => 'int',
                            'message'   => '状态',
                        ],
                        [
                            'name'      => 'message',
                            'form_type' => 'string',
                            'message'   => '说明',
                        ],
                    ];

                    $formField = $moduleService->getFormField($crudData['crud_id']);

                    foreach ($formField as $item) {
                        if ($item['crud']['id'] == $crudData['crud_id']) {
                            $crudData['response_data'][0]['children'][] = [
                                'name'      => $item['field_name_en'],
                                'form_type' => $this->getFeildType($item['form_value']),
                                'message'   => $item['field_name'],
                            ];
                        }
                    }
                } else {
                    $crudData['post_prams'] = [
                        [
                            'name'      => 'crud_id',
                            'form_type' => 'int',
                            'is_must'   => false,
                            'message'   => '一对一关联实体id',
                        ],
                        [
                            'name'      => 'crud_value',
                            'form_type' => 'string',
                            'is_must'   => false,
                            'message'   => '一对一关联实体数据',
                        ],
                    ];

                    $crudList = $moduleService->getFormField($crudData['crud_id']);
                    foreach ($crudList as $item) {
                        $fieldName = $item['crud']['id'] === $crudData['crud_id']
                            ? $item['field_name_en']
                            : $item['crud']['table_name_en'] . '.' . $item['field_name_en'];

                        $fieldValueName = str_replace('.', '@', $fieldName);

                        [, $ruleData] = $moduleService->getValidationRule($item, $fieldName);

                        $crudData['post_prams'][] = [
                            'name'      => $fieldValueName,
                            'form_type' => $this->getFeildType($item['form_value']),
                            'is_must'   => in_array('required', $ruleData),
                            'message'   => $item['field_name'],
                        ];
                    }

                    $crudData['response_data'] = [
                        [
                            'name'      => 'data',
                            'form_type' => 'array',
                            'message'   => '数据',
                        ],
                        [
                            'name'      => 'status',
                            'form_type' => 'int',
                            'message'   => '状态',
                        ],
                        [
                            'name'      => 'message',
                            'form_type' => 'string',
                            'message'   => '说明',
                        ],
                    ];
                }
                break;
            case 'PUT':
                $crudData['path_prams'] = [
                    [
                        'name'      => 'id',
                        'form_type' => 'int',
                        'is_must'   => true,
                        'message'   => '自增ID',
                    ],
                ];

                $crudData['post_prams'] = [
                    [
                        'name'      => 'crud_id',
                        'form_type' => 'int',
                        'is_must'   => false,
                        'message'   => '一对一关联实体id',
                    ],
                    [
                        'name'      => 'crud_value',
                        'form_type' => 'string',
                        'is_must'   => false,
                        'message'   => '一对一关联实体数据',
                    ],
                ];

                $crudList = $moduleService->getFormField($crudData['crud_id']);
                foreach ($crudList as $item) {
                    $fieldName = $item['crud']['id'] === $crudData['crud_id']
                        ? $item['field_name_en']
                        : $item['crud']['table_name_en'] . '.' . $item['field_name_en'];

                    $fieldValueName = str_replace('.', '@', $fieldName);

                    [, $ruleData] = $moduleService->getValidationRule($item, $fieldName);

                    $crudData['post_prams'][] = [
                        'name'      => $fieldValueName,
                        'form_type' => $this->getFeildType($item['form_value']),
                        'is_must'   => in_array('required', $ruleData),
                        'message'   => $item['field_name'],
                    ];
                }

                $crudData['response_data'] = [
                    [
                        'name'      => 'data',
                        'form_type' => 'array',
                        'message'   => '数据',
                    ],
                    [
                        'name'      => 'status',
                        'form_type' => 'int',
                        'message'   => '状态',
                    ],
                    [
                        'name'      => 'message',
                        'form_type' => 'string',
                        'message'   => '说明',
                    ],
                ];

                break;
            case 'DELETE':
                $crudData['path_prams'] = [
                    [
                        'name'      => 'id',
                        'form_type' => 'int',
                        'is_must'   => true,
                        'message'   => '自增ID',
                    ],
                ];

                $crudData['response_data'] = [
                    [
                        'name'      => 'data',
                        'form_type' => 'array',
                        'message'   => '数据',
                    ],
                    [
                        'name'      => 'status',
                        'form_type' => 'int',
                        'message'   => '状态',
                    ],
                    [
                        'name'      => 'message',
                        'form_type' => 'string',
                        'message'   => '说明',
                    ],
                ];
                break;
        }

        if (! empty($crudData['post_prams'])) {
            $crudData['request_json'] = $this->getDataJosn($crudData['post_prams']);
        } elseif (! empty($crudData['get_prams'])) {
            $crudData['request_json'] = $this->getDataJosn($crudData['get_prams']);
        } else {
            $crudData['request_json'] = (object) [];
        }

        if (! empty($crudData['response_data'])) {
            $crudData['response_json'] = $this->getDataJosn($crudData['response_data']);
        } else {
            $crudData['response_json'] = (object) [];
        }

        return $crudData;
    }

    /**
     * 获取对外接口文档内容.
     * @return array
     * @throws BindingResolutionException
     */
    public function getSystemApiDoc(array $crudData)
    {
        if (! empty($crudData['post_prams'])) {
            $crudData['post_prams']   = is_array($crudData['post_prams']) ? $crudData['post_prams'] : json_decode($crudData['post_prams'], true);
            $crudData['request_json'] = $this->getDataJosn($crudData['post_prams']);
        } elseif (! empty($crudData['get_prams'])) {
            $crudData['get_prams']    = is_array($crudData['get_prams']) ? $crudData['get_prams'] : json_decode($crudData['get_prams'], true);
            $crudData['request_json'] = $this->getDataJosn($crudData['get_prams']);
        } else {
            $crudData['request_json'] = (object) [];
        }

        if (! empty($crudData['path_prams'])) {
            $crudData['path_prams'] = is_array($crudData['path_prams']) ? $crudData['post_prams'] : json_decode($crudData['path_prams'], true);
        }

        if (! empty($crudData['response_data'])) {
            $crudData['response_data'] = is_array($crudData['response_data']) ? $crudData['response_data'] : json_decode($crudData['response_data'], true);
            $crudData['response_json'] = $this->getDataJosn($crudData['response_data']);
        } else {
            $crudData['response_json'] = (object) [];
        }

        return $crudData;
    }

    /**
     * 获取接口文档.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getApiDoc()
    {
        $tree = $this->getRoleTree(['*'], 'doc');
        foreach ($tree as $index => $item) {
            foreach ($item['children'] as $chinx => $child) {
                if (! empty($child['children'])) {
                    foreach ($child['children'] as $i => $v) {
                        if ($v['crud_id']) {
                            $tree[$index]['children'][$chinx]['children'][$i] = $this->getCacheCrudApiDoc($v);
                        }
                    }
                } else {
                    $tree[$index]['children'][$chinx] = $this->getCacheSystemApiDoc($child);
                }
            }
        }

        return $tree;
    }

    /**
     * 组合 params.
     */
    public function assembleParams(array $rule, array $option = []): array
    {
        if (isset($rule['path_prams']) && is_array($rule['path_prams'])) {
            $rule['path_prams'] = json_encode($rule['path_prams'], JSON_UNESCAPED_UNICODE);
        }

        if (isset($rule['post_prams']) && is_array($rule['post_prams'])) {
            $rule['post_prams'] = json_encode($rule['post_prams'], JSON_UNESCAPED_UNICODE);
        }

        if (in_array($rule['method'], ['PUT', 'DELETE']) && ! isset($rule['path_prams'])) {
            $pathPrams = [
                [
                    'name'      => 'id',
                    'form_type' => 'int',
                    'is_must'   => true,
                    'message'   => '自增ID',
                ],
            ];
            $rule['path_prams'] = json_encode($pathPrams, JSON_UNESCAPED_UNICODE);
        }

        if (in_array($rule['method'], ['POST', 'PUT']) && ! isset($rule['post_prams']) && $option) {
            $postPrams = [];
            foreach ($option as $item) {
                $postPrams[] = [
                    'name'      => $item['key'],
                    'form_type' => $item['key'] == 'area_cascade' ? 'array' : match ($item['type']) {
                        'checked' => 'array',
                        'number', 'text', 'radio', 'single', 'textarea', 'date', 'file' => 'string',
                        default => 'string',
                    },
                    'is_must' => $item['required'] > 0,
                    'message' => $item['key_name'],
                ];
            }
            $rule['post_prams'] = json_encode($postPrams, JSON_UNESCAPED_UNICODE);
        }

        $responseData = [
            [
                'name'      => 'data',
                'form_type' => 'array',
                'message'   => '数据',
            ],
            [
                'name'      => 'status',
                'form_type' => 'int',
                'message'   => '状态',
            ],
            [
                'name'      => 'message',
                'form_type' => 'string',
                'message'   => '说明',
            ],
        ];

        if (isset($rule['response_data'])) {
            $responseData[0]['children'] = $rule['response_data'];
            $rule['response_data']       = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        }

        if ($rule['method'] == 'POST' && ! isset($rule['response_data'])) {
            $responseData[0]['children'] = [['name' => 'id', 'form_type' => 'int', 'message' => '自增ID']];
            $rule['response_data']       = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        }

        if (in_array($rule['method'], ['PUT', 'DELETE']) && ! isset($rule['response_data'])) {
            $rule['response_data'] = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        }

        return $rule;
    }

    /**
     * 更新自定义业务文档.
     */
    public function reloadCustomRuleParam(int $types = 0): void
    {
        if (! in_array($types, [CustomEnum::CUSTOMER, CustomEnum::CONTRACT, CustomEnum::LIAISON])) {
            return;
        }

        $rules = [
            CustomEnum::CUSTOMER => [
                'name'    => '客户基本信息',
                'crud_id' => 0,
                'method'  => '',
                'url'     => '',
                'type'    => 0,
                'child'   => [
                    [
                        'name'    => '新增客户',
                        'crud_id' => 0,
                        'method'  => 'POST',
                        'url'     => 'open/customer',
                        'type'    => 1,
                    ],
                    [
                        'name'    => '修改客户',
                        'crud_id' => 0,
                        'method'  => 'PUT',
                        'url'     => 'open/customer/{id}',
                        'type'    => 1,
                    ],
                    [
                        'name'    => '删除客户',
                        'crud_id' => 0,
                        'method'  => 'DELETE',
                        'url'     => 'open/customer/{id}',
                        'type'    => 1,
                    ],
                ],
            ],
            CustomEnum::CONTRACT => [
                'name'    => '订单',
                'crud_id' => 0,
                'method'  => '',
                'url'     => '',
                'type'    => 0,
                'child'   => [
                    [
                        'name'    => '新增订单',
                        'crud_id' => 0,
                        'method'  => 'POST',
                        'url'     => 'open/contract',
                        'type'    => 1,
                    ],
                    [
                        'name'    => '修改订单',
                        'crud_id' => 0,
                        'method'  => 'PUT',
                        'url'     => 'open/contract/{id}',
                        'type'    => 1,
                    ],
                    [
                        'name'    => '删除订单',
                        'crud_id' => 0,
                        'method'  => 'DELETE',
                        'url'     => 'open/contract/{id}',
                        'type'    => 1,
                    ],
                ],
            ],
            CustomEnum::LIAISON => [
                'name'    => '客户联系人',
                'crud_id' => 0,
                'method'  => '',
                'url'     => '',
                'type'    => 0,
                'child'   => [
                    [
                        'name'    => '新增联系人',
                        'crud_id' => 0,
                        'method'  => 'POST',
                        'url'     => 'open/liaison',
                        'type'    => 1,
                    ],
                    [
                        'name'    => '修改联系人',
                        'crud_id' => 0,
                        'method'  => 'PUT',
                        'url'     => 'open/liaison/{id}',
                        'type'    => 1,
                    ],
                    [
                        'name'    => '删除联系人',
                        'crud_id' => 0,
                        'method'  => 'DELETE',
                        'url'     => 'open/liaison/{id}',
                        'type'    => 1,
                    ],
                ],
            ],
        ];

        $rule = $rules[$types] ?? [];
        if (empty($rule)) {
            return;
        }

        try {
            $this->transaction(function () use ($rule, $types) {
                $field  = ['id', 'key', 'key_name', 'type', 'input_type', 'required'];
                $option = app()->get(FormService::class)->getCustomDataByTypes($types, $field);
                $pid    = $this->dao->value(['name' => $rule['name'], 'crud_id' => 0], 'id');
                if (! $pid) {
                    $parent = $this->dao->create(['name' => $rule['name'], 'crud_id' => 0, 'type' => 0]);
                    $pid    = $parent?->id;
                }

                if (! $pid) {
                    return;
                }

                foreach ($rule['child'] as $child) {
                    $id = $this->dao->value(['name' => $child['name'], 'pid' => $pid], 'id');
                    if (! $id) {
                        $this->dao->create($this->assembleParams(array_merge($child, ['pid' => $pid]), $option));
                    } else {
                        $this->dao->update(['id' => $id], $this->assembleParams($child, $option));
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('自定义业务文档更新失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * 获取json数据.
     * @return array
     */
    protected function getDataJosn(array $response)
    {
        $responseJson = [];
        foreach ($response as $datum) {
            if (! empty($datum['children'])) {
                $value = $this->getDataJosn($datum['children']);
            } else {
                $value = match ($datum['form_type']) {
                    'string' => '',
                    'int'    => '0',
                    'array'  => '[]',
                };
            }
            $responseJson[$datum['name']] = $value;
        }

        return $responseJson;
    }

    /**
     * 获取字段类型.
     * @return string
     */
    protected function getFeildType(string $formType)
    {
        $dataType = 'int';
        switch ($formType) {
            case CrudFormEnum::FORM_TAG:
            case CrudFormEnum::FORM_CHECKBOX:
            case CrudFormEnum::FORM_CASCADER_ADDRESS:
            case CrudFormEnum::FORM_CASCADER_RADIO:
                $dataType = 'array';
                break;
            case CrudFormEnum::FORM_IMAGE:
            case CrudFormEnum::FORM_FILE:
            case CrudFormEnum::FORM_CASCADER:
            case CrudFormEnum::FORM_DATE_PICKER:
            case CrudFormEnum::FORM_DATE_TIME_PICKER:
            case CrudFormEnum::FORM_RICH_TEXT:
                $dataType = 'string';
                break;
        }

        return $dataType;
    }

    /**
     * 获取缓存的接口文档.
     * @return array|mixed
     * @throws BindingResolutionException
     */
    protected function getCacheCrudApiDoc(array $crudData)
    {
        return Cache::tags('docs')->remember('openapi_docs_' . md5(json_encode($crudData)), 86000, fn () => $this->getCrudApiDoc($crudData));
    }

    /**
     * 获取缓存的接口文档.
     * @return array|mixed
     * @throws BindingResolutionException
     */
    protected function getCacheSystemApiDoc(array $crudData)
    {
        return Cache::tags('docs')->remember('openapi_docs_' . md5(json_encode($crudData)), 86000, fn () => $this->getSystemApiDoc($crudData));
    }
}
