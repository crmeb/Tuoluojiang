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

namespace Tuoluojiang\Open\services;

use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\UI\Elm\Components\DatePicker;
use FormBuilder\UI\Elm\Components\Radio;
use FormBuilder\UI\Elm\Components\Switches;
use Illuminate\Support\Collection;

/**
 * Class FormService.
 */
class FormService extends Elm
{
    protected int $col = 24;

    protected string $name = '';

    protected string $key = '';

    protected string $keyName = '';

    public function getCol(): int
    {
        return $this->col;
    }

    public function setCol(int $col): void
    {
        $this->col = $col;
    }

    /**
     * 创建Form表单规则.
     * @return array|mixed|void
     * @throws FormBuilderException
     */
    public function createFormRule(Collection $data)
    {
        $type          = $data->get('type', 'text');
        $this->key     = (string) $data->get('key');
        $this->keyName = (string) $data->get('key_name');

        $form = match ($type) {
            'text'      => $this->createTextFormRule($data->get('input_type', ''), $data),
            'radio'     => $this->createRadioFormRule($data),
            'textarea'  => $this->createTextareaFormRule($data),
            'upload'    => $this->createUpoadFormRule((int) $data->get('upload_type', 0), $data),
            'checkbox'  => $this->createCheckboxFormRule($data),
            'select'    => $this->createSelectFormRule($data),
            'switches'  => $this->createSwitchesFormRule($data),
            'date'      => $this->createDateFormRule($data),
            'dateTime'  => $this->createDateTimeFormRule($data),
            'dateRange' => $this->createDateRangeFormRule($data),
            default     => [],
        };

        $placeholder = $data->get('placeholder', '');
        if ($form && $placeholder && in_array($type, ['text', 'number'])) {
            $form->placeholder($placeholder);
        }

        if ($data->get('required')) {
            if (str_contains($type, 'date')) {
                $form->validate([$this->validateStr()->required()->message('请选择' . $data->get('key_name'))]);
            } else {
                $form->required();
            }
        }

        $info = $data->get('desc');
        if ($info) {
            $form->info($info);
        }

        return $form->col($this->getCol());
    }

    /**
     * 创建单行表单规则.
     */
    protected function createTextFormRule(string $type, Collection $data)
    {
        return match ($type) {
            'number' => $this->number($this->key, $this->keyName, $data->get('value', 0))
                ->precision($data->get('decimal_place', 0))
                ->min($data->get('min', 0))
                ->max($data->get('max', 0)),
            'dateTime' => $this->dateTime($this->key, $this->keyName, $data->get('value'))
            ,
            'color' => $this->color($this->key, $this->keyName, $data->get('value', '')),
            default => $this->input($this->key, $this->keyName, $data->get('value', ''))
                ->minlength($data->get('min', 0))
                ->maxlength($data->get('max', 0)),
        };
    }

    /**
     * 创建Radio规则.
     * @return null|Radio
     */
    protected function createRadioFormRule(Collection $data)
    {
        return $this->radio($this->key, $this->keyName, (int) $data->get('value', 0))->options($data->get('options', []));
    }

    /**
     * switches表单规则.
     * @return null|Switches
     */
    protected function createSwitchesFormRule(Collection $data)
    {
        // return $this->switches($this->key, $this->keyName , $data->get('value', 0))
        //             ->inactiveValue($options[0]['value'] ?? 0)
        //             ->activeValue($options[1]['value'] ?? 1)
        //             ->inactiveText($options[0]['label'] ?? '关闭')
        //             ->activeText($options[1]['label'] ?? '开启');
    }

    /**
     * 创建关联规则.
     * @return array
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
     * 创建多行文本框.
     * @return mixed
     */
    protected function createTextareaFormRule(Collection $data)
    {
        return $this->textarea($this->key, $this->keyName, $data->get('value', ''))->rows(6);
    }

    /**
     * 创建上传组件表单.
     */
    protected function createUpoadFormRule(int $type, Collection $data)
    {
        $form = null;
        switch ($type) {
            case 1:
                $form = $this->frameImage($this->key, $this->keyName, get_image_frame_url(['field' => $this->key, 'type' => 1]), $data->get('value', ''))
                    ->icon('ios-image')->width('950px')->height('420px');
                break;
            case 2:
                $form = $this->frameImages($this->key, $this->keyName, get_image_frame_url(['field' => $this->key, 'type' => 2]), $data->get('value', ''))
                    ->maxLength(5)->icon('ios-images')->width('950px')->height('420px');
                break;
            case 3:
                $form = $this->uploadFile($this->key, $this->keyName, '/system/attach/file/1', $data->get('value'))
                    ->name('file')->headers(['Authorization' => request()->header('Authorization'),
                             ]);
                break;
        }
        return $form;
    }

    /**
     * 创建单选框.
     * @throws FormBuilderException
     */
    protected function createCheckboxFormRule(Collection $data)
    {
        return $this->checkbox($this->key, $this->keyName, $data->get('value', []))->options($data->get('options', []));
    }

    /**
     * 创建选择框表单.
     * @throws FormBuilderException
     */
    protected function createSelectFormRule(Collection $data)
    {
        $type = $data->get('input_type', '');
        if ($type == 'multiple') {
            $value = $data->get('value', []);
            if (! is_array($value)) {
                $value = '';
            }
            return $this->select($this->key, $this->keyName, $value)
                ->multiple(true)->options($data->get('options', []));
        }
        return $this->cascader($this->key, $this->keyName, (array) $data->get('value', []))
            ->options($data->get('options', []))->props(['multiple' => true]);
    }

    /**
     * 日期表单.
     * @return DatePicker
     */
    private function createDateFormRule(Collection $data)
    {
        return $this->date($this->key, $this->keyName, $data->get('value'));
    }

    /**
     * 日期区间选择表单.
     * @return DatePicker
     */
    private function createDateRangeFormRule(Collection $data)
    {
        return $this->dateRange($this->key, $this->keyName, $data->get('value'));
    }

    /**
     * 单选日期时间表单.
     * @return DatePicker
     */
    private function createDateTimeFormRule(Collection $data)
    {
        return $this->dateTime($this->key, $this->keyName, $data->get('value'));
    }
}
