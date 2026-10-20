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
use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\LabelService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\LeadService;
use App\Http\Service\Customer\LiaisonService;
use App\Http\Service\Customer\OpportunityService;
use App\Http\Service\Customer\ProductService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\RecordService as CustomerRecordService;
use App\Http\Service\Config\FormService;
use App\Jobs\Customer\MergeCustomerJob;
use Tuoluojiang\Open\services\export\BaseImport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 客户导入.
 */
class CustomerImportService extends BaseImport
{
    protected array $formData = [];

    protected array $customType = [
        ViewSearchEnum::VIEW_CUSTOMER      => CustomEnum::CUSTOMER,
        ViewSearchEnum::VIEW_CUSTOMER_SEAS => CustomEnum::CUSTOMER,
        ViewSearchEnum::VIEW_CONTRACT      => CustomEnum::CONTRACT,
        ViewSearchEnum::VIEW_LIAISON       => CustomEnum::LIAISON,
        ViewSearchEnum::VIEW_CLUE          => CustomEnum::CLUE,
        ViewSearchEnum::VIEW_CLUE_SEAS     => CustomEnum::CLUE,
        ViewSearchEnum::VIEW_ODDS          => CustomEnum::ODDS,
        ViewSearchEnum::VIEW_PRODUCT       => CustomEnum::PRODUCT,
    ];

    private string $viewTypes;

    private array $fields;

    private array $clueMap = [];

    public function __construct(protected int $uid, string $filePath = '', string $viewTypes = ViewSearchEnum::VIEW_CUSTOMER, protected int $recordId = 0)
    {
        $this->filePath  = $filePath;
        $this->viewTypes = $viewTypes;
        $this->fields    = collect(app(FormService::class)->getFormDataList($this->customType[$viewTypes], ['options' => fn ($query) => $query->select(['type_name', 'name', 'value', 'level'])]) ?? [])
            ->filter(fn ($item) => ! in_array($item['input_type'], ['file', 'images', 'oaWangeditor']) && ! in_array($item['key'], ['customer_followed', 'customer_status', 'followed', 'contract_followed']))->all();
        $this->formData = $this->formData ?: $this->getFormData();
        parent::__construct($recordId);
    }

    public function processCallback(): callable
    {
        return function ($row) {
            $data = collect($this->fields)->mapWithKeys(function ($item, $index) use ($row) {
                // 取值 + 清洗：去除空格，空字符串转null
                $value = $row[$index] ?? '';
                $value = $value === '' ? null : $value;
                return [$item['key'] => $value];
            })->forget(['id'])->all();
            if ($this->validateData($data)) {
                return $this->dataProcess($data);
            }
            return [];
        };
    }

    public function afterInsertCallback(): ?callable
    {
        if (! in_array($this->viewTypes, [ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS], true)) {
            return null;
        }

        return function (array $batchData) {
            $clueMap = collect($batchData)
                ->pluck('customer_no')
                ->filter()
                ->mapWithKeys(fn ($customerNo) => isset($this->clueMap[$customerNo]) ? [$customerNo => $this->clueMap[$customerNo]] : [])
                ->all();

            if (! $clueMap) {
                return;
            }

            $customerService = app(CustomerService::class);
            $customers       = $customerService->dao->getModel(false)
                ->whereIn('customer_no', array_keys($clueMap))
                ->select(['id', 'customer_no', 'customer_name', 'creator_uid', 'uid', 'userid', 'external_userid'])
                ->get()
                ->keyBy('customer_no');
            if (! $customers || $customers->isEmpty()) {
                foreach (array_keys($clueMap) as $customerNo) {
                    unset($this->clueMap[$customerNo]);
                }
                return;
            }

            $leadService      = app(LeadService::class);
            $followUpService  = app(FollowUpService::class);
            $recordService    = app(CustomerRecordService::class);

            foreach ($clueMap as $customerNo => $clue) {
                $customer = $customers->get($customerNo);
                if (! $customer) {
                    unset($this->clueMap[$customerNo]);
                    continue;
                }
                $clueId = (int) ($clue['id'] ?? 0);
                if (! $clueId) {
                    unset($this->clueMap[$customerNo]);
                    continue;
                }

                $customer->uid             = (int) ($clue['uid'] ?? $customer->uid);
                $customer->userid          = $clue['userid'] ?? '';
                $customer->external_userid = $clue['external_userid'] ?? '';
                $customer->save();
                $followUpService->update(['link_type' => ViewSearchEnum::VIEW_CLUE, 'eid' => $clueId], [
                    'eid'       => $customer->id,
                    'link_type' => ViewSearchEnum::VIEW_CUSTOMER,
                ]);
                $recordService->saveRecord(ViewSearchEnum::VIEW_CUSTOMER, [
                    'eid'            => $customer->id,
                    'type'           => CustomerEnum::OPERATE_CONVERT,
                    'creator_uid'    => (int) ($customer->creator_uid ?? 0),
                    'record_version' => 0,
                    'reason'         => '线索“' . ($clue['name'] ?? '') . '”转客户“' . ($customer->customer_name ?? '') . '”',
                ]);
                $leadService->delete($clueId);
                $customer->external_userid && MergeCustomerJob::dispatch($customer->external_userid);
                unset($this->clueMap[$customerNo]);
            }
        };
    }

    public function beforeInsertCallback(): ?callable
    {
        if (! in_array($this->viewTypes, [ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS], true)) {
            return null;
        }

        return function (array $batchData) {
            $clueNames = collect($batchData)
                ->pluck('clue_id')
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values()
                ->all();

            $clues = $clueNames
                ? app(LeadService::class)->dao->getModel(false)
                    ->whereIn('name', $clueNames)
                    ->orderByDesc('id')
                    ->select(['id', 'userid', 'external_userid', 'uid', 'name'])
                    ->get()
                    ->unique('name')
                    ->keyBy('name')
                : collect();

            return collect($batchData)->map(function (array $data) use ($clues) {
                $clueName = trim((string) ($data['clue_id'] ?? ''));
                unset($data['clue_id']);
                if ($clueName === '') {
                    return $data;
                }

                $clue = $clues->get($clueName);
                if (! $clue) {
                    Log::error('客户导入关联线索不存在', ['线索名称' => $clueName, '客户名称' => $data['customer_name'] ?? '']);
                    return null;
                }

                $this->clueMap[$data['customer_no']] = $clue->toArray();
                return $data;
            })->filter()->values()->all();
        };
    }

    public function setTable(): string
    {
        return match ($this->viewTypes) {
            ViewSearchEnum::VIEW_CUSTOMER ,ViewSearchEnum::VIEW_CUSTOMER_SEAS => app(CustomerService::class)->getTable(),
            ViewSearchEnum::VIEW_CONTRACT => app(OrderService::class)->getTable(),
            ViewSearchEnum::VIEW_LIAISON  => app(LiaisonService::class)->getTable(),
            ViewSearchEnum::VIEW_CLUE ,ViewSearchEnum::VIEW_CLUE_SEAS => app(LeadService::class)->getTable(),
            ViewSearchEnum::VIEW_ODDS    => app(OpportunityService::class)->getTable(),
            ViewSearchEnum::VIEW_PRODUCT => app(ProductService::class)->getTable(),
        };
    }

    public function setFieldMap(): array
    {
        return array_column($this->fields, 'key');
    }

    /**
     * 数据验证方法（直接验证数据并返回bool，替代闭包）.
     * @param array $data 待验证的数据数组
     * @return bool 验证结果：true=通过，false=失败
     */
    private function validateData(array $data): bool
    {
        // 预缓存外部依赖
        $timezone        = config('app.timezone');
        $formData        = $this->formData;
        $customerService = app(CustomerService::class);
        // 将表单验证规则转为Collection，简化遍历逻辑
        return collect($formData)->every(function ($item) use ($data, $timezone) {
            $key       = $item['key'];
            $keyName   = $item['key_name'];
            $required  = $item['required'] ?? false;
            $min       = $item['min'] ?? '';
            $max       = $item['max'] ?? '';
            $type      = strtolower($item['type'] ?? '');
            $inputType = strtolower($item['input_type'] ?? '');
            $isUnique  = $item['uniqued'] ?? false;

            // 仅处理当前字段在待验证数据中的情况（Collection精准匹配）
            if (! collect($data)->has($key)) {
                return true; // 字段不存在，跳过验证
            }

            $value    = $data[$key];
            $errorMsg = '';

            // 按输入类型分分支验证（简化嵌套层级）
            switch ($inputType) {
                // 输入框验证
                case 'input':
                    $len = mb_strlen((string) $value);
                    // 必填验证
                    if ($required && ! $len) {
                        $errorMsg = "请输入{$keyName}";
                        break;
                    }
                    if (empty($value)) {
                        break;
                    }
                    // 长度验证
                    $text = $type == 'number' ? '数字' : '字';
                    if ($max && $len > $max) {
                        $errorMsg = sprintf('%s最多输入%d个%s', $keyName, $max, $text);
                    } elseif ($min && $len < $min) {
                        $errorMsg = sprintf('%s最少输入%d个%s', $keyName, $min, $text);
                    }
                    break;
                    // 选择/勾选/文件验证
                case 'select':
                case 'checked':
                case 'file':
                    //                    if ($type != 'single') {
                    //                        $len = is_array($value) ? count($value) : 0;
                    //                        if ($required && (! $value || ! $len)) {
                    //                            $errorMsg = "请选择{$keyName}";
                    //                            break;
                    //                        }
                    //                    } else {
                    //                        $len = 1;
                    //                        if ($required && ! $value) {
                    //                            $errorMsg = "请选择{$keyName}";
                    //                            break;
                    //                        }
                    //                    }
                    //                    if (empty($value)) {
                    //                        break;
                    //                    }
                    //                    // 数量验证
                    //                    if ($max && $len > $max) {
                    //                        $errorMsg = sprintf('%s最多选择数量%d', $keyName, $max);
                    //                    } elseif ($min && $len < $min) {
                    //                        $errorMsg = sprintf('%s最少选择数量%d', $keyName, $min);
                    //                    }
                    break;
                    // 单选框验证
                case 'radio':
                    if ($required && $value === '') {
                        $errorMsg = "请选择{$keyName}";
                    }
                    break;
                    // 日期验证
                case 'date':
                    if ($required && $value === '') {
                        $errorMsg = "请选择{$keyName}";
                        break;
                    }
                    if (empty($value)) {
                        break;
                    }
                    // 日期范围验证
                    try {
                        $valueCarbon = Carbon::parse($value, $timezone);
                        if ($max && $valueCarbon->gt(Carbon::parse($max, $timezone))) {
                            $errorMsg = sprintf('%s不能晚于%s', $keyName, $max); // 修复原bug：提示文案错误（原写的$value）
                        } elseif ($min && $valueCarbon->lt(Carbon::parse($min, $timezone))) {
                            $errorMsg = sprintf('%s不能早于%s', $keyName, $min); // 修复原bug：提示文案错误
                        }
                    } catch (\Exception $e) {
                        $errorMsg = sprintf('%s日期格式错误', $keyName);
                    }
                    break;
                    // 富文本编辑器验证
                case 'oawangeditor':
                    if ($required && $value === '') {
                        $errorMsg = "请输入{$keyName}";
                        break;
                    }
                    if (empty($value)) {
                        break;
                    }
                    $len = mb_strlen((string) $value);
                    if ($len > 65535) {
                        $errorMsg = '最多输入65535个字';
                    } elseif ($min && $len < $min) {
                        $errorMsg = sprintf('最少输入字数%d', $min);
                    }
                    break;
            }

            // 唯一性验证（独立分支，简化逻辑）
            if (empty($errorMsg) && $isUnique) {
                $uniqueValue = $value;
                // 统一处理唯一性验证的value格式
                switch ($inputType) {
                    case 'select':
                        $uniqueValue = $type == 'single' ? intval(is_array($value) ? ($value[0] ?? 0) : $value) : $uniqueValue;
                        break;
                    case 'radio':
                        $uniqueValue = (int) $value;
                        break;
                    case 'date':
                    case 'input':
                    case 'oawangeditor':
                        break;
                    default:
                        if (is_array($uniqueValue)) {
                            sort($uniqueValue);
                            $uniqueValue = json_encode($uniqueValue);
                        }
                        break;
                }
                // 检查唯一性
                //                if ($customerService->exists([$key => $uniqueValue])) {
                //                    $errorMsg = "{$keyName}已存在";
                //                }
            }
            // 验证失败：记录日志并返回false
            if (! empty($errorMsg)) {
                Log::error('数据验证失败', [
                    '验证字段'   => $key,
                    '字段名称'   => $keyName,
                    '失败原因'   => $errorMsg,
                    '待验证数据' => $data,
                    '字段值'     => $value,
                    '验证规则'   => $item,
                    '验证时间'   => Carbon::now()->toDateTimeString(),
                ]);
                return false;
            }
            return true;
        });
    }

    /**
     * 表单数据处理.
     */
    private function dataProcess(array $data): array
    {
        $formData = collect($this->formData)->keyBy('key');
        $data     = collect($data)->map(function ($value, $key) use ($formData) {
            $item    = $formData->get($key) ?? [];
            $options = collect($item['options'] ?? []);
            if (empty($item) || is_null($value) || $value === '') {
                return $value;
            }
            if ($options->isNotEmpty() || in_array($item['input_type'], ['member', 'checked'])) {
                if (is_string($value) && str_contains($value, ',')) {
                    $value = explode(',', $value);
                } else {
                    $value = is_array($value) ? $value : [$value];
                }
                $result = collect($value)->map(function ($val) use ($options) {
                    $val = trim((string) $val ?? '');
                    if ($val === '') {
                        return '';
                    }
                    if (str_contains($val, '、')) {
                        $names = array_map('trim', explode('、', $val));
                        return $options->filter(fn ($opt) => in_array($opt['name'] ?? '', $names))->pluck('value')->filter()->all();
                    }
                    if (str_contains($val, '/')) {
                        $areaNames = array_map('trim', explode('/', $val));
                        return collect($areaNames)->map(function ($v, $idx) use ($options) {
                            $currentLevel = $idx + 1;
                            return $options->filter(fn ($opt) => ($opt['name'] ?? '') === $v && ($opt['level'] ?? 0) === $currentLevel)->pluck('value')->first() ?: '';
                        })->filter()->toArray();
                    }
                    return $options->filter(fn ($opt) => $opt['name'] == $val)->pluck('value')->first() ?: '';
                });
                if ($result->isNotEmpty()) {
                    $firstItem = $result->first();
                    if ((is_string($firstItem) && $firstItem !== '') || is_numeric($firstItem)) {
                        $value = $firstItem;
                    } else {
                        $value = $result->when($result->count() > 1, fn ($col) => $col, fn ($col) => collect($col->first() ?: []))->filter()->toArray();
                    }
                } else {
                    $value = [];
                }
            }
            switch ($item['input_type'] ?? '') {
                case 'member':
                case 'checked':
                    $value = is_array($value) ? $value : [$value];
                    return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
                case 'select':
                    if ($item['dict_ident'] == 'area_cascade') {
                        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
                    }
                    if ($item['type'] == 'single') {
                        return $value ?: '';
                    }
                    return (! empty($value) && is_array($value)) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
                case 'date':
                    if ($value instanceof \DateTime) {
                        return $value->format('Y-m-d');
                    }
                    return $value ?: null;
                case 'datetime':
                    if ($value instanceof \DateTime) {
                        return $value->format('Y-m-d H:i:s');
                    }
                    return $value ?: null;
                case 'radio':
                case 'input':
                default:
                    return $value ?: '';
            }
        })->all();
        if (in_array($this->viewTypes, [ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS], true)) {
            $data['customer_no'] = ($data['customer_no'] ?? '') ?: app(CustomerService::class)->generateNo();
        }
        $data['created_at']  = date('Y-m-d H:i:s');
        $data['creator_uid'] = $this->uid;
        $data['uid']         = $this->uid;
        if (in_array($this->viewTypes, [ViewSearchEnum::VIEW_CUSTOMER_SEAS, ViewSearchEnum::VIEW_CLUE_SEAS])) {
            $data['uid'] = 0;
        }
        return $data;
    }

    private function getFormData()
    {
        return collect($this->fields)->map(function ($item) {
            if ($item['input_type'] == 'member') {
                $item['options'] = app(AdminService::class)->select([], ['id as value', 'name'])?->toArray();
            }
            if ($item['key'] == 'customer_label') {
                $item['options'] = app(LabelService::class)->select([], ['id as value', 'name'])?->toArray();
            }
            return $item;
        })->all();
    }
}
