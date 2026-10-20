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

use App\Constants\CacheEnum;
use App\Constants\Crud\CrudAggregateEnum;
use App\Constants\Crud\CrudEventEnum;
use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudOperatorEnum;
use App\Constants\Crud\CrudTriggerEnum;
use App\Constants\Crud\CrudUpdateEnum;
use App\Http\Contract\System\MenusInterface;
use App\Http\Dao\Crud\SystemCrudDao;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\DictTypeService;
use App\Http\Service\Open\OpenapiRuleService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\ApiException;
use Doctrine\DBAL\Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 低代码
 * Class SystemCrudService.
 * @email 136327134@qq.com
 * @date 2024/2/24
 * @mixin SystemCrudDao
 */
class SystemCrudService extends BaseService
{
    public const TAG_NAME = 'crud_info';

    // 关于数字的搜索
    public const OPERATOR_NUMBER_TYPE = [
        [
            'label' => '等于',
            'value' => CrudOperatorEnum::OPERATOR_EQ,
        ],
        [
            'label' => '大于',
            'value' => CrudOperatorEnum::OPERATOR_GT,
        ],
        [
            'label' => '小于',
            'value' => CrudOperatorEnum::OPERATOR_LT,
        ],
        [
            'label' => '大于等于',
            'value' => CrudOperatorEnum::OPERATOR_GT_EQ,
        ],
        [
            'label' => '小于等于',
            'value' => CrudOperatorEnum::OPERATOR_LT_EQ,
        ],
        [
            'label' => '区间',
            'value' => CrudOperatorEnum::OPERATOR_BT,
        ],
    ];

    // 字符串或者数组的条件搜索
    public const OPERATOR_TYPE = [
        [
            'label' => '包含',
            'value' => CrudOperatorEnum::OPERATOR_IN,
        ],
        [
            'label' => '不包含',
            'value' => CrudOperatorEnum::OPERATOR_NOT_IN,
        ],
        [
            'label' => '不等于',
            'value' => CrudOperatorEnum::OPERATOR_NOT_EQ,
        ],
        [
            'label' => '为空',
            'value' => CrudOperatorEnum::OPERATOR_IS_EMPTY,
        ],
        [
            'label' => '不为空',
            'value' => CrudOperatorEnum::OPERATOR_NOT_EMPTY,
        ],
    ];

    // 时间戳相关的搜索条件
    public const OPERATOR_TIMER_TYPE = [
        [
            'label' => '等于',
            'value' => CrudOperatorEnum::OPERATOR_EQ,
        ],
        [
            'label' => '大于',
            'value' => CrudOperatorEnum::OPERATOR_GT,
        ],
        [
            'label' => '小于',
            'value' => CrudOperatorEnum::OPERATOR_LT,
        ],
        [
            'label' => '区间',
            'value' => CrudOperatorEnum::OPERATOR_BT,
        ],
        [
            'label' => 'N天前',
            'value' => CrudOperatorEnum::OPERATOR_N_DAY,
        ],
        [
            'label' => '最近N天',
            'value' => CrudOperatorEnum::OPERATOR_LAST_DAY,
        ],
        [
            'label' => '未来N天',
            'value' => CrudOperatorEnum::OPERATOR_NEXT_DAY,
        ],
        [
            'label' => '未来N天',
            'value' => CrudOperatorEnum::OPERATOR_NEXT_DAY,
        ],
        [
            'label' => '今天',
            'value' => CrudOperatorEnum::OPERATOR_TO_DAY,
        ],
        [
            'label' => '本周',
            'value' => CrudOperatorEnum::OPERATOR_WEEK,
        ],
        [
            'label' => '本月',
            'value' => CrudOperatorEnum::OPERATOR_MONTH,
        ],
        [
            'label' => '本季度',
            'value' => CrudOperatorEnum::OPERATOR_QUARTER,
        ],
        [
            'label' => '本年',
            'value' => CrudOperatorEnum::OPERATOR_YEAR,
        ],
    ];

    // 表单类型
    public const FORM_TYPE = [
        [
            'options' => [
                [
                    'label'   => '文本',
                    'value'   => CrudFormEnum::FORM_INPUT,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => '',
                ],
                [
                    'label'        => '长文本',
                    'value'        => CrudFormEnum::FORM_TEXTAREA,
                    'type'         => 'text',
                    'limit'        => null,
                    'default'      => null,
                    'default_type' => '2',
                ],
                [
                    'label'        => '富文本',
                    'value'        => CrudFormEnum::FORM_RICH_TEXT,
                    'type'         => 'longtext',
                    'limit'        => null,
                    'default'      => null,
                    'default_type' => '2',
                ],
            ],
        ],
        [
            'options' => [
                [
                    'label'   => '布尔类型',
                    'value'   => CrudFormEnum::FORM_SWITCH,
                    'type'    => 'tinyint',
                    'limit'   => 1,
                    'default' => 0,
                ],
                [
                    'label'   => '整数类型',
                    'value'   => CrudFormEnum::FORM_INPUT_NUMBER,
                    'type'    => 'int',
                    'limit'   => 11,
                    'default' => 0,
                ],
                [
                    'label'   => '精度小数',
                    'value'   => CrudFormEnum::FORM_INPUT_FLOAT,
                    'type'    => 'decimal',
                    'limit'   => '10,2',
                    'default' => 0,
                ],
                [
                    'label'   => '百分比',
                    'value'   => CrudFormEnum::FORM_INPUT_PERCENTAGE,
                    'type'    => 'int',
                    'limit'   => 11,
                    'default' => 0,
                ],
                [
                    'label'   => '金额',
                    'value'   => CrudFormEnum::FORM_INPUT_PRICE,
                    'type'    => 'decimal',
                    'limit'   => '10,2',
                    'default' => 0,
                ],
            ],
        ],
        [
            'options' => [
                [
                    'label'   => '单选项',
                    'value'   => CrudFormEnum::FORM_RADIO,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => 0,
                ],
                [
                    'label'   => '级联单选',
                    'value'   => CrudFormEnum::FORM_CASCADER_RADIO,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => '',
                ],
                [
                    'label'   => '地址选择',
                    'value'   => CrudFormEnum::FORM_CASCADER_ADDRESS,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => '',
                ],
            ],
        ],
        [
            'options' => [
                [
                    'label'   => '复选项',
                    'value'   => CrudFormEnum::FORM_CHECKBOX,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => '',
                ],
                [
                    'label'   => '标签组',
                    'value'   => CrudFormEnum::FORM_TAG,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => '',
                ],
                [
                    'label'   => '级联复选',
                    'value'   => CrudFormEnum::FORM_CASCADER,
                    'type'    => 'varchar',
                    'limit'   => 255,
                    'default' => '',
                ],
            ],
        ],
        [
            'options' => [
                [
                    'label'   => '日期',
                    'value'   => CrudFormEnum::FORM_DATE_PICKER,
                    'type'    => 'date',
                    'limit'   => null,
                    'default' => null,
                ],
                [
                    'label'   => '日期时间',
                    'value'   => CrudFormEnum::FORM_DATE_TIME_PICKER,
                    'type'    => 'datetime',
                    'limit'   => null,
                    'default' => null,
                ],
            ],
        ],
        [
            'options' => [
                [
                    'label'        => '图片',
                    'value'        => CrudFormEnum::FORM_IMAGE,
                    'type'         => 'text',
                    'limit'        => null,
                    'default'      => null,
                    'default_type' => '2',
                ],
                [
                    'label'        => '文件',
                    'value'        => CrudFormEnum::FORM_FILE,
                    'type'         => 'text',
                    'limit'        => null,
                    'default'      => null,
                    'default_type' => '2',
                ],
            ],
        ],
        [
            'options' => [
                [
                    'label'   => '一对一关联',
                    'value'   => CrudFormEnum::FORM_INPUT_SELECT,
                    'type'    => 'int',
                    'limit'   => 11,
                    'default' => 0,
                ],
                //                [
                //                    'label' => '自建表格',
                //                    'value' => 'diy_table',
                //                    'type' => 'longtext',
                //                    'limit' => null,
                //                    'default' => null,
                //                    'default_type' => '2'
                //                ],
            ],
        ],
    ];

    // 事件类型
    public const EVENT_TYPE = [
        [
            'label'   => '发送通知',
            'value'   => CrudEventEnum::EVENT_SEND_NOTICE,
            'message' => '自动触发通知',
        ],
        [
            'label'   => '流程审批',
            'value'   => CrudEventEnum::EVENT_AUTH_APPROVE,
            'message' => '自动触发审批流程',
        ],
        [
            'label'   => '日程待办',
            'value'   => CrudEventEnum::EVENT_TO_DO_SCHEDULE,
            'message' => '增加日程待办',
        ],
        [
            'label'   => '自动新增',
            'value'   => CrudEventEnum::EVENT_AUTO_CREATE,
            'message' => '新增从表明细记录',
        ],
        [
            'label'   => '字段更新',
            'value'   => CrudEventEnum::EVENT_FIELD_UPDATE,
            'message' => '更新本表和关联表',
        ],
        [
            'label'   => '获取数据',
            'value'   => CrudEventEnum::EVENT_GET_DATA,
            'message' => '远程获取数据',
        ],
        [
            'label'   => '推送数据',
            'value'   => CrudEventEnum::EVENT_PUSH_DATA,
            'message' => '推送给他人数据',
        ],
        [
            'label'   => '字段聚合',
            'value'   => CrudEventEnum::EVENT_FIELD_AGGREGATE,
            'message' => '聚合从表字段',
        ],
        //        [
        //            'label' => '分组聚合',
        //            'value' => CrudEventEnum::EVENT_GROUP_AGGREGATE,
        //        ],
        [
            'label'   => '流程撤销',
            'value'   => CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE,
            'message' => '流程撤销触发动作',
        ],
        [
            'label'   => '数据校验',
            'value'   => CrudEventEnum::EVENT_DATA_CHECK,
            'message' => '表单数据校验',
        ],
    ];

    // 聚合方式
    public const AGGREGATE_TYPE = [
        [
            'label' => '求和',
            'value' => CrudAggregateEnum::AGGREGATE_SUM,
        ],
        [
            'label' => '计数',
            'value' => CrudAggregateEnum::AGGREGATE_COUNT,
        ],
        [
            'label' => '去重计数',
            'value' => CrudAggregateEnum::AGGREGATE_UNIQID_COUNT,
        ],
        [
            'label' => '平均值',
            'value' => CrudAggregateEnum::AGGREGATE_AVG,
        ],
        [
            'label' => '最大值',
            'value' => CrudAggregateEnum::AGGREGATE_MAX,
        ],
        [
            'label' => '最小值',
            'value' => CrudAggregateEnum::AGGREGATE_MIN,
        ],
    ];

    // 触发动作
    public const ACTION_TYPE = [
        [
            'label' => '新建时',
            'value' => CrudTriggerEnum::TRIGGER_CREATED,
        ],
        [
            'label' => '更新时',
            'value' => CrudTriggerEnum::TRIGGER_UPDATED,
        ],
        [
            'label' => '删除时',
            'value' => CrudTriggerEnum::TRIGGER_DELETED,
        ],
        [
            'label' => '审批通过时',
            'value' => CrudTriggerEnum::TRIGGER_APPROVED,
        ],
        [
            'label' => '审批撤销',
            'value' => CrudTriggerEnum::TRIGGER_REVOKE,
        ],
        [
            'label' => '审批提交时',
            'value' => CrudTriggerEnum::TRIGGER_SAVED,
        ],
        [
            'label' => '审批驳回/撤回',
            'value' => CrudTriggerEnum::TRIGGER_REJECT,
        ],
        [
            'label' => '定期执行',
            'value' => CrudTriggerEnum::TRIGGER_TIMER,
        ],
    ];

    public const UPDATE_TYPE = [
        [
            'label' => '字段值',
            'value' => CrudUpdateEnum::UPDATE_TYPE_FIELD,
        ],
        [
            'label' => '固定值',
            'value' => CrudUpdateEnum::UPDATE_TYPE_VALUE,
        ],
        [
            'label' => '计算公式',
            'value' => CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE,
        ],
        [
            'label' => '置空',
            'value' => CrudUpdateEnum::UPDATE_TYPE_NULL_VALUE,
        ],
    ];

    // 表字符集
    public const TABLR_COLLATION = 'utf8mb4_general_ci';

    public const SYSTEM_TABLE_TABLE = [
        'admin', 'frame', 'gongzitiaojiegou', 'gongzitiaojilu', 'customer', 'contract', 'customer_liaison',
        'client_bill', 'client_follow', 'client_remind', 'client_subscribe', 'customer_record', 'client_invoice',
        'system_attach', 'bill_list', 'bill_category', 'customer_clue', 'customer_odds',
    ];

    public const SYSTEM_ALLOW_OPERATE_TABLE = [
        'gongzitiaojiegou', 'gongzitiaojilu',
    ];

    public const SYSTEM_TABLE_TABLE_USER = 'admin';

    public const SYSTEM_TABLE_TABLE_FRAME = 'frame';

    /**
     * SytemCrudService constructor.
     */
    public function __construct(SystemCrudDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取表名.
     * @return string
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getTableName(string $tableName, bool $fullName = true)
    {
        $tableName = addslashes($tableName);
        $tablePrefix = config('database.connections.mysql.prefix');
        $pattern = '/^' . $tablePrefix . '/i';
        return ($fullName ? $tablePrefix : '') . preg_replace($pattern, '', $tableName);
    }

    /**
     * 创建表.
     * @email 136327134@qq.com
     * @date 2024/2/23
     */
    public function createTable(string $tableName, bool $index = false, string $sideTable = '', string $comment = '')
    {
        Schema::create($tableName, function (Blueprint $table) use ($sideTable, $index, $comment) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            if ($index) {
                $table->integer($sideTable . '_id')->default(0)->comment($comment . '关联id');
            }
            $table->integer('user_id')->index()->default(0)->comment('创建用户id');
            $table->integer('update_user_id')->index()->default(0)->comment('修改用户id');
            $table->integer('owner_user_id')->index()->default(0)->comment('所属用户id');
            $table->integer('frame_id')->index()->default(0)->comment('部门ID');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * 删除索引.
     * @param string $tableName 副表表名
     * @param string $sideName 主表名称
     * @param string $unique 索引类型
     * @return array
     */
    public function schemaDeleteIndex(string $tableName, string $sideName, string $unique = 'unique')
    {
        $tablePrefix = config('database.connections.mysql.prefix');
        $indexName = $tablePrefix . $tableName . '_' . $sideName . '_id_' . $unique;
        $this->dropIndex($tableName, $indexName, $unique);
    }

    public function schemaAddIndex(string $tableName, string $sideName, string $unique = 'unique')
    {
        $indexName = $tableName . '_' . $sideName . '_id_' . $unique;
        $this->createIndex($tableName, $indexName, $unique);
    }

    /**
     * 删除索引.
     */
    public function dropIndex(string $tableName, string $indexName, $unique)
    {
        if (Schema::hasTable($tableName)) {
            $newTableName = config('database.connections.mysql.prefix') . $tableName;
            // 先检查索引是否存在
            $indexes = DB::select("SHOW INDEX FROM `{$newTableName}` WHERE Key_name = ?", [$indexName]);

            if (!empty($indexes)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName, $unique) {
                    if ($unique === 'unique') {
                        $table->dropUnique($indexName);
                    } else {
                        $table->dropIndex($indexName);
                    }
                });
            }
        }
    }

    /**
     * 添加索引.
     */
    public function createIndex(string $tableName, string $indexName, string $index = 'index')
    {
        Schema::table($tableName, function (Blueprint $table) use ($indexName, $index) {
            if ($index == 'index') {
                $table->index($indexName);
            } elseif ($index == 'unique') {
                $table->unique($indexName);
            }
        });
    }

    /**
     * 删除表.
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function dropIfExists(string $tableName)
    {
        Schema::dropIfExists($tableName);
    }

    /**
     * 获取一对一关联表.
     * @return mixed[]
     * @email 136327134@qq.com
     * @date 2024/2/26
     * @throws BindingResolutionException
     */
    public function getAssociationCrud(int $id)
    {
        [$page, $limit] = $this->getPageValue();
        $tableNameEn = $this->dao->value($id, 'table_name_en');
        return $this->dao->getAssociationList($tableNameEn, $page, $limit);
    }


    /**
     * 表单设置中获取字段和实体信息组合成的表单配置.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/18
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMainFieldForm(int $id)
    {
        $notField = ['deleted_at', 'id'];
        $master = ['id', 'table_name', 'table_name_en', 'crud_id'];
        $select = [
            'crud_id', 'is_default_value_not_null', 'create_modify', 'update_modify',
            'is_form', 'data_dict_id', 'options', 'id', 'field_name_en', 'field_name',
            'form_value', 'form_field_uniqid', 'data_type', 'customize_items', 'data_type',
        ];
        $crudInfo = $this->dao->get($id, $master, [
            'children' => fn($q) => $q->with([
                'field' => fn($query) => $query->whereNotIn('field_name_en', $notField)->select($select),
            ])->select($master),
            'field'    => fn($query) => $query->whereNotIn('field_name_en', $notField)->select($select),
        ]);

        if (!$crudInfo) {
            throw $this->exception('没有查询到字段信息');
        }

        $crudInfo = $crudInfo->toArray();
        $children = $crudInfo['children'] ?? [];
        unset($crudInfo['children']);

        $list = [$crudInfo];
        foreach ((array)$children as $item) {
            foreach ($item['field'] as $index => $value) {
                if (($crudInfo['table_name_en'] . '_id') === $value['field_name_en']) {
                    unset($item['field'][$index]);
                }
            }

            $item['field'] = array_merge($item['field']);

            array_push($list, $item);
        }

        $dictData = [];
        $dictDataService = app()->get(DictDataService::class);
        $dictTypeService = app()->get(DictTypeService::class);
        $formService = app()->get(SystemCrudFormService::class);
        foreach ($list as $index => $item) {
            foreach ($item['field'] as $k => $v) {
                $v['form_field_uniqid'] = ($item['id'] !== $id ? $item['table_name_en'] . '.' : '') . $v['field_name_en'];

                if ($v['data_dict_id']) {
                    if (!isset($dictData[$v['data_dict_id']])) {
                        $typeName = $dictTypeService->value($v['data_dict_id'], 'ident');
                        $dictData[$v['data_dict_id']] = $dictDataService->getTreeData(['type_id' => $v['data_dict_id'], 'type_name' => $typeName]);
                    }
                    $v['data_dict'] = $dictData[$v['data_dict_id']];
                } else {
                    $v['data_dict'] = [];
                }
                $v['crud_id'] = $item['crud_id'];
                $item['field'][$k] = $formService->setFormConfig($v);
            }
            $list[$index] = $item;
        }

        return $list;
    }

    /**
     * 获取事件内的字段信息.
     * @return array|mixed[]
     * @email 136327134@qq.com
     * @date 2024/3/19
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getEventCrud(int $crudId, int $eventId = 0)
    {
        $notField = ['deleted_at'];
        $master = ['id', 'table_name', 'table_name_en', 'crud_id'];
        $select = ['options', 'is_default', 'crud_id', 'data_dict_id', 'id', 'field_name_en', 'field_name', 'form_value', 'form_field_uniqid', 'is_uniqid', 'customize_items', 'data_type', 'association_show_type'];

        $with = [
            'field' => fn($query) => $query->orderByDesc('id')->whereNotIn('field_name_en', $notField)->select($select),
        ];

        $crudInfo = $this->dao->get($crudId, $master, $with)?->toArray();

        if (!$crudInfo) {
            throw $this->exception('没有查询到字段信息');
        }

        $list = [$crudInfo];

        $event = $eventId ? app()->get(SystemCrudEventService::class)->value($eventId, 'event') : '';

        // 如果是 字段更新 需要查询当前表中一对一关联的表；还需要其他实体中一对一关联了当前表的数据，当前表
        // 如果是 自动创建 展示其他实体关联当前实体的表
        // 如果是 字段聚合 当为附表时只展示关联的主表和一对一关联的表
        // 如果是 分组聚合 目标实体的列表是所有实体，分组字段关联展示的字段是当前所选主体的字段，而聚合的规则是数据库字段int和小数类型的
        // 如果是 数据校验 当为附表时只展示关联的主表
        // 如果是 自动审批 目标实体的列表为，从表情况下，主表和当前从表的一对一关联表,主表情况下获取到当前自行创建的一对一关联的表

        $association = [];
        if ($event !== CrudEventEnum::EVENT_AUTO_CREATE) {
            $associationList = app()->get(SystemCrudFieldService::class)->getAssociationCrudId($crudId, 1);
            foreach ($associationList as $item) {
                $association[$item['association_crud_id']] = $item['field_name'];
            }

            $associationIds = array_merge(array_unique(array_column($associationList, 'association_crud_id')));
            $associationList = $this->dao->getCrudList($associationIds, $master, $with);

            if ($associationList) {
                foreach ($associationList as $index => $item) {
                    $msg = $item['id'] === $crudInfo['crud_id'] ? '主从关联ID' : $association[$item['id']];
                    $item['table_name'] = $item['table_name'] . '(' . $msg . ')';
                    $associationList[$index] = $item;
                }
                $list = array_merge($list, $associationList);
            }
        }

        if ($crudInfo['crud_id']) {
            // 从表查找主表
            $scheduleCrudInfo = $this->dao->get($crudInfo['crud_id'], $master, $with);
            if ($scheduleCrudInfo) {
                $scheduleCrudInfo = $scheduleCrudInfo->toArray();
                $scheduleCrudInfo['table_name'] = $scheduleCrudInfo['table_name'] . '(主从关联ID)';
                array_push($list, $scheduleCrudInfo);
            }
        }

        $dictDataService = app()->get(DictDataService::class);

        $field = [];
        $dictData = [];
        foreach ($list as $index => $item) {
            $newsField = [];
            foreach ($item['field'] as $i => $k) {
                $dataDict = [];
                $item['field'][$i]['data_dict'] = [];

                $name = ($item['id'] !== $crudId ? $item['table_name_en'] . '.' : '') . $k['field_name_en'];

                $item['field'][$i]['form_field_uniqid'] = $k['form_field_uniqid'] = $name;

                //                if ($k['is_default']) {
                //                    unset($item['field'][$i]);
                //                }

                if ($k['data_type'] == 1) {
                    $item['field'][$i]['data_dict'] = $dataDict = $k['customize_items'];
                } elseif ($k['data_dict_id']) {
                    if (!isset($dictData[$k['data_dict_id']])) {
                        $typeName = app()->get(DictTypeService::class)->value($k['data_dict_id'], 'ident');
                        $dictData[$k['data_dict_id']] = $dictDataService->getTreeData(['type_id' => $k['data_dict_id'], 'type_name' => $typeName]);
                    }
                    $item['field'][$i]['data_dict'] = $dataDict = $dictData[$k['data_dict_id']];
                }

                $field[] = [
                    'id'                    => $k['id'],
                    'label'                 => ($item['id'] !== $crudId ? $item['table_name'] . '.' : '') . $k['field_name'],
                    'value'                 => $name,
                    'form_value'            => $k['form_value'],
                    'field_name_en'         => ($item['id'] !== $crudId ? $item['table_name_en'] . '.' : '') . $k['field_name_en'],
                    'data_dict'             => $dataDict,
                    'field_value'           => $name,
                    'crud_id'               => $item['id'],
                    'is_uniqid'             => $k['is_uniqid'],
                    'association_show_type' => $k['association_show_type'],
                    'customize_items'       => $k['customize_items'],
                    'data_type'             => $k['data_type'],
                    'is_city_show'          => $item['options']['is_city_show'] ?? '',
                ];

                if (in_array($k['form_value'], [
                    CrudFormEnum::FORM_INPUT_FLOAT,
                    CrudFormEnum::FORM_INPUT_PERCENTAGE,
                    CrudFormEnum::FORM_INPUT_PRICE,
                    CrudFormEnum::FORM_INPUT_NUMBER,
                ])) {
                    $newsField[] = $k;
                }
            }

            $item['field'] = array_merge($item['field']);

            // 字段聚合情况下只有整数类型才可以被放入目标实体选择
            if (in_array($event, [CrudEventEnum::EVENT_FIELD_AGGREGATE])) {
                $item['field'] = $newsField;
                $list[$index] = $item;
            } else {
                $list[$index] = $item;
            }
        }
        unset($index, $item, $i, $k, $push);

        $list = array_merge($list);

        if ($eventId && in_array($event, [CrudEventEnum::EVENT_FIELD_UPDATE, CrudEventEnum::EVENT_AUTO_CREATE])) {
            // 查询付表的id
            $lowerId = $this->dao->value(['crud_id' => $crudId], 'id');
            $crudIds = [$crudId];
            if ($lowerId) {
                $crudIds[] = $lowerId;
            }
            // 需要去除掉附表的管理记录，因为在关联展示中，附表不能进行列表设计和表单设计而tab中存在编辑
            $associationCrudList = app()->get(SystemCrudFieldService::class)->crudIdByAssociationCrudList($crudIds, $master, $with);

            foreach ($associationCrudList as $item) {
                if ($item['crud']) {
                    $item['crud']['table_name'] = $item['crud']['table_name'] . '(' . $item['field_name'] . ')(N)';
                    $crudField = [];
                    foreach ($item['crud']['field'] as $i => $v) {
                        $name = $item['crud']['table_name_en'] . '.' . $v['field_name_en'];
                        $v['form_field_uniqid'] = $name;

                        if ($v['data_dict_id']) {
                            if (!isset($dictData[$v['data_dict_id']])) {
                                $typeName = app()->get(DictTypeService::class)->value($v['data_dict_id'], 'ident');
                                $dictData[$v['data_dict_id']] = $dictDataService->getTreeData(['type_id' => $v['data_dict_id'], 'type_name' => $typeName]);
                            }
                            $v['data_dict'] = $dictData[$v['data_dict_id']];
                        }

                        $crudField[] = $v;
                    }

                    $item['crud']['field'] = $crudField;
                    array_push($list, $item['crud']);
                }
            }
        }

        if (in_array($event, [CrudEventEnum::EVENT_FIELD_AGGREGATE, CrudEventEnum::EVENT_AUTO_CREATE])) {
            foreach ($list as $index => $item) {
                if ($item['id'] == $crudInfo['id']) {
                    unset($list[$index]);
                }
                // 自动创建去除掉，当前实体一对一关联的数据
                if ($event === CrudEventEnum::EVENT_AUTO_CREATE && isset($association[$item['id']])) {
                    unset($list[$index]);
                }
            }
            $list = array_merge($list);
        }

        // 分组聚合字段
        if ($eventId && in_array($event, [CrudEventEnum::EVENT_GROUP_AGGREGATE])) {
            $list = $this->dao->select(['crud_id' => 0], $master, $with);
            foreach ($list as $index => $item) {
                $rule = [];
                $newitem = [];
                foreach ($item['field'] as $k) {
                    $k['field_name_en'] = ($item['id'] !== $crudId ? $item['table_name_en'] . '.' : '') . $k['field_name_en'];
                    $k['form_field_uniqid'] = $k['field_name_en'];
                    $newitem[] = $k;
                    if (in_array($k['form_value'], [
                        CrudFormEnum::FORM_INPUT_FLOAT,
                        CrudFormEnum::FORM_INPUT_PERCENTAGE,
                        CrudFormEnum::FORM_INPUT_PRICE,
                        CrudFormEnum::FORM_INPUT_NUMBER,
                    ])) {
                        $rule[] = $k;
                    }
                }

                $list[$index]['rule_field'] = $rule;
                $list[$index]['field'] = $newitem;
            }
        }

        $approve = [];
        if (in_array($event, [
            CrudEventEnum::EVENT_AUTH_APPROVE,
            CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE,
        ])) {
            $approve = app()->get(SystemCrudApproveService::class)->getEventApproveList($crudId);
        }

        $stringFields = $timeFeilds = $userFeilds = [];
        if ($event == CrudEventEnum::EVENT_TO_DO_SCHEDULE) {
            $crudUserId = $this->dao->value(['table_name_en' => self::SYSTEM_TABLE_TABLE_USER], 'id');
            foreach ($field as $item) {
                if (in_array($item['form_value'], [
                    CrudFormEnum::FORM_INPUT,
                    CrudFormEnum::FORM_INPUT_NUMBER,
                    CrudFormEnum::FORM_INPUT_FLOAT,
                    CrudFormEnum::FORM_INPUT_PERCENTAGE,
                    CrudFormEnum::FORM_INPUT_PRICE,
                    CrudFormEnum::FORM_INPUT,
                    CrudFormEnum::FORM_TEXTAREA,
                    CrudFormEnum::FORM_RICH_TEXT,
                    CrudFormEnum::FORM_DATE_PICKER,
                    CrudFormEnum::FORM_DATE_TIME_PICKER,
                ])) {
                    $stringFields[] = $item;
                }
                if (in_array($item['form_value'], [
                    CrudFormEnum::FORM_DATE_TIME_PICKER,
                ])) {
                    $timeFeilds[] = $item;
                }
                if (in_array($item['form_value'], [
                        CrudFormEnum::FORM_INPUT_SELECT,
                    ]) && $crudUserId == app()->get(SystemCrudFieldService::class)->value(['crud_id' => $item['crud_id'], 'field_name_en' => $item['field_name_en']], 'association_crud_id')) {
                    $userFeilds[] = $item;
                }
            }
        }

        return [
            'list'               => $list,
            'field'              => $field,
            'string_fields'      => $stringFields,
            'time_feilds'        => $timeFeilds,
            'user_feilds'        => $userFeilds,
            'approve'            => $approve,
            'uniqid_update_type' => [
                [
                    'label' => '跳过',
                    'value' => CrudUpdateEnum::UPDATE_TYPE_SKIP_VALUE,
                ],
                [
                    'label' => '更新',
                    'value' => CrudUpdateEnum::UPDATE_TYPE_FIELD,
                ],
            ],
            'update_type'        => $event === CrudEventEnum::EVENT_FIELD_AGGREGATE ? self::AGGREGATE_TYPE : self::UPDATE_TYPE,
        ];
    }

    /**
     * 根据id获取表.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/2/27
     * @throws BindingResolutionException
     */
    public function getCrudTableAll(array $crudIds, bool $showTable = false)
    {
        $crudList = $this->dao->getSearchModel()->whereIn('id', $crudIds)->select(['table_name', 'table_name_en', 'id'])->get()->toArray();
        $column = [];

        foreach ($crudList as $item) {
            $item = (array)$item;
            if ($showTable) {
                $column[$item['id']] = [
                    'table_name'    => $item['table_name'],
                    'table_name_en' => $item['table_name_en'],
                ];
            } else {
                $column[$item['id']] = $item['table_name_en'];
            }
        }

        return $column;
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2024/2/26
     * @throws BindingResolutionException
     */
    public function getCrudTableList(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();

        $model = $this->dao->getSearchModel($where);

        $count = $model->count();
        $list = $model->orderByDesc('id')->when(
            value: $page && $limit,
            callback: fn($q) => $q->forPage($page, $limit),
            default: fn($q) => $q->select(['id', 'table_name', 'table_name_en'])
        )->with([
            'children' => fn($q) => $q->with([
                'user' => fn($q) => $q->select(['name', 'id']),
            ])->orderByDesc('id'),
            'user'     => fn($q) => $q->select(['name', 'id', 'avatar']),
            'menuNamePathData',
        ])->get()->toArray();

        $cateIds = [];
        foreach ($list as $item) {
            if (!empty($item['cate_ids'])) {
                $cateIds = array_merge($cateIds, $item['cate_ids']);
            }
        }
        if ($cateIds) {
            $columnList = app()->get(SystemCrudCateService::class)->idsByNameColumn($cateIds);
            foreach ($list as &$item) {
                $cate = [];
                foreach ($item['cate_ids'] as $cId) {
                    if (isset($columnList[$cId])) {
                        $cate[] = $columnList[$cId];
                    }
                }
                $item['cate'] = $cate;
            }
        }
        return compact('count', 'list');
    }

    /**
     * 查询数据列表，可自定义with.
     * @return array
     */
    public function getCrudTableFieldList(array $where = [])
    {
        ksort($where);
        $key = 'search_databases_list_' . md5(json_encode($where));
        return Cache::tags([self::TAG_NAME])->remember($key, 60, function () use ($where) {
            $tables = DB::select('SHOW TABLES');
            $list = [];
            foreach ($tables as $i => $item) {
                $table = array_values((array)$item)[0] ?? null;
                if (!$table) {
                    continue;
                }
                $sql = (array)DB::select("SHOW CREATE TABLE `{$table}`");
                $exce = $sql[0]->{'Create Table'};
                $comment = '';
                if (preg_match("/COMMENT='(.*?)'/s", $exce, $matches)) {
                    $comment = $matches[1];
                    $comment = str_replace(['\n', '\r'], '', $comment);
                }
                if (isset($where['keyword']) && $where['keyword'] !== '') {
                    if (
                        ($comment == '' || mb_strpos($comment, $where['keyword']) === false)
                        && str_contains($table, $where['keyword']) === false
                    ) {
                        continue;
                    }
                }
                $list[] = [
                    'exce'    => $sql[0]->{'Create Table'},
                    'table'   => $table,
                    'comment' => $comment,
                ];
            }
            return $list;
        });
    }

    /**
     * 获取实体和应用组合成的数据.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCrudTree(array $where = [])
    {
        $crud = $this->dao->setDefaultSort('id')->select($where, ['id', 'table_name', 'table_name_en', 'cate_ids'])->toArray();
        $cateIds = [];
        $notCateCrud = [];
        foreach ($crud as $item) {
            if ($item['cate_ids']) {
                $cateIds = array_merge($cateIds, $item['cate_ids']);
            } else {
                $notCateCrud[] = [
                    'value'         => $item['id'],
                    'label'         => $item['table_name'],
                    'table_name_en' => $item['table_name_en'],
                ];
            }
        }
        $tree = [];
        if ($cateIds) {
            $cateList = app()->get(SystemCrudCateService::class)->idsByNameColumn($cateIds);
            foreach ($cateList as $cid => $val) {
                $children = [];
                foreach ($crud as $item) {
                    if (in_array($cid, $item['cate_ids'])) {
                        $children[] = [
                            'value'         => $item['id'],
                            'label'         => $item['table_name'],
                            'table_name_en' => $item['table_name_en'],
                        ];
                    }
                }
                $tree[] = [
                    'value'    => $cid,
                    'label'    => $val,
                    'children' => $children,
                ];
            }
        }

        $tree[] = [
            'value'    => 0,
            'label'    => '未关联应用',
            'children' => $notCateCrud,
        ];

        return $tree;
    }

    /**
     * 创建表.
     * @param int $adminId
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function saveCrudTable(array $data, $adminId, bool $batchCrudBool = true)
    {
        $tableName = $data['table_name'];
        $tableNameEn = $data['table_name_en'];
        $isSystemTable = !in_array($tableNameEn, self::SYSTEM_TABLE_TABLE);
        if ($isSystemTable) {
            $tableNameEn = 'crud_' . $tableNameEn;
        }
        $crudId = $data['crud_id'];

        if ($this->dao->count(['table_name' => $tableName])) {
            throw $this->exception('实体名已存在');
        }
        if ($this->dao->withTrashedTable($tableNameEn)) {
            throw $this->exception('实体已存在或者已被删除，不能添加相同实体名称的实体');
        }

        $indexTableNameEn = null;
        $indexTableName = null;
        if ($crudId) {
            $indexTableName = $this->dao->value($crudId, 'table_name');
            if (!$indexTableNameEn = $this->dao->value($crudId, 'table_name_en')) {
                throw $this->exception('主表不存在');
            }
            if ($this->dao->count(['crud_id' => $crudId])) {
                throw $this->exception('此主体下只能存在一个从表');
            }
            if ($this->dao->value($crudId, 'crud_id')) {
                throw $this->exception('从表下不能添加从表');
            }
        }

        $cateIds = '';
        if ($data['cate_ids']) {
            $cateIds = '/' . implode('/', $data['cate_ids']) . '/';
        }

        $saveField = [];
        [$default, $defaultTime] = $this->getDefaultList($indexTableNameEn ?: '', (int)$crudId, $indexTableName ?: '');

        $table = true;
        $message = null;
        try {
            if ($isSystemTable) {
                $this->createTable($tableNameEn, (bool)$crudId, $crudId ? $this->dao->value($crudId, 'table_name_en') : '', $tableName);
            }
        } catch (\Throwable $e) {
            try {
                $this->dropIfExists($tableNameEn);
            } catch (\Throwable) {
            }
            $message = $e->getMessage();
            if (str_contains($e->getMessage(), "Base table or view already exists: 1050 Table '" . $this->getTableName($tableNameEn) . "' already exists")) {
                $message = '表' . $tableNameEn . '已存在，请创建其他实体名称';
            }
            $table = false;
        }

        $menuData = [];
        if ($batchCrudBool && $data['path']) {
            $menuData = [
                'menu_name'   => $tableName,
                'icon'        => $data['icon'],
                'uni_img'     => $data['uni_img'],
                'crud_id'     => 0,
                'menu_type'   => 1,
                'menu_path'   => '/crud/module',
                'sort'        => 0,
                'crud_app_id' => 0,
                'type'        => 'M',
                'unique_auth' => uniqid('module'),
            ];
            $menuData['uniqued'] = md5($menuData['unique_auth']);
            if (!is_array($data['path'])) {
                $data['path'] = [];
            }
            $path = $menuData['paths'] = array_filter($data['path']);
            $menuData['paths'] = implode('/', $path);
            if ($path) {
                $menuData['pid'] = end($path);
                $menuData['level'] = count($path);
                if ($menuData['pid']) {
                    $menuData['parent_uniqued'] = app()->get(MenusInterface::class)->getModel()->where('id', $menuData['pid'])->value('unique_auth');
                }
            }
        }


        $id = $this->transaction(function () use ($default, $message, $table, $defaultTime, $cateIds, $saveField, $tableNameEn, $tableName, $data, $crudId, $adminId, $menuData) {
            $id = $this->dao->create([
                'table_name'      => $tableName,
                'table_name_en'   => $tableNameEn,
                'cate_ids'        => $cateIds,
                'info'            => $data['info'],
                'crud_id'         => $crudId,
                'user_id'         => $adminId,
                'is_update_form'  => $data['is_update_form'],
                'is_update_table' => $data['is_update_table'],
                'show_comment'    => $data['show_comment'],
                'show_log'        => $data['show_log'],
            ])->id;

            if (!in_array($tableNameEn, self::SYSTEM_TABLE_TABLE)) {
                foreach (array_merge($default, $defaultTime) as $item) {
                    if (!$item['association_crud_id'] && $item['association_crud_table_name_en']) {
                        $item['association_crud_id'] = (int)$this->dao->value(['table_name_en' => $item['association_crud_table_name_en']], 'id');
                    }
                    $saveField[] = [
                        'crud_id'                   => $id,
                        'field_name'                => $item['field_name'],
                        'field_name_en'             => $item['field_name_en'],
                        'form_value'                => $item['form_value'],
                        'field_type'                => $item['field_type'],
                        'is_default_value_not_null' => $item['is_default_value_not_null'] ? 1 : 0,
                        'is_table_show_row'         => $item['is_table_show_row'] ? 1 : 0,
                        'comment'                   => $item['comment'],
                        'prev_field'                => $item['prev_field'],
                        'is_default'                => $item['is_default'],
                        'association_crud_id'       => $item['association_crud_id'],
                        'form_field_uniqid'         => uniqid($tableNameEn),
                        'create_modify'             => 1,
                        'update_modify'             => 1,
                        'created_at'                => date('Y-m-d H:i:s'),
                        'updated_at'                => date('Y-m-d H:i:s'),
                    ];
                }

                if ($saveField) {
                    app()->get(SystemCrudFieldService::class)->insert($saveField);
                }

                if (!$crudId) {
                    app()->get(OpenapiRuleService::class)->saveCrudRule($tableName, $id, $tableNameEn);
                }
            }

            if (!$table) {
                throw $this->exception($message);
            }

            if ($menuData) {
                /** @var Model $menusModel */
                $menusModel = app()->get(MenusInterface::class)->getModel();
                if ($menusModel->where('crud_id', $id)->value('id')) {
                    unset($menuData['uniqued'], $menuData['unique_auth']);
                    $menusModel->update(['crud_id' => $id], $menuData);
                } else {
                    $menuData['crud_id'] = $id;
                    $menusModel->create($menuData);
                }
            }

            return $id;
        });

        event('system.crud');
        //自动创建角色对应的实体权限
        event('system.crud_role', [$adminId, $id, $tableName]);

        return $id;
    }

    /**
     * 修改表信息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function updateCrudTable(int $id, array $data)
    {
        $tableInfo = $this->dao->get($id);
        if (!$tableInfo) {
            throw $this->exception('没有查询到实体');
        }
        if ($this->dao->existsTable($id, $data['table_name'])) {
            throw $this->exception('实体名称已经存在');
        }
        $tableInfo->table_name = $data['table_name'];
        $tableInfo->is_update_form = (int)$data['is_update_form'];
        $tableInfo->is_update_table = (int)$data['is_update_table'];
        $tableInfo->show_comment = (int)$data['show_comment'];
        $tableInfo->show_log = (int)$data['show_log'];
        $tableInfo->info = $data['info'];
        $tableInfo->comment_title = $data['comment_title'];
        if ($data['cate_ids']) {
            $tableInfo->cate_ids = '/' . implode('/', $data['cate_ids']) . '/';
        }
        $tableInfo->save();

        if (!in_array($tableInfo->table_name_en, self::SYSTEM_TABLE_TABLE)) {
            $tableName = $this->getTableName($tableInfo->table_name_en);
            $comment = $data['table_name'];
            DB::select("ALTER TABLE `{$tableName}` COMMENT '{$comment}

'");
        }

        $menuData = [
            'menu_name'   => $tableInfo->table_name,
            'icon'        => $data['icon'],
            'uni_img'     => $data['uni_img'],
            'menu_type'   => 1,
            'menu_path'   => '/crud/module',
            'sort'        => 0,
            'crud_app_id' => 0,
            'type'        => 'M',
            'unique_auth' => uniqid('module'),
        ];
        $menuData['uniqued'] = md5($menuData['unique_auth']);
        if (!is_array($data['path'])) {
            $data['path'] = [];
        }
        $path = $menuData['paths'] = array_filter($data['path']);
        if ($path) {
            $menuData['pid'] = end($path);
            $menuData['level'] = count($path);
            if ($menuData['pid']) {
                $menuData['parent_uniqued'] = app()->get(MenusInterface::class)->getModel()->where('id', $menuData['pid'])->value('unique_auth');
            }
        }
        if ($menuData['paths']) {
            $menuData['paths'] = implode('/', $menuData['paths']);
        }

        /** @var Model $menusModel */
        $menusModel = app()->get(MenusInterface::class)->getModel();
        if ($menusModel->where('crud_id', $id)->value('id')) {
            unset($menuData['uniqued'], $menuData['unique_auth']);
            $menusModel->update(['crud_id' => $id], $menuData);
        } else {
            $menuData['crud_id'] = $id;
            $menusModel->create($menuData);
        }

        event('system.crud');
    }

    /**
     * 复制主体.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/5
     */
    public function copyCrud(int $id, array $data)
    {
        $crudInfo = $this->dao->get($id);
        if (!$crudInfo) {
            throw $this->exception('没有查询到表数据');
        }

        $crudInfoCopy = $crudInfo->toArray();

        unset($crudInfoCopy['id']);

        $fieldService = app()->get(SystemCrudFieldService::class);
        $formService = app()->get(SystemCrudFormService::class);
        $tableService = app()->get(SystemCrudTableService::class);

        if ($this->dao->count(['table_name_en' => $data['table_name_en']])) {
            throw $this->exception('实体表名已经存在请更换其他名称');
        }

        $crudInfoCopy['table_name'] = $data['table_name'];
        $crudInfoCopy['table_name_en'] = $data['table_name_en'];
        $crudInfoCopy['info'] = $data['info'];
        $crudInfoCopy['form_fields'] = json_encode($crudInfoCopy['form_fields']);
        $crudInfoCopy['created_at'] = date('Y - m - d H:i:s');
        $crudInfoCopy['updated_at'] = date('Y - m - d H:i:s');
        if ($data['cate_ids']) {
            $crudInfoCopy['cate_ids'] = ' / ' . implode(' / ', $data['cate_ids']) . ' / ';
        }
        $fieldList = $fieldService->select(['crud_id' => $id])->toArray();
        $formInfo = $formService->get(['crud_id' => $id, 'is_index' => 1]);
        $tableInfo = $tableService->get(['crud_id' => $id]);

        $tableInfoCopy = null;
        if ($tableInfo) {
            $tableInfoCopy = $tableInfo->toArray();
            unset($tableInfoCopy['id']);
            $tableInfoCopy['created_at'] = date('Y - m - d H:i:s');
            $tableInfoCopy['updated_at'] = date('Y - m - d H:i:s');
        }

        $formInfoCopy = null;
        if ($tableInfo) {
            $formInfoCopy = $formInfo->toArray();
            unset($formInfoCopy['id']);
            $formInfoCopy['created_at'] = date('Y - m - d H:i:s');
            $formInfoCopy['updated_at'] = date('Y - m - d H:i:s');
        }

        $sideTable = $crudInfoCopy['crud_id'] ? $this->dao->value($crudInfoCopy['crud_id'], 'table_name_en') : '';

        $this->transaction(function () use (
            $fieldService,
            $formService,
            $tableService,
            $tableInfoCopy,
            $formInfoCopy,
            $crudInfoCopy,
            $fieldList,
            $sideTable
        ) {
            $copyId = $this->dao->create($crudInfoCopy)->id;

            $fieldSave = [];
            $createField = [];

            foreach ($fieldList as $item) {
                $item = (array)$item;
                unset($item['id']);
                $item['crud_id'] = $copyId;
                $item['form_field_uniqid'] = uniqid($crudInfoCopy['table_name_en']);
                $item['created_at'] = date('Y - m - d H:i:s');
                $item['updated_at'] = date('Y - m - d H:i:s');
                $item['association_field_names'] = json_encode($item['association_field_names']);
                $item['options'] = json_encode($item['options']);
                $fieldSave[] = $item;
                if ($item['field_name_en'] !== $sideTable && !in_array($item['field_name_en'], ['id', 'user_id', 'update_user_id', 'frame_id', 'created_at', 'updated_at', 'deleted_at'])) {
                    $createField[] = $item;
                }
            }

            if ($fieldSave) {
                $fieldService->insert($fieldSave);
            }
            if ($tableInfoCopy) {
                $tableService->create($tableInfoCopy);
            }
            if ($formInfoCopy) {
                $formService->create($formInfoCopy);
            }

            $this->createTable(
                tableName: $crudInfoCopy['table_name_en'],
                index: (bool)$crudInfoCopy['crud_id'],
                sideTable: $sideTable,
                comment: $crudInfoCopy['table_name']
            );

            if ($createField) {
                foreach ($createField as $item) {
                    $formInfo = $this->getFormInfo($item['form_value']);

                    app()->make(SystemCrudFieldService::class)->addAlter(
                        tableName: $crudInfoCopy['table_name_en'],
                        field: $item['field_name_en'],
                        prevFiled: $item['prev_field'],
                        type: $formInfo['type'],
                        limit: $formInfo['limit'] ?: '',
                        default: (string)$formInfo['default'],
                        comment: $item['field_name'],
                        options: [
                            'default_type' => $item['is_default_value_not_null'] ? '2' : '1',
                        ]
                    );
                }
            }
        });
    }

    /**
     * @return null|array|array[][]
     * @email 136327134@qq.com
     * @date 2024/3/11
     */
    public function getFormInfo(string $value)
    {
        $formInfo = null;
        foreach (self::FORM_TYPE as $item) {
            if (!empty($item['value'])) {
                if ($item['value'] === $value) {
                    $formInfo = $item;
                    break;
                }
            } else {
                foreach ($item['options'] as $option) {
                    if ($option['value'] === $value) {
                        $formInfo = $option;
                        break;
                    }
                }
            }
        }
        return $formInfo;
    }

    /**
     * 删除表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function deleteCrudTable(int $id)
    {
        $associationCrudId = app()->get(SystemCrudFieldService::class)->value(['association_crud_id' => $id], 'crud_id');
        if ($associationCrudId) {
            throw $this->exception('请先解除"' . $this->getCrudTableNameCache((int)$associationCrudId) . '"实体中的一对一关联字段');
        }

        $this->transaction(function () use ($id) {
            $this->dao->delete($id);
            app()->get(SystemCrudFieldService::class)->delete(['crud_id' => $id]); // 删除表字段
            app()->get(SystemCrudFormService::class)->delete(['crud_id' => $id]); // 删除表字段
            app()->get(SystemCrudTableService::class)->delete(['crud_id' => $id]); // 删除表
            app()->get(SystemCrudApproveService::class)->delete(['crud_id' => $id]); // 删除审批
            app()->get(SystemCrudEventService::class)->delete(['crud_id' => $id]); // 删除触发器
            app()->get(SystemCrudEventLogService::class)->delete(['crud_id' => $id]); // 删除触发器日志
            app()->get(ApproveApplyService::class)->delete(['crud_id' => $id]); // 删除审批
            app()->get(SystemCrudQuestionnaireService::class)->delete(['crud_id' => $id]); // 删除问卷
            app()->get(SystemCrudShareService::class)->delete(['crud_id' => $id]); // 删除共享
            app()->get(SystemCrudDataShareService::class)->delete(['crud_id' => $id]); // 删除数据共享
            app()->get(SystemCrudLogService::class)->delete(['crud_id' => $id]); // 删除日志
            app()->get(SystemCrudCommentService::class)->delete(['crud_id' => $id]); // 删除评论
        });

        event('system.crud');
    }

    /**
     * 获取默认字段.
     * @return array[][]
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    public function getDefaultList(string $sideTable = '', int $crudId = 0, string $sideTableName = '')
    {
        $default = [
            [
                'field_name'                     => '主键ID',
                'field_name_en'                  => 'id',
                'form_value'                     => 'input_number',
                'field_type'                     => 'int', // 字段类型
                'is_default_value_not_null'      => false,
                'is_table_show_row'              => false,
                'comment'                        => '主键ID',
                'prev_field'                     => '',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => '', // 关联表名
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
            [
                'field_name'                     => '创建用户',
                'field_name_en'                  => 'user_id',
                'form_value'                     => 'input_select',
                'field_type'                     => 'int', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '创建用户',
                'prev_field'                     => 'id',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => 'admin', // 关联表名
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
            [
                'field_name'                     => '修改用户',
                'field_name_en'                  => 'update_user_id',
                'form_value'                     => 'input_select',
                'field_type'                     => 'int', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '修改用户',
                'prev_field'                     => 'user_id',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => 'admin', // 关联表名
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
            [
                'field_name'                     => '所属部门',
                'field_name_en'                  => 'frame_id',
                'form_value'                     => 'input_select',
                'field_type'                     => 'int', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '所属部门',
                'prev_field'                     => 'update_user_id',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => 'frame', // 关联表名
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
            [
                'field_name'                     => '所属用户',
                'field_name_en'                  => 'owner_user_id',
                'form_value'                     => 'input_select',
                'field_type'                     => 'int', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '所属用户',
                'prev_field'                     => 'frame_id',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => 'admin', // 关联表名
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
        ];

        $defaultTime = [
            [
                'field_name'                     => '创建时间',
                'field_name_en'                  => 'created_at',
                'form_value'                     => 'date_time_picker',
                'field_type'                     => 'timestamp', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '创建时间',
                'prev_field'                     => 'frame_id',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => '', // 关联表名
                'association_field_name_index'   => '',
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
            [
                'field_name'                     => '修改时间',
                'field_name_en'                  => 'updated_at',
                'form_value'                     => 'date_time_picker',
                'field_type'                     => 'timestamp', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '修改时间',
                'prev_field'                     => 'created_at',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => '', // 关联表名
                'association_field_name_index'   => '',
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
            [
                'field_name'                     => '伪删除',
                'field_name_en'                  => 'deleted_at',
                'form_value'                     => 'date_time_picker',
                'field_type'                     => 'timestamp', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '伪删除',
                'prev_field'                     => 'updated_at',
                'data_dict_id'                   => 0,
                'association_crud_id'            => 0, // 关联表ID
                'association_crud_table_name_en' => '', // 关联表名
                'association_field_name_index'   => '',
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ],
        ];

        if ($sideTable) {
            $default[0]['prev_field'] = $sideTable . '_id';
            array_unshift($default, [
                'field_name'                     => '附表' . $sideTableName,
                'field_name_en'                  => $sideTable . '_id',
                'form_value'                     => 'input_select',
                'field_type'                     => 'int', // 字段类型
                'is_default_value_not_null'      => true,
                'is_table_show_row'              => false,
                'comment'                        => '附表' . $sideTableName,
                'prev_field'                     => 'id',
                'data_dict_id'                   => 0,
                'association_crud_id'            => $crudId, // 关联表ID
                'association_crud_table_name_en' => '', // 关联表名
                'association_field_name_index'   => '',
                'association_field_names'        => [],
                'options'                        => [],
                'is_default'                     => 1,
            ]);
        }

        return [$default, $defaultTime];
    }

    /**
     * 获取附表的ID.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/13
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCrudScheduleId(int $crudId)
    {
        return $this->dao->column(['crud_id' => $crudId], 'id');
    }

    /**
     * 获取关联join查询表.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/22
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getJoinCrudData(int $crudId, int $isDefault = 0)
    {
        $association = app()->get(SystemCrudFieldService::class)->getAssociationCrudIdCache($crudId, $isDefault);
        $joinData = [];

        foreach ($association as $item) {
            if (!$item['association']) {
                continue;
            }
            $joinData[] = [
                'table_name_en' => $item['association']['table_name_en'],
                'field_name_en' => $item['field_name_en'],
            ];
        }

        return $joinData;
    }

    /**
     * 不可操作表.
     */
    public function notAllowOperateTable(): array
    {
        return array_diff(self::SYSTEM_TABLE_TABLE, self::SYSTEM_ALLOW_OPERATE_TABLE);
    }

    /**
     * 验证crud信息.
     * @return array|Model
     * @throws BindingResolutionException
     */
    public function checkCrud(string $name = '', int $crudId = 0, array $select = [])
    {
        if (!$select) {
            $select = ['crud_id', 'field_name_en', 'is_main', 'field_name',
                       'form_value', 'field_type', 'is_default', 'is_uniqid',
                       'data_dict_id', 'association_crud_id', 'is_default', 'customize_items', 'data_type', 'association_show_type'];
        }

        $crudInfo = app()->get(SystemCrudService::class)->get(
            where: $name ? ['table_name_en' => $name] : $crudId,
            with: [
                'field' => fn($q) => $q
                    ->select($select),
            ]
        );
        if (!$crudInfo) {
            throw new ApiException('没有查询到实体信息');
        }

        return $crudInfo;
    }

    /**
     * 获取crud信息缓存.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getCrudInfoCache(string $name = '', int $crudId = 0)
    {
        $select = ['crud_id', 'field_name_en', 'is_main', 'field_name',
                   'form_value', 'field_type', 'is_default', 'is_uniqid',
                   'data_dict_id', 'association_crud_id', 'is_default', 'customize_items', 'data_type', 'association_show_type'];

        return Cache::tags(self::TAG_NAME)->remember('crud_info_' . $name . '_' . $crudId . '_' . md5(json_encode($select)), null, function () use ($select, $name, $crudId) {
            return $this->checkCrud($name, $crudId, $select);
        });
    }

    /**
     * 获取下级crud信息缓存.
     * @return mixed
     */
    public function getSubordinateCrudInfoCache(int $parentId)
    {
        return Cache::tags(self::TAG_NAME)->remember('subordinate_crud_info_' . $parentId, null, function () use ($parentId) {
            return $this->dao->getSubordinateCrudInfo($parentId);
        });
    }

    /**
     * 获取crud表名缓存.
     * @return null|array|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCrudTableNameCache(int $crudId = 0)
    {
        $list = Cache::tags(self::TAG_NAME)->remember('crud_table_name_cache', null, function () {
            return $this->dao->column([], 'table_name', 'id');
        });

        return $crudId ? ($list[$crudId] ?? null) : $list;
    }

    /**
     * 获取crud英文名缓存.
     * @return null|array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCrudTableNameEnCache(int $crudId = 0)
    {
        $list = Cache::tags(self::TAG_NAME)->remember('crud_table_name_en_cache', null, function () {
            return $this->dao->column([], 'table_name_en', 'id');
        });

        return $crudId ? ($list[$crudId] ?? null) : $list;
    }

    /**
     * 通过表名获取crudid.
     * @return null|int|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function tableNameEnByCrudIdCache(string $tableNameEn)
    {
        $crudId = null;
        $list   = $this->getCrudTableNameEnCache();

        foreach ((array) $list as $id => $tableName) {
            if ($tableName === $tableNameEn) {
                $crudId = $id;
                break;
            }
        }

        return $crudId;
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getDictDataList(int $id)
    {
        $dictIds = app()->make(SystemCrudFieldService::class)->getDataDictIds($id);
        $dictType = [];
        if ($dictIds) {
            $dictType = app()->make(DictTypeService::class)->getModel()->whereIn('id', $dictIds)->get()->toArray();
        }
        return $dictType;
    }

    /**
     * 批量保存字典数据.
     * @throws BindingResolutionException
     */
    public function batchDictData(int $dictId, array $data)
    {
        $typeName = app()->make(DictTypeService::class)->getModel()->where('id', $dictId)->value('ident');

        $this->saveChildren($data, $dictId, 0, $typeName);

        Cache::tags([CacheEnum::TAG_DICT, CacheEnum::TAG_CUSTOMER])->flush();
    }

    /**
     * 保存子级字典数据.
     * @param array $data
     * @param $dictId
     * @param int $parentId
     * @param $typeName
     * @return void
     * @throws BindingResolutionException
     */
    public function saveChildren(array $data, $dictId, int $parentId, $typeName)
    {
        $dictDataService = app()->make(DictDataService::class);

        foreach ($data as $item) {
            if (isset($item['id']) && $item['id']) {
                $dictDataService->update($item['id'], [
                    'type_id' => $dictId,
                    'name'    => $item['name'],
                    'value'   => $item['value'],
                    'sort'    => $item['sort'] ?? 0,
                    'color'   => $item['color'] ?? '',
                    'status'  => $item['status'] ?? '',
                    'mark'    => $item['mark'] ?? '',
                ]);
                $id = $item['id'];
            } else {
                $res = $dictDataService->create([
                    'type_id'   => $dictId,
                    'pid'       => $parentId,
                    'name'      => $item['name'],
                    'type_name' => $typeName,
                    'value'     => $item['value'],
                    'sort'      => $item['sort'] ?? 0,
                    'color'     => $item['color'] ?? '',
                    'status'    => $item['status'] ?? '',
                    'mark'      => $item['mark'] ?? '',
                ]);
                $id = $res->id;
            }

            if (!empty($item['children'])) {
                $this->saveChildren($item['children'], $dictId, $id, $typeName);
            }
        }

    }

    /**
     * 清除缓存.
     * @return bool
     */
    public function clearCache()
    {
        Cache::tags('event')->clear();
        Cache::tags(self::TAG_NAME)->clear();
    }
}
