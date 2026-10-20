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

namespace App\Observers\Todo;

use App\Constants\TodoEnum;
use App\Http\Model\Customer\Contract;
use Illuminate\Database\Eloquent\Model;

/**
 * 合同待办观察者
 */
class ContractObserver extends BaseObserver
{
    protected function getType(): string
    {
        return TodoEnum::TYPE_CONTRACT;
    }

    protected function getSourceId(Model $model): int
    {
        return (int) $model->id;
    }

    protected function getUserIdsBeforeDelete(Model $model): array
    {
        if (! $model instanceof Contract) {
            return [];
        }
        return array_filter([$model->uid]);
    }

    protected function getUserIdsBeforeUpdate(Model $model): array
    {
        if (! $model instanceof Contract) {
            return [];
        }
        return array_filter([$model->getOriginal('uid')]);
    }
}
