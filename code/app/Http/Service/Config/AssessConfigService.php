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

namespace App\Http\Service\Config;

use App\Constants\CacheEnum;
use App\Http\Contract\Config\AssessConfigInterface;
use App\Http\Dao\Access\AssessPlanDao;
use App\Http\Dao\Access\AssessScoreDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Assess\AssessScoreService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 绩效设置.
 */
class AssessConfigService extends BaseService implements AssessConfigInterface
{
    /**
     * 绩效考核部门.
     */

    /**
     * 绩效评分.
     */
    protected AssessScoreDao $scoreDao;

    public function __construct(AssessPlanDao $dao, AssessScoreDao $scoreDao)
    {
        $this->dao      = $dao;
        $this->scoreDao = $scoreDao;
    }

    /**
     * 获取积分配置及说明.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getScoreConfig()
    {
        $score_mark   = sys_config('assess_score_mark', '');
        $compute_mode = (int) sys_config('assess_compute_mode', 1);
        $score_list   = app()->get(AssessScoreService::class)->getList([], ['name', 'min', 'max', 'mark'], ['level' => 'asc']);
        return compact('compute_mode', 'score_mark', 'score_list');
    }

    /**
     * 保存积分配置及说明.
     * @param mixed $data
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveScoreConfig($data)
    {
        sys_config('assess_score_mark', $data['score_mark'], true);
        sys_config('assess_compute_mode', $data['compute_mode'], true);
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        return $this->transaction(function () use ($data) {
            $score_list = $data['score_list'] ? $this->initScoreData($data['score_list']) : [];
            if ($score_list) {
                foreach ($score_list as $k => &$v) {
                    if (isset($score_list[$k - 1]) && ($v['max'] < $score_list[$k - 1]['max'] || $v['min'] < $score_list[$k - 1]['min'] || $v['min'] < $score_list[$k - 1]['max'])) {
                        throw $this->exception('保存失败，等级分数存在冲突！');
                    }
                    if ($v['max'] <= $v['min']) {
                        throw $this->exception('保存失败，等级分数区间错误！');
                    }
                    $v['level'] = $k + 1;
                    $v['entid'] = 1;
                }
                app()->get(AssessScoreService::class)->bachAddOrSave(1, $score_list);
            }
            return true;
        });
    }

    /**
     * 获取绩效审核配置信息.
     * @return array
     * @throws BindingResolutionException
     */
    public function getVerifyConfig()
    {
        $is_superior = sys_config('assess_verify_superior');
        $is_appoint  = sys_config('assess_verify_appoint');
        $staff       = sys_config('assess_verify_staff');
        $staff       = $staff ? explode(',', $staff) : [];
        $staff       = app()->get(AdminService::class)->select(['id' => $staff], ['name', 'avatar', 'id']);
        return compact('is_superior', 'is_appoint', 'staff');
    }

    /**
     * 格式化评分数据.
     * @param mixed $data
     * @return mixed
     */
    private function initScoreData($data)
    {
        $max = array_column($data, 'max');
        array_multisort($max, SORT_ASC, SORT_NUMERIC, $data);
        return $data;
    }
}
