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

namespace App\Http\Service\Train;

use App\Http\Contract\Company\PromotionInterface;
use App\Http\Dao\Train\PromotionDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 晋升
 * Class PromotionService.
 */
class PromotionService extends BaseService implements PromotionInterface
{
    use ResourceServiceTrait;

    /**
     * PromotionService constructor.
     */
    public function __construct(PromotionDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param array $sort
     */
    public function getList(array $where = [], array $field = ['*'], $sort = ['sort', 'created_at'], array $with = []): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     */
    public function save(array $data): mixed
    {
        return $this->dao->create($data);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     */
    public function update(int $id, array $data): mixed
    {
        return $this->dao->update($id, $data);
    }
}
