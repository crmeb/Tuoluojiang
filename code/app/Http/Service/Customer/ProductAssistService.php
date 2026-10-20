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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Dao\Customer\ProductAssistDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 产品辅助service.
 * @mixin ProductAssistDao
 */
class ProductAssistService extends BaseService
{
    public function __construct(ProductAssistDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 校验保存产品.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function saveProducts(array $products, int $linkId, int $types = CustomEnum::ODDS)
    {
        if (! $products) {
            $this->dao->delete(['link_type' => $types, 'link_id' => $linkId]);
            return '0.00';
        }
        foreach ($products as $product) {
            $keys      = array_keys($product);
            $emptyAttr = array_diff(CustomEnum::PRODUCT_PARAMS, $keys);
            if ($emptyAttr) {
                throw $this->exception(__('common.empty.attr', ['attr' => end($emptyAttr)]));
            }
        }
        $productInfo = app(ProductAttrValueService::class)->select(['unique' => array_column($products, 'unique')], with: ['product'])?->toArray();
        if (! $productInfo) {
            throw $this->exception('未找到相关产品信息');
        }
        $diff       = array_diff($this->dao->column(['link_type' => $types, 'link_id' => $linkId], 'unique'), array_column($products, 'unique'));
        $totalPrice = 0;
        $this->transaction(function () use ($products, $linkId, $types, $productInfo, $diff, &$totalPrice) {
            $this->dao->delete(['unique' => $diff, 'link_type' => $types, 'link_id' => $linkId]);
            $productInfoMap = array_column($productInfo, null, 'unique');
            $allowParams    = array_flip(CustomEnum::PRODUCT_PARAMS);
            foreach ($products as $product) {
                $product = array_intersect_key($product, $allowParams);
                $unique  = $product['unique'] ?? '';
                if (! $unique || ! isset($productInfoMap[$unique])) {
                    continue;
                }
                $info    = $productInfoMap[$unique];
                $product['price']       = $product['price'] ?? '0.00';
                $product['total_price'] = $product['total_price'] ?? '0.00';
                $product['count']       = $product['count'] ?? 0;
                $product['discount']    = $product['discount'] ?? 100;
                $product = array_merge($product, [
                    'product_id'   => $info['product_id'],
                    'product_name' => $info['product']['name'],
                    'ot_price'     => $info['price'],
                    'link_id'      => $linkId,
                    'link_type'    => $types,
                    'image'        => $info['image'],
                ]);
                $totalPrice = bcadd((string) $totalPrice, (string) $product['total_price'], 2);
                $this->dao->updateOrCreate([
                    'link_type' => $types,
                    'link_id'   => $linkId,
                    'unique'    => $unique,
                ], $product);
            }
            return true;
        });
        return $totalPrice;
    }
}
