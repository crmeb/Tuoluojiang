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

namespace App\Http\Model\Customer\Traits;

use App\Constants\CacheEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use Illuminate\Support\Facades\Cache;

trait CustomFormCasts
{
    public function getCasts()
    {
        return array_merge(parent::getCasts(), $this->getCustomFormCasts());
    }

    protected function getCustomFormCasts(): array
    {
        $table                = $this->getTable();
        $customFormTableTypes = $this->customFormTableTypes();
        if (! isset($customFormTableTypes[$table])) {
            return [];
        }

        try {
            return Cache::tags([CacheEnum::TAG_CUSTOMER])->remember(
                'custom_form_casts:v2:' . $table,
                null,
                function () use ($table, $customFormTableTypes) {
                    $formService = app(FormService::class);

                    return collect($formService->getCustomDataByTypes(
                        $customFormTableTypes[$table],
                        ['key', 'type', 'input_type', 'dict_ident']
                    ))
                        ->filter(fn ($field) => $formService->isJsonCustomField($field, $this->getDictLevel($field['dict_ident'] ?? '')))
                        ->mapWithKeys(fn ($field) => [$field['key'] => 'array'])
                        ->all();
                }
            );
        } catch (\Throwable) {
            return [];
        }
    }

    private function customFormTableTypes(): array
    {
        return [
            'customer'         => CustomEnum::CUSTOMER,
            'contract'         => CustomEnum::CONTRACT,
            'customer_liaison' => CustomEnum::LIAISON,
            'customer_clue'    => CustomEnum::CLUE,
            'customer_odds'    => CustomEnum::ODDS,
            'customer_product' => CustomEnum::PRODUCT,
        ];
    }

    private function getDictLevel(string $dictIdent): ?int
    {
        if (! $dictIdent) {
            return null;
        }

        try {
            return (int) app(DictDataService::class)->max(['type_name' => $dictIdent], 'level');
        } catch (\Throwable) {
            return null;
        }
    }
}
