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

namespace App\Http\Service\Package;

use App\Constants\CommonEnum;
use App\Http\Dao\Package\UpgradeDao;
use App\Http\Dao\Package\UpgradeRecordDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

class UpgradeService extends BaseService
{
    private UpgradeRecordDao $recordDao;

    public function __construct(UpgradeDao $dao, UpgradeRecordDao $recordDao)
    {
        $this->dao       = $dao;
        $this->recordDao = $recordDao;
    }

    public function upgradeList($where, $field, $page, $limit, $sort, $with)
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    public function recordList($where, $field, $page, $limit, $sort, $with)
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->recordDao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->recordDao->count($where);
        return $this->listData($list, $count);
    }

    public function upgradeInfo($where, $field = ['*'])
    {
        return toArray($this->dao->get($where, $field));
    }

    /**
     * 获取下载地址
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function download(string $code, string $key, array $field = ['*'])
    {
        $info = toArray($this->dao->get([
            'code' => $code,
            'key'  => $key,
        ], $field));
        if ($info) {
            return [
                'url'  => $info['url'],
                'name' => $info['name'],
            ];
        }
        throw $this->exception('未找到相关版本信息');
    }

    public function getUpgradeVersion($versionCode, $appId, $origin = CommonEnum::ORIGIN_SAAS)
    {
        $oldVersion = toArray($this->dao->get(['code' => $versionCode, 'origin' => $origin]));
        if (! $oldVersion) {
            throw $this->exception('您当前版本不符');
        }
        do {
            ++$oldVersion['code'];
            $newVersion = toArray($this->dao->get(['code' => $oldVersion['code'], 'origin' => $origin, 'status' => 1]));
        } while (! $newVersion && ($oldVersion['code'] - $versionCode < 100));
        if ($newVersion) {
            return [
                'upgrade'     => $newVersion['types'],
                'pathAndroid' => url('upgrade/package?code=' . $newVersion['code'] . '&key=' . $newVersion['key']),
                'pathIos'     => 'itms-apps://itunes.apple.com/cn/app/id' . $appId,
            ];
        }
        throw $this->exception('未找到新的升级包');
    }
}
