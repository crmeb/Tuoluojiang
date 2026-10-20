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

namespace App\Console\Commands;

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Service\Config\FormService;
use Illuminate\Console\Command;

/**
 * 修复自定义字段业务表列为可空.
 */
class FixCustomFieldNullable extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fix:custom-field-nullable
                            {--types= : 业务类型：1客户 2合同 3联系人 4线索 5商机 6产品，不传则全部}';

    /**
     * @var string
     */
    protected $description = '将自定义字段对应业务表列改为可空，修复未填字段插入时报 Column cannot be null';

    /**
     * 执行命令.
     */
    public function handle(FormService $formService): int
    {
        $typesOption = $this->option('types');
        $types       = $typesOption !== null && $typesOption !== '' ? (int) $typesOption : null;
        if ($types !== null && ! in_array($types, [
            CustomEnum::CUSTOMER,
            CustomEnum::CONTRACT,
            CustomEnum::LIAISON,
            CustomEnum::CLUE,
            CustomEnum::PRODUCT,
            CustomEnum::ODDS,
        ], true)) {
            $this->error('业务类型无效');
            return self::FAILURE;
        }

        $typeNames = [
            CustomEnum::CUSTOMER => '客户',
            CustomEnum::CONTRACT => '合同',
            CustomEnum::LIAISON  => '联系人',
            CustomEnum::CLUE     => '线索',
            CustomEnum::PRODUCT  => '产品',
            CustomEnum::ODDS     => '商机',
        ];

        $results = $formService->fixCustomTableFieldsNullable($types);
        $failed  = false;
        foreach ($results as $type => $ok) {
            $label = $typeNames[$type] ?? (string) $type;
            if ($ok) {
                $this->info("[{$label}] 自定义字段列同步成功");
            } else {
                $failed = true;
                $this->error("[{$label}] 自定义字段列同步失败，请查看日志");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
