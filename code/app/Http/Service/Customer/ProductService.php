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

namespace App\Http\Service\Customer;

use App\Constants\AttachEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\ProductEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Dao\Customer\ProductDao;
use App\Http\Dao\Warehouse\WarehouseSkuStockDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webpatser\Uuid\Uuid;

/**
 * 产品service.
 */
class ProductService extends BaseService
{
    use CustomerTrait;

    public function __construct(ProductDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存产品
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveProduct(array $data, int $uid, int $spec_type, array $attr, array $attrValue, int $id = 0)
    {
        if (! $spec_type) {
            if (count($attrValue) > 1) {
                throw $this->exception('单规格商品属性错误');
            }
        }
        $formService     = app(FormService::class);
        $dictDataService = app(DictDataService::class);
        $attaches        = [];
        $list            = $formService->getFormDataList(CustomEnum::PRODUCT);
        $formService->fieldValueCheck($data, CustomEnum::PRODUCT, 0, $list);
        foreach ($list as $item) {
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            $dictIdent = $item['dict_ident'] ?? '';
            $dictLevel = $dictIdent ? (int) $dictDataService->max(['type_name' => $dictIdent], 'level') : null;
            $isJsonCustomField = $formService->isJsonCustomField($item, $dictLevel);
            foreach ($data as $key => &$datum) {
                if ($item['key'] == $key) {
                    if (in_array($inputType, ['file', 'images'])) {
                        $attaches = array_merge($attaches, (array) $datum);
                    }
                    $datum = $formService->getFormValue($type, $inputType, $datum, $key);
                    if (is_array($datum) && ! $isJsonCustomField) {
                        $datum = json_encode($datum, JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }
        if (! $id) {
            $data['uid'] = $uid;
        } else {
            unset($data['types']);
        }
        $attaches = array_filter($attaches);
        return $this->transaction(function () use ($spec_type, $attaches, $attr, $attrValue, $data, $id) {
            $data['spec_type'] = $spec_type;
            if ($data['path']) {
                $path = is_array($data['path']) ? $data['path'] : json_decode((string) $data['path'], true);
                if (is_string($path)) {
                    $path = json_decode($path, true);
                }
                $path         = is_array($path) ? $path : [];
                $data['pid']  = (int) end($path);
                $data['path'] = $path;
            }
            if ($id) {
                $this->dao->update($id, $data);
            } else {
                $result = $this->dao->create($data);
                $id     = $result->id;
            }
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $id, 'relation_type' => AttachEnum::RELATION_TYPE[ViewSearchEnum::VIEW_PRODUCT]]);
            }
            $settleParams         = $this->setAttrValue($attrValue, $id);
            $settleParams['attr'] = $this->setAttr($attr, $id);
            $this->save($id, $settleParams);

            return $id;
        });
    }

    /**
     * 编辑表单.
     * @param mixed $id
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo($id)
    {
        $info = $this->dao->get($id, with: ['attr', 'attrValue', 'category'])?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }
        foreach ($info['attr'] as $k => $v) {
            $info['attr'][$k] = [
                'value'  => $v['attr_name'],
                'detail' => $this->getAttrValue($v['attr_values']),
            ];
        }
        $attrValue = $info['attr_value'];
        unset($info['attr_value']);
        $arr = [];
        foreach ($attrValue as $item) {
            $sku = explode(',', $item['sku']);
            foreach ($sku as $k => $v) {
                $item['value' . $k] = $v;
            }
            $arr[] = $item;
        }
        $attachService   = app(AttachService::class);
        $dictDataService = app(DictDataService::class);

        $list = app(FormService::class)->getFormDataWithType(CustomEnum::PRODUCT);
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], CustomEnum::SCENE_INFO);
                    if ($datum['dict_ident']) {
                        if (is_dimensional_data($datum['value'])) {
                            $datum['value'] = $this->handleDictValue($datum['dict_ident'], $datum['value'], $type);
                        } else {
                            $datum['value'] = $dictDataService->getNamesByValue($datum['dict_ident'], $datum['value']);
                        }
                    }
                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? [] : $attachService->select(['id' => $datum['value']], ['id', 'att_dir', 'att_size', 'real_name'])?->toArray();
                    }
                    if ($datum['key'] == 'path') {
                        $datum['value'] = $datum['value'] ? app(ProductCategoryService::class)->column(['id' => end($datum['value'])], 'name') : [];
                    }
                }
            }
        }

        return [
            'attr'      => $info['attr'],
            'name'      => $info['name'],
            'spec_type' => $info['spec_type'],
            'attrValue' => $arr,
            'list'      => $list,
        ];
    }

    /**
     * 客户编辑表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getEditInfo(int $id): mixed
    {
        $info = $this->dao->get($id, with: ['attr', 'attrValue', 'category'])?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }
        foreach ($info['attr'] as $k => $v) {
            $info['attr'][$k] = [
                'value'  => $v['attr_name'],
                'detail' => $this->getAttrValue($v['attr_values']),
            ];
        }
        $attrValue = $info['attr_value'];
        unset($info['attr_value']);
        $arr = [];
        foreach ($attrValue as $item) {
            $sku = explode(',', $item['sku']);
            foreach ($sku as $k => $v) {
                $item['value' . $k] = $v;
                $item['attr_arr'][] = $v;
            }
            $arr[] = $item;
        }
        $attachField   = $this->getAttachField();
        $attachService = app(AttachService::class);
        $list          = app(FormService::class)->getFormDataWithType(CustomEnum::PRODUCT, platform: $this->getPlatform());
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], CustomEnum::SCENE_EDIT);
                    if ($inputType == 'member') {
                        $datum['options'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']]);
                    }
                    if ($datum['key'] == 'types') {
                        $datum['disabled'] = true;
                    }
                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? []
                            : $attachService->getListByRelationType(AttachService::RELATION_TYPE_PRODUCT, $datum['value'], $attachField);
                    }
                }
            }
        }
        return [
            'attr'      => $info['attr'],
            'spec_type' => $info['spec_type'],
            'attrValue' => $arr,
            'list'      => $list,
        ];
    }

    /**
     * 产品详情.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function detail(int $id)
    {
        $data = $this->dao->get($id, with: ['attr', 'attrValue', 'category'])?->toArray();
        foreach ($data['attr'] as $k => $v) {
            $data['attr'][$k] = [
                'value'  => $v['attr_name'],
                'detail' => $v['attr_values'],
            ];
        }
        $attrValue = $data['attr_value'];
        unset($data['attr_value']);
        $arr = [];
        foreach ($attrValue as $item) {
            $sku = explode(',', $item['sku']);
            foreach ($sku as $k => $v) {
                $item['value' . $k] = $v;
            }
            $arr[] = $item;
        }
        $data['attrValue'] = $arr;
        return $data;
    }

    /**
     * 字段过滤.
     * @return string[]
     */
    public function dictFilterField(): array
    {
        return [];
    }

    /**
     * 获取用户设置的搜索列表.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function searchField()
    {
        $field[]  = ['types', ''];
        $fieldSet = app(FormService::class)->getCustomDataByTypes(CustomEnum::PRODUCT, ['key as field', 'input_type']);
        $fieldSet = array_merge($fieldSet, ProductEnum::PRODUCT_SEARCH_FIELD);
        $field    = [];
        foreach ($fieldSet as $value) {
            $field[] = [$value['field'], ''];
        }
        return $field;
    }

    /**
     * 无需同步字段.
     * @return string[]
     */
    public function getOutOfSyncField(): array
    {
        return [];
    }

    /**
     * 获取产品选择属性树.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAttrTree(array $where)
    {
        $attr_like = '';
        $filterStock = (int) ($where['filter_stock'] ?? 0);
        $warehouseId = (int) ($where['warehouse_id'] ?? 0);
        $skuIds = array_values(array_unique(array_filter(
            array_map('strval', (array) ($where['sku_id'] ?? [])),
            fn ($id) => $id !== '' && $id !== '0'
        )));
        unset($where['sku_id']);
        if ($skuIds) {
            $where['id'] = app(ProductAttrValueService::class)->column(['id' => $skuIds], 'product_id') ?: [0];
        }
        if (isset($where['attr_like'])) {
            $attr_like   = $where['attr_like'];
            $productIds  = array_unique(app(ProductAttrValueService::class)->column(['attr_like' => $attr_like], 'product_id'));
            $where['id'] = isset($where['id']) ? array_values(array_intersect($where['id'], $productIds)) : $productIds;
            unset($where['attr_like']);
        }
        $where['filter_stock'] = ['value' => $filterStock, 'warehouse_id' => $warehouseId];
        unset($where['warehouse_id']);
        [$page, $limit] = $this->getPageValue();
        $data           = $this->dao->setDefaultSort(['sort', 'id'])->select($where, ['id', 'name', 'unit_name', 'pid'], ['attrValue', 'category' => fn ($query) => $query->select(['id', 'name'])], $page, $limit)?->toArray();
        $warehouseStockMap = $this->getWarehouseSkuStockMap($warehouseId, $data);
        foreach ($data as &$item) {
            $item['category']   = $item['category']['name'] ?? '';
            $item['attr_value'] = collect($item['attr_value'])->filter(function ($v) use ($attr_like, $filterStock, $warehouseStockMap, $skuIds) {
                if ($skuIds && ! in_array((string) $v['id'], $skuIds, true)) {
                    return false;
                }
                if ($attr_like && ! str_contains($v['sku'], $attr_like) && ! str_contains($v['bar_code'] ?? '', $attr_like)) {
                    return false;
                }
                if ($filterStock && (! isset($warehouseStockMap[(int) $v['id']]) || $warehouseStockMap[(int) $v['id']]['good_stock'] <= $warehouseStockMap[(int) $v['id']]['occupy_stock'])) {
                    return false;
                }
                return true;
            })->map(function ($v) use ($warehouseId, $warehouseStockMap) {
                $v['sku_id'] = $v['id'];
                if (! $warehouseId) {
                    return $v;
                }
                return $this->withWarehouseStock($v, $warehouseStockMap[(int) $v['id']] ?? []);
            })->values();
        }
        $count = $this->dao->count($where);
        return $this->listData($data, $count);
    }

    public function getRealProductAttrTree(array $where): array
    {
        return $this->getAttrTree($where);
    }

    private function getWarehouseSkuStockMap(int $warehouseId, array $data): array
    {
        if (! $warehouseId) {
            return [];
        }
        $skuIds = collect($data)->pluck('attr_value')->flatten(1)->pluck('id')->filter()->unique()->values()->all();
        if (! $skuIds) {
            return [];
        }
        return app(WarehouseSkuStockDao::class)->getModel(false)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('sku_id', $skuIds)
            ->get(['sku_id', 'good_stock', 'defect_stock', 'occupy_stock', 'transit_stock'])
            ->keyBy('sku_id')
            ->map(fn ($stock) => [
                'good_stock'    => (int) $stock->good_stock,
                'defect_stock'  => (int) $stock->defect_stock,
                'occupy_stock'  => (int) $stock->occupy_stock,
                'transit_stock' => (int) $stock->transit_stock,
            ])
            ->all();
    }

    private function withWarehouseStock(array $sku, array $stock): array
    {
        if (! $stock) {
            $stock = [
                'good_stock'    => 0,
                'defect_stock'  => 0,
                'occupy_stock'  => 0,
                'transit_stock' => 0,
            ];
        }
        $sku['good_stock']        = $stock['good_stock'];
        $sku['defect_stock']      = $stock['defect_stock'];
        $sku['occupy_stock']      = $stock['occupy_stock'];
        $sku['transit_stock']     = $stock['transit_stock'];
        $sku['available_stock']   = max(0, $stock['good_stock'] - $stock['occupy_stock']);
        $sku['book_good_stock']   = $stock['good_stock'];
        $sku['book_defect_stock'] = $stock['defect_stock'];
        return $sku;
    }

    private function getAttrValue($attrValue)
    {
        $values = [];
        foreach ($attrValue as $v) {
            $values[] = ['value' => $v];
        }
        return $values;
    }

    /**
     * TODO 单产品sku.
     * @param mixed $data
     * @return array
     * @day 2020-08-05
     */
    private function detailAttrValue($data)
    {
        $sku = [];
        foreach ($data as $value) {
            $_value = [
                'sku'      => $value['sku'],
                'price'    => $value['price'],
                'stock'    => $value['stock'],
                'image'    => $value['image'],
                'weight'   => $value['weight'],
                'volume'   => $value['volume'],
                'sales'    => $value['sales'],
                'unique'   => $value['unique'],
                'bar_code' => $value['bar_code'],
            ];
            $sku[$value['sku']] = $_value;
        }
        return $sku;
    }

    /**
     * 格式产品SKU.
     * @return array
     */
    private function setAttrValue(array $attrValue, int $productId)
    {
        $price = $stock = $ot_price = $cost = $svip_price = 0;
        try {
            foreach ($attrValue as $value) {
                $sku = '';
                if (! empty($value['detail']) && is_array($value['detail'])) {
                    $sku = implode(',', $value['detail']);
                }

                $sprite = ($value['price'] < 0) ? 0 : $value['price'];

                $cost  = ! $cost ? $value['cost'] : (($cost > $value['cost']) ? $cost : $value['cost']);
                $price = ! $price ? $sprite : (($price > $sprite) ? $sprite : $price);

                $unique    = str_replace('-', '', (string) Uuid::generate(4));
                $new_price = $value['price'] ? (($value['price'] < 0) ? 0 : $value['price']) : 0;
                $array     = [
                    'detail'           => json_encode($value['detail'] ?? '', JSON_UNESCAPED_UNICODE),
                    'bar_code'         => $value['bar_code'] ?? '',
                    'product_bar_code' => $value['product_bar_code'] ?? '',
                    'image'            => $value['image'] ?? '',
                    'cost'             => $value['cost'] ? (($value['cost'] < 0) ? 0 : $value['cost']) : 0,
                    'price'            => $new_price,
                    'product_id'       => $productId,
                    'sku'              => $sku,
                    'unique'           => $unique,
                ];
                $result['attrValue'][] = $array;
            }
            $result['data'] = [
                'price'      => $price,
                'stock'      => $stock,
                'ot_price'   => $ot_price,
                'cost'       => $cost,
                'svip_price' => $svip_price,
            ];
            return $result;
        } catch (\Exception $exception) {
            throw $this->exception('规格错误 ：' . $exception->getMessage() . $exception->getFile() . $exception->getLine());
        }
    }

    /**
     * 保存产品规格.
     * @param mixed $productId
     * @param mixed $settleParams
     * @return true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function save($productId, $settleParams)
    {
        $productAttrRepository = app(ProductAttrService::class);
        $productAttrRepository->delete(['product_id' => $productId]);
        if (isset($settleParams['attr'])) {
            $productAttrRepository->insert($settleParams['attr']);
        }
        $productAttrValueRepository = app(ProductAttrValueService::class);
        $productAttrValueRepository->delete(['product_id' => $productId]);
        if (isset($settleParams['attrValue'])) {
            $arr = array_chunk($settleParams['attrValue'], 30);
            foreach ($arr as $item) {
                $productAttrValueRepository->insert($item);
            }
        }
        return true;
    }

    /**
     * 格式产品规格.
     * @return array
     */
    private function setAttr(array $data, int $productId)
    {
        $result = [];
        foreach ($data as $value) {
            $result[] = [
                'product_id'  => $productId,
                'attr_name'   => $value['value'] ?? $value['attr_name'],
                'attr_values' => implode('-!-', array_column($value['detail'], 'value')),
            ];
        }
        return $result;
    }

    /**
     * 获取列表搜索条件.
     */
    private function viewSearchWhere(array $where, int $uid = 0): array
    {
        if (! isset($where['view_search'])) {
            unset($where['scope_frame']);
            return $where;
        }
        switch ((int) $where['view_search']) {
            case 1:// 上架
                $where['is_show'] = 1;
                break;
            case 2:// 下架
                $where['is_show'] = 0;
                break;
        }
        unset($where['view_search'], $where['scope_frame']);
        return $where;
    }
}
