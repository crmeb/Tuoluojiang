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

namespace App\Http\Service\Crud;

use App\Constants\Crud\CrudFormEnum;
use App\Http\Dao\Crud\SystemCrudFormDao;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Model\Crud\SystemCrudField;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Log;

/**
 * 实体表单
 * Class SystemCrudFormService.
 * @email 136327134@qq.com
 * @date 2024/2/26
 * @mixin SystemCrudFormDao
 */
class SystemCrudFormService extends BaseService
{
    /**
     * 表单类型转换.
     * @var string[][]
     */
    public $formType = [
        CrudFormEnum::FORM_INPUT            => ['icon' => 'text-field'],
        CrudFormEnum::FORM_INPUT_SELECT     => ['icon' => 'reference-field'],
        CrudFormEnum::FORM_SELECT           => ['icon' => 'select-field'],
        CrudFormEnum::FORM_INPUT_PERCENTAGE => ['icon' => 'slider-field'],
        CrudFormEnum::FORM_INPUT_PRICE      => ['icon' => 'number-field'],
        CrudFormEnum::FORM_INPUT_FLOAT      => ['icon' => 'number-field'],
        CrudFormEnum::FORM_INPUT_NUMBER     => ['icon' => 'number-field'],
        CrudFormEnum::FORM_CHECKBOX         => ['icon' => 'checkbox-field'],
        CrudFormEnum::FORM_TAG              => ['icon' => 'form-label-field'],
        CrudFormEnum::FORM_CASCADER         => ['icon' => 'cascader-field'],
        CrudFormEnum::FORM_DATE_PICKER      => ['icon' => 'date-field'],
        CrudFormEnum::FORM_DATE_TIME_PICKER => ['icon' => 'date-range-field'],
        CrudFormEnum::FORM_IMAGE            => ['icon' => 'picture-upload-field'],
        CrudFormEnum::FORM_FILE             => ['icon' => 'file-upload-field'],
        CrudFormEnum::FORM_CASCADER_RADIO   => ['icon' => 'cascader-field'],
        CrudFormEnum::FORM_CASCADER_ADDRESS => ['icon' => 'cascader-field'],
        CrudFormEnum::FORM_SWITCH           => ['icon' => 'switch-field'],
        CrudFormEnum::FORM_TEXTAREA         => ['icon' => 'textarea-field'],
        CrudFormEnum::FORM_RADIO            => ['icon' => 'radio-field'],
    ];

    /**
     * @var array
     */
    protected $formConfig = [
        'name'         => '',
        'type'         => '',
        'icon'         => '',
        'formItemFlag' => true,
        'options'      => [
            'name'         => '',
            'label'        => '',
            'labelAlign'   => '',
            'type'         => '',
            'defaultValue' => '',
            'placeholder'  => '',
            'columnWidth'  => '200px',
            'size'         => '',
            'labelWidth'   => null,
            'labelHidden'  => false,
            'readonly'     => false,
            'disabled'     => false,
            'hidden'       => false,

            'clearable'    => false,
            'showPassword' => false,

            'required'       => false,
            'requiredHint'   => '',
            'validation'     => '',
            'validationHint' => '',

            'customClass'       => '',
            'labelIconClass'    => null,
            'labelIconPosition' => 'rear',
            'labelTooltip'      => null,

            'minLength'            => null,
            'maxLength'            => null,
            'showWordLimit'        => false,
            'prefixIcon'           => '',
            'suffixIcon'           => '',
            'appendButton'         => false,
            'appendButtonDisabled' => false,
            'buttonIcon'           => '',
            'dataDictId'           => 0,
            'fieldId'              => 0,

            'onCreated'           => '',
            'onMounted'           => '',
            'onInput'             => '',
            'onChange'            => '',
            'onFocus'             => '',
            'onBlur'              => '',
            'onValidate'          => '',
            'onAppendButtonClick' => '',
        ],
    ];

    /**
     * 下拉选择.
     * @var array
     */
    protected $selectInputOptions = [
        'filterable'        => false,
        'allowCreate'       => false,
        'remote'            => false,
        'automaticDropdown' => false,
        'multiple'          => false,
        'multipleLimit'     => 0,
        'optionItems'       => [],
        'customizeItems'    => [],
    ];

    /**
     * 复选框.
     * @var array
     */
    protected $checkboxOptions = [
        'displayStyle'   => 'inline',
        'buttonStyle'    => false,
        'border'         => false,
        'optionItems'    => [],
        'customizeItems' => [],
    ];

    /**
     * 小数.
     * @var array
     */
    protected $floatNumberOptions = [
        'keyNameEnabled' => false,
        'keyName'        => '',
        'min'            => -999999999,
        'max'            => 999999999,
        'precision'      => 2,
        'step'           => 1,
    ];

    /**
     * 数字.
     * @var array
     */
    protected $numberOptions = [
        'keyNameEnabled' => false,
        'keyName'        => '',
        'min'            => -100000000000,
        'max'            => 100000000000,
        'precision'      => 0,
        'step'           => 1,
    ];

    /**
     * 一对一关联.
     * @var string[]
     */
    protected $referenceOptions = [
        'buttonIcon' => '',
        'buttonText' => '选择',
    ];

    /**
     * 地址选择.
     * @var array
     */
    protected $addressOptions = [
        'filterable'      => false,
        'multiple'        => false,
        'checkStrictly'   => false,
        'showAllLevels'   => false,
        'dsEnabled'       => false,
        'dsName'          => '',
        'isCityShow'      => '',
        'dataSetName'     => '',
        'labelKey'        => 'label',
        'valueKey'        => 'value',
        'childrenKey'     => 'children',
        'areaDataEnabled' => true,
        'areaDataType'    => 2,
    ];

    /**
     * 图片上传.
     * @var array
     */
    protected $imageOptions = [
        'uploadURL'       => 'common/upload',
        'uploadTip'       => '',
        'withCredentials' => false,
        'multipleSelect'  => false,
        'showFileList'    => false,
        'limit'           => 1,
        'fileMaxSize'     => 2,
        'fileTypes'       => ['jpg', 'jpeg', 'png', 'gif'],
    ];

    /**
     * 文件上传.
     * @var array
     */
    protected $fileOptions = [
        'uploadURL'       => 'common/upload',
        'uploadTip'       => '',
        'withCredentials' => false,
        'multipleSelect'  => false,
        'showFileList'    => false,
        'limit'           => 1,
        'fileMaxSize'     => 2,
        'fileTypes'       => ['doc', 'docx', 'xls', 'xlsx'],
    ];

    /**
     * 多选.
     * @var array
     */
    protected $cascaderOptions = [
        'filterable'      => false,
        'multiple'        => true,
        'checkStrictly'   => false,
        'showAllLevels'   => true,
        'disableMultiple' => false,
        'optionItems'     => [],
        'customizeItems'  => [],
    ];

    /**
     * 单选.
     * @var array
     */
    protected $cascaderRadioOptions = [
        'filterable'      => false,
        'multiple'        => false,
        'disableMultiple' => true,
        'checkStrictly'   => false,
        'showAllLevels'   => true,
        'optionItems'     => [],
        'customizeItems'  => [],
    ];

    /**
     * 开关.
     * @var array
     */
    protected $switchOptions = [
        'switchWidth'   => 40,
        'activeText'    => '开启',
        'inactiveText'  => '关闭',
        'activeColor'   => null,
        'inactiveColor' => null,
    ];

    /**
     * 时间.
     * @var array
     */
    protected $dateOptions = [
        'editable'    => false,
        'format'      => 'yyyy-MM-dd',
        'valueFormat' => 'yyyy-MM-dd',
    ];

    /**
     * 多行文本.
     * @var int[]
     */
    protected $textareaOptions = [
        'rows' => 3,
    ];

    /**
     * SystemCrudFormService constructor.
     */
    public function __construct(SystemCrudFormDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 设置数据.
     * @return array|string[]
     * @email 136327134@qq.com
     * @date 2024/3/18
     */
    public function setFormConfig(array $item, int $id = 0)
    {
        $formConfig = $this->formConfig;

        $config = $this->formType[$item['form_value']] ?? [];

        $formConfig         = array_merge($formConfig, $config);
        $formConfig['type'] = str_replace('_', '-', $item['form_value']);

        switch ($item['form_value']) {
            case CrudFormEnum::FORM_SWITCH:
                $formConfig['options'] = array_merge($formConfig['options'], $this->switchOptions);
                break;
            case CrudFormEnum::FORM_INPUT_NUMBER:
            case CrudFormEnum::FORM_INPUT_PERCENTAGE:
            case CrudFormEnum::FORM_INPUT_PRICE:
                $formConfig['options'] = array_merge($formConfig['options'], $this->numberOptions);
                if ($item['form_value'] === CrudFormEnum::FORM_INPUT_PERCENTAGE) {
                    $formConfig['options']['max']          = 100;
                    $formConfig['options']['min']          = 0;
                    $formConfig['options']['defaultValue'] = 0;
                }
                break;
            case CrudFormEnum::FORM_INPUT_FLOAT:
                $formConfig['options'] = array_merge($formConfig['options'], $this->floatNumberOptions);
                break;
            case CrudFormEnum::FORM_TEXTAREA:
                $formConfig['options']         = array_merge($formConfig['options'], $this->textareaOptions);
                $formConfig['options']['rows'] = (int) ($item['options']['rows'] ?? 3);
                break;
            case CrudFormEnum::FORM_RADIO:
            case CrudFormEnum::FORM_CHECKBOX:
                if ($item['form_value'] === CrudFormEnum::FORM_RADIO) {
                    $isRadioShow = $item['options']['is_radio_show'] ?? '';
                    if ($isRadioShow === 'select') {
                        $formConfig                            = array_merge($formConfig, $this->formType['select']);
                        $formConfig['options']                 = array_merge($formConfig['options'], $this->selectInputOptions);
                        $formConfig['options']['defaultValue'] = [];
                    } else {
                        $formConfig['options'] = array_merge($formConfig['options'], $this->checkboxOptions);
                    }
                } else {
                    $formConfig['options'] = array_merge($formConfig['options'], $this->checkboxOptions);
                }

                if ($item['form_value'] === CrudFormEnum::FORM_CHECKBOX) {
                    $formConfig['options']['defaultValue'] = [];
                }
                $formConfig['options']['optionItems']    = $item['data_dict'] ?? [];
                $formConfig['options']['customizeItems'] = $item['data_dict'] ?? [];
                break;
            case CrudFormEnum::FORM_TAG:
                $formConfig['options']['optionItems']    = $item['data_dict'] ?? [];
                $formConfig['options']['customizeItems'] = $item['data_dict'] ?? [];
                break;
            case CrudFormEnum::FORM_CASCADER_RADIO:
                $formConfig['options'] = array_merge($formConfig['options'], $this->cascaderRadioOptions);
                break;
            case CrudFormEnum::FORM_CASCADER_ADDRESS:
                $formConfig['options'] = array_merge($formConfig['options'], $this->addressOptions);
                if (isset($item['options']['is_city_show'])) {
                    $formConfig['options']['isCityShow'] = $item['options']['is_city_show'];
                }
                break;
            case CrudFormEnum::FORM_CASCADER:
                $formConfig['options'] = array_merge($formConfig['options'], $this->cascaderOptions);
                break;
            case CrudFormEnum::FORM_DATE_PICKER:
            case CrudFormEnum::FORM_DATE_TIME_PICKER:
                $formConfig['options'] = array_merge($formConfig['options'], $this->dateOptions);
                if ($item['form_value'] === CrudFormEnum::FORM_DATE_TIME_PICKER) {
                    $formConfig['options']['format']      = 'yyyy-MM-dd HH:mm:ss';
                    $formConfig['options']['valueFormat'] = 'yyyy-MM-dd HH:mm:ss';
                }
                $formConfig['options']['defaultType'] = 1;
                break;
            case CrudFormEnum::FORM_IMAGE:
                $formConfig['options']                 = array_merge($formConfig['options'], $this->imageOptions);
                $formConfig['options']['fileMaxSize']  = (int) ($item['options']['size'] ?? 2);
                $formConfig['options']['limit']        = (int) ($item['options']['max'] ?? 1);
                $formConfig['options']['defaultValue'] = [];
                break;
            case CrudFormEnum::FORM_FILE:
                $formConfig['options']                 = array_merge($formConfig['options'], $this->fileOptions);
                $formConfig['options']['fileMaxSize']  = (int) ($item['options']['size'] ?? 2);
                $formConfig['options']['limit']        = (int) ($item['options']['max'] ?? 1);
                $formConfig['options']['defaultValue'] = [];
                break;
            case CrudFormEnum::FORM_INPUT_SELECT:
                $formConfig['options'] = array_merge($formConfig['options'], $this->referenceOptions);
                break;
        }

        $formConfig['options']['dataDictId']      = $item['data_dict_id'];
        $formConfig['options']['fieldId']         = $item['id'];
        $formConfig['options']['formFieldUniqid'] = $item['form_field_uniqid'];
        $formConfig['options']['minLength']       = (int) ($item['options']['minlength'] ?? 0);
        $formConfig['options']['maxLength']       = (int) ($item['options']['maxlength'] ?? 300);
        $formConfig['options']['name']            = str_replace('.', '@', $item['form_field_uniqid']);
        $formConfig['options']['label']           = $item['field_name'];
        $formConfig['options']['data_type']       = $item['data_type'];
        $formConfig['options']['customizeItems']  = $item['customize_items'] ?? [];
        $formConfig['options']['crud_id']         = $item['crud_id'] ?? 0;
        $formConfig['name']                       = $item['field_name'];
        $formConfig['hide']                       = (bool) $item['is_form'];
        $formConfig['id']                         = $item['id'];

        if (isset($config['disabled'])) {
            $formConfig['options']['disabled'] = $item['disabled'];
        }
        if (! $item['create_modify'] && ! $id) {
            $formConfig['options']['disabled'] = true;
        }
        if (! $item['update_modify'] && $id) {
            $formConfig['options']['disabled'] = true;
        }
        $formConfig['options']['required'] = ! $item['is_default_value_not_null'];

        return $formConfig;
    }

    /**
     * 设置表单配置.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/4/2
     */
    public function getFormOptions(array $options, array $fieldConfig = [])
    {
        foreach ($options as $index => $option) {
            if (isset($option['cols'])) {
                foreach ($option['cols'] as $indxCole => $col) {
                    if (isset($col['widgetList'])) {
                        foreach ($col['widgetList'] as $i => $item) {
                            if (isset($item['cols'])) {
                                $options[$index]['cols'][$indxCole]['widgetList'][$i]['cols'] = $this->getFormOptions($item['cols'], $fieldConfig);
                            } else {
                                $item = $this->setFormVlaueConfig($item, $item['options']['name'], $fieldConfig);

                                $options[$index]['cols'][$indxCole]['widgetList'][$i]['options'] = $item['options'];
                            }
                        }
                    }
                }
            } elseif (isset($option['tabs'])) {
                foreach ($option['tabs'] as $kk => $tab) {
                    if (isset($tab['widgetList'])) {
                        foreach ($tab['widgetList'] as $i => $widget) {
                            if (isset($widget['cols'])) {
                                $options[$index]['tabs'][$kk]['widgetList'][$i]['cols'] = $this->getFormOptions($widget['cols'], $fieldConfig);
                            } else {
                                $widget = $this->setFormVlaueConfig($widget, $widget['options']['name'], $fieldConfig);

                                $options[$index]['tabs'][$kk]['widgetList'][$i]['options'] = $widget['options'];
                            }
                        }
                    }
                }
            } elseif (isset($option['widgetList'])) {
                $tableDetails = $option['type'] === CrudFormEnum::FORM_TABLE;
                foreach ($option['widgetList'] as $ii => $itemWidget) {
                    if (isset($itemWidget['cols'])) {
                        $options[$index]['widgetList'][$ii]['cols'] = $this->getFormOptions($itemWidget['cols'], $fieldConfig);
                    } else {
                        $itemWidget = $this->setFormVlaueConfig($itemWidget, $itemWidget['options']['name'], $fieldConfig);

                        $options[$index]['widgetList'][$ii]['options'] = $itemWidget['options'];
                    }
                }
            } else {
                $option          = $this->setFormVlaueConfig($option, $option['options']['name'], $fieldConfig);
                $options[$index] = $option;
            }
        }

        return $options;
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2024/4/2
     */
    public function setFormVlaueConfig(array $item, string $name, array $fieldConfig)
    {
        $item['options']['name'] = str_replace('.', '@', $item['options']['name']);

        $config = $fieldConfig[$name] ?? null;
        if (! $config) {
            return $item;
        }

        if (isset($config['disabled'])) {
            $item['options']['disabled'] = $config['disabled'];
        }

        $item['crud_name']             = $config['crud']['table_name_en'] ?? '';
        $item['data_id']               = $config['data_id'] ?? 0;
        $item['options']['isCityShow'] = '';
        $item['options']['required']   = ! $config['is_default_value_not_null'] ?: $item['options']['required'] ?? false;

        $formValue = str_replace('-', '_', $item['type']);
        switch ($formValue) {
            case CrudFormEnum::FORM_INPUT_SELECT:
                if (! empty($config['defaultValue'])) {
                    $item['options']['defaultValue'] = $config['defaultValue'];
                }
                break;
            case CrudFormEnum::FORM_FILE:
            case CrudFormEnum::FORM_IMAGE:
                //                if (isset($config['options']['size'])) {
                //                    $item['options']['fileMaxSize'] = (int)$config['options']['size'];
                //                }
                //                if (isset($config['options']['max'])) {
                //                    $item['options']['limit'] = (int)$config['options']['max'];
                //                }
                if (isset($config['options']['info']) && $formValue == CrudFormEnum::FORM_IMAGE) {
                    $item['options']['placeholder'] = $config['options']['info'];
                }
                break;
            case CrudFormEnum::FORM_RADIO:
            case CrudFormEnum::FORM_CHECKBOX:
            case CrudFormEnum::FORM_TAG:
            case CrudFormEnum::FORM_CASCADER_RADIO:
            case CrudFormEnum::FORM_CASCADER:
            case CrudFormEnum::FORM_SELECT:
                if (isset($config['data_dict'])) {
                    $item['options']['optionItems']    = $config['data_dict'];
                    $item['options']['customizeItems'] = $config['data_dict'];
                }
                break;
            case CrudFormEnum::FORM_INPUT_NUMBER:
            case CrudFormEnum::FORM_INPUT_PERCENTAGE:
            case CrudFormEnum::FORM_INPUT_PRICE:
                //                if (isset($config['options']['min'])) {
                //                    $item['options']['min'] = (int)$config['options']['min'];
                //                }
                //                if (isset($config['options']['max'])) {
                //                    $item['options']['max'] = (int)$config['options']['max'];
                //                }
                //                if (isset($config['options']['precision'])) {
                //                    $item['options']['precision'] = (int)$config['options']['precision'];
                //                }
                break;
            case CrudFormEnum::FORM_CASCADER_ADDRESS:
                if (isset($config['options']['is_city_show'])) {
                    $item['options']['isCityShow'] = $config['options']['is_city_show'];
                }
                break;
        }

        return $item;
    }

    /**
     * 获取表单中的数据字段.
     * @return array
     */
    public function getFormFields(array $options)
    {
        $fields = [];

        foreach ($options as $option) {
            if (isset($option['cols'])) {
                foreach ($option['cols'] as $col) {
                    if (isset($col['widgetList'])) {
                        foreach ($col['widgetList'] as $item) {
                            if (isset($item['cols'])) {
                                $fields = array_merge($fields, $this->getFormFields($item['cols']));
                            } else {
                                $fields[] = $item['options']['name'];
                            }
                        }
                    }
                }
            } elseif (isset($option['tabs'])) {
                foreach ($option['tabs'] as $tab) {
                    if (isset($tab['widgetList'])) {
                        foreach ($tab['widgetList'] as $widget) {
                            if (isset($widget['cols'])) {
                                $fields = array_merge($fields, $this->getFormFields($widget['cols']));
                            } else {
                                $fields[] = $widget['options']['name'];
                            }
                        }
                    }
                }
            } elseif (isset($option['widgetList'])) {
                foreach ($option['widgetList'] as $itemWidget) {
                    if (isset($itemWidget['cols'])) {
                        $fields = array_merge($fields, $this->getFormFields($itemWidget['cols']));
                    } else {
                        $fields[] = $itemWidget['options']['name'];
                    }
                }
            } else {
                $fields[] = $option['options']['name'];
            }
        }
        return $fields;
    }

    public function getFormOptionItems(array $options)
    {
        $fields = [];

        foreach ($options as $option) {
            if (isset($option['cols'])) {
                foreach ($option['cols'] as $col) {
                    if (isset($col['widgetList'])) {
                        foreach ($col['widgetList'] as $item) {
                            if (isset($item['cols'])) {
                                $fields = array_merge($fields, $this->getFormFields($item['cols']));
                            } else {
                                $fields[] = $item;
                            }
                        }
                    }
                }
            } elseif (isset($option['tabs'])) {
                foreach ($option['tabs'] as $tab) {
                    if (isset($tab['widgetList'])) {
                        foreach ($tab['widgetList'] as $widget) {
                            if (isset($widget['cols'])) {
                                $fields = array_merge($fields, $this->getFormFields($widget['cols']));
                            } else {
                                $fields[] = $widget;
                            }
                        }
                    }
                }
            } elseif (isset($option['widgetList'])) {
                foreach ($option['widgetList'] as $itemWidget) {
                    if (isset($itemWidget['cols'])) {
                        $fields = array_merge($fields, $this->getFormFields($itemWidget['cols']));
                    } else {
                        $fields[] = $itemWidget;
                    }
                }
            } else {
                $fields[] = $option;
            }
        }
        return $fields;
    }

    /**
     * 获取表单中的数据字段.
     * @return array
     */
    public function getFormOptionList(array $options)
    {
        $fields = [];

        foreach ($options as $option) {
            if (isset($option['cols'])) {
                foreach ($option['cols'] as $col) {
                    if (isset($col['widgetList'])) {
                        foreach ($col['widgetList'] as $item) {
                            if (isset($item['cols'])) {
                                $fields = array_merge($fields, $this->getFormOptionItems($item['cols']));
                            } else {
                                $fields[] = $item;
                            }
                        }
                    }
                }
            } elseif (isset($option['tabs'])) {
                foreach ($option['tabs'] as $tab) {
                    if (isset($tab['widgetList'])) {
                        foreach ($tab['widgetList'] as $widget) {
                            if (isset($widget['cols'])) {
                                $fields = array_merge($fields, $this->getFormOptionItems($widget['cols']));
                            } else {
                                $fields[] = $widget;
                            }
                        }
                    }
                }
            } elseif (isset($option['widgetList'])) {
                $tableDetails = $option['type'] === CrudFormEnum::FORM_TABLE;
                foreach ($option['widgetList'] as $itemWidget) {
                    if (isset($itemWidget['cols'])) {
                        $fields = array_merge($fields, $this->getFormOptionItems($itemWidget['cols']));
                    } else {
                        $itemWidget['tableDetails'] = $tableDetails;
                        $fields[]                   = $itemWidget;
                    }
                }
            } else {
                $fields[] = $option;
            }
        }
        return $fields;
    }

    /**
     * 获取列表表单.
     * @return array|mixed
     */
    public function getListForm(SystemCrud $crud)
    {
        return $this->getList(['crud_id' => $crud->id]);
    }

    /**
     * 生成默认表单数据.
     */
    public function saveDefaultValue(int $crudId, SystemCrudField $crudField, SystemCrud $crudInfo)
    {
        try {
            $formInfo = $this->dao->get(['crud_id' => $crudId, 'is_index' => 1]);
            if (! $formInfo) {
                $formInfo = $this->dao->create([
                    'crud_id'  => $crudId,
                    'version'  => 1,
                    'is_index' => 1,
                ]);
            }

            $fieldForm = [];
            $default   = json_decode(file_get_contents(resource_path('/crudform/default.json')), true);
            $formValue = str_replace('_', '-', $crudField->form_value);
            foreach ($default as $item) {
                if ($item['type'] === $formValue) {
                    $fieldForm = $item;
                    break;
                }
            }
            if (! $fieldForm) {
                throw $this->exception('表单类型不存在');
            }
            $fieldForm['name']               = $crudField->field_name;
            $fieldForm['options']['name']    = $crudField->field_name_en;
            $fieldForm['options']['label']   = $crudField->field_name;
            $fieldForm['options']['fieldId'] = $crudField->id;
            $fieldForm['id']                 = str_replace('_', '', $crudField->form_value) . date('YmdHis');
            $formInfo->options               = array_merge($formInfo->options, [$fieldForm]);

            if (! $formInfo->global_options) {
                $formInfo->global_options = json_decode(file_get_contents(resource_path('/crudform/global.json')), true);
            }
            if ($formInfo->fields) {
                $formInfo->fields = array_merge($formInfo->fields, [$crudField->field_name_en]);
            } else {
                $formInfo->fields = [$crudField->field_name_en];
            }

            if ($crudInfo->form_fields) {
                $crudInfo->form_fields = array_merge($crudInfo->form_fields, [$crudField->field_name_en]);
            } else {
                $crudInfo->form_fields = [$crudField->field_name_en];
            }

            $crudInfo->save();
            $formInfo->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getFile()]);
        }
    }
}
