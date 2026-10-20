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

namespace App\Http\Service\Config;

use App\Http\Dao\Config\ConfigDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ConfigInterface;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\ConfigService;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\UI\Elm\Components\Checkbox;
use FormBuilder\UI\Elm\Components\ColorPicker;
use FormBuilder\UI\Elm\Components\DatePicker;
use FormBuilder\UI\Elm\Components\Frame;
use FormBuilder\UI\Elm\Components\Input;
use FormBuilder\UI\Elm\Components\InputNumber;
use FormBuilder\UI\Elm\Components\Radio;
use FormBuilder\UI\Elm\Components\Select;
use FormBuilder\UI\Elm\Components\Switches;
use FormBuilder\UI\Elm\Components\Upload;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 企业系统配置
 * Class CompanyConfigService.
 */
class CompanyConfigService extends BaseService implements ConfigInterface, ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * @var FormService
     */
    protected $builder;

    /**
     * 表单数据切割符号.
     * @var string
     */
    protected $cuttingStr = '=>';

    /**
     * @var array
     */
    protected $relatedRule = [];

    /**
     * SystemConfigServices constructor.
     */
    public function __construct(ConfigDao $dao, FormService $builder)
    {
        $this->dao     = $dao;
        $this->builder = $builder;
    }

    /**
     * 获取企业配置.
     * @return null|mixed
     * @throws BindingResolutionException
     */
    public function getConfig(string $name)
    {
        return html_entity_decode($this->dao->value(['key' => $name], 'value'));
    }

    /**
     * 获取企业多个配置.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getConfigs(array $name): array
    {
        return $this->dao->column(['key' => $name], 'key', 'value');
    }

    /**
     * 获取多条配置.
     * @return array
     */
    public function getConfigLimit(string $name, int $limit = 0, int $entid = 1, int $page = 0)
    {
        return [];
    }

    /**
     * 获取配置列表(分页).
     * @param null|mixed $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $data           = $this->dao->getList($where, $field, $page, $limit, 'id');
        $count          = $this->dao->count($where);
        return $this->listData($data, $count);
    }

    /**
     * 创建数据前返回数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('创建配置', $this->createDefaultFormRule(), '/admin/system/config', 'post', ['width' => '800px']);
    }

    /**
     * 获得修改数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $config = $this->dao->get($id);
        if (! $config) {
            throw $this->exception('修改配置');
        }
        return $this->createElementForm('修改配置', $this->createDefaultFormRule($config->toArray()), '/admin/system/config/' . $id, 'put', ['width' => '800px']);
    }

    /**
     * 修改配置.
     * @return array
     * @throws FormBuilderException
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getConfigForm(int $cateId)
    {
        $this->getRelatedRule();
        $configList    = $this->dao->getList(['cate_id' => $cateId, 'is_show' => 1], ['*'], 0, 0, 'sort');
        $rules         = [];
        $keys          = [];
        $configListNew = [];
        foreach ($configList as $item) {
            $configListNew[$item['key']] = $item;
        }
        foreach ($configListNew as $k => $item) {
            $form = $this->createFormRule(collect($item));
            if ($form) {
                if (isset($this->relatedRule[$k])) {
                    [$rule, $key] = $this->createControlRule($configListNew, $this->relatedRule[$k]);
                    $form->control($rule);
                    $keys = array_merge($keys, $key);
                }
                $rules[$item['key']] = $form;
            }
        }
        foreach ($keys as $k) {
            unset($rules[$k]);
        }
        return $this->createElementForm('修改配置', $rules, '/admin/system/config/all/' . $cateId, 'put');
    }

    /**
     * 保存配置数据.
     * @return array
     */
    public function saveLoginConfig(array $data)
    {
        $this->transaction(function () use ($data) {
            $res = true;
            foreach ($data as $key => $value) {
                $res = $res && $this->dao->update(['key' => $key], ['value' => $value]);
            }
            if (! $res) {
                throw $this->exception('保存配置失败');
            }
        });
        Cache::flush();
        return $data;
    }

    /**
     * 获取登录配置数据.
     * @return array
     */
    public function getLoginConfig()
    {
        $configKey = ['login_password_length', 'login_password_type', 'logint_time_out', 'logint_error_count', 'logint_lock'];
        return $this->dao->column(['key' => $configKey], 'value', 'key');
    }

    /**
     * 获取网站信息.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSiteInfo()
    {
        $configKey = ['website_logo', 'site_name', 'site_seo_title', 'site_url', 'site_record_number'];
        return $this->dao->column(['key' => $configKey], 'value', 'key');
    }

    /**
     * 创建配置.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $path = $data['path'];
        if ($path) {
            $data['cate_id'] = $path[count($path) - 1];
        }
        $res = $this->dao->create($data);
        Cache::flush();
        return $res;
    }

    /**
     * 修改配置.
     * @param int $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $path = $data['path'];
        if ($path) {
            $data['cate_id'] = $path[count($path) - 1];
        }
        $res = $this->dao->update($id, $data);
        Cache::flush();
        return $res;
    }

    /**
     * 修改配置.
     * @throws BindingResolutionException
     */
    public function updateAllConfig(array $data)
    {
        foreach ($data as $key => $value) {
            $this->dao->update(['key' => $key], ['value' => $value]);
        }
        Cache::flush();
    }

    /**
     * 获取分类配置表单.
     * @param string $title
     * @param string $category
     * @param string $url
     * @param string $method
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getConfigFrom($entid, $title = '', $category = '', $url = '', $method = 'post')
    {
        if (! $category) {
            throw $this->exception('缺少配置类型');
        }
        $config = $this->dao->select(['category' => 'work_bench', 'entid' => $entid, 'is_show' => 1], ['key', 'key_name', 'value', 'type', 'input_type', 'desc', 'parameter']);
        $form   = [];
        foreach ($config as $val) {
            switch ($val['type']) {
                case 'text':
                    $form[] = Form::input($val['key'], $val['key_name'], $val['value']);
                    break;
                case 'upload':
                    $form[] = Form::frameImage($val['key'], $val['key_name'], get_roule_mobu('', 1) . '/setting/uploadPicture?field=' . $val['key'], $val['value'] ?? '')->width('890px')
                        ->modal(['modal' => true, 'showCancelButton' => false, 'showConfirmButton' => false])->props(['footer' => false])
                        ->validate([Form::validateStr()->required()->message($val['key_name'])]);
                    break;
                case 'radio':
                    $form[] = $this->createRadioFormRule(new Collection($val));
                    break;
            }
        }
        return $this->createElementForm($title, $form, $url, $method);
    }

    /**
     * 保存分类配置.
     * @return bool
     * @throws BindingResolutionException
     */
    public function saveConfig($entid, $category, $data)
    {
        if (! $data || ! $category) {
            throw $this->exception('保存失败，缺少必要参数');
        }
        foreach ($data as $k => $v) {
            $this->dao->update(['category' => 'work_bench', 'entid' => $entid, 'key' => $k], ['value' => $v]);
        }
        return true;
    }

    /**
     * 获取工作台配置.
     * @param string $cateName
     * @return array|ConfigService|int|mixed|string
     */
    public function getBenchConfig($name, $entId, $cateName = 'work_bench')
    {
        if ($this->exists(['category' => $cateName, 'key' => $name, 'entid' => $entId])) {
            $info = $this->value(['category' => $cateName, 'key' => $name, 'entid' => $entId], 'value');
        } else {
            $info = sys_config($name);
            $this->create([
                'key'        => $name,
                'key_name'   => '工作台背景',
                'value'      => $info,
                'type'       => 'upload',
                'input_type' => '',
                'category'   => $cateName,
                'is_show'    => 1,
                'entid'      => $entId,
            ]);
        }
        return $info;
    }

    /**
     * 获取基础表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function createDefaultFormRule(array $config = [])
    {
        $cateCascader = app()->get(SystemConfigCateService::class)->catecascader();
        array_unshift($cateCascader, ['value' => 0, 'label' => '顶级分类']);
        $cascader = $this->builder->select('cate_id', '配置分类', $config['pid'] ?? 0)->options($cateCascader);
        $key      = $this->builder->input('key', '字段变量', $config['key'] ?? '')->required();
        $keyName  = $this->builder->input('key_name', '配置名称', $config['key_name'] ?? '')->required();
        $desc     = $this->builder->input('desc', '配置简介', $config['desc'] ?? '');
        $value    = $this->builder->input('value', '默认值', isset($config['value']) && ! is_array($config['value']) ? $config['value'] : '');
        return [
            $this->builder->radio('type', '表单类型', $config['type'] ?? 'text')->options([
                ['value' => 'text', 'label' => '文本框'],
                ['value' => 'textarea', 'label' => '多行文本框'],
                ['value' => 'upload', 'label' => '文件上传'],
                ['value' => 'checkbox', 'label' => '多选框'],
                ['value' => 'radio', 'label' => '单选框'],
                ['value' => 'select', 'label' => '下拉框'],
                ['value' => 'switches', 'label' => '开关'],
            ])->control([
                [
                    'value' => 'text',
                    'rule'  => [
                        $cascader,
                        $this->builder->select('input_type', '文本类型', $config['input_type'] ?? '')->options([
                            ['value' => 'input', 'label' => '文本框'],
                            ['value' => 'dateTime', 'label' => '时间'],
                            ['value' => 'color', 'label' => '颜色'],
                            ['value' => 'number', 'label' => '数字'],
                        ])->required(),
                        $keyName,
                        $key,
                        $desc,
                        $value,
                    ],
                ],
                [
                    'value' => 'textarea',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('value', '默认值', isset($config['value']) && ! is_array($config['value']) ? $config['value'] : ''),
                        $this->builder->number('width', '文本框宽(%)', $config['width'] ?? ''),
                        $this->builder->number('heigth', '多行文本框高(%)', $config['heigth'] ?? ''),
                    ],
                ],
                [
                    'value' => 'upload',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->radio('upload_type', '上传类型', (int) ($config['upload_type'] ?? 1))->options([
                            ['value' => 1, 'label' => '单图'],
                            ['value' => 2, 'label' => '多图'],
                            ['value' => 3, 'label' => '文件'],
                        ]),
                    ],
                ],
                [
                    'value' => 'checkbox',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? ''),
                    ],
                ],
                [
                    'value' => 'radio',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? ''),
                    ],
                ],
                [
                    'value' => 'select',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? ''),
                    ],
                ],
                [
                    'value' => 'switches',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? ''),
                    ],
                ],
            ]),
            $this->builder->number('sort', '排序', $config['sort'] ?? 0),
            $this->builder->switches('is_show', '状态', $config['is_show'] ?? 0)->inactiveValue(0)->activeValue(1)->inactiveText('禁用')->activeText('启用'),
        ];
    }

    /**
     * 创建单行表单规则.
     * @return ColorPicker|DatePicker|Input|InputNumber
     */
    protected function createTextFormRule(string $type, Collection $data)
    {
        $formbuider = null;
        switch ($type) {
            case 'input':
                $formbuider = $this->builder->input($data->get('key'), $data->get('key_name'), $data->get('value', ''))->info($data->get('desc'))->placeholder($data->get('desc'))->col(13);
                break;
            case 'number':
                $formbuider = $this->builder->number($data->get('key'), $data->get('key_name'), $data->get('value', 0))->info($data->get('desc'));
                break;
            case 'dateTime':
                $formbuider = $this->builder->dateTime($data->get('key'), $data->get('key_name'), $data->get('value'))->info($data->get('desc'));
                break;
            case 'color':
                $formbuider = $this->builder->color($data->get('key'), $data['key_name'], $data->get('value', ''))->info($data->get('desc'));
                break;
            default:
                $formbuider = $this->builder->input($data->get('key'), $data->get('key_name'), $data->get('value', ''))->info($data->get('desc'))->placeholder($data->get('desc'))->col(13);
                break;
        }
        return $formbuider;
    }

    /**
     * 创建多行文本框.
     * @return mixed
     */
    protected function createTextareaFormRule(Collection $data)
    {
        return $this->builder->textarea($data->get('key'), $data->get('key_name'), $data->get('value', ''))->placeholder($data->get('desc'))->info($data->get('desc'))->rows(6)->col(13);
    }

    /**
     * 创建上传组件表单.
     * @return null|Frame|Upload
     */
    protected function createUpoadFormRule(int $type, Collection $data)
    {
        $formbuider = null;
        switch ($type) {
            case 1:
                $formbuider = $this->builder->frameImage($data->get('key'), $data->get('key_name'), get_image_frame_url(['field' => $data->get('key'), 'type' => 1]), $data->get('value', ''))
                    ->icon('ios-image')->width('950px')->height('420px')->info($data->get('desc'))->col(13);
                break;
            case 2:
                $formbuider = $this->builder->frameImages($data->get('key'), $data->get('key_name'), get_image_frame_url(['field' => $data->get('key'), 'type' => 2]), $data->get('value', ''))
                    ->maxLength(5)->icon('ios-images')->width('950px')->height('420px')
                    ->info($data->get('desc'))->col(13);
                break;
            case 3:
                $formbuider = $this->builder->uploadFile($data->get('key'), $data->get('key_name'), '/system/attach/file/1', $data->get('value'))
                    ->name('file')->info($data->get('desc'))->col(13)->headers([
                        'Authorization' => request()->header('Authorization'),
                    ]);
                break;
        }
        return $formbuider;
    }

    /**
     * 创建单选框.
     * @return null|Checkbox
     */
    protected function createCheckboxFormRule(Collection $data)
    {
        $formbuider = null;
        $parameter  = explode("\n", $data->get('parameter'));
        $options    = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata     = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider = $this->builder->checkbox($data->get('key'), $data->get('key_name'), $data->get('value', ''))->options($options)->info($data->get('desc'))->col(13);
        }
        return $formbuider;
    }

    /**
     * 创建选择框表单.
     * @return null|Select
     */
    protected function createSelectFormRule(Collection $data)
    {
        $formbuider = null;
        $parameter  = explode("\n", $data->get('parameter'));
        $options    = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata     = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider = $this->builder->select($data->get('key'), $data->get('key_name'), $data->get('value', ''))->options($options)->info($data->get('desc'))->col(13);
        }
        return $formbuider;
    }

    /**
     * 创建Radio规则.
     * @return null|Radio
     */
    protected function createRadioFormRule(Collection $data)
    {
        $formbuider = null;
        $parameter  = explode("\n", $data->get('parameter'));
        if ($parameter) {
            $options = [];
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata     = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => (int) $pdata[0]];
                }
            }
            $formbuider = $this->builder->radio($data->get('key'), $data->get('key_name'), (int) $data->get('value', 0))->options($options)->info($data->get('desc'))->col(13);
        }
        return $formbuider;
    }

    /**
     * switches表单规则.
     * @return null|Switches
     */
    protected function createSwitchesFormRule(Collection $data)
    {
        $formbuider = null;
        $parameter  = explode("\n", $data->get('parameter'));
        if ($parameter) {
            $options = [];
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata     = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => (int) $pdata[0]];
                }
            }
            $formbuider = $this->builder->switches($data->get('key'), $data->get('key_name'), $data->get('value', 0))->inactiveValue($options[0]['value'] ?? 0)->activeValue($options[1]['value'] ?? 1)->inactiveText($options[0]['label'] ?? '关闭')->activeText($options[1]['label'] ?? '开启');
        }
        return $formbuider;
    }

    /**
     * 创建关联规则.
     * @param string $key
     * @throws FormBuilderException
     */
    protected function createControlRule(array $congfigList, array $ruleData, array &$key = [])
    {
        $rule = [];
        foreach ($ruleData as $ruleKey) {
            $ruleNew = ['value' => $ruleKey['value'], 'rule' => []];
            if (is_array($ruleKey['rule'])) {
                foreach ($ruleKey['rule'] as $k => $v) {
                    if (is_array($v)) {
                        $key[]             = $k;
                        [$r]               = $this->createControlRule($congfigList, $v, $key);
                        $ruleNew['rule'][] = $this->createFormRule(collect($congfigList[$k]))->control($r);
                    } else {
                        $key[]             = $v;
                        $ruleNew['rule'][] = $this->createFormRule(collect($congfigList[$v]));
                    }
                }
            }
            $rule[] = $ruleNew;
        }
        return [$rule, $key];
    }

    /**
     * 创建Form表单规则.
     * @return null|Checkbox|ColorPicker|DatePicker|Frame|Input|InputNumber|mixed|Radio|Select|Switches|Upload|void
     */
    protected function createFormRule(Collection $data)
    {
        switch ($data->get('type', 'text')) {
            case 'text':// 文本框
                return $this->createTextFormRule((string) $data->get('input_type', ''), $data);
                break;
            case 'radio':// 单选框
                return $this->createRadioFormRule($data);
                break;
            case 'textarea':// 多行文本框
                return $this->createTextareaFormRule($data);
                break;
            case 'upload':// 文件上传
                return $this->createUpoadFormRule((int) $data->get('upload_type', 0), $data);
                break;
            case 'checkbox':// 多选框
                return $this->createCheckboxFormRule($data);
                break;
            case 'select':// 多选框
                return $this->createSelectFormRule($data);
                break;
            case 'switches':
                return $this->createSwitchesFormRule($data);
                break;
        }
    }

    /**
     * 规则.
     */
    protected function getRelatedRule()
    {
        $this->relatedRule = [
            'site_open' => [
                [
                    'value' => 0,
                    'rule'  => [],
                ],
                [
                    'value' => 1,
                    'rule'  => [
                        'site_record_number',
                        'site_seo_title',
                        'site_url',
                        'site_name',
                        'site_address',
                        'site_tel',
                    ],
                ],
            ],
            'upload_type' => [
                [
                    'value' => 2,
                    'rule'  => [
                        'qiniu_accessKey',
                        'qiniu_secretKey',
                        'qiniu_uploadUrl',
                        'qiniu_storage_name',
                        'qiniu_storage_region',
                    ],
                ],
                [
                    'value' => 3,
                    'rule'  => [
                        'accessKey',
                        'secretKey',
                        'uploadUrl',
                        'storage_name',
                        'storage_region',
                    ],
                ],
                [
                    'value' => 4,
                    'rule'  => [
                        'tengxun_accessKey',
                        'tengxun_secretKey',
                        'tengxun_uploadUrl',
                        'tengxun_storage_name',
                        'tengxun_storage_region',
                    ],
                ],
            ],
        ];
    }
}
