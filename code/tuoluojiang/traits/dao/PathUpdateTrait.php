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

namespace Tuoluojiang\Open\traits\dao;

use App\Http\Dao\BaseDao;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;

/**
 * 更新path路径
 * Trait PathUpdateTrait.
 * @mixin BaseDao
 */
trait PathUpdateTrait
{
    /**
     * @var string
     */
    protected $pathFileName = 'path';

    /**
     * @var string
     */
    protected $pathTag = '/';

    /**
     * @var array
     */
    protected $fields = ['path', 'level', 'id'];

    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 获取tree型数据 自带某个id下的菜单禁用选择 配合 PathServiceTrait 和 PathAttrTrait类使用.
     * @param array $where 原始条件
     * @param array|string[] $field 展示字段
     * @param null|array|string $sort 排序
     * @param int $id 禁用id
     * @param array $otherWhere 禁用其他条件
     * @param string $key 禁用id字段
     * @param string $pathKey 禁用查询key
     * @param string $disabledName 禁用key名
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTreeList(array $where, array $field = ['*'], $sort = null, int $id = 0, array $otherWhere = [], string $key = 'id', string $pathKey = 'path', string $disabledName = 'disabled')
    {
        $data = $this->search($where)->select($field)
            ->when($sort, function ($query) use ($sort) {
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
            })->get()->toArray();

        if ($id) {
            $totalIds = $this->search($otherWhere, false)
                ->where($pathKey, 'LIKE', '%/' . $id . '/%')
                ->select([$key])->get()->map(function ($item) use ($key) {
                    return $item[$key];
                })->all();

            array_push($totalIds, $id);

            if ($totalIds) {
                foreach ($data as &$item) {
                    if (in_array($item[$key], $totalIds)) {
                        $item[$disabledName] = true;
                    }
                }
            }
        }

        return $data;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param mixed $item
     * @return string[]
     */
    public function getPathValue($item, string $path, string $newPath)
    {
        return explode($this->getPathTag(), str_replace($path, $newPath, implode($this->getPathTag(), $item->path)));
    }

    /**
     * @param mixed $item
     * @return int
     */
    public function getLevelValue($item, string $path, string $newPath)
    {
        $count    = count(explode($this->getPathTag(), $path));
        $newCount = count(explode($this->getPathTag(), $newPath));
        if ($count < $newCount) {
            $levelChang = $item->level + ($newCount - $count);
        } else {
            $levelChang = $item->level - ($count - $newCount);
        }
        return $levelChang;
    }

    public function getPathTag(): string
    {
        return $this->pathTag;
    }

    /**
     * @return string
     */
    public function getPathField()
    {
        return $this->pathFileName;
    }

    /**
     * 设置path字段.
     * @return $this
     */
    public function setPathField(string $pathFileName)
    {
        $this->pathFileName = $pathFileName;
        return $this;
    }

    /**
     * 修改下级.
     * @throws BindingResolutionException
     */
    public function updatePath(int $id, string $path, string $newPath)
    {
        $path .= '/' . $id;
        $newPath .= '/' . $id;
        $this->getModel()->where($this->getPathField(), 'like', '%/' . $id . '%')
            ->select($this->getFields())->get()
            ->each(function ($item) use ($path, $newPath) {
                foreach ($this->getFields() as $field) {
                    $method = 'get' . Str::studly($field) . 'Value';
                    if (method_exists($this, $method)) {
                        $item->{$field} = $this->{$method}($item, $path, $newPath);
                    }
                }
                $item->save();
            })->all();
    }
}
