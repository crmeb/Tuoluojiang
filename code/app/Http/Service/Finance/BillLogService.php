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

namespace App\Http\Service\Finance;

use App\Http\Dao\Finance\BillLogDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 客户发票操作日志
 * Class BillLogService.
 */
class BillLogService extends BaseService
{
    use ResourceServiceTrait;

    /**
     * @var array|string[] 账目类型
     */
    public array $types = [
        0 => '支出',
        1 => '收入',
    ];

    /**
     * @var array|string[] 操作记录
     */
    public array $operationType = [
        0 => '创建账目信息',
        1 => '编辑账目信息',
    ];

    /**
     * BillLogService constructor.
     */
    public function __construct(BillLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'created_at', array $with = ['card']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        foreach ($list as &$item) {
            $item['operation_name'] = $this->operationType[$item['type']] ?? '';
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 保存操作记录.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function saveRecord(int $entId, int $billListId, int $uid, int $type, array $param)
    {
        if (!isset($this->operationType[$type])) {
            throw $this->exception('操作类型错误');
        }

        $res = $this->dao->create([
            'uid'          => $uid,
            'type'         => $type,
            'entid'        => $entId,
            'bill_list_id' => $billListId,
            'operation'    => $this->generatorOperation($billListId, $type, $param),
        ]);
        if (!$res) {
            throw $this->exception('操作记录添加失败');
        }
        return $res;
    }

    /**
     * 生成操作记录内容.
     * @throws BindingResolutionException
     */
    public function generatorOperation(int $billListId, int $type, array $param): array
    {
        return match ($type) {
            0       => $this->getBillResult($param),
            1       => $this->getBillChange($billListId, $param),
            default => [],
        };
    }

    /**
     * 获取开票结果.
     * @throws BindingResolutionException
     */
    public function getBillResult(array $param): array
    {
        $cateId   = $param['cate_id'] ?? 0;
        $cateName = '';
        if ($cateId) {
            $cateName = app()->get(BillCategoryService::class)->setEntValue($param['entid'])->value($param['cate_id'], 'name');
        }
        return [
            ['name' => '记账类型：', 'val' => $this->types[$param['types']]],
            ['name' => '账目分类：', 'val' => $cateName],
            ['name' => '账目金额(元)：', 'val' => $param['num'] ?? ''],
            ['name' => '支付方式：', 'val' => $param['pay_type'] ?? ''],
            ['name' => '收支时间：', 'val' => $param['edit_time'] ?? ''],
            ['name' => '操作时间：', 'val' => date('Y-m-d H:i:s')],
        ];
    }

    /**
     * 获取发票变更.
     * @throws BindingResolutionException
     */
    public function getBillChange(int $billListId, array $before): ?array
    {
        $info = app()->get(BillService::class)->get($billListId, with: ['file' => fn($q) => $q->select(['id', 'name', 'att_dir as url', 'relation_id'])])?->toArray();
        if (!$info) {
            return [];
        }
        $record = [];
        $before['types'] != $info['types'] && $record[] = ['name' => '记账类型：', 'val' => ($this->types[$before['types']] ?? '') . ' 改为 ' . $this->types[$info['types']] ?? ''];

        if ($before['cate_id'] != $info['cate_id']) {
            $billCategoryService = app()->get(BillCategoryService::class);
            $beforeName          = $billCategoryService->setEntValue($before['entid'])->value($before['cate_id'], 'name');
            $afterName           = $billCategoryService->setEntValue($info['entid'])->value($info['cate_id'], 'name');
            $record[]            = ['name' => '账目分类：', 'val' => $beforeName . ' 改为 ' . $afterName];
        }

        $before['num'] != $info['num'] && $record[] = ['name' => '账目金额：', 'val' => ($before['num'] ?? '') . ' 改为 ' . ($info['num'] ?? '')];
        $before['pay_type'] != $info['pay_type'] && $record[] = ['name' => '支付方式：', 'val' => $before['pay_type'] . ' 改为 ' . $info['pay_type']];
        $before['edit_time'] != $info['edit_time'] && $record[] = ['name' => '收支时间：', 'val' => ($before['edit_time'] ?? '') . ' 改为 ' . ($info['edit_time'] ?? '')];
        $before['mark'] != $info['mark'] && $record[] = ['name' => '备注：', 'val' => $before['mark'] . ' 改为 ' . $info['mark']];
        ($before['file'] ? $before['file']['id'] : []) != ($info['file'] ? $info['file']['id'] : []) && $record[] = ['name' => '支付凭证：', 'val' => [
            'before' => $before['file']['url'] ? link_file($before['file']['url']) : '',
            'after'  => $info['file']['url'] ? link_file($info['file']['url']) : '',
        ]];
        $record[] = ['name' => '操作时间：', 'val' => date('Y-m-d H:i:s')];
        return $record;
    }
}
