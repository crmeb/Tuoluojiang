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

namespace App\Http\Dao;

use App\Constants\DataPermissionLevelEnum;
use App\Constants\ModuleEnum;
use App\Http\Context\DataPermissionContext;
use App\Http\Model\BaseModel;
use App\Http\Service\System\ModulePermissionService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 基础Dao
 * Class BaseDao.
 */
abstract class BaseDao
{
    /**
     * 表名.
     * @var string
     */
    protected $table;

    /**
     * 局部作用域没有自动进入 where搜索.
     * @var bool
     */
    protected $authWhere = true;

    /**
     * 默认条件.
     * @var array
     */
    protected $defaultWhere = [];

    /**
     * 时间作用域字段.
     * @var string
     */
    protected $timeField;

    /**
     * 是否查询软删除.
     * @var bool
     */
    protected $trashed = false;

    /**
     * 仅查询软删除.
     * @var bool
     */
    protected $onlyTrashed = false;

    /**
     * 默认排序.
     * @var string
     */
    protected $defaultSort = '';

    /**
     * 是否启用自动数据权限过滤.
     * 默认为 true，会自动根据请求上下文中的权限配置注入 uid 过滤条件
     * 设置为 false 可禁用特定 DAO 的自动过滤
     * @var bool
     */
    protected $autoAuthWhere = true;

    /**
     * 设置默认查询条件.
     * @return $this
     */
    public function setDefaultWhere(array $where)
    {
        $this->defaultWhere = $where;
        return $this;
    }

    /**
     * 设置默认排序.
     * @param array|string $sort
     * @return $this
     */
    public function setDefaultSort($sort)
    {
        $this->defaultSort = $sort;
        return $this;
    }

    /**
     * 设置查询软删除.
     * @return $this
     */
    public function setTrashed(bool $trashed = true)
    {
        $this->trashed = $trashed;
        return $this;
    }

    /**
     * 设置查询软删除.
     * @return $this
     */
    public function setOnlyTrashed()
    {
        $this->onlyTrashed = true;
        return $this;
    }

    /**
     * 设置自动进入where搜索开关.
     * @return $this
     */
    public function authWhere(bool $authWhere)
    {
        $this->authWhere = $authWhere;
        return $this;
    }

    /**
     * 设置自动数据权限过滤开关.
     * 当启用时，会自动根据请求上下文中的权限配置注入 uid 过滤条件
     * @return $this
     */
    public function autoAuthWhere(bool $autoAuthWhere)
    {
        $this->autoAuthWhere = $autoAuthWhere;
        return $this;
    }

    /**
     * 时间段搜索字段.
     * @return $this
     */
    public function setTimeField(string $timeField)
    {
        $this->timeField = $timeField;
        return $this;
    }

    /**
     * 获取模型.
     * @return BaseModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getModel(bool $need = true)
    {
        /** @var BaseModel $model */
        $model = app()->get($this->setModel());
        // 时间字段
        if ($this->timeField) {
            $model->setTimeField($this->timeField);
        }
        // 默认条件
        if ($this->defaultWhere && $need) {
            $model = $model->where($this->getDefaultWhereValue());
        }

        return $model;
    }

    /**
     * 获取表名.
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getTable()
    {
        return $this->getModel(false)->getTable();
    }

    /**
     * 搜索.
     * @param array|int|string $where where条件
     * @param bool $authWhere 不再局部作用域之内的自动进入where条件
     * @return BaseModel
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        if (is_array($where)) {
            $where = array_filter($where, function ($v) {
                return ! (is_null($v) || $v === '');
            });
        }
        $where = $this->handleWhere($where);

        [$with, $newWhere] = $this->getScope($where, $this->setModel());

        $where = collect($where)->only($with)->all();

        $model = $this->withSearch($this->getModel(false), $with, $where);

        if ($authWhere === null) {
            $authWhere = $this->authWhere;
        }

        if ($authWhere && $newWhere) {
            $model = $model->where($newWhere);
        }

        if ($this->defaultWhere) {
            $model = $model->where($this->getDefaultWhereValue());
        }
        // 自动注入数据权限过滤条件
        if ($this->autoAuthWhere) {
            $model = $this->applyAutoAuthWhere($model);
        }
        if ($this->trashed) {
            $model         = $model->withTrashed();
            $this->trashed = false;
        }
        if ($this->onlyTrashed) {
            $model             = $model->onlyTrashed();
            $this->onlyTrashed = false;
        }
        return $model;
    }

    /**
     * 应用自动数据权限过滤
     * 支持主表和关联表的权限过滤
     * @param BaseModel $model
     * @return BaseModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function applyAutoAuthWhere($model)
    {
        // 获取当前 Model 的表名
        $table = $model instanceof \Illuminate\Database\Eloquent\Builder ? $model->getModel()->getTable() : $model->getTable();
        // 检查表是否属于内置模块
        $module = ModuleEnum::getModuleByTable($table);
        if (! $module) {
            // 检查是否是关联表
            foreach (ModuleEnum::all() as $mod => $name) {
                $config = ModuleEnum::getModuleFieldConfig()[$mod];
                $joins = $config['joins'] ?? [];
                if (isset($joins[$table])) {
                    $module = $mod;
                    // 关联表需要特殊处理
                    return $this->applyJoinTablePermission($model, $table, $module, $joins[$table]);
                }
            }

            return $model;
        }

        // 如果权限上下文未启用，自动初始化
        if (! DataPermissionContext::isEnabled()) {
            $this->initDataPermissionContext($module);
        }

        // 再次检查上下文是否匹配当前模块
        $contextModule = DataPermissionContext::getModule();
        if ($contextModule !== $module) {
            return $model;
        }

        $dataLevel = DataPermissionContext::getDataLevel();
        if ($dataLevel === DataPermissionLevelEnum::ALL) {
            return $model;
        }
        if ($dataLevel === DataPermissionLevelEnum::NONE) {
            return $model->whereRaw('0 = 1');
        }

        $uids = DataPermissionContext::getUids();
        if (empty($uids)) {
            return $model->whereRaw('0 = 1');
        }

        // 获取该模块的字段配置（支持多字段）
        $config = ModuleEnum::getModuleFieldConfig()[$module] ?? null;
        if (! $config) {
            return $model;
        }

        // 注入 uid 过滤条件，支持多字段（OR条件）
        $fields     = $config['fields'] ?? [$config['field']];
        $jsonFields = $config['json_fields'] ?? [];

        if (empty($jsonFields)) {
            // 无JSON字段时保持原有逻辑
            if (count($fields) === 1) {
                // 单字段优化
                return $model->whereIn($fields[0], $uids);
            }
            // 多字段使用OR连接
            return $model->where(function ($query) use ($fields, $uids) {
                foreach ($fields as $field) {
                    $query->orWhereIn($field, $uids);
                }
            });
        }

        // 有JSON字段时，整合处理（uid字段和JSON字段通过OR连接）
        return $model->where(function ($query) use ($fields, $uids, $jsonFields) {
            // 普通uid字段：使用whereIn
            foreach ($fields as $i => $field) {
                if ($i === 0) {
                    $query->whereIn($field, $uids);
                } else {
                    $query->orWhereIn($field, $uids);
                }
            }
            // JSON字段：使用whereJsonContains一次检查所有uid（如 member 字段，JSON数组类型）
            // MySQL JSON_CONTAINS(doc, '[1,2,3]') 检查数组中是否包含任一元素
            foreach ($jsonFields as $jsonField) {
                $query->orWhereJsonContains($jsonField, $uids);
            }
        });
    }

    /**
     * 应用关联表权限过滤
     *
     * @param mixed $model 查询构建器
     * @param string $joinTable 关联表名
     * @param string $module 模块标识
     * @param array $joinConfig 关联配置
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function applyJoinTablePermission($model, string $joinTable, string $module, array $joinConfig)
    {
        // 如果权限上下文未启用，自动初始化
        if (! DataPermissionContext::isEnabled()) {
            $this->initDataPermissionContext($module);
        }
        // 检查上下文是否匹配当前模块
        if (DataPermissionContext::getModule() !== $module) {
            return $model;
        }
        $dataLevel = DataPermissionContext::getDataLevel();
        if ($dataLevel === DataPermissionLevelEnum::ALL) {
            return $model;
        }
        if ($dataLevel === DataPermissionLevelEnum::NONE) {
            return $model->whereRaw('0 = 1');
        }

        $uids = DataPermissionContext::getUids();
        if (empty($uids)) {
            return $model->whereRaw('0 = 1');
        }

        // 获取主表信息
        $config   = ModuleEnum::getModuleFieldConfig()[$module];
        $mainTable = $config['table'];
        $fields     = $config['fields'] ?? [$config['field']];
        $jsonFields = $config['json_fields'] ?? [];
        $directFields = $joinConfig['direct_fields'] ?? [];

        $applyDirectFields = function ($query) use ($directFields, $uids) {
            $query->where(function ($subQuery) use ($directFields, $uids) {
                foreach ($directFields as $i => $field) {
                    if ($i === 0) {
                        $subQuery->whereIn($field, $uids);
                    } else {
                        $subQuery->orWhereIn($field, $uids);
                    }
                }
            });
        };

        // 解析ON条件
        $onCondition = $joinConfig['on'] ?? [];
        if (count($onCondition) < 3) {
            return $directFields ? $model->where($applyDirectFields) : $model;  // 无效的关联配置
        }

        // 构建EXISTS子查询
        // 注意：$onCondition[0] 中的表名需要替换为别名 dp_main，因为子查询使用了 FROM mainTable as dp_main
        $onCondition[0] = preg_replace('/^' . preg_quote($mainTable, '/') . '\./', 'dp_main.', $onCondition[0]);
        $applyExists = function ($subQuery) use ($mainTable, $uids, $fields, $jsonFields, $onCondition) {
            $subQuery->from($mainTable . ' as dp_main')
                ->whereColumn($onCondition[0], $onCondition[2]);

            // uid条件需要用括号分组，避免AND/OR优先级问题
            $subQuery->where(function ($query) use ($fields, $uids, $jsonFields) {
                // OR条件：任一uid字段匹配即可
                foreach ($fields as $i => $field) {
                    if ($i === 0) {
                        $query->whereIn('dp_main.' . $field, $uids);
                    } else {
                        $query->orWhereIn('dp_main.' . $field, $uids);
                    }
                }
                // JSON字段：使用whereJsonContains一次检查所有uid
                foreach ($jsonFields as $jsonField) {
                    $query->orWhereJsonContains('dp_main.' . $jsonField, $uids);
                }
            });
        };

        if ($directFields) {
            return $model->where(function ($query) use ($applyDirectFields, $applyExists) {
                $applyDirectFields($query);
                $query->orWhereExists($applyExists);
            });
        }

        return $model->whereExists($applyExists);
    }

    /**
     * 自动初始化数据权限上下文
     * 当权限上下文未启用时，根据模块自动设置
     * @param string $module
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function initDataPermissionContext(string $module)
    {
        // 确保是 HTTP 请求上下文
        if (! auth('admin')->check()) {
            return;
        }

        $userId = auth('admin')->id();
        if (! $userId) {
            return;
        }

        app()->get(ModulePermissionService::class)->hydrateDataPermissionContext($userId, $module);
    }

    /**
     * 获取一条数据.
     * @param mixed $where
     * @param null|mixed $sort
     * @return null|BaseModel|BuildsQueries|mixed|Model|object
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function get($where, array $field = [], array $with = [], $sort = null)
    {
        return $this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($sort, function ($query) use ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($sort);
            }
        })->select($field ?: '*')->first();
    }

    /**
     * 获取多条数据.
     * @param mixed $where
     * @return Collection
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function select($where = [], array $field = [], array $with = [], int $page = 0, int $limit = 0, bool $cursor = false)
    {
        $query = $this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($this->defaultSort, function ($query) {
            if (is_array($this->defaultSort)) {
                foreach ($this->defaultSort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($this->defaultSort);
            }
            $this->defaultSort = '';
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->select($field ?: '*');
        return $cursor ? $query->cursor() : $query->get();
    }

    /**
     * 获取条数.
     * @param mixed $where
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function count($where = [], array $with = [])
    {
        return $this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->count();
    }

    /**
     * 创建数据.
     * @return BaseModel|Model
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function create(array $data)
    {
        return $this->getModel(false)->create($data);
    }

    /**
     * 更新.
     * @param mixed $where
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update($where, array $data)
    {
        $model = $this->getModel(false);

        foreach ($data as $key => $value) {
            $model->setAttribute($key, $value);
        }

        $data = $model->getAttributes();

        return $this->search($where)->update($data);
    }

    /**
     * 无记录则新增.
     * @return BaseModel|bool|Model
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function firstOrCreate(array $where, array $data)
    {
        return $this->getModel(false)->firstOrCreate($where, $data);
    }

    /**
     * 更新或新增.
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateOrCreate(array $where, array $data)
    {
        return $this->getModel(false)->updateOrInsert($where, $data);
    }

    /**
     * 获取集合.
     * @param null $field
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function column(array $where, $field = null, string $key = '')
    {
        $select = [];
        $pk     = $this->getPk();
        if (! $key && ! $field) {
            $select[] = $pk;
        }

        if ($key) {
            $select[] = $key;
        }

        if ($field) {
            if (is_string($field)) {
                if (strstr($field, ',')) {
                    $field = explode(',', $field);
                    foreach ($field as $k) {
                        $select[] = $k;
                    }
                } else {
                    $select[] = $field;
                }
            } elseif (is_array($field)) {
                $select = array_merge($select, $field);
            }
        }

        $data = $this->search($where)->when($this->defaultSort, function ($query) {
            if (is_array($this->defaultSort)) {
                foreach ($this->defaultSort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } elseif (is_object($this->defaultSort)) {
                $query->orderBy($this->defaultSort);
            } else {
                $query->orderByDesc($this->defaultSort);
            }
            $this->defaultSort = '';
        })->select($select)->get();

        if ($data->count()) {
            $data = $data->mapWithKeys(function ($item, $index) use ($key, $field, $pk) {
                if (is_array($field)) {
                    $items = [];
                    foreach ($field as $k) {
                        if (isset($item[$k])) {
                            $items[$k] = $item[$k];
                        }
                    }
                    return [$key ? $item[$key] : $index => $items];
                }
                return [$key ? $item[$key] : $index => $item[$field ?: $pk]];
            });

            if ((! $key && ! $field) || (! $key && $field)) {
                return $data->values()->all();
            }
            if ($key && ! $field) {
                return $data->keys()->all();
            }
            return $data->all();
        }
        return [];
    }

    /**
     * 某个字段减少数量.
     * @param mixed $where
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dec($where, int $num, string $key)
    {
        return $this->search($where)->decrement($key, $num);
    }

    /**
     * 某个字段增加数量.
     * @param mixed $where
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function inc($where, int $num, string $key)
    {
        return $this->search($where)->increment($key, $num);
    }

    /**
     * 获取一个value值
     * @param mixed $where
     * @return null|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function value($where, string $key)
    {
        return $this->search($where)->orderByDesc($this->getPk())->value($key);
    }

    /**
     * 查询是否存在.
     * @param mixed $where
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function exists($where)
    {
        return $this->search($where)->exists();
    }

    /**
     * 添加返回主键ID.
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getIncId(array $data)
    {
        return $this->getModel(false)->insertGetId($data);
    }

    /**
     * 删除.
     * @param mixed $id
     * @return null|bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function delete($id, ?string $key = null)
    {
        return $this->search($key ? [$key => $id] : $id)->delete();
    }

    /**
     * 恢复软删除数据.
     * @param mixed $where
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function restore(array|int|string $where)
    {
        return $this->setOnlyTrashed()->search($where)->restore();
    }

    /**
     * 彻底删除数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function forceDelete(array|int|string $where): bool|int
    {
        return $this->search($where)->forceDelete();
    }

    /**
     * 设置模型.
     * @return mixed
     */
    abstract protected function setModel();

    /**
     * 获取默认查询条件.
     * @return array
     */
    protected function getDefaultWhereValue()
    {
        $defaultWhere = [];
        foreach ($this->defaultWhere as $key => $value) {
            if ($value instanceof \Closure) {
                $defaultWhere[$key] = $value();
            } else {
                $defaultWhere[$key] = $value;
            }
        }
        return $defaultWhere;
    }

    /**
     * 搜索器.
     * @param BaseModel $model
     * @param array|string $fields
     * @param array $data
     * @return BaseModel
     */
    protected function withSearch($model, $fields, $data = [])
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        foreach ($fields as $key => $field) {
            if ($field instanceof \Closure) {
                $model = $field($model, $data[$key] ?? null, $data);
            } else {
                $fieldName = is_numeric($key) ? $field : $key;
                $method    = Str::studly($fieldName);
                $model     = $model->{$method}($data[$field] ?? null, $data);
            }
        }
        return $model;
    }

    /**
     * 获取主键.
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getPk()
    {
        return $this->getModel(false)->getKeyName();
    }

    /**
     * 处理where条件.
     * @param mixed $where
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function handleWhere($where)
    {
        if (! is_array($where)) {
            $where = [$this->getPk() => $where];
        }
        return $where;
    }

    /**
     * 获取局部作用域
     * @return array[]
     * @throws \ReflectionException
     */
    protected function getScope(array $withSearch, string $model)
    {
        $with     = [];
        $whereKey = [];
        $response = new \ReflectionClass($model);
        foreach ($withSearch as $key => $value) {
            $method = 'scope' . Str::studly($key);
            if ($response->hasMethod($method)) {
                $with[] = $key;
            } elseif ($value !== '') {
                $whereKey[$key] = $value;
            }
        }
        return [$with, $whereKey];
    }
}
