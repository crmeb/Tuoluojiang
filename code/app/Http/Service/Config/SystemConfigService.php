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

use App\Constants\ApproveEnum;
use App\Constants\CacheEnum;
use App\Constants\System\CategoryEnum;
use App\Constants\System\ConfigEnum;
use App\Http\Dao\Config\SystemConfigDao;
use App\Http\Service\Approve\ApproveService;
use App\Support\YihaotongFactory;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ConfigInterface;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\services\MimeTypeDetector;
use Tuoluojiang\Open\services\SmsService;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 系统配置
 * Class SystemConfigServices.
 */
class SystemConfigService extends BaseService implements ConfigInterface, ResourceServicesInterface
{
    use ResourceServiceTrait;

    public const WORK_CONFIG = [
        'wechat_work_user_switch',
        'wechat_work_client_switch',
        'wechat_work_client_radio',
        'wechat_work_user_secret',
        'wechat_work_address_secret',
        'wechat_work_build_agent_id',
        'wechat_work_build_secret',
        'wechat_work_corpid',
        'wechat_work_token',
        'wechat_work_aes_key',
        'wechat_work_session_switch',
        'wechat_work_session_secret',
        'wechat_work_session_public_key_version',
        'wechat_work_session_public_key',
        'wechat_work_session_private_key',
        'wechat_work_forced_build',
    ];

    /**
     * @var FormService
     */
    protected $builder;

    public function __construct(SystemConfigDao $dao, FormService $builder)
    {
        $this->dao     = $dao;
        $this->builder = $builder;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getWorkConfig()
    {
        $config     = sys_more(self::WORK_CONFIG);
        $workConfig = [];
        foreach (self::WORK_CONFIG as $item) {
            if (isset($config[$item])) {
                $workConfig[$item] = $config[$item];
            } else {
                $workConfig[$item] = '';
            }
        }
        $workConfig['work_server_url'] = sys_config('site_url') . '/api/ent/work_serve';
        return $workConfig;
    }

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveWorkConfig(array $data)
    {
        $res = true;
        foreach ($data as $key => $value) {
            $res = $res && $this->dao->update(['key' => $key], ['value' => $value]);
        }
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
    }

    /**
     * 获取配置.
     * @param mixed $default
     * @param mixed $isSet
     * @return array|mixed|string
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getConfig(string $name, $default = '', $isSet = false)
    {
        $data = $this->dao->get(['key' => $name]);
        if ($data) {
            if ($isSet) {
                $data->value = $default;
                $data->save();
                return $default;
            }
            return $data->value ?? $default;
        }
        if (ConfigEnum::isValidKey(strtoupper($name))) {
            $config = ConfigEnum::values()[strtoupper($name)]->getValue();
            if ($name == ConfigEnum::SITE_URL['key']) {
                $config['value'] = request()->root();
            }
            if ($isSet) {
                $config['value'] = $default;
            }
            $data = $this->setConfigData($name, $config['title'], $config['value'], $config['category'], $config['type'], $config['input_type'], $config['desc'] ?? $config['title'], $config['parameter']);
            $this->dao->create($data);
            return $config['value'] ?: $default;
        }
        return $default;
    }

    /**
     * 获取多个配置.
     */
    public function getConfigs(array $name): array
    {
        $arr = [];
        foreach ($name as $item) {
            if ($this->dao->exists(['key' => $item])) {
                $arr[$item] = $this->dao->value(['key' => $item], 'value');
                continue;
            }
            if (ConfigEnum::isValidKey(strtoupper($item))) {
                $config = ConfigEnum::values()[strtoupper($item)]->getValue();
                if ($item == ConfigEnum::SITE_URL['key']) {
                    $config['value'] = request()->root();
                }
                $data = $this->setConfigData($item, $config['title'], $config['value'], $config['category'], $config['type'], $config['input_type'], $config['desc'] ?? $config['title'], $config['parameter']);
                $this->dao->create($data);
                $arr[$item] = $config['value'];
            }
        }
        return $arr;
    }

    /**
     * 获取多个配置.
     */
    public function getApproveConfigs(array $keys): array
    {
        $info    = sys_more($keys);
        $service = app()->get(ApproveService::class);
        foreach ($info as $key => $value) {
            if (! $value) {
                $types = match ($key) {
                    'contract_refund_switch'                => ApproveEnum::CUSTOMER_CONTRACT_PAYMENT,
                    'contract_renew_switch'                 => ApproveEnum::CUSTOMER_CONTRACT_RENEWAL,
                    'contract_disburse_switch'              => ApproveEnum::CUSTOMER_CONTRACT_EXPENSES,
                    'invoicing_switch'                      => ApproveEnum::CUSTOMER_INVOICE_ISSUANCE,
                    'void_invoice_switch'                   => ApproveEnum::CUSTOMER_INVOICE_CANCELLATION,
                    ConfigEnum::CONTRACT_SIGN_SWITCH['key'] => ApproveEnum::CUSTOMER_CONTRACT_SIGN
                };
                $info[$key] = (int) $service->value(['types' => $types, 'examine' => 0], 'id') ?: 0;
            }
        }
        return $info;
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
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('创建配置', $this->createDefaultFormRule(), '/admin/system/config', 'post', ['width' => '800px']);
    }

    /**
     * 获得修改数据.
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $config = $this->dao->get($id);
        if (! $config) {
            throw $this->exception('修改配置');
        }
        return $this->createElementForm('修改配置', $this->createDefaultFormRule($config->toArray()), '/config/data/' . $id, 'put', ['width' => '800px']);
    }

    /**
     * 修改配置.
     * @throws FormBuilderException
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getConfigForm(string $category): array
    {
        $keys = [];
        foreach (ConfigEnum::values() as $value) {
            if ($value->getValue()['category'] == $category) {
                $keys[] = $value->getValue()['key'];
            }
        }
        sys_more($keys);
        if ($category == CategoryEnum::UPLOAD_CONFIG['key']) {
            $rules = $this->uploadTypeForm();
        } else {
            $configList    = $this->dao->select(['key' => $keys, 'is_show' => 1]);
            $rules         = [];
            $configListNew = [];
            foreach ($configList as $item) {
                $configListNew[$item['key']] = $item;
            }
            foreach ($configListNew as $item) {
                $form = $this->createFormRule(collect($item));
                if ($form) {
                    $rules[$item['key']] = $form;
                }
            }
        }
        return $this->createElementForm('修改配置', $rules, '/config/data/all/' . $category, 'put');
    }

    /**
     * 保存配置数据.
     */
    public function saveLoginConfig(array $data): array
    {
        $this->transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                $this->dao->update(['key' => $key], ['value' => $value]);
            }
            return true;
        });
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        return $data;
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
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
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
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        return $res;
    }

    /**
     * 修改配置.
     * @throws BindingResolutionException
     */
    public function updateAllConfig(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key == 'login_password_type') {
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
                if (empty($value)) {
                    throw $this->exception('至少选择一种密码类型！');
                }
            }
            sys_config($key, $value, true);
        }
    }

    /**
     * 按当前一号通凭证同步电子签开通状态.
     *
     * 凭证无效时不抛异常：关闭电子签标记并返回 is_auth=0，保证配置保存接口可成功返回.
     *
     * @return array{is_auth: int, need_sign_staff: bool} is_auth 是否已授权；need_sign_staff 已开通则需派发员工签约任务
     */
    public function syncSignatureStatus(): array
    {
        try {
            YihaotongFactory::reset();
            $isAuth = (new SmsService((string) sys_config(ConfigEnum::YIHAOTONG_APPID['key']), (string) sys_config(ConfigEnum::YIHAOTONG_APPSECRET['key'])))->checkSignature();
        } catch (\Throwable) {
            // 错误凭证：落库已成功，此处仅同步关闭电子签状态，不向上抛
            sys_config(ConfigEnum::E_SIGNATURE['key'], 0, true);
            return [
                'is_auth'         => 0,
                'need_sign_staff' => false,
            ];
        }
        return [
            'is_auth'         => (int) $isAuth,
            'need_sign_staff' => (int) $isAuth === 1,
        ];
    }

    /**
     * 设置配置数据.
     * @param mixed $key
     * @param mixed $keyName
     * @param mixed $value
     * @param mixed $category
     */
    public function setConfigData($key, $keyName, $value, $category, string $type = 'text', string $input_type = 'input', string $desc = '', array $parameter = []): array
    {
        return [
            'key'        => $key,
            'key_name'   => $keyName,
            'type'       => $type,
            'input_type' => $input_type,
            'category'   => $category,
            'parameter'  => $parameter,
            'value'      => $value,
            'desc'       => $desc ?? $keyName,
            'entid'      => 1,
            'is_show'    => 1,
        ];
    }

    /**
     * 获取审批规则配置表单.
     * @return array
     * @throws FormBuilderException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getApproveConfig(array $keys)
    {
        $configs        = $this->dao->column(['key' => $keys], ['key', 'value', 'key_name']);
        $approveService = app()->get(ApproveService::class);

        $form = [];
        foreach ($configs as $config) {
            $data    = $this->getApproveTypes($config['key']);
            $default = [['value' => 0, 'label' => $data['label']]];
            $select  = toArray($approveService->select(['types' => $data['types'], 'examine' => 1], ['id as value', 'name as label']));
            $form[]  = $this->builder->select($config['key'], $config['key_name'] . '：', (int) $config['value'])->options(array_merge($default, $select))->multiple(false);
        }
        return $this->createElementForm('修改配置', $form, '/config/client_rule/approve', 'put');
    }

    /**
     * 获取防火墙配置.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFirewallConfig()
    {
        return [
            'firewall_switch'  => (int) sys_config('firewall_switch'),
            'firewall_content' => sys_config('firewall_content'),
        ];
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
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? []),
                    ],
                ],
                [
                    'value' => 'radio',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? []),
                    ],
                ],
                [
                    'value' => 'select',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? []),
                    ],
                ],
                [
                    'value' => 'switches',
                    'rule'  => [
                        $cascader,
                        $keyName,
                        $key,
                        $desc,
                        $this->builder->textarea('parameter', '配置参数', $config['parameter'] ?? []),
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
        return match ($type) {
            'number'   => $this->builder->number($data->get('key'), $data->get('key_name'), $data->get('value', 0))->info($data->get('desc')),
            'dateTime' => $this->builder->dateTime($data->get('key'), $data->get('key_name'), $data->get('value'))->info($data->get('desc')),
            'color'    => $this->builder->color($data->get('key'), $data['key_name'], $data->get('value', ''))->info($data->get('desc')),
            default    => $this->builder->input($data->get('key'), $data->get('key_name'), $data->get('value', ''))->info($data->get('desc'))->placeholder($data->get('desc', ''))->col(13),
        };
    }

    /**
     * 创建多行文本框.
     * @return mixed
     */
    protected function createTextareaFormRule(Collection $data)
    {
        return $this->builder->textarea($data->get('key'), $data->get('key_name'), $data->get('value', ''))->placeholder($data->get('desc', ''))->info($data->get('desc'))->rows(6)->col(13);
    }

    /**
     * 创建上传组件表单.
     * @return array|Frame|Upload
     */
    protected function createUpoadFormRule(int $type, Collection $data)
    {
        $formbuider = match ($type) {
            1 => $this->builder->frameImage($data->get('key'), $data->get('key_name'), get_image_frame_url(['field' => $data->get('key'), 'type' => 1]), $data->get('value', ''))
                ->icon('ios-image')->width('1250px')->height('580px')->info($data->get('desc'))->col(13),
            2 => $this->builder->frameImages($data->get('key'), $data->get('key_name'), get_image_frame_url(['field' => $data->get('key'), 'type' => 2]), $data->get('value', ''))
                ->maxLength(5)->icon('ios-images')->width('1250px')->height('580px')
                ->info($data->get('desc'))->col(13),
            3 => $this->builder->uploadFile($data->get('key'), $data->get('key_name'), '/system/attach/file/1', $data->get('value'))
                ->name('file')->info($data->get('desc'))->col(13)->headers([
                    'Authorization' => request()->header('Authorization'),
                ]),
            default => $this->builder->frameImage($data->get('key'), $data->get('key_name'), get_image_frame_url(['field' => $data->get('key'), 'type' => 0]), $data->get('value', ''))
                ->icon('ios-image')->width('1250px')->height('580px')->info($data->get('desc'))->col(13),
        };

        $formbuider->props(['closeBtn' => false, 'okBtn' => false]);
        //        if ($data->get('key') == 'ent_website_logo') {
        //        }
        return $formbuider;
    }

    /**
     * 创建单选框.
     * @return array|Checkbox
     * @throws FormBuilderException
     */
    protected function createCheckboxFormRule(Collection $data)
    {
        $formbuider = null;
        $options    = [];
        if ($data->get('parameter')) {
            foreach ($data->get('parameter') as $k => $v) {
                $options[] = ['label' => $v, 'value' => $k];
            }
            $formbuider = $this->builder->checkbox($data->get('key'), $data->get('key_name'), $data->get('value', ''))->options($options)->info($data->get('desc'))->col(13);
        }
        return $formbuider;
    }

    /**
     * 创建选择框表单.
     * @return array|Select
     * @throws FormBuilderException
     */
    protected function createSelectFormRule(Collection $data)
    {
        $formbuider = null;
        $options    = [];
        if ($data->get('parameter')) {
            foreach ($data->get('parameter') as $k => $v) {
                $options[] = ['label' => $v, 'value' => $k];
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
        if ($data->get('parameter')) {
            $options = [];
            foreach ($data->get('parameter') as $k => $v) {
                $options[] = ['label' => $v, 'value' => $k];
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
        if ($data->get('parameter')) {
            $options = [];
            foreach ($data->get('parameter') as $k => $v) {
                $options[] = ['label' => $v, 'value' => $k];
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
     * @return array|mixed|void
     * @throws FormBuilderException
     */
    protected function createFormRule(Collection $data)
    {
        return match ($data->get('type', 'text')) {
            'text'     => $this->createTextFormRule($data->get('input_type', ''), $data),
            'radio'    => $this->createRadioFormRule($data),
            'textarea' => $this->createTextareaFormRule($data),
            'upload'   => $this->createUpoadFormRule((int) $data->get('upload_type', 0), $data),
            'checkbox' => $this->createCheckboxFormRule($data),
            'select'   => $this->createSelectFormRule($data),
            'switches' => $this->createSwitchesFormRule($data),
            default    => '',
        };
    }

    private function uploadTypeForm($key = 'upload_mime')
    {
        $options = [];
        $data    = app()->get(MimeTypeDetector::class)->getMimeTypes();

        // 定义常见文件类型的友好描述
        $typeDescriptions = [
            'pdf'    => 'PDF文档',
            'doc'    => 'Word文档(旧版)',
            'docx'   => 'Word文档',
            'xls'    => 'Excel表格(旧版)',
            'xlsx'   => 'Excel表格',
            'ppt'    => 'PPT演示文稿(旧版)',
            'pptx'   => 'PPT演示文稿',
            'txt'    => '文本文件',
            'jpg'    => 'JPEG图片',
            'jpeg'   => 'JPEG图片',
            'png'    => 'PNG图片',
            'gif'    => 'GIF图片',
            'bmp'    => 'BMP图片',
            'webp'   => 'WebP图片',
            'svg'    => 'SVG矢量图',
            'zip'    => 'ZIP压缩包',
            'rar'    => 'RAR压缩包',
            '7z'     => '7Z压缩包',
            'tar'    => 'TAR归档',
            'gz'     => 'GZ压缩',
            'mp4'    => 'MP4视频',
            'avi'    => 'AVI视频',
            'mov'    => 'MOV视频',
            'wmv'    => 'WMV视频',
            'mp3'    => 'MP3音频',
            'wav'    => 'WAV音频',
            'wma'    => 'WMA音频',
            'csv'    => 'CSV数据',
            'md'     => 'Markdown文档',
            'xml'    => 'XML文件',
            'json'   => 'JSON文件',
            'html'   => 'HTML网页',
            'css'    => 'CSS样式',
            'js'     => 'JavaScript脚本',
            'psd'    => 'PSD设计稿',
            'ai'     => 'AI矢量图',
            'dwg'    => 'CAD图纸',
            'xmind'  => 'XMind思维导图',
            'sketch' => 'Sketch设计稿',
            'xd'     => 'XD设计稿',
            'tif'    => 'TIFF图片',
            'tiff'   => 'TIFF图片',
            'rm'     => 'RM视频',
            'rmvb'   => 'RMVB视频',
        ];

        foreach ($data as $ext => $mime) {
            // 确保扩展名为字符串类型
            $ext         = (string) $ext;
            $description = $typeDescriptions[$ext] ?? strtoupper($ext) . '文件';

            // 处理 MIME 类型（可能是数组或字符串）
            if (is_array($mime)) {
                // 多个 MIME 类型时，使用第一个作为值
                foreach ($mime as $m) {
                    $options[] = ['label' => $description . ' (.' . $ext . ')', 'value' => $m];
                }
            } else {
                $options[] = ['label' => $description . ' (.' . $ext . ')', 'value' => $mime];
            }
        }

        return [
            $this->builder->select($key, '允许上传文件类型', sys_config($key))->options($options)->filterable(true)->multiple(true)->clearable(true)->info(ConfigEnum::UPLOAD_MIME['desc'])->col(24),
        ];
    }

    private function getApproveTypes($key)
    {
        return match ((string) $key) {
            ConfigEnum::CONTRACT_REFUND_SWITCH['key'] => ['types' => ApproveEnum::CUSTOMER_CONTRACT_PAYMENT, 'label' => '无需审批（直接生成收入账目）'],
            'contract_renew_switch'                   => ['types' => ApproveEnum::CUSTOMER_CONTRACT_RENEWAL, 'label' => '无需审批（直接生成收入账目）'],
            'contract_disburse_switch'                => ['types' => ApproveEnum::CUSTOMER_CONTRACT_EXPENSES, 'label' => '无需审批（直接生成支出账目）'],
            'invoicing_switch'                        => ['types' => ApproveEnum::CUSTOMER_INVOICE_ISSUANCE, 'label' => '无需审批（财务直接核对开票）'],
            'void_invoice_switch'                     => ['types' => ApproveEnum::CUSTOMER_INVOICE_CANCELLATION, 'label' => '无需审批（直接进行发票冲红）'],
            ConfigEnum::CONTRACT_SIGN_SWITCH['key']   => ['types' => ApproveEnum::CUSTOMER_CONTRACT_SIGN, 'label' => '无需审批（直接发起合同签约）'],
            default                                   => [],
        };
    }
}
