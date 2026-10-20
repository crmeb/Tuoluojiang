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

namespace App\Http\Service\Config;

use App\Http\Dao\Config\AgreementDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * 协议
 * Class SystemAgreementService.
 */
class SystemAgreementService extends BaseService
{
    /**
     * SystemAgreementService constructor.
     */
    public function __construct(AgreementDao $dao, FormService $builder)
    {
        $this->dao     = $dao;
        $this->builder = $builder;
    }

    /**
     * 协议数据.
     */
    public function getAgreement(string $ident): array
    {
        return Cache::remember('agreement:' . $ident, 60, function () use ($ident) {
            return toArray($this->dao->get(['ident' => $ident], ['ident', 'title', 'content']));
        });
    }

    /**
     * 保存修改.
     * @throws BindingResolutionException
     */
    public function resourceUpdate(int $id, array $data): int
    {
        return $this->dao->update($id, $data);
    }

    /**
     * 获取详情.
     */
    public function getInfo(int $id): array
    {
        return toArray($this->dao->get($id));
    }
}
