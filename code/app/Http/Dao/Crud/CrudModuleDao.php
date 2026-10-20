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

namespace App\Http\Dao\Crud;

use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudOperatorEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Frame\Frame;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 虚拟模型
 * Class CrudModuleDao.
 * @email 136327134@qq.com
 * @date 2024/4/9
 */
class CrudModuleDao extends BaseDao
{
    use TogetherSearchTrait;
    use BatchSearchTrait;

    protected string $tableName = '';

    /**
     * 关联表数据.
     */
    protected array $join = [];

    protected string $alias;

    protected bool $isWithTrashed = false;

    /**
     * @var string[]
     */
    protected $notDeleteTableName = ['frame', 'client_bill', 'client_invoice', 'customer_record', 'client_subscribe', 'bill_list', 'bill_category'];

    protected $notCreatedAtTableName = ['bill_category'];

    protected $systemCustomer = ['customer', 'contract'];

    /**
     * 设置表名.
     * @return $this
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * 是否需要查询伪删除.
     * @return $this
     * @email 136327134@qq.com
     * @date 2024/4/26
     */
    public function withTrashed(bool $isWithTrashed = true)
    {
        $this->isWithTrashed = $isWithTrashed;
        return $this;
    }

    /**
     * @return $this
     * @email 136327134@qq.com
     * @date 2024/3/22
     */
    public function setJoin(array $join)
    {
        $this->join = $join;
        return $this;
    }

    /**
     * 是否链表.
     * @return bool
     * @email 136327134@qq.com
     * @date 2024/3/26
     */
    public function isJoin()
    {
        return count($this->join) > 0;
    }

    /**
     * 获取模型.
     * @return BaseModel
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    public function getModel(bool $need = true)
    {
        if (in_array($this->tableName, $this->notDeleteTableName)) {
            $model = new class extends BaseModel {
                protected $primaryKey = 'id';

                /**
                 * 创建用户.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function createUser()
                {
                    return $this->hasOne(Admin::class, 'id', 'user_id');
                }

                /**
                 * 更新用户.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function updateUser()
                {
                    return $this->hasOne(Admin::class, 'id', 'update_user_id');
                }

                /**
                 * 所属用户.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function ownerUser()
                {
                    return $this->hasOne(Admin::class, 'id', 'owner_user_id');
                }

                /**
                 * 所属部门.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function ownerFrame()
                {
                    return $this->hasOne(Frame::class, 'id', 'frame_id');
                }
            };

            $model->setTable($this->tableName);
        } else {
            $model = new class extends BaseModel {
                use SoftDeletes;

                protected $primaryKey = 'id';

                /**
                 * 创建用户.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function createUser()
                {
                    return $this->hasOne(Admin::class, 'id', 'user_id');
                }

                /**
                 * 更新用户.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function updateUser()
                {
                    return $this->hasOne(Admin::class, 'id', 'update_user_id');
                }

                /**
                 * 所属用户.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function ownerUser()
                {
                    return $this->hasOne(Admin::class, 'id', 'owner_user_id');
                }

                /**
                 * 所属部门.
                 * @return HasOne
                 * @email 136327134@qq.com
                 * @date 2024/3/12
                 */
                public function ownerFrame()
                {
                    return $this->hasOne(Frame::class, 'id', 'frame_id');
                }
            };

            $model->setTable($this->tableName)->withTrashed($this->isWithTrashed);
        }

        if (in_array($this->tableName, $this->notCreatedAtTableName)) {
            $model->timestamps = false;
        }

        // 时间字段
        if ($this->timeField) {
            $model->setTimeField($this->timeField);
        }
        // 默认条件
        if ($this->defaultWhere && $need) {
            $model = $model->where($this->getDefaultWhereValue());
        }

        // 设置链表
        if ($this->join) {
            $this->alias = $this->tableName;

            foreach ($this->join as $item) {
                $model = $model->join($item['table_name_en'], $this->alias . '.' . $item['field_name_en'], '=', $item['table_name_en'] . '.' . ($item['join_field_name_en'] ?? 'id'), $item['join'] ?? 'left');
            }
        }

        return $model;
    }

    /**
     * 默认搜索.
     * @param array|int|string $where
     * @return BaseModel|Builder
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    public function search($where, ?bool $authWhere = null)
    {
        if (is_array($where)) {
            $where = array_filter($where, function ($v) {
                return ! (is_null($v) || $v === '');
            });
        }
        $where = $this->handleWhere($where);
        $model = $this->getModel(false)->where($where);

        if ($this->defaultWhere) {
            $model = $model->where($this->getDefaultWhereValue());
        }
        if ($this->trashed) {
            $model = $model->withTrashed();
        }
        if ($this->onlyTrashed) {
            $model = $model->onlyTrashed();
        }
        return $model;
    }

    /**
     * 链表查询模型获取.
     * @return BaseModel|__anonymous@1802
     * @email 136327134@qq.com
     * @date 2024/3/21
     */
    public function joinModel(array $join)
    {
        $model = $this->getModel(false);

        $alias = $model->getTable();

        foreach ($join as $item) {
            $model = $model->join($item['table_name_en'], $alias . '.' . $item['field_name_en'], '=', $item['table_name_en'] . '.id', 'left');
        }

        return $model;
    }

    /**
     * 条件搜索.
     * @param null $orderBy
     * @param null $model
     * @return Builder
     * @email 136327134@qq.com
     * @date 2024/3/11
     */
    public function searchWhere(array $defaultWhere = [], array $where = [], array $viewSearch = [], $orderBy = null, string $boolean = 'and', string $defaulAlias = '', $model = null)
    {
        if (is_null($model)) {
            $model = $this->getModel(false);
        }

        if ($this->join && ! $defaulAlias) {
            $defaulAlias = $this->alias;
        }

        $defaulAlias = $defaulAlias ? $defaulAlias . '.' : '';

        $model = $model->when(! empty($defaultWhere['show_search_type']), function ($query) use ($defaultWhere, $defaulAlias) {
            switch ($defaultWhere['show_search_type']) {
                case 1:
                    $query->where($defaulAlias . 'owner_user_id', $defaultWhere['uid']);
                    break;
                case 2:
                    $query->where($defaulAlias . 'user_id', $defaultWhere['uid']);
                    break;
            }
        })->when($orderBy, function ($q) use ($orderBy, $defaulAlias) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $k => $v) {
                    if (is_numeric($k)) {
                        $q->orderByDesc($defaulAlias . $v);
                    } else {
                        $q->orderBy($defaulAlias . $k, $v);
                    }
                }
            } else {
                $q->orderByDesc($defaulAlias . $orderBy);
            }
        })->when(! empty($defaultWhere['share_ids']) && empty($defaultWhere['user_id']), fn ($q) => $q->whereIn($defaulAlias . 'id', $defaultWhere['share_ids']))
            ->when(! empty($defaultWhere['user_id']) && empty($defaultWhere['share_ids']), fn ($q) => $q->whereIn($defaulAlias . 'user_id', $defaultWhere['user_id']))
            ->when(! empty($defaultWhere['user_id']) && ! empty($defaultWhere['share_ids']), fn ($q) => $q->where(fn ($qq) => $qq->whereIn($defaulAlias . 'id', $defaultWhere['share_ids'])->orWhereIn($defaulAlias . 'user_id', $defaultWhere['user_id'])));

        $likeWhere = [];
        foreach ($where as $item) {
            if ($item['value'] === '' || $item['value'] === null) {
                continue;
            }

            $alias = $item['alias'] ?? $defaulAlias;

            $fieldName = ($alias ?: '') . $item['field_name'];

            switch ($item['form_value']) {
                case CrudFormEnum::FORM_SWITCH:
                case CrudFormEnum::FORM_RADIO:
                case CrudFormEnum::FORM_INPUT_SELECT:
                    $model = $model->where($fieldName, $item['value']);
                    break;
                case CrudFormEnum::FORM_INPUT_NUMBER:
                case CrudFormEnum::FORM_INPUT_FLOAT:
                case CrudFormEnum::FORM_INPUT_PERCENTAGE:
                case CrudFormEnum::FORM_INPUT_PRICE:
                case CrudFormEnum::FORM_INPUT:
                case CrudFormEnum::FORM_TEXTAREA:
                case CrudFormEnum::FORM_IMAGE:
                case CrudFormEnum::FORM_FILE:
                case CrudFormEnum::FORM_RICH_TEXT:
                    // 需要进行多个字段进行or like 查询
                    $likeWhere[] = $item;
                    break;
                case CrudFormEnum::FORM_TAG:
                case CrudFormEnum::FORM_CHECKBOX:
                case CrudFormEnum::FORM_CASCADER_RADIO:
                case CrudFormEnum::FORM_CASCADER_ADDRESS:
                case CrudFormEnum::FORM_CASCADER:
                    $model = $model->where(function ($query) use ($item, $fieldName) {
                        foreach ($item['value'] as $i => $v) {
                            if ($i) {
                                $query->orWhere($fieldName, 'like', '%/' . implode('/', $v) . '/%');
                            } else {
                                $query->where($fieldName, 'like', '%/' . implode('/', $v) . '/%');
                            }
                        }
                    });
                    break;
                case CrudFormEnum::FORM_DATE_TIME_PICKER:
                case CrudFormEnum::FORM_DATE_PICKER:
                    $model = $this->scopeTime($model, $item['value'], $fieldName);
                    break;
            }
        }
        if ($likeWhere) {
            $model = $model->where(function ($query) use ($likeWhere, $defaulAlias) {
                foreach ($likeWhere as $i => $item) {
                    $alias = $item['alias'] ?? trim($defaulAlias, '.');

                    $fieldName = ($alias ? $alias . '.' : '') . $item['field_name'];

                    if (is_array($item['value'])) {
                        $item['value'] = json_encode($item['value']);
                    }

                    if ($item['value'] !== '') {
                        if ($i) {
                            $query->orWhere($fieldName, 'like', '%' . $item['value'] . '%');
                        } else {
                            $query->where($fieldName, 'like', '%' . $item['value'] . '%');
                        }
                    }
                }
            });
        }

        if ($viewSearch) {
            $model = $this->viewSearch($viewSearch, $model, $boolean, $defaulAlias);
        }

        return $model;
    }

    /**
     * 高级视图搜索.
     * @param null $query
     * @return BaseModel|__anonymous@1802
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    public function viewSearch(array $viewSearch = [], $query = null, string $boolean = 'and', string $defaulAlias = '')
    {
        if (is_null($query)) {
            $query = $this->getModel(false);
        }
        if ($this->join && ! $defaulAlias) {
            $defaulAlias = $this->alias;
        }
        return $query->where(function (Builder $query) use ($viewSearch, $boolean, $defaulAlias) {
            foreach ($viewSearch as $search) {
                if (empty($search['field_name']) || empty($search['operator'])) {
                    continue;
                }

                if (! isset($search['value'])) {
                    $search['value'] = '';
                }

                if (strstr($search['field_name'], '.') !== false) {
                    $fieldName = $search['field_name'];
                } else {
                    $alias     = $item['alias'] ?? trim($defaulAlias, '.');
                    $fieldName = ($alias ? $alias . '.' : '') . $search['field_name'];
                }

                switch ($search['operator']) {
                    case CrudOperatorEnum::OPERATOR_IN:
                        if (isset($search['form_value'])) {
                            switch ($search['form_value']) {
                                case CrudFormEnum::FORM_IMAGE:
                                case CrudFormEnum::FORM_FILE:
                                    $search['value'] = json_encode($search['value']);
                                    $query           = $query->where($fieldName, 'LIKE', '%' . $search['value'] . '%', $boolean);
                                    break;
                                case CrudFormEnum::FORM_CASCADER:
                                    $model = $model->where(function ($query) use ($search, $fieldName) {
                                        foreach ($search['value'] as $i => $v) {
                                            if ($i) {
                                                $query->orWhere($fieldName, 'like', '%/' . implode('/', $v) . '/%');
                                            } else {
                                                $query->where($fieldName, 'like', '%/' . implode('/', $v) . '/%');
                                            }
                                        }
                                    }, null, null, $boolean);
                                    break;
                                case CrudFormEnum::FORM_RICH_TEXT:
                                    $query = $query->where($fieldName, 'LIKE', '%' . $search['value'] . '%', $boolean);
                                    break;
                                case CrudFormEnum::FORM_RADIO:
                                    $query = is_array($search['value']) ? $query->whereIn($fieldName, $search['value'], $boolean) : $query->where($fieldName, null, $search['value'], $boolean);
                                    break;
                                case CrudFormEnum::FORM_INPUT:
                                case CrudFormEnum::FORM_TEXTAREA:
                                    if (is_array($search['value'])) {
                                        $search['value'] = json_encode($search['value']);
                                    }
                                    $query = $query->where($fieldName, 'LIKE', '%' . $search['value'] . '%', $boolean);
                                    break;
                                case CrudFormEnum::FORM_TAG:
                                case CrudFormEnum::FORM_CHECKBOX:
                                case CrudFormEnum::FORM_CASCADER_ADDRESS:
                                    $tags  = is_array($search['value']) ? $search['value'] : explode(',', $search['value']);
                                    $query = $query->where(function ($query) use ($tags, $fieldName) {
                                        if (in_array($this->tableName, $this->systemCustomer)) {
                                            $tablePrefix = config('database.connections.mysql.prefix');
                                            $tags        = array_merge(array_unique($tags));
                                            foreach ($tags as $tag) {
                                                $query->orWhereRaw('JSON_CONTAINS(' . $tablePrefix . $fieldName . ', ?,"$")', is_int($tag) ? ['"' . $tag . '"'] : ['"' . end($tag) . '"']);
                                            }
                                        } else {
                                            foreach ($tags as $i => $tag) {
                                                if ($i) {
                                                    $query->orWhere($fieldName, 'like', '%/' . $tag . '/%');
                                                } else {
                                                    $query->where($fieldName, 'like', '%/' . $tag . '/%');
                                                }
                                            }
                                        }
                                    }, null, null, $boolean);
                                    break;
                                case CrudFormEnum::FORM_CASCADER_RADIO:
                                    $query = $query->where(function ($query) use ($search, $fieldName) {
                                        $vv = [];
                                        foreach ($search['value'] as $i => $val) {
                                            if (in_array($this->tableName, $this->systemCustomer)) {
                                                $endVal = is_array($val) ? end($val) : $val;
                                                if (in_array($endVal, $vv)) {
                                                    continue;
                                                }
                                                $vv[]        = $endVal;
                                                $tablePrefix = config('database.connections.mysql.prefix');
                                                $query->orWhereRaw('JSON_CONTAINS(' . $tablePrefix . $fieldName . ', ?,"$")', ['"' . $endVal . '"']);
                                            } else {
                                                $val = implode('/', $val);
                                                if ($i) {
                                                    $query->orWhere($fieldName, 'like', '%/' . $val . '/%');
                                                } else {
                                                    $query->where($fieldName, 'like', '%/' . $val . '/%');
                                                }
                                            }
                                        }
                                    }, null, null, $boolean);
                                    break;
                                default:
                                    $query = $query->whereIn($fieldName, is_array($search['value']) ? $search['value'] : [$search['value']], $boolean);
                                    break;
                            }
                        } else {
                            $query = $query->whereIn($fieldName, is_array($search['value']) ? $search['value'] : [$search['value']], $boolean);
                        }
                        break;
                    case CrudOperatorEnum::OPERATOR_NOT_IN:
                        if (isset($search['form_value'])) {
                            switch ($search['form_value']) {
                                case CrudFormEnum::FORM_IMAGE:
                                case CrudFormEnum::FORM_FILE:
                                    $search['value'] = json_encode($search['value']);
                                    $query           = $query->whereNot($fieldName, 'LIKE', '%' . $search['value'] . '%', $boolean);
                                    break;
                                case CrudFormEnum::FORM_CASCADER:
                                    $model = $model->where(function ($query) use ($search, $fieldName) {
                                        $vv = [];
                                        foreach ($search['value'] as $i => $v) {
                                            if (in_array($this->tableName, $this->systemCustomer)) {
                                                $endVal = end($v);
                                                if (in_array($endVal, $vv)) {
                                                    continue;
                                                }
                                                $vv[]        = $endVal;
                                                $tablePrefix = config('database.connections.mysql.prefix');
                                                //                                                $query->orWhereRaw('JSON_CONTAINS(' . $tablePrefix . $fieldName . ', ?,"$")', ['"' . $endVal . '"']);
                                                $query->orWhereJsonContains($tablePrefix . $fieldName, (string) $endVal);
                                            } else {
                                                if ($i) {
                                                    $query->orWhereNot($fieldName, 'like', '%/' . implode('/', $v) . '/%');
                                                } else {
                                                    $query->whereNot($fieldName, 'like', '%/' . implode('/', $v) . '/%');
                                                }
                                            }
                                        }
                                    }, null, null, $boolean);
                                    break;
                                case CrudFormEnum::FORM_RICH_TEXT:
                                    $query = $query->whereNot($fieldName, 'LIKE', '%' . $search['value'] . '%', $boolean);
                                    break;
                                case CrudFormEnum::FORM_RADIO:
                                    $query = is_array($search['value']) ? $query->whereNotIn($fieldName, $search['value'], $boolean) : $query->whereNot($fieldName, null, $search['value'], $boolean);
                                    // no break
                                case CrudFormEnum::FORM_INPUT:
                                case CrudFormEnum::FORM_TEXTAREA:
                                    if (is_array($search['value'])) {
                                        $search['value'] = json_encode($search['value']);
                                    }
                                    $query = $query->whereNot($fieldName, 'LIKE', '%' . $search['value'] . '%', $boolean);
                                    break;
                                case CrudFormEnum::FORM_CASCADER_RADIO:
                                    $query = $query->whereNot(function ($query) use ($search, $fieldName) {
                                        $vv = [];
                                        foreach ($search['value'] as $i => $val) {
                                            if (in_array($this->tableName, $this->systemCustomer)) {
                                                $endVal = end($v);
                                                if (in_array($endVal, $vv)) {
                                                    continue;
                                                }
                                                $vv[]        = $endVal;
                                                $tablePrefix = config('database.connections.mysql.prefix');
                                                $query->orWhereRaw('JSON_CONTAINS(' . $tablePrefix . $fieldName . ', ?,"$")', ['"' . $endVal . '"']);
                                            } else {
                                                $val = implode('/', $val);
                                                if ($i) {
                                                    $query->orWhere($fieldName, 'like', '%/' . $val . '/%');
                                                } else {
                                                    $query->where($fieldName, 'like', '%/' . $val . '/%');
                                                }
                                            }
                                        }
                                    }, null, null, $boolean);
                                    break;
                                case CrudFormEnum::FORM_TAG:
                                case CrudFormEnum::FORM_CHECKBOX:
                                case CrudFormEnum::FORM_CASCADER_ADDRESS:
                                    $tags  = is_array($search['value']) ? $search['value'] : explode(',', $search['value']);
                                    $query = $query->whereNot(function ($query) use ($tags, $fieldName) {
                                        $vv = [];
                                        foreach ($tags as $i => $tag) {
                                            if (in_array($this->tableName, $this->systemCustomer)) {
                                                $endVal = end($tag);
                                                if (in_array($endVal, $vv)) {
                                                    continue;
                                                }
                                                $vv[]        = $endVal;
                                                $tablePrefix = config('database.connections.mysql.prefix');
                                                $query->orWhereRaw('JSON_CONTAINS(' . $tablePrefix . $fieldName . ', ?,"$")', ['"' . $endVal . '"']);
                                            } else {
                                                if ($i) {
                                                    $query->orWhere($fieldName, 'like', '%/' . $tag . '/%');
                                                } else {
                                                    $query->where($fieldName, 'like', '%/' . $tag . '/%');
                                                }
                                            }
                                        }
                                    }, null, null, $boolean);
                                    break;
                                default:
                                    $query = $query->whereNotIn($fieldName, is_array($search['value']) ? $search['value'] : [$search['value']], $boolean);
                                    break;
                            }
                        } else {
                            $query = $query->whereNotIn($fieldName, is_array($search['value']) ? $search['value'] : [$search['value']], $boolean);
                        }
                        break;
                    case CrudOperatorEnum::OPERATOR_EQ:
                        $query = $query->where($fieldName, '=', $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_GT:
                        $query = $query->where($fieldName, '>', $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_GT_EQ:
                        $query = $query->where($fieldName, '>=', $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_LT:
                        $query = $query->where($fieldName, '<', $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_LT_EQ:
                        $query = $query->where($fieldName, '<=', $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_NOT_EQ:
                        $query = $query->where($fieldName, '<>', $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_IS_EMPTY:
                        $query = $query->whereNull($fieldName, $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_NOT_EMPTY:
                        $query = $query->whereNotNull($fieldName, $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_BT:
                        if (is_string($search['value']) && str_contains($search['value'], '-')) {
                            [$startTime, $endTime] = explode('-', $search['value']);
                            $startTime             = str_replace('/', '-', trim($startTime));
                            $endTime               = str_replace('/', '-', trim($endTime));
                            $search['value']       = [$startTime, $endTime];
                        }
                        $query = $query->whereBetween($fieldName, $search['value'], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_N_DAY:
                        $query = $query->where($fieldName, '>', Carbon::today()->subDays((int) $search['value'])->toDateTimeString(), $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_LAST_DAY:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->subDays((int) $search['value'])->toDateTimeString(), Carbon::today()->toDateTimeString()], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_NEXT_DAY:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->toDateTimeString(), Carbon::today()->addDays((int) $search['value'])->toDateTimeString()], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_TO_DAY:
                        $query = $query->whereDate($fieldName, '=', Carbon::today()->toDateString(), $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_WEEK:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->startOfWeek()->toDateTimeString(), Carbon::today()->endOfWeek()->toDateTimeString()], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_MONTH:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->startOfMonth()->toDateTimeString(), Carbon::today()->endOfMonth()->toDateTimeString()], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_QUARTER:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->startOfQuarter()->toDateTimeString(), Carbon::today()->endOfQuarter()->toDateTimeString()], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_LAST_YEAR:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->subYear()->startOfYear()->toDateTimeString(), Carbon::today()->subYear()->endOfYear()->toDateTimeString()], $boolean);
                        break;
                    case CrudOperatorEnum::OPERATOR_YEAR:
                        $query = $query->whereBetween($fieldName, [Carbon::today()->startOfYear()->toDateTimeString(), Carbon::today()->endOfYear()->toDateTimeString()], $boolean);
                        break;
                }
            }
        });
    }

    /**
     * 时间查询作用域
     *
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeTime($query, $value, string $createTimeField)
    {
        switch ($value) {
            case 'today':// 今天
                return $query->whereDate($createTimeField, Carbon::today()->toDateString());
            case 'week':// 本周
                return $query->whereBetween($createTimeField, [Carbon::today()->startOfWeek()->toDateTimeString(), Carbon::today()->endOfWeek()->toDateTimeString()]);
            case 'month':// 本月
                return $query->whereBetween($createTimeField, [Carbon::today()->startOfMonth()->toDateTimeString(), Carbon::today()->endOfMonth()->toDateTimeString()]);
            case 'year':// 今年
                return $query->whereBetween($createTimeField, [Carbon::today()->startOfYear()->toDateTimeString(), Carbon::today()->endOfYear()->toDateTimeString()]);
            case 'yesterday':// 昨天
                return $query->whereDate($createTimeField, Carbon::yesterday()->toDateString());
            case 'last year':// 去年
                return $query->whereDate($createTimeField, Carbon::today()->subYear()->year);
            case 'last week':// 上周
                return $query->whereBetween($createTimeField, [Carbon::today()->subWeek()->startOfWeek()->toDateTimeString(), Carbon::today()->subWeek()->endOfWeek()->toDateTimeString()]);
            case 'last month':// 上个月
                return $query->whereBetween($createTimeField, [Carbon::today()->subMonth()->startOfMonth()->toDateTimeString(), Carbon::today()->subMonth()->endOfMonth()->toDateTimeString()]);
            case 'quarter':// 本季度
                return $query->whereBetween($createTimeField, [Carbon::today()->startOfQuarter()->toDateTimeString(), Carbon::today()->endOfQuarter()->toDateTimeString()]);
            case 'lately7':// 近7天
                return $query->whereBetween($createTimeField, [Carbon::today()->subDays(7)->toDateTimeString(), Carbon::today()->toDateTimeString()]);
            case 'lately30':
                return $query->whereBetween($createTimeField, [Carbon::today()->subDays(30)->toDateTimeString(), Carbon::today()->toDateTimeString()]);
            case 'future30':
                return $query->whereBetween($createTimeField, [Carbon::today()->toDateTimeString(), Carbon::today()->addDays(30)->toDateTimeString()]);
            default:
                if (is_array($value)) {
                    $value = implode('-', $value);
                }
                if (str_contains($value, '-')) {
                    [$startTime, $endTime] = explode('-', $value);
                    $startTime             = str_replace('/', '-', trim($startTime));
                    $endTime               = str_replace('/', '-', trim($endTime));
                    if (! str_contains($startTime, ':') && ! str_contains($endTime, ':')) {
                        $endDate = Carbon::parse($endTime)->timezone(config('app.timezone'))->addDay()->toDateString();
                        return $query->whereDate($createTimeField, '>=', $startTime)->whereDate($createTimeField, '<', $endDate);
                    }
                    if ($startTime && $endTime && $startTime != $endTime) {
                        return $query->whereBetween($createTimeField, [$startTime, $endTime]);
                    }
                    if ($startTime && $endTime && $startTime == $endTime) {
                        return $query->whereBetween($createTimeField, [$startTime, date('Y-m-d H:i:s', datetime_timestamp($endTime) + 86400)]);
                    }
                    if (! $startTime && $endTime) {
                        return $query->whereTime($createTimeField, '<', $endTime);
                    }
                    if ($startTime && ! $endTime) {
                        return $query->whereTime($createTimeField, '>=', $startTime);
                    }
                } elseif (preg_match('/^lately+[1-9]{1,3}/', $value)) {
                    // 最近天数 lately[1-9] 任意天数
                    $day = (int) str_replace('lately', '', $value);
                    if ($day) {
                        return $query->whereBetween($createTimeField, [Carbon::today()->subDays($day)->toDateTimeString(), Carbon::today()->toDateTimeString()]);
                    }
                }
        }

        return $query;
    }

    /**
     * 重写修改数据.
     * @email 136327134@qq.com
     * @date 2024/4/10
     * @param mixed $where
     * @return bool|int
     * @throws BindingResolutionException
     */
    public function update($where, array $data)
    {
        return $this->search($where)->update($data);
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    protected function setModel()
    {
        return null;
    }
}
