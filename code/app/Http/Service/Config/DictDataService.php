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

namespace App\Http\Service\Config;

use App\Constants\CacheEnum;
use App\Http\Dao\Config\DictDataDao;
use App\Task\system\BatchUpdateDictTask;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @mixin DictDataDao
 */
class DictDataService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(DictDataDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 根据id获取数据.
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/3
     */
    public function idByValues(int $typeId, array $ids = [])
    {
        return $this->dao->idByValues($typeId, $ids);
    }

    /**
     * 根据id获取数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function idByCacheValues(int $typeId, array $ids = [])
    {
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5(json_encode([$ids, $typeId])), (int) sys_config('system_cache_ttl', 3600), function () use ($ids, $typeId) {
            return $this->idByValues($typeId, $ids);
        });
    }

    /**
     * 使用IDS获取数据.
     * @return string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function idsByCacheValues(array $ids)
    {
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5(json_encode($ids)), (int) sys_config('system_cache_ttl', 3600), function () use ($ids) {
            return $this->dao->idsByValues($ids);
        });
    }

    public function getList(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $data           = Cache::tags([CacheEnum::TAG_DICT])->remember(md5(json_encode($where) . $page . $limit), (int) sys_config('system_cache_ttl', 3600), function () use ($where, $field, $with, $page, $limit, $sort) {
            if ($where['level'] > 1) {
                unset($where['level']);
            }
            $list = $this->dao->setDefaultSort($sort)->select($where, $field, $with, $page, $limit)->each(function (&$item) use ($where) {
                $item['hasChildren'] = $this->dao->count(['pid' => $item['value'], 'type_name' => $where['type_name']]);
            });
            $count = $this->dao->count($where);
            return json_encode($this->listData($list, $count), JSON_UNESCAPED_UNICODE);
        });
        return json_decode($data, true);
    }

    /**
     * 获取字典属性数据.
     * @param mixed $where
     * @param mixed $field
     * @param mixed $with
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTreeData(array $where, array $field = ['id', 'pid', 'name', 'status', 'type_id', 'type_name', 'color', 'value'], array $with = []): array
    {
        $where['status'] = 1;
        $data            = Cache::tags([CacheEnum::TAG_DICT])->remember(md5('tree' . json_encode($where) . json_encode($field)), (int) sys_config('system_cache_ttl', 3600), function () use ($where, $field, $with) {
            if (isset($where['level']) && $where['level'] > 1) {
                unset($where['level']);
            }
            $list = collect($this->dao->setDefaultSort(['sort' => 'desc', 'id' => 'asc'])->select($where, $field, $with)?->toArray())->each(function (&$item) {
                $item['checkbox'] = false;
            })->values()->all();
            return json_encode(get_tree_children($list, keyName: 'value'), JSON_UNESCAPED_UNICODE);
        });
        return json_decode($data, true);
    }

    /**
     * 新增表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('新增字典数据', $this->getFormRule(collect($other)), '/ent/config/dict_data');
    }

    /**
     * 保存数据.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if (! $data['pid']) {
            $data['level'] = 1;
        } else {
            $data['level'] = $this->dao->value(['value' => $data['pid'], 'type_id' => $data['type_id']], 'level') + 1;
        }
        $type = app()->get(DictTypeService::class)->get($data['type_id'], ['level', 'ident'])?->toArray();
        if (empty($data['value'])) {
            $data['value'] = $this->getUniqueValue($type['ident']);
        }
        if ($data['level'] > $type['level']) {
            throw $this->exception('已超出字典最大层级限制');
        }
        $data['type_name'] = $type['ident'];
        if ($this->dao->exists(['value' => $data['value'], 'type_name' => $data['type_name']])) {
            throw $this->exception('字典数据值已在重复, 请勿重复添加');
        }
        $res = $this->dao->create($data);
        $res && $res['status'] && Task::deliver(new BatchUpdateDictTask((int) $res->id));
        Cache::tags([CacheEnum::TAG_DICT, CacheEnum::TAG_CUSTOMER])->flush();
        return $res;
    }

    /**
     * 修改表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $info = toArray($this->dao->get($id));
        if (! $info) {
            throw $this->exception('修改的字典数据不存在');
        }
        return $this->createElementForm('修改字典数据', $this->getFormRule(collect($info)), '/ent/config/dict_data/' . $id, 'PUT');
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if (! $data['pid']) {
            $data['level'] = 1;
        } else {
            $data['level'] = $this->dao->value(['value' => $data['pid'], 'type_id' => $data['type_id']], 'level') + 1;
        }
        $type = app()->get(DictTypeService::class)->get($data['type_id'], ['level', 'ident'])?->toArray();
        if (empty($data['value'])) {
            $data['value'] = $this->getUniqueValue($type['ident']);
        }
        if ($data['level'] > $type['level']) {
            throw $this->exception('已超出字典最大层级限制');
        }
        $data['type_name'] = $type['ident'];
        if ($this->dao->exists(['not_id' => $id, 'value' => $data['value'], 'type_name' => $data['type_name']])) {
            throw $this->exception('字典数据值已存在, 请勿重复添加');
        }
        $is_default = $this->dao->value(['id' => $id], 'is_default');
        if ($is_default) {
            unset($data['value']);
        }
        $res = $this->transaction(function () use ($id, $data) {
            $oldLevel     = $this->dao->value(['id' => $id], 'level');
            [$_, $subIds] = $this->subData($id, $data['type_id']);
            if ($oldLevel > $data['level']) {
                $this->dao->dec(['id' => $subIds], (int) bcsub((string) $oldLevel, (string) $data['level']), 'level');
            } else {
                $this->dao->inc(['id' => $subIds], (int) bcsub((string) $data['level'], (string) $oldLevel), 'level');
            }
            return $this->dao->update($id, $data);
        });
        $res && $data['status'] && Task::deliver(new BatchUpdateDictTask((int) $id));
        Cache::tags([CacheEnum::TAG_DICT, CacheEnum::TAG_CUSTOMER])->flush();
        return $res;
    }

    public function resourceShowUpdate($id, array $data)
    {
        $res = $this->dao->update($id, $data);
        Task::deliver(new BatchUpdateDictTask((int) $id));
        Cache::tags([CacheEnum::TAG_DICT, CacheEnum::TAG_CUSTOMER])->flush();
        return $res;
    }

    /**
     * 删除字典数据.
     * @param mixed $id
     * @return int|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $ids = array_values(array_filter(explode(',', (string) $id), fn ($item) => $item !== ''));
        if (! $ids) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $table        = '`' . str_replace('`', '``', DB::getTablePrefix() . $this->dao->getTable()) . '`';
        DB::statement('DROP TEMPORARY TABLE IF EXISTS dict_data_delete_ids');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS dict_data_delete_frontier');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS dict_data_delete_next');
        DB::statement('CREATE TEMPORARY TABLE dict_data_delete_ids (id BIGINT PRIMARY KEY) ENGINE=InnoDB');
        DB::statement('CREATE TEMPORARY TABLE dict_data_delete_frontier (id BIGINT PRIMARY KEY) ENGINE=InnoDB');
        DB::statement('CREATE TEMPORARY TABLE dict_data_delete_next (id BIGINT PRIMARY KEY) ENGINE=InnoDB');
        try {
            DB::statement(
                <<<SQL
INSERT IGNORE INTO dict_data_delete_frontier (id)
SELECT id FROM {$table}
WHERE id IN ({$placeholders})
SQL,
                $ids
            );
            DB::statement('INSERT IGNORE INTO dict_data_delete_ids (id) SELECT id FROM dict_data_delete_frontier');

            while (true) {
                DB::statement('TRUNCATE TABLE dict_data_delete_next');
                $inserted = DB::affectingStatement(
                    <<<SQL
INSERT IGNORE INTO dict_data_delete_next (id)
SELECT child.id
FROM {$table} child
INNER JOIN {$table} parent ON child.pid = parent.value AND child.type_id = parent.type_id
INNER JOIN dict_data_delete_frontier ON dict_data_delete_frontier.id = parent.id
LEFT JOIN dict_data_delete_ids ON dict_data_delete_ids.id = child.id
WHERE dict_data_delete_ids.id IS NULL
SQL
                );
                if (! $inserted) {
                    break;
                }
                DB::statement('INSERT IGNORE INTO dict_data_delete_ids (id) SELECT id FROM dict_data_delete_next');
                DB::statement('TRUNCATE TABLE dict_data_delete_frontier');
                DB::statement('INSERT IGNORE INTO dict_data_delete_frontier (id) SELECT id FROM dict_data_delete_next');
            }

            $res = DB::delete(
                <<<SQL
DELETE dict_data_source
FROM {$table} dict_data_source
INNER JOIN dict_data_delete_ids ON dict_data_delete_ids.id = dict_data_source.id
WHERE dict_data_source.is_default = 0
SQL
            );
        } finally {
            DB::statement('DROP TEMPORARY TABLE IF EXISTS dict_data_delete_ids');
            DB::statement('DROP TEMPORARY TABLE IF EXISTS dict_data_delete_frontier');
            DB::statement('DROP TEMPORARY TABLE IF EXISTS dict_data_delete_next');
        }
        Cache::tags([CacheEnum::TAG_DICT, CacheEnum::TAG_CUSTOMER])->flush();
        return $res;
    }

    /**
     * 多个字典回显.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getNamesByValue(string $ident, mixed $value): mixed
    {
        if ($value === '' || is_null($value)) {
            return [];
        }
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5('names_' . $ident . json_encode($value)), (int) sys_config('system_cache_ttl', 3600), function () use ($value, $ident) {
            return is_array($value) ? $this->dao->setDefaultSort(['level' => 'asc'])->column(['dict_value' => $value, 'type_name' => $ident], 'name') : $this->dao->setDefaultSort(['level' => 'asc'])->get(['dict_value' => $value, 'type_name' => $ident], ['name', 'value', 'color']);
        });
    }

    /**
     * 单选字典回显.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRadio(string $ident, null|int|string $value): mixed
    {
        if ($value === '' || is_null($value)) {
            return [];
        }
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5('radio_name_' . $ident . json_encode($value)), (int) sys_config('system_cache_ttl', 3600), function () use ($value, $ident) {
            return $this->dao->setDefaultSort(['level' => 'asc'])->get(['dict_value' => $value, 'type_name' => $ident], ['name', 'value', 'color'])?->toArray();
        });
    }

    /**
     * 字典回显.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getNameByValue(string $ident, mixed $value): string
    {
        if ($value === '') {
            return '';
        }
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5('name_' . $ident . json_encode($value)), (int) sys_config('system_cache_ttl', 3600), function () use ($value, $ident) {
            return (string) $this->dao->value(['dict_value' => $value, 'type_name' => $ident], 'name');
        });
    }

    /**
     * 获取默认订单分类数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDefaultContractCategory(): array
    {
        return $this->combination($this->getTreeData(['type_name' => 'contract_type']));
    }

    /**
     * 组合分类数据.
     */
    public function combination(array $options, array $combination = []): array
    {
        $result = [];
        foreach ($options as $option) {
            $newCombination = array_merge($combination, [$option['value']]);
            if (isset($option['children'])) {
                $result = array_merge($result, $this->combination($option['children'], $newCombination));
            } else {
                $result[] = $newCombination;
            }
        }
        return array_map(function ($item) {
            return is_array($item) ? json_encode($item) : (string) $item;
        }, $result);
    }

    /**
     * 获取完整分类数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCompleteData(string $ident, array|string $value): array
    {
        $info = $this->dao->get(['dict_value' => $value, 'type_name' => $ident], ['id', 'name', 'value', 'pid']);
        if (! $info) {
            return [];
        }
        if ($info['pid'] > 0) {
            return array_merge($this->getCompleteData($ident, $info['pid']), [$info['value']]);
        }
        return [$info['value']];
    }

    /**
     * 根据名称获取value.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getValuesByName(mixed $name, string $ident): array
    {
        $values = [];
        $name   = array_filter(explode('/', $name));
        if (! $name) {
            return $values;
        }
        $where = ['name_eq' => $name, 'type_name' => $ident, 'status' => 1];
        if ($ident == 'area_cascade') {
            $pid      = $this->dao->value(array_merge($where, ['pid' => 0]), 'value');
            $values[] = $pid;
            for ($i = 0; $i < count($name) - 1; ++$i) {
                $pid = $this->dao->value(array_merge($where, ['pid' => $pid]), 'value');
                if ($pid < 1) {
                    break;
                }
                $values[] = $pid;
            }
        } else {
            $list = $this->dao->column($where, 'value', 'name');
            foreach ($name as $tmpValue) {
                if (isset($list[$tmpValue])) {
                    $values[] = $list[$tmpValue];
                }
            }
        }
        return $values;
    }

    /**
     * 根据值获取数据的id.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getValuesByIds(mixed $name, int $id): array
    {
        $values = [];
        $name   = array_filter(explode('/', $name));
        if (! $name) {
            return $values;
        }
        $where = ['name_eq' => $name, 'type_id' => $id, 'status' => 1];

        $list = $this->dao->column($where, 'value', 'name');
        foreach ($name as $tmpValue) {
            if (isset($list[$tmpValue])) {
                $values[] = $list[$tmpValue];
            }
        }

        return $values;
    }

    /**
     * 根据类型获取value.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getValuesByType(array|string $name, string $ident, string $inputType, string $type): mixed
    {
        $key = md5(json_encode(['name' => $name, 'ident' => $ident, 'inputType' => $inputType, 'type' => $type]));
        return Cache::remember($key, 60, function () use ($name, $ident, $inputType, $type) {
            $values = [];
            switch ($inputType) {
                case 'checked':
                    $values = $this->getValuesByName($name, $ident);
                    break;
                case 'select':
                    if ($type == 'multiple') {
                        $name = array_filter(explode('#', $name));
                        foreach ($name as $item) {
                            $values[] = $this->getValuesByName($item, $ident);
                        }
                    } else {
                        $values = $this->getValuesByName($name, $ident);
                    }
                    break;
                case 'radio':
                    $values = $this->dao->value(['name_eq' => $name, 'type_name' => $ident, 'status' => 1], 'value');
                    break;
                default:
                    $values = $name;
                    break;
            }
            return $values;
        });
    }

    /**
     * 获取字典数据.
     * @param mixed $where
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getIdNameByTypeName($where, array $field = ['value as id', 'pid', 'name', 'level'], array $with = []): array
    {
        $where['status'] = 1;
        return Cache::tags([CacheEnum::TAG_DICT])->remember(md5('select' . json_encode($where) . json_encode($field)), (int) sys_config('system_cache_ttl', 3600), function () use ($where, $field, $with) {
            return get_tree_children(toArray($this->dao->select($where, $field, $with)));
        });
    }

    /**
     * 获取表单规则.
     * @return array
     */
    private function getFormRule(Collection $collection)
    {
        $type = app()->get(DictTypeService::class)->get($collection->get('type_id', 0));
        if (! $type) {
            throw $this->exception('缺少字典关联参数');
        }
        $form[] = FormService::hidden('type_id', $collection->get('type_id', ''));
        $form[] = FormService::input('type_name', '字典标识', $type->name)->readonly(true);
        if ($type->level > 1) {
            [$count, $ids] = $this->subData($collection->get('value', 0), $collection->get('type_id', 0));
            $ids[]         = $collection->get('value', '');
            $list          = get_tree_children(toArray($this->dao->select(['not_value' => $ids, 'type_id' => $collection->get('type_id', 0), 'level_lt' => $type->level - $count], ['value', 'id', 'pid', 'name as label'])), keyName: 'value');
            $form[]        = FormService::cascader('pid', '父级数据')
                ->options($list)->appendRule('value', $collection->get('pid', ''))
                ->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])->clearable(true);
        }
        $value = $collection->get('value', '');
        if (! $value) {
            $value = $this->getUniqueValue($type->ident);
        }
        $form[]                      = FormService::input('name', '数据名称', $collection->get('name', ''))->required();
        $type->level == 1 && $form[] = FormService::color('color', '颜色标识', $collection->get('color', '#303133') ?: '#303133');
        $form[]                      = FormService::input('value', '数据值', $value)->required();
        $form[]                      = FormService::number('sort', '排序', (int) $collection->get('sort', 0))->min(0)->max(999999)->precision(0);
        $form[]                      = FormService::radio('status', '状态', (int) $collection->get('status', 1))->options([['value' => 1, 'label' => '启用'], ['value' => 0, 'label' => '停用']]);
        $form[]                      = FormService::textarea('mark', '备注', $collection->get('mark', ''))->placeholder('请输入备注信息，最多可输入200字')->maxlength(200);
        return $form;
    }

    /**
     * 获取唯一字典值
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function getUniqueValue(string $ident): int
    {
        $existingValues = $this->dao->column(['type_name' => $ident], 'value');
        $existingValues = array_map(function ($item) {
            return is_numeric($item) ? (int) $item : $item;
        }, $existingValues);
        $candidate = 1;
        while (in_array($candidate, $existingValues, true)) {
            ++$candidate;
        }
        return $candidate;
    }

    private function subData($id, $type_id, $count = 1, $ids = [])
    {
        if (! $id) {
            return [$count, $ids];
        }
        if ($pid = $this->dao->column(['pid' => $id, 'type_id' => $type_id], 'value')) {
            ++$count;
            $ids = array_unique(array_merge($pid, $ids));
            return $this->subData($pid, $type_id, $count, $ids);
        }
        return [$count, $ids];
    }
}
