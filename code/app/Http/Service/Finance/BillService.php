<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Finance;

use App\Http\Dao\Finance\BillDao;
use App\Http\Service\Attach\AttachService;
use App\Task\customer\BillListRecordTask;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use FormBuilder\Exception\FormBuilderException;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 资金流水service
 * Class BillService.
 */
class BillService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * BillService constructor.
     */
    public function __construct(BillDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 处理分类数据.
     * @return array|mixed
     */
    public function handleCateId(mixed $cateId): mixed
    {
        if ($cateId){
            return array_unique(array_reduce($cateId, function ($carry, $item) {
                return array_merge($carry, $item);
            }, []));
        }else{
            return [];
        }
    }

    /**
     * 列表.
     *
     * @param array|string[] $field
     * @param string $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        if (! $where['cate_id']) {
            unset($where['cate_id']);
        } else {
            $where['cate_id'] = $this->handleCateId($where['cate_id']);
        }
        if (str_contains($where['sort'], ' ')) {
            [$key, $val] = explode(' ', $where['sort']);
            $sort        = [$key => $val];
        } else {
            $sort = $where['sort'];
        }
        unset($where['sort']);
        $cateService = app(BillCategoryService::class);
        $list        = $this->dao->getList($where, $field, $page, $limit, $sort, [
            'user' => fn ($query) => $query->select(['id', 'uid', 'name', 'avatar']),
            'cate' => function ($query) {
                $query->select(['id', 'name', 'path']);
            },
            'attachs' => function ($query) {
                $query->select(['id', 'att_dir as src', 'att_size as size', 'relation_id', 'real_name']);
            },
            'files' => function ($query) {
                $query->select(['id', 'att_dir as src', 'att_size as size', 'relation_id', 'real_name']);
            },
            'clientBill' => function ($query) {
                $query->select(['id', 'bill_no', 'cid', 'eid', 'mark']);
            },
            'client'   => fn ($q) => $q->select(['customer.id', 'customer.customer_name', 'customer.uid'])->with(['card']),
            'contract' => fn ($q) => $q->select(['contract.id', 'contract.contract_name as title']),
        ]);
        foreach ($list as &$item) {
            if ($item['cate'] && $item['cate']['path']) {
                $name                 = implode('/', $cateService->column(['ids' => $item['cate']['path']], 'name'));
                $item['cate']['name'] = $name ? $name . '/' . $item['cate']['name'] : $item['cate']['name'];
                unset($name);
            }
            if (! $item['attachs']) {
                $item['attachs'] = $item['files'];
            }
            if ($item['attachs']) {
                foreach ($item['attachs'] as &$attach) {
                    $attach['src'] = link_file($attach['src']);
                }
            }
            $item['mark'] = $item['mark'] ?: ($item['client_bill']['mark'] ?? '');
        }
        $count = $this->dao->count($where);
        unset($where['types']);
        $income = $this->dao->sum($where + ['types' => 1], 'num');
        $expend = $this->dao->sum($where + ['types' => 0], 'num');

        return compact('list', 'count', 'income', 'expend');
    }

    /**
     * 创建财务流水获取表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加', $this->getRankRuleForm(collect()), '/ent/bill');
    }

    /**
     * 修改财务流水获取表单信息.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws FormBuilderException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankInfo = $this->dao->get($id, with: ['files' => fn ($q) => $q->select(['id', 'att_dir as src', 'att_dir as url', 'relation_id', 'real_name'])]);
        if (! $rankInfo) {
            throw $this->exception('数据不存在');
        }

        return $this->createElementForm('编辑', $this->getRankRuleForm(collect($rankInfo->toArray())), '/ent/bill/' . $id, 'put');
    }

    /**
     * 修改财务流水.
     * @param int $id
     * @return bool|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($data['cate_id'] && app()->get(BillCategoryService::class)->value(['id' => $data['cate_id']], 'types') != $data['types']) {
            throw $this->exception('请选择正确的分类');
        }
        $data['pay_type'] = app()->get(PaytypeService::class)->getTypeName((int) $data['type_id'], (int) $data['entid']);

        $info = $this->dao->get($id, with: ['file' => fn ($q) => $q->select(['id', 'name', 'att_dir as url', 'relation_id'])])?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }
        unset($data['uid']);
        // 修改关联附件
        $attaches = $data['file_id'] ?? [];
        unset($data['file_id']);
        $res = $this->dao->update($id, $data);
        if (! in_array(null, $attaches)) {
            $attach = app()->get(AttachService::class);
            $attach->update(['relation_id' => $id, 'relation_type' => 10], ['relation_id' => 0, 'relation_type' => 0]);
            $attach->update(['id' => $attaches], ['relation_id' => $id, 'relation_type' => 10]);
        }

        if ($res) {
            Task::deliver(new BillListRecordTask($data['entid'], (int) $id, auth('admin')->id(), 1, $info));
        }
        return $res;
    }

    /**
     * 保存财务流水.
     * @return bool|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resourceSave(array $data)
    {
        if ($data['cate_id'] && app()->get(BillCategoryService::class)->value(['id' => $data['cate_id']], 'types') != $data['types']) {
            throw $this->exception('请选择正确的分类');
        }
        $data['pay_type'] = app()->get(PaytypeService::class)->getTypeName((int) $data['type_id'], (int) $data['entid']);
        $data['user_id']  = auth('admin')->id();
        $attaches         = $data['file_id'] ?? [];
        unset($data['file_id']);
        $res = $this->dao->create($data);
        // 修改关联附件
        if ($attaches) {
            app()->get(AttachService::class)->update(['id' => $attaches], ['relation_id' => $res->id, 'relation_type' => 10]);
        }
        if ($res) {
            Task::deliver(new BillListRecordTask($data['entid'], (int) $res->id, auth('admin')->id(), 0, $res->toArray()));
        }
        return $res;
    }

    /**
     * 资金趋势
     *
     * @param false $all
     * @param mixed $cateId
     * @param mixed $time
     * @param mixed $income
     * @param mixed $expend
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function getTrend($time, $income, $expend, $all = false, $cateId = [], string $type = '', array $userIds = [], int $entId = 1)
    {
        $time = explode('-', $time);
        if (count($time) != 2) {
            throw $this->exception('参数错误');
        }
        $dayCount = (datetime_timestamp($time[1]) - datetime_timestamp($time[0])) / 86400 + 1;
        $data     = [];
        if ($dayCount == 1) {
            $data = $this->trend($time, 0, $income, $expend, $all, $cateId, $type, $userIds, $entId);
        } elseif ($dayCount > 1 && $dayCount <= 31) {
            $data = $this->trend($time, 1, $income, $expend, $all, $cateId, $type, $userIds, $entId);
        } elseif ($dayCount > 31 && $dayCount <= 92) {
            $data = $this->trend($time, 3, $income, $expend, $all, $cateId, $type, $userIds, $entId);
        } elseif ($dayCount > 92) {
            $data = $this->trend($time, 30, $income, $expend, $all, $cateId, $type, $userIds, $entId);
        }

        return $data;
    }

    /**
     * 资金趋势
     * @param mixed $cateId
     * @param mixed $time
     * @param mixed $num
     * @param mixed $incomeLevel
     * @param mixed $expendLevel
     * @return array
     * @throws BindingResolutionException
     */
    public function trend($time, $num, $incomeLevel, $expendLevel, bool $all = false, array $cateId = [], string $type = '', array $userIds = [], int $entId = 1)
    {
        $incomeRank = $expendRank = $income = $expend = [];
        $pid        = app()->get(BillCategoryService::class)->column(['id' => $cateId], 'pid');
        if ($incomeLevel !== '' && ($type === '' || $type === '1')) {
            $incomeRank = $this->dao->getBillRank($time, 1, $entId, $incomeLevel, $this->dao->getSum($time, 1, $incomeLevel, $entId, $cateId, $userIds), array_unique($pid), cateSearch: $cateId, userIds: $userIds);
        }

        if ($expendLevel !== '' && ($type === '' || $type === '0')) {
            $expendRank = $this->dao->getBillRank($time, 0, $entId, $expendLevel, $this->dao->getSum($time, 0, $expendLevel, $entId, $cateId, $userIds), array_unique($pid), cateSearch: $cateId, userIds: $userIds);
        }
        if ($all) {
            if ($num == 0) {
                $xAxis    = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
                $timeType = '%H';
            } else {
                $dt_start = datetime_timestamp($time[0]);
                $dt_end   = datetime_timestamp($time[1]);
                $xAxis    = [];
                $timeType = '%Y-%m';
                while ($dt_start <= $dt_end) {
                    if ($num == 30) {
                        $xAxis[]  = date('Y-m', $dt_start);
                        $dt_start = strtotime('+1 month', $dt_start);
                        $timeType = '%Y-%m';
                    } else {
                        $xAxis[]  = date('m-d', $dt_start);
                        $dt_start = strtotime("+{$num} day", $dt_start);
                        $timeType = '%m-%d';
                    }
                }
            }

            if ($type === '' || $type === '1') {
                $income = array_column($this->dao->getTrend($time, $timeType, 'sum(num)', 1, $entId, $cateId, $userIds), 'num', 'days');
            }
            if ($type === '' || $type === '0') {
                $expend = array_column($this->dao->getTrend($time, $timeType, 'sum(num)', 0, $entId, $cateId, $userIds), 'num', 'days');
            }
            $data = $series = [];
            foreach ($xAxis as $item) {
                $data['收入金额'][] = isset($income[$item]) ? floatval($income[$item]) : 0;
                $data['支出金额'][] = isset($expend[$item]) ? floatval($expend[$item]) : 0;
            }
            foreach ($data as $key => $item) {
                if ($key == '消费金额' || $key == '充值金额' || $key == '系统增加' || $key == '系统扣除') {
                    $series[] = [
                        'name'   => $key,
                        'data'   => $item,
                        'type'   => 'line',
                        'smooth' => 'true',
                    ];
                } else {
                    $series[] = [
                        'name'       => $key,
                        'data'       => $item,
                        'type'       => 'bar',
                        'yAxisIndex' => 1,
                    ];
                }
            }

            return compact('xAxis', 'series', 'incomeRank', 'expendRank');
        }
        return compact('incomeRank', 'expendRank');
    }

    /**
     * TODO 资金记录导入.
     *
     * @param mixed $data
     * @param mixed $uid
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveBill($data, $uid)
    {
        if (! count($data)) {
            throw $this->exception('导入内容不能为空');
        }
        $cateService = app()->get(BillCategoryService::class);
        $payTypeMap  = app()->get(PaytypeService::class)->column([], 'id', 'name');
        $time        = now()->toDateTimeString();
        return $this->transaction(function () use ($data, $cateService, $uid, $payTypeMap, $time) {
            $tz    = config('app.timezone');
            $saved = 0;
            foreach ($data as $index => $val) {
                $rowNo = $index + 1;
                $typeName = trim((string) ($val['types'] ?? ''));
                if ($typeName === '' || str_contains($typeName, '账目类型') || preg_match('/^示例[:：]/u', $typeName)) {
                    continue;
                }
                if (! in_array($typeName, ['收入', '支出'], true)) {
                    throw $this->exception("第{$rowNo}行账目类型只能填写收入或支出");
                }
                $types = $typeName == '收入' ? 1 : 0;
                $num = $val['num'] ?? 0;
                if (! is_numeric($num) || $num <= 0) {
                    throw $this->exception("第{$rowNo}行账目金额必须大于0");
                }
                $cateName = trim((string) ($val['cate_id'] ?? ''));
                if ($cateName === '') {
                    throw $this->exception("第{$rowNo}行账目分类不能为空");
                }
                $payType = trim((string) ($val['pay_type'] ?? ''));
                if ($payType === '') {
                    throw $this->exception("第{$rowNo}行支付方式不能为空");
                }
                if (! isset($payTypeMap[$payType])) {
                    throw $this->exception("第{$rowNo}行支付方式不存在");
                }
                $editTime = null;
                try {
                    if (! empty($val['edit_time']) && strlen((string) $val['edit_time']) >= 8) {
                        $editTime = Carbon::parse($val['edit_time'], $tz)->toDateTimeString();
                    }
                } catch (\Exception $e) {
                    $editTime = null;
                }
                if (! $editTime) {
                    throw $this->exception("第{$rowNo}行收支时间格式不正确");
                }

                $info = [
                    'types'      => $types,
                    'num'        => $num,
                    'entid'      => 1,
                    'uid'        => uid_to_uuid($uid),
                    'user_id'    => $uid,
                    'pay_type'   => $payType,
                    'type_id'    => $payTypeMap[$payType],
                    'mark'       => $val['mark'] ?? '',
                    'edit_time'  => $editTime,
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
                if (is_numeric($cateName)) {
                    if (! $cateService->exists(['id' => $cateName, 'entid' => 1])) {
                        throw $this->exception("第{$rowNo}行存在无效的分类");
                    }
                    $info['cate_id'] = (int) $cateName;
                } else {
                    if (! $cateService->exists(['types' => $types, 'name' => $cateName, 'entid' => 1])) {
                        $cate = $cateService->create([
                            'types'   => $info['types'],
                            'name'    => $cateName,
                            'entid'   => 1,
                            'cate_no' => $cateService->generateNo(),
                        ]);
                        $info['cate_id'] = $cate->id;
                    } else {
                        $info['cate_id'] = $cateService->value(['types' => $types, 'name' => $cateName, 'entid' => 1], 'id');
                    }
                }
                $res = $this->dao->create($info);
                if (! $res) {
                    throw $this->exception('记账数据保存失败');
                }
                app()->get(BillLogService::class)->saveRecord(1, $res->id, $uid, 0, $res->toArray());
                ++$saved;
            }
            if (! $saved) {
                throw $this->exception('未读取到有效导入数据');
            }
            return true;
        });
    }

    /**
     * 占比分析.
     * @throws BindingResolutionException
     */
    public function getRankAnalysis(string $time, int $cateId, int $types, array $cateIds = [], array $userIds = []): array
    {
        $time = explode('-', $time);
        if (count($time) != 2) {
            throw $this->exception('参数错误');
        }

        $types = $types > 0 ? 1 : 0;
        $entId = 1;

        return $this->dao->getBillRank($time, $types, $entId, 0, $this->dao->getSum($time, $types, 0, $entId, $cateId, $userIds), $cateId, $cateIds, $userIds);
    }

    /**
     * 审核付款记录.
     * @return BaseModel|bool|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveOrUpdate(int $entId, int $linkId, string $linkCate, array $data)
    {
        $billInfo = $this->dao->get(['entid' => $entId, 'link_id' => $linkId, 'link_cate' => $linkCate]);
        $id       = 0;
        $type     = 0;

        if ($billInfo) {
            $param = $billInfo->toArray();

            $id = $billInfo->id;
            if (isset($data['cate_id']) && $data['cate_id'] > 0) {
                $billInfo->cate_id = $data['cate_id'];
            }
            $billInfo->num       = $data['num'];
            $billInfo->edit_time = $data['edit_time'];
            $billInfo->types     = $data['types'];
            $billInfo->mark      = $data['mark'];
            $billInfo->type_id   = $data['type_id'];
            $billInfo->pay_type  = $data['pay_type'];
            $res                 = $billInfo->save();
            $type                = 1;
        } else {
            $uid = uid_to_uuid((int) $data['uid']);
            if (! $uid) {
                throw $this->exception('用户信息获取异常');
            }
            $data['uid'] = $uid;
            $res         = $this->dao->create($data);
            if ($res) {
                $id = $res->id;
            }
            $param = $data;
        }

        if (! $res) {
            throw $this->exception('收支记账保存失败');
        }

        Task::deliver(new BillListRecordTask($data['entid'], (int) $id, auth('admin')->id(), $type, $param));
        return $res;
    }

    public function listForUni(array $where, array $field = ['id', 'cate_id', 'pay_type', 'edit_time', 'num', 'types']): array
    {
        [$page, $limit] = $this->getPageValue();
        if (! $where['cate_id']) {
            unset($where['cate_id']);
        } else {
            $where['cate_id'] = $this->handleCateId($where['cate_id']);
        }
        if (str_contains($where['sort'], ' ')) {
            [$key, $val] = explode(' ', $where['sort']);
            $sort        = [$key => $val];
        } else {
            $sort = $where['sort'];
        }
        unset($where['sort']);
        return $this->dao->listByDate($where, $field, $page, $limit, $sort, [
            'cate' => fn ($q) => $q->select(['id', 'name', 'path']),
        ]);
    }

    public function trendForUni($time, $income, $expend, $all = false, $cateId = [], string $type = ''): array
    {
        $data           = $this->getTrend($time, $income, $expend, $all, $cateId, $type);
        $time           = explode('-', $time);
        $income         = $this->dao->getSumSearch($time, 1, $cateId)->sum('num');
        $expend         = $this->dao->getSumSearch($time, 0, $cateId)->sum('num');
        $profit         = (float) bcsub((string) $income, (string) $expend, 2);
        $count          = $this->dao->getSumSearch($time, [0, 1], $cateId)->count();
        $data['census'] = compact('income', 'expend', 'profit', 'count');
        return $data;
    }

    /**
     * 获取财务流水详情.
     * @param mixed $id
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function detail($id, array $userIds = [])
    {
        $where = ['id' => (int) $id];
        if ($userIds) {
            $where['user_id'] = $userIds;
        }
        $detail = $this->dao->get($where, ['id', 'user_id', 'cate_id', 'num', 'edit_time', 'types', 'type_id', 'pay_type', 'mark', 'link_id', 'order_id', 'link_cate', 'created_at'], [
            'files'      => fn ($q) => $q->select(['id', 'att_dir as src', 'relation_id', 'real_name']),
            'cate'       => fn ($q) => $q->select(['id', 'name', 'path']),
            'user'       => fn ($q) => $q->select(['id', 'uid', 'name', 'avatar']),
            'attachs'    => fn ($q) => $q->select(['id', 'att_dir as src', 'relation_id', 'real_name']),
            'clientBill' => fn ($q) => $q->select(['id', 'bill_no', 'cid', 'eid', 'mark']),
            'client'     => fn ($q) => $q->select(['customer.id', 'customer.customer_name', 'customer.uid'])->with(['card']),
            'contract'   => fn ($q) => $q->select(['contract.id', 'contract.contract_name as title']),
        ])?->toArray();
        if (! $detail) {
            throw $this->exception('数据不存在');
        }
        if ($detail['cate']) {
            $name                   = implode('/', app(BillCategoryService::class)->column(['ids' => $detail['cate']['path']], 'name'));
            $detail['cate']['name'] = $name ? $name . '/' . $detail['cate']['name'] : $detail['cate']['name'];
            unset($name);
        }
        foreach ($detail['files'] as &$item) {
            $item['src'] = link_file($item['src']);
        }
        foreach ($detail['attachs'] as &$item) {
            $item['src'] = link_file($item['src']);
        }
        return $detail;
    }

    /**
     * 获取财务流水表单规则.
     *
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function getRankRuleForm(Collection $collection)
    {
        $cateService = app()->get(BillCategoryService::class);
        $typeService = app()->get(PaytypeService::class);

        $typeId      = $collection->get('type_id', 0);
        $payTypeData = $typeService->getBillPayType(1);
        if (! $payTypeData) {
            if (($typeId = $collection->get('type_id', 0)) && ($payType = $collection->get('pay_type', ''))) {
                $payTypeData = [['value' => $typeId, 'label' => $payType]];
            }
        } else {
            if ($typeId && ! in_array($typeId, array_column($payTypeData, 'value'))) {
                $payTypeData[] = ['value' => $typeId, 'label' => $collection->get('pay_type', '')];
            }
        }
        $typesDisabled = $collection->get('link_id', 0) > 0 && $collection->get('link_cate', '') == 'client';
        $requiredSelectValidate = fn (string $message): array => [
            'required' => true,
            'type'     => 'integer',
            'min'      => 1,
            'message'  => $message,
            'trigger'  => 'change',
        ];
        return [
            Form::radio('types', '账目类型', (int) $collection->get('types', 0))->disabled($typesDisabled)->options([['value' => 1, 'label' => '收入'], ['value' => 0, 'label' => '支出']])->control([
                [
                    'value' => 0,
                    'rule'  => [
                        Form::cascader('cate_id', '账目分类')
                            ->options($cateService->getBillCateTree(0))->appendRule('value', (int) $collection->get('cate_id', 0))
                            ->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])
                            ->validate([$requiredSelectValidate('请选择账目分类')]),
                    ],
                ], [
                    'value' => 1,
                    'rule'  => [
                        Form::cascader('cate_id', '账目分类')
                            ->options($cateService->getBillCateTree(1))->appendRule('value', (int) $collection->get('cate_id', 0))
                            ->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])
                            ->validate([$requiredSelectValidate('请选择账目分类')]),
                    ],
                ],
            ]),
            Form::number('num', '账目金额', $collection->get('num', 0))->min(0.01)->precision(2)->required(),
            Form::select('type_id', '支付方式', $collection->get('type_id', '') ?: '')->options($payTypeData)->validate([$requiredSelectValidate('请选择支付方式')]),
            Form::uploadImage('file_id', '支付凭证', '/api/ent/common/upload', $collection->get('files', []) ? $collection->get('files', [])[0]['src'] : '')->headers([
                'Authorization' => request()->header('Authorization'),
            ])->data(['way' => 3])->uploadType('image')->data(['types' => 'image'])->limit(1),
            Form::dateTime('edit_time', '日期', $collection->get('edit_time', date('Y-m-d H:i:s', time())))->validate([Form::validateStr()->required()->message('请选择日期')]),
            Form::textarea('mark', '备注信息', $collection->get('mark'))->placeholder('请输入备注信息'),
        ];
    }
}
