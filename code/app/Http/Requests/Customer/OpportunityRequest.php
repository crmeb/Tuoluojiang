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

namespace App\Http\Requests\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Requests\ApiValidate;
use App\Http\Requests\Traits\DynamicFormValidate;

/**
 * 商机请求验证
 * Class OpportunityRequest.
 */
class OpportunityRequest extends ApiValidate
{
    use DynamicFormValidate;

    /**
     * 表单类型
     */
    protected int $formType = CustomEnum::ODDS;

    /**
     * 设置request.
     */
    public function __construct()
    {
        parent::__construct();
        // 单字段编辑时，设置 only 限制验证字段
        $this->handleSingleFieldEdit();
    }

    /**
     * 验证并获取数据（兼容 Laravel FormRequest 的 validated 方法）.
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validated(): array
    {
        if (empty($this->data)) {
            $this->check();
        }
        return $this->data;
    }

    /**
     * 产品信息不是自定义表单字段，需额外接收.
     */
    protected function getExtraSingleFieldRules(): array
    {
        return [
            'products' => $this->getRequiredArrayRules('products'),
        ];
    }
}
