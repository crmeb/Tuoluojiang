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

namespace App\Http\Service\System;

use App\Http\Dao\Config\GroupDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ConfigInterface;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 组合数据
 * Class SystemGroupService.
 */
class SystemGroupService extends BaseService implements ConfigInterface, ResourceServicesInterface
{
    use ResourceServiceTrait;

    // input类型
    public const INPUT_TYPE = ['input', 'textarea', 'radio', 'checkbox', 'select', 'upload', 'uploads'];

    /**
     * SystemGroupServices constructor.
     */
    public function __construct(GroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取单个组合数据(全部).
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function getConfig(string $name)
    {
        $groupId = $this->dao->value(['group_key' => $name], 'id');
        return array_column(app()->get(SystemGroupDataService::class)->getGroupDataList(['group_id' => $groupId], ['value']), 'value');
    }

    /**
     * 获取多个配置下的组合数据.
     */
    public function getConfigs(array $name): array
    {
        return [];
    }

    /**
     * 获取组合数据(截取).
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function getConfigLimit(string $name, int $limit = 0, int $entid = 1, int $page = 0)
    {
        $services = app()->get(SystemGroupDataService::class);
        $groupId  = $this->dao->value(['group_key' => $name, 'entid' => $entid], 'id') ?? 0;
        if ($page) {
            $list  = $services->getGroupDataList(['group_id' => $groupId], ['value', 'id', 'sort'], $page, $limit);
            $count = $services->count(['group_id' => $groupId]);
            return $this->listData($list, $count);
        }
        return $services->getGroupDataList(['group_id' => $groupId], ['value', 'id', 'sort'], $page, $limit);
    }

    /**
     * 获取组合数据详情.
     * @param array|string $field
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupInfo(int $id, $field)
    {
        $groupInfo = $this->dao->get($id, is_string($field) ? [$field] : $field);
        if (! $groupInfo) {
            throw $this->exception('组合数据不存在');
        }
        return is_string($field) ? $groupInfo[$field] : $groupInfo->toArray();
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        return [];
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 保存数据组.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($this->dao->exists(['group_key' => $data['group_key'], 'entid' => $data['entid']])) {
            throw $this->exception('数据组字段已存在！');
        }
        if (! count($data['fields'])) {
            throw $this->exception('字段至少存在一个！');
        }
        $validate = ['name', 'type', 'title', 'param'];
        foreach ($data['fields'] as $key => &$value) {
            foreach ($validate as $v) {
                if (! isset($value[$v])) {
                    throw $this->exception('缺少字段:' . $v);
                }
            }
            if (! preg_match('/^[a-z\_]+$/', $value['name'])) {
                throw $this->exception('字段name必须为英文字母');
            }
            if (! in_array($value['type'], self::INPUT_TYPE)) {
                throw $this->exception('字段type类型不正确');
            }
            $value['param'] = htmlspecialchars_decode($value['param']);
        }
        return $this->dao->create($data);
    }

    /**
     * 编辑数据组.
     * @param int $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if (isset($data['group_key'])) {
            unset($data['group_key']);
        }
        if (! count($data['fields'])) {
            throw $this->exception('字段至少存在一个！');
        }
        $validate = ['name', 'type', 'title', 'param'];
        foreach ($data['fields'] as $key => &$value) {
            foreach ($validate as $v) {
                if (! isset($value[$v])) {
                    throw $this->exception('缺少字段:' . $v);
                }
            }
            if (! preg_match('/^[a-z\_]+$/', $value['name'])) {
                throw $this->exception('字段name必须为英文字母');
            }
            if (! in_array($value['type'], self::INPUT_TYPE)) {
                throw $this->exception('字段type类型不正确');
            }
            $value['param'] = htmlspecialchars_decode($value['param']);
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 删除组合数据分类.
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(SystemGroupDataService::class)->count(['group_id' => $id])) {
            throw $this->exception('请先删除组合数据的数据内容');
        }
        return $this->dao->delete($id, $key);
    }
}
