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

namespace App\Http\Service\Approve;

use App\Constants\CommonEnum;
use App\Http\Service\Attendance\AttendanceStatisticsService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Customer\ContractService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\InvoiceService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\PaymentService;
use App\Http\Service\Finance\BillCategoryService;
use App\Http\Service\Finance\PaytypeService;
use App\Http\Service\System\RolesService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 审批辅助类(处理系统相关审批表单).
 */
class ApproveAssistService
{
    /**
     * 获取客户列表
     * 传参客户ID(customer_id)则选中并禁用.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function customerList(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(CustomerService::class)->value($args['value'], 'customer_name') ?: '';
        }
        $field = ['id as value', 'customer_name as label'];
        if ($args['data']['customer_id']) {
            $args['child']['options']           = toArray(app()->get(CustomerService::class)->select(['id' => $args['data']['customer_id']], $field));
            $args['child']['value']             = (int) $args['data']['customer_id'];
            $args['child']['props']['disabled'] = true;
        } else {
            $where                    = ['uid' => app()->get(RolesService::class)->getDataUids($args['uid'])];
            $args['child']['options'] = toArray(app()->get(CustomerService::class)->select($where, $field));
            if ($args['child']['options']) {
                $args['child']['value'] = $args['child']['options'][0]['value'];
            }
        }
        return $args['child'];
    }

    /**
     * 获取客户信息
     * 传参客户ID(customer_id)则选中并禁用.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function eid(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(CustomerService::class)->value($args['value'], 'customer_name') ?: '';
        }
        $field = ['id as value', 'customer_name as label'];
        if ($args['data']['customer_id']) {
            $args['child']['options']           = toArray(app()->get(CustomerService::class)->select(['id' => $args['data']['customer_id']], $field));
            $args['child']['value']             = (int) $args['data']['customer_id'];
            $args['child']['props']['disabled'] = true;
        } else {
            $where                    = ['uid' => app()->get(RolesService::class)->getDataUids($args['uid'])];
            $args['child']['options'] = toArray(app()->get(CustomerService::class)->select($where, $field));
            if ($args['child']['options']) {
                $args['child']['value'] = $args['child']['options'][0]['value'];
            }
        }
        return $args['child'];
    }

    /**
     * 获取签约方式.
     */
    public function signType(...$args): array|string
    {
        if (isset($args['value'])) {
            return match ((int) $args['value']) {
                1       => '线下签约',
                2       => '电子签约',
                default => '未知签约方式',
            };
        }
        $args['child']['options'] = [
            ['value' => 1, 'label' => '线下签约'],
            ['value' => 2, 'label' => '电子签约'],
            ['value' => 3, 'label' => '未知签约方式'],
        ];
        return $args['child'];
    }

    /**
     * 获取订单列表
     * 传参客户ID(customer_id)则选中并禁用.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function contractList(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(OrderService::class)->value($args['value'], 'contract_no') ?: '';
        }
        if ($args['data']['contract_id']) {
            $where = [
                'id' => $args['data']['contract_id'],
            ];
            $args['child']['value']             = (int) $args['data']['contract_id'];
            $args['child']['props']['disabled'] = true;
        } elseif ($args['data']['customer_id']) {
            $where = [
                'eid' => $args['data']['customer_id'],
            ];
        } else {
            $where['uid'] = $args['uid'];
        }
        $field                    = ['id as value', 'contract_no as label'];
        $args['child']['options'] = app(OrderService::class)->select($where, $field)?->toArray();
        if ($args['child']['options']) {
            $args['child']['value'] = $args['child']['value'] ?? $args['child']['options'][0]['value'];
        }
        return $args['child'];
    }

    /**
     * 财务收入分类.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function incomeCategories(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(BillCategoryService::class)->value(is_array($args['value']) ? end($args['value']) : $args['value'], 'name') ?: '';
        }
        $field                             = $args['origin'] === CommonEnum::ORIGIN_WEB ? ['id', 'id as value', 'name as label', 'pid'] : ['id', 'id as value', 'name as text', 'pid'];
        $list                              = toArray(app()->get(BillCategoryService::class)->select(['types' => 1], $field));
        $args['child']['props']['options'] = $list ? get_tree_children($list) : [];
        return $args['child'];
    }

    /**
     * 财务支出分类.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function expenditureCategories(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(BillCategoryService::class)->value(is_array($args['value']) ? end($args['value']) : $args['value'], 'name') ?: '';
        }
        $field                             = $args['origin'] === CommonEnum::ORIGIN_WEB ? ['id', 'id as value', 'name as label', 'pid'] : ['id', 'id as value', 'name as text', 'pid'];
        $list                              = toArray(app()->get(BillCategoryService::class)->select(['types' => 0], $field));
        $args['child']['props']['options'] = get_tree_children($list);
        return $args['child'];
    }

    /**
     * 支付方式.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function payType(...$args): array|string
    {
        if (isset($args['value'])) {
            return app(PaytypeService::class)->value($args['value'], 'name') ?: '';
        }
        $field                    = ['id as value', 'name as label'];
        $args['child']['options'] = app(PaytypeService::class)->setDefaultSort('sort')->select(['status' => 1], $field)->toArray();
        if ($args['child']['options']) {
            $args['child']['value'] = $args['child']['options'][0]['value'];
        }
        return $args['child'];
    }

    /**
     * 续费类型.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function renewalType(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(DictDataService::class)->value(['value' => $args['value'], 'type_name' => 'client_renew'], 'name') ?: '';
        }
        $field                    = ['name as label', 'value'];
        $args['child']['options'] = app()->get(DictDataService::class)->setDefaultSort('sort')->select(['status' => 1, 'type_name' => 'client_renew'], $field)?->toArray();
        if ($args['child']['options']) {
            $args['child']['value'] = $args['child']['options'][0]['value'];
        }
        return $args['child'];
    }

    /**
     * 回显付款单ID
     * 传参付款单ID(bill_id).
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function billId(...$args): array|string
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if ($args['data']['bill_id']) {
            $args['child']['value'] = is_string($args['data']['bill_id']) ? explode(',', $args['data']['bill_id']) : $args['data']['bill_id'];
        } else {
            $args['child']['value'] = [];
        }
        return $args['child'];
    }

    /**
     * 计算付款单金额
     * 选填付款记录ID（bill_id）.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function billAmount(...$args): array|string
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if ($args['data']['bill_id']) {
            $args['data']['bill_id'] = is_string($args['data']['bill_id']) ? explode(',', $args['data']['bill_id']) : $args['data']['bill_id'];
            $args['child']['value']  = app()->get(PaymentService::class)->sum(['id' => $args['data']['bill_id']], 'num');
        } else {
            $args['child']['value'] = 0;
        }
        $args['child']['props']['disabled'] = true;
        return $args['child'];
    }

    /**
     * 计算付款单金额
     * 选填付款记录ID（contract_id）.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function collectionAmount(...$args): mixed
    {
        if (isset($args['value'])) {
            return $args['value'];
        }if ($args['data']['contract_id']) {
            $contract = app()->get(OrderService::class)->get(['id' => $args['data']['contract_id']], ['contract_price','surplus']);
            $args['child']['value']  = $contract ? ($contract->surplus > 0 ? $contract->surplus : $contract->contract_price) : 0;
        } else {
            $args['child']['value'] = 0;
        }
        $args['child']['props']['disabled'] = false;
        return $args['child'];
    }

    /**
     * 计算开票金额
     * 选填付款记录ID（bill_id）.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoiceAmount(...$args): mixed
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if ($args['data']['bill_id']) {
            $args['data']['bill_id'] = is_string($args['data']['bill_id']) ? explode(',', $args['data']['bill_id']) : $args['data']['bill_id'];
            $args['child']['value']  = app()->get(PaymentService::class)->sum(['id' => $args['data']['bill_id']], 'num');
        } else {
            $args['child']['value'] = 0;
        }
        return $args['child'];
    }

    /**
     * 获取开票方式.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoicingMethod(...$args): array|string
    {
        $args['child']['options'] = [
            ['value' => 'mail', 'label' => '电子'],
            ['value' => 'express', 'label' => '纸质'],
        ];
        if (isset($args['value'])) {
            foreach ($args['child']['options'] as $v) {
                if ($args['value'] == $v['value']) {
                    return $v['label'];
                }
            }
        }
        $args['child']['value'] = 'mail';
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'collect_type') ?: 'mail';
        }
        return $args['child'];
    }

    /**
     * 发票类型.
     */
    public function invoiceType(...$args): array|string
    {
        $args['child']['options'] = [
            ['value' => 1, 'label' => '个人普通发票'],
            ['value' => 2, 'label' => '企业普通发票'],
            ['value' => 3, 'label' => '企业专用发票'],
        ];
        if (isset($args['value'])) {
            foreach ($args['child']['options'] as $v) {
                if ($args['value'] == $v['value']) {
                    return $v['label'];
                }
            }
        }
        $args['child']['value'] = 2;
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = (int) app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'types') ?: 2;
        }
        return $args['child'];
    }

    /**
     * 已开发票列表
     *  必传发票ID（invoice_id）.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoiceList(...$args): array|string
    {
        if (isset($args['value'])) {
            return app()->get(InvoiceService::class)->value($args['value'], 'title') ?: '';
        }
        $field                    = ['id as value', 'title as label'];
        $args['child']['options'] = toArray(app()->get(InvoiceService::class)->select(['status' => 5], $field));
        if ($args['data']['invoice_id']) {
            $args['child']['value']             = (int) $args['data']['invoice_id'];
            $args['child']['props']['disabled'] = true;
        } else {
            if ($args['child']['options']) {
                $args['child']['value'] = $args['child']['options'][0]['value'];
            }
        }
        return $args['child'];
    }

    /**
     * 可申请假期类型列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function holidayType(...$args)
    {
        if (isset($args['value'])) {
            return app()->get(ApproveHolidayTypeService::class)->value($args['value'], 'name');
        }
        $args['child']['options'] = app()->get(ApproveHolidayTypeService::class)->getSelectList($args['uid']);
        return $args['child'];
    }

    /**
     * 回显假期时长.
     * @return mixed
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function leaveDuration(...$args)
    {
        if (isset($args['value'])) {
            if ($args['value']['timeType'] == 'day') {
                return [
                    [
                        'label' => '开始时间',
                        'value' => $args['value']['dateStart'] . ($args['value']['timeStart'] ? ' 上午' : ' 下午'),
                    ],
                    [
                        'label' => '结束时间',
                        'value' => $args['value']['dateEnd'] . ($args['value']['timeEnd'] ? ' 上午' : ' 下午'),
                    ],
                    [
                        'label' => '时长',
                        'value' => $args['value']['duration'] . ' 天',
                    ],
                ];
            }
            return [
                [
                    'label' => '开始时间',
                    'value' => $args['value']['dateStart'],
                ],
                [
                    'label' => '结束时间',
                    'value' => $args['value']['dateEnd'],
                ],
                [
                    'label' => '时长',
                    'value' => $args['value']['duration'] . ' 小时',
                ],
            ];
        }
    }

    /**
     * 异常日期列表.
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function attendanceExceptionDate(...$args): mixed
    {
        if (isset($args['value'])) {
            return app()->get(AttendanceStatisticsService::class)->get($args['value'])?->date;
        }
        $args['child']['options'] = app()->get(AttendanceStatisticsService::class)->getAbnormalDateList($args['uid'], true);
        return $args['child'];
    }

    /**
     * 异常日期记录.
     * 无效的历史打卡序号返回空展示值，避免影响审批列表读取。
     * @param mixed ...$args 审批字段上下文，value.value 为 1–4 的打卡序号
     * @return array|string 字段配置或异常打卡时间
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function attendanceExceptionRecord(...$args): mixed
    {
        if (isset($args['value'])) {
            $value = is_array($args['value']) ? ($args['value']['value'] ?? null) : null;
            if (! in_array($value, [1, 2, 3, 4, '1', '2', '3', '4'], true)) {
                return '';
            }

            $recordId   = (int) $value - 1;
            $abnormalId = app()->get(ApproveContentService::class)->value(['apply_id' => ($args['value']['apply_id'] ?? 0), 'symbol' => 'attendanceExceptionDate'], 'value');
            return app()->get(AttendanceStatisticsService::class)->getRecordTimeWithAbnormalId((int) $abnormalId, $recordId);
        }

        $args['child']['options'] = [];
        return $args['child'];
    }

    public function invoicingEmail(...$args)
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'collect_email') ?: '';
        }
        return $args['child'];
    }

    public function invoiceHeader(...$args)
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            // 无历史发票抬头时默认取客户名称
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'title')
                ?: (app()->get(CustomerService::class)->value($args['data']['customer_id'], 'customer_name') ?: '');
        }
        return $args['child'];
    }

    public function dutyParagraph(...$args)
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'ident') ?: '';
        }
        return $args['child'];
    }

    public function liaisonMan(...$args)
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'collect_name') ?: '';
        }
        return $args['child'];
    }

    public function telephone(...$args)
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'collect_tel') ?: '';
        }
        return $args['child'];
    }

    public function mailingAddress(...$args)
    {
        if (isset($args['value'])) {
            return $args['value'];
        }
        if (isset($args['data']['customer_id']) && $args['data']['customer_id']) {
            $args['child']['value'] = app()->get(InvoiceService::class)->value(['eid' => $args['data']['customer_id']], 'mail_address') ?: '';
        }
        return $args['child'];
    }
}
