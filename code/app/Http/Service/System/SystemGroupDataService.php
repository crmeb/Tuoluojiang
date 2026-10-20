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

use App\Http\Dao\Config\GroupDataDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * Class SystemGroupDataService.
 * @method array getGroupDataList(array $where, array $field = ['*'], int $page = 0, $limit = 0) 获取组合数据
 */
class SystemGroupDataService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * SystemGroupDataService constructor.
     */
    public function __construct(GroupDataDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $data   = parent::getList($where, $field, $sort, $with);
        $fields = app()->get(SystemGroupService::class)->getGroupInfo($where['group_id'], 'fields');
        foreach ($data['list'] as &$item) {
            foreach ($fields as $val) {
                if (isset($val['name'])) {
                    $item[$val['name']] = $item['value'][$val['name']] ?? '';
                }
            }
        }
        return $data;
    }

    /**
     * 创建表单.
     * @return mixed
     * @throws FormBuilderException
     */
    public function createGroupForm(int $gid, array $groupData = [])
    {
        $groupDataValue = isset($groupData['value']) ? (is_string($groupData['value']) ? json_decode($groupData['value'], true) : $groupData['value']) : [];
        $fields         = app()->get(SystemGroupService::class)->getGroupInfo($gid, 'fields');
        $f[]            = Form::hidden('group_id', $gid);
        foreach ($fields as $key => $value) {
            $info = [];
            if (isset($value['param'])) {
                $value['param'] = str_replace("\r\n", "\n", $value['param']); // 防止不兼容
                $params         = explode("\n", $value['param']);
                if (is_array($params) && ! empty($params)) {
                    foreach ($params as $index => $v) {
                        $vl = explode('=>', $v);
                        if (isset($vl[0], $vl[1])) {
                            $info[$index]['value'] = $vl[0];
                            $info[$index]['label'] = $vl[1];
                        }
                    }
                }
            }
            $fvalue = isset($groupDataValue[$value['name']]) ? $groupDataValue[$value['name']] : '';
            switch ($value['type']) {
                case 'input':
                    $f[] = Form::input($value['name'], $value['title'], $fvalue);
                    break;
                case 'textarea':
                    $f[] = Form::input($value['name'], $value['title'], $fvalue)->type('textarea')->placeholder($value['param']);
                    break;
                case 'radio':
                    $f[] = Form::radio($value['name'], $value['title'], $fvalue ?: (int) ($info[0]['value'] ?? ''))->options($info);
                    break;
                case 'checkbox':
                    $f[] = Form::checkbox($value['name'], $value['title'], $fvalue ?: $info[0] ?? '')->options($info);
                    break;
                case 'select':
                    $f[] = Form::select($value['name'], $value['title'], $fvalue !== '' ? $fvalue : $info[0] ?? '')
                        ->options($info)->multiple(false);
                    break;
                case 'upload':
                    if (! empty($fvalue)) {
                        $image = is_string($fvalue) ? $fvalue : $fvalue[0];
                    } else {
                        $image = '';
                    }
                    $f[] = Form::frameImage($value['name'], $value['title'], get_image_frame_url(['field' => $value['name'], 'type' => 1]), $image)
                        ->icon('ios-image')->width('950px')->height('420px');
                    break;
                case 'uploads':
                    if ($fvalue) {
                        if (is_string($fvalue)) {
                            $fvalue = [$fvalue];
                        }
                        $images = ! empty($fvalue) ? $fvalue : [];
                    } else {
                        $images = [];
                    }
                    $f[] = Form::frameImages($value['name'], $value['title'], get_image_frame_url(['field' => $value['name'], 'type' => 2]), $images)
                        ->maxLength(5)->icon('ios-images')->width('950px')->height('420px')->spin(0);
                    break;
                default:
                    $f[] = Form::input($value['name'], $value['title'], $fvalue);
                    break;
            }
        }
        $f[] = Form::number('sort', '排序', (int) ($groupData['sort'] ?? 1))->min(0)->max(999999);
        $f[] = Form::radio('status', '状态', (int) ($groupData['status'] ?? 1))->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]);
        return $f;
    }

    /**
     * 获取组合数据table头.
     * @return array
     */
    public function getGroupDataTabHeader(int $id)
    {
        $header = [];
        $data   = app()->get(SystemGroupService::class)->getGroupInfo($id, 'fields');
        foreach ($data as $key => $item) {
            if ($item['type'] == 'upload' || $item['type'] == 'uploads') {
                $header[$key]['key']      = $item['name'];
                $header[$key]['minWidth'] = 60;
                $header[$key]['type']     = $item['type'] == 'uploads' ? 'imgs' : 'img';
            } elseif ($item['name'] == 'url' || $item['name'] == 'wap_url' || $item['name'] == 'link' || $item['name'] == 'wap_link') {
                $header[$key]['key']      = $item['name'];
                $header[$key]['minWidth'] = 200;
            } else {
                $header[$key]['key']      = $item['name'];
                $header[$key]['minWidth'] = 100;
            }
            $header[$key]['title'] = $item['title'];
        }
        array_unshift($header, ['key' => 'id', 'title' => '编号', 'minWidth' => 35]);
        array_push(
            $header,
            ['slot' => 'status', 'title' => '是否可用', 'minWidth' => 80],
            ['key'  => 'sort', 'title' => '排序', 'minWidth' => 80],
            ['slot' => 'action', 'fixed' => 'right', 'title' => '操作', 'minWidth' => 120]
        );
        return compact('header');
    }

    /**
     * 获取创建组合数据表单.
     * @throws FormBuilderException
     */
    public function resourceCreate(array $other = []): array
    {
        if (! isset($other['gid'])) {
            throw $this->exception('缺少组合数据id');
        }
        return $this->createElementForm('添加数组', $this->createGroupForm($other['gid']), '/admin/system/groupData');
    }

    /**
     * 获取修改组合数据表单.
     * @throws FormBuilderException
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if (! isset($other['gid'])) {
            throw $this->exception('缺少组合数据id');
        }
        $groupDataInfo = $this->dao->get($id);
        if (! $groupDataInfo) {
            throw $this->exception('修改的组合数据不存在');
        }
        return $this->createElementForm('修改组合数据', $this->createGroupForm($other['gid'], $groupDataInfo->toArray()), '/admin/system/groupData/' . $id, 'put');
    }

    /**
     * TODO 保存自定义组合数据.
     * @return bool|mixed
     * @throws BindingResolutionException
     */
    public function saveData(array $data, array $group, array $delete = [])
    {
        if ($delete) {
            $this->dao->delete($delete, 'id');
        }
        $services = app()->get(SystemGroupService::class);
        if (! $services->exists(['group_key' => $group['group_key'], 'entid' => $group['entid']])) {
            $info = $services->create($group);
        } else {
            $info = $services->get(['group_key' => $group['group_key'], 'entid' => $group['entid']]);
        }
        if (! count($data)) {
            Cache::tags("data_{$group['group_key']}_{$group['entid']}")->flush();
            return true;
        }
        return $this->transaction(function () use ($data, $info, $group) {
            foreach ($data as $val) {
                if (! isset($val['title']) || ! $val['title']) {
                    throw $this->exception(__('common.empty.attr', ['attr' => '名称']));
                }
                $value = ['title' => $val['title']];
                $save  = [
                    'group_id' => $info->id,
                    'sort'     => isset($val['sort']) ? $val['sort'] : 1,
                    'status'   => 1,
                    'value'    => $value,
                ];
                if (! $val['id'] && $this->dao->exists(['group_id' => $info->id, 'value' => json_encode($value)])) {
                    throw $this->exception('common.insert.exists');
                }
                if ($val['id']) {
                    $res = $this->dao->update(['id' => $val['id'], 'group_id' => $info->id], $save);
                } else {
                    $res = $this->dao->create($save);
                }
                if (! $res) {
                    throw $this->exception('common.operation.fail');
                }
            }
            Cache::tags("data_{$group['group_key']}_{$group['entid']}")->flush();
            return true;
        });
    }
}
