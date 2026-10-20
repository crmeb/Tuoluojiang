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

namespace App\Http\Dao\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\Order;
use App\Http\Model\Customer\Payment;
use App\Http\Model\Customer\Product;
use App\Http\Model\Customer\ProductAssist;
use App\Http\Model\Customer\ProductCategory;
use App\Http\Service\Customer\CustomerTrait;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 订单Dao.
 */
class OrderDao extends BaseDao
{
    use TogetherSearchTrait;
    use CustomerTrait;
    use ListSearchTrait;

    protected string $joinTable;

    protected $otherSearch = [
        'statistics_type',
        'types',
        'scope_frame',
        'before_salesman',
        'customer_repeat_check',
        'subscribe_uid',
        'payment_time',
        'contract_customer',
        'product_name',
        'uid_scope',
    ];

    /**
     * @return BaseModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function clientContractJoinModel()
    {
        $model     = $this->getModel();
        $joinModel = app(Customer::class);

        $joinTable = $this->joinTable = $joinModel->getTable();

        $table = $this->table = $model->getTable();

        return $model->join($joinTable, $table . '.eid', '=', $joinTable . '.id');
    }

    /**
     * 获取合同订单条数.
     * @throws BindingResolutionException
     */
    public function getClientContractCount(array $where = []): int
    {
        return $this->clientContractJoinModel()->where($where)->count();
    }

    /**
     * 获取合同订单列表.
     * @throws BindingResolutionException
     */
    public function getClientContractList(array $where = [], int $page = 0, int $limit = 0): array
    {
        $model     = $this->clientContractJoinModel();
        $table     = $this->table;
        $joinTable = $this->joinTable;
        return $model->where($where)
            ->whereNotNull($table . '.end_date')
            ->groupBy($table . '.id')
            ->select([
                $table . '.contract_name as title',
                $table . '.contract_price as price',
                $table . '.start_date',
                $table . '.end_date',
                $table . '.uid',
                $table . '.id',
                $joinTable . '.customer_name as name',
            ])
            ->with([
                'card' => fn ($qq) => $qq->where('admin.entid', $where['entid']),
            ])
            ->when($page && $limit, fn ($q) => $q->forPage($page, $limit))
            ->get()->toArray();
    }

    /**
     * 合同订单类型分析.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRankByCategory(string $time, array $userIds, array $categoryId, array $types = [0, 1]): array
    {
        $prefix     = Config::get('database.connections.mysql.prefix');
        $categoryId = collect($categoryId)->map(function ($item) {
            $array = json_decode($item, true);
            return collect($array)->last();
        });
        $model = $this->getModel(false);
        $table = $model->getTable();

        $joinModel = app(Payment::class);
        $joinTable = $joinModel->getTable();

        $preTable     = $prefix . $table;
        $preJoinTable = $prefix . $joinTable;
        return $model->join($joinTable, function ($join) use ($joinTable, $table, $time, $types) {
            $join->on($joinTable . '.cid', '=', $table . '.id')->whereIn($joinTable . '.types', $types)
                ->where($joinTable . '.status', 1)->where($joinTable . '.entid', 1)
                ->when($time, function ($query) use ($time, $joinTable) {
                    $query->whereBetween($joinTable . '.date', explode('-', $time));
                });
        })->when($categoryId, function ($query) use ($categoryId) {
            $categoryId->each(function ($item) use ($query) {
                $query->orWhereJsonContains('contract_category', $item);
            });
        })->whereIn($joinTable . '.uid', $userIds)
            ->selectRaw("`{$preTable}`.`contract_category`, sum(`{$preJoinTable}`.`num`) as `price`")->first()->toArray();
    }

    /**
     * 批量获取合同订单分类统计数据（优化N+1查询）.
     *
     * @param string $time 时间区间
     * @param array $userIds 用户ID列表
     * @param array $groupKeys 分组键列表（分类ID数组）
     * @return array<string, array{price: string, count: int, expend: string}> 分类ID => 统计数据
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCategoryStatistics(string $time, array $userIds, array $groupKeys): array
    {
        $prefix = Config::get('database.connections.mysql.prefix');
        $model  = $this->getModel(false);
        $table  = $model->getTable();

        $joinModel    = app(Payment::class);
        $joinTable    = $joinModel->getTable();
        $preTable     = $prefix . $table;
        $preJoinTable = $prefix . $joinTable;

        // 构建分类条件：用 groupKeys 做 orWhereJsonContains 筛选
        // 同时匹配字符串和整数类型的 JSON 值（兼容历史数据）
        $categoryCondition = function ($query) use ($groupKeys) {
            if (! empty($groupKeys)) {
                $query->where(function ($q) use ($groupKeys) {
                    foreach ($groupKeys as $key) {
                        $q->orWhereJsonContains('contract_category', $key);
                        // 如果 key 是数字字符串，同时匹配整数类型
                        if (ctype_digit((string) $key)) {
                            $q->orWhereJsonContains('contract_category', (int) $key);
                        }
                    }
                });
            }
        };

        // 始终按完整 contract_category 分组，由 Service 层聚合
        $groupByExpr = "`{$preTable}`.`contract_category`";

        // 一次性查询收入统计（types: 0,1）
        $incomeQuery = clone $model;
        $incomeData  = $incomeQuery->join($joinTable, function ($join) use ($joinTable, $table, $time) {
            $join->on($joinTable . '.cid', '=', $table . '.id')
                ->whereIn($joinTable . '.types', [0, 1])
                ->where($joinTable . '.status', 1)
                ->where($joinTable . '.entid', 1)
                ->when($time, function ($query) use ($time, $joinTable) {
                    $query->whereBetween($joinTable . '.date', explode('-', $time));
                });
        })->where($categoryCondition)
            ->whereIn($joinTable . '.uid', $userIds)
            ->selectRaw("{$groupByExpr} as `category_key`, sum(`{$preJoinTable}`.`num`) as `price`")
            ->groupBy(DB::raw($groupByExpr))
            ->get()
            ->keyBy('category_key')
            ->map(fn ($row) => $row->price ?? '0.00')
            ->toArray();

        // 一次性查询支出统计（types: 2）
        $expendQuery = clone $model;
        $expendData  = $expendQuery->join($joinTable, function ($join) use ($joinTable, $table, $time) {
            $join->on($joinTable . '.cid', '=', $table . '.id')
                ->where($joinTable . '.types', 2)
                ->where($joinTable . '.status', 1)
                ->where($joinTable . '.entid', 1)
                ->when($time, function ($query) use ($time, $joinTable) {
                    $query->whereBetween($joinTable . '.date', explode('-', $time));
                });
        })->where($categoryCondition)
            ->whereIn($joinTable . '.uid', $userIds)
            ->selectRaw("{$groupByExpr} as `category_key`, sum(`{$preJoinTable}`.`num`) as `price`")
            ->groupBy(DB::raw($groupByExpr))
            ->get()
            ->keyBy('category_key')
            ->map(fn ($row) => $row->price ?? '0.00')
            ->toArray();

        // 一次性查询合同数量
        $countQuery = clone $model;
        $countData  = $countQuery->join($joinTable, function ($join) use ($joinTable, $table, $time) {
            $join->on($joinTable . '.cid', '=', $table . '.id')
                ->where($joinTable . '.types', 0)
                ->where($joinTable . '.status', 1)
                ->where($joinTable . '.entid', 1)
                ->when($time, function ($query) use ($time, $joinTable) {
                    $query->whereBetween($joinTable . '.date', explode('-', $time));
                });
        })->where($categoryCondition)
            ->whereIn($joinTable . '.uid', $userIds)
            ->selectRaw("{$groupByExpr} as `category_key`, count(DISTINCT `{$preTable}`.`id`) as `count`")
            ->groupBy(DB::raw($groupByExpr))
            ->get()
            ->keyBy('category_key')
            ->map(fn ($row) => (int) ($row->count ?? 0))
            ->toArray();

        // 合并结果，键为分类标识（第一层分组时为分类ID，下钻时为完整JSON）
        $result = [];
        foreach ($incomeData as $catKey => $price) {
            $result[$catKey] = [
                'price'  => $price,
                'count'  => $countData[$catKey] ?? 0,
                'expend' => $expendData[$catKey] ?? '0.00',
            ];
        }

        return $result;
    }

    /**
     * 按产品分类统计订单业绩.
     *
     * @param string $time 时间区间
     * @param array $userIds 用户ID列表
     * @param array $categoryIds 筛选的分类ID列表（产品分类ID）
     * @return array<int, array{price: string, count: int, expend: string}> 分类ID => 统计数据
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getProductCategoryStatistics(string $time, array $userIds, array $categoryIds = []): array
    {
        // 获取模型实例
        $assistModel   = app(ProductAssist::class);
        $contractModel = $this->getModel(false);
        $productModel  = app(Product::class);
        $paymentModel  = app(Payment::class);

        // 获取表名（不带前缀）
        $assistTable   = $assistModel->getTable();
        $contractTable = $contractModel->getTable();
        $productTable  = $productModel->getTable();
        $paymentTable  = $paymentModel->getTable();

        // 获取带前缀的表名（用于 raw SQL）
        $prefix           = Config::get('database.connections.mysql.prefix');
        $preAssistTable   = $prefix . $assistTable;
        $preContractTable = $prefix . $contractTable;
        $preProductTable  = $prefix . $productTable;
        $prePaymentTable  = $prefix . $paymentTable;

        // 产品分类筛选条件：按 product.pid 筛选
        $categoryCondition = function ($query) use ($categoryIds, $productTable) {
            if (! empty($categoryIds)) {
                $query->whereIn($productTable . '.pid', $categoryIds);
            }
        };

        // 一次性查询收入统计（types: 0,1）
        // join 时使用不带前缀的表名（让 Laravel 自动添加前缀），selectRaw 时使用带前缀的表名
        $incomeData = $assistModel
            ->join($contractTable, $assistTable . '.link_id', '=', $contractTable . '.id')
            ->join($productTable, $assistTable . '.product_id', '=', $productTable . '.id')
            ->join($paymentTable, $contractTable . '.id', '=', $paymentTable . '.cid')
            ->where($paymentTable . '.types', '<=', 1)
            ->where($paymentTable . '.status', 1)
            ->where($paymentTable . '.entid', 1)
            ->where($assistTable . '.link_type', 2) // 2=订单
            ->when($time, function ($query) use ($time, $paymentTable) {
                $query->whereBetween($paymentTable . '.date', explode('-', $time));
            })
            ->where($categoryCondition)
            ->whereIn($paymentTable . '.uid', $userIds)
            ->selectRaw("{$preProductTable}.pid as category_id, sum({$prePaymentTable}.num) as price")
            ->groupBy($productTable . '.pid')
            ->get()
            ->keyBy('category_id')
            ->map(fn ($row) => $row->price ?? '0.00')
            ->toArray();

        // 一次性查询支出统计（types: 2）
        $expendData = $assistModel
            ->join($contractTable, $assistTable . '.link_id', '=', $contractTable . '.id')
            ->join($productTable, $assistTable . '.product_id', '=', $productTable . '.id')
            ->join($paymentTable, $contractTable . '.id', '=', $paymentTable . '.cid')
            ->where($paymentTable . '.types', 2)
            ->where($paymentTable . '.status', 1)
            ->where($paymentTable . '.entid', 1)
            ->where($assistTable . '.link_type', 2)
            ->when($time, function ($query) use ($time, $paymentTable) {
                $query->whereBetween($paymentTable . '.date', explode('-', $time));
            })
            ->where($categoryCondition)
            ->whereIn($paymentTable . '.uid', $userIds)
            ->selectRaw("{$preProductTable}.pid as category_id, sum({$prePaymentTable}.num) as price")
            ->groupBy($productTable . '.pid')
            ->get()
            ->keyBy('category_id')
            ->map(fn ($row) => $row->price ?? '0.00')
            ->toArray();

        // 一次性查询订单数量
        $countData = $assistModel
            ->join($contractTable, $assistTable . '.link_id', '=', $contractTable . '.id')
            ->join($productTable, $assistTable . '.product_id', '=', $productTable . '.id')
            ->join($paymentTable, $contractTable . '.id', '=', $paymentTable . '.cid')
            ->where($paymentTable . '.types', 0)
            ->where($paymentTable . '.status', 1)
            ->where($paymentTable . '.entid', 1)
            ->where($assistTable . '.link_type', 2)
            ->when($time, function ($query) use ($time, $paymentTable) {
                $query->whereBetween($paymentTable . '.date', explode('-', $time));
            })
            ->where($categoryCondition)
            ->whereIn($paymentTable . '.uid', $userIds)
            ->selectRaw("{$preProductTable}.pid as category_id, count(DISTINCT {$preContractTable}.id) as count")
            ->groupBy($productTable . '.pid')
            ->get()
            ->keyBy('category_id')
            ->map(fn ($row) => (int) ($row->count ?? 0))
            ->toArray();

        // 合并结果
        $result = [];
        foreach ($incomeData as $categoryId => $price) {
            $result[$categoryId] = [
                'price'  => $price,
                'count'  => $countData[$categoryId] ?? 0,
                'expend' => $expendData[$categoryId] ?? '0.00',
            ];
        }

        return $result;
    }

    /**
     * 按产品规格维度获取业绩排行列表（带分页）.
     *
     * @param string $time 时间区间
     * @param array $userIds 用户ID列表
     * @param array $categoryIds 分类ID列表（产品分类ID）
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array{list: array, count: int}
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getProductRankList(string $time, array $userIds, array $categoryIds = [], int $page = 1, int $limit = 10): array
    {
        // 获取模型实例
        $assistModel           = app(ProductAssist::class);
        $contractModel         = $this->getModel(false);
        $productModel          = app(Product::class);
        $paymentModel          = app(Payment::class);
        $categoryModel         = app(ProductCategory::class);

        // 获取表名（不带前缀）
        $assistTable    = $assistModel->getTable();
        $contractTable  = $contractModel->getTable();
        $productTable   = $productModel->getTable();
        $paymentTable   = $paymentModel->getTable();

        // 获取带前缀的表名（用于 raw SQL）
        $prefix            = Config::get('database.connections.mysql.prefix');
        $preAssistTable    = $prefix . $assistTable;
        $preContractTable  = $prefix . $contractTable;
        $preProductTable   = $prefix . $productTable;

        // 产品分类筛选条件：按 product.pid 筛选
        $categoryCondition = function ($query) use ($categoryIds, $productTable) {
            if (! empty($categoryIds)) {
                $query->whereIn($productTable . '.pid', $categoryIds);
            }
        };

        // 基础查询：关联 product_assist → product，并用 client_bill 筛选有效业绩记录
        $baseQuery = $assistModel
            ->join($contractTable, $assistTable . '.link_id', '=', $contractTable . '.id')
            ->join($productTable, $assistTable . '.product_id', '=', $productTable . '.id')
            ->where($assistTable . '.link_type', 2)
            ->whereExists(function ($query) use ($paymentTable, $contractTable, $time, $userIds) {
                $query->select(DB::raw(1))
                    ->from($paymentTable)
                    ->whereColumn($paymentTable . '.cid', $contractTable . '.id')
                    ->where($paymentTable . '.types', '<=', 1)
                    ->where($paymentTable . '.status', 1)
                    ->where($paymentTable . '.entid', 1)
                    ->when($time, function ($query) use ($time, $paymentTable) {
                        $query->whereBetween($paymentTable . '.date', explode('-', $time));
                    })
                    ->whereIn($paymentTable . '.uid', $userIds);
            })
            ->where($categoryCondition);

        // 按 unique 分组，聚合金额和数量
        $selectFields = [
            DB::raw("{$preAssistTable}.`unique`"),
            "{$preAssistTable}.`sku`",
            "round(sum({$preAssistTable}.`total_price`) / nullif(sum({$preAssistTable}.`count`), 0), 2) as unit_price",
            "{$preAssistTable}.`image`",
            "{$preProductTable}.`id` as product_id",
            "{$preProductTable}.`name` as product_name",
            "{$preProductTable}.`pid` as category_id",
            "sum({$preAssistTable}.`total_price`) as total_price",
            "count(DISTINCT {$preContractTable}.`id`) as order_count",
        ];

        // 统计总记录数（分页前）
        $countResult = clone $baseQuery;
        $count       = $countResult
            ->selectRaw("count(DISTINCT {$preAssistTable}.`unique`) as cnt")
            ->first()?->cnt ?? 0;

        if ($count == 0) {
            return ['list' => [], 'count' => 0];
        }

        // 分页查询
        $offset = ($page - 1) * $limit;
        $list   = $baseQuery
            ->selectRaw(implode(',', $selectFields))
            ->groupBy(DB::raw("{$preAssistTable}.`unique`"))
            ->havingRaw("sum({$preAssistTable}.`total_price`) > 0")
            ->orderByDesc('total_price')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        // 获取分类名称
        $categoryIds  = array_unique(array_column($list, 'category_id'));
        $categoryList = $categoryModel->whereIn('id', $categoryIds)->select(['id', 'name'])->get()->keyBy('id')->toArray();

        // 组装分类名称和规格类型
        foreach ($list as &$item) {
            $item['category_name'] = $categoryList[$item['category_id']]['name'] ?? '';
            $item['total_price']   = $item['total_price'] ?? '0.00';
            // 单规格/多规格：sku中包含逗号则为多规格
            $item['spec_type'] = strpos($item['sku'] ?? '', ',') !== false ? 2 : 1;
        }

        return ['list' => $list, 'count' => (int) $count];
    }

    /**
     * 插入数据.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function insert(array $data): bool
    {
        return $this->getModel(false)->insert($data);
    }

    /**
     * 搜索.
     * @param mixed $where
     * @return BaseModel|HigherOrderWhenProxy
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        $title = $where['title'] ?? '';
        $renew = $where['renew'] ?? '';
        $ids   = $where['ids'] ?? [];
        if (isset($where['title'])) {
            unset($where['title']);
        }
        if (isset($where['type'])) {
            unset($where['type']);
        }
        if ($renew == 3) {
            unset($where['renew']);
            if (isset($where['ids'])) {
                unset($where['ids']);
            }
        }
        return parent::search($where, $authWhere)
            ->when($renew == 3, function ($query) use ($ids) {
                $query->where(function ($query) use ($ids) {
                    $query->where('renew', 0)->orWhere(function ($query) use ($ids) {
                        $query->where('renew', 1)->whereIn('id', $ids);
                    });
                });
            })->where(function ($query) use ($title) {
                $query->when($title, function ($query) use ($title) {
                    $query->orWhere('title', 'like', '%' . $title . '%')
                        ->orWhere('contract_no', 'like', '%' . $title . '%')
                        ->orWhere(function ($query) use ($title) {
                            $query->whereIn('eid', function ($query) use ($title) {
                                $query->from('client_list')->select(['id'])->where('name', 'like', '%' . $title . '%');
                            });
                        });
                });
            });
    }

    /**
     * 合同订单类型数量.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCountByCategory(string $time, array $userIds, array|int $categoryId = 0): int
    {
        $model      = $this->getModel(false);
        $table      = $model->getTable();
        $categoryId = collect($categoryId)->map(function ($item) {
            $array = json_decode($item, true);
            return collect($array)->last();
        });
        $preTable  = Config::get('database.connections.mysql.prefix') . $table;
        $joinTable = app(Payment::class)->getTable();
        return $model->join($joinTable, function ($join) use ($joinTable, $table, $time) {
            $join->on($joinTable . '.cid', '=', $table . '.id')
                ->where($joinTable . '.entid', 1)->where($joinTable . '.status', 1)->where($joinTable . '.types', 0)
                ->when($time, function ($query) use ($time, $joinTable) {
                    $query->whereBetween($joinTable . '.date', explode('-', $time));
                });
        })->whereIn($joinTable . '.uid', $userIds)
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where(function ($q) use ($categoryId) {
                    $categoryId->each(function ($item) use ($q) {
                        $q->orWhereJsonContains('contract_category', $item);
                    });
                });
            })->selectRaw("count(DISTINCT `{$preTable}`.`id`) as `count`")->first()?->count ?? 0;
    }

    /**
     * 列表筛选数据.
     *
     * @param mixed $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param array $with 预加载关联
     * @param int $uid 用户ID
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function listSearch(array $where, int $page = 0, int $limit = 0, array $with = ['product'], int $uid = 0)
    {
        // 1. 处理基础 where 条件
        $where = $this->getWhere($where);

        // 2. 收集闭包条件（用于 search 之后添加的特殊查询）
        $callbacks = [];

        foreach ($where as $field => $value) {
            if ($value === '') {
                continue;
            }

            if (in_array($field, $this->otherSearch)) {
                $callbacks[] = match ($field) {
                    'statistics_type'   => $this->buildStatisticsTypeCondition($value, $uid),
                    'payment_time'      => fn ($query) => $this->applyPaymentTimeCondition($query, $value),
                    'contract_customer' => fn ($query) => $this->applyContractCustomerCondition($query, $value),
                    'product_name'      => $this->buildProductNameCondition($value),
                    'uid_scope'         => fn ($query) => $this->applyUidScopeCondition($query, $this->getTable(), $value),
                    default             => null,
                };
                unset($where[$field]);
            } elseif (is_array($value)) {
                if (isset($value['input_type'])) {
                    $callbacks[] = match ($value['input_type']) {
                        'select'           => fn ($query) => $this->getMoreSelectSearch($query, $field, $value['value'], $value['type']),
                        'radio'            => fn ($query) => $this->getSelectSearch($query, $field, $value['value']),
                        'checked'          => fn ($query) => $this->getMemberSearch($query, $field, $value['value']),
                        'input'            => fn ($query) => $this->getInputSearch($query, $field, $value['value']),
                        'date', 'datetime' => fn ($query) => $this->getDateSearch($query, $field, $value['value']),
                        'personnel'        => fn ($query) => $this->getPersonnelSearch($query, $field, $value['value']),
                        'member'           => fn ($query) => $this->getMemberSearch($query, $field, $value['value']),
                        default            => fn ($query) => $query->where($field, $value['value']),
                    };
                } else {
                    $callbacks[] = fn ($query) => $query->whereIn($field, $value);
                }
                unset($where[$field]);
            } else {
                $callbacks[] = fn ($query) => $query->where($field, $value);
                unset($where[$field]);
            }
        }

        // 3. 过滤掉 null 的回调
        $callbacks = array_filter($callbacks);

        // 4. 统一调用 search() 生成查询（自动应用权限过滤）
        return $this->search($where)
            ->when(! empty($callbacks), function ($query) use ($callbacks) {
                foreach ($callbacks as $callback) {
                    $callback($query);
                }
            })
            ->when($limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })
            ->when($sort = sort_mode('id'), function ($query) use ($sort) {
                if (is_array($sort)) {
                    foreach ($sort as $k => $v) {
                        if (is_numeric($k)) {
                            $query->orderByDesc($v);
                        } else {
                            $query->orderBy($k, $v);
                        }
                    }
                } else {
                    $query->orderByDesc($sort);
                }
            })
            ->with($with);
    }

    /**
     * 业务员查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchSalesman($dao, $value)
    {
        return is_array($value) ? $dao->whereIn('uid', $value) : $dao->where('uid', $value);
    }

    /**
     * 客户创建人查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchCreator($dao, $value)
    {
        return $value ? (is_array($value) ? $dao->whereIn('creator_uid', $value) : $dao->where('creator_uid', $value)) : $dao;
    }

    /**
     * 合同订单客户查询 (优化为 whereExists).
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchContractCustomer($dao, $value)
    {
        return $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer')
                ->whereColumn('customer.id', $this->getTable() . '.eid')
                ->where('customer_name', 'like', '%' . $value . '%');
        });
    }

    public function getContractNum(array $where, string $group = 'eid', string $field = 'eid, COUNT(id) as count')
    {
        return $this->search($where)->groupBy($group)->selectRaw($field)->get();
    }

    public function getRenewalRemindCount(array $where, bool $isExpire = false): int
    {
        return (int) $this->applyRenewalRemindCondition($this->search($where), $isExpire)->count();
    }

    /**
     * 构建统计类型查询条件.
     *
     * @param string $value 统计类型值
     * @param int $uid 用户ID
     */
    protected function buildStatisticsTypeCondition(string $value, int $uid): \Closure
    {
        return match ($value) {
            'concern' => fn ($query) => $query->whereExists(function ($subQuery) use ($uid) {
                $subQuery->from('client_subscribe')
                    ->whereColumn('client_subscribe.eid', $this->getTable() . '.id')
                    ->where('client_subscribe.uid', $uid)
                    ->where('client_subscribe.types', CustomEnum::CONTRACT)
                    ->where('client_subscribe.subscribe_status', 1);
            }),
            'not_signed'  => fn ($query) => $query->where('signing_status', 0),
            'signed'      => fn ($query) => $query->where('signing_status', 1),
            'void_signed' => fn ($query) => $query->where('signing_status', 2),
            'expired'     => fn ($query) => $query->where('signing_status', '<', 2)
                ->whereDate('end_date', '<', now()->toDateString())
                ->where('is_abnormal', 0)
                ->whereNotNull('end_date'),
            'urgent_renewal' => function ($query) {
                return $this->applyRenewalRemindCondition($query);
            },
            'cost_expired' => function ($query) {
                return $this->applyRenewalRemindCondition($query, true);
            },
            default => fn ($query) => $query,
        };
    }

    /**
     * 续费提醒用 exists 关联 client_remind，避免先取出海量合同ID再 whereIn。
     */
    protected function applyRenewalRemindCondition($query, bool $isExpire = false)
    {
        $today = now()->startOfDay();

        return $query->where('signing_status', '<', 2)
            ->whereExists(function ($subQuery) use ($isExpire, $today) {
                $subQuery->selectRaw('1')
                    ->from('client_remind')
                    ->whereColumn('client_remind.cid', $this->getTable() . '.id')
                    ->where('client_remind.entid', 1)
                    ->where('client_remind.status', 0)
                    ->whereNull('client_remind.deleted_at')
                    ->when($isExpire, function ($query) use ($today) {
                        $query->where('client_remind.time', '<', $today->toDateTimeString());
                    }, function ($query) use ($today) {
                        $query->whereBetween('client_remind.time', [
                            $today->toDateTimeString(),
                            $today->copy()->addDays(29)->endOfDay()->toDateTimeString(),
                        ]);
                    });
            });
    }

    /**
     * 应用付款时间查询条件 (优化为 whereExists).
     *
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function applyPaymentTimeCondition($query, $value)
    {
        $date = is_array($value) ? ($value['value'] ?? '') : $value;

        return $query->whereExists(function ($subQuery) use ($date) {
            $subQuery->from('client_bill')
                ->whereColumn('client_bill.cid', $this->getTable() . '.id')
                ->where('client_bill.status', 1)
                ->whereIn('client_bill.types', [0,1])
                ->when($date, fn ($query, $date) => $this->applyClientBillDateCondition($query, $date));
        });
    }

    /**
     * 应用回款日期筛选，兼容前端传入的 YYYY/MM/DD-YYYY/MM/DD 区间格式.
     *
     * @param mixed $query
     * @return mixed
     */
    protected function applyClientBillDateCondition($query, mixed $date)
    {
        $ranges = $this->parseSearchDateRange($date);
        if ($ranges) {
            [$startTime, $endTime] = $ranges;
            $startTime = str_replace('/', '-', trim((string) $startTime));
            $endTime   = str_replace('/', '-', trim((string) $endTime));

            if ($startTime === '' && $endTime === '') {
                return $query;
            }

            $start = $startTime !== '' ? Carbon::parse($startTime, config('app.timezone')) : null;
            $end   = $endTime !== '' ? Carbon::parse($endTime, config('app.timezone')) : null;

            if ($start && $end && $start->gt($end)) {
                [$startTime, $endTime] = [$endTime, $startTime];
                [$start, $end] = [$end, $start];
            }

            if ($start && ! str_contains($startTime, ':')) {
                $start = $start->startOfDay();
            }
            if ($end && ! str_contains($endTime, ':')) {
                $end = $end->endOfDay();
            }

            if ($start && $end) {
                return $query->whereBetween('client_bill.date', [$start->toDateTimeString(), $end->toDateTimeString()]);
            }

            if ($start) {
                return $query->where('client_bill.date', '>=', $start->toDateTimeString());
            }

            if ($end) {
                return $query->where('client_bill.date', '<=', $end->toDateTimeString());
            }

            return $query;
        }

        $date = trim((string) $date);
        if ($date !== '') {
            $normalizedDate = str_replace('/', '-', $date);
            if (preg_match('#^\d{4}-\d{1,2}-\d{1,2}(?:\s+\d{1,2}:\d{2}(?::\d{2})?)?$#', $normalizedDate)) {
                $dateTime = Carbon::parse($normalizedDate, config('app.timezone'));
                if (str_contains($normalizedDate, ':')) {
                    return $query->where('client_bill.date', $dateTime->toDateTimeString());
                }

                return $query->whereBetween('client_bill.date', [
                    $dateTime->startOfDay()->toDateTimeString(),
                    $dateTime->endOfDay()->toDateTimeString(),
                ]);
            }
        }

        return $query;
    }

    /**
     * 应用合同客户查询条件 (优化为 whereExists).
     *
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function applyContractCustomerCondition($query, $value)
    {
        return $query->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer')
                ->whereColumn('customer.id', $this->getTable() . '.eid')
                ->where('customer_name', 'like', '%' . $value['value'] . '%');
        });
    }

    /**
     * 构建产品名称查询条件.
     *
     * @param mixed $value
     */
    protected function buildProductNameCondition($value): \Closure
    {
        return fn ($query) => $query->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer_product_assist')
                ->whereColumn('customer_product_assist.link_id', $this->getTable() . '.id')
                ->where('customer_product_assist.link_type', CustomEnum::CONTRACT)
                ->where('customer_product_assist.product_name', 'like', '%' . $value['value'] . '%');
        });
    }

    protected function setModel(): string
    {
        return Order::class;
    }

    /**
     * 付款单号查询 (优化为 whereExists).
     * @param mixed $dao
     * @param mixed $value
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function searchBillNo($dao, $value): mixed
    {
        return $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('client_bill')
                ->whereColumn('client_bill.cid', $this->getTable() . '.id')
                ->where('client_bill.bill_no', 'like', '%' . $value . '%');
        });
    }

    /**
     * 合同订单分类查询.
     * @param mixed $dao
     * @param mixed $value
     */
    private function searchContractCategory($dao, $value): mixed
    {
        return $dao->where(function ($query) use ($value) {
            collect($value ?? [])->each(function ($item) use ($query) {
                $query->orWhereJsonContains('contract_category', $item);
            });
            return $query;
        });
    }
}
