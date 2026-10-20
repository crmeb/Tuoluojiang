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

namespace Tuoluojiang\Open\traits\service;

use App\Http\Service\System\CategoryService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService as Form;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * Trait CategoryTrait.
 * @mixin  BaseService
 */
trait CategoryTrait
{
    use ResourceServiceTrait;

    /**
     * 表字段名.
     * @var string[]
     */
    protected $tableField = [
        'id'        => 'id',
        'cate_name' => 'cate_name',
        'path'      => 'path',
        'level'     => 'level',
        'pid'       => 'pid',
        'pic'       => 'pic',
        'is_show'   => 'is_show',
        'type'      => 'type',
        'sort'      => 'sort',
        'entid'     => 'entid',
    ];

    /**
     * 能添加层级.
     */
    protected int $maxLevel = 9999;

    /**
     * 能添加层级.
     * @var int
     */
    protected $entId = 0;

    /**
     * 路径分割符.
     * @var string
     */
    protected $pathDivision = '/';

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null|string $sort
     * @return mixed
     */
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = []): array
    {
        $where['type']  = $this->getType();
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 添加分类.
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if (! isset($data[$this->tableField['type']])) {
            $data[$this->tableField['type']] = $this->getType();
        }
        if (! isset($data[$this->tableField['entid']])) {
            $data[$this->tableField['entid']] = 1;
        }

        $path = $data[$this->tableField['path']];
        if ($path) {
            $data[$this->tableField['pid']] = $path[count($path) - 1];
        }
        if ($this->dao->exists(
            [
                'path'                                 => $path,
                'entid'                                => 1,
                $this->tableField['path']              => $data[$this->tableField['path']],
                'eq_' . $this->tableField['cate_name'] => $data[$this->tableField['cate_name']], ]
        )) {
            throw $this->exception('已存在相同分类');
        }
        if ($data[$this->tableField['pid']]) {
            $path = $this->dao->value($data[$this->tableField['pid']], $this->tableField['path']);
            if ($path && count($path) > $this->getLevel()) {
                throw $this->exception('添加的层级已超最高层级');
            }
        }

        $cate = $this->dao->create($data);
        if (! $cate) {
            throw $this->exception('添加分类失败');
        }
        Cache::tags(['category'])->flush();
        return $cate->toArray();
    }

    /**
     * 删除分类.
     * @param int $id
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if ($this->dao->count([$this->tableField['pid'] => $id])) {
            throw $this->exception('请先删除下级分类');
        }
        Cache::tags(['category'])->flush();
        return $this->dao->delete($id);
    }

    /**
     * 获取指定分类下的分类路径.
     * @return null|mixed
     * @throws BindingResolutionException
     */
    public function getCatePath(int $id): string
    {
        return $this->dao->value($id, $this->tableField['path']);
    }

    /**
     * 获取catecascadera分类数据.
     * @return array[]
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function catecascader()
    {
        return $this->menus(['type' => $this->getType()]);
    }

    /**
     * 保存.
     * @param int $id
     * @return int
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $path = $data[$this->tableField['path']];
        if ($path) {
            $data[$this->tableField['pid']] = $path[count($path) - 1];
        }
        Cache::tags(['category'])->flush();
        return $this->dao->update($id, $data);
    }

    /**
     * 获取分类组合数据.
     * @return array[]
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function menus(array $where = []): array
    {
        return Cache::tags(['category'])->remember(md5(json_encode($where)), (int) sys_config('system_cache_ttl', 3600), function () use ($where) {
            return app()->get(CategoryService::class)->getTierList($where, [$this->tableField['id'] . ' as value', $this->tableField['cate_name'] . ' as label', $this->tableField['pid']]);
        });
    }

    /**
     * 分类类型.
     */
    abstract protected function getType(): string;

    /**
     * 分类层级.
     */
    abstract protected function getLevel(): int;

    /**
     * 设置字段.
     * @return $this
     */
    protected function setTableField(string $key, string $field)
    {
        $keys = array_keys($this->tableField);
        if (in_array($key, $keys)) {
            $this->tableField[$key] = $field;
        }
        return $this;
    }

    /**
     * 设置最大层级.
     * @param string $maxLevel
     * @return $this
     */
    protected function setMaxLevel(int $maxLevel)
    {
        $this->maxLevel = $maxLevel;
        return $this;
    }

    /**
     * 获取表单数据.
     */
    protected function createFormRule(array $data = []): array
    {
        $where = [
            'type'   => $this->getType(),
            'not_id' => $data[$this->tableField['pid']] ?? 0,
        ];
        return [
            $this->getLevel() == 1 ?
                Form::select($this->tableField['pid'], '父级分类', $data[$this->tableField['pid']] ?? 0)
                    ->options([['value' => 0, 'label' => '顶级分类']] + $this->menus($where))
                :
                Form::cascader($this->tableField['path'], '父级分类')
                    ->options($this->menus($where))
                    ->value($data[$this->tableField['path']] ?? [])
                    ->props(['props' => ['checkStrictly' => true]]),
            Form::input($this->tableField['cate_name'], '分类名称', $data[$this->tableField['cate_name']] ?? '')->maxlength(20)->required(),
            Form::frameImage($this->tableField['pic'], '分类图标', get_image_frame_url(['field' => $this->tableField['pic']]), $data[$this->tableField['pic']] ?? '')->handleIcon(false)->width('800')->modal(['modal' => false, 'showCancelButton' => false, 'showConfirmButton' => false]),
            Form::hidden($this->tableField['type'], $this->getType()),
            Form::number($this->tableField['sort'], '排序', $data[$this->tableField['sort']] ?? 0)->min(0)->max(999999)->precision(0),
            Form::switches($this->tableField['is_show'], '状态', $data[$this->tableField['is_show']] ?? 1)->inactiveValue(0)->activeValue(1)->inactiveText('关闭')->activeText('开启'),
        ];
    }
}
