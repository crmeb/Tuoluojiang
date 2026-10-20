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

namespace App\Http\Service\ImportExport;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Dao\Other\ExportRecordDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\LeadService;
use App\Http\Service\Customer\LiaisonService;
use App\Http\Service\Customer\OpportunityService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Frame\FrameAssistService;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 数据导入导出记录.
 * @mixin ExportRecordDao
 */
class RecordService extends BaseService
{
    public function __construct(ExportRecordDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = ['admin']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 导入模板
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function importTemp(string $types): string
    {
        $fileDir = public_path('exports');
        if (! is_dir($fileDir)) {
            mkdir($fileDir, 0755, true);
        }
        $storageDir = storage_path('exports');
        if (! is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        return match ($types) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(FormService::class)->getImportTemp(CustomEnum::CUSTOMER),
            ViewSearchEnum::VIEW_ODDS => app(FormService::class)->getImportTemp(CustomEnum::ODDS),
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS => app(FormService::class)->getImportTemp(CustomEnum::CLUE),
            ViewSearchEnum::VIEW_CONTRACT => app(FormService::class)->getImportTemp(CustomEnum::CONTRACT),
            ViewSearchEnum::VIEW_LIAISON  => app(FormService::class)->getImportTemp(CustomEnum::LIAISON),
            default                       => ''
        };
    }

    /**
     * 导出数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function exportData(string $types, int $uid): void
    {
        $service = match ($types) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(CustomerService::class),
            ViewSearchEnum::VIEW_ODDS => app(OpportunityService::class),
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS => app(LeadService::class),
            ViewSearchEnum::VIEW_CONTRACT => app(OrderService::class),
            ViewSearchEnum::VIEW_LIAISON  => app(LiaisonService::class),
            default                       => throw $this->exception('导出业务类型错误')
        };
        $paramsName = $service->searchField($types);
        $where      = collect();
        collect($paramsName ?: [])->each(function ($item) use ($where) {
            $key = $item[2] ?? $item[0];
            $where->put($key, request()->post($item[0], $item[1]));
        });
        $where->put('view_search', request()->post('view_search', 1));
        $service->dataExport($where->all(), $types, $uid);
    }

    /**
     * 导入数据.
     */
    public function importData(string $types, int $fileId, int $uid): void
    {
        $service = match ($types) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(CustomerService::class),
            ViewSearchEnum::VIEW_ODDS => app(OpportunityService::class),
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS => app(LeadService::class),
            ViewSearchEnum::VIEW_CONTRACT => app(OrderService::class),
            ViewSearchEnum::VIEW_LIAISON  => app(LiaisonService::class),
            default                       => null
        };
        $service->dataImport($types, $fileId, $uid);
    }

    /**
     * 删除记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteRecord(int $id, int $uid): void
    {
        $info = $this->dao->get($id, with: ['attach']);
        if (! $info) {
            throw $this->exception('数据不存在');
        }
        $subUid = app(FrameAssistService::class)->getScopeUid($uid);
        if (! in_array($info->uid, $subUid)) {
            throw $this->exception('无权限操作');
        }
        $this->transaction(function () use ($info) {
            if ($info->attach) {
                app(AttachService::class)->delImg($info->attach?->id);
            } else {
                $info->file_path && unlink(public_path('exports/' . $info->name));
            }
            $info->delete();
            return true;
        });
    }
}
